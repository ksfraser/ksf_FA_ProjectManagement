# FR-PROJECT-001-002 — Milestone Management

**Parent:** BR-PROJECT-001 (Fixed Price Contracts)
**Status:** Proposed

## Functional Requirement

### 1. Overview

The system SHALL provide the ability to define, manage, and track milestones for fixed-price contracts. Milestones represent deliverables and payment triggers within the contract lifecycle.

### 2. Input Fields

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `contract_id` | int | Yes | Must exist in `0_contracts` |
| `milestone_name` | string(200) | Yes | Max 200 chars |
| `description` | text | No | Max 1000 chars |
| `sort_order` | int | Yes | >= 0 |
| `payment_amount` | decimal(20,4) | Yes | >= 0 |
| `percentage` | decimal(5,2) | Yes | 0-100 |
| `due_date` | date | No | Valid date format |

### 3. Business Rules

1. `contract_id` MUST reference an existing, active contract
2. `payment_amount` + SUM(other milestones) <= contract `total_value`
3. `percentage` values for all milestones under a contract SHOULD sum to 100
4. `sort_order` determines the sequence of milestones
5. Status defaults to `pending`
6. Once status is `completed` or `billed`, status cannot be changed back to `pending`

### 4. Behavior

#### 4.1 Create Milestone Flow

```
User submits milestone form
        │
        ▼
Validate contract exists and is active
        │
        ▼
Validate payment amounts do not exceed contract total
        │
        ▼
Insert into 0_contract_milestones
        │
        ▼
Return milestone_id and success message
```

#### 4.2 Complete Milestone Flow

```
User marks milestone as complete
        │
        ▼
Check milestone is in 'pending' or 'in_progress' status
        │
        ▼
Set status to 'completed'
        │
        ▼
Set completed_at to current datetime
        │
        ▼
Fire hook 'contract_milestone_completed'
```

#### 4.3 Status Transitions

```
pending ──────▶ in_progress
    │                 │
    │                 ▼
    │            completed
    │                 │
    │                 ▼
    └─────────────── billed
```

### 5. Service Methods

```php
class MilestoneService
{
    /**
     * Create a new milestone for a contract.
     *
     * @param array $data Milestone data
     * @return MilestoneCreationResult
     * @BABOK Related: FR-PROJECT-001-002
     * @since 1.0.0
     */
    public function create(array $data): MilestoneCreationResult;

    /**
     * Update milestone details.
     *
     * @param int $milestoneId
     * @param array $data
     * @return bool
     * @BABOK Related: FR-PROJECT-001-002
     * @since 1.0.0
     */
    public function update(int $milestoneId, array $data): bool;

    /**
     * Mark a milestone as completed.
     *
     * @param int $milestoneId
     * @return bool
     * @BABOK Related: FR-PROJECT-001-002
     * @since 1.0.0
     */
    public function complete(int $milestoneId): bool;

    /**
     * Get all milestones for a contract.
     *
     * @param int $contractId
     * @return MilestoneEntity[]
     * @BABOK Related: FR-PROJECT-001-002
     * @since 1.0.0
     */
    public function getByContract(int $contractId): array;

    /**
     * Get billable (completed but not yet billed) milestones.
     *
     * @param int $contractId
     * @return MilestoneEntity[]
     * @BABOK Related: FR-PROJECT-001-002
     * @since 1.0.0
     */
    public function getBillableMilestones(int $contractId): array;
}
```

### 6. Validation Failures

| Condition | Error Message |
|-----------|---------------|
| Missing required field | "{Field name} is required" |
| Invalid contract_id | "Contract not found" |
| Contract not active | "Contract must be active to add milestones" |
| Payment exceeds contract value | "Total milestone payments exceed contract value" |
| percentage out of range | "Percentage must be between 0 and 100" |
| Cannot modify billed milestone | "Cannot modify billed milestone" |

### 7. Sample Input

```php
$data = [
    'contract_id' => 1,
    'milestone_name' => 'Design Phase Complete',
    'description' => 'UI/UX design approved by client',
    'sort_order' => 1,
    'payment_amount' => 10000.00,
    'percentage' => 20.00,
    'due_date' => '2024-02-15',
];
```

---

## Acceptance Criteria

- [ ] Milestone can be created for an active contract
- [ ] Payment amounts validated against contract total
- [ ] Percentage validated (0-100 range)
- [ ] sort_order controls display sequence
- [ ] Milestone can be marked as completed
- [ ] completed_at is set when milestone is completed
- [ ] Completed milestone triggers hook
- [ ] Billed milestones cannot be modified
- [ ] List milestones by contract

---

## Related

- BR-PROJECT-001 (Fixed Price Contracts)
- FR-PROJECT-001-001 (Contract Creation)
- FR-PROJECT-001-003 (Progress Tracking)
- FR-PROJECT-001-005 (Contract Billing)
