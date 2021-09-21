<?php

namespace App\Service;

use KubAT\PhpSimple\HtmlDomParser;
use Monolog\Logger;
use TonchikTm\PdfToHtml\Pdf;

class MrtPdfConverter implements IConverter
{
    private $modality, $input, $logger, $config;

    //@TODO: add COnfig to constructor so we can change paths
    function __construct(Logger $logger, ConfigObject $config)
    {
        $this->logger = $logger;
        $this->logger->notice('Logger is now Ready in class ' . __CLASS__);
        $this->config = $config;
    }

    function setinput(string $input): void
    {
        $this->input = $input;
    }

    /**
     * @param $limits
     * @param $max
     * @return array of integers holding page numbers
     */
    function get_limits($limits, $max): array
    {
        // see if limits holds a range
        if (is_string($limits) and stristr($limits, '-')) {
            [$start, $end] = explode('-', $limits);
            $start = intval(trim($start));
            $end = intval(trim($end));

            // sanity checks
            if ($start < 0) {
                $start = $start * -1;
            }

            if ($end < 0) {
                $end = $end * -1;
            }

            if ($end > $max) {
                $end = $max;
            }

            if ($start > $end) {
                // switch numbers
                $new_end = $start;
                $start = $end;
                $end = $new_end;
            }

            return (range($start, $end));
        }

        if (is_string($limits) and stristr($limits, ',')) {
            $items = array_map(
                function ($value) {
                    return intval(trim($value)); // trim each value and turn into int
                },
                explode(',', $limits)
            );
            foreach ($items as $item) {
                if ($item > $max) {
                    unset($item);
                }
            }
            return (array_unique($items)); // remove duplicate values
        }

        // assume its a single number
        if (is_int($limits)) {
            if ($limits > $max or 0 == $limits) {
                $limits = $max;
            }
            return (array($limits));
        }
    }

    function convert(): array
    {
        $return = array();
        $pdf = new Pdf($this->input, [
            'pdftohtml_path' => '/usr/bin/pdftohtml -c',
            'pdfinfo_path' => '/usr/bin/pdfinfo',
            'generate' => [
                'ignoreImages' => true,
            ],
            'outputDir' => dirname(__DIR__) . '/uploads/' . uniqid(), // output dir
            'html' => [ // settings for processing html
                'inlineCss' => false, // replaces css classes to inline css rules
                'inlineImages' => false, // looks for images in html and replaces the src attribute to base64 hash
                'onlyContent' => true, // takes from html body content only
            ]
        ]);

        $pdfInfo = $pdf->getInfo();
        $countPages = $pdf->countPages();

        $helpers = $this->config->getHelpers();
        if (isset($helpers['limit_files'])) {
            $limits = $helpers['limit_files'];
        } else {
            $limits = 0;
        }

        $pages = $this->get_limits($limits, $countPages);

        $html = $pdf->getHtml();
        foreach ($pages as $pagenumber) {
            $this->logger->notice('converting page ' . $pagenumber);
            $page = $html->getPage($pagenumber);
            $page_extract = $this->convert_for_MRT($page);
            $return = array_merge($return, $page_extract);
        }

        return ($return);
    }

    function convert_for_MRT($html): array
    {
        $dom = HtmlDomParser::str_get_html($html);
        $output_array = array(); // make sure we return an array
        $region_proto_sequence = false;

        foreach ($dom->find('div p.ft05') as $element) { // Strip out Comments
            $converted = false;
            // Special: poppler puts some wanted values in p.ft05 element, catch those
            foreach ($this->config->getParameters() as $wanted) {
                if (preg_match('#\b' . preg_quote($wanted, '#') . '\b#i', $element->innertext)) {
                    $this->logger->debug("DEBUG: cought bogus ft5 element $wanted in $element->innertext");
                    //cought a target element, turn into p.ft03 element with altered name
                    $element->class = 'ft03';
                    $element->innertext = $wanted;
                    $converted = 1;
                    break;
                }
            }

            if (false == $converted) {
                $element->outertext = '';
                $this->logger->debug("Stripped 1 Comment..<br>\n");
            }
        }

        foreach ($dom->find('div p.ft01') as $protocol_full) {
            // extract the region/protocol/sequence
            $rps = $protocol_full->innertext;
            $protocol_elements = explode('\\', $rps);

            if (count($protocol_elements) < 6) {
                continue; // skip loop, this is no full protocol
            }
            $this->logger->debug("parsing 1 protocol..<br>\n");

            $sequence = $protocol_elements[6];
            $protocol = $protocol_elements[4] . '_' . $protocol_elements[5];
            $region = $protocol_elements[3];
            $region_proto_sequence = $region . '_' . $protocol . '_' . $sequence;

            $output_array[$region_proto_sequence]['region'] = $region;
            $output_array[$region_proto_sequence]['protocol'] = $protocol;
            $output_array[$region_proto_sequence]['sequence'] = strtoupper(str_replace('_', ' ', $sequence));

            // explode the sequence-name, it usually holds hints for measurment direction
            // TODO: find a more adequate way to extract that info
            $seq_parts = explode('_', $sequence);
            foreach ($seq_parts as $part) {
                if (in_array(strtolower(trim($part)), array('tra', 'sag', 'cor'))) {
                    $output_array[$region_proto_sequence]['direction'] = strtolower(trim($part));
                    break;
                }
            }

            if (!isset($output_array[$region_proto_sequence]['direction'])) {
                $output_array[$region_proto_sequence]['direction'] = '';
            }
        }

        foreach ($dom->find('p.ft02') as $arrival_time) {
            if (false == $region_proto_sequence) {
                continue;
            }
            $this->logger->debug("extracting measurement time from $arrival_time->innertext ..<br>\n");
            // innertext holds multiple strings in "name: value" format, separated by multiple blank spaces
            // if we split by 1 or more blank spaces, first item is 'TA', second item holds time value
            $parts = preg_split("/\s+/", $arrival_time->innertext);
            if ('TA:' == trim($parts[0])) {
                $output_array[$region_proto_sequence]['messdauer'] = trim($parts[1]);
                $this->logger->debug(" measurement time is .." . trim($parts[1]) . "<br>\n");
            }
            break; // ne need to search for other occurrences
        }

        foreach ($dom->find('p.ft03') as $potential_hit) {
            if (false == $region_proto_sequence) {
                continue;
            }
            $unvalidated_entry = trim(str_replace('&#160;', '', strtolower($potential_hit->innertext)));
            $unvalidated_entry = str_replace('.', ',', $unvalidated_entry); // german decimal separator
            $this->logger->debug("DEBUG: checkin if $unvalidated_entry is in valid entries ...<br>\n");

            if (in_array($unvalidated_entry, $this->config->getParameters())) {
                $actual_hit = $unvalidated_entry;
                $hit = 1;
                continue;
            }

            if (isset($hit) and 1 == $hit) {
                if ($this->config->getHelperByName('stripunits')) {
                    $unvalidated_entry = strtok($unvalidated_entry, " ");
                }
                $output_array[$region_proto_sequence][$actual_hit] = $unvalidated_entry;
                $this->logger->debug("DEBUG: $actual_hit is a hit containing $unvalidated_entry !<br>\n");
                $hit = 0;
                continue;
            }
        }

        $dom->clear();
        return ($output_array);
    }
}
