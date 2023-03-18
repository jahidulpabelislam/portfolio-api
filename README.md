# Portfolio API

[![CodeFactor](https://www.codefactor.io/repository/github/jahidulpabelislam/portfolio-api/badge?style=flat-square)](https://www.codefactor.io/repository/github/jahidulpabelislam/portfolio-api)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/491ad3efe79b413c9ecdbc941342986c)](https://app.codacy.com/app/jahidulpabelislam/portfolio-api?utm_source=github.com&utm_medium=referral&utm_content=jahidulpabelislam/portfolio-api&utm_campaign=Badge_Grade_Dashboard)

This API is to manage the projects and their images displayed in my [Portfolio](https://jahidulpabelislam.com/). This is all connected together via the [CMS Repo](https://github.com/jahidulpabelislam/portfolio-cms/) as well the actual [Portfolio Repo](https://github.com/jahidulpabelislam/portfolio/).

This (and the CMS) was created as I had realised that all my projects were consistent regarding what information was being shown. Therefore, for future-proofing and better maintainability, I thought it would be good to enforce a project structure, so each project has the same information. This was done by adding CRUD abilities through a database, this API and CMS.
By doing this, on the website, I can use templating to generate the projects area, this reduced the duplication of HTML. I can then also easily update a project structure for all current projects at once (e.g. adding a new piece of information). I will only need to update the database structure, API endpoints, CMS, and website.

The first two versions were developed within the [Portfolio repo](https://github.com/jahidulpabelislam/portfolio/). However, from v3, it was decided it would be a good idea to separate the API (and the CMS) from the portfolio to aid maintainability and readability.

## Requirements

-   Git
-   PHP 8.0+
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

    -   Copy `/set-up/Manager-sample.php` and move to `/src/Auth/Manager.php` then update all 3 functions with your auth functionality.

## API

### Endpoints

#### Resources

| URI                                                                                                    |                 Description                 |  `GET`   |  `POST`  |  `PUT`   | `DELETE` |
| ------------------------------------------------------------------------------------------------------ | :-----------------------------------------: | :------: | :------: | :------: | :------: |
| [/projects/](https://api.jahidulpabelislam.com/v4/projects/)                                           |                All projects                 | &#10004; | &#10004; | &#10006; | &#10006; |
| [/projects/{projectId}/](https://api.jahidulpabelislam.com/v4/projects/13/)                            |              A single project               | &#10004; | &#10006; | &#10004; | &#10004; |
| [/projects/{projectId}/images/](https://api.jahidulpabelislam.com/v4/projects/13/images/)              |     Images attached to a single project     | &#10004; | &#10004; | &#10006; | &#10006; |
| [/projects/{projectId}/images/{imageId}/](https://api.jahidulpabelislam.com/v4/projects/13/images/72/) | A single image attached to a single project | &#10004; | &#10006; |  &#10006; | &#10004; |

#### Auth

| URI                                                                  |       Description        |  `GET`   |  `POST`  |  `PUT`   | `DELETE` |
| -------------------------------------------------------------------- | :----------------------: | :------: | :------: | :------: | :------: |
| [/auth/login/](https://api.jahidulpabelislam.com/v4/auth/login/)     |          Log in          | &#10006; | &#10004; | &#10006; | &#10006; |
| [/auth/logout/](https://api.jahidulpabelislam.com/v4/auth/logout/)   |         Log out          | &#10006; | &#10006; | &#10006; | &#10004; |
| [/auth/status/](https://api.jahidulpabelislam.com/v4/auth/status/) | Get current auth status | &#10004; | &#10006; | &#10006; | &#10006; |
