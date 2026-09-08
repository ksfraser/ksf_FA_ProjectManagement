# FR-PROJECT-001-003 — Progress Tracking

**Parent:** BR-PROJECT-001 (Fixed Price Contracts)
**Status:** Proposed

## Functional Requirement

### 1. Overview

The system SHALL provide the ability to track progress against contract milestones and overall contract completion. Progress is measured by milestone status and completion percentage.

### 2. Progress Calculation

#### 2.1 Contract-Level Progress

```
contract_progress = SUM(milestone_percentage * milestone_status_weight) / 100

Where status weights:
  - pending: 0
  - in_progress: 0.5
  - completed: 1.0
  - billed: 1.0
```

#### 2.2 Milestone-Level Progress

```
milestone_progress = based on status (0%, 50%, 100%, 100%)
```

### 3. Business Rules

1. Contract progress is calculated from milestone statuses
2. Progress percentage is displayed with 2 decimal precision
3. Progress can be filtered by: all, active, completed
4. Overdue milestones affect progress calculation based on due_date vs today

### 4. Behavior

#### 4.1 Get Contract Progress

```
User/System requests contract progress
        │
        ▼
Fetch all milestones for contract
        │
        ▼
Calculate weighted progress
        │
        ▼
Return progress percentage
```

#### 4.2 Progress Reporting

| Metric | Description |
|--------|-------------|
| Overall Progress | Weighted average of milestone progress |
| Completed Milestones | Count of milestones at 100% |
| Total Milestones | Count of all milestones |
| Pending Value | Sum of pending milestone amounts |
| Completed Value | Sum of completed milestone amounts |

### 5. Service Methods

```php
class ContractService
{
    /**
     * Calculate overall progress for a contract.
     *
     * @param int $contractId
     * @return float Progress percentage (0-100)
     * @BABOK Related: FR-PROJECT-001-003
     * @since 1.0.0
     */
    public function getProgress(int $contractId): float;

    /**
     * Get detailed progress report for a contract.
     *
     * @param int $contractId
     * @return array
     * @BABOK Related: FR-PROJECT-001-003
     * @since 1.0.0
     */
    public function getProgressReport(int $contractId): array;
}

class MilestoneService
{
    /**
     * Get milestones grouped by status.
     *
     * @param int $contractId
     * @return array<string, MilestoneEntity[]>
     * @BABOK Related: FR-PROJECT-001-003
     * @since 1.0.0
     */
    public function getMilestonesByStatus(int $contractId): array;

    /**
     * Get overdue milestones.
     *
     * @param int $contractId
     * @return MilestoneEntity[]
     * @BABOK Related: FR-PROJECT-001-003
     * @since 1.0.0
     */
    public function getOverdueMilestones(int $contractId): array;
}
```

### 6. Progress Report Output

```php
[
    'contract_id' => 1,
    'contract_ref' => 'CONTRACT-2024-001',
    'overall_progress' => 45.50,
    'milestones' => [
        'total' => 5,
        'pending' => 2,
        'in_progress' => 1,
        'completed' => 2,
        'billed' => 0,
    ],
    'values' => [
        'total_contract' => 50000.00,
        'billed' => 15000.00,
        'completed_not_billed' => 15000.00,
        'pending' => 20000.00,
    ],
    'overdue_milestones' => [
        ['milestone_id' => 3, 'name' => 'Phase 2', 'due_date' => '2024-01-01']
    ],
]
```

### 7. Visual Indicators

| Progress Range | Visual Indicator | Status Label |
|---------------|------------------|--------------|
| 0% | Red progress bar | Not Started |
| 1-49% | Orange progress bar | In Progress |
| 50-99% | Yellow progress bar | Near Completion |
| 100% | Green progress bar | Completed |

---

## Acceptance Criteria

- [ ] Overall contract progress calculated correctly
- [ ] Milestone status affects progress weighting
- [ ] Progress displayed with 2 decimal precision
- [ ] Overdue milestones identified and reported
- [ ] Progress report includes value breakdown
- [ ] Progress can be filtered by status
- [ ] Visual indicators match progress ranges

---

## Related

- BR-PROJECT-001 (Fixed Price Contracts)
- FR-PROJECT-001-001 (Contract Creation)
- FR-PROJECT-001-002 (Milestone Management)
- FR-PROJECT-001-005 (Contract Billing)
