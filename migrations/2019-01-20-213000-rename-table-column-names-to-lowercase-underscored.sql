ALTER TABLE `portfolio_project`
    CHANGE `ID` `id` int(11) NOT NULL AUTO_INCREMENT,
    CHANGE `Name` `name` varchar(200) NOT NULL,
    CHANGE `LongDescription` `long_description` varchar(10000) DEFAULT NULL,
    CHANGE `ShortDescription` `short_description` varchar(10000) NOT NULL,
    CHANGE `Skills` `skills` varchar(200) DEFAULT NULL,
    CHANGE `Link` `link`  varchar(200) DEFAULT NULL,
    CHANGE `GitHub` `github` varchar(200) NOT NULL,
    CHANGE `Download` `download` varchar(200) DEFAULT NULL,
    CHANGE `colour` `colour` varchar(20) DEFAULT NULL,
    CHANGE `Date` `date` date NOT NULL;

ALTER TABLE `portfolio_project_image`
    CHANGE `ID` `id` int(11) NOT NULL AUTO_INCREMENT,
    CHANGE `ProjectID` `project_id` int(11) NOT NULL,
    CHANGE `File` `file` varchar(500) NOT NULL,
    CHANGE `SortOrderNumber` `sort_order_number` int(11) NOT NULL;
