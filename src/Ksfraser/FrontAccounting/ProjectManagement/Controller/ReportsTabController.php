<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Controller;

use ksfraser\FrontAccounting\ProjectManagement\Service\ReportService;

/**
 * ReportsTabController — read-only PM reporting tab.
 *
 * Renders budget-vs-revenue and hours-by-project tables from the ported
 * ReportService (QueryBuilder aggregates on DbConnectionInterface).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §2 (controller SRP)
 * @BABOK Related: FR-PM-010-001 — the reports tab surface. Replaces the previous
 *   `@BABOK BR-012, FR-PM-009/010`, none of which match a ProjectDcs document here.
 */
class ReportsTabController
{
    /** @var ReportService */
    private $service;

    /**
     * @BABOK Related: FR-PM-010-001, BR-PM-010 — the PM app-shell / front-controller tab surface
     */
    public function __construct(?ReportService $service = null)
    {
        $this->service = $service ?? new ReportService();
    }

    /**
     * Render the reports inside the already-open host page.
     *
     * @return void
     *
     * @since 1.0.0
      *
 * @BABOK Related: FR-PM-010-001, BR-PM-010 — the PM app-shell / front-controller tab surface
 */
    public function run(): void
    {
        if (!function_exists('start_table')) {
            return;
        }

        $this->renderBudgetVsRevenue();
        echo '<br>';
        $this->renderHoursByProject();
    }

    /**
     * Budget vs recognized revenue per project.
     *
     * @return void
     *
     * @since 1.0.0
      *
 * @BABOK Related: N/A — no requirement in this repo for sales-order linkage / revenue recognition (see the class docblock)
 */
    private function renderBudgetVsRevenue(): void
    {
        $rows = $this->service->budgetVsRevenue();

        start_table(TABLESTYLE2, 'width="98%"');
        table_header(array(_('Project'), _('Budget'), _('Recognized Revenue'), _('Variance')));
        if (empty($rows)) {
            echo '<tr><td colspan="4">' . _('No projects yet.') . '</td></tr>';
        } else {
            foreach ($rows as $row) {
                start_row();
                label_cell((string) ($row['name'] ?? ''));
                amount_cell((float) ($row['budget'] ?? 0));
                amount_cell((float) ($row['revenue'] ?? 0));
                $variance = (float) ($row['budget'] ?? 0) - (float) ($row['revenue'] ?? 0);
                amount_cell($variance);
                end_row();
            }
        }
        end_table(1);
    }

    /**
     * Estimated vs actual hours per project.
     *
     * @return void
     *
     * @since 1.0.0
      *
 * @BABOK Related: N/A — no requirement defined in this repo for the hours-by-project report
 */
    private function renderHoursByProject(): void
    {
        $rows = $this->service->hoursByProject();

        start_table(TABLESTYLE2, 'width="98%"');
        table_header(array(_('Project'), _('Tasks'), _('Estimated Hours'), _('Actual Hours'), _('Delta')));
        if (empty($rows)) {
            echo '<tr><td colspan="5">' . _('No tasks yet.') . '</td></tr>';
        } else {
            foreach ($rows as $row) {
                start_row();
                label_cell((string) ($row['project_id'] ?? ''));
                label_cell((string) ($row['task_count'] ?? 0));
                amount_cell((float) ($row['estimated_hours'] ?? 0));
                amount_cell((float) ($row['actual_hours'] ?? 0));
                $delta = (float) ($row['estimated_hours'] ?? 0) - (float) ($row['actual_hours'] ?? 0);
                amount_cell($delta);
                end_row();
            }
        }
        end_table(1);
    }
}