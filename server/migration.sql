-- SQL code for migrating the server from v1.0.0 to v2.0.0 table format:
BEGIN TRANSACTION;

-- From v2.0.0 onwards the track column is split into two columns: trackSet ("Summer 2020") and trackNumber (1 to 25).
ALTER TABLE records ADD COLUMN trackSet TEXT;
ALTER TABLE records ADD COLUMN trackNumber INTEGER;
UPDATE records SET trackSet = SUBSTR(track, 0, INSTR(track, " - "));
UPDATE records SET trackNumber = CAST(SUBSTR(track, INSTR(track, " - ")+3) AS INTEGER);

-- Add NOT NULL constraints for the new columns. For this we have to create a new table because
-- SQLite does not support adding the NOT NULL constraint to existing columns.
CREATE TABLE records_temp (
    game         TEXT NOT NULL,
    user         TEXT NOT NULL,
    trackSet     TEXT NOT NULL,
    trackNumber  INTEGER NOT NULL,
    best         INTEGER NOT NULL,
    PRIMARY KEY (game, user, trackSet, trackNumber)
);

-- Copy data from old table.
INSERT INTO records_temp SELECT game, user, trackSet, trackNumber, best FROM records;

-- Replace the old table.
DROP TABLE records;
ALTER TABLE records_temp RENAME TO records;

COMMIT;
