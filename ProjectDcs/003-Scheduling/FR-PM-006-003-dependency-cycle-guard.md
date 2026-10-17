# FR-PM-006-003 — Dependency guard: cycle + self-ref rejection

| | |
|---|---|
| ID | FR-PM-006-003 |
| Related | BR-PM-006; UT-PM-006-003-* |
| Status | Approved (package) |

### Requirement
`TaskDependencyService::add` must reject (a) predecessor==successor (self
dependency) and (b) any edge whose addition would close a precedence cycle,
before persisting; `TaskDependencyRepository` must expose such guards and
`deleteAllFor` for re-sync.

### Acceptance
- UT-PM-006-003-001 self-dependency rejected (no write).
- UT-PM-006-003-002 back-edge closing a cycle rejected (no write).
- UT-PM-006-003-003 acyclic edge persists + returns id.
- UT-PM-006-003-004 deleteAllFor removes all deps of a project.
