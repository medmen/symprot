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
        $proto_arr = unserialize($serialized_payload);

        // treat errors
        if (isset($proto_arr['error'])) {
            return '<h1 class="error error-message">'.$proto_arr['error'].'</h1>';
        }

        if (empty($proto_arr) || !isset($proto_arr[0]) || !is_array($proto_arr[0])) {
            return '<div class="alert alert-warning">No data</div>';
        }

        // Build a single header from the first row, but omit region and protocol columns
        $allKeys = array_keys($proto_arr[0]);
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
            $starter = $this->isStarter($row);

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
                    $cells[] = isset($row[$k]) ? (string)$row[$k] : '';
                }
                $tbody .= '<tr><td>'.implode('</td><td>', $cells).'</td></tr>';
                $seqCount++;
                $taVal = isset($row['ta']) ? (string)$row['ta'] : '';
                $totalTaSeconds += $this->parseTaToSeconds($taVal);
            }

            $totalTaFormatted = $this->formatSeconds($totalTaSeconds);
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

    private function parseTaToSeconds(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        // handle mm:ss
        if (str_contains($value, ':')) {
            [$m, $s] = array_pad(explode(':', $value, 2), 2, '0');
            $m = (int)trim($m);
            $s = (int)trim($s);
            if ($m < 0) { $m = 0; }
            if ($s < 0) { $s = 0; }
            return $m * 60 + $s;
        }
        // plain seconds
        if (is_numeric($value)) {
            $sec = (int)$value;
            return $sec > 0 ? $sec : 0;
        }
        return 0;
    }

    private function formatSeconds(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $secs);
    }

    private function isStarter(array $row): bool
    {
        $seq = strtolower(trim((string)($row['sequence'] ?? '')));
        if ($seq === '') {
            return false;
        }
        return str_contains($seq, 'localizer') || str_contains($seq, 'scout') || str_contains($seq, 'topo');
    }
}
