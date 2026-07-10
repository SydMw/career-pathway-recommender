-- Adds brute-force lockout tracking to an already-deployed users table.
-- Safe to skip if you imported the current schema.sql from scratch.
USE career_system;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS failed_login_attempts INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL DEFAULT NULL;
