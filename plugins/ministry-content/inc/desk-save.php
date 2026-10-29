<?php
/**
 * Desk save module: permission checks and record writes.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save a desk form and return to the screen.
 */
function ministry_desk_handle_save() {
	if ( ! ministry_desk_can_open() ) {
		wp_die( esc_html__( 'You cannot save this.', 'ministry-content' ) );
	}
	check_admin_referer( 'ministry_desk_save' );
	$raw  = isset( $_POST['form'] ) ? wp_unslash( $_POST['form'] ) : ( isset( $_GET['form'] ) ? wp_unslash( $_GET['form'] ) : '' );
	$form = sanitize_key( $raw );
	$need = ministry_desk_form_cap( $form );
	$export_type = isset( $_REQUEST['type'] ) ? sanitize_key( wp_unslash( $_REQUEST['type'] ) ) : '';
	if ( 'export' === $form && ministry_desk_is_registration_type( $export_type ) && ministry_desk_can( 'events' ) ) {
		$need = 'events';
	}
	if ( $need && ! ministry_desk_can( $need ) ) {
		wp_die( esc_html__( 'Your role cannot save this.', 'ministry-content' ) );
	}
	$back = isset( $_REQUEST['back'] ) ? esc_url_raw( wp_unslash( $_REQUEST['back'] ) ) : ministry_desk_url( 'mom-desk' );
	if ( 'delete' === $form ) {
		$back = remove_query_arg( array( 'id', 'index', 'new' ), $back );
	}
	$id   = ministry_desk_save_form( $form );
	if ( $id && 'album-create' === $form ) {
		$back = ministry_desk_url( 'mom-albums', array( 'view' => 'edit', 'id' => $id ) );
	} elseif ( $id && 'delete' !== $form ) {
		$back = add_query_arg( 'id', $id, $back );
	}
	wp_safe_redirect( add_query_arg( 'saved', '1', $back ) );
	exit;
}

/**
 * Block edits the current user cannot make.
 *
 * @param int $id Post ID, or 0 when creating.
 */
function ministry_desk_guard_post( $id ) {
	if ( ! $id ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $id ) ) {
		wp_die( esc_html__( 'You cannot edit this record.', 'ministry-content' ) );
	}
}

/**
 * @param string $form Form key.
 * @return int Saved post ID, or 0.
 */
function ministry_desk_save_form( $form ) {
	switch ( $form ) {
		case 'notice':
		case 'news':
			return ministry_desk_save_story( $form );
		case 'document':
			return ministry_desk_save_document();
		case 'service':
			return ministry_desk_save_service();
		case 'faq':
			return ministry_desk_save_faq();
		case 'event':
			return ministry_desk_save_event();
		case 'album':
			return ministry_desk_save_album_form();
		case 'album-create':
			return ministry_desk_save_album_create();
		case 'photos':
			ministry_desk_save_photos();
			return 0;
		case 'person':
			ministry_desk_save_person();
			return 0;
		case 'leadership':
			ministry_desk_save_leadership();
			return 0;
		case 'people-order':
			ministry_desk_save_people_order();
			return 0;
		case 'departments-order':
			ministry_desk_save_id_order( isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '' );
			ministry_desk_log( 'reordered departments' );
			return 0;
		case 'offices-order':
			ministry_desk_save_id_order( isset( $_POST['order_centres'] ) ? sanitize_text_field( wp_unslash( $_POST['order_centres'] ) ) : '' );
			ministry_desk_save_id_order( isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '' );
			ministry_desk_log( 'reordered offices' );
			return 0;
		case 'structure':
			ministry_desk_save_structure();
			return 0;
		case 'department':
		case 'office':
			return ministry_desk_save_department( $form );
		case 'entity':
			ministry_desk_save_entity();
			return 0;
		case 'opportunity':
			return ministry_desk_save_opportunity();
		case 'facts':
			ministry_desk_save_facts();
			return 0;
		case 'enquiry':
			ministry_desk_save_enquiry();
			return 0;
		case 'user':
			ministry_desk_save_user();
			return 0;
		case 'bulk':
			ministry_desk_save_bulk();
			return 0;
		case 'review':
			ministry_desk_save_review();
			return 0;
		case 'translate':
			return ministry_desk_save_translate();
		case 'page':
			return ministry_desk_save_page();
		case 'export':
			ministry_desk_export_enquiries();
			return 0;
		case 'delete':
			ministry_desk_save_delete();
			return 0;
	}
	return 0;
}

/**
 * Trash a desk record, or drop a person/agency from settings.
 */
function ministry_desk_save_delete() {
	if ( isset( $_REQUEST['leader_index'] ) && '' !== (string) wp_unslash( $_REQUEST['leader_index'] ) ) {
		if ( ! ministry_desk_can( 'people' ) || ! ministry_desk_can_publish() ) {
			wp_die( esc_html__( 'Your role cannot remove people.', 'ministry-content' ) );
		}
		$index   = (int) wp_unslash( $_REQUEST['leader_index'] );
		$leaders = ministry_settings()['leaders'];
		if ( isset( $leaders[ $index ] ) ) {
			unset( $leaders[ $index ] );
			ministry_desk_patch_settings( array( 'leaders' => array_values( $leaders ) ) );
			ministry_desk_log( 'deleted a person' );
		}
		return;
	}
	if ( isset( $_REQUEST['entity_index'] ) && '' !== (string) wp_unslash( $_REQUEST['entity_index'] ) ) {
		if ( ! ministry_desk_can( 'agencies' ) || ! ministry_desk_can_publish() ) {
			wp_die( esc_html__( 'Your role cannot remove an agency.', 'ministry-content' ) );
		}
		$index    = (int) wp_unslash( $_REQUEST['entity_index'] );
		$entities = ministry_settings()['entities'];
		if ( isset( $entities[ $index ] ) ) {
			unset( $entities[ $index ] );
			ministry_desk_patch_settings( array( 'entities' => array_values( $entities ) ) );
			ministry_desk_log( 'deleted an agency' );
		}
		return;
	}
	$attachment = isset( $_REQUEST['attachment_id'] ) ? absint( $_REQUEST['attachment_id'] ) : 0;
	if ( $attachment ) {
		if ( ! current_user_can( 'delete_post', $attachment ) || ! ministry_desk_can( 'albums' ) ) {
			wp_die( esc_html__( 'Your role cannot delete this photo.', 'ministry-content' ) );
		}
		ministry_photo_detach( $attachment );
		wp_delete_attachment( $attachment, true );
		ministry_desk_log( 'deleted a photo' );
		return;
	}
	$id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
	if ( ! $id || ! current_user_can( 'delete_post', $id ) ) {
		return;
	}
	$type_cap = ministry_desk_type_cap( (string) get_post_type( $id ) );
	if ( $type_cap && ! ministry_desk_can( $type_cap ) ) {
		wp_die( esc_html__( 'Your role cannot delete this.', 'ministry-content' ) );
	}
	if ( 'mom_album' === get_post_type( $id ) ) {
		$photos = ministry_album_photos( $id );
		foreach ( $photos as $photo ) {
			ministry_photo_remove_from_album( (int) $photo['id'], $id );
		}
	}
	wp_trash_post( $id );
	ministry_desk_log( 'deleted ' . get_the_title( $id ) );
}

/**
 * Delete control for a WordPress record.
 *
 * @param WP_Post|null $post Record.
 */
function ministry_desk_delete_button( $post ) {
	if ( ! $post || ! current_user_can( 'delete_post', $post->ID ) ) {
		return;
	}
	$type_cap = ministry_desk_type_cap( $post->post_type );
	if ( $type_cap && ! ministry_desk_can( $type_cap ) ) {
		return;
	}
	echo '<button class="mom-btn mom-btn-danger" name="form" value="delete" onclick="return confirm(\'Remove this from the website? It can be restored from Trash in WordPress.\')">Delete</button>';
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $post->ID ) . '">';
}

/**
 * Compact delete link for a list row.
 *
 * @param int    $id   Post ID.
 * @param string $back Return URL.
 */
