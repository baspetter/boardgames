-- Allows best_players to store a range (e.g. "2-4") instead of a single number.
-- Run against an existing database with:
--   mysql -u USER -p DBNAME < sql/003_best_players_range.sql
ALTER TABLE games
  MODIFY COLUMN best_players VARCHAR(20) NULL;
