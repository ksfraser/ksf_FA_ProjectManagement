# BR-PROJECT-002 - Project Templates & Activity Codes

## Business Requirement

**Module**: ksf_FA_ProjectManagement
**Status**: Proposed
**Integration**: Hook-based (hook_invoke_all)

### Problem Statement

FA lacks a project template system that pre-seeds new projects with common activities based on industry type. This leads to:
- Repetitive project setup for similar projects
- Inconsistent activity coding across projects
- No standard activity library by industry vertical

### Industry Activity Libraries

#### Software Development
```
Stage: Requirements
  - Business Requirements Gathering
  - Stakeholder Interviews
  - Requirements Documentation
  - Use Case Development
  - Acceptance Criteria Definition

Stage: Design
  - System Architecture Design
  - Database Design
  - UI/UX Design
  - API Design
  - Security Design

Stage: Development
  - Backend Coding
  - Frontend Coding
  - Database Scripts
  - Unit Testing
  - Code Review

Stage: Testing
  - Integration Testing
  - System Testing
  - Performance Testing
  - Security Testing
  - User Acceptance Testing

Stage: Deployment
  - Environment Setup
  - Migration
  - Deployment
  - Training
  - Documentation

Stage: Support
  - Bug Fixes
  - Hotfixes
  - Maintenance
  - Enhancements
  - Monitoring
```

#### House Construction
```
Stage: Pre-Construction
  - Site Survey
  - Permits & Approvals
  - Architect Plans
  - Contractor Selection
  - Budget Finalization

Stage: Foundation
  - Site Clearing
  - Excavation
  - Footings
  - Foundation Pour
  - Foundation Cure

Stage: Structure
  - Framing
  - Roofing
  - Exterior Walls
  - Windows & Doors
  - Rough Inspections

Stage: Finishing
  - Electrical
  - Plumbing
  - HVAC
  - Insulation
  - Drywall
  - Painting
  - Flooring

Stage: Closeout
  - Final Inspections
  - Certificate of Occupancy
  - Punch List
  - Owner Training
  - Warranty Documentation
```

#### Sales
```
Stage: Lead Generation
  - Cold Calling
  - Networking Events
  - Trade Shows
  - Marketing Campaigns
  - Referral Follow-up

Stage: Qualification
  - Initial Meeting
  - Needs Assessment
  - Budget Discussion
  - Timeline Confirmation
  - Decision Maker ID

Stage: Proposal
  - Solution Design
  - Pricing Configuration
  - Proposal Writing
  - Presentation
  - Negotiation

Stage: Closing
  - Contract Review
  - Legal Approval
  - Contract Signing
  - Deposit Collection
  - Kickoff Scheduling

Stage: Post-Sale Support
  - Onboarding
  - Training Delivery
  - Support Handoff
  - Relationship Management
  - Renewal Pursuit
```

#### Generic Business
```
Stage: Administrative
  - Email & Communication
  - Document Management
  - Meeting Preparation
  - Travel Planning
  - Training & Development

Stage: Planning
  - Strategy Sessions
  - Goal Setting
  - Budget Planning
  - Resource Allocation
  - Risk Assessment

Stage: Execution
  - Task Completion
  - Collaboration
  - Problem Solving
  - Quality Assurance
  - Reporting

Stage: Review
  - Performance Review
  - Lessons Learned
  - Process Improvement
  - Stakeholder Feedback
  - Archive & Close
```

### Scope

#### In Scope
1. Template library with industry verticals
2. Project → Stages → Activities hierarchy
3. Stage constraints (active flag, date range, both)
4. Activity code inheritance and override
5. Template versioning
6. Hook emissions for integration

#### Out of Scope
1. Resource scheduling (use MRP)
2. Gantt chart generation
3. Budget tracking (separate BR)

### Hook Integration Points

```php
// Project created from template
hook_invoke_all('project_template_applied', [
    'project_id' => $projectId,
    'template_id' => $templateId,
    'stages' => $appliedStages,
    'activities' => $appliedActivities,
]);

// Activity status changed
hook_invoke_all('project_activity_status_changed', [
    'project_id' => $projectId,
    'activity_id' => $activityId,
    'old_status' => $oldStatus,
    'new_status' => $newStatus,
]);

// Stage constraints validated
hook_invoke_all('project_stage_access_check', [
    'project_id' => $projectId,
    'stage_id' => $stageId,
    'user_id' => $userId,
    'allowed' => &$allowed,
]);
```

### Dependencies

- ksf_FA_Timesheets (time → activity mapping)
- ksf_FA_TravelExpense (expense → activity mapping)
- ksf_FA_RBAC (activity permissions)
- ksf_FA_Teams (org chart for approvals)
