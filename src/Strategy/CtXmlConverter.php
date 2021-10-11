<?php

namespace App\Strategy;

use App\Strategy\StrategyInterface;

use Monolog\Logger;
use SimpleXMLElement;
use XMLReader;

class CtXmlConverter implements StrategyInterface
{
    private ConfigObject $config;
    private array $can_process_mimetype = ['application/xml', 'text/xml'];

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
        return('doing CT XML conversion');

        $return_arr = array();
        $countIx = 0;
        $target_elements = $this->getGeraet($data.geraet)->getParametersSelected();
        $xml = new XMLReader();
        $xml->open($data.filepath);

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