function ministry_desk_delete_link( $id, $back = '', $extra = array() ) {
	if ( $id && ! current_user_can( 'delete_post', $id ) ) {
		return;
	}
	if ( $id ) {
		$type_cap = ministry_desk_type_cap( (string) get_post_type( $id ) );
		if ( $type_cap && ! ministry_desk_can( $type_cap ) ) {
			return;
		}
	}
	if ( ! $id && empty( $extra ) ) {
		return;
	}
	$message = isset( $extra['confirm'] ) ? (string) $extra['confirm'] : 'Remove this from the website?';
	unset( $extra['confirm'] );
	$query = array_merge(
		array(
			'action' => 'ministry_desk_save',
			'form'   => 'delete',
			'back'   => $back ? $back : admin_url( 'admin.php?page=mom-desk' ),
		),
		$extra
	);
	if ( $id ) {
		$query['post_id'] = (int) $id;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?' . http_build_query( $query ) ), 'ministry_desk_save' );
	echo ' · <a class="mom-danger-link" href="' . esc_url( $url ) . '" onclick="return confirm(\'' . esc_js( $message ) . '\')">Delete</a>';
}

/**
 * @param string $intent Requested intent.
 * @param int    $id     Existing post.
 */
function ministry_desk_status_for( $intent, $id ) {
	if ( 'pending' === $intent ) {
		return 'pending';
	}
	if ( 'draft' === $intent || 'preview' === $intent ) {
		return $id ? (string) get_post_status( $id ) : 'draft';
	}
	if ( 'publish' === $intent || 'keep' === $intent ) {
		if ( ! ministry_desk_can_publish() ) {
			return 'pending';
		}
		if ( 'keep' === $intent && $id ) {
			$status = (string) get_post_status( $id );
			return in_array( $status, array( 'publish', 'future', 'private' ), true ) ? $status : 'publish';
		}
		$when = isset( $_POST['schedule_date'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_date'] ) ) : '';
		if ( $when && strtotime( $when ) > time() ) {
			return 'future';
		}
		return 'publish';
	}
	return 'draft';
}

/**
 * @param string $form notice|news.
 */
function ministry_desk_save_story( $form ) {
	$id      = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	ministry_desk_guard_post( $id );
	$intent  = isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'draft';
	$status  = ministry_desk_status_for( $intent, $id );
	$kind    = 'notice' === $form ? 'notice' : sanitize_key( isset( $_POST['kind'] ) ? wp_unslash( $_POST['kind'] ) : 'press' );
	$allowed = array( 'press', 'speech', 'tender', 'vacancy', 'news' );
	if ( 'notice' !== $form && ! in_array( $kind, $allowed, true ) ) {
		$kind = 'press';
	}
	$excerpt = sanitize_textarea_field( isset( $_POST['post_excerpt'] ) ? wp_unslash( $_POST['post_excerpt'] ) : '' );
	$title   = sanitize_text_field( isset( $_POST['post_title'] ) ? wp_unslash( $_POST['post_title'] ) : '' );
	$number  = isset( $_POST['notice_number'] ) ? sanitize_text_field( wp_unslash( $_POST['notice_number'] ) ) : '';
	if ( 'notice' === $form ) {
		if ( $id && ( '' === $number || ! ministry_desk_can( 'notice_numbers' ) ) ) {
			$existing = (string) get_post_meta( $id, '_mom_number', true );
			$number   = $existing ? $existing : ministry_desk_next_notice_number();
		} elseif ( '' === $number ) {
			$number = ministry_desk_next_notice_number();
		}
		if ( '' === $title ) {
			$title = $number ? 'Public Notice ' . $number : wp_trim_words( $excerpt, 12, '' );
		}
		if ( '' === $title ) {
			$title = 'Public notice';
		}
	}
	$postarr = array(
		'post_type'    => 'mom_news',
		'post_title'   => $title,
		'post_excerpt' => $excerpt,
		'post_content' => wp_kses_post( isset( $_POST['post_content'] ) ? wp_unslash( $_POST['post_content'] ) : '' ),
		'post_status'  => $status,
	);
	if ( 'future' === $status && ! empty( $_POST['schedule_date'] ) ) {
		$postarr['post_date'] = sanitize_text_field( wp_unslash( $_POST['schedule_date'] ) ) . ' 08:00:00';
	}
	if ( $id ) {
		$postarr['ID'] = $id;
		wp_update_post( $postarr );
	} else {
		$id = (int) wp_insert_post( $postarr );
	}
	if ( ! $id ) {
		return 0;
	}
	if ( 'notice' === $form && ( '' === $number || ! ministry_desk_can( 'notice_numbers' ) ) ) {
		$existing = (string) get_post_meta( $id, '_mom_number', true );
		$number   = $existing ? $existing : $number;
	}
	update_post_meta( $id, '_mom_kind', 'notice' === $form ? 'notice' : $kind );
	update_post_meta( $id, '_mom_number', $number );
	update_post_meta( $id, '_mom_issued', sanitize_text_field( isset( $_POST['issued'] ) ? wp_unslash( $_POST['issued'] ) : '' ) );
	update_post_meta( $id, '_mom_effect', sanitize_text_field( isset( $_POST['effect'] ) ? wp_unslash( $_POST['effect'] ) : '' ) );
	update_post_meta( $id, '_mom_deadline', sanitize_text_field( isset( $_POST['deadline'] ) ? wp_unslash( $_POST['deadline'] ) : '' ) );
	update_post_meta( $id, '_mom_signed_by', sanitize_text_field( isset( $_POST['signed_by'] ) ? wp_unslash( $_POST['signed_by'] ) : '' ) );
	$pdf_id = absint( isset( $_POST['pdf_id'] ) ? wp_unslash( $_POST['pdf_id'] ) : 0 );
	update_post_meta( $id, '_mom_pdf_id', $pdf_id );
	if ( 'notice' === $form ) {
		ministry_desk_sync_notice_document( $id, $pdf_id, $title, $number );
	}
	$banner = empty( $_POST['banner'] ) ? '0' : '1';
	update_post_meta( $id, '_mom_banner', $banner );
	if ( '1' === $banner ) {
		$others = get_posts(
			array(
				'post_type'      => 'mom_news',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_mom_banner',
				'meta_value'     => '1',
				'exclude'        => array( $id ),
			)
		);
		foreach ( $others as $other ) {
			update_post_meta( (int) $other, '_mom_banner', '0' );
		}
	}
	update_post_meta( $id, '_mom_banner_text', sanitize_text_field( isset( $_POST['banner_text'] ) ? wp_unslash( $_POST['banner_text'] ) : '' ) );
	update_post_meta( $id, '_mom_banner_until', sanitize_text_field( isset( $_POST['banner_until'] ) ? wp_unslash( $_POST['banner_until'] ) : '' ) );
	update_post_meta( $id, '_mom_topics', ministry_desk_text_list( 'topics' ) );
	update_post_meta( $id, '_mom_provinces', ministry_desk_text_list( 'provinces' ) );
	update_post_meta( $id, '_mom_applies', ministry_desk_text_list( 'applies' ) );
	update_post_meta( $id, '_mom_correction', empty( $_POST['correction'] ) ? '0' : '1' );
	$thumb = absint( isset( $_POST['thumbnail_id'] ) ? wp_unslash( $_POST['thumbnail_id'] ) : 0 );
	if ( $thumb ) {
		set_post_thumbnail( $id, $thumb );
	} elseif ( isset( $_POST['thumbnail_id'] ) ) {
		delete_post_thumbnail( $id );
	}
	if ( $thumb && isset( $_POST['alt'] ) ) {
		update_post_meta( $thumb, '_wp_attachment_image_alt', sanitize_text_field( wp_unslash( $_POST['alt'] ) ) );
	}
	update_post_meta( $id, '_mom_caption', sanitize_text_field( isset( $_POST['caption'] ) ? wp_unslash( $_POST['caption'] ) : '' ) );
	update_post_meta( $id, '_mom_i18n_stale', ministry_desk_i18n( $id )['sn']['title'] || ministry_desk_i18n( $id )['nd']['title'] ? '1' : '0' );
	ministry_desk_log( ( 'pending' === $status ? 'submitted ' : 'updated ' ) . $postarr['post_title'] );
	return $id;
}

/**
 * Keep the signed PDF on the Documents list, as the notice form promises.
 *
 * @param int    $news_id Notice ID.
 * @param int    $pdf_id  Attachment ID.
 * @param string $title   Notice title.
 * @param string $number  Notice number.
 */
function ministry_desk_sync_notice_document( $news_id, $pdf_id, $title, $number ) {
	$news_id = (int) $news_id;
	$pdf_id  = (int) $pdf_id;
	if ( ! $news_id || ! $pdf_id ) {
		return;
	}
	$existing = (int) get_post_meta( $news_id, '_mom_document_id', true );
	$label    = $number ? 'Public Notice ' . $number . ' — ' . $title : $title;
	$postarr  = array(
		'post_type'    => 'mom_document',
		'post_title'   => $label,
		'post_excerpt' => $number ? 'Notice ' . $number : '',
		'post_status'  => 'publish' === get_post_status( $news_id ) ? 'publish' : 'draft',
	);
	if ( $existing && get_post( $existing ) ) {
		$postarr['ID'] = $existing;
		$doc_id        = (int) wp_update_post( $postarr );
	} else {
		$doc_id = (int) wp_insert_post( $postarr );
	}
	if ( ! $doc_id ) {
		return;
	}
	update_post_meta( $doc_id, '_mom_category', 'publications' );
	update_post_meta( $doc_id, '_mom_file_id', $pdf_id );
	update_post_meta( $news_id, '_mom_document_id', $doc_id );
}

/**
 * @param string $key Posted list key.
 * @return string[]
 */
function ministry_desk_text_list( $key ) {
	$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : array();
	if ( ! is_array( $raw ) ) {
		$raw = array( $raw );
	}
	$out = array();
	foreach ( $raw as $item ) {
		$text = sanitize_text_field( (string) $item );
		if ( '' !== $text ) {
			$out[] = $text;
		}
	}
	return $out;
}

/**
 * @return int
 */
function ministry_desk_save_document() {
	$id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	ministry_desk_guard_post( $id );
	$intent = isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'pending';
	$file   = absint( isset( $_POST['file_id'] ) ? wp_unslash( $_POST['file_id'] ) : 0 );
	$postarr = array(
		'post_type'    => 'mom_document',
		'post_title'   => sanitize_text_field( isset( $_POST['post_title'] ) ? wp_unslash( $_POST['post_title'] ) : '' ),
		'post_excerpt' => sanitize_textarea_field( isset( $_POST['version'] ) ? wp_unslash( $_POST['version'] ) : '' ),
		'post_status'  => ministry_desk_status_for( $intent, $id ),
	);
	if ( $id ) {
		$postarr['ID'] = $id;
		wp_update_post( $postarr );
	} else {
		$id = (int) wp_insert_post( $postarr );
	}
	if ( ! $id ) {
		return 0;
	}
	update_post_meta( $id, '_mom_category', sanitize_key( isset( $_POST['category'] ) ? wp_unslash( $_POST['category'] ) : 'guidelines' ) );
	update_post_meta( $id, '_mom_file_id', $file );
	update_post_meta( $id, '_mom_draft_label', empty( $_POST['is_draft'] ) ? '0' : '1' );
	update_post_meta( $id, '_mom_replaced_by', absint( isset( $_POST['replaced_by'] ) ? wp_unslash( $_POST['replaced_by'] ) : 0 ) );
	ministry_desk_log( 'updated a document' );
	return $id;
}

/**
 * @return int
 */
function ministry_desk_save_service() {
	$id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	ministry_desk_guard_post( $id );
	$intent = isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'draft';
	$postarr = array(
		'post_type'    => 'mom_service',
		'post_title'   => sanitize_text_field( isset( $_POST['post_title'] ) ? wp_unslash( $_POST['post_title'] ) : '' ),
		'post_excerpt' => sanitize_textarea_field( isset( $_POST['post_excerpt'] ) ? wp_unslash( $_POST['post_excerpt'] ) : '' ),
		'post_status'  => ministry_desk_status_for( $intent, $id ),
	);
	if ( $id ) {
		$postarr['ID'] = $id;
		wp_update_post( $postarr );
	} else {
		$id = (int) wp_insert_post( $postarr );
	}
	if ( ! $id ) {
		return 0;
	}
	$fees = array();
	$rows = isset( $_POST['fees'] ) ? wp_unslash( $_POST['fees'] ) : array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = sanitize_text_field( isset( $row['label'] ) ? (string) $row['label'] : '' );
			if ( '' === $label ) {
				continue;
			}
			$fees[] = array(
				'label'  => $label,
				'amount' => sanitize_text_field( isset( $row['amount'] ) ? (string) $row['amount'] : '' ),
				'note'   => sanitize_text_field( isset( $row['note'] ) ? (string) $row['note'] : '' ),
			);
		}
	}
	update_post_meta(
		$id,
		'_mom_service',
		array(
			'icon'       => sanitize_key( isset( $_POST['icon'] ) ? wp_unslash( $_POST['icon'] ) : 'doc' ),
			'category'   => sanitize_text_field( isset( $_POST['category'] ) ? wp_unslash( $_POST['category'] ) : '' ),
			'number'     => sanitize_text_field( isset( $_POST['turn_number'] ) ? wp_unslash( $_POST['turn_number'] ) : '' ),
			'unit'       => sanitize_text_field( isset( $_POST['turn_unit'] ) ? wp_unslash( $_POST['turn_unit'] ) : '' ),
			'glance'     => empty( $_POST['glance'] ) ? '0' : '1',
			'needs'      => ministry_desk_text_list( 'needs' ),
			'steps'      => ministry_desk_text_list( 'steps' ),
			'fees'       => $fees,
			'fee_source' => sanitize_text_field( isset( $_POST['fee_source'] ) ? wp_unslash( $_POST['fee_source'] ) : '' ),
			'office'     => sanitize_text_field( isset( $_POST['office'] ) ? wp_unslash( $_POST['office'] ) : '' ),
			'department' => sanitize_text_field( isset( $_POST['department'] ) ? wp_unslash( $_POST['department'] ) : '' ),
		)
	);
	ministry_desk_log( 'updated a service' );
	return $id;
}

/**
 * @return int
 */
function ministry_desk_save_faq() {
	$id      = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$title   = sanitize_text_field( isset( $_POST['post_title'] ) ? wp_unslash( $_POST['post_title'] ) : '' );
	$content = wp_kses_post( isset( $_POST['post_content'] ) ? wp_unslash( $_POST['post_content'] ) : '' );
	ministry_desk_guard_post( $id );
	if ( $id || '' !== $title || '' !== trim( wp_strip_all_tags( $content ) ) ) {
		$postarr = array(
			'post_type'    => 'mom_faq',
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => '' === trim( wp_strip_all_tags( $content ) ) ? 'draft' : 'publish',
			'menu_order'   => isset( $_POST['menu_order'] ) ? absint( $_POST['menu_order'] ) : 0,
		);
		if ( $id ) {
			$postarr['ID'] = $id;
			wp_update_post( $postarr );
		} else {
			$id = (int) wp_insert_post( $postarr );
		}
		if ( $id ) {
			update_post_meta( $id, '_mom_department', sanitize_text_field( isset( $_POST['department'] ) ? wp_unslash( $_POST['department'] ) : '' ) );
			update_post_meta( $id, '_mom_topic', sanitize_text_field( isset( $_POST['topic'] ) ? wp_unslash( $_POST['topic'] ) : '' ) );
			update_post_meta( $id, '_mom_readmore', absint( isset( $_POST['readmore'] ) ? wp_unslash( $_POST['readmore'] ) : 0 ) );
		}
	}
	$order = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '';
	if ( $order ) {
		$position = 0;
		foreach ( array_filter( array_map( 'absint', explode( ',', $order ) ) ) as $faq_id ) {
			wp_update_post(
				array(
					'ID'         => $faq_id,
					'menu_order' => $position,
				)
			);
			$position++;
		}
	}
	ministry_desk_log( 'updated an FAQ' );
	return (int) $id;
}

/**
 * @return int
 */
function ministry_desk_save_event() {
	$id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	ministry_desk_guard_post( $id );
	$intent = isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'draft';
	$start  = sanitize_text_field( isset( $_POST['start_date'] ) ? wp_unslash( $_POST['start_date'] ) : '' );
	$time   = sanitize_text_field( isset( $_POST['start_time'] ) ? wp_unslash( $_POST['start_time'] ) : '' );
	$end    = sanitize_text_field( isset( $_POST['end_date'] ) ? wp_unslash( $_POST['end_date'] ) : '' );
	$postarr = array(
		'post_type'    => 'mom_event',
		'post_title'   => sanitize_text_field( isset( $_POST['post_title'] ) ? wp_unslash( $_POST['post_title'] ) : '' ),
		'post_excerpt' => sanitize_textarea_field( isset( $_POST['post_excerpt'] ) ? wp_unslash( $_POST['post_excerpt'] ) : '' ),
		'post_content' => wp_kses_post( isset( $_POST['who'] ) ? wp_unslash( $_POST['who'] ) : '' ),
		'post_status'  => ministry_desk_status_for( $intent, $id ),
	);
	if ( $id ) {
		$postarr['ID'] = $id;
		wp_update_post( $postarr );
	} else {
		$id = (int) wp_insert_post( $postarr );
	}
	if ( ! $id ) {
		return 0;
	}
	update_post_meta( $id, '_mom_starts_at', trim( $start . ' ' . ( $time ? $time . ':00' : '' ) ) );
	update_post_meta( $id, '_mom_ends_at', $end ? $end . ' 17:00:00' : '' );
	update_post_meta( $id, '_mom_location', sanitize_text_field( isset( $_POST['venue'] ) ? wp_unslash( $_POST['venue'] ) : '' ) );
	update_post_meta( $id, '_mom_province', sanitize_text_field( isset( $_POST['province'] ) ? wp_unslash( $_POST['province'] ) : '' ) );
	update_post_meta( $id, '_mom_category', sanitize_text_field( isset( $_POST['event_type'] ) ? wp_unslash( $_POST['event_type'] ) : '' ) );
	update_post_meta( $id, '_mom_places', sanitize_text_field( isset( $_POST['places'] ) ? wp_unslash( $_POST['places'] ) : '' ) );
	update_post_meta( $id, '_mom_reg_close', sanitize_text_field( isset( $_POST['reg_close'] ) ? wp_unslash( $_POST['reg_close'] ) : '' ) );
	update_post_meta( $id, '_mom_accept_reg', empty( $_POST['accept_reg'] ) ? '0' : '1' );
	update_post_meta( $id, '_mom_reg_url', esc_url_raw( isset( $_POST['reg_url'] ) ? wp_unslash( $_POST['reg_url'] ) : '' ) );
	update_post_meta( $id, '_mom_calendar', empty( $_POST['calendar'] ) ? '0' : '1' );
	update_post_meta( $id, '_mom_album_id', absint( isset( $_POST['album_id'] ) ? wp_unslash( $_POST['album_id'] ) : 0 ) );
	$thumb = absint( isset( $_POST['thumbnail_id'] ) ? wp_unslash( $_POST['thumbnail_id'] ) : 0 );
	if ( $thumb ) {
		set_post_thumbnail( $id, $thumb );
		update_post_meta( $thumb, '_wp_attachment_image_alt', sanitize_text_field( isset( $_POST['alt'] ) ? wp_unslash( $_POST['alt'] ) : '' ) );
	}
	ministry_desk_log( 'updated an event' );
	return $id;
}

/**
 * @return int
 */
function ministry_desk_save_album_form() {
	$id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	ministry_desk_guard_post( $id );
	$intent = isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'draft';
	$photos = array();
	$rows   = isset( $_POST['mom_photos'] ) ? wp_unslash( $_POST['mom_photos'] ) : array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$photo = absint( isset( $row['id'] ) ? $row['id'] : 0 );
			if ( ! $photo ) {
				continue;
			}
			$alt = sanitize_text_field( isset( $row['alt'] ) ? (string) $row['alt'] : '' );
			$photos[] = array(
				'id'      => $photo,
				'caption' => sanitize_text_field( isset( $row['caption'] ) ? (string) $row['caption'] : '' ),
				'alt'     => $alt,
				'home'    => '0',
			);
			update_post_meta( $photo, '_wp_attachment_image_alt', $alt );
		}
	}
	if ( 'pending' === $intent ) {
		foreach ( $photos as $photo ) {
			if ( '' === $photo['alt'] ) {
				$intent = 'draft';
				break;
			}
		}
	}
	$postarr = array(
		'post_type'   => 'mom_album',
		'post_title'  => sanitize_text_field( isset( $_POST['post_title'] ) ? wp_unslash( $_POST['post_title'] ) : '' ),
		'post_status' => ministry_desk_status_for( $intent, $id ),
	);
	if ( $id ) {
		$postarr['ID'] = $id;
		wp_update_post( $postarr );
	} else {
		$id = (int) wp_insert_post( $postarr );
	}
	if ( ! $id ) {
		return 0;
	}
	$previous = ministry_album_photos( $id );
	foreach ( $previous as $old ) {
		ministry_photo_remove_from_album( (int) $old['id'], $id );
	}
	update_post_meta( $id, '_mom_photos', $photos );
	foreach ( $photos as $photo ) {
		ministry_photo_add_to_album( (int) $photo['id'], $id );
	}
	update_post_meta( $id, '_mom_year', sanitize_text_field( isset( $_POST['event_date'] ) ? wp_unslash( $_POST['event_date'] ) : '' ) );
	update_post_meta( $id, '_mom_album_cat', sanitize_text_field( isset( $_POST['album_cat'] ) ? wp_unslash( $_POST['album_cat'] ) : '' ) );
	update_post_meta( $id, '_mom_location', sanitize_text_field( isset( $_POST['location'] ) ? wp_unslash( $_POST['location'] ) : '' ) );
	update_post_meta( $id, '_mom_credit', sanitize_text_field( isset( $_POST['credit'] ) ? wp_unslash( $_POST['credit'] ) : '' ) );
	update_post_meta( $id, '_mom_show_credit', empty( $_POST['show_credit'] ) ? '0' : '1' );
	update_post_meta( $id, '_mom_event_id', absint( isset( $_POST['event_id'] ) ? wp_unslash( $_POST['event_id'] ) : 0 ) );
	ministry_desk_log( 'updated a photo album' );
	return $id;
}

