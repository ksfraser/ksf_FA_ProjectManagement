<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\ProjectManagement\Engine;

class CpmEngine
{
    const FS = 'fs';
    const SS = 'ss';
    const FF = 'ff';
    const SF = 'sf';

    public function run(array $tasks, array $dependencies): array
    {
        $byId = [];
        foreach ($tasks as $t) {
            $id = (string) ($t['id'] ?? $t['task_id'] ?? '');
            if ($id === '') {
                continue;
            }
            $duration = (int) ($t['duration_days'] ?? $t['duration'] ?? 0);
            if ($duration < 0) {
                $duration = 0;
            }
            $isMilestone = (bool) ($t['is_milestone'] ?? false);
            $byId[$id] = [
                'id'           => $id,
                'name'         => (string) ($t['name'] ?? ''),
                'duration'     => $duration,
                'is_milestone' => $isMilestone,
                'constraint'   => $t['constraint'] ?? [],
                'es'           => 0,
                'ef'           => $duration,
                'ls'           => 0,
                'lf'           => 0,
                'slack'        => 0,
                'critical'     => false,
                'pred'         => [],
                'succ'         => [],
            ];
        }
        foreach ($byId as $id => &$row) {
            if ($row['is_milestone']) {
                $row['duration'] = 0;
                $row['ef'] = $row['es'];
            }
        }
        unset($row);

        $edges = [];
        foreach ($dependencies as $dep) {
            $pred = (string) ($dep['predecessor'] ?? $dep['predecessor_id'] ?? '');
            $succ = (string) ($dep['successor'] ?? $dep['task_id'] ?? '');
            if ($pred === '' || $succ === '') {
                continue;
            }
            if ($pred === $succ) {
                return ['ok' => false, 'cycle' => [$pred, $succ]];
            }
            if (!isset($byId[$pred]) || !isset($byId[$succ])) {
                continue;
            }
            $type = (string) ($dep['type'] ?? $dep['dependency_type'] ?? 'fs');
            if (!in_array($type, ['fs', 'ss', 'ff', 'sf'], true)) {
                $type = 'fs';
            }
            $lag = (int) ($dep['lag'] ?? $dep['lag_days'] ?? 0);
            $edges[$pred . "\x1f" . $succ] = [
                'from'  => $pred,
                'to'    => $succ,
                'type'  => $type,
                'lag'   => $lag,
                'label' => $dep['label'] ?? ($type . ($lag !== 0 ? '+' . $lag : '')),
            ];
            $byId[$succ]['pred'][] = $pred;
            $byId[$pred]['succ'][] = $succ;
        }

        $inDeg = [];
        foreach ($byId as $id => $row) {
            $inDeg[$id] = count($row['pred']);
        }
        $queue = [];
        foreach ($inDeg as $id => $d) {
            if ($d === 0) {
                $queue[] = $id;
            }
        }
        $order = [];
        while ($queue) {
            sort($queue);
            $n = array_shift($queue);
            $order[] = $n;
            foreach ($byId[$n]['succ'] as $m) {
                $inDeg[$m]--;
                if ($inDeg[$m] === 0) {
                    $queue[] = $m;
                }
            }
        }
        if (count($order) !== count($byId)) {
            $cycle = array_values(array_diff(array_keys($byId), $order));
            return ['ok' => false, 'cycle' => $cycle];
        }

        foreach ($order as $n) {
            foreach ($byId[$n]['pred'] as $p) {
                $edge = $edges[$p . "\x1f" . $n];
                $t = $edge['type'];
                $lag = $edge['lag'];
                $cand = 0;
                switch ($t) {
                    case 'ss':
                        $cand = $byId[$p]['es'] + $lag;
                        break;
                    case 'ff':
                        $cand = $byId[$p]['ef'] + $lag;
                        break;
                    case 'sf':
                        $cand = $byId[$p]['ef'] + $lag - $byId[$n]['duration'];
                        break;
                    case 'fs':
                    default:
                        $cand = $byId[$p]['ef'] + $lag;
                        break;
                }
                if ($cand > $byId[$n]['es']) {
                    $byId[$n]['es'] = $cand;
                }
            }
            foreach ($byId[$n]['constraint'] as $c) {
                switch ($c['type'] ?? '') {
                    case 'start_no_earlier_than':
                        $anchor = $this->toDuration($c['date'] ?? $c['anchor_date'] ?? null);
                        if ($anchor > $byId[$n]['es']) {
                            $byId[$n]['es'] = $anchor;
                        }
                        break;
                    case 'finish_no_earlier_than':
                        $anchor = $this->toDuration($c['date'] ?? $c['anchor_date'] ?? null);
                        if ($anchor > $byId[$n]['ef']) {
                            $byId[$n]['ef'] = $anchor;
                        }
                        break;
                }
            }
            $byId[$n]['ef'] = max($byId[$n]['es'] + $byId[$n]['duration'], $byId[$n]['ef']);
        }

        $projectEnd = 0;
        foreach ($byId as $n) {
            if ($n['ef'] > $projectEnd) {
                $projectEnd = $n['ef'];
            }
        }
        foreach ($byId as $id => &$row) {
            $row['lf'] = $projectEnd;
            $row['ls'] = $row['lf'] - $row['duration'];
        }
        unset($row);

        $revOrder = array_reverse($order);
        foreach ($revOrder as $n) {
            foreach ($byId[$n]['succ'] as $m) {
                $edge = $edges[$n . "\x1f" . $m];
                $t = $edge['type'];
                $lag = $edge['lag'];
                $cand = $byId[$n]['ef'];
                switch ($t) {
                    case 'ss':
                        $cand = $byId[$m]['ls'] - $lag;
                        break;
                    case 'ff':
                        $cand = $byId[$m]['lf'] - $lag;
                        break;
                    case 'sf':
                        $cand = $byId[$m]['ls'] + $byId[$m]['duration'] - $lag;
                        break;
                    case 'fs':
                    default:
                        $cand = $byId[$m]['ls'] - $lag;
                        $cand = $byId[$m]['es'] - $byId[$n]['duration'] - $lag;
                        break;
                }
                if ($cand < $byId[$n]['lf']) {
                    $byId[$n]['lf'] = $cand;
                }
            }
            foreach ($byId[$n]['constraint'] as $c) {
                switch ($c['type'] ?? '') {
                    case 'start_no_later_than':
                        $anchor = $this->toDuration($c['date'] ?? $c['anchor_date'] ?? null);
                        if ($anchor < $byId[$n]['ls']) {
                            $byId[$n]['ls'] = $anchor;
                        }
                        break;
                    case 'finish_no_later_than':
                        $anchor = $this->toDuration($c['date'] ?? $c['anchor_date'] ?? null);
                        if ($anchor < $byId[$n]['lf']) {
                            $byId[$n]['lf'] = $anchor;
                        }
                        break;
                }
            }
            $byId[$n]['ls'] = min($byId[$n]['lf'] - $byId[$n]['duration'], $byId[$n]['ls']);
        }

        $projectDuration = 0;
        foreach ($byId as $n) {
            if ($n['ef'] > $projectDuration) {
                $projectDuration = $n['ef'];
            }
            if ($n['lf'] > $projectDuration) {
                $projectDuration = $n['lf'];
            }
        }

        $critical = [];
        foreach ($byId as $id => $n) {
            $byId[$id]['slack'] = $byId[$id]['ls'] - $byId[$id]['es'];
            $byId[$id]['critical'] = (abs($byId[$id]['slack']) <= 0.5);
            if ($byId[$id]['critical']) {
                $critical[] = $byId[$id]['id'];
            }
        }

        return [
            'ok'               => true,
            'tasks'            => $byId,
            'project_duration' => $projectDuration,
            'critical'         => $critical,
        ];
    }

    private function toDuration($date): int
    {
        if ($date === null || $date === '') {
            return 0;
        }
        if (is_int($date) || is_float($date)) {
            return (int) $date;
        }
        $s = (string) $date;
        if (preg_match('/^\d+$/', $s)) {
            return (int) $s;
        }
        $ts = strtotime($s);
        if ($ts === false) {
            return 0;
        }
        return (int) floor($ts / 86400);
    }
}
