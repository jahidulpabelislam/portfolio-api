# Portfolio API

[![CodeFactor](https://www.codefactor.io/repository/github/jahidulpabelislam/portfolio-api/badge?style=flat-square)](https://www.codefactor.io/repository/github/jahidulpabelislam/portfolio-api)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/491ad3efe79b413c9ecdbc941342986c)](https://app.codacy.com/app/jahidulpabelislam/portfolio-api?utm_source=github.com&utm_medium=referral&utm_content=jahidulpabelislam/portfolio-api&utm_campaign=Badge_Grade_Dashboard)

This API is to manage the projects and its images in my [Portfolio](https://jahidulpabelislam.com/). This will essentially will be used to display projects in my Portfolio, this is all connected together via the [CMS Repo](https://github.com/jahidulpabelislam/portfolio-cms/) as well the actual [Portfolio Repo](https://github.com/jahidulpabelislam/portfolio/).

The API (and CMS) was created as I had realised that all the projects within my site were consistent regarding what information was being shown. Therefore, for future proofing and making it easier to maintain, I thought to make sure it is ALWAYS consistent I can define a common project structure, so each project has the same information. This was done by adding CRUD abilities through a database, this API and CMS.

By doing this, in the website I can define one project element/HTML structure and styling and then just do a loop through all the projects returned from the API via an AJAX request and create multiple project elements using that one defined structure.

This way I can easily update a project structure for all current projects at once (e.g adding a new piece of information). I will only need to update the Database structure, API endpoints, CMS, and Website (HTML, CSS & Javascript).

It was originally built within the [Portfolio project/repo](https://github.com/jahidulpabelislam/portfolio/), and two versions were built initially built within this project. However, from v3, it was decided it would be a good idea to separate the API (and CMS) from the portfolio to aid maintainability and readability.

## Work Done

### [v1](https://github.com/jahidulpabelislam/portfolio/tree/v2/admin/)

The initial creation of the API began and finished in summer of 2016. The one main aim of this project was to make a start and finish adding functionality for managing the CRUD of projects and images linked to projects.

I knew from the beginning that I would build the API using PHP as I had previous experience building an API with PHP. And as a challenge for myself I also aimed to build the whole API (& CMS) from scratch (without any libraries/plugins).

### [v2](https://github.com/jahidulpabelislam/portfolio/tree/v4/api/v2/)

In the summer of 2017 I decided to start on a new version, version 2 which eventually finished in the summer of 2018. v2 was just a slight update on the code base to tidy up the code base such as:

-   updating the URI structure
-   better login and logout functionality (e.g. hashing passwords)
-   adding better security throughout

### [v3](https://github.com/jahidulpabelislam/portfolio-api/releases/tag/v3/)

Towards the end of 2018, I aimed to split the API part of the Portfolio project and create a new base project to build from in the future for new features and new versions. This was then the start of v3.

Also a sub-aim was also to refactor the whole of the API part of the original project with better code and following consistent standards throughout (PHP & SQL).

Also for the Cross domain requests from the CMS for secured endpoints such as Deletes & Posts I integrated JWT auth using Firebase.

## API

### Endpoints

#### Resources

| URI                                                                                                    |                 Description                 |  `GET`   |  `POST`  |  `PUT`   | `DELETE` |
| ------------------------------------------------------------------------------------------------------ | :-----------------------------------------: | :------: | :------: | :------: | :------: |
| [/projects/](https://api.jahidulpabelislam.com/v3/projects/)                                           |                All projects                 | &#10004; | &#10004; | &#10006; | &#10006; |
| [/projects/{projectId}/](https://api.jahidulpabelislam.com/v3/projects/13/)                            |              A single project               | &#10004; | &#10006; | &#10004; | &#10004; |
| [/projects/{projectId}/images/](https://api.jahidulpabelislam.com/v3/projects/13/images/)              |     Images attached to a single project     | &#10004; | &#10004; | &#10006; | &#10006; |
| [/projects/{projectId}/images/{imageId}/](https://api.jahidulpabelislam.com/v3/projects/13/images/72/) | A single image attached to a single project | &#10004; | &#10006; | &#10004; | &#10004; |

#### Auth

| URI                                                                  |       Description        |  `GET`   |  `POST`  |  `PUT`   | `DELETE` |
| -------------------------------------------------------------------- | :----------------------: | :------: | :------: | :------: | :------: |
| [/auth/login/](https://api.jahidulpabelislam.com/v3/auth/login/)     |          Log in          | &#10006; | &#10004; | &#10006; | &#10006; |
| [/auth/logout/](https://api.jahidulpabelislam.com/v3/auth/logout/)   |         Log out          | &#10006; | &#10006; | &#10006; | &#10004; |
| [/auth/session/](https://api.jahidulpabelislam.com/v3/auth/session/) | Get current login status | &#10004; | &#10006; | &#10006; | &#10006; |

## Requirements

-   Git
-   PHP 7.1+
-   PHP PDO
-   Composer
-   MySQL 5+
-   Apache

## Setup

1.  Clone repo with `git@github.com:jahidulpabelislam/portfolio-api.git`.

2.  Navigate to project folder and run `composer install`.

3.  Create new database.

4.  A MySQL user with select, insert, update & delete capabilities on the new database.

5.  Import `/set-up/base-structure.sql` into the newly created database.

6.  Run migrations from `/migrations/*` (if any) into MySQL in the order of the number at the start of the filenames.

7.  Copy and fill in example files (in any order).

    -   Copy `/set-up/User-sample.php` and move to `/src/Entity/User.php` then update all 3 functions with your Auth functionality.

## Related Projects

-   [Portfolio](https://github.com/jahidulpabelislam/portfolio/)
-   [CMS](https://github.com/jahidulpabelislam/portfolio-cms/)