/**
 * Save a person onto the leaders list, or hold them for approval.
 */
function ministry_desk_save_person() {
	if ( ! ministry_desk_can_publish() && empty( $_POST['intent'] ) ) {
		$_POST['intent'] = 'pending';
	}
	$index   = ( isset( $_POST['index'] ) && '' !== (string) wp_unslash( $_POST['index'] ) ) ? (int) wp_unslash( $_POST['index'] ) : -1;
	$leaders = ministry_settings()['leaders'];
	if ( ! is_array( $leaders ) ) {
		$leaders = array();
	}
	$current = ( $index >= 0 && isset( $leaders[ $index ] ) && is_array( $leaders[ $index ] ) ) ? $leaders[ $index ] : array(
		'title'    => '',
		'name'     => '',
		'bio'      => '',
		'imageUrl' => '',
	);
	$honor   = sanitize_text_field( isset( $_POST['honorific'] ) ? wp_unslash( $_POST['honorific'] ) : '' );
	$initial = sanitize_text_field( isset( $_POST['initials'] ) ? wp_unslash( $_POST['initials'] ) : '' );
	$surname = sanitize_text_field( isset( $_POST['surname'] ) ? wp_unslash( $_POST['surname'] ) : '' );
	$name    = trim( $honor . ' ' . $initial . ' ' . $surname );
	$image   = absint( isset( $_POST['image_id'] ) ? wp_unslash( $_POST['image_id'] ) : 0 );
	$row     = $current;
	$row['name']      = '' !== $name ? $name : ( isset( $current['name'] ) ? (string) $current['name'] : '' );
	$row['title']     = sanitize_text_field( isset( $_POST['job'] ) ? wp_unslash( $_POST['job'] ) : '' );
	$row['bio']       = sanitize_textarea_field( isset( $_POST['short_bio'] ) ? wp_unslash( $_POST['short_bio'] ) : '' );
	$row['fullBio']   = wp_kses_post( isset( $_POST['full_bio'] ) ? wp_unslash( $_POST['full_bio'] ) : '' );
	$row['honorific'] = $honor;
	$row['initials']  = $initial;
	$row['surname']   = $surname;
	$row['department'] = sanitize_text_field( isset( $_POST['department'] ) ? wp_unslash( $_POST['department'] ) : '' );
	$row['status']    = empty( $_POST['former'] ) ? 'current' : 'former';
	$row['acting']    = empty( $_POST['acting'] ) ? '0' : '1';
	$row['email']     = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' );
	$row['showEmail'] = empty( $_POST['show_email'] ) ? '0' : '1';
	$row['facts']     = ministry_desk_text_list( 'facts' );
	$row['home']      = isset( $current['home'] ) ? $current['home'] : '1';
	$pdf = absint( isset( $_POST['profile_id'] ) ? wp_unslash( $_POST['profile_id'] ) : 0 );
	if ( $pdf ) {
		$url = wp_get_attachment_url( $pdf );
		$row['profileId']  = $pdf;
		$row['profileUrl'] = $url ? $url : '';
	}
	if ( $image ) {
		$url = wp_get_attachment_url( $image );
		if ( $url ) {
			$row['imageUrl'] = $url;
			$row['imageId']  = $image;
		}
	}
	$intent = isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'keep';
	if ( ! ministry_desk_can_publish() || 'pending' === $intent ) {
		$meta = ministry_desk_meta();
		$key  = $index >= 0 ? (string) $index : 'new-' . wp_rand( 100, 999 );
		$pending = isset( $meta['person_pending'] ) && is_array( $meta['person_pending'] ) ? $meta['person_pending'] : array();
		$pending[ $key ] = array(
			'row'  => $row,
			'user' => wp_get_current_user()->display_name,
			'time' => time(),
		);
		ministry_desk_update_meta( array( 'person_pending' => $pending ) );
		ministry_desk_log( 'submitted a person for review' );
		return;
	}
	if ( $index >= 0 && isset( $leaders[ $index ] ) ) {
		$leaders[ $index ] = $row;
	} else {
		$leaders[] = $row;
	}
	ministry_desk_patch_settings( array( 'leaders' => array_values( $leaders ) ) );
	ministry_desk_log( 'updated a person' );
}

