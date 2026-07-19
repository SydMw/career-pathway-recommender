-- Removes soft-delete support. Admin delete is now permanent (a real
-- DELETE, cascading to academic_records/recommendations/feedback via
-- existing FKs) instead of setting deleted_at, so the trash/restore
-- page and this column are no longer needed.
-- Before running against a live database: any row with deleted_at IS
-- NOT NULL should be hard-deleted first, otherwise dropping the column
-- silently restores it as an active account.
USE career_system;

DELETE FROM users WHERE deleted_at IS NOT NULL;

ALTER TABLE users
    DROP COLUMN deleted_at;
