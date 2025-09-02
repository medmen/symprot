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

    private ParameterRepository $parameterRepository;

    private array $can_process_mimetype = ['application/xml', 'text/xml'];

    public function __construct(EntityManagerInterface $entityManager, ContainerBagInterface $params, LoggerInterface $procLogger, KernelInterface $kernelif)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->logger = $procLogger;
        $this->kernel = $kernelif->getProjectDir();
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

    public function process($data): string
    {
        // return(serialize(array('error' => 'MRT XML conversion is not_yet_implemented')));

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
            $config = new Config(); // load default config
        }

        $this->config = $config->getDefaults();

        $target_params = array();
        foreach ($target_elements as $param) {
            // reduce parameters to nameonly, turn to lowercase
            $target_params[] = strtolower($param->getParameterName());
        }
        // store target params in object so we can retrieve from other functions
        $this->target_params = $target_params;

        // clean up
        unset($target_elements, $target_params);

        // get paths
        $protocol_path = $this->params->get('app.path.protocols');
        $target_path = $this->kernel.'/public'.$protocol_path;
        $this->filepath = $this->kernel.'/public'.$data->filepath;

        $this->logger->info('doing MRT XML conversion with parameters '.implode(' | ', $this->target_params));

        $return_arr = [];
        $proto_cnt = 0;
        $last_sequence = '';
        $last_protocol = '';

        $xml = new \XMLReader();
        $xml->open($this->filepath);

        /*
         * To use xmlReader easily we have to make sure we parse at the outermost level of repeating elements.
         * This is because xmlReaders next() option does not behave as one would think by intuition
         */
        while ($xml->read() && $xml->name != 'PrintProtocol') {
        }

        while ($xml->name == 'PrintProtocol') {
            $element = new \SimpleXMLElement($xml->readInnerXML());

            // Step 1: extract protocol info from header
            $proto_path = explode('\\', strval($element->SubStep->ProtHeaderInfo->HeaderProtPath));
            $region = trim($proto_path[3]);
            $actual_sequence = trim(str_replace('*', '', $proto_path[6]));
            if ($actual_sequence == 'localizer' and $last_sequence != 'localizer') {
                ++$proto_cnt;
            }

            $actual_protocol = trim($proto_path[4].'-'.$proto_path[5]);
            if ($actual_protocol !== $last_protocol) {
                $proto_cnt = 1;
            }

            $protocol = trim($proto_path[4].'-'.$proto_path[5]).'-'.$proto_cnt;

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
                    if (in_array(strtolower($key), $this->target_params)) {
                        $prod[$key] = $val;
                        ++$count_matches; // count how many parameters we found in this protocol
                    }
                }
            }

            // Step 3: walk through each sequence and search for matching parameters
            foreach ($element->SubStep->Card as $card) {
                foreach ($card->ProtParameter as $seq_property) {
                    if (in_array(strtolower(strval($seq_property->Label)), $this->target_params)) {
                        $label = strval($seq_property->Label);
                        $value = strval($seq_property->ValueAndUnit);
                        $prod[$label] = $value;
                        ++$count_matches; // count how many parameters we found in this protocol
                    }
                    if($count_matches == count($this->target_params)) {
                        break; // we found all parameters, no need to continue
                    }
                }
            }

            $return_arr[] = $prod;
            $xml->next('PrintProtocol');
            unset($element);
        }

        return serialize($return_arr);
    }
}
