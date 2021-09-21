<?php

declare(strict_types=1);

namespace App\Service;

use Monolog\Logger;
use SimpleXMLElement;
use XMLReader;

class MrtXmlConverter implements IConverter
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
        while ($xml->read() && $xml->name != 'PrintProtocol') {
        }

        while ($xml->name == 'PrintProtocol') {
            $element = new SimpleXMLElement($xml->readInnerXML()); //

            $proto_path = explode('\\', strval($element->SubStep->ProtHeaderInfo->HeaderProtPath));

            $prod = array(
                'region' => $proto_path[3],
                'protocol' => $proto_path[4] . '-' . $proto_path[5],
                'sequence' => $proto_path[6],
            );

            // read potential values from protocol header
            $ta_pm_voxel_pat_snr = preg_split('/\s+/', strval($element->SubStep->ProtHeaderInfo->HeaderProperty));
            $potential['ta'] = $ta_pm_voxel_pat_snr[1] . ' min';
            $potential['pm'] = $ta_pm_voxel_pat_snr[3];
            $potential['voxel'] = $ta_pm_voxel_pat_snr[5];
            $potential['pat'] = $ta_pm_voxel_pat_snr[7];
            $potential['snr'] = $ta_pm_voxel_pat_snr[10];

            foreach ($potential as $key => $val) {
                if (in_array($key, $target_elements)) {
                    $prod[$key] = $val;
                }
            }

            foreach ($element->SubStep->Card as $card) {
                foreach ($card->ProtParameter as $seq_property) {
                    if (in_array(strtolower(strval($seq_property->Label)), $target_elements)) {
                        $label = strval($seq_property->Label);
                        $value = strval($seq_property->ValueAndUnit);
                        $prod[$label] = $value;
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
