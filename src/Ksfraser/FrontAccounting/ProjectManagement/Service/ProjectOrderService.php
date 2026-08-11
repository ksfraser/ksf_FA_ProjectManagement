<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Service;

use ksfraser\FrontAccounting\ProjectManagement\Repository\ProjectOrderRepository;

/**
 * Links imported FA orders to projects and records project revenue.
 *
 * Consumes the order_imported event broadcast by source modules
 * (Square, WooCommerce). Orders are linked to the customer's active
 * projects; revenue is recognized at the order total.
 *
 * @BABOK Related: FR-PM-009/010
 * @since 1.0.0
 */
class ProjectOrderService
{
    private ProjectOrderRepository $repo;

    public function __construct()
    {
        $this->repo = new ProjectOrderRepository();
    }

    /**
     * Handle an order_imported event payload.
     *
     * Links the order to every active project for the payload's customer
     * and records revenue, skipping orders that are already linked.
     *
     * @param array $payload order_imported payload
     * @return array Created link ids
     */
    public function onOrderImported(array $payload): array
    {
        $customerId = (int)($payload['customer_id'] ?? 0);
        if ($customerId <= 0) {
            return [];
        }

        $projects = $this->repo->findProjectsForCustomer($customerId);
        if (empty($projects)) {
            return [];
        }

        $orderNo = (int)($payload['fa_order_no'] ?? 0);
        $transType = (int)($payload['fa_trans_type'] ?? 10);
        if ($orderNo <= 0 || !empty($this->repo->findLinksByOrder($orderNo, $transType))) {
            return [];
        }

        $created = [];
        foreach ($projects as $project) {
            $link = $this->linkOrderToProject($project['project_id'], $payload);
            if ($link !== null) {
                $created[] = $link;
            }
        }

        return $created;
    }

    /**
     * Link an FA order to a project and record its revenue.
     *
     * @param string $projectId Project id
     * @param array $payload order_imported payload
     * @return array|null Link id and revenue id, or null when already linked
     */
    public function linkOrderToProject(string $projectId, array $payload): ?array
    {
        $orderNo = (int)($payload['fa_order_no'] ?? 0);
        $transType = (int)($payload['fa_trans_type'] ?? 10);

        if ($orderNo <= 0 || !empty($this->repo->findLinksByOrder($orderNo, $transType))) {
            return null;
        }

        $orderTotal = (float)($payload['order_total'] ?? 0);
        $source = (string)($payload['source'] ?? 'all');
        $sourceOrderId = (string)($payload['source_order_id'] ?? '');
        $orderDate = (string)($payload['order_date'] ?? date('Y-m-d'));

        $linkId = $this->repo->linkOrder([
            'project_id' => $projectId,
            'fa_order_no' => $orderNo,
            'fa_trans_type' => $transType,
            'source' => $source,
            'source_order_id' => $sourceOrderId,
        ]);

        $revenueId = $this->repo->recordRevenue([
            'project_id' => $projectId,
            'fa_order_no' => $orderNo,
            'fa_trans_type' => $transType,
            'source' => $source,
            'source_order_id' => $sourceOrderId,
            'order_total' => $orderTotal,
            'revenue_amount' => $orderTotal,
            'order_date' => $orderDate,
        ]);

        return ['link_id' => $linkId, 'revenue_id' => $revenueId];
    }

    /**
     * Get active projects for a customer.
     *
     * @param int $customerId debtors_master.debtor_no
     * @return array[] Project rows
     */
    public function getActiveProjectsForCustomer(int $customerId): array
    {
        return $this->repo->findProjectsForCustomer($customerId);
    }

    /**
     * List sales-order links for a project.
     *
     * @param string $projectId Project id
     * @return array
     */
    public function listLinksByProject(string $projectId): array
    {
        return $this->repo->findLinksByProject($projectId);
    }

    /**
     * List revenue rows for a project.
     *
     * @param string $projectId Project id
     * @return array
     */
    public function listRevenueByProject(string $projectId): array
    {
        return $this->repo->findRevenueByProject($projectId);
    }

    /**
     * Get the revenue summary for a project.
     *
     * @param string $projectId Project id
     * @return array Revenue total and order count
     */
    public function getRevenueSummary(string $projectId): array
    {
        return $this->repo->getRevenueSummaryByProject($projectId);
    }
}
