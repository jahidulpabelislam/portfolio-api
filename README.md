# Portfolio API

This API is to manage the CRUD of projects and its images. This will essentially will be used to display projects in my [Portfolio](https://jahidulpabelislam.com/), this is all joined together via a [CMS](https://github.com/jahidulpabelislam/portfolio-cms/).

The API and CMS were created as I had realised that the projects within my site were consistent regarding what information was being shown. Therefore, for future proofing and making it easier to maintain, I thought to make sure it is ALWAYS consistent I can define a common project structure, so each project has the same information. This was done by a database through this API and CMS.

By doing this, in the website I can define one project element/HTML structure and styling and then just do a loop through all the projects returned from the API via an AJAX request and create multiple project elements using that one defined structure.

It was originally built within the [Portfolio](https://github.com/jahidulpabelislam/portfolio/) project/repo, and two versions were built initially built within this project. However, from v3, it was decided it would be good to separate the API from the portfolio to aid maintainability and readability.

## API

### Endpoints

#### Resources

| URI | Description | <code>GET</code> | <code>POST</code> | <code>DELETE</code> | <code>PUT</code>|
| --- | :----: | :----: |:----:|:----:|:----:|
| [/projects/](https://api.jahidulpabelislam.com/v3/projects/) | All Projects | &#10004; | &#10004; | &#10006; | &#10006; |
| [/projects/{projectId}/](https://api.jahidulpabelislam.com/v3/projects/13/) | A Single Project | &#10004; | &#10006; | &#10004; | &#10004; |
| [/projects/{projectId}/images/](https://api.jahidulpabelislam.com/v3/projects/13/images/) | Images Attached To a Single Project | &#10004; | &#10004; | &#10006; | &#10006; |
| [/projects/{projectId}/images/{imageId}/](https://api.jahidulpabelislam.com/v3/projects/13/images/72/) | A Single Image Attached to a Single Project | &#10004; | &#10006; | &#10004; | &#10004; |

#### Auth

| URI | Description | <code>GET</code> | <code>POST</code> | <code>DELETE</code> | <code>PUT</code>|
| --- | :----: | :----: |:----:|:----:|:----:|
| [/login/](https://api.jahidulpabelislam.com/v3/login/) | Log In A User | &#10006; | &#10004; | &#10006; | &#10006; |
| [/logout/](https://api.jahidulpabelislam.com/v3/logout/) | Log Current User Out | &#10006; | &#10006; | &#10004; | &#10006; |
| [/session/](https://api.jahidulpabelislam.com/v3/session/) | Get Current Auth Status | &#10004; | &#10006; | &#10006; | &#10006; |


## Requirements

* Git
* PHP7
* PHP PDO
* Composer
* MySQL
* Apache

## Setup

1. Clone repo with `git@github.com:jahidulpabelislam/portfolio-api.git`.

2. Navigate to project folder and run `composer install`.

3. Create new database.

4. A MySQL user with select, insert, update & delete capabilities on new database.

5. Import `/set-up/base-structure.sql` into newly created database.

6. Run migrations from `/migrations/*` (if any) into MySQL.

7. Copy and fill in example files (in any order).

    * Copy `/set-up/Config-sample.php` and move to `/classes/Config.php` then fill in the 6 necessary constants.

    * Copy `/set-up/Hasher-sample.php` and move to `/classes/Hasher.php` then update both the functions with your Hashing functionality.

    * Copy `/set-up/Auth-sample.php` and move to `/classes/Auth.php` then update all 3 functions with your Auth functionality.