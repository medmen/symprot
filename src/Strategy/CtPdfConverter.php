<?php

namespace App\Strategy;

use App\Entity\Parameter;
use App\Entity\Config;
use App\Repository\ParameterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use App\Strategy\StrategyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use KubAT\PhpSimple\HtmlDomParser;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use TonchikTm\PdfToHtml\Pdf;

class CtPdfConverter implements StrategyInterface
{
    private array $can_process_mimetype = ['application/pdf'];
    private $entityManager, $params, $logger, $kernel, $target_params;
    private $filepath; // holds full path to input pdf

    public function __construct(EntityManagerInterface $entityManager, ContainerBagInterface $params, LoggerInterface $logger, KernelInterface $kernel)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->logger = $logger;
        $this->kernel = $kernel->getProjectDir();
    }

    public function canProcess($data)
    {
        return (
            is_object($data) and
            $data->geraet == 'CT' and
            in_array($data->mimetype, $this->can_process_mimetype)
        );
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

            return(range($start, $end));
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

        // if nothing fits, assume we process everything
        return(range(1, $max));
    }

    public function process($data)
    {
        /**
         * So far i could not find a PDF file to implement this parser
         * if you find one, feel free to commit.
         * Probably the MrtPdfConverter can guide you...
         */
        return (serialize(array('error'=>'No parser exists (yet) for CT protocols in PDF')));


        // get all parameters we selected for chosen geraet
        $target_elements = $this->entityManager
            ->getRepository(Parameter::class)
            ->findSelectedbyGeraetName($data->geraet);

        // get the config
        $config = $this->entityManager
            ->getRepository(Config::class)
            ->find(1);
        // ->findOneBy(array('selected' => true));

        if(false == (is_object($config) or count((array)$config) < 1)) {
            $config = new Config();
        }

        $this->config = $config->getDefaults();

        foreach($target_elements as $param) {
            // reduce parameters to nameonly, turn to lowercase
            $target_params[] = strtolower($param->getParameterName());
        }
        // store target params in object so we can retrieve from other functions
        $this->target_params = $target_params;

        $this->logger->info('doing CT PDF conversion with parameters '.implode(' | ', $target_params));

        // get paths
        $protocol_path = $this->params->get('app.path.protocols');
        $target_path = $this->kernel.'/public'.$protocol_path;
        $this->filepath = $this->kernel.'/public'.$data->filepath;

        $return = array();

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
                ]
            ]);

        $pdfInfo = $pdf->getInfo();
        if(!is_array($pdfInfo) or (count($pdfInfo) < 1)) {
            $this->logger->critical($target_path.' contains no valid file');
            throw new FileNotFoundException($target_path.' contains no valid file');
        }

        $numofPages = $pdf->countPages();

        $limits = $this->config->getLimitPages();
        if(!isset($limits)) {
            $limits = 0;
        }

        $pages = $this->get_limits($limits, $numofPages);

        $html = $pdf->getHtml();
        foreach ($pages as $pagenumber) {
            $this->logger->info('converting page ' . $pagenumber);
            $page = $html->getPage($pagenumber);
            $page_extract = $this->convert_for_CT($page);
            $return = array_merge($return, $page_extract);
        }

        // save output to file
        $target_file_parts = pathinfo($this->filepath);
        $target_file = $target_file_parts['dirname']. DIRECTORY_SEPARATOR .$target_file_parts['filename'].'.txt';
        file_put_contents($target_file, serialize($return));

        return (serialize($return));
        // return (['success' => true]);
    }

    function convert_for_CT($html): array
    {
        $dom = HtmlDomParser::str_get_html($html);
        $output_array = array(); // make sure we return an array

        //Strip out all &nbsp; tags if any (otherwise they would destroy our parsers)
        foreach ($dom->find('td') as $td) {
            $td->innertext = str_replace('&nbsp;', '', $td->innertext);
        }

        // Match a numerated array of wanted fields
        //@TODO: this implementation sucks probably???
        $i = 0;
        foreach ($dom->find('tr th.c5') as $wanted_maybe) {
            //  check if the th.c5 contains a span.c4 tag, if yes its holds a new region and thus we need to skip  it...
            if ($wanted_maybe->find('span.c4')) {
                continue;
            }
            // otherwise we check its contents to see if it contains a "wanted" header-entry and note it's position in the array
            $wanted_maybe_trimmed = trim(strtolower($wanted_maybe->innertext));
            if (in_array($wanted_maybe_trimmed, $this->target_params)) {
                $wanted_entry[$wanted_maybe_trimmed] = $i;
            }
            ++$i;
        }

        // extract the region/protocol/sequence
        foreach ($dom->find('span.c3') as $region_now) {
            $this->logger->debug("REGION is $region_now in dom->find");
            $region_now = $region_now->innertext;
            $is_new_region = check_if_changed($region, $region_now);
            if ($is_new_region) {
                parse_region($is_new_region);
            }

            foreach ($dom->find('tr') as $row) {
                $protocol_now = $row->find('td.c6', 0);
                $protocol_now = $protocol_now->innertext;
                $is_new_protocol = check_if_changed($protocol, $protocol_now);
                if ($is_new_protocol) {
                    parse_protocol($is_new_protocol);
                }

                $sequence_now = $row->find('td.c6', 1);
                $sequence_now = $sequence_now->innertext;
                $is_new_sequence = check_if_changed($sequence, $sequence_now);
                if ($is_new_sequence) {
                    parse_sequence($is_new_sequence);
                }
                // parse the actual Data
                parse_row($row);
            }
        }

        $this->logger->debug("POST-CHECK is REGION " . $region . " PROTOCOL " . $protocol . " SEQUENCE " . $sequence);

        $dom->clear();
        return $output;
    }

    function check_if_changed($subject, $new_subject)
    {
        // strip included HTML-Tags, clean leading and trailing whitespaces
        $subject = trim(strip_tags($subject));
        $new_subject = trim(strip_tags($new_subject));
        if (true == $this->config->getDebug()) {
            var_dump($subject);
            var_dump($new_subject);
        }

        if (true == $this->config->getDebug()) {
            $this->logger->debug("DEBUG: check if subject: '" . $subject . "' matches new subject: '" . $new_subject . "'");
        }

        if (empty($subject) and empty($new_subject)) {
            if (true == $this->config->getDebug()) {
                $this->logger->debug("DEBUG: subject and new_subject empty --> returning false");
            }
            return (false);
        }

        if (empty($new_subject)) {
            if (true == $this->config->getDebug()) {
                $this->logger->debug("DEBUG: subject exists and new_subject empty --> returning false");
            }
            return (false);
        }

        if (empty($subject)) {
            if (true == $this->config->getDebug()) {
                $this->logger->debug("DEBUG: subject empty and new_subject exists --> returning new_subject");
            }
            return ($new_subject);
        }

        if ($subject != $new_subject) {
            if (true == $this->config->getDebug()) {
                $this->logger->debug("DEBUG: subject and new_subject exist --> returning new_subject");
            }
            return ($new_subject);
        }

        return (false);
    }

    function parse_region($this_region)
    {
        $region = $this_region;
        if (true == $this->config->getDebug()) {
            $this->logger->debug("DEBUG: new region is '$region'");
        }
    }

    function parse_protocol($this_protocol)
    {
        global $protocol, $output, $conf_valid_entries, $conf_protomuncher_debug;
        // close table of previous protocol if there was one
        if (!empty($protocol)) {
            $output .= "\n\t</tbody>\n</table>\n</div>\n\n";
        }

        $protocol = $this_protocol;
        if ($conf_protomuncher_debug) {
            trigger_error("DEBUG: new protocol is '$protocol'", E_USER_NOTICE);
        }

        // beautify the anchor for HTML-compatibility, several escalation-steps are used
        //2nd: a div with an ID, this requires the first character to be a letter,
        // umlauts are not allowed
        $sonderzeichen = array("/ä/", "/ö/", "/ü/", "/Ä/", "/Ö/", "/Ü/", "/ß/", "/\s/");
        $replace = array("ae", "oe", "ue", "Ae", "Oe", "Ue", "ss", "_");
        $output .= '<div id="' . preg_replace($sonderzeichen, $replace, $protocol) . '">' . "\n";
        $output .= '<h3><a name="' . preg_replace($sonderzeichen, $replace, $protocol) . '">' . $protocol . "</a></h3>\n";
        $output .= "<table>\n\t<thead>\n\t\t<tr>\n\t\t\t\n";
        foreach ($conf_valid_entries as $th_field) {
            $output .= "<th>" . strtoupper($th_field) . "</th>";
        }
        $output .= "</tr>\n</thead>\n<tfoot><tr><td colspan=\"" . count($conf_valid_entries) . "\">Erklärungen</td></tr></tfoot>\n<tbody>\n\n";
    }

    function parse_sequence($this_sequence)
    {
        global $sequence, $output, $conf_protomuncher_debug, $conf_protomuncher_debug;
        // close table-row of previous sequence if there was one
        /*  if(!empty($sequence))
            {
                $output.= "\n\t\t</tr>\n";
            }
        */
        $sequence = $this_sequence;
        if ($conf_protomuncher_debug) {
            trigger_error("DEBUG: new sequence is '$sequence'", E_USER_NOTICE);
        }
    }

    function parse_row($this_row)
    {
        global $conf_valid_entries, $output, $conf_protomuncher_debug, $wanted_entry, $temp_arr;

        if ($conf_protomuncher_debug) {
            trigger_error("DEBUG: parsing row '$this_row'", E_USER_NOTICE);
        }

        foreach ($this_row->find('td.c6') as $potential_hit) {
            $potential_hits_array[] = trim(strip_tags($potential_hit->innertext)); // should give an ordered array
        }

        foreach ($conf_valid_entries as $valid_entry) {
            // exapmle: if $valid_entry = 'FOV', then $wanted_entry[$valid_entry]  == $wanted_entry['FOV'] == some integer (e.g. 4)
            $array_position = $wanted_entry[$valid_entry];
            if (empty($potential_hits_array[$array_position])) {
                if (empty($temp_arr[$array_position]) or $temp_arr[$array_position] == '<td>---</td>') {
                    $temp_arr[$array_position] = '<td>---</td>';
                }
            } else {
                if (empty($temp_arr[$array_position]) or $temp_arr[$array_position] == '<td>---</td>') {
                    $temp_arr[$array_position] = "<td>$potential_hits_array[$array_position]</td>";
                } else {
                    ksort($temp_arr);
                    $output .= "<tr>" . implode($temp_arr) . "</tr>\n";
                    $temp_arr = array();
                    $temp_arr[$array_position] = "<td>$potential_hits_array[$array_position]</td>";
                }
            }
            /*
                ksort($temp_arr);
                print_r($temp_arr); echo "<br>\n";
            */
        }
    }

    function trimall($str, $charlist = " \t\n\r\0\x0B")
    {
        return str_replace(str_split($charlist), '', $str);
    }
}
