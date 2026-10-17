# FR-PM-006-001 — CpmEngine: precedence-graph schedule computation

| | |
|---|---|
| ID | FR-PM-006-001 |
| Related | BR-PM-006, BR-003; UT-PM-006-* |
| Status | Approved (package) |

### Requirement
Provide a pure `CpmEngine::run(array $tasks, array $dependencies): array`
computing a full CPM schedule for precedence graphs with edge types
FS/SS/FF/SF, integer lag days, zero-duration milestones, and hard
start/finish no-earlier/later-than anchor constraints.

### Acceptance (traceable to UT)
- **UT-PM-006-001-001** forward pass ES/EF for FS(+lag) chain.
- **UT-PM-006-001-002** SS edge honors successor ES via predecessor ES+lag.
- **UT-PM-006-001-003** FF edge via predecessor EF+lag.
- **UT-PM-006-001-004** SF edge via predecessor EF+lag−duration.
- **UT-PM-006-001-005** milestone EF=ES (zero duration).
- **UT-PM-006-001-006** cycle → `ok=false` + cycle path (no throw).
- **UT-PM-006-001-007** backward pass LF/LS; slack=LS−ES.
- **UT-PM-006-001-008** criticality within tolerance (abs(slack)<=0.5).
