# BR-PROJECT-004 — Documents & Attachments

**Module:** ksf_FA_ProjectManagement
**Status:** Proposed
**BABOK Direction:** Strategy Analysis → Future State
**Traceability:** FR-PROJECT-004-001; ARCH-PROJECT-004; UT-PROJECT-004-*

## Business Requirement

PM entities (projects, tasks, milestones) must carry documents and attachments,
stored through a transport-agnostic storage abstraction that supports BOTH the
native FA attachment system and the KSF CRM documents feed:

1. **Entity-linked documents** — files attached to a project, task or milestone
   (contracts, SOWs, change orders, scopecreep evidence, supplier quotes).
2. **Native FA attachments** — a storage backend that writes through FA's own
   `0_attachments` registry (`db_attach()`/`attachment_url()`) so files appear in
   the FA attachment list and respect FA's upload/delete conventions.
3. **CRM documents** — a storage backend that emits the cross-module document
   hook (`hook_invoke_first('document_*')`) so PM documents appear in the CRM
   documents feed without a hard dependency on ksf_FA_CRM.
4. **Portable local storage** — a pluggable local/disk backend so the PM DAO
   layer stays testable and usable outside FA (CLI/standalone/tests), matching
   the `DbConnectionInterface` DI pattern.
5. **Metadata dictionary** — file rows carry entity_type/entity_id, original
   name, mime type, size, storage backend, storage path, uploader, upload date,
   description, inactive flag.

## Business Value

- **Single source of truth** — attachments live once, referenced by both FA and
  CRM views, never duplicated or re-uploaded.
- **Audit** — who uploaded what, when, to which entity, on which backend.
- **Compliance** — signed contracts and change orders travel with the project
  record the whole lifecycle.

## Scope

**In:** project/task/milestone attachments; FA-native backend; CRM-docs emitter;
local backend; dictionary + repository + service with lifecycle hooks and
activity-log entries.

**Out (follow-ups):** S3/object-storage driver, virus scanning, per-role
download policies beyond SA area, OCR/thumbnails.

## Related Requirements

- BR-PROJECT-001 (fixed-price — contract documents), BR-PROJECT-002 (templates —
  attach template document sets), BR-PROJECT-003 (milestone/dependency SOWs).
