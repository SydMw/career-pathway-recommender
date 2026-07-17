-- Adds soft-delete support (a 30-day recoverable trash) to an already-
-- deployed users table. Safe to skip if you imported the current
-- schema.sql from scratch, since it already includes this column.
USE career_system;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL;
