<?php

declare(strict_types=1);

namespace App\Formatter;

use Psr\Log\LoggerInterface;

class MrtXml2HtmlFormatter implements FormatterStrategyInterface
{
    private $can_process_mimetype;
    private $format;

    public function __construct(private LoggerInterface $logger)
    {
        $this->can_process_mimetype = ['application/xml', 'text/xml'];
        $this->format = 'html';
    }

    public function canFormat($data, $format)
    {
        return
            is_object($data)
            and $data->geraet == 'MRT'
            and in_array($data->mimetype, $this->can_process_mimetype)
            and $format == $this->format
        ;
    }

    public function format($serialized_payload, $format)
    {
        $proto_arr = unserialize($serialized_payload);

        // treat errors
        if (isset($proto_arr['error'])) {
            return '<h1 class="error error-message">'.$proto_arr['error'].'</h1>';
        }

        $thead_arr = array_keys($proto_arr[0]);
        $td_count = count($thead_arr);

        $thead = '<tr><th>'.implode('</th><th>', $thead_arr).'</th></tr>';

        // $count = count($proto_arr, COUNT_RECURSIVE);
        $formatted = '';

        $thead_parameters_already_set = false;
        $regions = 0;
        $protocols = 0;
        $sequences = 0;
        $actual_region = '';
        $actual_protocol = '';

        foreach ($proto_arr as $row) {
            if (is_array($row)) {
                $formatted .= '<tr><td>'.implode('</td><td>', $row).'</td></tr>';

                if ($row['region'] !== $actual_region) {
                    ++$regions;
                    $actual_region = $row['region'];
                }

                if ($row['protocol'] !== $actual_protocol) {
                    ++$protocols;
                    $actual_protocol = $row['protocol'];
                }

                ++$sequences;
            }
        }

        return "<table class='table-dark table-responsive output-table bordered'><thead>$thead</thead><tfoot><td colspan=$td_count>extracted $regions regions with $protocols protocols and $sequences sequences</td></tfoot><tbody>$formatted</tbody></table>";
    }
}