/**
 * Reorder the home leadership list.
 */
function ministry_desk_save_leadership() {
	if ( ! ministry_desk_can( 'people' ) ) {
		wp_die( esc_html__( 'Your role cannot change the leadership order.', 'ministry-content' ) );
	}
	$leaders = ministry_settings()['leaders'];
	$order   = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '';
	$home    = isset( $_POST['home'] ) && is_array( $_POST['home'] ) ? wp_unslash( $_POST['home'] ) : array();
	$next    = array();
	$indexes = array_filter( array_map( 'intval', explode( ',', $order ) ), function ( $index ) use ( $leaders ) {
		return isset( $leaders[ $index ] );
	} );
	if ( ! $indexes ) {
		$indexes = array_keys( $leaders );
	}
	foreach ( $indexes as $index ) {
		$row = $leaders[ $index ];
		$row['home'] = empty( $home[ $index ] ) ? '0' : '1';
		$next[] = $row;
	}
	ministry_desk_patch_settings( array( 'leaders' => $next ) );
	ministry_desk_log( 'reordered the leadership team' );
}

/**
 * Reorder people on The Ministry and the homepage.
 */
function ministry_desk_save_people_order() {
	if ( ! ministry_desk_can( 'people' ) ) {
		wp_die( esc_html__( 'Your role cannot change the people order.', 'ministry-content' ) );
	}
	$leaders = ministry_settings()['leaders'];
	if ( ! is_array( $leaders ) ) {
		return;
	}
	$order   = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '';
	$home    = isset( $_POST['home'] ) && is_array( $_POST['home'] ) ? wp_unslash( $_POST['home'] ) : array();
	$indexes = array_filter(
		array_map( 'intval', explode( ',', $order ) ),
		function ( $index ) use ( $leaders ) {
			return isset( $leaders[ $index ] );
		}
	);
	$next = array();
	$seen = array();
	foreach ( $indexes as $index ) {
		$row            = $leaders[ $index ];
		$row['home']    = empty( $home[ $index ] ) ? '0' : '1';
		$next[]         = $row;
		$seen[ $index ] = true;
	}
	foreach ( $leaders as $index => $row ) {
		if ( ! isset( $seen[ $index ] ) ) {
			$row['home'] = empty( $home[ $index ] ) ? '0' : '1';
			$next[]      = $row;
		}
	}
	ministry_desk_patch_settings( array( 'leaders' => $next ) );
	ministry_desk_log( 'reordered people' );
}

