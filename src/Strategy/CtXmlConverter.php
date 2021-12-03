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
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

use \SimpleXMLElement;
use \XMLReader;
use function PHPUnit\Framework\containsIdentical;

class CtXmlConverter implements StrategyInterface
{
    private array $can_process_mimetype = ['application/xml', 'text/xml'];
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

    public function process($data)
    {
        /**
         * Preparation
         */
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

        $this->logger->info('doing CT XML conversion with parameters '.implode(' | ', $target_params));

        // get paths
        $protocol_path = $this->params->get('app.path.protocols');
        $target_path = $this->kernel.'/public'.$protocol_path;
        $this->filepath = $this->kernel.'/public'.$data->filepath;

        $return_arr = array();
        $countIx = 0;

        /**
         * XML Parsing
         * we will use XMLReader for swift "streaming" of a potentially large file
         * Only parts of this file will be processed by SimpleXMLElement, which is much easier to use but memory hungry and slow
         */
        $xml = new XMLReader();
        $xml->open($this->filepath);

        /**
         * To use xmlReader easily we have to make sure we parse at the outermost level of repeating elements.
         * This is because xmlReaders next() option does not behave as one would think by intuition
         */
        while ($xml->read() && $xml->name != 'Folder') {
        }

        $prod = array();
        while ($xml->name == 'Folder') {
            $element = new SimpleXMLElement($xml->readOuterXML()); //

            // skip Siemens standard protocols
            if (stristr(strval($element->FolderName), 'siemens')) {
                continue;
            }

            $bodysize = '';
            $region = '';
            $protocol = '';
            $series = '';
            $prod = array();
            foreach ($element->Body as $body) {
                $bodysize = strval($body->BodySize);
                $prod['bodysize'] = $bodysize;

                if(@count($body->Region) < 1) {
                    continue;
                }
                foreach ($body->Region as $reg) {
                    $region = strval($reg->RegionName);
                    $prod['region'] = $region;

                    foreach ($reg->Protocol as $prot) {
                        $protocol = strval($prot->ProtocolName);
                        $prod['protocol'] = $protocol;

                        foreach ($prot->ScanEntry as $ser) {
                            $scantype = $ser->attributes(); // can be used to discriminate topo from scan
                            $series = strval($ser->ReconJob->SeriesDescription);
                            $prod['series'] = $series;

                            foreach ($ser->children() as $potential) {
                                if ($scantype->ScanType = "Topo") {
                                    // do stuff only valid for Topo
                                }

                                if (in_array(strtolower($potential->getName()), $target_params)) {
                                    $param_name = strtolower($potential->getName());
                                    $param_value = strval(strtolower($potential));
                                    $prod['param'][$param_name] = $param_value;
                                }
                            }
                            $return_arr[] = $prod;
                        }
                    }
                }
            }
            $countIx++;
            $xml->next('Folder');
            unset($element);
        }

        // save output to file
        $target_file_parts = pathinfo($this->filepath);
        $target_file = $target_file_parts['dirname']. DIRECTORY_SEPARATOR .$target_file_parts['filename'].'.txt';
        file_put_contents($target_file, serialize($return_arr));

        return (serialize($return_arr));
    }
}

