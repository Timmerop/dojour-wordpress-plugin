<?php
/**
 * Plugin Name:       Dojour
 * Plugin URI:        https://dojour.us/
 * Description:       A way for businesses to link their Dojour account to their WordPress website.
 * Version:           0.2.0
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

	private static $plugin_slug = 'dojour';

	private static $instance = null;

	private static $api_namespace = 'dojour/v1';

	protected $templates;

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
		$posts = self::find_posts ($remote_id);

		if ($posts !== null) {
			$post = $posts[0];

			if ($post instanceof WP_Post) {
				return $post -> ID;
			}

			return $post;
		}

		return null;
	}

	public static function find_posts ($remote_id) {
		$posts = get_posts ([
			'numberposts' => 1,
			'post_type'  => 'dojour_event',
			'meta_key' => 'remote_id',
			'meta_value' => $remote_id
		]);

		if (count ($posts) > 0) {
			return $posts;
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
		require_once (ABSPATH . 'wp-admin/includes/media.php');
		require_once (ABSPATH . 'wp-admin/includes/file.php');
		require_once (ABSPATH . 'wp-admin/includes/image.php');

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
			'menu_icon' => 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjxzdmcKICAgeG1sbnM6ZGM9Imh0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvIgogICB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIgogICB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiCiAgIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICAgeG1sbnM6c29kaXBvZGk9Imh0dHA6Ly9zb2RpcG9kaS5zb3VyY2Vmb3JnZS5uZXQvRFREL3NvZGlwb2RpLTAuZHRkIgogICB4bWxuczppbmtzY2FwZT0iaHR0cDovL3d3dy5pbmtzY2FwZS5vcmcvbmFtZXNwYWNlcy9pbmtzY2FwZSIKICAgd2lkdGg9IjIwIgogICBoZWlnaHQ9IjIwIgogICB2aWV3Qm94PSIwIDAgMjAgMjAiCiAgIGZpbGw9Im5vbmUiCiAgIHZlcnNpb249IjEuMSIKICAgaWQ9InN2ZzgiCiAgIHNvZGlwb2RpOmRvY25hbWU9IjIweDIwIFNWRy5zdmciCiAgIGlua3NjYXBlOnZlcnNpb249IjAuOTIuNCAodW5rbm93bikiPgogIDxtZXRhZGF0YQogICAgIGlkPSJtZXRhZGF0YTE0Ij4KICAgIDxyZGY6UkRGPgogICAgICA8Y2M6V29yawogICAgICAgICByZGY6YWJvdXQ9IiI+CiAgICAgICAgPGRjOmZvcm1hdD5pbWFnZS9zdmcreG1sPC9kYzpmb3JtYXQ+CiAgICAgICAgPGRjOnR5cGUKICAgICAgICAgICByZGY6cmVzb3VyY2U9Imh0dHA6Ly9wdXJsLm9yZy9kYy9kY21pdHlwZS9TdGlsbEltYWdlIiAvPgogICAgICA8L2NjOldvcms+CiAgICA8L3JkZjpSREY+CiAgPC9tZXRhZGF0YT4KICA8ZGVmcwogICAgIGlkPSJkZWZzMTIiIC8+CiAgPHNvZGlwb2RpOm5hbWVkdmlldwogICAgIHBhZ2Vjb2xvcj0iI2ZmZmZmZiIKICAgICBib3JkZXJjb2xvcj0iIzY2NjY2NiIKICAgICBib3JkZXJvcGFjaXR5PSIxIgogICAgIG9iamVjdHRvbGVyYW5jZT0iMTAiCiAgICAgZ3JpZHRvbGVyYW5jZT0iMTAiCiAgICAgZ3VpZGV0b2xlcmFuY2U9IjEwIgogICAgIGlua3NjYXBlOnBhZ2VvcGFjaXR5PSIwIgogICAgIGlua3NjYXBlOnBhZ2VzaGFkb3c9IjIiCiAgICAgaW5rc2NhcGU6d2luZG93LXdpZHRoPSIxOTIwIgogICAgIGlua3NjYXBlOndpbmRvdy1oZWlnaHQ9IjEwMTciCiAgICAgaWQ9Im5hbWVkdmlldzEwIgogICAgIHNob3dncmlkPSJmYWxzZSIKICAgICBpbmtzY2FwZTp6b29tPSI0Ny4yIgogICAgIGlua3NjYXBlOmN4PSIxMy45MDYyMDgiCiAgICAgaW5rc2NhcGU6Y3k9IjEwLjc2NjkzMyIKICAgICBpbmtzY2FwZTp3aW5kb3cteD0iMCIKICAgICBpbmtzY2FwZTp3aW5kb3cteT0iMzAiCiAgICAgaW5rc2NhcGU6d2luZG93LW1heGltaXplZD0iMSIKICAgICBpbmtzY2FwZTpjdXJyZW50LWxheWVyPSJzdmc4IiAvPgogIDxwYXRoCiAgICAgZD0iTSAxMCAxIEMgNS4wMjk0NCAxIDEgNS4wMjk0NCAxIDEwIEMgMSAxNC45NzA2IDUuMDI5NDQgMTkgMTAgMTkgQyAxNC45NzA2IDE5IDE5IDE0Ljk3MDYgMTkgMTAgQyAxOSA1LjAyOTQ0IDE0Ljk3MDYgMSAxMCAxIHogTSA5LjIyMDcwMzEgMy41ODIwMzEyIEMgOS40NTA0NzMxIDMuNTk3MzExMyA5LjY1Mzg1ODEgMy42ODI2NDc1IDkuODMwMDc4MSAzLjgzNTkzNzUgQyAxMC4wMDYyODggMy45ODkxMjc1IDEwLjA2OTUzNiA0LjE4ODMyMzcgMTAuMDIzNDM4IDQuNDMzNTkzOCBDIDkuOTkyODM3IDQuNjE3NDMzNyA5Ljk0ODAwODcgNC45NjUxNzU2IDkuODg2NzE4OCA1LjQ3ODUxNTYgQyA5LjgyNTY0ODcgNS45OTE3NDU2IDkuNzYwMjE2NCA2LjU4NTQ5NTYgOS42OTE0MDYyIDcuMjU5NzY1NiBDIDkuNjIyMjc2MyA3LjkzNDAzNTYgOS41NjU1NTEzIDguNjMxMzUyNSA5LjUxOTUzMTIgOS4zNTE1NjI1IEMgOS40NzM1MTEzIDEwLjA3MTg5MyA5LjQ0OTIxODggMTAuNzM4NzYyIDkuNDQ5MjE4OCAxMS4zNTE1NjIgQyA5LjQ0OTIxODggMTEuOTY0NjY0IDkuNDc3NDkgMTIuNDY2NzIyIDkuNTMxMjUgMTIuODU3NDIyIEMgOS41ODQ4IDEzLjI0ODIyMiA5LjY4NjYyMzcgMTMuNDQzMzU5IDkuODM5ODQzOCAxMy40NDMzNTkgQyA5Ljk3NzY4MzggMTMuNDQzMzU5IDEwLjEyMDAyNSAxMy4zMTMxMzQgMTAuMjY1NjI1IDEzLjA1MjczNCBDIDEwLjQxMDkyNSAxMi43OTIyMzQgMTAuNTUzNDA2IDEyLjQ4OTMzMSAxMC42OTE0MDYgMTIuMTQ0NTMxIEMgMTAuODI5MjA2IDExLjc5OTczMSAxMC45NDczNzUgMTEuNDYyMzEzIDExLjA0Njg3NSAxMS4xMzI4MTIgQyAxMS4xNDY0NzUgMTAuODAzMzE0IDExLjIxMTM4NiAxMC41NjMzNTYgMTEuMjQyMTg4IDEwLjQxMDE1NiBDIDExLjI1MzI4NyAxMC4zNzMxMDYgMTEuMjcwOTIgMTAuMzQ3NjM4IDExLjI4NTE1NiAxMC4zMTY0MDYgQyAxMS4zNTI4NiAxMC4wNTE4NzUgMTEuNDM4Nzc0IDkuNzk4MzQxNiAxMS41NTQ2ODggOS41NjA1NDY5IEMgMTEuNzYxMTg3IDkuMTM3MDA2OSAxMi4wMzI3NDEgOC43ODQ4NzI1IDEyLjM2OTE0MSA4LjUwNzgxMjUgQyAxMi43MDU0NDEgOC4yMzA1MzI1IDEzLjEwMzggOC4xMDc5NzE5IDEzLjU2MjUgOC4xMzg2NzE5IEMgMTQuMDk3NyA4LjE2OTU5MTkgMTQuNTMyOTQxIDguMzMyMzcgMTQuODY5MTQxIDguNjI1IEMgMTUuMjA1NjQxIDguOTE3NjMgMTUuNDUwNzE2IDkuMjc3NzY0NCAxNS42MDM1MTYgOS43MDg5ODQ0IEMgMTUuNzU2NDE2IDEwLjE0MDE5NCAxNS44MjE2MjggMTAuNTk4OTg0IDE1Ljc5ODgyOCAxMS4wODM5ODQgQyAxNS43NzU5MjggMTEuNTY5MTg0IDE1LjY2MzY0NCAxMi4wMTk5NDcgMTUuNDY0ODQ0IDEyLjQzNTU0NyBDIDE1LjI2NTg0NCAxMi44NTE0NDcgMTQuOTg4MTA2IDEzLjE5NzQwOSAxNC42Mjg5MDYgMTMuNDc0NjA5IEMgMTQuMjY5NzA2IDEzLjc1MTcwOSAxMy44MzY1MzEgMTMuODkwNjI1IDEzLjMzMjAzMSAxMy44OTA2MjUgQyAxMi43NjYzMzEgMTMuODkwNjI1IDEyLjMxNjc2OSAxMy43NDM2NzIgMTEuOTgwNDY5IDEzLjQ1MTE3MiBDIDExLjc0MTYyOCAxMy4yNDM0MjkgMTEuNTcyMjY4IDEyLjk4ODUxMSAxMS40Mzc1IDEyLjcxMDkzOCBDIDExLjQxNjA5MyAxMi43NTk5MDIgMTEuNDAxNTA4IDEyLjgwODIwNSAxMS4zNzg5MDYgMTIuODU3NDIyIEMgMTEuMTY0NTA2IDEzLjMyNDkyMiAxMC44ODU2MTYgMTMuNzQxNzc1IDEwLjU0MTAxNiAxNC4xMDkzNzUgQyAxMC4xOTY0MTYgMTQuNDc3MTc1IDkuNzg3MzEgMTQuNjYyMTA5IDkuMzEyNSAxNC42NjIxMDkgQyA4Ljk2MDM4IDE0LjY2MjEwOSA4LjY4NzQ3MzcgMTQuNTE5NzI4IDguNDk2MDkzOCAxNC4yMzYzMjggQyA4LjMwNDYwMzggMTMuOTUyODI4IDguMTU2MTI4MSAxMy41ODA3OTQgOC4wNDg4MjgxIDEzLjEyMTA5NCBDIDcuOTI2NDY4MSAxMy40MTIyOTQgNy43NjE1NDc1IDEzLjY3MjQ0NCA3LjU1NDY4NzUgMTMuOTAyMzQ0IEMgNy4zNDgwMjc1IDE0LjEzMjI0NCA3LjA5MTQ3NjIgMTQuMzAwOTAzIDYuNzg1MTU2MiAxNC40MDgyMDMgQyA2LjQ0ODI5NjQgMTQuNTMwODAzIDYuMTU3MTM5NCAxNC41NDcxNzggNS45MTIxMDk0IDE0LjQ1NTA3OCBDIDUuNDY3ODM5NCAxNC4zNDc5NzggNS4xMTU4ODg3IDE0LjEwNTg2OSA0Ljg1NTQ2ODggMTMuNzMwNDY5IEMgNC41OTUwNTg3IDEzLjM1NDk2OSA0LjQxNDEyMzEgMTIuOTE3OTIyIDQuMzE0NDUzMSAxMi40MTk5MjIgQyA0LjIxNDg5MzEgMTEuOTIxODIyIDQuMjA0NjkgMTEuNDA1MzQxIDQuMjgxMjUgMTAuODY5MTQxIEMgNC4zNTc4IDEwLjMzMjc0MSA0LjUxNDU1MzEgOS44NTA5ODUgNC43NTE5NTMxIDkuNDIxODc1IEMgNC45ODkzNTMxIDguOTkyODc1IDUuMzA2ODI4MSA4LjY1OTM5NSA1LjcwNTA3ODEgOC40MjE4NzUgQyA2LjEwMzIxODEgOC4xODQzNDUgNi41ODU2MTM4IDguMTExMTU1IDcuMTUyMzQzOCA4LjIwMzEyNSBDIDcuNDU4NTYzNyA4LjI0OTA2NSA3LjcyNzI2MTIgOC40MDk3MzY5IDcuOTU3MDMxMiA4LjY4NTU0NjkgQyA4LjAxODEwMTMgNy44MTIwNTY5IDguMDg3NjEyNSA2Ljk5ODkwNjkgOC4xNjQwNjI1IDYuMjQ4MDQ2OSBDIDguMjQwNzIyNSA1LjQ5NzA3NjkgOC4yNzkyOTY5IDQuOTMxODg4MSA4LjI3OTI5NjkgNC41NDg4MjgxIEMgOC4yNzkyOTY5IDQuMTgxMDI4MSA4LjM3Njk0MTkgMy45MjI5OTM3IDguNTc2MTcxOSAzLjc3NzM0MzggQyA4Ljc3NTMwMTkgMy42MzE2ODM3IDguOTkwOTMzMSAzLjU2NjY1MTQgOS4yMjA3MDMxIDMuNTgyMDMxMiB6IE0gMTMuNDkyMTg4IDguOTk0MTQwNiBDIDEzLjMzOTM4NiA4Ljk5NDE0MDYgMTMuMjEwNjE2IDkuMDg2NjI0NCAxMy4xMDM1MTYgOS4yNzE0ODQ0IEMgMTIuOTk2NTE2IDkuNDU2MjI0NCAxMi45MTY3ODEgOS42ODU3MjA2IDEyLjg2MzI4MSA5Ljk2Mjg5MDYgQyAxMi44MDk1ODEgMTAuMjQwMDYxIDEyLjc4MTI1IDEwLjU0NTk1MyAxMi43ODEyNSAxMC44NzY5NTMgQyAxMi43ODEyNSAxMS4yMDgwNTMgMTIuODExOTQ3IDExLjUxNjA4MSAxMi44NzMwNDcgMTEuODAwNzgxIEMgMTIuOTM0MjQ3IDEyLjA4NTc4MSAxMy4wMjYxMzcgMTIuMzI3MDQ0IDEzLjE0ODQzOCAxMi41MjczNDQgQyAxMy4yNzA5MzYgMTIuNzI3NDQ0IDEzLjQzMTk1OSAxMi44MzYyNjQgMTMuNjMwODU5IDEyLjg1MTU2MiBDIDEzLjc5OTA1OSAxMi44NjcwNjMgMTMuOTM1ODY5IDEyLjc4NjU3NSAxNC4wNDI5NjkgMTIuNjA5Mzc1IEMgMTQuMTQ5ODY5IDEyLjQzMjM3NSAxNC4yMjc1MzYgMTIuMjAwODE2IDE0LjI3MzQzOCAxMS45MTYwMTYgQyAxNC4zMTkyMzYgMTEuNjMxMTE2IDE0LjMzNzc3OCAxMS4zMTkxNjkgMTQuMzMwMDc4IDEwLjk4MDQ2OSBDIDE0LjMyMjQ3OCAxMC42NDE2NjkgMTQuMjgzNzQ0IDEwLjMyNTkwMyAxNC4yMTQ4NDQgMTAuMDMzMjAzIEMgMTQuMTQ2MDQ0IDkuNzQwNzAzMiAxNC4wNTE3ODYgOS40OTMxNzg4IDEzLjkyOTY4OCA5LjI5Mjk2ODggQyAxMy44MDcxODcgOS4wOTI3Njg2IDEzLjY2MDQ4OCA4Ljk5NDE0MDYgMTMuNDkyMTg4IDguOTk0MTQwNiB6IE0gNy4xOTkyMTg4IDkuMjE0ODQzOCBDIDYuOTU0MTg4NiA5LjE2ODkwMzggNi43MzQzNDg3IDkuMjQ0MTI5NCA2LjU0Mjk2ODggOS40NDMzNTk0IEMgNi4zNTE0Nzg3IDkuNjQyNDc5NCA2LjE5NDgzNTYgOS45MDA3NTM0IDYuMDcyMjY1NiAxMC4yMTQ4NDQgQyA1Ljk0OTgwNTYgMTAuNTI4OTQ0IDUuODYyMjQzOCAxMC44NzY3NjYgNS44MDg1OTM4IDExLjI1OTc2NiBDIDUuNzU0OTMzOCAxMS42NDI4NjYgNS43NDA2NzE5IDEyLjAwNjc2MyA1Ljc2MzY3MTkgMTIuMzUxNTYyIEMgNS43ODY2ODE5IDEyLjY5NjM2MyA1Ljg1MTc4NDQgMTIuOTg3MTA5IDUuOTU4OTg0NCAxMy4yMjQ2MDkgQyA2LjA2NjE4NDQgMTMuNDYyMTA5IDYuMjE4NzI4NyAxMy41OTYyNTMgNi40MTc5Njg4IDEzLjYyNjk1MyBDIDYuNjE3MDg4NyAxMy42NTc1NTMgNi44MDQzNDg3IDEzLjU2NjE2MyA2Ljk4MDQ2ODggMTMuMzUxNTYyIEMgNy4xNTY2ODg3IDEzLjEzNzA2NCA3LjMwOTM1MzEgMTIuODYwNTM3IDcuNDM5NDUzMSAxMi41MjM0MzggQyA3LjU2OTY1MzEgMTIuMTg2MzM4IDcuNjY1MjcyNSAxMS44MTIyMzggNy43MjY1NjI1IDExLjM5ODQzOCBDIDcuNzg3NzQyNSAxMC45ODQ2MzYgNy43OTYwMiAxMC41OTI0MDkgNy43NSAxMC4yMjQ2MDkgQyA3LjcxOTM2IDEwLjAxMDAwOSA3LjY2MjMxNSA5Ljc5NjUzMTMgNy41NzgxMjUgOS41ODIwMzEyIEMgNy40OTM4MzUgOS4zNjc1MzEzIDcuMzY3ODA4OCA5LjI0NTQ5MzcgNy4xOTkyMTg4IDkuMjE0ODQzOCB6ICIKICAgICBpZD0icGF0aDIiCiAgICAgc3R5bGU9ImZpbGw6IzMzMzMzMyIgLz4KPC9zdmc+Cg==',
			'has_archive' => true,
			'rewrite' => array('slug' => $archive),
			'supports' => array('title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes', 'post-formats')
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
			'archive' => 'dojour-events',
			'version' => 0
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

			if (isset ($params['location'])) {
				add_post_meta ($id, 'location_title', $params['location']['title'], true);
				add_post_meta ($id, 'location_address', $params['location']['address'], true);
			}

			set_post_format ($id, 'Dojour Event');

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
		$this -> templates = array ();

		add_action ('wp_enqueue_scripts', array ($this, 'setup_theme_assets'));
		add_action ('after_setup_theme', array ($this, 'setup_theme_templates'));

		// Add your templates to this array.
		$this -> templates = array(
			'templates/single-dojour_event.php' => 'Dojour Event',
		);
	}

	public function setup_theme_templates () {
		// Add a filter to the wp 4.7 version attributes metabox
		add_filter ('theme_dojour_event_templates', array ($this, 'add_new_template'));

		// Add a filter to the save post to inject out template into the page cache
		add_filter ('wp_insert_post_data', array( $this, 'register_project_templates'));

		// Add a filter to the template include to determine if the page has our
		// template assigned and return it's path
		add_filter ('template_include', array( $this, 'view_project_template'));
	}

	public function setup_theme_assets () {
		$theme = wp_get_theme ();

		if ($theme -> exists ()) {
			wp_enqueue_style ($theme -> get ('Name'), get_template_directory_uri () . '/style.css' );
			wp_enqueue_style ('child-style', plugins_url ('style.css', __FILE__ ), array ($theme -> get ('Name')), $theme -> get ('Version'));
		}
	}

	public function add_new_template( $posts_templates ) {
		$posts_templates = array_merge( $posts_templates, $this -> templates );
		return $posts_templates;
	}

	public function register_project_templates ($props) {
		// Create the key used for the themes cache
		$cache_key = 'page_templates-' . md5 (get_theme_root () . '/' . get_stylesheet ());

		// Retrieve the cache list.
		// If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme () -> get_page_templates ();

		if (empty ($templates)) {
			$templates = array ();
		}

		// New cache, therefore remove the old one
		wp_cache_delete ($cache_key , 'themes');

		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$templates = array_merge ($templates, $this -> templates);

		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		wp_cache_add ($cache_key, $templates, 'themes', 1800);

		return $props;
	}

	public function view_project_template( $template ) {
		// Get global post
		global $post;

		// Return template if post is empty
		if (!$post) {
			return $template;
		}

		$post_template = get_post_meta ($post -> ID, '_wp_page_template', true);

		// Return default template if we don't have a custom one defined
		if (!isset( $this -> templates[$post_template])) {
			return $template;
		}

		$file = plugin_dir_path (__FILE__) . $post_template;

		// Just to be safe, we check if the file exist first
		if (file_exists ($file)) {
			return $file;
		} else {
			echo $file;
		}

		// Return template
		return $template;
	}
}

register_activation_hook (__FILE__, array ('Dojour', 'activate'));
register_deactivation_hook (__FILE__, array ('Dojour', 'deactivate'));
register_uninstall_hook (__FILE__, array ('Dojour', 'uninstall'));

add_action ('plugins_loaded', array ('Dojour', 'get_instance'));

add_action ('init', array ('Dojour', 'setup_post_type'));
add_action ('init', array ('Dojour', 'check_dependencies'));
add_action ('rest_api_init', array ('Dojour', 'setup_custom_endpoints'));

?>