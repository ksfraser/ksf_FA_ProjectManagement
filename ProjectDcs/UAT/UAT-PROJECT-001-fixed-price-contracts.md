# UAT-PROJECT-001 — Fixed Price Contracts UAT Plan

**Module:** ksf_FA_ProjectManagement
**Status:** Proposed

## 1. UAT Objectives

### 1.1 Goals

- Validate fixed-price contract creation and management workflows
- Confirm milestone-based billing functions correctly
- Ensure change order approval affects contract value
- Verify progress tracking and cost tracking work as expected
- Obtain sign-off for production deployment

### 1.2 Success Criteria

- All critical test cases pass
- No high-severity defects open
- User acceptance obtained
- Sign-off documented

---

## 2. UAT Scope

### 2.1 In Scope

- Contract CRUD operations
- Milestone management and completion
- Change order creation, approval, rejection
- Milestone billing and invoice generation
- Progress tracking and reporting
- Cost tracking and variance reporting
- FA customer integration

### 2.2 Out of Scope

- Time and materials contracts
- Resource scheduling
- Performance stress testing
- Security penetration testing

---

## 3. UAT User Roles

| Role | Description | Tests Executed |
|------|-------------|----------------|
| Project Manager | Manages contracts and milestones | FPC-001 through FPC-012 |
| Account Manager | Reviews billing and costs | FPC-013 through FPC-015 |
| Administrator | System configuration | FPC-016 |

---

## 4. UAT Test Cases

### 4.1 Contract Management (FPC-001)

#### UAT-FPC-001: Create Fixed Price Contract

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-001 |
| Scenario | Create a new fixed-price contract |
| Preconditions | User has PROJECT_MANAGE permission, customer exists in FA |
| Test Steps | 1. Navigate to Contracts |
| | 2. Click "New Contract" |
| | 3. Enter: Contract Ref = "UAT-CON-001", Customer = existing customer |
| | 4. Enter: Description = "UAT Test Contract", Total Value = 50000 |
| | 5. Set: Start Date = today, End Date = today + 6 months |
| | 6. Click Save |
| Expected Result | Contract created with status "draft" |
| Acceptance Criteria | [ ] Contract saved to database |
| | [ ] Contract appears in contract list |
| | [ ] Status defaults to draft |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-002: View Contract Details

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-002 |
| Scenario | View contract details and milestones |
| Preconditions | Contract exists from UAT-FPC-001 |
| Test Steps | 1. Navigate to Contracts |
| | 2. Click View on contract UAT-CON-001 |
| Expected Result | Contract details displayed with milestones section |
| Acceptance Criteria | [ ] Contract ref, customer, dates displayed |
| | [ ] Total value shown |
| | [ ] Milestones section visible (empty) |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-003: Edit Contract Details

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-003 |
| Scenario | Modify contract details |
| Preconditions | Contract exists |
| Test Steps | 1. Edit contract UAT-CON-001 |
| | 2. Change description to "UAT Modified Contract" |
| | 3. Change status to "Active" |
| | 4. Save |
| Expected Result | Changes saved successfully |
| Acceptance Criteria | [ ] Description updated |
| | [ ] Status changed to Active |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-004: Contract Validation - Duplicate Ref

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-004 |
| Scenario | Attempt to create contract with duplicate reference |
| Preconditions | Contract UAT-CON-001 exists |
| Test Steps | 1. Create new contract |
| | 2. Enter Contract Ref = "UAT-CON-001" |
| | 3. Fill other required fields |
| | 4. Attempt to save |
| Expected Result | Error message displayed |
| Acceptance Criteria | [ ] Error message: "Contract reference already exists" |
| | [ ] Contract not created |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-005: Contract Validation - Invalid Customer

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-005 |
| Scenario | Attempt to create contract with non-existent customer |
| Preconditions | None |
| Test Steps | 1. Create new contract |
| | 2. Select non-existent customer ID |
| | 3. Fill other required fields |
| | 4. Attempt to save |
| Expected Result | Error message displayed |
| Acceptance Criteria | [ ] Error message: "Customer not found" |
| | [ ] Contract not created |
| Result | PASS/FAIL |
| Notes | |

---

### 4.2 Milestone Management (FPC-002)

