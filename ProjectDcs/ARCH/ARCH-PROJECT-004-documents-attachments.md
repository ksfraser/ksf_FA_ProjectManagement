# ARCH-PROJECT-004 — Documents & Attachments (FA-native reuse)

**Parent:** BR-PROJECT-004
**Status:** Proposed
**Traceability:** FR-PROJECT-004-001-*; UT-PROJECT-004-001-*

## Design Goal

**Reuse FA's native attachment machinery, do not build a parallel stack.**
PM documents are FA attachments with a different save location under
`company/<id>/` (user directive, 2026-09). Storage metadata lives in PM's
`0_fa_pm_files` dictionary so PM services/tests see one vocabulary; the blob
bytes and low-level CRUD are FA's.

## Layering

```
PmFileService            (workflow hooks, activity log, life cycle)
   │
   ├─ FileRepository     (0_fa_pm_files — dictionary CRUD, DI'd FaDbAdapter)
   │
   └─ FaAttachmentFacade (thin wrapper over admin/db/attachments_db.inc)
         ├─ add_attachment / update_attachment / delete_attachment
         ├─ has_attachment / get_attached_documents / get_sql_for_attached_documents
         └─ move_trans_attachments
```

`FaAttachmentFacade` is an adapter (implements `AttachmentStoreInterface`)
so unit tests can substitute a stub that records calls — no FA function is
invoked in the test process.

## Save location (the "different saving location" decision)

- Blob dir: `company/<id>/ksf_pm_attachments/` (subdirectory of the company
  data area), NOT the default `company/<id>/attachments/`. Rationale:
  keeps PM files out of FA's global attachment namespace while inheriting all
  FA file-serving/security/path conventions.
- Metadata table `0_fa_pm_files` retains `storage_type` (default `local`),
  `storage_path`, `size`, `mime_type`, `unique_name` — mirror of the FA
  `0_attachments` columns for PM-scoped queries/reports.

## Entity scoping

- `entity_type` ∈ {project, task, milestone}; `entity_id` = project_id /
  task_id. One row per uploaded file; multiple files per entity supported.
- When a task transfers projects (`move_trans_attachments` + repository
  `entity_id` update), blob location is unchanged; only the registry row is
  re-pointed. This is the "different saving location" boundary — the file
  itself never moves within the PM attachment dir.

## Lifecycle

On save: workflow hooks `document_before_save`/`document_after_save`
(ksf_FA_Common WorkflowHooksTrait) + activity log `DOCUMENT_UPLOAD`;
on delete: `document_before_delete`/`document_after_delete` +
`DOCUMENT_REMOVED`.

## Security

Reads/writes restricted by SA_ksf_FA_ProjectManagementVIEW + PM role maps;
no new security area. File streaming goes through FA's attachment view
(inherited).
