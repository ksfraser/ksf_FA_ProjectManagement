# RTM-PROJECT-001 — Fixed Price Contracts Traceability Matrix

**Module:** ksf_FA_ProjectManagement
**Status:** Proposed

## 1. Overview

This document maps Business Requirements, Functional Requirements, Unit Tests, and UAT Test Cases for the Fixed Price Contracts feature to ensure complete traceability throughout the development lifecycle.

---

## 2. Requirement Traceability

### 2.1 Business Requirement to Functional Requirements

| BR ID | BR Name | FR IDs |
|-------|---------|--------|
| BR-PROJECT-001 | Fixed Price Contracts | FR-PROJECT-001-001, FR-PROJECT-001-002, FR-PROJECT-001-003, FR-PROJECT-001-004, FR-PROJECT-001-005, FR-PROJECT-001-006 |

### 2.2 Functional Requirement to Unit Tests

| FR ID | FR Name | UT IDs |
|-------|---------|--------|
| FR-PROJECT-001-001 | Contract creation | UT-PROJECT-001-001-001 |
| FR-PROJECT-001-002 | Milestone management | (to be defined) |
| FR-PROJECT-001-003 | Progress tracking | (to be defined) |
| FR-PROJECT-001-004 | Change orders | (to be defined) |
| FR-PROJECT-001-005 | Contract billing | (to be defined) |
| FR-PROJECT-001-006 | Cost tracking | (to be defined) |

### 2.3 Functional Requirement to UAT Test Cases

| FR ID | FR Name | UAT IDs |
|-------|---------|---------|
| FR-PROJECT-001-001 | Contract creation | UAT-FPC-001, UAT-FPC-002, UAT-FPC-003, UAT-FPC-004, UAT-FPC-005 |
| FR-PROJECT-001-002 | Milestone management | UAT-FPC-006, UAT-FPC-007, UAT-FPC-008 |
| FR-PROJECT-001-003 | Progress tracking | UAT-FPC-009 |
| FR-PROJECT-001-004 | Change orders | UAT-FPC-010, UAT-FPC-011, UAT-FPC-012 |
| FR-PROJECT-001-005 | Contract billing | UAT-FPC-013, UAT-FPC-014, UAT-FPC-015 |
| FR-PROJECT-001-006 | Cost tracking | UAT-FPC-016, UAT-FPC-017, UAT-FPC-018 |
| N/A | Integration | UAT-FPC-019 |

---

## 3. Detailed Traceability Matrix

### 3.1 Contract Creation (FR-PROJECT-001-001)

| Requirement | Acceptance Criteria | UT ID | UAT ID | Status |
|------------|-------------------|-------|--------|--------|
| FR-001-001: Create Contract | Contract can be created with all required fields | UT-001-001-01 | UAT-FPC-001 | Proposed |
| FR-001-001 | Unique contract_ref is enforced | UT-001-001-08 | UAT-FPC-004 | Proposed |
| FR-001-001 | Customer validation against debtors_master | UT-001-001-09 | UAT-FPC-005 | Proposed |
| FR-001-001 | Dates validated (end_date >= start_date) | UT-001-001-07 | UAT-FPC-001 | Proposed |
| FR-001-001 | total_value > 0 validation | UT-001-001-05, UT-001-001-06 | UAT-FPC-001 | Proposed |
| FR-001-001 | Status defaults to 'draft' | UT-001-001-12 | UAT-FPC-001 | Proposed |
| FR-001-001 | created_by set from session user | N/A | UAT-FPC-001 | Proposed |
| FR-001-001 | created_at set to current datetime | N/A | UAT-FPC-001 | Proposed |
| FR-001-001 | Error messages returned on validation failure | UT-001-001-11 | UAT-FPC-004, UAT-FPC-005 | Proposed |

### 3.2 Milestone Management (FR-PROJECT-001-002)

| Requirement | Acceptance Criteria | UT ID | UAT ID | Status |
|------------|-------------------|-------|--------|--------|
| FR-001-002 | Milestone can be created for active contract | N/A | UAT-FPC-006 | Proposed |
| FR-001-002 | Payment amounts validated against contract total | N/A | UAT-FPC-007 | Proposed |
| FR-001-002 | Percentage validated (0-100 range) | N/A | UAT-FPC-006 | Proposed |
| FR-001-002 | sort_order controls display sequence | N/A | UAT-FPC-006 | Proposed |
| FR-001-002 | Milestone can be marked as completed | N/A | UAT-FPC-008 | Proposed |
| FR-001-002 | completed_at is set when milestone is completed | N/A | UAT-FPC-008 | Proposed |
| FR-001-002 | Completed milestone triggers hook | N/A | UAT-FPC-008 | Proposed |
| FR-001-002 | Billed milestones cannot be modified | N/A | UAT-FPC-013 | Proposed |
| FR-001-002 | List milestones by contract | N/A | UAT-FPC-002 | Proposed |

### 3.3 Progress Tracking (FR-PROJECT-001-003)

| Requirement | Acceptance Criteria | UT ID | UAT ID | Status |
|------------|-------------------|-------|--------|--------|
| FR-001-003 | Overall contract progress calculated correctly | N/A | UAT-FPC-009 | Proposed |
| FR-001-003 | Milestone status affects progress weighting | N/A | UAT-FPC-009 | Proposed |
| FR-001-003 | Progress displayed with 2 decimal precision | N/A | UAT-FPC-009 | Proposed |
| FR-001-003 | Overdue milestones identified and reported | N/A | UAT-FPC-009 | Proposed |
| FR-001-003 | Progress report includes value breakdown | N/A | UAT-FPC-009 | Proposed |
| FR-001-003 | Progress can be filtered by status | N/A | N/A | Proposed |
| FR-001-003 | Visual indicators match progress ranges | N/A | UAT-FPC-009 | Proposed |