#### UAT-FPC-006: Create Milestone

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-006 |
| Scenario | Add milestone to active contract |
| Preconditions | Contract UAT-CON-001 is active |
| Test Steps | 1. Navigate to contract UAT-CON-001 |
| | 2. Click "Add Milestone" |
| | 3. Enter: Name = "Design Phase", Payment Amount = 10000 |
| | 4. Set: Percentage = 20, Due Date = today + 1 month |
| | 5. Save |
| Expected Result | Milestone created with status "pending" |
| Acceptance Criteria | [ ] Milestone saved to database |
| | [ ] Milestone appears in milestone list |
| | [ ] Status is pending |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-007: Create Multiple Milestones

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-007 |
| Scenario | Add multiple milestones that sum to contract value |
| Preconditions | Contract UAT-CON-001 is active |
| Test Steps | 1. Add milestone "Development" - 20000 (40%) |
| | 2. Add milestone "Testing" - 10000 (20%) |
| | 3. Add milestone "Deployment" - 10000 (20%) |
| Expected Result | All milestones created, progress shows correctly |
| Acceptance Criteria | [ ] All milestones saved |
| | [ ] Progress calculates to 0% (none completed) |
| | [ ] Total = 50000 (matches contract) |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-008: Complete Milestone

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-008 |
| Scenario | Mark milestone as completed |
| Preconditions | Milestone "Design Phase" exists |
| Test Steps | 1. Navigate to contract milestones |
| | 2. Click "Complete" on "Design Phase" |
| Expected Result | Milestone status changes to "completed" |
| Acceptance Criteria | [ ] Status changed to completed |
| | [ ] completed_at timestamp set |
| | [ ] Progress updated to 20% |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-009: Progress Tracking

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-009 |
| Scenario | Verify progress calculation |
| Preconditions | 1 of 5 milestones completed |
| Test Steps | 1. Navigate to contract details |
| | 2. View progress section |
| Expected Result | Progress shows 20% |
| Acceptance Criteria | [ ] Progress percentage = 20% |
| | [ ] Completed milestones count = 1 |
| | [ ] Pending milestones count = 4 |
| Result | PASS/FAIL |
| Notes | |

---

### 4.3 Change Orders (FPC-003)

#### UAT-FPC-010: Create Change Order

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-010 |
| Scenario | Add change order to contract |
| Preconditions | Contract UAT-CON-001 is active |
| Test Steps | 1. Navigate to contract change orders |
| | 2. Click "New Change Order" |
| | 3. Enter: Ref = "CO-001", Description = "Additional feature" |
| | 4. Enter: Amount = 5000, Reason = "Client requested" |
| | 5. Save |
| Expected Result | Change order created with status "pending" |
| Acceptance Criteria | [ ] Change order saved |
| | [ ] Status is pending |
| | [ ] Contract value unchanged |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-011: Approve Change Order

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-011 |
| Scenario | Approve pending change order |
| Preconditions | Change order CO-001 is pending |
| Test Steps | 1. Navigate to change orders |
| | 2. Click "Approve" on CO-001 |
| Expected Result | Change order approved, contract value updated |
| Acceptance Criteria | [ ] Status changed to approved |
| | [ ] approved_by and approved_at set |
| | [ ] Contract total_value increased by 5000 |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-012: Reject Change Order

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-012 |
| Scenario | Reject pending change order |
| Preconditions | Create and reject a change order |
| Test Steps | 1. Create change order CO-002 with amount 3000 |
| | 2. Click "Reject" on CO-002 |
| | 3. Enter rejection reason |
| Expected Result | Change order rejected |
| Acceptance Criteria | [ ] Status changed to rejected |
| | [ ] Reason recorded |
| | [ ] Contract value unchanged |
| Result | PASS/FAIL |
| Notes | |

---

### 4.4 Billing (FPC-004)

#### UAT-FPC-013: Bill Completed Milestone

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-013 |
| Scenario | Generate invoice from completed milestone |
| Preconditions | Milestone "Design Phase" is completed |
| Test Steps | 1. Navigate to completed milestone |
| | 2. Click "Create Invoice" |
| Expected Result | FA invoice created, milestone status = billed |
| Acceptance Criteria | [ ] Invoice created in FA |
| | [ ] Milestone status = billed |
| | [ ] billing_transaction_id stored |
| | [ ] Billed amount = 10000 |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-014: Billing Report

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-014 |
| Scenario | View billing status report |
| Preconditions | At least one milestone billed |
| Test Steps | 1. Navigate to contract billing report |
| Expected Result | Report shows billed and pending amounts |
| Acceptance Criteria | [ ] billed_amount = 10000 |
| | [ ] pending_amount = 40000 |
| | [ ] Billed milestones listed |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-015: Cannot Bill Non-Completed Milestone

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-015 |
| Scenario | Attempt to bill non-completed milestone |
| Preconditions | Milestone "Development" is pending |
| Test Steps | 1. Attempt to create invoice for pending milestone |
| Expected Result | Error message displayed |
| Acceptance Criteria | [ ] Error: "Milestone must be completed" |
| | [ ] No invoice created |
| Result | PASS/FAIL |
| Notes | |

---

### 4.5 Cost Tracking (FPC-005)

