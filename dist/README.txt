=== Dojour ===
Tags: dojour, events
Requires at least: 5.2
Tested up to: 5.2
Requires PHP: 7.2
Stable tag: trunk
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin lets you publish the events you create on Dojour to your WordPress site automatically.

== Description ==

This plugin lets you publish the events you create on Dojour to your WordPress site automatically.

The code and more information about the inner workings of the plugin can be found at the [GitHub repository](https://github.com/Timmerop/dojour-wordpress-plugin).

== Installation ==

You can install this plugin from the WordPress plugin directory or manually using a zip file.

= Plugin Directory =

1. Install the [Application Passwords Plugin](https://wordpress.org/plugins/application-passwords/)
2. In the your site's admin dashboard go to Plugins > Add New and search for “Dojour”
3. Click install and then click activate

= Upload Manually =

1. Install the [Application Passwords Plugin](https://wordpress.org/plugins/application-passwords/)
2. Download the zip file from [Dojour](https://dojour.us/admin-tools/profile)
3. In the your site's admin dashboard go to Plugins > Add New
4. Click the “Upload Plugin” button shown on the top of the page
5. Click the file input button and select the zip file you downloaded
6. Click install and then click activate

== Frequently Asked Questions ==

= Why do I need to install the Application Passwords plugin? =
This plugin will create new endpoints on your WordPress site REST API. In order to secure
those endpoints and prevent them from being publicly available, you will have to create an
application password which will be used by Dojour to be able to post to your site.

== Changelog ==

= 0.2.2 =
* Cancelled events won't show the action to buy tickets or view the event on Dojour anymore

= 0.2.1 =
* Small performance improvements

= 0.2.0 =
* A sub theme has been added to show events with custom styling
* Event cover images are now only downloaded once
* Event archive is now a customized page

= 0.1.0 =
* Initial Release
