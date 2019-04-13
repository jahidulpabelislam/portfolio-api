-- Add the new status column
ALTER TABLE `portfolio_project`
    ADD COLUMN `status` varchar(20) DEFAULT "draft"
    AFTER `date`;

-- Update all status values to "published"
UPDATE `portfolio_project`
    SET `status` = "published";
