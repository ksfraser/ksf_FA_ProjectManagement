# FR-PROJECT-004-001 — Project Files & Attachments

**Parent:** BR-PROJECT-004
**Status:** Proposed
**BABOK Direction:** Requirements Analysis → Define Requirements & Design → FR
**Traceability:** ARCH-PROJECT-004; UT-PROJECT-004-001-*

## Functional Requirements

### FR-PROJECT-004-001-001 — Entity-scoped file registry

Files are tracked in `0_fa_pm_files`, scoped by `entity_type` (project | task |
milestone) + `entity_id`, with `description`, `filetype`, `filesize`,
`original_name`, `unique_name`, `uploaded_by`, `uploaded_at`, `inactive`.

### FR-PROJECT-004-001-002 — Reuse FA's native attachment machinery

**HARD RULE (user directive):** Do NOT build a parallel file-attachment
stack. Reuse FA's own `admin/db/attachments_db.inc` API (`add_attachment`,
`get_attachment`, `has_attachment`, `move_trans_attachments`) — but with a
**different save location under `company/<company_id>/`** (a PM-specific
subdirectory), matching FA's convention that attachment payloads live on the
company filesystem, not in the DB. Storage column `storage_type` on
`0_fa_pm_files` records `local` (native FA `attachments_db.inc` path under the
company dir).

### FR-PROJECT-004-001-003 — Transfer management

When a project/task is renumbered or the company prefix changes, PM uses
`move_trans_attachments($type, $from_no, $to_no)` and updates the referencing
`0_fa_pm_files` rows' `entity_id` in the same transaction. UI list sorts by
`uploaded_at DESC`.

### FR-PROJECT-004-001-004 — Activity + feed wiring

Uploads fire `activity_log` rows (`FILE_UPLOAD`) and emit the `pm_file_uploaded`
workflow hook via `hook_invoke_all` so listeners (e.g. calendar event with
document link) can react.

## Acceptance Criteria

1. `FileRepository::save()` persists one row in `0_fa_pm_files` and delegates
   the blob to the FA native attachment storage under `company/<id>/`.
2. `FileRepository::moveEntityIds()` re-points rows after a transfer.
3. No new table for blob payloads; only metadata in `0_fa_pm_files`.