### 3.4 Change Orders (FR-PROJECT-001-004)

| Requirement | Acceptance Criteria | UT ID | UAT ID | Status |
|------------|-------------------|-------|--------|--------|
| FR-001-004 | Change order can be created for active contract | N/A | UAT-FPC-010 | Proposed |
| FR-001-004 | Unique change_order_ref enforced | N/A | UAT-FPC-010 | Proposed |
| FR-001-004 | Positive and negative amounts supported | N/A | UAT-FPC-010 | Proposed |
| FR-001-004 | Only pending change orders can be approved/rejected | N/A | UAT-FPC-011, UAT-FPC-012 | Proposed |
| FR-001-004 | Approval updates contract total_value | N/A | UAT-FPC-011 | Proposed |
| FR-001-004 | approved_by and approved_at set on approval | N/A | UAT-FPC-011 | Proposed |
| FR-001-004 | Rejection reason is recorded | N/A | UAT-FPC-012 | Proposed |
| FR-001-004 | Hook fired on approval | N/A | N/A | Proposed |

### 3.5 Contract Billing (FR-PROJECT-001-005)

| Requirement | Acceptance Criteria | UT ID | UAT ID | Status |
|------------|-------------------|-------|--------|--------|
| FR-001-005 | Invoice created from completed milestone | N/A | UAT-FPC-013 | Proposed |
| FR-001-005 | Milestone status updated to 'billed' | N/A | UAT-FPC-013 | Proposed |
| FR-001-005 | billing_transaction_id stored | N/A | UAT-FPC-013 | Proposed |
| FR-001-005 | Billed milestones cannot be re-billed | N/A | UAT-FPC-015 | Proposed |
| FR-001-005 | Billed amount sum matches invoice totals | N/A | UAT-FPC-014 | Proposed |
| FR-001-005 | FA invoice integration works correctly | N/A | UAT-FPC-013 | Proposed |
| FR-001-005 | Customer from contract used for invoice | N/A | UAT-FPC-013 | Proposed |
| FR-001-005 | Billing report accurate | N/A | UAT-FPC-014 | Proposed |

### 3.6 Cost Tracking (FR-PROJECT-001-006)

| Requirement | Acceptance Criteria | UT ID | UAT ID | Status |
|------------|-------------------|-------|--------|--------|
| FR-001-006 | Material costs can be added to contract | N/A | UAT-FPC-016 | Proposed |
| FR-001-006 | Labor costs can be added to contract | N/A | UAT-FPC-017 | Proposed |
| FR-001-006 | Overhead costs can be added to contract | N/A | UAT-FPC-017 | Proposed |
| FR-001-006 | Service costs can be added to contract | N/A | UAT-FPC-017 | Proposed |
| FR-001-006 | Actual costs can be updated | N/A | UAT-FPC-016 | Proposed |
| FR-001-006 | BOM status transitions work correctly | N/A | UAT-FPC-016 | Proposed |
| FR-001-006 | Actual cost calculated correctly | N/A | UAT-FPC-018 | Proposed |
| FR-001-006 | Budgeted cost calculated correctly | N/A | UAT-FPC-018 | Proposed |
| FR-001-006 | Variance calculated correctly | N/A | UAT-FPC-018 | Proposed |
| FR-001-006 | Cost breakdown by category accurate | N/A | UAT-FPC-018 | Proposed |
| FR-001-006 | Profit margin calculated correctly | N/A | UAT-FPC-018 | Proposed |

---

## 4. Test Coverage Summary

| FR ID | FR Name | Total Criteria | UT Coverage | UAT Coverage | Combined Coverage |
|-------|---------|----------------|-------------|--------------|------------------|
| FR-PROJECT-001-001 | Contract creation | 9 | 100% | 56% | 78% |
| FR-PROJECT-001-002 | Milestone management | 9 | 0% | 78% | 39% |
| FR-PROJECT-001-003 | Progress tracking | 7 | 0% | 100% | 50% |
| FR-PROJECT-001-004 | Change orders | 8 | 0% | 88% | 44% |
| FR-PROJECT-001-005 | Contract billing | 8 | 0% | 75% | 38% |
| FR-PROJECT-001-006 | Cost tracking | 11 | 0% | 100% | 50% |

---

## 5. Document Dependencies

| Document | Document ID | Version |
|----------|-------------|---------|
| Business Requirement | BR-PROJECT-001 | 1.0 |
| Architecture | ARCH-PROJECT-001 | 1.0 |
| Functional Requirement - Contract Creation | FR-PROJECT-001-001 | 1.0 |
| Functional Requirement - Milestone Management | FR-PROJECT-001-002 | 1.0 |
| Functional Requirement - Progress Tracking | FR-PROJECT-001-003 | 1.0 |
| Functional Requirement - Change Orders | FR-PROJECT-001-004 | 1.0 |
| Functional Requirement - Contract Billing | FR-PROJECT-001-005 | 1.0 |
| Functional Requirement - Cost Tracking | FR-PROJECT-001-006 | 1.0 |
| Unit Test - Contract Creation | UT-PROJECT-001-001-001 | 1.0 |
| UAT Plan | UAT-PROJECT-001 | 1.0 |

---

## 6. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-09-07 | KSF Development | Initial RTM for Fixed Price Contracts |

---

## 7. Approval

| Role | Name | Date | Signature |
|------|------|------|----------|
| Business Analyst | | | |
| Development Lead | | | |
| QA Lead | | | |
| Project Manager | | | |
