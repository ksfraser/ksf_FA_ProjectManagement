<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Engine;

/**
 * Pure Critical Path Method engine (BR-PM-006).
 *
 * Terminology, fixed here once so the rest of the module uses one meaning:
 *
 * - ES/EF — earliest start/finish: the FORWARD pass. For every task, the
 *   earliest start is the MAX of the candidate offered by EACH predecessor
 *   (FS/SS/FF/SF, plus lag), then clamped up by any "no earlier than" anchor.
 * - LS/LF — latest start/finish: the BACKWARD pass. For every task, the
 *   latest finish is the MIN of the bound contributed by EACH successor.
 * - `slack` — TOTAL FLOAT, `LS - ES`. It is signed on purpose: it is
 *   non-negative for every task of a well-formed acyclic network, and goes
 *   negative only when a "no later than" anchor is earlier than the computed
 *   schedule, which is a constraint violation the caller must see. It is NOT
 *   abs(LS - ES): taking the absolute value launders that violation into a
 *   healthy-looking positive float. See ProjectDcs/003-Scheduling/
 *   CPM-SPEC-REVIEW.md §2.
 * - `critical` — the critical path is the set of tasks with total float of
 *   zero, tested against a tolerance band because ES/EF/LS/LF are day
 *   offsets. The band is one-sided: a task whose float is negative is a
 *   constraint violation, not a critical task.
 *
 * Every relation type is bound by the same rule, expressed through the
 * successor's LS/LF and the predecessor's own duration:
 *
 *   FS(i->j): LF_i <= LS_j - lag
 *   SS(i->j): LF_i <= LS_j - lag + dur_i
 *   FF(i->j): LF_i <= LF_j - lag
 *   SF(i->j): LF_i <= LF_j - lag + dur_i
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @BABOK Related: FR-PM-006-001, BR-PM-006
 * @UML Note: ProjectDcs/003-Scheduling/ARCH-PM-006-standalone-cpm-package.md
 */
class CpmEngine
{
    const FS = 'fs';
    const SS = 'ss';
    const FF = 'ff';
    const SF = 'sf';

    /** Total-float band (in days) inside which a task counts as critical. */
    const CRITICAL_TOLERANCE = 0.5;

    /**
     * Run the forward pass, the backward pass and the critical-path selection.
     *
     * @param array<int,array<string,mixed>> $tasks        Task rows.
     * @param array<int,array<string,mixed>> $dependencies Precedence edges.
     * @param array<string,mixed>             $options
     *        Optional `origin` (alias `start_date`): the calendar date that
     *        ES/EF/LS/LF day offsets are counted from — i.e. the project
     *        start date. Only constraint anchors need it; integer anchors and
     *        anchors with no origin are already offsets and pass through.
     *        Without an origin a calendar-date anchor cannot be resolved and
     *        is treated as t=0 rather than as days-since-epoch.
     *
     * @return array<string,mixed>
     *         {ok, tasks, project_duration, critical} or {ok:false, cycle}.
     *
     * @BABOK Related: FR-PM-006-001, BR-PM-006
     */
    public function run(array $tasks, array $dependencies, array $options = []): array
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
                'constraint'   => $this->normalizeConstraints($t['constraint'] ?? []),
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

        // Day-zero of the schedule: the project start date every ES/EF/LS/LF
        // offset is counted from. Only dated constraint anchors need it.
        $originDay = $this->toEpochDay($options['origin'] ?? $options['start_date'] ?? null);

