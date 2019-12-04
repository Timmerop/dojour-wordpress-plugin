# Dojour WordPress Plugin

This plugin creates a new post type `dojour_event` and establishes a custom API to interact with such post types, allowing the creation, edition and deletion of these posts remotely.

## Requirements

* PHP >= 7.2
* WordPress >= 5.2
* [Application Passwords Plugin](https://wordpress.org/plugins/application-passwords/)
* Node.js
* Yarn

## Development
1. Install all the dependencies using `yarn install`
2. The plugin is using PostCSS for its styling and it needs to be transpiled using `yarn build` or `yarn watch:css` to watch for any changes and automatically rebuild it
3. The contents inside the `dist` directory must be zipped and this zip is the one that can be distributed to be installed

## Authentication
By default, WordPress uses a cookie based authentication system which means that once a user is logged, it can perform any actions on the blog if that cookie is present, however, since we want to interact with the sites on the server side, we can't rely on cookies as we don't have them.

Since we can't leave the endpoints open due to security reasons, this plugin requires the installation of another one to perform the authorization and authentication procedures. [Application Passwords Plugin](https://wordpress.org/plugins/application-passwords/) was chosen because of its simplicity and it being actively maintained.

While technically we could include the Aplication Passwords plugin as part of this one, it has been decided that to prevent falling out of sync with it, users should install it separatelly.

### Authorization Flow
To request the creation of an application password, the flow is similar to that of OAuth:

Show the user the grant screen by redirecting them to this path on their WordPress site:

```
/wp-admin/admin.php?page=auth_app
```

Add the following URL parameters to the URL to provide information of your app:

| Parameter | Required | Description |
| --------- | -------- | ----------- |
| `app_name` | Yes | The human readable identifier for your app. This will be the name of the generated application password, so structure it like … “WordPress Mobile App on iPhone 12” for uniqueness between multiple versions. If omitted, the user will be required to provide an application name. |
| `success_url` | No | The URL that you’d like the user to be sent to if they approve the connection. Two GET variables will be appended when they are passed back — user_login and password — these credentials can then be used for API calls. If the success_url variable is omitted, a password will be generated and displayed to the user, to manually enter into your application. |
| `reject_url` | No | If included, the user will get sent there if they reject the connection. If omitted, the user will be sent to the success_url, with ?success=false appended to the end. If the success_url is omitted, the user will be sent to their dashboard. |


Once the user accepts, a new password will be created and the user will be redirected to the `success_url` you provided with two parameters in the URL that need to be saved as they are required to perform API calls, `user_login` and `password`.

## API Documentation

The API is versioned, with its base path being `/wp-json/dojour/v<version_number>/`. 

### Common Headers

All request sent to this API must include the following headers:

| Header | Type | Description | Example |
| ------ | ---- | ----------- | ------- |
| Authorization | `String` | The `Basic` authorization token | `Basic ZGllZ286elFWYyBRdEZKIFZPMTkgZWdVbyBRaDZyIHJSZW4=` |
| Content-Type | `String` | The content type to be used, in this case is always JSON | `application/json` |

### Common Response Codes

All requests share these response codes unless stated otherwise in their individual description.

#### 200: OK
The operation was successful
```javascript
{
  "version": string, // Semantic version of the plugin
  "version_code": integer, // Numeric representation of the version
  "success": true
}
```

#### 401: Unauthorized
The credentials provided for authorization were incorrect or they were not sent.

```javascript
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that.",
  "data": {
    "status": 401
  }
}
```

#### 500: Server Error
There's an issue with the site, if it only happens when trying to access the endpoint, it might mean that the Application Passwords plugin is not installed or was deactivated.

---

### GET `/status`

This endpoint is mainly used to check if the access token we have for the site is still valid.


### POST `/settings`

This endpoint is used to set any settings from the Dojour wordpress connect modal to the wordpress site.

#### Request Body
```javascript
{
  "archive": string // The URL path for the archive
}
```

### POST `/dojour/v1/event`

This endpoint will create a new `Event` post if a post with the same `Dojour Event ID` did not already exist.

#### Request Body
```javascript
{
  "id": integer, // Dojour event ID
  "title": string,
  "description": string,
  "absolute_url": string,
  "location": null | {
    "title": string,
    "address": string
  },
  "has_offer": boolean,
  "photo": {
      "file": string // Full public URL to the photo file
  },

  "first_showing": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
  "last_showing": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
  "showing_count": integer,

  "showing": null,

  "post_frequency": string // event | showing
}
```

#### Response

##### 200: OK

Event successfully created
```javascript
{
  "id": integer, // The ID of the post on WordPress
  "version": string, // Semantic version of the plugin
  "version_code": integer, // Numeric representation of the version
  "success": true
}
```

### PUT `/dojour/v1/event`

This endpoint will update **all posts** that have the same `Dojour Event ID` as the one provided.

#### Request Body
```javascript
{
  "id": integer, // Dojour event ID
  "title": string,
  "description": string,
  "absolute_url": string,
  "location": null | {
    "title": string,
    "address": string
  },
  "has_offer": boolean,
  "photo": {
      "file": string // Full public URL to the photo file
  },

  "first_showing": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
  "last_showing": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
  "showing_count": integer,

  "showing": null,

  "post_frequency": string // event | showing
}
```

### DELETE `/dojour/v1/event`

This endpoint will delete **all posts** that have the same `Dojour Event ID` as the one provided.

#### Request Body
```javascript
{
  "id": integer, // Dojour event ID
  "title": string,
  "description": string,
  "absolute_url": string,
  "location": null | {
    "title": string,
    "address": string
  },
  "has_offer": boolean,
  "photo": {
      "file": string // Full public URL to the photo file
  },

  "first_showing": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
  "last_showing": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
  "showing_count": integer,

  "showing": null,

  "post_frequency": string // event | showing
}
```

### POST `/dojour/v1/showing`

This endpoint will create a new `Showing` post if a post with the same `Dojour Event ID` + `Dojour Showing ID` did not already exist.

#### Request Body
```javascript
{
  "id": integer, // Dojour event ID
  "title": string,
  "description": string,
  "absolute_url": string,
  "location": null | {
    "title": string,
    "address": string
  },
  "has_offer": boolean,
  "photo": {
      "file": string // Full public URL to the photo file
  },

  "first_showing": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
  "last_showing": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
  "showing_count": integer,

  "showing": {
    "id": integer,
    "start_date": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
    "end_date": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
    "door_time": string<datetime> // Y-m-dTH:M:Sz America/Chicago
  },

  "post_frequency": string // event | showing
}
```

#### Response

##### 200: OK

Showing successfully created
```javascript
{
  "id": integer, // The ID of the post on WordPress
  "version": string, // Semantic version of the plugin
  "version_code": integer, // Numeric representation of the version
  "success": true
}
```

### PUT `/dojour/v1/showing`

This endpoint will create update the `Showing` post that matches the `Dojour Event ID` + `Dojour Showing ID` provided or create it if it didn't exist.

#### Request Body
```javascript
{
  "id": integer, // Dojour event ID
  "title": string,
  "description": string,
  "absolute_url": string,
  "location": null | {
    "title": string,
    "address": string
  },
  "has_offer": boolean,
  "photo": {
      "file": string // Full public URL to the photo file
  },

  "first_showing": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
  "last_showing": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
  "showing_count": integer,

  "showing": {
    "id": integer,
    "start_date": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
    "end_date": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
    "door_time": string<datetime> // Y-m-dTH:M:Sz America/Chicago
  },

  "post_frequency": string // event | showing
}
```

### DELETE `/dojour/v1/showing`

This endpoint will delete the `Showing` post that matches the `Dojour Event ID` + `Dojour Showing ID` provided.

#### Request Body
```javascript
{
  "id": integer, // Dojour event ID
  "title": string,
  "description": string,
  "absolute_url": string,
  "location": null | {
    "title": string,
    "address": string
  },
  "has_offer": boolean,
  "photo": {
      "file": string // Full public URL to the photo file
  },

  "first_showing": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
  "last_showing": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
  "showing_count": integer,

  "showing": {
    "id": integer,
    "start_date": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
    "end_date": string<datetime>, // Y-m-dTH:M:Sz America/Chicago
    "door_time": string<datetime> // Y-m-dTH:M:Sz America/Chicago
  },

  "post_frequency": string // event | showing
}
```
