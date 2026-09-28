# ksf_FA_ProjectManagement — AGENTS.local.md

> Scoped 2026: this file holds ONLY what this repo decides. Cross-module
> conventions live in the shared `/home/kevin/Documents/AGENTS.md` +
> `AGENTS_ARCH.md`. PM stands the _source copy_ of every repo it touches; it
> never invents a parallel abstraction.

## Identity (PROVEN, do not re-derive)
- FA module dir: `ksf_FA_ProjectManagement`; SQL tables: `0_ksf_pm_*`
  (literal `0_` prefix — FA's db_import only rewrites `0_` -> TB_PREF; never
  `{TB_PREF}` in FA-install sql/ files).
- _init/config: gzip-compressed, `Key: Value`, numeric version `2.4.19-0`.
- hooks class: `hooks_ksf_FA_ProjectManagement`. Security sections:
  `SS_ksf_FA_ProjectManagement_*`.
- Required DW dep (Packagist, NOT git-submodule, NOT a fork): package
  `ksfraser/ksf-fa-projectmanagement`.  Every CSM publication out)
- **GL posting bridge — ONE trait, two consumers** (payroll + expense): both
  `ksf_FA_EmployeePay` and `ksf_FA_TravelExpense` stop at capture. Both need
  the same "approved source -> balanced FA GL entry (date/dim/memo)" path.
  Implement ONCE as `PostingBridgeTrait` in `ksf_FA_Common`, consumed by both;
  testable on the PDO side with double-entry-must-balance. Never a per-module
  bridge. GL map (cost to GL account) is data, not code: `0_ksf_pm_gl_map`
  per expense/payroll category.
- FRs: `FR-PM-032` (posting bridge contract), `FR-PM-033` (double-entry
  balance invariant).

## PM vs dotProject (the original: 3 carry-over links; PM is otherwise ahead)
dotProject (unmaintained 2.1.x): CS/FS-only deps, task tree, Gantt,
per-task notes, per-task time logs.
- ksf PM already BEATS it: full CPM (FS/SS/SF/FF + lag + constraints + cycle
  detect), real Gantt, project tree, slack, milestones.
- ksf lacks (all cheap links on EXISTING modules — no new scope):
  (a) `ksf_FA_Notes` wired to `task_id` (per-task notes),
  (b) `ksf_FA_Timesheets` linked to PM task (task time-logs),
  (c) aggregate resource-load grid (pure SELECT rollup; NO leveling engine).
- FRs: `FR-PM-034` (note-task link), `FR-PM-035` (timesheet-task link),
  `FR-PM-036` (resource-load grid).

## VS webERP — one costing gap
webERP has component scrap-yield on BoM explosion; ksf_FA_MRP doesn't cost
scrap on sub-assembly lines. FR-PM-037: add scrap_qty margin into the BoM
explosion costing pass. (Pricing matrix + kits already covered by
DynamicPricing/PriceBook.)
