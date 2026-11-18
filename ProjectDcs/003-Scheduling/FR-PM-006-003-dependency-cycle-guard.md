# FR-PM-006-003 — Dependency guard: cycle + self-ref rejection

| | |
|---|---|
| ID | FR-PM-006-003 |
| Related | BR-PM-006; UT-PM-006-005-*, UT-PM-006-006-003..004 |
| Status | Approved (package) |
| Amended | 2026-10-17 — the `Blocked` cycle-guard item is now implemented; acceptance ids renumbered per `ID-SCHEME-DECISION.md` §3. |

### Requirement
`TaskDependencyService::add()` must reject (a) predecessor==successor (self
dependency) and (b) any edge whose addition would close a precedence cycle,
before persisting. `TaskDependencyRepository` must expose the row scan the
guard reads (`all()`) and `deleteAllFor()` for re-sync.

This is the entry point named by this requirement, and the code now matches:
the method is `add()`. The name `createDependency` that the code previously
shipped was renamed to `add()` (2026-10-17) so that the documented API is the
real one.

### How the guard works

`add()` loads the persisted rows **once** and shares them between the duplicate
check and the cycle guard, then builds an `Engine\TaskDependencyGraph` from
them plus the candidate edge and asks `CpmEngine::detectCycle()`.

- `Engine\TaskDependencyGraph` holds edges and hands out adjacency, nothing
  else. It carries no durations and no relation types: a cycle is a purely
  topological property, so FS/SS/FF/SF and lag are irrelevant to it.
- `CpmEngine::detectCycle()` returns the first real cycle as a **closed**
  traversal (`A -> B -> A` comes back as `['A','B','A']`), and `[]` when the
  graph is acyclic. It runs the *same* tri-colour depth-first search
  `CpmEngine::run()` uses to build its topological order, so the scheduler's
  notion of a cycle and the write guard's cannot drift apart.
- The refusal message carries that closed path, so the caller can show the user
  where the loop closes rather than only that one exists.

### Validation errors vs precedence cycles

These are deliberately two different answers, not one:

- A **self dependency** is refused by `validate()` with
  `A task cannot depend on itself.` and is never a "cycle". dotProject and
  OpenProject both reject it in the validation layer before the scheduler sees
  the edge. `CpmEngine::run()` likewise returns `ok=false` with an **empty**
  `cycle` array for a self edge.
- A **cycle** involves two or more tasks in a loop and is reported with the
  path.

A caller must be able to tell "you typed nonsense" from "your plan has a loop".

### Acceptance

- **UT-PM-006-005-001** self-dependency rejected by validation (no write).
- **UT-PM-006-005-002** back-edge closing a cycle rejected (no write).
- **UT-PM-006-005-003** acyclic edge in an acyclic graph persists + returns id.
- **UT-PM-006-005-004** `deleteAllFor` removes all deps of a task.
- **UT-PM-006-005-005** `CpmEngine::detectCycle()` + `Engine\TaskDependencyGraph` exist and are reachable.
- **UT-PM-006-005-006** `TaskDependencyRepository::all()` exists (the row scan the guard reads).
- **UT-PM-006-005-007** `TaskDependencyService` is loadable at all.
- **UT-PM-006-005-008** the cycle refusal reports the closed path back to its start.
- **UT-PM-006-006-003** `detectCycle()` returns a genuine closed cycle path naming only the loop's own nodes.
- **UT-PM-006-006-004** `detectCycle()` accepts an acyclic graph.