<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Controller;

use ksfraser\FrontAccounting\ProjectManagement\Service\ReportService;

/**
 * DashboardTabController — read-only PM dashboard.
 *
 * Renders the stat-card summary and the recent-activity feed using the
 * ported ReportService (QueryBuilder aggregates on DbConnectionInterface).
 * Guards all FA UI helpers so it degrades gracefully outside FA (tests/CLI).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §2 (controller SRP)
 * @BABOK Related: BR-012 (dashboard)
 */
class DashboardTabController
{
    /** @var ReportService */
    private $service;

    public function __construct(?ReportService $service = null)
    {
        $this->service = $service ?? new ReportService();
    }

    /**
     * Render the dashboard inside the already-open host page.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function run(): void
    {
        if (!function_exists('start_table')) {
            return;
        }

        $stats = $this->service->dashboardStats();

        start_table(TABLESTYLE2, 'width="98%"');
        $cols = [
            ['label' => 'Projects',     'key' => 'total_projects'],
            ['label' => 'Active',       'key' => 'active_projects'],
            ['label' => 'Completed',    'key' => 'completed_projects'],
            ['label' => 'Tasks',        'key' => 'total_tasks'],
            ['label' => 'Pending',      'key' => 'pending_tasks'],
            ['label' => 'In Progress',  'key' => 'in_progress_tasks'],
            ['label' => 'Overdue',      'key' => 'overdue_tasks'],
        ];
        echo '<tr>';
        foreach ($cols as $col) {
            echo '<th class="logo">' . _($col['label']) . '</th>';
        }
        echo '</tr>';
        echo '<tr>';
        foreach ($cols as $col) {
            echo '<td class="r">' . htmlspecialchars((string) ($stats[$col['key']] ?? 0)) . '</td>';
        }
        echo '</tr>';
        end_table(1);

        echo '<br>';

        start_table(TABLESTYLE2, 'width="98%"');
        th_row(_('Recognized Revenue'), _('Overdue Tasks'));
        td_row_html(htmlspecialchars(number_format((float) ($stats['total_revenue'] ?? 0), 2)), 6, 1);
        td_row_html((string) ($stats['overdue_tasks'] ?? 0), 6);
        end_table(1);

        echo '<br>';

        $this->renderActivity((int) ($_GET['activity'] ?? 8));
    }

    /**
     * Recent-activity feed table.
     *
     * @param int $limit
     * @return void
     *
     * @since 1.0.0
     */
    private function renderActivity(int $limit): void
    {
        if (!function_exists('start_table') || !function_exists('th_row')) {
            return;
        }

        $rows = $this->service->recentActivity($limit);

        start_table(TABLESTYLE2, 'width="98%"');
        th_row(_('When'), _('User'), _('Action'), _('Entity'), _('Details'));
        if (empty($rows)) {
            echo '<tr><td colspan="5">' . _('No activity recorded yet.') . '</td></tr>';
        } else {
            foreach ($rows as $row) {
                start_row();
                label_cell((string) ($row['created_at'] ?? ''));
                label_cell((string) ($row['user_id'] ?? ''));
                label_cell((string) ($row['action'] ?? ''));
                label_cell((string) ($row['entity_type'] ?? '') . ' ' . (string) ($row['entity_id'] ?? ''));
                label_cell((string) ($row['details'] ?? ''));
                end_row();
            }
        }
        end_table(1);
    }
}