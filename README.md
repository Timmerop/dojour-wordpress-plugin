# Dojour WordPress Plugin

This plugin creates a new post type `dojour_event` and establishes a custom API to interact with such post types, allowing the creation, edition and deletion of these posts remotely.

## Requirements

* PHP >= 7.2
* WordPress >= 5.2
* [Application Passwords Plugin](https://wordpress.org/plugins/application-passwords/)

## Authentication
By default, WordPress uses a cookie based authentication system which means that once a user is logged, it can perform any actions on the blog if that cookie is present, however, since we want to interact with the sites on the server side, we can't rely on cookies as we don't have them.

Since we can't leave the endpoints open due to security reasons, this plugin requires the installation of another one to perform the authorization and authentication procedures. [Application Passwords Plugin](https://wordpress.org/plugins/application-passwords/) was chosen because of its simplicity and it being actively maintained.

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