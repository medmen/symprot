<?php

declare(strict_types=1);

namespace App\Strategy;

use App\Entity\Config;
use App\Entity\Parameter;
use App\Repository\ParameterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use XMLReader;

class MrtXmlConverter implements StrategyInterface
{
    private Config $config;
    private EntityManagerInterface $entityManager;
    private ContainerBagInterface $params;
    private LoggerInterface $logger;
    private ?string $kernel = null;
    private array $target_params = [];
    private string $filepath;
    private string $format;
    private string $appUploadsDir;

    private ParameterRepository $parameterRepository;

    private array $can_process_mimetype = ['application/xml', 'text/xml'];

    public function __construct(EntityManagerInterface $entityManager, ContainerBagInterface $params, LoggerInterface $procLogger, KernelInterface $kernelif, string $appUploadsDir)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->logger = $procLogger;
        $this->kernel = $kernelif->getProjectDir();
        $this->appUploadsDir = $appUploadsDir;
    }

    public function canProcess($data)
    {
        return
            is_object($data)
            and $data->geraet == 'MRT_Siemens'
            and in_array($data->mimetype, $this->can_process_mimetype)
        ;
    }

    private function trim_with_star(string $value)
    {
        return trim(str_replace('*', '', $value));
    }

    public function process($data, ?callable $onProgress = null): string
    {
        // return(serialize(array('error' => 'MRT XML conversion is not_yet_implemented')));

        // get all parameters we selected for chosen geraet (unless already preset)
        $target_elements = [];
        if (empty($this->target_params)) {
            $target_elements = $this->entityManager
                ->getRepository(Parameter::class)
                ->findSelectedbyGeraetName($data->geraet);
        }

        // get the config
        $config = $this->entityManager
            ->getRepository(Config::class)
            ->find(1);
        // ->findOneBy(array('selected' => true));

        if (!$config instanceof Config) {
            $config = new Config(); // load default config
        }

        $this->config = $config->getDefaults();

        if (empty($this->target_params)) {
            $target_params = array();
            foreach ($target_elements as $param) {
                // reduce parameters to nameonly, turn to lowercase
                $name = null;
                if (is_object($param) && method_exists($param, 'getParameterName')) {
                    $name = $param->getParameterName();
                }
                if (null !== $name && $name !== '') {
                    $target_params[] = strtolower((string)$name);
                }
            }
            // store target params in object so we can retrieve from other functions
            $this->target_params = $target_params;
        }

        // clean up
        unset($target_elements, $target_params);

        // get paths: resolve full path from uploads dir and provided filename
        $this->filepath = rtrim($this->appUploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $data->filename;

        // check if file exists!
        if (!file_exists($this->filepath)) {
                $this->logger->error('Target XML file not found at '.$this->filepath);
                 return(serialize(array('error' => 'No file found for MRT XML conversion')));
        }

        $this->logger->info('doing MRT XML conversion with parameters '.implode(' | ', $this->target_params));

        $return_arr = [];
        $proto_cnt = 0;
        $last_sequence = '';
        $last_protocol = '';
        $totalSize = @filesize($this->filepath) ?: 0;
        $lastReported = -1;
        // init onProgress callback, this writes back progress status
        if (null !== $onProgress && $totalSize > 0) {
            $onProgress(0);
        }

        $fp = fopen($this->filepath, 'rb');
        if (false === $fp) {
            return serialize(['error' => 'Could not open XML file ' . $this->filepath]);
        }

        $xml = new \XMLReader();
        $success = $xml->open($this->filepath);
        if (!$success) {
            return serialize(['error' => 'Could not open XML file '.$this->filepath]);
        }

        /*
         * To use xmlReader easily we have to make sure we parse at the outermost level of repeating elements.
         * This is because xmlReaders next() option does not behave as one would think by intuition
         */
        while ($xml->read() && $xml->name != 'PrintProtocol') {
            // doeing nothin here skips to the first PrintProtocol element
        }

        while ($xml->name == 'PrintProtocol') {

            // Report progress via ftell/totalSize
            if ($totalSize > 0) {
                $pos = ftell($fp);
                if ($pos !== false) {
                    $percent = (int) floor(($pos / $totalSize) * 100);
                    if ($percent !== $lastReported && $percent <= 100) {
                        $lastReported = $percent;
                        if (null !== $onProgress) { $onProgress($percent); }
                    }
                }
            }

            $element = new \SimpleXMLElement($xml->readInnerXML());
            $element_id = (string) $element['ID'] ?: '0815';

            // Step 1: extract protocol info from header
            if(!isset($element->SubStep->ProtHeaderInfo->HeaderProtPath)) {
                // no header found, log and continue
                $this->logger->warning('ne header found in current Protocol with ID: '.$element_id.', skipping..');
                $xml->next('PrintProtocol');
            }

            $header = strval($element->SubStep->ProtHeaderInfo->HeaderProtPath);

            /**
            if(strpos(strval($header), '\\') == false) {
                $this->logger->warning('malformed header found (without backslash) in current Protocol with ID: '.$element_id.', skipping..');
                $xml->next('PrintProtocol');
            }
             */

            $proto_path = explode('\\', strval($element->SubStep->ProtHeaderInfo->HeaderProtPath));
            $region = trim((string)($proto_path[3] ?? ''));
            $actual_sequence = trim(str_replace('*', '', (string)($proto_path[6] ?? '')));
            if ($actual_sequence == 'localizer' and $last_sequence != 'localizer') {
                ++$proto_cnt;
            }

            $actual_protocol = trim(((string)($proto_path[4] ?? '')) . '-' . ((string)($proto_path[5] ?? '')));
            if ($actual_protocol !== $last_protocol) {
                $proto_cnt = 1;
            }

            $protocol = trim(((string)($proto_path[4] ?? '')) . '-' . ((string)($proto_path[5] ?? ''))) . '-' . $proto_cnt;

            $prod = [
                'region' => $region,
                'protocol' => $protocol,
                'sequence' => $actual_sequence,
            ];

            // done parsing the Protocol name stuff, set last_sequence for next iteration
            $last_sequence = $actual_sequence;
            $last_protocol = $actual_protocol;
            $count_matches = 0; // count how many parameters we found in this protocol

            // Step 2: read potential values from protocol header
            $header = trim(strval($element->SubStep->ProtHeaderInfo->HeaderProperty));
            // here healthineers get rough on us - we need to split a string by colons and white spaces :(
            $header = str_replace('::', ':', $header); // strip double colon :: to 1 colon!
            $header = str_replace(': ', ':', $header); // trim white space after colon!
            $header = preg_replace('/\s+/', ' ', $header); // strip multi blank spaces to 1
            // $header_pairs = preg_split('/(\w+):([^:]+)(?: |$)/', $header); // does not work with special chars??
            $parts = preg_split('/\s+/', $header);
            foreach ($parts as $part) {
                if (stristr($part, ':')) {
                    [$key, $val] = explode(':', $part, 2);
                    $prod[$key] = $val; // collect all, will filter/order later
                    if (in_array(strtolower($key), $this->target_params)) {
                        ++$count_matches; // only increment for target params
                    }
                }
            }

            // Step 3: walk through each sequence and search for matching parameters
            foreach ($element->SubStep->Card as $card) {
                foreach ($card->ProtParameter as $seq_property) {
                    $label = strval($seq_property->Label);
                    $value = strval($seq_property->ValueAndUnit);
                    $prod[$label] = $value; // collect all
                    if (in_array(strtolower($label), $this->target_params)) {
                        ++$count_matches; // count only target params
                    }
                    if($count_matches == count($this->target_params)) {
                        break; // we found all parameters, no need to continue
                    }
                }
            }

            // Reorder collected parameters according to admin-defined order ($this->target_params)
            $meta = [
                'region' => $prod['region'] ?? null,
                'protocol' => $prod['protocol'] ?? null,
                'sequence' => $prod['sequence'] ?? null,
            ];
            $ordered = [];
            foreach ($this->target_params as $lowerName) {
                // Find original key case-insensitively in $prod
                foreach ($prod as $k => $v) {
                    if (in_array($k, ['region','protocol','sequence'], true)) { continue; }
                    if (strtolower((string)$k) === $lowerName) {
                        $ordered[$k] = $v;
                        break;
                    }
                }
            }
            $return_arr[] = $meta + $ordered;
            $xml->next('PrintProtocol');
            unset($element);
        }

        // clean up
        if (is_resource($fp)) { fclose($fp); }
        if (null !== $onProgress && $totalSize > 0) { $onProgress(100); }

        return serialize($return_arr);
    }
}
