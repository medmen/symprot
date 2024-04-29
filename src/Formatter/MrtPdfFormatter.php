<?php

declare(strict_types=1);

namespace App\Formatter;

use http\Exception\InvalidArgumentException;

class MrtPdfFormatter
{
    private array $can_process_mimetype = ['application/pdf'];

    private $format;
    private $old_protocol;
    private $old_region;
    private $pretty;

    public function __construct($format)
    {
        $valid_formats = [
            'md',
            // markdown
            'html',
        ];

        if (!in_array($format, $valid_formats)) {
            throw new InvalidArgumentException('invalid format: '.$format);
        }
        $this->format = $format;
        $this->pretty = '';
    }

    public function canFormat($data)
    {
        return
            is_object($data)
            and $data->geraet == 'MRT'
            and in_array($data->mimetype, $this->can_process_mimetype)
        ;
    }

    public function format($serialized_payload)
    {
        $proto_arr = unserialize($serialized_payload);

        // treat errors
        if (isset($proto_arr['error'])) {
            return '<h1 class="error error-message">'.$proto_arr['error'].'</h1>';
        }

        return var_export($proto_arr, true);
    }

    public function format_pretty(array $data): string
    {
        $data = array_values($data);

        if (!$this->old_protocol) {
            $this->old_protocol = '';
        }

        if (!$this->old_region) {
            $this->old_region = '';
        }

        $headers_arr = array_keys($data[0]);
        // remove region and protocol from headers
        $headers_arr = array_filter(
            $headers_arr,
            fn ($val) => !in_array($val, ['region', 'protocol'])
        );

        foreach ($data as $row) {
            switch ($this->format) {
                case 'html':
                    if ($row['region'] !== $this->old_region) {
                        $this->pretty .= '<h1>'.$row['region'].'</h1>';
                        $this->old_region = $row['region'];
                        $this->old_protocol = '';
                    }

                    if ($row['protocol'] !== $this->old_protocol) {
                        $this->pretty .= '</table>'.PHP_EOL;
                        $this->pretty .= '<h2>'.$row['protocol'].'</h2>'.PHP_EOL;
                        $this->pretty .= '<table>.PHP_EOL<thead>.PHP_EOL<tr><th>'.implode('</th><th>', $headers_arr).'</th></tr>.PHP_EOL</thead>.PHP_EOL<tfoot>a nice footer</tfoot>'.PHP_EOL;
                        $this->old_protocol = $row['protocol'];
                    }
                    unset($row['region'], $row['protocol']);
                    $this->pretty .= '<tr><td>'.implode('</td>'.PHP_EOL.'<td>', $row).'</td></tr>'.PHP_EOL;
                    break;

                case 'md':
                default:
                    if ($row['region'] !== $this->old_region) {
                        $this->pretty .= '====== '.$row['region'].' ======'.PHP_EOL;
                        $this->old_region = $row['region'];
                        $this->old_protocol = '';
                    }

                    if ($row['protocol'] !== $this->old_protocol) {
                        $this->pretty .= '===== '.$row['protocol'].' ====='.PHP_EOL;
                        $this->pretty .= '^ '.implode(' ^ ', $headers_arr).' ^'.PHP_EOL;
                        $this->old_protocol = $row['protocol'];
                    }
                    unset($row['region'], $row['protocol']);
                    $this->pretty .= '|'.implode(' | ', $row).' |'.PHP_EOL;
            }
        }

        return $this->pretty;
    }
}
