-- Adds a per-user customizable accent color.
-- Run against an existing database with:
--   mysql -u USER -p DBNAME < sql/002_add_user_accent_color.sql
ALTER TABLE users
  ADD COLUMN accent_color VARCHAR(7) NULL AFTER password_hash;