        $edges = [];
        foreach ($dependencies as $dep) {
            $pred = (string) ($dep['predecessor'] ?? $dep['predecessor_id'] ?? '');
            $succ = (string) ($dep['successor'] ?? $dep['task_id'] ?? '');
            if ($pred === '' || $succ === '') {
                continue;
            }
            if ($pred === $succ) {
                // A self edge is a degenerate (length-1) loop, but every
                // established PM product reports it as an input-validation
                // error, not as a precedence cycle: dotProject and OpenProject
                // both reject "task depends on itself" in the validation
                // layer before the scheduler ever sees the edge. So it comes
                // back ok=false with an EMPTY cycle path (there is no cycle to
                // report) and the human-readable reason is the validator's
                // job — see TaskDependencyService::validate().
                return ['ok' => false, 'cycle' => []];
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
            // The set difference (everything missing from the topological
            // order) is NOT the cycle: it also contains every acyclic task
            // that merely hangs off a loop. Report a real traversal instead —
            // see extractCyclePath().
            return ['ok' => false, 'cycle' => $this->extractCyclePath($byId)];
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
                        $anchor = $this->toDuration($c['date'] ?? $c['anchor_date'] ?? null, $originDay);
                        if ($anchor > $byId[$n]['es']) {
                            $byId[$n]['es'] = $anchor;
                        }
                        break;
                    case 'finish_no_earlier_than':
                        $anchor = $this->toDuration($c['date'] ?? $c['anchor_date'] ?? null, $originDay);
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
                        $cand = $byId[$m]['ls'] - $lag + $byId[$n]['duration'];
                        break;
                    case 'ff':
                        $cand = $byId[$m]['lf'] - $lag;
                        break;
                    case 'sf':
                        $cand = $byId[$m]['lf'] - $lag + $byId[$n]['duration'];
                        break;
                    case 'fs':
                    default:
                        // FS: predecessor finish gates successor start, so the
                        // predecessor's latest finish is the successor's latest
                        // start minus the lag. (An earlier revision overwrote
                        // this with succ.ES - pred.duration, which ignored the
                        // successor's own slack entirely and drove every FS
                        // predecessor one duration early.)
                        $cand = $byId[$m]['ls'] - $lag;
                        break;
                }
                if ($cand < $byId[$n]['lf']) {
                    $byId[$n]['lf'] = $cand;
                }
            }
            foreach ($byId[$n]['constraint'] as $c) {
                switch ($c['type'] ?? '') {
                    case 'start_no_later_than':
                        $anchor = $this->toDuration($c['date'] ?? $c['anchor_date'] ?? null, $originDay);
                        if ($anchor < $byId[$n]['ls']) {
                            $byId[$n]['ls'] = $anchor;
                        }
                        break;
                    case 'finish_no_later_than':
                        $anchor = $this->toDuration($c['date'] ?? $c['anchor_date'] ?? null, $originDay);
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
            // Total float. Signed: only a "no later than" anchor earlier than
            // the computed schedule can make this negative, and that is a
            // constraint violation, not float.
            $byId[$id]['slack'] = $byId[$id]['ls'] - $byId[$id]['es'];
            // Critical = total float zero, inside a one-sided tolerance band.
            // A negative float is excluded on purpose (see the class docblock).
            $byId[$id]['critical'] = ($byId[$id]['slack'] >= 0
                && $byId[$id]['slack'] <= self::CRITICAL_TOLERANCE);
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

    /**
     * Coerce a task's `constraint` payload into a list of constraint arrays.
     *
     * Repository rows carry `null` when a task is unconstrained, which the
     * constraint loops used to iterate directly (a `foreach` over null warns
     * on every unconstrained task), and a caller may pass either a single
     * associative constraint or a list of them.
     *
     * @param mixed $raw
     * @return array<int,array<string,mixed>>
     *
     * @BABOK Related: FR-PM-006-001
     */
    private function normalizeConstraints($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        if (isset($raw['type'])) {
            $raw = [$raw];
        }
        $out = [];
        foreach ($raw as $c) {
            if (is_array($c) && isset($c['type'])) {
                $out[] = $c;
            }
        }

        return $out;
    }

    /**
     * The first real precedence cycle, as a traversal that closes on itself.
     *
     * Depth-first over the successor edges with the classic tri-colour rule
     * (1 = on the current path, 2 = finished): an edge into a node that is on
     * the current path is a back edge, and the cycle is the slice of the path
     * from that node to the top, with the closing node appended — `[A,B,C,A]`
     * for A->B->C->A. Reporting the closing node is a display convention every
     * established product uses, and it is what makes "where does the loop
     * close?" readable in a UI. Acyclic tasks hanging off the loop are
     * correctly absent.
     *
     * @param array<string,array<string,mixed>> $byId Task table.
     * @return string[] Closed cycle path, or [] if none is found.
     *
     * @BABOK Related: FR-PM-006-001, BR-PM-006
     */
    private function extractCyclePath(array $byId): array
    {
        $state = [];
        $path  = [];
        foreach ($byId as $id => $row) {
            if (isset($state[$id])) {
                continue;
            }
            $found = $this->walkForCycle($id, $byId, $state, $path);
            if ($found !== null) {
                return $found;
            }
        }

        return [];
    }

    /**
     * Depth-first leg of extractCyclePath().
     *
     * @param string                            $node Current node id.
     * @param array<string,array<string,mixed>> $byId Task table.
     * @param array<string,int>                 $state 1 = on path, 2 = done.
     * @param string[]                          $path  Current DFS path.
     * @return string[]|null Closed cycle path, or null when this leg is clean.
     *
     * @BABOK Related: FR-PM-006-001
     */
    private function walkForCycle(string $node, array $byId, array &$state, array &$path): ?array
    {
        $state[$node] = 1;
        $path[]       = $node;

        foreach ($byId[$node]['succ'] as $succ) {
            if (!isset($byId[$succ])) {
                continue;
            }
            if (!isset($state[$succ])) {
                $found = $this->walkForCycle($succ, $byId, $state, $path);
                if ($found !== null) {
                    return $found;
                }
                continue;
            }
            if ($state[$succ] === 1) {
                $start  = array_search($succ, $path, true);
                $cycle  = array_slice($path, $start === false ? 0 : $start);
                $cycle[] = $succ;

                return $cycle;
            }
        }

        array_pop($path);
        $state[$node] = 2;

        return null;
    }

    /**
     * Days from `origin` to a calendar date, or null when unresolvable.
     *
     * @param mixed $origin Project start date (Y-m-d) or epoch-day integer.
     * @return int|null
     *
     * @BABOK Related: FR-PM-006-001
     */
    private function toEpochDay($origin): ?int
    {
        if ($origin === null || $origin === '') {
            return null;
        }
        if (is_int($origin) || is_float($origin)) {
            return (int) $origin;
        }
        $ts = strtotime((string) $origin);

        return $ts === false ? null : (int) floor($ts / 86400);
    }

    /**
     * Normalize a constraint anchor to a day offset on the schedule timeline.
     *
     * Integers (and numeric strings) are already offsets and pass straight
     * through. A calendar date is only meaningful relative to the schedule
     * origin, so with no origin it resolves to 0: the previous behaviour of
     * `floor(ts / 86400)` returned days-since-epoch and put every dated
     * constraint in the year 20458.
     *
     * @param mixed    $date    Constraint anchor.
     * @param int|null $originDay Epoch day of the schedule origin, if known.
     * @return int
     *
     * @BABOK Related: FR-PM-006-001
     */
    private function toDuration($date, ?int $originDay = null): int
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
        if ($ts === false || $originDay === null) {
            return 0;
        }

        return (int) floor($ts / 86400) - $originDay;
    }
}
