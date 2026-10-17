# BR-PM-006 — Standalone CPM/Scheduling Business-Logic Package

| Attribute      | Value |
|----------------|-------|
| ID             | BR-PM-006 |
| Module         | PM (Project Management) |
| Doc Group      | ProjectDcs / 003-Scheduling |
| Status         | Approved — code + package extraction |
| Related        | BR-003 (Scheduling/CPM engine), ATM-006 |

## 1. Problem
The module's core scheduling logic (TDD CpmEngine + SchedulingService) is
currently embedded in the FrontAccounting module tree. The pure computation
(Critical Path Method forward/backward passes, slack, criticality, precedence
graph, dependency validation) has **no FA dependency** and is not FA-specific.
Keeping it inside the module forces every other consumer — other FAPM modules,
CLI tooling, other ERP/Framework hosts, standalone test harnesses — to
re-implement or vendor the same algorithm.

A survey of the PHP/Packagist ecosystem (BR-PM-006-ADD-1 research register)
found **no reusable package** meeting all of: PHP 7.3 floor, pure
transport-agnostic CPM business logic, and FA-hook-compatible DI. Existing
candidates are either stale minimal helpers (rencie/cpm), framework-bound
rendering libs (laravel-gantt, filament-gantt-lite — Laravel/Filament, PHP 8.x),
or wrong-version RCPSP engines (PHP 8.3).

## 2. Business Need
Provide a **shared, standalone PHP package** exposing the critical-path
scheduler and project-scheduling business rules as pure, framework-neutral,
PHP 7.3-compatible code, so that:

1. The FA module consumes it via Composer + DI (same playbook as
   `ksfraser/ksf-common-db`), instead of owning the logic inline.
2. Non-FA consumers (CLI, tests, other transport hosts) get the identical
   engine without pulling FrontAccounting.
3. Future CPM features (resource leveling, calendars, baselines) land in one
   place.

## 3. Scope
### In scope
- `CpmEngine` — pure CPM: FS/SS/FF/SF edge types, lag, milestone zero-duration,
  no-earlier/no-later constraint anchors, deterministic topological order,
  forward ES/EF + backward LS/LF pass, slack, tolerance-based criticality,
  project duration, critical-path return, cycle detection (ok=false + path).
- `TaskNode`/`DependencyEdge` value objects + a pure `SchedulingEngine` facade
  matching the current SchedulingService contract.
- Composer package with PSR-4, targeted PHP 7.3↔8.3, `suggest` for hooks, and
  zero hard dependencies.

### Out of scope
- FA transport: FA repositories, workflow hooks, db_* calls, SQL, install.sql.
  These remain in the module (which depends on the package).
- Gantt *rendering* (chart display lives in the module's cluster 005 layer).
- Resource-constrained scheduling (network/calendar leveling) — future FR.

## 4. Business Value
- **Reuse**: one algorithm across FA modules, CLI, tests, other hosts.
- **Testability**: engine unit-tested in isolation (TDD), 100% target.
- **Maintainability**: single source of truth for CPM math; versioned via
  composer.
- **Interop**: transport-injectable → satisfies "right classes can be DI'd"
  requirement (ASP-5).

## 5. Acceptance Criteria
- [ ] Package installs via `composer require ksfraser/ksf-common-pm` with zero
      FA/Framework dependencies.
- [ ] `CpmEngine::run()` returns the documented
      `{ok, tasks, project_duration, critical, cycle?}` shape for FS/SS/FF/SF
      with lag and milestone/constraint anchors (matches UT corpus).
- [ ] A dependency cycle returns `ok=false` + the cycle path; never throws.
- [ ] FA module declares `ksfraser/ksf-common-pm` and DI-injects the engine
      (transport stays in the module).
- [ ] PHPUnit suite for the package is green; RTM auto-generated.

## 6. Risks & Mitigations
| Risk | Mitigation |
|------|------------|
| PHP 8-only ecosystem trend | pin floor 7.3; CI matrix 7.3..8.3 |
| Duplicate CPM implementations | archive module-inline engine; module consumes package |
| API drift | contractual return shape + full UT corpus frozen |

---
*Tags: BR, CPM, scheduling, package, reusable, ksf-common-pm*
