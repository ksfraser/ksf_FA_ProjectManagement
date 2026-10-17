# UT-PROJECT-004-001-001 — PmFileService + FaAttachmentFacade

**Under test:** `PmFileService`, `FileRepository`, `FaAttachmentFacade`
**BABOK:** Requirements Analysis → Validate
**Traceability:** FR-PROJECT-004-001-*; ARCH-PROJECT-004

## Test Cases

- **UT-PROJECT-004-001-001-001 — facade add calls FA + records row**
  Given a stub `AttachmentStoreInterface` recording calls, `save()` must
  invoke the store's `add` exactly once, and the registry row written with
  the returned `id`, `storage_type='local'`,
  `storage_path ≈ company/<id>/ksf_pm_attachments/<unique_name>`.
  No FA function is executed in-test.

- **UT-PROJECT-004-001-001-002 — facade delete cascade**
  `delete()` calls store `delete` and removes the `0_fa_pm_files` row. Stub
  returns `false` → service returns false, activity log shows failed delete.

- **UT-PROJECT-004-001-001-003 — workflow hooks fire on save**
  `document_before_save` / `document_after_save` invoked (hook_invoke_all
  stub records), activity log entry `DOCUMENT_UPLOAD` present.

- **UT-PROJECT-004-001-001-004 — entity scoping**
  `listForEntity(entity_type=task, entity_id)` returns only rows for that
  task; cross-entity rows excluded.

- **UT-PROJECT-004-001-001-005 — transfer re-points entity_id only**
  `moveTransAttachments(type, from, to)` updates `entity_id` in registry;
  store `move` NOT called (blob stays; "different saving location" rule).

- **UT-PROJECT-004-001-001-006 — unknown storage_type rejected**
  `storage_type='ftp'` → validation error, nothing written.
