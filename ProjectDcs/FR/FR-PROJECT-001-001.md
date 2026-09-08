# FR-PROJECT-001-001 — Contract Creation

**Parent:** BR-PROJECT-001 (Fixed Price Contracts)
**Status:** Proposed

## Functional Requirement

### 1. Overview

The system SHALL provide the ability to create fixed-price contracts with all necessary header information, validation, and integration with FA customer records.

### 2. Input Fields

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `contract_ref` | string(50) | Yes | Unique, alphanumeric + dashes |
| `customer_id` | int | Yes | Must exist in `0_debtors_master` |
| `description` | text | Yes | Max 1000 chars |
| `total_value` | decimal(20,4) | Yes | > 0 |
| `start_date` | date | Yes | Valid date format |
| `end_date` | date | No | Must be >= start_date if provided |
| `notes` | text | No | Max 2000 chars |

### 3. Business Rules

1. `contract_ref` MUST be unique across all contracts
2. `total_value` MUST equal the sum of all milestone `payment_amount` values
3. `customer_id` MUST reference an existing customer in `debtors_master`
4. Status defaults to `draft` on creation
5. `created_by` MUST be set to the current user ID from session
6. `created_at` MUST be set to current datetime

### 4. Behavior

#### 4.1 Create Contract Flow

```
User submits contract form
        │
        ▼
Validate all required fields
        │
        ▼
Check contract_ref uniqueness
        │
        ▼
Validate customer exists
        │
        ▼
Insert into 0_contracts
        │
        ▼
Return contract_id and success message
```

#### 4.2 Validation Failures

| Condition | Error Message |
|-----------|---------------|
| Missing required field | "{Field name} is required" |
| Duplicate contract_ref | "Contract reference already exists" |
| Invalid customer_id | "Customer not found" |
| end_date < start_date | "End date must be after start date" |
| total_value <= 0 | "Contract value must be greater than zero" |

### 5. Output

```php
interface ContractCreationResult
{
    success: bool;
    contract_id?: int;
    errors?: array<string, string>;
}
```

### 6. Service Method

```php
class ContractService
{
    /**
     * Create a new fixed-price contract.
     *
     * @param array $data Contract data
     * @return ContractCreationResult
     * @throws ValidationException
     *
     * @BABOK Related: FR-PROJECT-001-001
     * @since 1.0.0
     */
    public function create(array $data): ContractCreationResult;
}
```

### 7. Sample Input

```php
$data = [
    'contract_ref' => 'CONTRACT-2024-001',
    'customer_id' => 123,
    'description' => 'Custom software development project',
    'total_value' => 50000.00,
    'start_date' => '2024-01-15',
    'end_date' => '2024-06-30',
    'notes' => 'Includes UAT phase',
];
```

### 8. Sample Success Output

```php
[
    'success' => true,
    'contract_id' => 1,
    'errors' => []
]
```

### 9. Sample Error Output

```php
[
    'success' => false,
    'contract_id' => null,
    'errors' => [
        'contract_ref' => 'Contract reference already exists',
    ]
]
```

---

## Acceptance Criteria

- [ ] Contract can be created with all required fields
- [ ] Unique contract_ref is enforced
- [ ] Customer validation against debtors_master
- [ ] Dates are validated (end_date >= start_date)
- [ ] total_value > 0 validation
- [ ] Status defaults to 'draft'
- [ ] created_by set from session user
- [ ] created_at set to current datetime
- [ ] Appropriate error messages returned on validation failure

---

## Related

- BR-PROJECT-001 (Fixed Price Contracts)
- FR-PROJECT-001-002 (Milestone Management)
- FR-PROJECT-001-005 (Contract Billing)