/**
 * @param string $order Comma-separated post IDs.
 */
function ministry_desk_save_id_order( $order ) {
	if ( ! ministry_desk_can( 'people' ) ) {
		wp_die( esc_html__( 'Your role cannot change this order.', 'ministry-content' ) );
	}
	$position = 0;
	foreach ( array_filter( array_map( 'absint', explode( ',', (string) $order ) ) ) as $id ) {
		wp_update_post(
			array(
				'ID'         => $id,
				'menu_order' => $position,
			)
		);
		++$position;
	}
}

/**
 * Store department groups and the top of the structure.
 */
function ministry_desk_save_structure() {
	if ( ! ministry_desk_can( 'people' ) ) {
		wp_die( esc_html__( 'Your role cannot change the structure.', 'ministry-content' ) );
	}
	$org = array();
	$raw = isset( $_POST['org'] ) && is_array( $_POST['org'] ) ? wp_unslash( $_POST['org'] ) : array();
	foreach ( $raw as $key => $group ) {
		if ( ! is_array( $group ) ) {
			continue;
		}
		$org[ sanitize_key( (string) $key ) ] = array(
			'title'   => sanitize_text_field( isset( $group['title'] ) ? (string) $group['title'] : '' ),
			'reports' => sanitize_text_field( isset( $group['reports'] ) ? (string) $group['reports'] : '' ),
			'chief'   => sanitize_text_field( isset( $group['chief'] ) ? (string) $group['chief'] : '' ),
		);
		$ids = isset( $group['depts'] ) && is_array( $group['depts'] ) ? $group['depts'] : array();
		foreach ( $ids as $dept_id ) {
			$dept_id = absint( $dept_id );
			if ( ! $dept_id ) {
				continue;
			}
			update_post_meta( $dept_id, '_mom_group', sanitize_key( (string) $key ) );
		}
	}
	ministry_desk_update_meta( array( 'org' => $org ) );
	ministry_desk_log( 'saved the organisational structure' );
}

/**
 * @param string $form department|office.
 */
function ministry_desk_save_department( $form ) {
	$id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	ministry_desk_guard_post( $id );
	$intent = isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'keep';
	$postarr = array(
		'post_type'    => 'mom_department',
		'post_title'   => sanitize_text_field( isset( $_POST['post_title'] ) ? wp_unslash( $_POST['post_title'] ) : '' ),
		'post_status'  => ministry_desk_status_for( $intent, $id ),
	);
	if ( $id ) {
		$postarr['ID'] = $id;
		wp_update_post( $postarr );
	} else {
		$id = (int) wp_insert_post( $postarr );
	}
	if ( ! $id ) {
		return 0;
	}
	if ( 'office' === $form ) {
		if ( '1' !== (string) get_post_meta( $id, '_mom_centre', true ) ) {
			update_post_meta( $id, '_mom_provincial', '1' );
		}
	} else {
		update_post_meta( $id, '_mom_provincial', '0' );
	}
	if ( isset( $_POST['purpose'] ) ) {
		update_post_meta( $id, '_mom_mandate', sanitize_textarea_field( wp_unslash( $_POST['purpose'] ) ) );
	}
	if ( isset( $_POST['summary'] ) ) {
		update_post_meta( $id, '_mom_summary', sanitize_textarea_field( wp_unslash( $_POST['summary'] ) ) );
	}
	if ( isset( $_POST['standards'] ) ) {
		update_post_meta( $id, '_mom_standards', ministry_desk_standard_list() );
	}
	if ( isset( $_POST['functions'] ) ) {
		update_post_meta( $id, '_mom_functions', ministry_desk_text_list( 'functions' ) );
	}
	if ( 'office' !== $form ) {
		update_post_meta( $id, '_mom_contact_name', sanitize_text_field( isset( $_POST['head'] ) ? wp_unslash( $_POST['head'] ) : '' ) );
		update_post_meta( $id, '_mom_head_title', sanitize_text_field( isset( $_POST['head_title'] ) ? wp_unslash( $_POST['head_title'] ) : '' ) );
	}
	update_post_meta( $id, '_mom_email', sanitize_text_field( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' ) );
	$phones = ministry_desk_phone_list();
	$phone  = sanitize_text_field( isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '' );
	if ( '' === $phone && $phones ) {
		$phone = implode( ' · ', wp_list_pluck( $phones, 'number' ) );
	}
	update_post_meta( $id, '_mom_phone', $phone );
	update_post_meta( $id, '_mom_phones', $phones );
	update_post_meta( $id, '_mom_location', sanitize_text_field( isset( $_POST['location'] ) ? wp_unslash( $_POST['location'] ) : '' ) );
	update_post_meta( $id, '_mom_address', sanitize_text_field( isset( $_POST['address'] ) ? wp_unslash( $_POST['address'] ) : '' ) );
	update_post_meta( $id, '_mom_town', sanitize_text_field( isset( $_POST['town'] ) ? wp_unslash( $_POST['town'] ) : '' ) );
	update_post_meta( $id, '_mom_province_name', sanitize_text_field( isset( $_POST['province'] ) ? wp_unslash( $_POST['province'] ) : '' ) );
	update_post_meta( $id, '_mom_short', sanitize_text_field( isset( $_POST['short'] ) ? wp_unslash( $_POST['short'] ) : '' ) );
	update_post_meta( $id, '_mom_group', sanitize_text_field( isset( $_POST['group'] ) ? wp_unslash( $_POST['group'] ) : '' ) );
	update_post_meta( $id, '_mom_profile', sanitize_textarea_field( isset( $_POST['profile'] ) ? wp_unslash( $_POST['profile'] ) : '' ) );
	update_post_meta( $id, '_mom_minerals', ministry_desk_text_list( 'minerals' ) );
	if ( 'office' === $form && function_exists( 'ministry_office_geocode_save' ) ) {
		ministry_office_geocode_save( $id );
	}
	if ( 'office' !== $form ) {
		$thumb = absint( isset( $_POST['thumbnail_id'] ) ? wp_unslash( $_POST['thumbnail_id'] ) : 0 );
		if ( $thumb ) {
			set_post_thumbnail( $id, $thumb );
			update_post_meta( $thumb, '_wp_attachment_image_alt', sanitize_text_field( isset( $_POST['alt'] ) ? wp_unslash( $_POST['alt'] ) : '' ) );
		} elseif ( isset( $_POST['thumbnail_id'] ) ) {
			delete_post_thumbnail( $id );
		}
	}
	if ( 'department' === $form ) {
		$photo = absint( isset( $_POST['photo_id'] ) ? wp_unslash( $_POST['photo_id'] ) : 0 );
		if ( $photo && wp_attachment_is_image( $photo ) ) {
			update_post_meta( $id, '_mom_photo', $photo );
		} else {
			delete_post_meta( $id, '_mom_photo' );
		}
		$pages  = ministry_department_design_pages();
		$design = isset( $_POST['design_page'] ) ? sanitize_title( wp_unslash( $_POST['design_page'] ) ) : '';
		if ( isset( $pages[ $design ] ) ) {
			update_post_meta( $id, '_mom_design', $design );
			update_post_meta( $id, '_mom_design_key', $design );
		} else {
			delete_post_meta( $id, '_mom_design' );
			delete_post_meta( $id, '_mom_design_key' );
		}
	}
	ministry_desk_log( 'updated ' . $postarr['post_title'] );
	return $id;
}

/**
 * Service-standard rows from the department editor.
 *
 * @return array<int, array{label: string, time: string}>
 */
function ministry_desk_standard_list() {
	$raw = isset( $_POST['standards'] ) ? wp_unslash( $_POST['standards'] ) : array();
	$out = array();
	if ( ! is_array( $raw ) ) {
		return $out;
	}
	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$label = sanitize_text_field( isset( $row['label'] ) ? (string) $row['label'] : '' );
		$time  = sanitize_text_field( isset( $row['time'] ) ? (string) $row['time'] : '' );
		if ( '' === $label && '' === $time ) {
			continue;
		}
		$out[] = array(
			'label' => $label,
			'time'  => $time,
		);
	}
	return $out;
}

/**
 * @return array<int, array{number: string, label: string}>
 */
function ministry_desk_phone_list() {
	$raw = isset( $_POST['phones'] ) ? wp_unslash( $_POST['phones'] ) : array();
	$out = array();
	if ( ! is_array( $raw ) ) {
		return $out;
	}
	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$number = sanitize_text_field( isset( $row['number'] ) ? (string) $row['number'] : '' );
		if ( '' === $number ) {
			continue;
		}
		$out[] = array(
			'number' => $number,
			'label'  => sanitize_text_field( isset( $row['label'] ) ? (string) $row['label'] : '' ),
		);
	}
	return $out;
}

