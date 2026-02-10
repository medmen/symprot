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
            and $data->geraet == 'MRT_Siemens'
            and in_array($data->mimetype, $this->can_process_mimetype)
            and $format == $this->format
        ;
    }

    public function format($serialized_payload, $format)
    {
        $this->logger->debug('MrtXml2HtmlFormatter::format was called');
        $proto_arr = unserialize($serialized_payload);

        // treat errors
        if (isset($proto_arr['error'])) {
            return '<h1 class="error error-message">'.$proto_arr['error'].'</h1>';
        }

        if (empty($proto_arr) || !isset($proto_arr[0]) || !is_array($proto_arr[0])) {
            return '<div class="alert alert-warning">No data</div>';
        }

        // Build a single header from the row with largest field count, but omit region and protocol columns
        $allKeys = [];
        $maxSize = -1;
        foreach ($proto_arr as $row) {
            if (!is_array($row)) { continue; }
            $size = count($row);
            if ($size > $maxSize) {
                $maxSize = $size;
                $allKeys = array_keys($row);
            }
        }

        $displayKeys = array_values(array_filter($allKeys, function ($k) {
            $lk = strtolower((string)$k);
            return $lk !== 'region' && $lk !== 'protocol';
        }));
        $td_count = count($displayKeys);
        $thead = '<tr><th>'.implode('</th><th>', $displayKeys).'</th></tr>';

        // Segment rows into protocol groups using starter sequences in the 'sequence' field
        $groups = [];
        $current = [];
        $inStarterRun = false; // true while we are inside a run of consecutive starters

        foreach ($proto_arr as $row) {
            if (!is_array($row)) { continue; }
            $starter = HelperFunctions::isStarter($row);

            if ($starter) {
                // If we encounter a starter and the current group exists and was not a starter run, split
                if (!empty($current) && $inStarterRun === false) {
                    $groups[] = $current;
                    $current = [];
                }
                // Add starter to current group (consecutive starters remain in same group)
                $current[] = $row;
                $inStarterRun = true;
            } else {
                // Non-starter rows just join the current group
                $current[] = $row;
                $inStarterRun = false;
            }
        }
        if (!empty($current)) {
            $groups[] = $current;
        }

        $html = '';

        foreach ($groups as $rows) {
            $tbody = '';
            $seqCount = 0;
            $totalTaSeconds = 0;

            // derive region label (first non-empty region in group)
            $regionLabel = '';
            foreach ($rows as $r) {
                if (is_array($r) && isset($r['region']) && trim((string)$r['region']) !== '') {
                    $regionLabel = (string)$r['region'];
                    break;
                }
            }

            // Get protocol label from the first row of the group
            $protocol = (string)($rows[0]['protocol'] ?? '');

            foreach ($rows as $row) {
                // build row cells only for display keys (omit region and protocol)
                $cells = [];
                foreach ($displayKeys as $k) {
                    $value = isset($row[$k]) ? (string)$row[$k] : '';
                    $cells[] = $value;
                }
                $tbody .= '<tr><td>'.implode('</td><td>', $cells).'</td></tr>';
                $seqCount++;
                $taVal = isset($row['TA']) ? (string)$row['TA'] : '';
                $totalTaSeconds += HelperFunctions::parseTaToSeconds($taVal);
            }

            $totalTaFormatted = HelperFunctions::formatSeconds($totalTaSeconds);
            $titleProtocol = htmlspecialchars((string)$protocol, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $titleRegion = htmlspecialchars((string)$regionLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $heading = $titleRegion !== ''
                ? "Region: {$titleRegion} — Protocol: {$titleProtocol}"
                : "Protocol: {$titleProtocol}";

            $html .= "<h5 class='mt-3 mb-2'>{$heading}</h5>";
            $html .= "<table class='table-dark table-responsive output-table bordered'>"
                  .  "<thead>{$thead}</thead>"
                  .  "<tfoot><td colspan={$td_count}>sequences: {$seqCount} • total TA: {$totalTaFormatted}</td></tfoot>"
                  .  "<tbody>{$tbody}</tbody>"
                  .  "</table>";
        }

        return $html;
    }


    private function isStarter(array $row): bool
    {
        $seq = strtolower(trim((string)($row['sequence'] ?? '')));
        if ($seq === '') {
            return false;
        }
        return str_contains($seq, 'localizer') || str_contains($seq, 'scout') || str_contains($seq, 'topo') || str_contains($seq, 'fastview');
    }
}
