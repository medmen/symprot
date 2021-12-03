<?php
declare(strict_types = 1);

namespace App\Formatter;

use Doctrine\ORM\EntityManagerInterface;
use http\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CtXmlFormatter implements FormatterStrategyInterface
{
    private $logger, $can_process_mimetype, $valid_formats;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->can_process_mimetype = ['application/xml', 'text/xml'];
        $this->valid_formats = [
            'md', // markdown
            'html' // html
        ];
        $this->format = 'html';
    }

    public function canFormat($data)
    {
        return (
            is_object($data) and
            $data->geraet == 'CT' and
            in_array($data->mimetype, $this->can_process_mimetype)
        );
    }

    public function format($serialized_payload, $format='html')
    {
        if(!in_array($format, $this->valid_formats)) {

            $this->format = 'html';
        } else {
            $this->format = $format;
        }

        $proto_arr = unserialize($serialized_payload);
        // $count = count($proto_arr, COUNT_RECURSIVE);
        $formatted = '';
        $bodysize = '';
        $region = '';
        $protocol = '';
        $series = '';

        switch ($format) {
            case 'md':
                $thead = "<tr><th>^ bodysize</th><th> ^ Region</th><th> ^ Protocol</th><th> ^ Serie ^</th>";
                break;
            default:
                $thead = "<tr><th>bodysize</th><th>Region</th><th>Protocol</th><th>Serie</th>";
        }
        $thead_parameters_already_set = false;
        $cnt = 0;
        foreach ($proto_arr as $row) {
            if ($row['bodysize'] != $bodysize) {
                $bodysize = $row['bodysize'];
                switch ($format) {
                    case 'md':
                        $formatted .= "<tr>\n<td><b>| $bodysize | </b></td>\n";
                        break;
                    default:
                        $formatted .= "<tr>\n<td><b>$bodysize</b></td>\n";
                }
            } else {
                $formatted .= "<tr>\n<td></td>\n";
            }

            if ($row['region'] != $region) {
                $region = $row['region'];
                switch ($format) {
                    case 'md':
                        $formatted .= "<td><b>$region | </b></td>\n";
                        break;
                    default:
                        $formatted .= "<td><b>$region</b></td>\n";
                }
            } else {
                $formatted .= "<td></td>\n";
            }

            if ($row['protocol'] != $protocol) {
                $protocol = $row['protocol'];
                switch ($format) {
                    case 'md':
                        $formatted .= "<td><b>$protocol | </b></td>\n";
                        break;
                    default:
                        $formatted .= "<td><b>$protocol</b></td>\n";
                }
            } else {
                $formatted .= "<td></td>\n";
            }

            if ($row['series'] !== $series) {
                $series = $row['series'];
                switch ($format) {
                    case 'md':
                        $formatted .= "<td><b>$series | </b></td>\n";
                        break;
                    default:
                        $formatted .= "<td><b>$series</b></td>\n";
                }
            } else {
                $formatted .= "<td></td>\n";
            }

            foreach ($row['param'] as $paramname => $paramval) {
                switch ($format) {
                    case 'md':
                        $formatted .= "<td>$paramval | </td>\n";
                        break;
                    default:
                        $formatted .= "<td>$paramval</td>\n";
                }
                if (false === $thead_parameters_already_set) {
                    $thead .= "<th>$paramname</th>\n";
                }
            }
            $thead_parameters_already_set = true;
            $formatted .= "</tr>\n";
            $thead .= "</tr>\n";

            $cnt++;
            if ($cnt > 10) {
                break;
            }
        }


        return ("<table class='table-dark table-responsive output-table'><thead>$thead</thead></thead><tbody>$formatted</tbody></table>");
    }
}