/**
 * Save one parastatal or agency.
 */
function ministry_desk_save_entity() {
	if ( ! ministry_desk_can( 'agencies' ) ) {
		wp_die( esc_html__( 'Your role cannot edit agencies.', 'ministry-content' ) );
	}
	$settings = ministry_settings();
	$entities = isset( $settings['entities'] ) && is_array( $settings['entities'] ) ? $settings['entities'] : array();
	$index    = isset( $_POST['index'] ) ? (int) wp_unslash( $_POST['index'] ) : -1;
	$current  = ( $index >= 0 && isset( $entities[ $index ] ) && is_array( $entities[ $index ] ) ) ? $entities[ $index ] : array(
		'name'    => '',
		'acronym' => '',
		'desc'    => '',
		'href'    => '',
	);
	$current['name']    = sanitize_text_field( isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '' );
	$current['acronym'] = sanitize_text_field( isset( $_POST['acronym'] ) ? wp_unslash( $_POST['acronym'] ) : '' );
	$current['desc']    = sanitize_textarea_field( isset( $_POST['mandate'] ) ? wp_unslash( $_POST['mandate'] ) : '' );
	$current['href']    = ministry_sanitize_href( isset( $_POST['website'] ) ? wp_unslash( $_POST['website'] ) : '' );
	$current['type']    = sanitize_text_field( isset( $_POST['etype'] ) ? wp_unslash( $_POST['etype'] ) : '' );
	$current['act']     = sanitize_text_field( isset( $_POST['act'] ) ? wp_unslash( $_POST['act'] ) : '' );
	$current['chair']   = sanitize_text_field( isset( $_POST['chair'] ) ? wp_unslash( $_POST['chair'] ) : '' );
	$current['email']   = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' );
	$members = array();
	$rows    = isset( $_POST['members'] ) && is_array( $_POST['members'] ) ? wp_unslash( $_POST['members'] ) : array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$name = sanitize_text_field( isset( $row['name'] ) ? (string) $row['name'] : '' );
		if ( '' === $name ) {
			continue;
		}
		$members[] = array(
			'name' => $name,
			'role' => sanitize_text_field( isset( $row['role'] ) ? (string) $row['role'] : '' ),
		);
	}
	if ( $members ) {
		$current['members'] = $members;
		if ( 0 === strcasecmp( (string) $current['acronym'], 'MAB' ) ) {
			ministry_desk_patch_settings( array( 'mabMembers' => $members ) );
		}
	}
	$current['show']    = empty( $_POST['show'] ) ? '0' : '1';
	$current['invest']  = empty( $_POST['invest'] ) ? '0' : '1';
	$current['order']   = absint( isset( $_POST['sort'] ) ? wp_unslash( $_POST['sort'] ) : 0 );
	$logo = absint( isset( $_POST['logo_id'] ) ? wp_unslash( $_POST['logo_id'] ) : 0 );
	if ( $logo ) {
		$url = wp_get_attachment_image_url( $logo, 'medium' );
		$current['logoId']  = $logo;
		$current['logoUrl'] = $url ? $url : '';
	}
	$cover = absint( isset( $_POST['cover_id'] ) ? wp_unslash( $_POST['cover_id'] ) : 0 );
	if ( $cover ) {
		$url = wp_get_attachment_image_url( $cover, 'large' );
		$current['coverId']  = $cover;
		$current['coverUrl'] = $url ? $url : '';
	}
	if ( $index >= 0 && isset( $entities[ $index ] ) ) {
		$entities[ $index ] = $current;
	} else {
		$entities[] = $current;
	}
	ministry_desk_patch_settings( array( 'entities' => array_values( $entities ) ) );
	ministry_desk_log( 'updated ' . $current['acronym'] );
}

/**
 * @return int
 */
function ministry_desk_save_opportunity() {
	$id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	ministry_desk_guard_post( $id );
	$intent = isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'draft';
	$postarr = array(
		'post_type'    => 'mom_opportunity',
		'post_title'   => sanitize_text_field( isset( $_POST['post_title'] ) ? wp_unslash( $_POST['post_title'] ) : '' ),
		'post_excerpt' => sanitize_textarea_field( isset( $_POST['post_excerpt'] ) ? wp_unslash( $_POST['post_excerpt'] ) : '' ),
		'post_content' => wp_kses_post( isset( $_POST['post_content'] ) ? wp_unslash( $_POST['post_content'] ) : '' ),
		'post_status'  => ministry_desk_status_for( $intent, $id ),
	);
	if ( $id ) {
		$postarr['ID'] = $id;
		wp_update_post( $postarr );
	} else {
		$id = (int) wp_insert_post( $postarr );
	}
	if ( $id ) {
		update_post_meta( $id, '_mom_mineral', sanitize_text_field( isset( $_POST['mineral'] ) ? wp_unslash( $_POST['mineral'] ) : '' ) );
		update_post_meta( $id, '_mom_region', sanitize_text_field( isset( $_POST['region'] ) ? wp_unslash( $_POST['region'] ) : '' ) );
		update_post_meta( $id, '_mom_progress', absint( isset( $_POST['progress'] ) ? wp_unslash( $_POST['progress'] ) : 0 ) );
		update_post_meta( $id, '_mom_target', sanitize_text_field( isset( $_POST['target'] ) ? wp_unslash( $_POST['target'] ) : '' ) );
		update_post_meta( $id, '_mom_entity', sanitize_text_field( isset( $_POST['entity'] ) ? wp_unslash( $_POST['entity'] ) : '' ) );
	}
	return (int) $id;
}

/**
 * Website facts used across the public site.
 */
