# Portfolio API

## API

### Entities
 
| URI | Description | <code>GET</code> | <code>POST</code> | <code>DELETE</code> | <code>PUT</code>|
| --- | :----: | :----: |:----:|:----:|:----:|
| [/projects/](https://api.jahidulpabelislam.com/v3/projects/) | All Projects | &#10004; | &#10004; |  |  |
| [/projects/{project_id}/](https://api.jahidulpabelislam.com/v3/projects/13/) | A Single Project | &#10004; |  | &#10004; | &#10004; |
| [/projects/{project_id}/images/](https://api.jahidulpabelislam.com/v3/projects/13/images/) | Images Attached To a Single Project | &#10004; | &#10004; |  |  |
| [/projects/{project_id}/images/{image_id}/](https://api.jahidulpabelislam.com/v3/projects/13/images/72/) | A Single Image Attached to a Single Project | &#10004; |  | &#10004; | &#10004; |

## Setup

Create Database.

Import /set-up/base-structure.sql into newly created database.

Copy /set-up/Config-sample.php and move to /classes/Config.php then fill in the 6 necessary constants.

Copy /set-up/Hasher-sample.php and move to /classes/Hasher.php then update both the functions with your Hashing functionality.

Copy /set-up/Auth-sample.php and move to /classes/Auth.php then update all 3 functions with your Auth functionality.