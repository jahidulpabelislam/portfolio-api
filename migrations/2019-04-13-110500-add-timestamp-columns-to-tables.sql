ALTER TABLE 'portfolio_project'
    ADD COLUMN 'created_at' DATETIME,
    ADD COLUMN 'updated_at' DATETIME;

ALTER TABLE 'portfolio_project_image'
    ADD COLUMN 'created_at' DATETIME,
    ADD COLUMN 'updated_at' DATETIME;
