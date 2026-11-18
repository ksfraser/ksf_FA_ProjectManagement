# Business Requirements - Project Management Access Control (BR-003-PM-Access-RBAC.md)

## Overview
Implement Role-Based Access Control (RBAC) for the Project Management module to enforce access control based on user roles, teams, and project assignments.

## Scope
- Define access levels for PM modules (Admin, Team Members, Project Owners)
- Establish permission matrix linking roles to departments and projects
- Configure default access policies (READ ONLY, TEAM, FULL)

## Requirements

### 1. Role Hierarchy
- **Administrator** - Full access to all PM records
- **Team Member** - Access limited to own team/project
- **Project Owner** - Access to own projects and team
- **Regular User** - Read-only access to PM records

### 2. Access Levels
- **FULL** - Complete access to all PM entities (projects, tasks, milestones)
- **TEAM** - Access restricted to own team/project
- **READ_ONLY** - Read-only access to PM records

### 3. Default Behavior
- If RBAC is not installed → Default to READ_ONLY
- PM Administrators get FULL access regardless of RBAC installation
- All other users → READ_ONLY or TEAM based on assignment

### 4. Integration Points
- PM module: Apply RBAC when accessing project/task records
- HRM module: Cross-module access validation
- CRM module: Share permissions with HRM for employee data

## Implementation Notes
- Leverage existing PM entity hierarchy (Project → Task → Milestone)
- Use RBAC grid helper for consistent permission mapping
- Cache RBAC queries to improve performance
- Document permission matrix in RbacGridHelpers.php

## Acceptance Criteria
- [ ] PM Admins can access all records
- [ ] Team members can only access their assigned team/project
- [ ] Regular users have READ_ONLY access
- [ ] RBAC queries are cached for performance
- [ ] Default fallback to READ_ONLY when RBAC not configured
