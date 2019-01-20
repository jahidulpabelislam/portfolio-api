# Portfolio API

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

## Setup

Create Database.

Import /set-up/base-structure.sql into newly created database.

Copy /set-up/Config-sample.php and move to /classes/Config.php then fill in the 6 necessary constants.

Copy /set-up/Hasher-sample.php and move to /classes/Hasher.php then update both the functions with your Hashing functionality.

Copy /set-up/Auth-sample.php and move to /classes/Auth.php then update all 3 functions with your Auth functionality.