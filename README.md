# Portfolio API

API to manage the projects and their images displayed in my [portfolio](https://jahidulpabelislam.com/). It provides CRUD functionality for the [CMS](https://github.com/jahidulpabelislam/portfolio-cms/) as well as the central source for the projects shown on the actual [portfolio](https://github.com/jahidulpabelislam/portfolio/).

## Why

I wanted a system to quickly and easily manage the projects without having to make code changes which lead to the creation of a CMS. I therefore needed a single source of information for both the CMS and portfolio which is this.

The first two versions were developed within the portfolio. However, from v3, I separated the API (and CMS) from the portfolio for improved maintainability and readability.

## Requirements

- Git
- PHP 8.0+
- PHP PDO
- Composer
- MySQL 5+
- Apache

## Setup

1. Clone repo with `git@github.com:jahidulpabelislam/portfolio-api.git`.

2. Navigate to project folder and run `composer install`.

3. Create new database.

4. A MySQL user with select, insert, update & delete capabilities on the new database.

5. Import `/set-up/base-structure.sql` into the newly created database.

6. Run migrations from `/migrations/*` (if any) into MySQL in the order of the number at the start of the filenames.

7. Copy and fill in example files (in any order).

    - Copy `/set-up/Manager-sample.php` and move to `/src/Auth/Manager.php` then update all 3 functions with your auth functionality.

## API

### Endpoints

#### Resources

| URI                                                                                                    |                 Description                 |  `GET`   |  `POST`  |  `PUT`   | `DELETE` |
|--------------------------------------------------------------------------------------------------------|:-------------------------------------------:|:--------:|:--------:|:--------:|:--------:|
| [/projects/](https://api.jahidulpabelislam.com/v4/projects/)                                           |                All projects                 | &#10004; | &#10004; | &#10006; | &#10006; |
| [/projects/{projectId}/](https://api.jahidulpabelislam.com/v4/projects/13/)                            |              A single project               | &#10004; | &#10006; | &#10004; | &#10004; |
| [/projects/{projectId}/images/](https://api.jahidulpabelislam.com/v4/projects/13/images/)              |     Images attached to a single project     | &#10004; | &#10004; | &#10006; | &#10006; |
| [/projects/{projectId}/images/{imageId}/](https://api.jahidulpabelislam.com/v4/projects/13/images/72/) | A single image attached to a single project | &#10004; | &#10006; | &#10006; | &#10004; |

#### Auth

| URI                                                                |       Description       |  `GET`   |  `POST`  |  `PUT`   | `DELETE` |
|--------------------------------------------------------------------|:-----------------------:|:--------:|:--------:|:--------:|:--------:|
| [/auth/login/](https://api.jahidulpabelislam.com/v4/auth/login/)   |         Log in          | &#10006; | &#10004; | &#10006; | &#10006; |
| [/auth/logout/](https://api.jahidulpabelislam.com/v4/auth/logout/) |         Log out         | &#10006; | &#10006; | &#10006; | &#10004; |
| [/auth/status/](https://api.jahidulpabelislam.com/v4/auth/status/) | Get current auth status | &#10004; | &#10006; | &#10006; | &#10006; |
