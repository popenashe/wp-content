<?php
/**
 * Gallery albums edited in wp-admin and rendered on the photo gallery.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box(
			'mom_album',
			'Album',
			'ministry_render_album_box',
			'mom_album',
			'normal',
			'high'
		);
	}
);

/**
 * Year, layout, order, and the photo list.
 *
 * @param WP_Post $post Album.
 */
function ministry_render_album_box( $post ) {
	wp_nonce_field( 'ministry_album', 'ministry_album_nonce' );
	$year   = (string) get_post_meta( $post->ID, '_mom_year', true );
	$layout = (string) get_post_meta( $post->ID, '_mom_layout', true );
	if ( '' === $year && 'auto-draft' === $post->post_status ) {
		$year = wp_date( 'Y' );
	}
	if ( 'wide' !== $layout ) {
		$layout = 'portrait';
	}
	$photos = get_post_meta( $post->ID, '_mom_photos', true );
	if ( ! is_array( $photos ) || ! $photos ) {
		$photos = array(
			array(
				'id'      => 0,
				'caption' => '',
				'alt'     => '',
				'home'    => '0',
			),
		);
	}

	echo '<p class="description">Photos appear on the photo gallery in this order. Tick <strong>Latest pictures</strong> to show a photo on the homepage.</p>';
	echo '<div class="mom-album-details">';
	echo '<p><label for="mom_year"><strong>Year</strong></label><br><input type="text" class="regular-text" id="mom_year" name="mom_year" value="' . esc_attr( $year ) . '"></p>';
	echo '<p><label for="mom_layout"><strong>Layout</strong></label><br><select id="mom_layout" name="mom_layout">';
	echo '<option value="portrait" ' . selected( $layout, 'portrait', false ) . '>Portrait grid</option>';
	echo '<option value="wide" ' . selected( $layout, 'wide', false ) . '>Wide images</option>';
	echo '</select></p>';
	echo '<p><label for="mom_order"><strong>Order</strong></label><br><input type="number" class="small-text" id="mom_order" name="mom_order" min="0" step="1" value="' . esc_attr( (string) $post->menu_order ) . '"> <span class="description">Lower numbers appear first.</span></p>';
	echo '</div>';

	echo '<div class="mom-album-photos"><div class="mom-photo-list">';
	foreach ( array_values( $photos ) as $index => $row ) {
		ministry_render_album_photo_row( $index, is_array( $row ) ? $row : array() );
	}
	echo '</div>';
	echo '<p><button type="button" class="button mom-add-photo">Add photo</button></p></div>';
}

/**
 * One photo row in the album editor.
 *
 * @param int                  $index Row index.
 * @param array<string, mixed> $row   Stored photo.
 */
function ministry_render_album_photo_row( $index, $row ) {
	$id      = isset( $row['id'] ) ? (int) $row['id'] : 0;
	$caption = isset( $row['caption'] ) ? (string) $row['caption'] : '';
	$alt     = isset( $row['alt'] ) ? (string) $row['alt'] : '';
	$home    = ! empty( $row['home'] ) && '0' !== (string) $row['home'];
	$thumb   = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
	if ( $id && ! $thumb ) {
		$thumb = wp_get_attachment_url( $id );
	}
	$name = 'mom_photos[' . $index . ']';
	echo '<div class="mom-photo-row">';
	echo '<span class="mom-photo-handle dashicons dashicons-menu" title="Drag to reorder"></span>';
	echo '<div class="mom-photo-preview">';
	if ( $thumb ) {
		echo '<img src="' . esc_url( $thumb ) . '" alt="">';
	}
	echo '</div><div class="mom-photo-fields">';
	echo '<input type="hidden" class="mom-photo-id" name="' . esc_attr( $name . '[id]' ) . '" value="' . esc_attr( (string) $id ) . '">';
	echo '<p><button type="button" class="button mom-photo-pick">Choose image</button> <button type="button" class="button-link-delete mom-remove-photo">Remove</button></p>';
	echo '<p><label>Caption<br><input type="text" class="widefat" name="' . esc_attr( $name . '[caption]' ) . '" value="' . esc_attr( $caption ) . '"></label></p>';
	echo '<p><label>Alt text<br><input type="text" class="widefat" name="' . esc_attr( $name . '[alt]' ) . '" value="' . esc_attr( $alt ) . '"></label></p>';
	echo '<p><label><input type="checkbox" name="' . esc_attr( $name . '[home]' ) . '" value="1" ' . checked( $home, true, false ) . '> Latest pictures</label></p>';
	echo '</div></div>';
}

add_action( 'save_post_mom_album', 'ministry_save_album' );

/**
 * Store album fields and photos.
 *
 * @param int $post_id Album ID.
 */
function ministry_save_album( $post_id ) {
	if ( ! isset( $_POST['ministry_album_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ministry_album_nonce'] ) ), 'ministry_album' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$year   = isset( $_POST['mom_year'] ) ? sanitize_text_field( wp_unslash( $_POST['mom_year'] ) ) : '';
	$layout = isset( $_POST['mom_layout'] ) ? sanitize_key( wp_unslash( $_POST['mom_layout'] ) ) : 'portrait';
	$order  = isset( $_POST['mom_order'] ) ? absint( wp_unslash( $_POST['mom_order'] ) ) : 0;
	update_post_meta( $post_id, '_mom_year', $year );
	update_post_meta( $post_id, '_mom_layout', 'wide' === $layout ? 'wide' : 'portrait' );

	$photos = array();
	$rows   = isset( $_POST['mom_photos'] ) ? wp_unslash( $_POST['mom_photos'] ) : array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$id = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
			if ( ! $id ) {
				continue;
			}
			$photos[] = array(
				'id'      => $id,
				'caption' => isset( $row['caption'] ) ? sanitize_text_field( (string) $row['caption'] ) : '',
				'alt'     => isset( $row['alt'] ) ? sanitize_text_field( (string) $row['alt'] ) : '',
				'home'    => empty( $row['home'] ) ? '0' : '1',
			);
		}
	}
	update_post_meta( $post_id, '_mom_photos', $photos );

	global $wpdb;
	$wpdb->update(
		$wpdb->posts,
		array( 'menu_order' => $order ),
		array( 'ID' => $post_id ),
		array( '%d' ),
		array( '%d' )
	);
	clean_post_cache( $post_id );
}

add_filter(
	'manage_mom_album_posts_columns',
	function ( $columns ) {
		$next = array();
		foreach ( $columns as $key => $label ) {
			$next[ $key ] = $label;
			if ( 'title' === $key ) {
				$next['mom_year']   = 'Year';
				$next['mom_photos'] = 'Photos';
				$next['mom_order']  = 'Order';
			}
		}
		return $next;
	}
);

add_action(
	'manage_mom_album_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'mom_year' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_mom_year', true ) );
		}
		if ( 'mom_photos' === $column ) {
			echo esc_html( (string) count( ministry_album_photos( $post_id ) ) );
		}
		if ( 'mom_order' === $column ) {
			$post = get_post( $post_id );
			echo esc_html( $post ? (string) $post->menu_order : '0' );
		}
	},
	10,
	2
);

add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'mom_album' !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( ! $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
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
		if ( ! $screen || 'mom_album' !== $screen->post_type ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style( 'ministry-content-admin', MINISTRY_CONTENT_URL . 'assets/admin.css', array(), '1.1.0' );
		wp_enqueue_script( 'ministry-content-admin', MINISTRY_CONTENT_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), '1.1.0', true );
	}
);