function ministry_desk_save_facts() {
	if ( ! ministry_desk_can( 'facts' ) ) {
		wp_die( esc_html__( 'Your role cannot edit website facts.', 'ministry-content' ) );
	}
	$stats = array();
	$rows  = isset( $_POST['stats'] ) ? wp_unslash( $_POST['stats'] ) : array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$value = sanitize_text_field( isset( $row['value'] ) ? (string) $row['value'] : '' );
			$label = sanitize_text_field( isset( $row['label'] ) ? (string) $row['label'] : '' );
			if ( '' === $value && '' === $label ) {
				continue;
			}
			$stats[] = array(
				'value'   => $value,
				'label'   => $label,
				'hint'    => sanitize_text_field( isset( $row['hint'] ) ? (string) $row['hint'] : '' ),
				'source'  => sanitize_text_field( isset( $row['source'] ) ? (string) $row['source'] : '' ),
				'checked' => sanitize_text_field( isset( $row['checked'] ) ? (string) $row['checked'] : '' ),
			);
		}
	}
	$social = array();
	$links  = isset( $_POST['social'] ) ? wp_unslash( $_POST['social'] ) : array();
	if ( is_array( $links ) ) {
		foreach ( $links as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = sanitize_text_field( isset( $row['label'] ) ? (string) $row['label'] : '' );
			if ( '' === $label ) {
				continue;
			}
			$social[] = array(
				'label' => $label,
				'href'  => ministry_sanitize_href( isset( $row['href'] ) ? (string) $row['href'] : '' ),
			);
		}
	}
	$assistant_url = isset( $_POST['assistantScriptUrl'] ) ? esc_url_raw( wp_unslash( $_POST['assistantScriptUrl'] ) ) : '';
	ministry_desk_patch_settings(
		array(
			'ministryName'       => sanitize_text_field( isset( $_POST['ministryName'] ) ? wp_unslash( $_POST['ministryName'] ) : '' ),
			'headOfficePhone'    => sanitize_text_field( isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '' ),
			'tollFree'           => sanitize_text_field( isset( $_POST['tollFree'] ) ? wp_unslash( $_POST['tollFree'] ) : '' ),
			'headOfficeEmail'    => sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' ),
			'ministerEmail'      => sanitize_email( isset( $_POST['ministerEmail'] ) ? wp_unslash( $_POST['ministerEmail'] ) : '' ),
			'officeHours'        => sanitize_text_field( isset( $_POST['officeHours'] ) ? wp_unslash( $_POST['officeHours'] ) : '' ),
			'homeStats'          => $stats,
			'socialLinks'        => $social,
			'assistantEnabled'   => ( isset( $_POST['assistantEnabled'] ) && '1' === (string) wp_unslash( $_POST['assistantEnabled'] ) ) ? '1' : '0',
			'assistantScriptUrl' => $assistant_url,
			'assistantTenant'    => sanitize_text_field( isset( $_POST['assistantTenant'] ) ? wp_unslash( $_POST['assistantTenant'] ) : '' ),
			'whatsappNumber'     => sanitize_text_field( isset( $_POST['whatsappNumber'] ) ? wp_unslash( $_POST['whatsappNumber'] ) : '' ),
			'downloadsEnabled'   => ( isset( $_POST['downloadsEnabled'] ) && '1' === (string) wp_unslash( $_POST['downloadsEnabled'] ) ) ? '1' : '0',
		)
	);
	$variation = isset( $_POST['variation'] ) ? sanitize_key( wp_unslash( $_POST['variation'] ) ) : 'a';
	ministry_desk_update_meta(
		array(
			'fax'       => sanitize_text_field( isset( $_POST['fax'] ) ? wp_unslash( $_POST['fax'] ) : '' ),
			'floor'     => sanitize_text_field( isset( $_POST['floor'] ) ? wp_unslash( $_POST['floor'] ) : '' ),
			'variation' => 'b' === $variation ? 'b' : 'a',
		)
	);
	$rows = isset( $_POST['banners'] ) ? wp_unslash( $_POST['banners'] ) : array();
	if ( is_array( $rows ) ) {
		$order = 0;
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$title = sanitize_text_field( isset( $row['title'] ) ? (string) $row['title'] : '' );
			$bid   = absint( isset( $row['id'] ) ? $row['id'] : 0 );
			if ( '' === $title && ! $bid ) {
				continue;
			}
			$banner = array(
				'post_type'   => 'mom_banner',
				'post_title'  => $title,
				'post_status' => 'publish',
				'menu_order'  => $order,
			);
			if ( $bid ) {
				$banner['ID'] = $bid;
				wp_update_post( $banner );
			} else {
				$bid = (int) wp_insert_post( $banner );
			}
			$image = absint( isset( $row['image'] ) ? $row['image'] : 0 );
			if ( $bid && $image ) {
				set_post_thumbnail( $bid, $image );
			}
			$order++;
		}
	}
	ministry_desk_log( 'updated website facts' );
}

/**
 * Update one enquiry.
 */
function ministry_desk_save_enquiry() {
	$id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	ministry_desk_guard_post( $id );
	if ( ! $id || 'mom_enquiry' !== get_post_type( $id ) ) {
		return;
	}
	$status = sanitize_key( isset( $_POST['mark'] ) ? wp_unslash( $_POST['mark'] ) : 'open' );
	if ( ! in_array( $status, array( 'open', 'assigned', 'replied', 'done' ), true ) ) {
		$status = 'open';
	}
	update_post_meta( $id, '_mom_status', $status );
	update_post_meta( $id, '_mom_due', sanitize_text_field( isset( $_POST['due'] ) ? wp_unslash( $_POST['due'] ) : '' ) );
	update_post_meta( $id, '_mom_note', sanitize_textarea_field( isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '' ) );
	ministry_desk_log( 'updated an enquiry' );
}

/**
 * Create a staff user.
 */
function ministry_desk_save_user() {
	if ( ! ministry_desk_can( 'users' ) ) {
		wp_die( esc_html__( 'Only an administrator can manage users.', 'ministry-content' ) );
	}
	$email = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' );
	$name  = sanitize_text_field( isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '' );
	$role  = sanitize_key( isset( $_POST['role'] ) ? wp_unslash( $_POST['role'] ) : 'author' );
	$roles = array_keys( ministry_desk_role_options() );
	$id    = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	if ( ! in_array( $role, $roles, true ) || ! is_email( $email ) ) {
		return;
	}
	if ( $id ) {
		$existing = get_userdata( $id );
		if ( ! $existing ) {
			return;
		}
		if ( 'administrator' === ministry_desk_user_role( $existing ) && 'administrator' !== $role ) {
			$admins = get_users( array( 'role' => 'administrator', 'fields' => 'ids' ) );
			if ( count( $admins ) < 2 ) {
				wp_die( esc_html__( 'Keep at least one administrator.', 'ministry-content' ) );
			}
		}
		$other = email_exists( $email );
		if ( $other && (int) $other !== $id ) {
			return;
		}
		wp_update_user(
			array(
				'ID'           => $id,
				'user_email'   => $email,
				'display_name' => $name ? $name : $existing->display_name,
				'role'         => $role,
			)
		);
		$user_id = $id;
		ministry_desk_log( 'updated a user' );
	} else {
		$user_id = email_exists( $email );
		if ( ! $user_id ) {
			$login   = sanitize_user( current( explode( '@', $email ) ), true );
			$base    = $login ? $login : 'staff';
			$try     = $base;
			$n       = 1;
			while ( username_exists( $try ) ) {
				$try = $base . $n;
				++$n;
			}
			$user_id = wp_insert_user(
				array(
					'user_login'   => $try,
					'user_email'   => $email,
					'display_name' => $name ? $name : $email,
					'role'         => $role,
					'user_pass'    => wp_generate_password( 20 ),
				)
			);
			if ( is_wp_error( $user_id ) ) {
				return;
			}
			wp_new_user_notification( (int) $user_id, null, 'user' );
		} else {
			wp_update_user(
				array(
					'ID'           => (int) $user_id,
					'display_name' => $name ? $name : get_userdata( (int) $user_id )->display_name,
					'role'         => $role,
				)
			);
		}
		ministry_desk_log( 'added a user' );
	}
	$user_id = (int) $user_id;
	if ( 'mom_custom' === $role ) {
		ministry_desk_store_custom_caps( $user_id, ministry_desk_posted_caps() );
	} else {
		delete_user_meta( $user_id, 'mom_desk_caps' );
		$user = new WP_User( $user_id );
		if ( 'author' === $role || 'mom_translator' === $role ) {
			$user->remove_cap( 'publish_posts' );
		}
		if ( 'mom_translator' === $role ) {
			$user->remove_cap( 'upload_files' );
		}
		if ( 'author' === $role || 'editor' === $role ) {
			$user->add_cap( 'upload_files' );
		}
	}
}

/**
 * Publish or return the ticked notices.
 */
function ministry_desk_save_bulk() {
	$ids    = isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) ? array_map( 'absint', wp_unslash( $_POST['ids'] ) ) : array();
	$action = isset( $_POST['bulk'] ) ? sanitize_key( wp_unslash( $_POST['bulk'] ) ) : '';
	foreach ( $ids as $id ) {
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			continue;
		}
		if ( 'publish' === $action && ministry_desk_can_publish() ) {
			wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		} elseif ( 'pending' === $action ) {
			wp_update_post( array( 'ID' => $id, 'post_status' => 'pending' ) );
		} elseif ( 'draft' === $action ) {
			wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
		} elseif ( 'trash' === $action && current_user_can( 'delete_post', $id ) ) {
			wp_trash_post( $id );
		}
	}
	ministry_desk_log( 'applied a bulk action' );
}

/**
 * Approve a pending post or person, or send it back.
 */
