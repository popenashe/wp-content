<?php
/**
 * Keyword FAQ matcher for the public chat widget.
 *
 * @package MinistryEngage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_ministry_chat', 'ministry_handle_chat' );
add_action( 'wp_ajax_nopriv_ministry_chat', 'ministry_handle_chat' );

/**
 * Reply from published FAQs. Matching stays on the server.
 */
function ministry_handle_chat() {
	check_ajax_referer( 'ministry_chat', 'nonce' );
	$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
	$query = trim( $query );
	if ( '' === $query || strlen( $query ) > 300 ) {
		wp_send_json_error( array( 'message' => 'Enter a short question.' ), 400 );
	}
	$fallback = 'Please use the Contact page to reach the relevant department.';
	$faqs     = function_exists( 'ministry_faqs' ) ? ministry_faqs() : array();
	$lower    = strtolower( $query );
	foreach ( $faqs as $faq ) {
		$keywords = strtolower( (string) get_post_meta( $faq->ID, '_mom_keywords', true ) );
		$question = strtolower( $faq->post_title );
		$hit      = false;
		foreach ( preg_split( '/\s+/', $keywords ) as $word ) {
			if ( $word && str_contains( $lower, $word ) ) {
				$hit = true;
				break;
			}
		}
		if ( ! $hit && strlen( $question ) >= 12 && str_contains( $lower, substr( $question, 0, 12 ) ) ) {
			$hit = true;
		}
		if ( $hit ) {
			$dept = (string) get_post_meta( $faq->ID, '_mom_department', true );
			$text = wp_strip_all_tags( $faq->post_content );
			if ( $dept ) {
				$text .= "\n\nEscalation route: " . $dept;
			}
			wp_send_json_success( array( 'reply' => $text ) );
		}
	}
	wp_send_json_success( array( 'reply' => $fallback ) );
}
