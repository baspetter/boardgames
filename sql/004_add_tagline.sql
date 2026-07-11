-- Adds a short, user-editable one-line tagline shown near the game title.
-- Run against an existing database with:
--   mysql -u USER -p DBNAME < sql/004_add_tagline.sql
ALTER TABLE games
  ADD COLUMN tagline VARCHAR(200) NULL AFTER description;
