<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Repository;

use ksfraser\CommonDb\Contract\DbConnectionInterface;
use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;
use ksfraser\FrontAccounting\ProjectManagement\Entity\ProjectSalesOrder;
use ksfraser\FrontAccounting\ProjectManagement\Entity\ProjectRevenue;

/**
 * ProjectOrderRepository — ported onto the shared DbConnectionInterface.
 *
 * Data access for project sales-order links and project revenue, coded against
 * the DbConnectionInterface contract (FaDbAdapter at FA runtime → native db_*).
 * Public API unchanged.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: AGENTS.md §ksf_common_db (DAO porting demonstration module)
 * @BABOK Related: FR-PM-009/010
 */
class ProjectOrderRepository
{
    /** @var DbConnectionInterface */
    private $db;

    public function __construct(?DbConnectionInterface $db = null)
    {
        $this->db = $db ?? Schema::adapter();
    }

    /**
     * Link an FA order to a project.
     *
     * @param array $data Link fields
     * @return int New link id
     */
    public function linkOrder(array $data): int
    {
        $row = [
            'project_id'      => $data['project_id'],
            'fa_order_no'     => (int) ($data['fa_order_no'] ?? 0),
            'fa_trans_type'   => (int) ($data['fa_trans_type'] ?? 10),
            'source'          => $data['source'] ?? 'all',
            'source_order_id' => (isset($data['source_order_id']) && $data['source_order_id'] !== '' && $data['source_order_id'] !== null)
                ? $data['source_order_id'] : null,
        ];
        $this->db->executeUpdate(Schema::projectSalesOrders()->insertSql(), $row);
        return $this->db->lastInsertId();
    }

    /**
     * Record project revenue for an FA order.
     *
     * @param array $data Revenue fields
     * @return int New revenue_id
     */
    public function recordRevenue(array $data): int
    {
        $row = [
            'project_id'      => $data['project_id'],
            'fa_order_no'     => (int) ($data['fa_order_no'] ?? 0),
            'fa_trans_type'   => (int) ($data['fa_trans_type'] ?? 10),
            'source'          => $data['source'] ?? 'all',
            'source_order_id' => (isset($data['source_order_id']) && $data['source_order_id'] !== '' && $data['source_order_id'] !== null)
                ? $data['source_order_id'] : null,
            'order_total'     => (float) ($data['order_total'] ?? 0),
            'revenue_amount'  => (float) ($data['revenue_amount'] ?? 0),
            'order_date'      => (isset($data['order_date']) && $data['order_date'] !== '') ? $data['order_date'] : null,
        ];
        $this->db->executeUpdate(Schema::projectRevenue()->insertSql(), $row);
        return $this->db->lastInsertId();
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
        $sql = "SELECT * FROM " . Schema::T_SALES_ORDERS
            . " WHERE fa_order_no = :order_no AND fa_trans_type = :trans_type";
        return array_map(
            fn($r) => new ProjectSalesOrder($r),
            $this->db->fetchAll($sql, ['order_no' => $orderNo, 'trans_type' => $transType])
        );
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
        $sql = "SELECT * FROM " . Schema::T_REVENUE
            . " WHERE fa_order_no = :order_no AND fa_trans_type = :trans_type";
        return array_map(
            fn($r) => new ProjectRevenue($r),
            $this->db->fetchAll($sql, ['order_no' => $orderNo, 'trans_type' => $transType])
        );
    }

    /**
     * Find sales-order links for a project.
     *
     * @param string $projectId Project id
     * @return ProjectSalesOrder[]
     */
    public function findLinksByProject(string $projectId): array
    {
        $sql = "SELECT * FROM " . Schema::T_SALES_ORDERS
            . " WHERE project_id = :project_id ORDER BY linked_at DESC";
        return array_map(
            fn($r) => new ProjectSalesOrder($r),
            $this->db->fetchAll($sql, ['project_id' => $projectId])
        );
    }

    /**
     * Find revenue rows for a project.
     *
     * @param string $projectId Project id
     * @return ProjectRevenue[]
     */
    public function findRevenueByProject(string $projectId): array
    {
        $sql = "SELECT * FROM " . Schema::T_REVENUE
            . " WHERE project_id = :project_id ORDER BY created_at DESC";
        return array_map(
            fn($r) => new ProjectRevenue($r),
            $this->db->fetchAll($sql, ['project_id' => $projectId])
        );
    }

    /**
     * Find active projects linked to a customer.
     *
     * @param int $customerId debtors_master.debtor_no
     * @return array[] Project rows (project_id, name, status)
     */
    public function findProjectsForCustomer(int $customerId): array
    {
        $sql = "SELECT project_id, name, status FROM " . Schema::T_PROJECTS
            . " WHERE customer_id = :customer_id AND status <> 'Cancelled'"
            . " ORDER BY start_date DESC";
        return $this->db->fetchAll($sql, ['customer_id' => $customerId]);
    }

    /**
     * Aggregate revenue summary for a project.
     *
     * @param string $projectId Project id
     * @return array Total and count (revenue_total, order_count)
     */
    public function getRevenueSummaryByProject(string $projectId): array
    {
        $sql = "SELECT COALESCE(SUM(revenue_amount), 0) AS revenue_total,"
            . " COUNT(*) AS order_count FROM " . Schema::T_REVENUE
            . " WHERE project_id = :project_id";
        $row = $this->db->fetchAssoc($sql, ['project_id' => $projectId]);
        return $row ?? ['revenue_total' => 0, 'order_count' => 0];
    }
}