<?php
/**
 * Plugin Name: Ministry Engage
 * Description: Contact enquiries, event RSVP, and the FAQ chat for the Ministry of Mines portal.
 * Version: 1.0.0
 * Author: Ministry of Mines and Mining Development
 * Requires Plugins: ministry-content
 * Text Domain: ministry-engage
 *
 * @package MinistryEngage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MINISTRY_ENGAGE_FILE', __FILE__ );

require __DIR__ . '/inc/enquiries.php';
require __DIR__ . '/inc/rsvp.php';
require __DIR__ . '/inc/chat.php';

register_activation_hook( __FILE__, 'ministry_engage_activate' );

/**
 * Seed one inbox item so the enquiries screen is not empty.
 */
function ministry_engage_activate() {
	ministry_register_enquiry_type();
	if ( get_option( 'mom_enquiry_seeded' ) ) {
		return;
	}
	$existing = get_posts(
		array(
			'post_type'      => 'mom_enquiry',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( $existing ) {
		update_option( 'mom_enquiry_seeded', 1 );
		return;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'mom_enquiry',
			'post_status'  => 'publish',
			'post_title'   => 'Lithium offtake discussion',
			'post_content' => 'Please share the current guidance for lithium offtake and provincial contacts.',
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_mom_name', 'Amina Patel' );
		update_post_meta( $id, '_mom_email', 'amina.demo@example.com' );
		update_post_meta( $id, '_mom_phone', '+27 11 000 0000' );
		update_post_meta( $id, '_mom_type', 'investor' );
		update_post_meta( $id, '_mom_status', 'open' );
	}
	update_option( 'mom_enquiry_seeded', 1 );
}

add_action( 'init', 'ministry_register_enquiry_type' );

add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) || function_exists( 'ministry_settings' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>Ministry Engage needs the Ministry Content plugin to be active.</p></div>';
	}
);

/**
 * Data the theme script uses for chat and RSVP.
 *
 * @return array<string, mixed>
 */
function ministry_engage_script_data() {
	return array(
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'chatNonce' => wp_create_nonce( 'ministry_chat' ),
		'rsvpNonce' => wp_create_nonce( 'ministry_rsvp' ),
		'greeting'  => '',
		'fallback'  => 'Please use the Contact page to reach the relevant department.',
		'chips'     => array(),
	);
}
