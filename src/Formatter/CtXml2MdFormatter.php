<?php
declare(strict_types = 1);

namespace App\Formatter;

use Doctrine\ORM\EntityManagerInterface;
use http\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CtXml2MdFormatter implements FormatterStrategyInterface
{
    private $logger, $can_process_mimetype, $valid_formats;
    public function __construct(LoggerInterface $procLogger)
    {
        $this->logger = $procLogger;
        $this->can_process_mimetype = ['application/xml', 'text/xml'];
        $this->format = 'md';
    }

    public function canFormat($data, $format)
    {
        return (
            is_object($data) and
            $data->geraet == 'CT' and
            in_array($data->mimetype, $this->can_process_mimetype) and
            $format == $this->format
        );
    }

    public function format($serialized_payload, $format)
    {
        $proto_arr = unserialize($serialized_payload);
        // $count = count($proto_arr, COUNT_RECURSIVE);
        $formatted = '';
        $bodysize = '';
        $region = '';
        $protocol = '';
        $series = '';

        $thead = "<tr><th>^ bodysize</th><th> ^ Region</th><th> ^ Protocol</th><th> ^ Serie ^ </th>";
        $thead_parameters_already_set = false;
        $cnt = 0;
        foreach ($proto_arr as $row) {
            if ($row['bodysize'] != $bodysize) {
                $bodysize = $row['bodysize'];
                $formatted .= "<tr>\n<td><b>| $bodysize | </b></td>\n";
            } else {
                $formatted .= "<tr>\n<td>| |</td>\n";
            }

            if ($row['region'] != $region) {
                $region = $row['region'];
                $formatted .= "<td><b>$region | </b></td>\n";
            } else {
                $formatted .= "<td>|</td>\n";
            }

            if ($row['protocol'] != $protocol) {
                $protocol = $row['protocol'];
                $formatted .= "<td><b>$protocol | </b></td>\n";
            } else {
                $formatted .= "<td>|</td>\n";
            }

            if ($row['series'] !== $series) {
                $series = $row['series'];
                $formatted .= "<td><b>$series | </b></td>\n";
            } else {
                $formatted .= "<td>|</td>\n";
            }

            foreach ($row['param'] as $paramname => $paramval) {
                $formatted .= "<td>$paramval | </td>\n";

                if (false === $thead_parameters_already_set) {
                    $thead .= "<th> $paramname ^ </th>\n";
                }
            }
            $thead_parameters_already_set = true;
            $formatted .= "</tr>\n";
            $thead .= "</tr>\n";

            $cnt++;
        }


        return ("<table class='table-dark table-responsive output-table'><thead>$thead</thead></thead><tbody>$formatted</tbody></table>");
    }
}