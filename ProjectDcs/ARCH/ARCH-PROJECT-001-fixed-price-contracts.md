# ARCH-PROJECT-001 — Fixed Price Contracts Architecture

**Parent:** BR-PROJECT-001 (Fixed Price Contracts)
**Module:** ksf_FA_ProjectManagement
**Status:** Proposed
**PHP:** 7.3+

## 1. System Context

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        Fixed Price Contracts                           │
│                                                                         │
│  ┌──────────────┐    ┌──────────────────┐    ┌──────────────────────┐  │
│  │   Contracts   │───▶│   Milestones     │───▶│   Billing/Invoice   │  │
│  │  (0_contracts)│    │(0_contract_      │    │ (FA Sales Invoice)  │  │
│  │               │    │ milestones)      │    │                      │  │
│  └──────────────┘    └──────────────────┘    └──────────────────────┘  │
│         │                     │                        ▲                │
│         │                     │                        │                │
│         ▼                     ▼                        │                │
│  ┌──────────────┐    ┌──────────────────┐             │                │
│  │ Contract BOM │    │   Change Orders  │             │                │
│  │(0_contract_  │    │(0_contract_     │             │                │
│  │  bom)        │    │ change_orders)   │─────────────┘                │
│  └──────────────┘    └──────────────────┘                              │
│         │                                                               │
│         ▼                                                               │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                     Cost Tracking                                │  │
│  │         (labor + materials + overhead vs contract value)         │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
```

### 1.1 Business Flow

1. **Contract Creation** → Customer signs fixed-price agreement
2. **Milestone Definition** → Define deliverables and payment schedule
3. **Work Execution** → Track progress against milestones
4. **Change Orders** → Handle scope changes (approved only)
5. **Milestone Billing** → Generate invoice when milestone achieved
6. **Cost Tracking** → Monitor actual vs budgeted costs

### 1.2 Integration with FA Modules

| External Module | Integration Point | Direction |
|-----------------|-------------------|-----------|
| FA Customers | `debtors_master` | Read customer data |
| FA Sales | `sales_orders` | Link to billing |
| FA Invoice | `sales_invoices` | Generate milestone invoices |
| FA Inventory | `stock_master` | BOM materials |
| FA GL | `gl_trans` | Revenue recognition |

---

## 2. Component Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                     FrontAccounting (FA)                             │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │              ksf_FA_ProjectManagement                        │   │
│  │                                                              │   │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐│   │
│  │  │ ContractService │  │MilestoneService │  │ChangeOrder  ││   │
│  │  │                 │  │                 │  │  Service    ││   │
│  │  │ - create()      │  │ - create()      │  │             ││   │
│  │  │ - update()      │  │ - update()      │  │ - create()  ││   │
│  │  │ - getById()     │  │ - complete()    │  │ - approve() ││   │
│  │  │ - getAll()      │  │ - getByContract │  │ - reject()  ││   │
│  │  │ - delete()      │  │                 │  │             ││   │
│  │  └────────┬────────┘  └────────┬────────┘  └──────┬──────┘│   │
│  │           │                    │                   │        │   │
│  │  ┌────────┴────────────────────┴───────────────────┴──────┐│   │
│  │  │              Repository Layer                           ││   │
│  │  │  ContractRepository | MilestoneRepository | ChangeOrder ││   │
│  │  │  FaDbAdapter (FA native db_* calls, PHP 7.3)            ││   │
│  │  └──────────────────────────────────────────────────────────┘│   │
│  └─────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

### 2.1 ContractService

**Namespace:** `ksfraser\FrontAccounting\ProjectManagement\Service`
**File:** `src/Service/ContractService.php`

```php
class ContractService
{
    public function create(array $data): ContractEntity;
    public function update(int $contractId, array $data): bool;
    public function getById(int $contractId): ContractEntity;
    public function getAll(array $filters = []): array;
    public function delete(int $contractId): bool;
    public function getProgress(int $contractId): float;
}
```

### 2.2 MilestoneService

**Namespace:** `ksfraser\FrontAccounting\ProjectManagement\Service`
**File:** `src/Service/MilestoneService.php`

```php
class MilestoneService
{
    public function create(array $data): MilestoneEntity;
    public function update(int $milestoneId, array $data): bool;
    public function complete(int $milestoneId): bool;
    public function getByContract(int $contractId): array;
    public function getBillableMilestones(int $contractId): array;
}
```

### 2.3 ChangeOrderService

**Namespace:** `ksfraser\FrontAccounting\ProjectManagement\Service`
**File:** `src/Service/ChangeOrderService.php`

```php
class ChangeOrderService
{
    public function create(array $data): ChangeOrderEntity;
    public function approve(int $changeOrderId): bool;
    public function reject(int $changeOrderId): bool;
    public function getByContract(int $contractId): array;
    public function getPendingByContract(int $contractId): array;
}
```

### 2.4 BillingService

**Namespace:** `ksfraser\FrontAccounting\ProjectManagement\Service`
**File:** `src/Service/BillingService.php`

```php
class BillingService
{
    public function createInvoiceFromMilestone(int $milestoneId): int;
    public function getBilledAmount(int $contractId): float;
    public function getBilledMilestones(int $contractId): array;
}
```

### 2.5 CostTrackingService

**Namespace:** `ksfraser\FrontAccounting\ProjectManagement\Service`
**File:** `src/Service/CostTrackingService.php`

```php
class CostTrackingService
{
    public function getActualCost(int $contractId): float;
    public function getBudgetedCost(int $contractId): float;
    public function getVariance(int $contractId): float;
    public function addMaterialCost(int $contractId, float $cost): bool;
    public function addLaborCost(int $contractId, float $cost): bool;
}
```

---

## 3. Database Schema

### 3.1 `0_contracts`

```sql
CREATE TABLE IF NOT EXISTS `0_contracts` (
  `contract_id` INT NOT NULL AUTO_INCREMENT,
  `contract_ref` VARCHAR(50) NOT NULL,
  `customer_id` INT NOT NULL,
  `description` TEXT NOT NULL,
  `total_value` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `status` ENUM('draft','active','completed','cancelled') NOT NULL DEFAULT 'draft',
  `parent_contract_id` INT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`contract_id`),
  UNIQUE KEY `uk_contract_ref` (`contract_ref`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_parent` (`parent_contract_id`),
  CONSTRAINT `fk_contracts_customer` FOREIGN KEY (`customer_id`) REFERENCES `0_debtors_master` (`debtor_no`),
  CONSTRAINT `fk_contracts_parent` FOREIGN KEY (`parent_contract_id`) REFERENCES `0_contracts` (`contract_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.2 `0_contract_milestones`

```sql
CREATE TABLE IF NOT EXISTS `0_contract_milestones` (
  `milestone_id` INT NOT NULL AUTO_INCREMENT,
  `contract_id` INT NOT NULL,
  `milestone_name` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `payment_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `percentage` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `due_date` DATE DEFAULT NULL,
  `status` ENUM('pending','in_progress','completed','billed') NOT NULL DEFAULT 'pending',
  `completed_at` DATETIME DEFAULT NULL,
  `billing_transaction_id` INT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`milestone_id`),
  KEY `idx_contract` (`contract_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_milestones_contract` FOREIGN KEY (`contract_id`) REFERENCES `0_contracts` (`contract_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.3 `0_contract_bom`

```sql
CREATE TABLE IF NOT EXISTS `0_contract_bom` (
  `bom_id` INT NOT NULL AUTO_INCREMENT,
  `contract_id` INT NOT NULL,
  `item_type` ENUM('material','labor','overhead','service') NOT NULL,
  `item_id` INT DEFAULT NULL,
  `item_description` VARCHAR(500) NOT NULL,
  `quantity` DECIMAL(15,4) NOT NULL DEFAULT 1,
  `unit_cost` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `total_cost` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `actual_cost` DECIMAL(20,4) DEFAULT NULL,
  `supplier_id` INT DEFAULT NULL,
  `po_reference` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('planned','ordered','received','invoiced') NOT NULL DEFAULT 'planned',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`bom_id`),
  KEY `idx_contract` (`contract_id`),
  KEY `idx_item_type` (`item_type`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_bom_contract` FOREIGN KEY (`contract_id`) REFERENCES `0_contracts` (`contract_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.4 `0_contract_change_orders`

```sql
CREATE TABLE IF NOT EXISTS `0_contract_change_orders` (
  `change_order_id` INT NOT NULL AUTO_INCREMENT,
  `contract_id` INT NOT NULL,
  `change_order_ref` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` INT DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `reason` TEXT DEFAULT NULL,
  `original_contract_value` DECIMAL(20,4) NOT NULL,
  `new_contract_value` DECIMAL(20,4) NOT NULL,
  `created_by` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`change_order_id`),
  UNIQUE KEY `uk_change_order_ref` (`change_order_ref`),
  KEY `idx_contract` (`contract_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_change_orders_contract` FOREIGN KEY (`contract_id`) REFERENCES `0_contracts` (`contract_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. Entity Classes

### 4.1 ContractEntity

```php
namespace ksfraser\FrontAccounting\ProjectManagement\Entity;

class ContractEntity
{
    var $contractId;
    var $contractRef;
    var $customerId;
    var $description;
    var $totalValue;
    var $startDate;
    var $endDate;
    var $status;
    var $parentContractId;
    var $notes;
    var $createdBy;
    var $createdAt;
    var $updatedAt;
}
```

### 4.2 MilestoneEntity

```php
namespace ksfraser\FrontAccounting\ProjectManagement\Entity;

class MilestoneEntity
{
    var $milestoneId;
    var $contractId;
    var $milestoneName;
    var $description;
    var $sortOrder;
    var $paymentAmount;
    var $percentage;
    var $dueDate;
    var $status;
    var $completedAt;
    var $billingTransactionId;
    var $notes;
    var $createdAt;
    var $updatedAt;
}
```

### 4.3 ChangeOrderEntity

```php
namespace ksfraser\FrontAccounting\ProjectManagement\Entity;

class ChangeOrderEntity
{
    var $changeOrderId;
    var $contractId;
    var $changeOrderRef;
    var $description;
    var $amount;
    var $status;
    var $approvedBy;
    var $approvedAt;
    var $reason;
    var $originalContractValue;
    var $newContractValue;
    var $createdBy;
    var $createdAt;
    var $updatedAt;
}
```

---

## 5. Repository Pattern

### 5.1 ContractRepository

```php
namespace ksfraser\FrontAccounting\ProjectManagement\Repository;

use ksfraser\CommonDb\Contract\DbConnectionInterface;

class ContractRepository
{
    private $db;

    public function __construct(DbConnectionInterface $db);
    public function create(ContractEntity $entity): int;
    public function update(ContractEntity $entity): bool;
    public function findById(int $id): ?ContractEntity;
    public function findAll(array $filters = []): array;
    public function delete(int $id): bool;
    public function getProgress(int $contractId): float;
}
```

### 5.2 FaDbAdapter Usage (PHP 7.3 Compatible)

```php
namespace ksfraser\FrontAccounting\ProjectManagement\Repository;

use ksfraser\CommonDb\Adapter\FaDbAdapter;

class ContractRepository
{
    private $db;

    public function __construct(FaDbAdapter $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?ContractEntity
    {
        $sql = "SELECT * FROM {$this->db->quote('0_contracts')} WHERE contract_id = ?";
        $result = $this->db->fetchAssoc($sql, [$id]);
        if (empty($result)) {
            return null;
        }
        return $this->hydrate($result);
    }
}
```

---

## 6. Integration with FA Sales/Invoice

### 6.1 Milestone to Invoice Flow

```
Milestone Completed
       │
       ▼
BillingService::createInvoiceFromMilestone()
       │
       ▼
FA Sales Invoice Created (using debtor_id from contract)
       │
       ▼
Milestone marked as 'billed' with transaction_id
```

### 6.2 Required FA Hooks

| Hook Name | Purpose |
|-----------|---------|
| `order_imported` | Link orders to contracts (existing) |
| `contract_milestone_completed` | Notify when milestone reaches 100% |
| `contract_value_changed` | Update total_value on change order approval |

---

## 7. Security

### 7.1 Security Areas

```php
define('SS_ksf_FA_ProjectManagement', 134 << 8);
define('SA_ksf_FA_PROJECTMANAGEMENT_VIEW', SS_ksf_FA_ProjectManagement | 1);
define('SA_ksf_FA_PROJECTMANAGEMENT_MANAGE', SS_ksf_FA_ProjectManagement | 2);
```

### 7.2 Page Security

```php
$page_security = 'SA_ksf_FA_PROJECTMANAGEMENT_VIEW';
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();
```

---

## 8. File Structure

```
ksf_FA_ProjectManagement/
├── src/
│   └── Ksfraser/
│       └── FrontAccounting/
│           └── ProjectManagement/
│               ├── Entity/
│               │   ├── ContractEntity.php
│               │   ├── MilestoneEntity.php
│               │   ├── ChangeOrderEntity.php
│               │   └── ContractBomEntity.php
│               ├── Repository/
│               │   ├── ContractRepository.php
│               │   ├── MilestoneRepository.php
│               │   ├── ChangeOrderRepository.php
│               │   └── FaRepositoryTrait.php
│               └── Service/
│                   ├── ContractService.php
│                   ├── MilestoneService.php
│                   ├── ChangeOrderService.php
│                   ├── BillingService.php
│                   └── CostTrackingService.php
├── sql/
│   └── install.sql
├── ProjectDcs/
│   ├── ARCH/
│   │   └── ARCH-PROJECT-001-fixed-price-contracts.md
│   ├── BR/
│   │   └── BR-PROJECT-001-fixed-price-contracts.md
│   ├── FR/
│   │   ├── FR-PROJECT-001-001.md
│   │   ├── FR-PROJECT-001-002.md
│   │   ├── FR-PROJECT-001-003.md
│   │   ├── FR-PROJECT-001-004.md
│   │   ├── FR-PROJECT-001-005.md
│   │   └── FR-PROJECT-001-006.md
│   ├── UT/
│   │   └── UT-PROJECT-001-001-001.md
│   ├── UAT/
│   │   └── UAT-PROJECT-001-fixed-price-contracts.md
│   └── RTM-PROJECT-001-fixed-price-contracts.md
└── hooks.php
```

---

## 9. Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `ksfraser/ksf-common-db` | ^1.0 | DbConnectionInterface, FaDbAdapter |
| `ksfraser/ksf_FA_Common` | ^2.4 | WorkflowHooksTrait, CrudOperationsTrait |

---

## 10. Related Documents

- BR-PROJECT-001 (Business Requirement)
- FR-PROJECT-001-001 through FR-PROJECT-001-006 (Functional Requirements)
- UT-PROJECT-001-001-001 (Unit Test)
- UAT-PROJECT-001-fixed-price-contracts (UAT Plan)
- RTM-PROJECT-001-fixed-price-contracts (Traceability Matrix)
