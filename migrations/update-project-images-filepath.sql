update PortfolioProjectImage set File = replace(File, "/assets/images/projects/", "/project-images/") where File LIKE "/assets/images/projects/%";