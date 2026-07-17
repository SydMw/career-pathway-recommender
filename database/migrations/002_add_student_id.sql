-- Adds a formatted student ID (e.g. STU-2026-0001) to an already-deployed
-- users table. Safe to skip if you imported the current schema.sql from
-- scratch, since it already includes this column.
USE career_system;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS student_id VARCHAR(20) UNIQUE AFTER user_id;
