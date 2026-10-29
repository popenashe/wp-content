<?php
/**
 * Private enquiry records and the contact form handler.
 *
 * @package MinistryEngage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enquiries are visible in wp-admin only.
 */
function ministry_register_enquiry_type() {
	register_post_type(
		'mom_enquiry',
		array(
			'labels'          => array(
				'name'          => 'Enquiries',
				'singular_name' => 'Enquiry',
				'edit_item'     => 'View enquiry',
				'menu_name'     => 'Enquiries',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-email',
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'supports'        => array( 'title', 'editor' ),
		)
	);
}

add_action(
	'admin_post_nopriv_ministry_enquiry',
	'ministry_handle_enquiry'
);
add_action(
	'admin_post_ministry_enquiry',
	'ministry_handle_enquiry'
);

/**
 * Save a contact form submission and return to the contact page.
 */
function ministry_handle_enquiry() {
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/contact/' );
	$redirect = remove_query_arg( array( 'enquiry', 'enquiry_error' ), $redirect );

	if ( ! isset( $_POST['ministry_enquiry_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ministry_enquiry_nonce'] ) ), 'ministry_enquiry' ) ) {
		wp_safe_redirect( add_query_arg( 'enquiry_error', '1', $redirect ) );
		exit;
	}
	if ( ! empty( $_POST['company_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'enquiry', 'sent', $redirect ) );
		exit;
	}

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'general';
	$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	$office  = isset( $_POST['office'] ) ? sanitize_text_field( wp_unslash( $_POST['office'] ) ) : '';
	$labels  = array(
		'general'    => 'General enquiry',
		'investor'   => 'Investor enquiry',
		'minister'   => 'Ask the Minister',
		'complaint'  => 'Complaint or grievance',
		'compliment' => 'Compliment or suggestion',
	);
	if ( ! isset( $labels[ $type ] ) ) {
		$type = 'general';
	}
	if ( '' === $subject && '' !== $name ) {
		$subject = $labels[ $type ] . ' from ' . $name;
	}
	if ( 'Head office or province' === $office ) {
		$office = '';
	}

	if ( '' === $name || '' === $email || '' === $subject || '' === $message || ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'enquiry_error', '1', $redirect ) . '#enquiry' );
		exit;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => 'mom_enquiry',
			'post_status'  => 'publish',
			'post_title'   => $subject,
			'post_content' => $message,
		)
	);
	if ( ! $id || is_wp_error( $id ) ) {
		wp_safe_redirect( add_query_arg( 'enquiry_error', '1', $redirect ) );
		exit;
	}
	update_post_meta( $id, '_mom_name', $name );
	update_post_meta( $id, '_mom_email', $email );
	update_post_meta( $id, '_mom_phone', $phone );
	update_post_meta( $id, '_mom_type', $type );
	update_post_meta( $id, '_mom_office', $office );
	update_post_meta( $id, '_mom_status', 'open' );

	wp_safe_redirect( add_query_arg( 'enquiry', 'sent', $redirect ) . '#enquiry' );
	exit;
}

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'mom_enquiry_details', 'Sender', 'ministry_enquiry_meta_box', 'mom_enquiry', 'side', 'high' );
	}
);

/**
 * @param WP_Post $post Enquiry.
 */
function ministry_enquiry_meta_box( $post ) {
	wp_nonce_field( 'ministry_enquiry_status', 'ministry_enquiry_status_nonce' );
	$status = (string) get_post_meta( $post->ID, '_mom_status', true );
	echo '<p><strong>Name</strong><br>' . esc_html( (string) get_post_meta( $post->ID, '_mom_name', true ) ) . '</p>';
	echo '<p><strong>Email</strong><br>' . esc_html( (string) get_post_meta( $post->ID, '_mom_email', true ) ) . '</p>';
	echo '<p><strong>Phone</strong><br>' . esc_html( (string) get_post_meta( $post->ID, '_mom_phone', true ) ) . '</p>';
	echo '<p><strong>Type</strong><br>' . esc_html( (string) get_post_meta( $post->ID, '_mom_type', true ) ) . '</p>';
	echo '<p><strong>Office</strong><br>' . esc_html( (string) get_post_meta( $post->ID, '_mom_office', true ) ) . '</p>';
	echo '<p><label for="mom_status"><strong>Status</strong></label><br>';
	echo '<select id="mom_status" name="mom_status">';
	foreach ( array( 'open', 'closed' ) as $option ) {
		echo '<option value="' . esc_attr( $option ) . '" ' . selected( $status ? $status : 'open', $option, false ) . '>' . esc_html( ucfirst( $option ) ) . '</option>';
	}
	echo '</select></p>';
}

add_action(
	'save_post_mom_enquiry',
	function ( $post_id ) {
		if ( ! isset( $_POST['ministry_enquiry_status_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ministry_enquiry_status_nonce'] ) ), 'ministry_enquiry_status' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$status = isset( $_POST['mom_status'] ) ? sanitize_key( wp_unslash( $_POST['mom_status'] ) ) : 'open';
		update_post_meta( $post_id, '_mom_status', 'closed' === $status ? 'closed' : 'open' );
	}
);

add_filter(
	'manage_mom_enquiry_posts_columns',
	function ( $columns ) {
		$columns['mom_from']   = 'From';
		$columns['mom_type']   = 'Type';
		$columns['mom_status'] = 'Status';
		return $columns;
	}
);

add_action(
	'manage_mom_enquiry_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'mom_from' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_mom_name', true ) );
		}
		if ( 'mom_type' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_mom_type', true ) );
		}
		if ( 'mom_status' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_mom_status', true ) );
		}
	},
	10,
	2
);
