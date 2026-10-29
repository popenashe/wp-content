<?php
/**
 * Plugin Name: Ministry Content
 * Description: News, events, departments, documents, and site settings for the Ministry of Mines portal.
 * Version: 1.0.1
 * Author: Ministry of Mines and Mining Development
 * Text Domain: ministry-content
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MINISTRY_CONTENT_FILE', __FILE__ );
define( 'MINISTRY_CONTENT_DIR', plugin_dir_path( __FILE__ ) );
define( 'MINISTRY_CONTENT_URL', plugin_dir_url( __FILE__ ) );

require MINISTRY_CONTENT_DIR . 'inc/defaults.php';
require MINISTRY_CONTENT_DIR . 'inc/helpers.php';
require MINISTRY_CONTENT_DIR . 'inc/post-types.php';
require MINISTRY_CONTENT_DIR . 'inc/meta.php';
require MINISTRY_CONTENT_DIR . 'inc/albums.php';
require MINISTRY_CONTENT_DIR . 'inc/settings-admin.php';
require MINISTRY_CONTENT_DIR . 'inc/nav.php';
require MINISTRY_CONTENT_DIR . 'inc/seed.php';
require MINISTRY_CONTENT_DIR . 'inc/desk.php';
require MINISTRY_CONTENT_DIR . 'inc/design-sync.php';
require MINISTRY_CONTENT_DIR . 'inc/content-import.php';
require MINISTRY_CONTENT_DIR . 'inc/public-bind.php';
require MINISTRY_CONTENT_DIR . 'inc/public-chrome.php';
require MINISTRY_CONTENT_DIR . 'inc/bind.php';

register_activation_hook( __FILE__, 'ministry_content_activate' );

/**
 * Register types, then seed once and flush permalinks.
 */
function ministry_content_activate() {
	ministry_register_post_types();
	ministry_register_rewrites();
	ministry_seed();
	if ( get_option( 'permalink_structure' ) !== '/%postname%/' ) {
		update_option( 'permalink_structure', '/%postname%/' );
		flush_rewrite_rules();
	}
}

add_action( 'init', 'ministry_register_post_types' );
add_action( 'init', 'ministry_register_rewrites' );

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'mom_cadastre';
		return $vars;
	}
);

add_action(
	'template_redirect',
	function () {
		if ( ! get_query_var( 'mom_cadastre' ) ) {
			return;
		}
		$settings = ministry_settings();
		$url      = isset( $settings['cadastrePortalUrl'] ) ? (string) $settings['cadastrePortalUrl'] : '';
		if ( $url && wp_http_validate_url( $url ) ) {
			wp_redirect( $url, 302 );
			exit;
		}
		wp_safe_redirect( home_url( '/' ), 302 );
		exit;
	}
);

add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}
		$query->set(
			'post_type',
			array( 'page', 'mom_news', 'mom_event', 'mom_document', 'mom_department', 'mom_opportunity' )
		);
	}
);
