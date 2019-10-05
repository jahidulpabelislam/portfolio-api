-- Add the new type column
ALTER TABLE portfolio_project
    ADD COLUMN type varchar(20) DEFAULT ''
    AFTER name;
