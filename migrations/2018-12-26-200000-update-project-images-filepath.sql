UPDATE PortfolioProjectImage
    SET File = replace(File, '/assets/images/projects/', '/project-images/')
    WHERE File LIKE '/assets/images/projects/%';
