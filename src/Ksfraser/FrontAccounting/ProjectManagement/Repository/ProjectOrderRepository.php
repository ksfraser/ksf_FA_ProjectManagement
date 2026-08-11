<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Repository;

use ksfraser\FrontAccounting\ProjectManagement\Entity\ProjectSalesOrder;
use ksfraser\FrontAccounting\ProjectManagement\Entity\ProjectRevenue;

/**
 * Data access for project sales-order links and project revenue.
 *
 * @BABOK Related: FR-PM-009/010
 * @since 1.0.0
 */
class ProjectOrderRepository
{
    use FaRepositoryTrait;

    private const PROJECTS_TABLE = 'fa_pm_projects';
    private const LINKS_TABLE = 'fa_pm_project_sales_orders';
    private const REVENUE_TABLE = 'fa_pm_project_revenue';

    /**
     * Link an FA order to a project.
     *
     * @param array $data Link fields
     * @return int New link id
     */
    public function linkOrder(array $data): int
    {
        $sql = "INSERT INTO " . TB_PREF . self::LINKS_TABLE . "
            (project_id, fa_order_no, fa_trans_type, source, source_order_id)
            VALUES (" .
            $this->escape($data['project_id']) . ", " .
            $this->intVal($data['fa_order_no'] ?? 0) . ", " .
            $this->intVal($data['fa_trans_type'] ?? 10) . ", " .
            $this->escape($data['source'] ?? 'all') . ", " .
            (isset($data['source_order_id']) && $data['source_order_id'] !== null && $data['source_order_id'] !== '' ? $this->escape($data['source_order_id']) : 'NULL') . ")";
        $this->dbQuery($sql);
        return $this->dbInsertId();
    }

    /**
     * Record project revenue for an FA order.
     *
     * @param array $data Revenue fields
     * @return int New revenue_id
     */
    public function recordRevenue(array $data): int
    {
        $sql = "INSERT INTO " . TB_PREF . self::REVENUE_TABLE . "
            (project_id, fa_order_no, fa_trans_type, source, source_order_id,
             order_total, revenue_amount, order_date)
            VALUES (" .
            $this->escape($data['project_id']) . ", " .
            $this->intVal($data['fa_order_no'] ?? 0) . ", " .
            $this->intVal($data['fa_trans_type'] ?? 10) . ", " .
            $this->escape($data['source'] ?? 'all') . ", " .
            (isset($data['source_order_id']) && $data['source_order_id'] !== null && $data['source_order_id'] !== '' ? $this->escape($data['source_order_id']) : 'NULL') . ", " .
            $this->floatVal($data['order_total'] ?? 0) . ", " .
            $this->floatVal($data['revenue_amount'] ?? 0) . ", " .
            (isset($data['order_date']) && $data['order_date'] !== '' ? $this->escape($data['order_date']) : 'NULL') . ")";
        $this->dbQuery($sql);
        return $this->dbInsertId();
    }

    /**
     * Find sales-order links for an FA order.
     *
     * @param int $orderNo FA order number
     * @param int $transType FA transaction type
     * @return ProjectSalesOrder[]
     */
    public function findLinksByOrder(int $orderNo, int $transType = 10): array
    {
        $sql = "SELECT * FROM " . TB_PREF . self::LINKS_TABLE . "
            WHERE fa_order_no = " . $this->intVal($orderNo) . "
              AND fa_trans_type = " . $this->intVal($transType);
        return array_map(fn($r) => new ProjectSalesOrder($r), $this->dbFetchAll($this->dbQuery($sql)));
    }

    /**
     * Find revenue rows for an FA order.
     *
     * @param int $orderNo FA order number
     * @param int $transType FA transaction type
     * @return ProjectRevenue[]
     */
    public function findRevenueByOrder(int $orderNo, int $transType = 10): array
    {
        $sql = "SELECT * FROM " . TB_PREF . self::REVENUE_TABLE . "
            WHERE fa_order_no = " . $this->intVal($orderNo) . "
              AND fa_trans_type = " . $this->intVal($transType);
        return array_map(fn($r) => new ProjectRevenue($r), $this->dbFetchAll($this->dbQuery($sql)));
    }

    /**
     * Find sales-order links for a project.
     *
     * @param string $projectId Project id
     * @return ProjectSalesOrder[]
     */
    public function findLinksByProject(string $projectId): array
    {
        $sql = "SELECT * FROM " . TB_PREF . self::LINKS_TABLE .
            " WHERE project_id = " . $this->escape($projectId) . " ORDER BY linked_at DESC";
        return array_map(fn($r) => new ProjectSalesOrder($r), $this->dbFetchAll($this->dbQuery($sql)));
    }

    /**
     * Find revenue rows for a project.
     *
     * @param string $projectId Project id
     * @return ProjectRevenue[]
     */
    public function findRevenueByProject(string $projectId): array
    {
        $sql = "SELECT * FROM " . TB_PREF . self::REVENUE_TABLE .
            " WHERE project_id = " . $this->escape($projectId) . " ORDER BY created_at DESC";
        return array_map(fn($r) => new ProjectRevenue($r), $this->dbFetchAll($this->dbQuery($sql)));
    }

    /**
     * Find active projects linked to a customer.
     *
     * @param int $customerId debtors_master.debtor_no
     * @return array[] Project rows (project_id, name, status)
     */
    public function findProjectsForCustomer(int $customerId): array
    {
        $sql = "SELECT project_id, name, status FROM " . TB_PREF . self::PROJECTS_TABLE . "
            WHERE customer_id = " . $this->intVal($customerId) . "
              AND status <> 'Cancelled'
            ORDER BY start_date DESC";
        return $this->dbFetchAll($this->dbQuery($sql));
    }

    /**
     * Aggregate revenue summary for a project.
     *
     * @param string $projectId Project id
     * @return array Total and count (revenue_total, order_count)
     */
    public function getRevenueSummaryByProject(string $projectId): array
    {
        $sql = "SELECT COALESCE(SUM(revenue_amount), 0) AS revenue_total,
                       COUNT(*) AS order_count
                FROM " . TB_PREF . self::REVENUE_TABLE .
            " WHERE project_id = " . $this->escape($projectId);
        return $this->dbFetchAssoc($this->dbQuery($sql)) ?? ['revenue_total' => 0, 'order_count' => 0];
    }
}
