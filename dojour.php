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

// Exit if this file is beint tried to be accessed directly.
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

	public static function check_dependencies () {
		if (!class_exists ('Application_Passwords')) {
			self::show_message ('error', '<a href="https://wordpress.org/plugins/application-passwords/" target="_blank" rel="noopener noreferrer">Application Passwords Plugin</a> is required for the <a href="https://dojour.us/" target="_blank" rel="noopener noreferrer">Dojour</a> plugin to work. Please make sure it has been installed and that it is activated.');
		}
	}

	public static function show_message ($level = '', $message = '') {
		echo "<div class='notice notice-$level is-dismissible'><p>$message</p></div>";
	}

	public static function activate () {
		add_option ('dojour_settings', [
			'username' => ''
		]);
	}

	public static function deactivate () {
		flush_rewrite_rules ();
	}

	public static function uninstall () {
		delete_option ('dojour_settings');
		unregister_post_type ('dojour_event');
		flush_rewrite_rules ();
	}

	public static function setup_custom_endpoints () {
		register_rest_route (self::$api_namespace, '/status', array(
			'methods' => 'GET',
			'callback' => array ('Dojour', 'status'),
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

	public static function authorize_request () {
		// Get HTTP request headers
		$auth = apache_request_headers();

		$authorization = $auth['Authorization'];

		$user = Application_Passwords::authenticate ($authorization, $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

		if ($user instanceof WP_User) {
			// get the id use return $user->ID;
			return true;
		} else {
			return false;
		}
	}


	public static function setup_post_type () {
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
			'rewrite' => array('slug' => 'dojour-events'),
			'supports' => array('title', 'thumbnail')
		]);

		flush_rewrite_rules ();
	}

	public static function status ($request) {
		return [
			'success' => true
		];
	}

	/**
	 * ================================
	 * Event Code Starts Here
	 * ================================
	 */

	public static function create_event ($request) {
		$params = $request -> get_json_params ();
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
	}

	public static function find_post ($remote_url) {
		$posts = get_posts ([
			'numberposts' => 1,
			'post_type'  => 'dojour_event',
			'meta_key' => 'remote_id',
			'meta_value' => $remote_url
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

	public static function fetch_image_for_post ($url, $post_id) {
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		$attachment_id = media_sideload_image ($url, $post_id, null, 'id');

		set_post_thumbnail ($post_id, $attachment_id);
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
        // do other stuff here
    }
}

register_activation_hook (__FILE__, array ('Dojour', 'activate'));
register_deactivation_hook (__FILE__, array ('Dojour', 'deactivate'));
register_uninstall_hook (__FILE__, array ('Dojour', 'uninstall'));

add_action ('init', array ('Dojour', 'setup_post_type'));
add_action ('init', array ('Dojour', 'check_dependencies'));
add_action ('rest_api_init', array ('Dojour', 'setup_custom_endpoints'));

?>