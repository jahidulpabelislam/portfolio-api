CREATE TABLE portfolio_project_type
(
    id int auto_increment primary key,
    name varchar(20) null,
    created_at DATETIME,
    updated_at DATETIME
);

-- Add the new type id column
ALTER TABLE portfolio_project
    ADD COLUMN type_id int AFTER type,
    ADD FOREIGN KEY type_id(type_id) REFERENCES portfolio_project_type(id)
;
