<?php
declare(strict_types = 1);

namespace App\Formatter;

use Doctrine\ORM\EntityManagerInterface;
use http\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MrtXml2MdFormatter implements FormatterStrategyInterface
{
    private $logger, $can_process_mimetype, $format;

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
            $data->geraet == 'MRT' and
            in_array($data->mimetype, $this->can_process_mimetype) and
            $format == $this->format
        );
    }

    public function format($serialized_payload, $format)
    {
        $proto_arr = unserialize($serialized_payload);
        $formatted = '';

        // treat errors
        if(isset($proto_arr['error'])) {
            return('<h1 class="error error-message">'.$proto_arr['error'].'</h1>');
        }

        $thead_arr = array_keys($proto_arr[0]);

        $thead = "<tr><th>".implode(' | </th><th>',$thead_arr)."</th></tr>";

        foreach ($proto_arr as $row) {
            $formatted.= "<tr><td>".implode(' | </td><td>', $row)."</td></tr>";
        }

        return ("<table class='table-dark table-responsive output-table bordered'><thead>$thead</thead><tbody>$formatted</tbody></table>");
    }
}