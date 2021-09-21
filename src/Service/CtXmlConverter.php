<?php

namespace App\Service;

use Monolog\Logger;
use SimpleXMLElement;
use XMLReader;

class CtXmlConverter implements IConverter
{
    private string $input;
    private Logger $logger;
    private ConfigObject $config;

    //@TODO: add Config to constructor so we can change paths
    public function __construct(Logger $logger, ConfigObject $config)
    {
        $this->logger = $logger;
        $this->logger->notice('Logger is now Ready in class ' . __CLASS__);
        $this->config = $config;
    }

    public function setinput(string $input): void
    {
        $this->input = $input;
    }

    public function convert(): array
    {
        $return_arr = array();
        $countIx = 0;
        $target_elements = $this->config->getParameters();
        $xml = new XMLReader();
        $xml->open($this->input);

        /**
         * To use xmlReader easily we have to make sure we parse at the outermost level of repeating elements.
         * This is because xmlReaders next() option does not behave as one would think by intuition
         */
        while ($xml->read() && $xml->name != 'Folder') {
        }

        while ($xml->name == 'Folder') {
            $element = new SimpleXMLElement($xml->readInnerXML()); //

            // skip Siemens standard protocols
            if (stristr(strval($element->FolderName), 'siemens')) {
                continue;
            }

            $this->bodysize = strval($element->Body->BodySize);

            foreach ($element->Body->Region as $reg) {
                $region = strval($reg->RegionName);
                foreach ($reg->Protocol as $prot) {
                    $protocol = strval($prot->ProtocolName);
                    foreach ($prot->ScanEntry as $ser) {
                        $series = strval($ser->ReconJob->SeriesDescription);
                        foreach ($ser->children() as $potential) {
                        }
                    }
                }
            }

            $return_arr[] = $prod;
            $countIx++;
            $xml->next('PrintProtocol');
            unset($element);
        }
        return ($return_arr);
    }
}