function ministry_desk_save_review() {
	if ( ! ministry_desk_can_publish() ) {
		wp_die( esc_html__( 'Only a Communications Director can approve.', 'ministry-content' ) );
	}
	$decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
	$note     = sanitize_textarea_field( isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '' );
	if ( isset( $_POST['person_key'] ) ) {
		$key  = sanitize_text_field( wp_unslash( $_POST['person_key'] ) );
		$meta = ministry_desk_meta();
		$pending = isset( $meta['person_pending'][ $key ] ) ? $meta['person_pending'][ $key ] : null;
		if ( $pending && 'approve' === $decision ) {
			$leaders = ministry_settings()['leaders'];
			if ( is_numeric( $key ) && isset( $leaders[ (int) $key ] ) ) {
				$leaders[ (int) $key ] = $pending['row'];
			} else {
				$leaders[] = $pending['row'];
			}
			ministry_desk_patch_settings( array( 'leaders' => array_values( $leaders ) ) );
		}
		unset( $meta['person_pending'][ $key ] );
		ministry_desk_update_meta( array( 'person_pending' => $meta['person_pending'] ) );
		ministry_desk_log( 'approve' === $decision ? 'approved a person' : 'requested changes to a person' );
		return;
	}
	$id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	ministry_desk_guard_post( $id );
	if ( ! $id ) {
		return;
	}
	wp_update_post(
		array(
			'ID'          => $id,
			'post_status' => 'approve' === $decision ? 'publish' : 'draft',
		)
	);
	update_post_meta( $id, '_mom_review_note', $note );
	ministry_desk_log( 'approve' === $decision ? 'approved a change' : 'requested changes' );
}

/**
 * @return int
 */
function ministry_desk_save_translate() {
	$id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	ministry_desk_guard_post( $id );
	$lang = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : 'sn';
	if ( ! $id || ! in_array( $lang, array( 'sn', 'nd' ), true ) ) {
		return 0;
	}
	$i18n = ministry_desk_i18n( $id );
	$i18n[ $lang ]['title']   = sanitize_text_field( isset( $_POST['tr_title'] ) ? wp_unslash( $_POST['tr_title'] ) : '' );
	$i18n[ $lang ]['excerpt'] = sanitize_textarea_field( isset( $_POST['tr_excerpt'] ) ? wp_unslash( $_POST['tr_excerpt'] ) : '' );
	$i18n[ $lang ]['content'] = wp_kses_post( isset( $_POST['tr_content'] ) ? wp_unslash( $_POST['tr_content'] ) : '' );
	$fields = isset( $_POST['tr_fields'] ) && is_array( $_POST['tr_fields'] ) ? wp_unslash( $_POST['tr_fields'] ) : array();
	$clean  = array();
	foreach ( $fields as $key => $value ) {
		$clean[ sanitize_key( (string) $key ) ] = sanitize_textarea_field( (string) $value );
	}
	$i18n[ $lang ]['fields'] = $clean;
	update_post_meta( $id, '_mom_i18n', $i18n );
	update_post_meta( $id, '_mom_i18n_stale', '0' );
	ministry_desk_log( 'saved a translation' );
	return $id;
}

/**
 * @return int
 */
function ministry_desk_save_page() {
	$id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	ministry_desk_guard_post( $id );
	$intent = isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'keep';
	$postarr = array(
		'post_type'    => 'page',
		'post_title'   => sanitize_text_field( isset( $_POST['post_title'] ) ? wp_unslash( $_POST['post_title'] ) : '' ),
		'post_excerpt' => sanitize_textarea_field( isset( $_POST['post_excerpt'] ) ? wp_unslash( $_POST['post_excerpt'] ) : '' ),
		'post_content' => wp_kses_post( isset( $_POST['post_content'] ) ? wp_unslash( $_POST['post_content'] ) : '' ),
		'post_status'  => ministry_desk_status_for( $intent, $id ),
	);
	if ( $id ) {
		$postarr['ID'] = $id;
		wp_update_post( $postarr );
		return $id;
	}
	return (int) wp_insert_post( $postarr );
}

/**
 * Download enquiries as a spreadsheet.
 */
function ministry_desk_export_enquiries() {
	$type     = isset( $_REQUEST['type'] ) ? sanitize_key( wp_unslash( $_REQUEST['type'] ) ) : '';
	$event_id = isset( $_REQUEST['event_id'] ) ? absint( wp_unslash( $_REQUEST['event_id'] ) ) : 0;
	$regs     = ministry_desk_is_registration_type( $type );
	$posts    = ministry_desk_posts( 'mom_enquiry', array( 'posts_per_page' => 500 ) );
	$slug     = $regs ? 'registrations' : ( $type ? $type : 'enquiries' );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $slug ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	if ( $regs ) {
		fputcsv( $out, array( 'Received', 'Event', 'Name', 'Email', 'Phone', 'Organisation', 'Notes', 'Status' ) );
	} else {
		fputcsv( $out, array( 'Received', 'Form', 'Subject', 'From', 'Email', 'Phone', 'Office', 'Organisation', 'Status', 'Message' ) );
	}
	foreach ( $posts as $post ) {
		$item_type = (string) get_post_meta( $post->ID, '_mom_type', true );
		if ( $regs ) {
			if ( ! ministry_desk_is_registration_type( $item_type ) ) {
				continue;
			}
			if ( $event_id && (int) get_post_meta( $post->ID, '_mom_event_id', true ) !== $event_id ) {
				continue;
			}
			fputcsv(
				$out,
				array(
					$post->post_date,
					(string) get_post_meta( $post->ID, '_mom_event', true ),
					(string) get_post_meta( $post->ID, '_mom_name', true ),
					(string) get_post_meta( $post->ID, '_mom_email', true ),
					(string) get_post_meta( $post->ID, '_mom_phone', true ),
					(string) get_post_meta( $post->ID, '_mom_org', true ),
					wp_strip_all_tags( $post->post_content ),
					(string) get_post_meta( $post->ID, '_mom_status', true ),
				)
			);
			continue;
		}
		if ( ministry_desk_is_registration_type( $item_type ) ) {
			continue;
		}
		if ( $type && $item_type !== $type ) {
			continue;
		}
		fputcsv(
			$out,
			array(
				$post->post_date,
				$item_type,
				$post->post_title,
				(string) get_post_meta( $post->ID, '_mom_name', true ),
				(string) get_post_meta( $post->ID, '_mom_email', true ),
				(string) get_post_meta( $post->ID, '_mom_phone', true ),
				(string) get_post_meta( $post->ID, '_mom_office', true ),
				(string) get_post_meta( $post->ID, '_mom_org', true ),
				(string) get_post_meta( $post->ID, '_mom_status', true ),
				wp_strip_all_tags( $post->post_content ),
			)
		);
	}
	fclose( $out );
	exit;
}

/**
 * Create an album without photos.
 *
 * @return int
 */
function ministry_desk_save_album_create() {
	$title = sanitize_text_field( isset( $_POST['post_title'] ) ? wp_unslash( $_POST['post_title'] ) : '' );
	if ( '' === $title ) {
		$title = 'Untitled album';
	}
	$id = (int) wp_insert_post(
		array(
			'post_type'   => 'mom_album',
			'post_title'  => $title,
			'post_status' => ministry_desk_status_for( isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'draft', 0 ),
		)
	);
	if ( ! $id ) {
		return 0;
	}
	update_post_meta( $id, '_mom_photos', array() );
	update_post_meta( $id, '_mom_year', sanitize_text_field( isset( $_POST['event_date'] ) ? wp_unslash( $_POST['event_date'] ) : wp_date( 'Y' ) ) );
	update_post_meta( $id, '_mom_album_cat', sanitize_text_field( isset( $_POST['album_cat'] ) ? wp_unslash( $_POST['album_cat'] ) : '' ) );
	update_post_meta( $id, '_mom_location', sanitize_text_field( isset( $_POST['location'] ) ? wp_unslash( $_POST['location'] ) : '' ) );
	update_post_meta( $id, '_mom_credit', sanitize_text_field( isset( $_POST['credit'] ) ? wp_unslash( $_POST['credit'] ) : 'MMMD Communications' ) );
	ministry_desk_log( 'created a photo album' );
	return $id;
}

/**
 * Assign uploaded photos to albums or keep them standalone.
 */
function ministry_desk_save_photos() {
	$rows = isset( $_POST['photos'] ) ? wp_unslash( $_POST['photos'] ) : array();
	if ( ! is_array( $rows ) ) {
		return;
	}
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$photo = absint( isset( $row['id'] ) ? $row['id'] : 0 );
		if ( ! $photo || ! wp_attachment_is_image( $photo ) ) {
			continue;
		}
		$alt = sanitize_text_field( isset( $row['alt'] ) ? (string) $row['alt'] : '' );
		if ( $alt ) {
			update_post_meta( $photo, '_wp_attachment_image_alt', $alt );
		}
		$caption = sanitize_text_field( isset( $row['caption'] ) ? (string) $row['caption'] : '' );
		if ( $caption ) {
			wp_update_post(
				array(
					'ID'           => $photo,
					'post_excerpt' => $caption,
				)
			);
		}
		$albums = array();
		if ( isset( $row['albums'] ) && is_array( $row['albums'] ) ) {
			foreach ( $row['albums'] as $album ) {
				$album = absint( $album );
				if ( $album && 'mom_album' === get_post_type( $album ) ) {
					$albums[] = $album;
				}
			}
		}
		ministry_photo_set_albums( $photo, $albums );
	}
	ministry_desk_log( 'updated gallery photos' );
}
