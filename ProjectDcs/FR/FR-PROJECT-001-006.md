# FR-PROJECT-001-006 — Cost Tracking

**Parent:** BR-PROJECT-001 (Fixed Price Contracts)
**Status:** Proposed

## Functional Requirement

### 1. Overview

The system SHALL provide the ability to track actual costs against contract value, including materials, labor, and overhead costs.

### 2. Cost Categories

| Category | Description | Source |
|----------|-------------|--------|
| material | Physical materials/items | Contract BOM or manual entry |
| labor | Employee time and wages | FA Timesheets integration |
| overhead | Indirect costs | Manual entry |
| service | External services | Manual entry |

### 3. Contract BOM (Bill of Materials)

The `0_contract_bom` table tracks planned vs actual costs.

### 4. Business Rules

1. Costs can be added at any time during contract lifecycle
2. Costs can be positive (actual cost) entries
3. `actual_cost` may be updated after initial `unit_cost` entry
4. Total actual cost = SUM(`actual_cost`) for all BOM lines
5. Budgeted cost = SUM(`quantity` * `unit_cost`) for planned BOM lines
6. Variance = Budgeted cost - Actual cost (positive = under budget)
7. Cost tracking does NOT automatically affect milestone status

### 5. Behavior

#### 5.1 Add Cost Entry Flow

```
User adds cost entry to contract
        │
        ▼
Validate required fields
        │
        ▼
Determine if item_type is 'material' and item_id provided
        │
        ▼
Calculate total_cost = quantity * unit_cost
        │
        ▼
Insert into 0_contract_bom
        │
        ▼
Return bom_id
```

#### 5.2 Update Actual Cost Flow

```
User updates actual cost for BOM line
        │
        ▼
Verify bom_id exists and belongs to contract
        │
        ▼
Update actual_cost
        │
        ▼
Return success
```

### 6. Service Methods

```php
class CostTrackingService
{
    /**
     * Get actual cost for a contract.
     *
     * @param int $contractId
     * @return float
     * @BABOK Related: FR-PROJECT-001-006
     * @since 1.0.0
     */
    public function getActualCost(int $contractId): float;

    /**
     * Get budgeted cost for a contract.
     *
     * @param int $contractId
     * @return float
     * @BABOK Related: FR-PROJECT-001-006
     * @since 1.0.0
     */
    public function getBudgetedCost(int $contractId): float;

    /**
     * Calculate variance (budgeted - actual).
     *
     * @param int $contractId
     * @return float Positive = under budget
     * @BABOK Related: FR-PROJECT-001-006
     * @since 1.0.0
     */
    public function getVariance(int $contractId): float;

    /**
     * Add material cost to contract.
     *
     * @param int $contractId
     * @param float $cost
     * @param array $details
     * @return int bom_id
     * @BABOK Related: FR-PROJECT-001-006
     * @since 1.0.0
     */
    public function addMaterialCost(int $contractId, float $cost, array $details = []): int;

    /**
     * Add labor cost to contract.
     *
     * @param int $contractId
     * @param float $cost
     * @param array $details
     * @return int bom_id
     * @BABOK Related: FR-PROJECT-001-006
     * @since 1.0.0
     */
    public function addLaborCost(int $contractId, float $cost, array $details = []): int;

    /**
     * Get cost breakdown by category.
     *
     * @param int $contractId
     * @return array<string, float>
     * @BABOK Related: FR-PROJECT-001-006
     * @since 1.0.0
     */
    public function getCostBreakdown(int $contractId): array;
}
```

### 7. Cost Report Output

```php
[
    'contract_id' => 1,
    'contract_ref' => 'CONTRACT-2024-001',
    'total_value' => 50000.00,
    'budgeted_cost' => 35000.00,
    'actual_cost' => 28000.00,
    'variance' => 7000.00,
    'profit_margin' => 22000.00,
    'margin_percentage' => 44.00,
    'breakdown' => [
        'material' => 15000.00,
        'labor' => 10000.00,
        'overhead' => 2000.00,
        'service' => 1000.00,
    ],
    'bom_items' => [
        [
            'bom_id' => 1,
            'item_type' => 'material',
            'description' => 'Steel beams',
            'quantity' => 100,
            'unit_cost' => 50.00,
            'total_cost' => 5000.00,
            'actual_cost' => 4800.00,
            'status' => 'received',
        ],
    ],
]
```

### 8. BOM Item Status Transitions

```
planned ──────▶ ordered
    │               │
    │               ▼
    │          received
    │               │
    │               ▼
    └─────────── invoiced
```

### 9. Validation Failures

| Condition | Error Message |
|-----------|---------------|
| Invalid contract_id | "Contract not found" |
| Negative cost | "Cost cannot be negative" |
| Invalid quantity | "Quantity must be greater than zero" |
| Invalid unit_cost | "Unit cost cannot be negative" |

---

## Acceptance Criteria

- [ ] Material costs can be added to contract
- [ ] Labor costs can be added to contract
- [ ] Overhead costs can be added to contract
- [ ] Service costs can be added to contract
- [ ] Actual costs can be updated
- [ ] BOM status transitions work correctly
- [ ] Actual cost calculated correctly
- [ ] Budgeted cost calculated correctly
- [ ] Variance calculated correctly
- [ ] Cost breakdown by category accurate
- [ ] Profit margin calculated correctly

---

## Related

- BR-PROJECT-001 (Fixed Price Contracts)
- FR-PROJECT-001-001 (Contract Creation)
- FR-PROJECT-001-002 (Milestone Management)
