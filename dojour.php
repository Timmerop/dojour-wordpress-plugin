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

// Exit if accessed directly.
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

	public static function activate () {
		add_option ('dojour_settings', [
			'username' => ''
		]);
	}

	public function deactivate () {
		flush_rewrite_rules ();
	}

	public function uninstall () {
		delete_option ('dojour_settings');
		unregister_post_type ('dojour_event');
		flush_rewrite_rules ();
	}

	public static function setup_custom_endpoints () {
		register_rest_route(self::$api_namespace, '/event', array(
			'methods' => 'POST',
			'callback' => array ('Dojour', 'create_event'),
		));

		register_rest_route('dojour/v1', '/event', array(
			'methods' => 'PUT',
			'callback' => array ('Dojour', 'update_event'),
		));

		register_rest_route('dojour/v1', '/event', array(
			'methods' => 'DELETE',
			'callback' => array ('Dojour', 'delete_event'),
		));
	}


	public static function setup_post_type () {
		// register the "event" custom post type
		register_post_type ('dojour_event', [
			'label' => _('Dojour Events'),
			'labels' => [
				'name' => _('Dojour Events'),
				'singular_name' => _('Dojour Event')
			],
			'description' => 'A dopjour evet',
			'public' => true,
			'show_ui' => true,
			'has_archive' => true,
			'rewrite' => array('slug' => 'dojour-events'),
			'supports' => array('title', 'editor', 'thumbnail')
		] );

		flush_rewrite_rules ();
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

		return $id;
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
	}

	public static function delete_event ($request) {
		$params = $request -> get_json_params ();
	}

	private function __construct () {
        // do other stuff here
    }


}

register_activation_hook (__FILE__, array ('Dojour', 'activate'));
register_deactivation_hook (__FILE__, array ('Dojour', 'deactivate'));
register_uninstall_hook (__FILE__, array ('Dojour', 'uninstall'));

add_action ('rest_api_init', array ('Dojour', 'setup_custom_endpoints'));
add_action ('init', array ('Dojour', 'setup_post_type'));
?>