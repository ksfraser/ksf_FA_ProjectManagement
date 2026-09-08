# FR-PROJECT-001-005 — Contract Billing

**Parent:** BR-PROJECT-001 (Fixed Price Contracts)
**Status:** Proposed

## Functional Requirement

### 1. Overview

The system SHALL provide the ability to generate invoices from completed milestones and track billing status for fixed-price contracts.

### 2. Integration with FA Sales/Invoice

Billing integrates with FA's existing invoice system using the standard FA hooks and database tables.

### 3. Business Rules

1. Only milestones with status `completed` can be billed
2. Billing creates a sales invoice linked to the contract's customer
3. Milestone status changes to `billed` after invoice creation
4. `billing_transaction_id` is stored to link milestone to invoice
5. Invoice includes contract reference in memo/description
6. Partial billing is not allowed (milestone billed in full or not at all)

### 4. Behavior

#### 4.1 Create Invoice from Milestone Flow

```
User initiates billing for milestone
        │
        ▼
Verify milestone status is 'completed'
        │
        ▼
Get contract and customer info
        │
        ▼
Create FA sales invoice via FA API
        │
        ▼
Get invoice transaction_id from FA
        │
        ▼
Update milestone status to 'billed'
        │
        ▼
Store billing_transaction_id
        │
        ▼
Fire hook 'contract_milestone_billed'
```

#### 4.2 Invoice Creation Details

| Field | Source |
|-------|--------|
| debtor_id | contract.customer_id |
| branch_id | Default branch for customer |
| lines | Single line: milestone description |
| amount | milestone.payment_amount |
| reference | contract.contract_ref + "-" + milestone.milestone_id |
| notes | "Milestone billing for " + milestone.milestone_name |

### 5. Service Methods

```php
class BillingService
{
    /**
     * Create an invoice from a completed milestone.
     *
     * @param int $milestoneId
     * @return int Invoice transaction_id
     * @throws ValidationException
     * @throws BillingException
     * @BABOK Related: FR-PROJECT-001-005
     * @since 1.0.0
     */
    public function createInvoiceFromMilestone(int $milestoneId): int;

    /**
     * Get total billed amount for a contract.
     *
     * @param int $contractId
     * @return float
     * @BABOK Related: FR-PROJECT-001-005
     * @since 1.0.0
     */
    public function getBilledAmount(int $contractId): float;

    /**
     * Get all billed milestones for a contract.
     *
     * @param int $contractId
     * @return MilestoneEntity[]
     * @BABOK Related: FR-PROJECT-001-005
     * @since 1.0.0
     */
    public function getBilledMilestones(int $contractId): array;

    /**
     * Get pending (completed but not billed) milestones.
     *
     * @param int $contractId
     * @return MilestoneEntity[]
     * @BABOK Related: FR-PROJECT-001-005
     * @since 1.0.0
     */
    public function getPendingBilling(int $contractId): array;
}
```

### 6. Validation Failures

| Condition | Error Message |
|-----------|---------------|
| Milestone not completed | "Milestone must be completed before billing" |
| Milestone already billed | "Milestone has already been billed" |
| Customer not found | "Contract customer not found" |
| Invoice creation failed | "Failed to create invoice: {FA error}" |

### 7. Billing Report

```php
[
    'contract_id' => 1,
    'contract_ref' => 'CONTRACT-2024-001',
    'customer_id' => 123,
    'total_value' => 50000.00,
    'billed_amount' => 15000.00,
    'pending_amount' => 35000.00,
    'billed_milestones' => [
        [
            'milestone_id' => 1,
            'name' => 'Design Phase',
            'amount' => 10000.00,
            'invoice_id' => 5001,
            'billed_at' => '2024-02-15 10:30:00',
        ],
        [
            'milestone_id' => 2,
            'name' => 'Development Phase',
            'amount' => 5000.00,
            'invoice_id' => 5002,
            'billed_at' => '2024-03-20 14:15:00',
        ],
    ],
    'pending_milestones' => [
        [
            'milestone_id' => 3,
            'name' => 'Testing Phase',
            'amount' => 15000.00,
        ],
        [
            'milestone_id' => 4,
            'name' => 'Deployment',
            'amount' => 20000.00,
        ],
    ],
]
```

### 8. Hook Events

| Hook Name | Payload | Purpose |
|-----------|---------|---------|
| `contract_milestone_billed` | milestone, invoice_id | Notify external systems |

---

## Acceptance Criteria

- [ ] Invoice created from completed milestone
- [ ] Milestone status updated to 'billed'
- [ ] billing_transaction_id stored
- [ ] Billed milestones cannot be re-billed
- [ ] Billed amount sum matches invoice totals
- [ ] FA invoice integration works correctly
- [ ] Customer from contract used for invoice
- [ ] Billing report accurate

---

## Related

- BR-PROJECT-001 (Fixed Price Contracts)
- FR-PROJECT-001-002 (Milestone Management)
- FR-PROJECT-001-003 (Progress Tracking)
