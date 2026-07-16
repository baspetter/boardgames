ALTER TABLE collection_entries ADD COLUMN loaned_to VARCHAR(100) NULL AFTER notes;
ALTER TABLE collection_entries ADD COLUMN loaned_at DATETIME NULL AFTER loaned_to;
