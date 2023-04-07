-- SQL code for migrating the server from v1.0.0 to v2.0.0 table format:
BEGIN TRANSACTION;

ALTER TABLE records ADD COLUMN trackSet TEXT;
ALTER TABLE records ADD COLUMN trackNumber INTEGER;
UPDATE records SET trackSet = SUBSTR(track, 0, INSTR(track, " - "));
UPDATE records SET trackNumber = CAST(SUBSTR(track, INSTR(track, " - ")+3) AS INTEGER);

CREATE TABLE records_temp (
    game         TEXT NOT NULL,
    user         TEXT NOT NULL,
    trackSet     TEXT NOT NULL,
    trackNumber  INTEGER NOT NULL,
    best         INTEGER NOT NULL,
    PRIMARY KEY (game, user, trackSet, trackNumber)
);

INSERT INTO records_temp SELECT game, user, trackSet, trackNumber, best FROM records;

DROP TABLE records;

ALTER TABLE records_temp RENAME TO records;

COMMIT;
