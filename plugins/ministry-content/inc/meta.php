<?php
/**
 * Meta boxes for ministry post types.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fields shown beside the editor.
 *
 * @return array<string, array<int, array<string, string>>>
 */
function ministry_meta_schema() {
	return array(
		'mom_event'       => array(
			array( 'key' => '_mom_location', 'label' => 'Location', 'type' => 'text' ),
			array( 'key' => '_mom_category', 'label' => 'Category', 'type' => 'text' ),
			array( 'key' => '_mom_starts_at', 'label' => 'Starts', 'type' => 'datetime' ),
			array( 'key' => '_mom_ends_at', 'label' => 'Ends', 'type' => 'datetime' ),
			array( 'key' => '_mom_rsvp_count', 'label' => 'RSVP count', 'type' => 'number' ),
		),
		'mom_document'    => array(
			array( 'key' => '_mom_category', 'label' => 'Category', 'type' => 'select', 'options' => 'policies,legislation,forms,reports,publications,guidelines' ),
			array( 'key' => '_mom_file_url', 'label' => 'File URL', 'type' => 'url' ),
			array( 'key' => '_mom_file_id', 'label' => 'Or upload a file', 'type' => 'file' ),
		),
		'mom_department'  => array(
			array( 'key' => '_mom_mandate', 'label' => 'Mandate', 'type' => 'textarea' ),
			array( 'key' => '_mom_services', 'label' => 'Services', 'type' => 'textarea' ),
			array( 'key' => '_mom_contact_name', 'label' => 'Contact name', 'type' => 'text' ),
			array( 'key' => '_mom_email', 'label' => 'Email', 'type' => 'text' ),
			array( 'key' => '_mom_phone', 'label' => 'Phone', 'type' => 'text' ),
			array( 'key' => '_mom_location', 'label' => 'Location', 'type' => 'text' ),
			array( 'key' => '_mom_provincial', 'label' => 'Provincial office', 'type' => 'checkbox' ),
		),
		'mom_banner'      => array(
			array( 'key' => '_mom_subtitle', 'label' => 'Subtitle', 'type' => 'textarea' ),
			array( 'key' => '_mom_href', 'label' => 'Link', 'type' => 'url' ),
		),
		'mom_opportunity' => array(
			array( 'key' => '_mom_mineral', 'label' => 'Mineral', 'type' => 'text' ),
			array( 'key' => '_mom_region', 'label' => 'Region', 'type' => 'text' ),
		),
		'mom_faq'         => array(
			array( 'key' => '_mom_keywords', 'label' => 'Keywords (space separated)', 'type' => 'text' ),
			array( 'key' => '_mom_department', 'label' => 'Department', 'type' => 'text' ),
		),
	);
}

add_action(
	'add_meta_boxes',
	function () {
		foreach ( ministry_meta_schema() as $type => $fields ) {
			add_meta_box(
				'mom_details',
				'Details',
				'ministry_render_meta_box',
				$type,
				'normal',
				'high'
			);
		}
	}
);

/**
 * Render the details box.
 *
 * @param WP_Post $post Current post.
 */
function ministry_render_meta_box( $post ) {
	wp_nonce_field( 'ministry_meta', 'ministry_meta_nonce' );
	$schema = ministry_meta_schema();
	$fields = isset( $schema[ $post->post_type ] ) ? $schema[ $post->post_type ] : array();
	echo '<div class="mom-meta">';
	foreach ( $fields as $field ) {
		$key   = $field['key'];
		$value = (string) get_post_meta( $post->ID, $key, true );
		echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $field['label'] ) . '</strong></label><br>';
		if ( 'textarea' === $field['type'] ) {
			echo '<textarea class="widefat" rows="3" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
		} elseif ( 'checkbox' === $field['type'] ) {
			echo '<label><input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="1" ' . checked( $value, '1', false ) . '> Yes</label>';
		} elseif ( 'select' === $field['type'] ) {
			echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
			foreach ( explode( ',', $field['options'] ) as $option ) {
				echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( ministry_document_category_label( $option ) ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'datetime' === $field['type'] ) {
			$local = $value ? str_replace( ' ', 'T', substr( $value, 0, 16 ) ) : '';
			echo '<input type="datetime-local" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $local ) . '">';
		} elseif ( 'file' === $field['type'] ) {
			$name = $value ? basename( (string) get_attached_file( (int) $value ) ) : '';
			echo '<input type="hidden" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
			echo '<button type="button" class="button mom-pick-file" data-target="' . esc_attr( $key ) . '">Choose file</button> ';
			echo '<span class="mom-file-name">' . esc_html( $name ) . '</span>';
		} else {
			$type = 'number' === $field['type'] ? 'number' : 'text';
			echo '<input class="widefat" type="' . esc_attr( $type ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
		}
		echo '</p>';
	}
	echo '</div>';
}

add_action(
	'save_post',
	function ( $post_id ) {
		if ( ! isset( $_POST['ministry_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ministry_meta_nonce'] ) ), 'ministry_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$schema = ministry_meta_schema();
		$type   = get_post_type( $post_id );
		if ( ! isset( $schema[ $type ] ) ) {
			return;
		}
		foreach ( $schema[ $type ] as $field ) {
			$key  = $field['key'];
			$raw  = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			$save = '';
			if ( 'checkbox' === $field['type'] ) {
				$save = $raw ? '1' : '0';
			} elseif ( 'number' === $field['type'] || 'file' === $field['type'] ) {
				$save = (string) absint( $raw );
			} elseif ( 'datetime' === $field['type'] ) {
				$raw  = str_replace( 'T', ' ', (string) $raw );
				$save = preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $raw ) ? substr( $raw, 0, 16 ) . ':00' : '';
			} elseif ( 'textarea' === $field['type'] ) {
				$save = sanitize_textarea_field( (string) $raw );
			} elseif ( 'url' === $field['type'] ) {
				$save = ministry_sanitize_href( (string) $raw );
			} else {
				$save = sanitize_text_field( (string) $raw );
			}
			update_post_meta( $post_id, $key, $save );
		}
	}
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! isset( ministry_meta_schema()[ $screen->post_type ] ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'ministry-content-admin', MINISTRY_CONTENT_URL . 'assets/admin.css', array(), '1.1.0' );
		wp_enqueue_script( 'ministry-content-admin', MINISTRY_CONTENT_URL . 'assets/admin.js', array( 'jquery' ), '1.1.0', true );
	}
);

add_filter(
	'manage_mom_department_posts_columns',
	function ( $columns ) {
		$columns['mom_provincial'] = 'Kind';
		$columns['mom_location']   = 'Location';
		return $columns;
	}
);

add_action(
	'manage_mom_department_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'mom_provincial' === $column ) {
			echo '1' === get_post_meta( $post_id, '_mom_provincial', true ) ? 'Provincial office' : 'Head office';
		}
		if ( 'mom_location' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_mom_location', true ) );
		}
	},
	10,
	2
);
