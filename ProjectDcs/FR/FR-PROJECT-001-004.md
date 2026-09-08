# FR-PROJECT-001-004 — Change Orders

**Parent:** BR-PROJECT-001 (Fixed Price Contracts)
**Status:** Proposed

## Functional Requirement

### 1. Overview

The system SHALL provide the ability to manage change orders for fixed-price contracts. Change orders represent approved scope changes that affect contract value.

### 2. Input Fields

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `contract_id` | int | Yes | Must exist in `0_contracts` |
| `change_order_ref` | string(50) | Yes | Unique per contract |
| `description` | text | Yes | Max 1000 chars |
| `amount` | decimal(20,4) | Yes | Can be positive or negative |
| `reason` | text | Yes | Max 1000 chars |
| `status` | enum | No | Defaults to 'pending' |

### 3. Business Rules

1. `contract_id` MUST reference an existing contract
2. `change_order_ref` MUST be unique across all change orders
3. `amount` can be positive (add to contract) or negative (deduct from contract)
4. Only change orders with status `approved` affect contract `total_value`
5. `approved_by` MUST be set when status changes to `approved`
6. `approved_at` MUST be set when status changes to `approved`
7. Contract `total_value` is updated when change order is approved

### 4. Change Order States

```
                    ┌──────────────┐
         ┌─────────▶│   pending    │
         │          └──────┬───────┘
         │                 │
         │     ┌───────────┴───────────┐
         │     ▼                       ▼
         │ ┌────────────┐        ┌────────────┐
         │ │  approved  │        │  rejected  │
         │ └────────────┘        └────────────┘
         │         │
         │         │ (no status change allowed)
         │         ▼
         │ ┌──────────────────────────────┐
         └─│  no transitions from either  │
           └──────────────────────────────┘
```

### 5. Behavior

#### 5.1 Create Change Order Flow

```
User submits change order form
        │
        ▼
Validate required fields
        │
        ▼
Validate contract exists
        │
        ▼
Generate change_order_ref if not provided
        │
        ▼
Set status to 'pending'
        │
        ▼
Set created_by from session
        │
        ▼
Insert into 0_contract_change_orders
        │
        ▼
Return change_order_id
```

#### 5.2 Approve Change Order Flow

```
User approves change order
        │
        ▼
Check status is 'pending'
        │
        ▼
Set approved_by from session user
        │
        ▼
Set approved_at to current datetime
        │
        ▼
Set status to 'approved'
        │
        ▼
Update contract total_value (+ amount)
        │
        ▼
Fire hook 'contract_value_changed'
```

#### 5.3 Reject Change Order Flow

```
User rejects change order
        │
        ▼
Check status is 'pending'
        │
        ▼
Set status to 'rejected'
        │
        ▼
Return success (no contract value change)
```

### 6. Service Methods

```php
class ChangeOrderService
{
    /**
     * Create a new change order.
     *
     * @param array $data Change order data
     * @return ChangeOrderCreationResult
     * @BABOK Related: FR-PROJECT-001-004
     * @since 1.0.0
     */
    public function create(array $data): ChangeOrderCreationResult;

    /**
     * Approve a pending change order.
     *
     * @param int $changeOrderId
     * @return bool
     * @throws InvalidStateException
     * @BABOK Related: FR-PROJECT-001-004
     * @since 1.0.0
     */
    public function approve(int $changeOrderId): bool;

    /**
     * Reject a pending change order.
     *
     * @param int $changeOrderId
     * @param string $reason
     * @return bool
     * @BABOK Related: FR-PROJECT-001-004
     * @since 1.0.0
     */
    public function reject(int $changeOrderId, string $reason): bool;

    /**
     * Get all change orders for a contract.
     *
     * @param int $contractId
     * @return ChangeOrderEntity[]
     * @BABOK Related: FR-PROJECT-001-004
     * @since 1.0.0
     */
    public function getByContract(int $contractId): array;

    /**
     * Get pending change orders for a contract.
     *
     * @param int $contractId
     * @return ChangeOrderEntity[]
     * @BABOK Related: FR-PROJECT-001-004
     * @since 1.0.0
     */
    public function getPendingByContract(int $contractId): array;
}
```

### 7. Validation Failures

| Condition | Error Message |
|-----------|---------------|
| Missing required field | "{Field name} is required" |
| Invalid contract_id | "Contract not found" |
| Duplicate change_order_ref | "Change order reference already exists" |
| Amount is zero | "Change order amount cannot be zero" |
| Contract not active | "Contract must be active to add change orders" |
| Approve non-pending | "Only pending change orders can be approved" |

### 8. Sample Input

```php
$data = [
    'contract_id' => 1,
    'change_order_ref' => 'CO-001',
    'description' => 'Add additional reporting module',
    'amount' => 5000.00,
    'reason' => 'Client requested additional features during planning phase',
];
```

### 9. Contract Value Update on Approval

```
Original contract total_value: 50000.00
Change order amount: +5000.00
New contract total_value: 55000.00
```

---

## Acceptance Criteria

- [ ] Change order can be created for active contract
- [ ] Unique change_order_ref enforced
- [ ] Positive and negative amounts supported
- [ ] Only pending change orders can be approved/rejected
- [ ] Approval updates contract total_value
- [ ] approved_by and approved_at set on approval
- [ ] Rejection reason is recorded
- [ ] Hook fired on approval

---

## Related

- BR-PROJECT-001 (Fixed Price Contracts)
- FR-PROJECT-001-001 (Contract Creation)
- FR-PROJECT-001-005 (Contract Billing)
