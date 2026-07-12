ALTER TABLE games ADD COLUMN expansions JSON NULL AFTER publishers;
ALTER TABLE games ADD COLUMN expansion_of JSON NULL AFTER expansions;