#### UAT-FPC-016: Add Material Cost

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-016 |
| Scenario | Add material cost to contract BOM |
| Preconditions | Contract UAT-CON-001 is active |
| Test Steps | 1. Navigate to contract BOM |
| | 2. Click "Add Material" |
| | 3. Enter: Description = "Steel beams", Qty = 100, Unit Cost = 50 |
| | 4. Save |
| Expected Result | BOM entry created |
| Acceptance Criteria | [ ] BOM entry saved |
| | [ ] total_cost = 5000 |
| | [ ] Status = planned |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-017: Add Labor Cost

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-017 |
| Scenario | Add labor cost to contract |
| Preconditions | Contract UAT-CON-001 is active |
| Test Steps | 1. Navigate to contract costs |
| | 2. Click "Add Labor Cost" |
| | 3. Enter: Amount = 5000, Description = "Developer hours" |
| | 4. Save |
| Expected Result | Labor cost entry created |
| Acceptance Criteria | [ ] Cost entry saved |
| | [ ] Cost appears in breakdown |
| Result | PASS/FAIL |
| Notes | |

#### UAT-FPC-018: Cost Variance Report

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-018 |
| Scenario | View cost variance report |
| Preconditions | Costs added to contract |
| Test Steps | 1. Navigate to contract cost report |
| Expected Result | Report shows actual vs budgeted costs |
| Acceptance Criteria | [ ] budgeted_cost displayed |
| | [ ] actual_cost displayed |
| | [ ] variance calculated |
| Result | PASS/FAIL |
| Notes | |

---

### 4.6 Integration (FPC-006)

#### UAT-FPC-019: Customer from FA CRM

| Field | Value |
|-------|-------|
| Test Case ID | UAT-FPC-019 |
| Scenario | Verify FA customers available in contract |
| Preconditions | Customers exist in FA |
| Test Steps | 1. Create new contract |
| | 2. View customer dropdown |
| Expected Result | FA customers populated in dropdown |
| Acceptance Criteria | [ ] Customer names from debtors_master |
| | [ ] Can select customer |
| Result | PASS/FAIL |
| Notes | |

---

## 5. UAT Execution

### 5.1 Execution Checklist

- [ ] All test cases reviewed
- [ ] Test environment ready
- [ ] Test data loaded (FA with customers, etc.)
- [ ] Test users configured
- [ ] Test cases executed
- [ ] Results documented
- [ ] Defects logged

### 5.2 Sign-off

| Role | Name | Date | Signature |
|------|------|------|----------|
| Project Manager | | | |
| QA Lead | | | |
| Development Lead | | | |

---

## 6. Test Results Summary

### 6.1 Results Summary

| Category | Total | Passed | Failed | Pass Rate |
|----------|-------|--------|--------|----------|
| Contract Management | 5 | | | |
| Milestone Management | 4 | | | |
| Change Orders | 3 | | | |
| Billing | 3 | | | |
| Cost Tracking | 3 | | | |
| Integration | 1 | | | |
| **TOTAL** | **19** | | | |

### 6.2 Defects Found

| Defect ID | Test Case | Severity | Description | Status |
|-----------|----------|----------|-------------|--------|
| | | | | |

---

## 7. UAT Completion

### 7.1 Completion Criteria

- [ ] All critical test cases pass
- [ ] No high-severity defects open
- [ ] All test data cleaned up
- [ ] Sign-off obtained

### 7.2 Final Sign-off

This module is approved for production deployment.

| Role | Name | Date | Signature |
|------|------|------|----------|
| Business Owner | | | |
| Project Manager | | | |
| QA Lead | | | |

---

## 8. Traceability

| Test Case | FR Covered |
|-----------|-----------|
| UAT-FPC-001 | FR-PROJECT-001-001 |
| UAT-FPC-002 | FR-PROJECT-001-001 |
| UAT-FPC-003 | FR-PROJECT-001-001 |
| UAT-FPC-004 | FR-PROJECT-001-001 |
| UAT-FPC-005 | FR-PROJECT-001-001 |
| UAT-FPC-006 | FR-PROJECT-001-002 |
| UAT-FPC-007 | FR-PROJECT-001-002 |
| UAT-FPC-008 | FR-PROJECT-001-002 |
| UAT-FPC-009 | FR-PROJECT-001-003 |
| UAT-FPC-010 | FR-PROJECT-001-004 |
| UAT-FPC-011 | FR-PROJECT-001-004 |
| UAT-FPC-012 | FR-PROJECT-001-004 |
| UAT-FPC-013 | FR-PROJECT-001-005 |
| UAT-FPC-014 | FR-PROJECT-001-005 |
| UAT-FPC-015 | FR-PROJECT-001-005 |
| UAT-FPC-016 | FR-PROJECT-001-006 |
| UAT-FPC-017 | FR-PROJECT-001-006 |
| UAT-FPC-018 | FR-PROJECT-001-006 |
| UAT-FPC-019 | Integration |
