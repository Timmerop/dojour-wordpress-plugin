<?php
/**
 * Plugin Name:       Dojour
 * Plugin URI:        https://dojour.us/
 * Description:       A way for businesses to link their Dojour account to their WordPress website.
 * Version:           0.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Dojour
 * Author URI:        https://dojour.us/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain:       dojour
 */

// Exit if this file is being tried to be accessed directly.
if (!defined ('ABSPATH')) {
	exit;
}

final class Dojour {

	private static $instance = null;

	private static $api_namespace = 'dojour/v1';

	public static function get_instance () {
		if (self::$instance === null) {
			self::$instance = new self ();
		}
		return self::$instance;
	}

	/**
	 * ======================================
	 * Utility Functions Code Starts Here
	 * ======================================
	 */

	/**
	 * Check if the dependencies required by the plugin are installing and show
	 * an error message if they aren't.
	 *
	 * @return void
	 */
	public static function check_dependencies () {
		// We need the application passwords plugin to put some security on the
		// API endpoints we'll create
		if (!class_exists ('Application_Passwords')) {
			self::show_message ('error', '<a href="https://wordpress.org/plugins/application-passwords/" target="_blank" rel="noopener noreferrer">Application Passwords Plugin</a> is required for the <a href="https://dojour.us/" target="_blank" rel="noopener noreferrer">Dojour</a> plugin to work. Please make sure it has been installed and that it is activated.');
		}
	}

	/**
	 * Display a custom message on the admin site with a specified level.
	 *
	 * @param string $level - The level of the message being shown. Depending on
	 * the type of message the level should be set to 'error', 'warning', 'success'
	 * or 'info'.
	 * @param string $message - The message to show
	 *
	 * @return void
	 */
	public static function show_message ($level = '', $message = '') {
		// We'll only show messages on the admin site to avoid disclosing any message to viewers
		if (is_admin ()) {
			echo "<div class='notice notice-$level is-dismissible'><p>$message</p></div>";
		}
	}

		/**
	 * Find an event post given the event ID on dojour
	 *
	 * @param int $remote_id - The ID of an event on dojour
	 *
	 * @return int|null - The ID of the post on wordpress corresponding to that
	 * event or null if it wasn't found
	 */
	public static function find_post ($remote_id) {
		$posts = get_posts ([
			'numberposts' => 1,
			'post_type'  => 'dojour_event',
			'meta_key' => 'remote_id',
			'meta_value' => $remote_id
		]);

		if (count ($posts) > 0) {
			$post = $posts[0];

			if ($post instanceof WP_Post) {
				return $post -> ID;
			}

			return $post;
		}
		return null;
	}

	/**
	 * Fetch the image cover for an event from a public URL and set it as the
	 * post thumbnail
	 *
	 * @param string $url - A public URL from where to fetch the image
	 * @param int $post_id - The ID of the WordPress post the image will be
	 * associated with
	 *
	 * @return Array
	 */
	public static function fetch_image_for_post ($url, $post_id) {
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		$attachment_id = media_sideload_image ($url, $post_id, null, 'id');

		set_post_thumbnail ($post_id, $attachment_id);
	}

	/**
	 * This function will create the custom post types for dojour events and
	 * flush the rewrite rules so the URLs start redirecting to the right
	 * resources.
	 *
	 * @return void
	 */
	public static function setup_post_type () {
		$settings = get_option ('dojour_settings');
		$archive = 'dojour-events';

		if (isset ($settings['archive'])) {
			$archive = $settings['archive'];
		}

		// Register the "dojour_event" custom post type
		register_post_type ('dojour_event', [
			'label' => _('Dojour Events'),
			'labels' => [
				'name' => _('Dojour Events'),
				'singular_name' => _('Dojour Event')
			],
			'description' => 'List of events published on Dojour!',
			'public' => true,
			'show_ui' => true,
			'has_archive' => true,
			'rewrite' => array('slug' => $archive),
			'supports' => array('title', 'thumbnail')
		]);

		flush_rewrite_rules ();
	}

	/**
	 * ======================================
	 * Plugin Lifecycle Hook Code Starts Here
	 * ======================================
	 */

	/**
	 * Activation Hook Callback. In here we'll perform actions needed the first
	 * time the plugin gets installed/activated such as registering custom
	 * values on the database.
	 *
	 * @return void
	 */
	public static function activate () {
		// Save a dojour settings object on the database so we can save up any
		// custom info such as the slug of the events archive.
		add_option ('dojour_settings', [
			'archive' => 'dojour-events'
		]);
	}

	/**
	 * Deactivation Hook Callback.
	 *
	 * We'll flush the rewrite rules so we stop showing the event archive and
	 * individual events when their URLs get accessed.
	 *
	 * @return void
	 */
	public static function deactivate () {
		flush_rewrite_rules ();
	}

