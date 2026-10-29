<?php
/**
 * Event RSVP counter.
 *
 * @package MinistryEngage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_ministry_rsvp', 'ministry_handle_rsvp' );
add_action( 'wp_ajax_nopriv_ministry_rsvp', 'ministry_handle_rsvp' );

/**
 * Increment the RSVP count for one event.
 */
function ministry_handle_rsvp() {
	check_ajax_referer( 'ministry_rsvp', 'nonce' );
	$post_id = isset( $_POST['eventId'] ) ? absint( $_POST['eventId'] ) : 0;
	$post    = $post_id ? get_post( $post_id ) : null;
	if ( ! $post || 'mom_event' !== $post->post_type || 'publish' !== $post->post_status ) {
		wp_send_json_error( array( 'message' => 'Event not found.' ), 404 );
	}
	$key = 'mom_rsvp_' . $post_id;
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$hit = 'mom_rsvp_' . md5( $ip . '|' . $post_id );
	if ( isset( $_COOKIE[ $key ] ) || get_transient( $hit ) ) {
		wp_send_json_success(
			array(
				'count' => (int) get_post_meta( $post_id, '_mom_rsvp_count', true ),
				'done'  => true,
			)
		);
	}
	$count = (int) get_post_meta( $post_id, '_mom_rsvp_count', true );
	$count++;
	update_post_meta( $post_id, '_mom_rsvp_count', (string) $count );
	set_transient( $hit, '1', DAY_IN_SECONDS );
	setcookie( $key, '1', time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	wp_send_json_success(
		array(
			'count' => $count,
			'done'  => true,
		)
	);
}
