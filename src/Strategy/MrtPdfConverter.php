<?php

namespace App\Strategy;

use App\Entity\Config;
use App\Entity\Parameter;
use Doctrine\ORM\EntityManagerInterface;
use KubAT\PhpSimple\HtmlDomParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\KernelInterface;
use TonchikTm\PdfToHtml\Pdf;

class MrtPdfConverter implements StrategyInterface
{
    private array $can_process_mimetype = ['application/pdf'];

    private $kernel;

    private $target_params;

    private $filepath; // holds full path to input pdf
    private string $appUploadsDir;

    private Config $config;

    public function __construct(private EntityManagerInterface $entityManager, private ContainerBagInterface $params, private LoggerInterface $logger, KernelInterface $kernel, string $appUploadsDir)
    {
        $this->kernel = $kernel->getProjectDir();
        $this->appUploadsDir = $appUploadsDir;
    }

    /**
     * @return array of integers holding page numbers
     */
    public function get_limits($limits, $max): array
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

            return range($start, $end);
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

            return array_unique($items); // remove duplicate values
        }

        // assume its a single number
        if (is_int($limits)) {
            if ($limits > $max or 0 == $limits) {
                $limits = $max;
            }

            return [$limits];
        }

        // if nothing fits, assume we process everything
        return range(1, $max);
    }

    public function canProcess($data)
    {
        return
            is_object($data)
            and $data->geraet == 'MRT_Siemens'
            and in_array($data->mimetype, $this->can_process_mimetype)
        ;
    }

    public function process($data)
    {
        // get all parameters we selected for chosen geraet
        $target_elements = $this->entityManager
            ->getRepository(Parameter::class)
            ->findSelectedbyGeraetName($data->geraet);

        // get the config
        $config = $this->entityManager
            ->getRepository(Config::class)
            ->find(1);
        // ->findOneBy(array('selected' => true));

        if (false == (is_object($config) or count((array) $config) < 1)) {
            $config = new Config();
        }

        $this->config = $config->getDefaults();

        foreach ($target_elements as $param) {
            // reduce parameters to nameonly, turn to lowercase
            $target_params[] = strtolower($param->getParameterName());
        }
        // store target params in object so we can retrieve from other functions
        $this->target_params = $target_params;

        $this->logger->info('doing MRT PDF conversion with paraeters '.implode(' | ', $target_params));

        // resolve input pdf path from uploads dir and provided filename
        $this->filepath = rtrim($this->appUploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $data->filename;

        $return = [];

        $pdf = new Pdf($this->filepath,
            [
                'pdftohtml_path' => '/usr/bin/pdftohtml -c',
                'pdfinfo_path' => '/usr/bin/pdfinfo',
                'generate' => [
                    'singlePage' => false, // we want separate pages
                    'imageJpeg' => false, // do not transform images
                    'ignoreImages' => true, // we need no images
                    'zoom' => 1.5, // scale pdf
                    'noFrames' => false, // we want separate pages
                ],
                'clearAfter' => true, // auto clear output dir (if removeOutputDir==false then output dir will remain)
                'removeOutputDir' => true, // remove output dir
                'outputDir' => '/tmp/'.uniqid(), // output dir
                'html' => [ // settings for processing html
                    'inlineCss' => false, // replaces css classes to inline css rules
                    'inlineImages' => false, // looks for images in html and replaces the src attribute to base64 hash
                    'onlyContent' => true, // takes from html body content only
                ],
            ]);

        $pdfInfo = $pdf->getInfo();
        if (!is_array($pdfInfo) or (count($pdfInfo) < 1)) {
            $this->logger->critical($this->filepath.' contains no valid file');
            throw new FileNotFoundException($this->filepath.' contains no valid file');
        }

        $numofPages = $pdf->countPages();

        $limits = $this->config->getLimitPages();
        if (!isset($limits)) {
            $limits = 0;
        }

        $pages = $this->get_limits($limits, $numofPages);

        $html = $pdf->getHtml();
        foreach ($pages as $pagenumber) {
            $this->logger->info('converting page '.$pagenumber);
            $page = $html->getPage($pagenumber);
            $page_extract = $this->convert_for_MRT($page);
            $return = array_merge($return, $page_extract);
        }

        // save output to file
        $target_file_parts = pathinfo($this->filepath);
        $target_file = $target_file_parts['dirname'].DIRECTORY_SEPARATOR.$target_file_parts['filename'].'.txt';
        file_put_contents($target_file, serialize($return));

        return serialize($return);
        // return (['success' => true]);
    }

    public function convert_for_MRT($html): array
    {
        $dom = HtmlDomParser::str_get_html($html);
        $output_array = []; // make sure we return an array
        $region_proto_sequence = false;

        foreach ($dom->find('div p.ft05') as $element) { // Strip out Comments
            $converted = false;
            // Special: poppler puts some wanted values in p.ft05 element, catch those
            foreach ($this->target_params as $wanted) {
                if (preg_match('#\b'.preg_quote($wanted, '#').'\b#i', $element->innertext)) {
                    $this->logger->debug("DEBUG: cought bogus ft5 element $wanted in $element->innertext");
                    // cought a target element, turn into p.ft03 element with altered name
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
            $protocol = $protocol_elements[4].'_'.$protocol_elements[5];
            $region = $protocol_elements[3];
            $region_proto_sequence = $region.'_'.$protocol.'_'.$sequence;

            $output_array[$region_proto_sequence]['region'] = $region;
            $output_array[$region_proto_sequence]['protocol'] = $protocol;
            $output_array[$region_proto_sequence]['sequence'] = strtoupper(str_replace('_', ' ', $sequence));

            // explode the sequence-name, it usually holds hints for measurment direction
            // TODO: find a more adequate way to extract that info
            $seq_parts = explode('_', $sequence);
            foreach ($seq_parts as $part) {
                if (in_array(strtolower(trim($part)), ['tra', 'sag', 'cor'])) {
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
                $this->logger->debug(' measurement time is ..'.trim($parts[1])."<br>\n");
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

            if (in_array($unvalidated_entry, $this->target_params)) {
                $actual_hit = $unvalidated_entry;
                $hit = 1;
                continue;
            }

            if (isset($hit) and 1 == $hit) {
                if (true == $this->config->getStripUnits()) {
                    $unvalidated_entry = strtok($unvalidated_entry, ' ');
                }
                $output_array[$region_proto_sequence][$actual_hit] = $unvalidated_entry;
                $this->logger->debug("DEBUG: $actual_hit is a hit containing $unvalidated_entry !<br>\n");
                $hit = 0;
                continue;
            }
        }

        $dom->clear();

        return $output_array;
    }
}