	/**
	 * Uninstall Hook Callback.
	 *
	 * Will delete the settings option, unregister the custom
	 * post type and flush the rewrite rules so that the archive slug gets invalidated.
	 *
	 * @return void
	 */
	public static function uninstall () {
		delete_option ('dojour_settings');
		unregister_post_type ('dojour_event');
		flush_rewrite_rules ();
	}

	/**
	 * ======================================
	 * API Endpoints Code Starts Here
	 * ======================================
	 */

	public static function setup_custom_endpoints () {
		register_rest_route (self::$api_namespace, '/status', array(
			'methods' => 'GET',
			'callback' => array ('Dojour', 'status'),
			'permission_callback' => array ('Dojour', 'authorize_request')
		));

		register_rest_route (self::$api_namespace, '/settings', array(
			'methods' => 'POST',
			'callback' => array ('Dojour', 'settings'),
			'permission_callback' => array ('Dojour', 'authorize_request')
		));

		register_rest_route (self::$api_namespace, '/event', array(
			'methods' => 'POST',
			'callback' => array ('Dojour', 'create_event'),
			'permission_callback' => array ('Dojour', 'authorize_request')
		));

		register_rest_route (self::$api_namespace, '/event', array(
			'methods' => 'PUT',
			'callback' => array ('Dojour', 'update_event'),
			'permission_callback' => array ('Dojour', 'authorize_request')
		));

		register_rest_route (self::$api_namespace, '/event', array(
			'methods' => 'DELETE',
			'callback' => array ('Dojour', 'delete_event'),
			'permission_callback' => array ('Dojour', 'authorize_request')
		));
	}

	/**
	 * Permission Callback.
	 *
	 * In here we'll determine if a request made to the API has the permissions
	 * to do so by checking its authorization and matching it to the application
	 * passwords available on the site.
	 *
	 * @return void
	 */
	public static function authorize_request () {
		// Get HTTP request headers
		$auth = apache_request_headers();

		$authorization = $auth['Authorization'];

		$user = Application_Passwords::authenticate ($authorization, $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

		if ($user instanceof WP_User) {
			return true;
		}

		return false;
	}

	/**
	 * The Status endpoint will allow users customize their settings from the
	 * Dojour site
	 *
	 * @param HTTPRequest $request
	 *
	 * @return Array
	 */
	public static function settings ($request) {
		$params = $request -> get_json_params ();

		update_option ('dojour_settings', $params);


		unregister_post_type ('dojour_event');
		self::setup_post_type ();

		return [
			'success' => true
		];
	}

	public static function status ($request) {
		return [
			'success' => true
		];
	}

	/**
	 * ======================================
	 * Event CRUD Code Starts Here
	 * ======================================
	 */

	public static function create_event ($request) {
		$params = $request -> get_json_params ();

		$post_id = null;

		if (isset ($params['id'])) {
			$post_id = self::find_post ($params['id']);
		}

		if ($post_id === null) {
			$post = [
				'post_title' => $params['title'],
				'post_content' => $params['description'],
				'post_type' => 'dojour_event',
				'post_status' => 'publish',
				'comment_status' => 'closed'
			];

			$id = wp_insert_post (sanitize_post ($post, 'db'));

			self::fetch_image_for_post ($params['photo']['file'], $id);

			add_post_meta ($id, 'remote_id', $params['id'], true);
			add_post_meta ($id, 'remote_url', $params['absolute_url'], true);

			return [
				'id' => $id
			];
		} else {
			return self::update_event ($request);
		}
	}

	public static function update_event ($request) {
		$params = $request -> get_json_params ();

		$post_id = self::find_post ($params['id']);

		if ($post_id !== null) {
			$post = [
				'ID' => $post_id,
				'post_title' => $params['title'],
				'post_content' => $params['description']
			];

			wp_update_post ($post);

			update_post_meta ($post_id, 'remote_url', $params['absolute_url']);

			return [
				'id' => $post_id
			];
		} else {
			return self::create_event ($request);
		}
	}

	public static function delete_event ($request) {
		$params = $request -> get_json_params ();

		$post_id = self::find_post ($params['id']);

		if ($post_id !== null) {
			wp_delete_attachment ($post_id, true);
			wp_delete_post ($post_id, true);
		}
	}

	private function __construct () {
        // Do other stuff here
    }
}

register_activation_hook (__FILE__, array ('Dojour', 'activate'));
register_deactivation_hook (__FILE__, array ('Dojour', 'deactivate'));
register_uninstall_hook (__FILE__, array ('Dojour', 'uninstall'));

add_action ('init', array ('Dojour', 'setup_post_type'));
add_action ('init', array ('Dojour', 'check_dependencies'));
add_action ('rest_api_init', array ('Dojour', 'setup_custom_endpoints'));

?>