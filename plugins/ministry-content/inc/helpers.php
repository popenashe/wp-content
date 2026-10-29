<?php
/**
 * Read API used by the theme.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Merged site settings. Stored values that no longer match the default shape are dropped.
 *
 * @return array<string, mixed>
 */
function ministry_settings() {
	$stored = get_option( 'mom_site_settings', array() );
	$merged = ministry_merge_settings( is_array( $stored ) ? $stored : array() );
	if ( ! empty( $merged['logoId'] ) ) {
		$url = wp_get_attachment_image_url( (int) $merged['logoId'], 'full' );
		if ( $url ) {
			$merged['logoUrl'] = $url;
		}
	}
	return $merged;
}

/**
 * Leaders list from settings.
 *
 * @return array<int, array<string, mixed>>
 */
function ministry_leaders() {
	$settings = ministry_settings();
	return isset( $settings['leaders'] ) && is_array( $settings['leaders'] ) ? $settings['leaders'] : array();
}

/**
 * Agencies and parastatals from settings.
 *
 * @return array<int, array<string, mixed>>
 */
function ministry_entities() {
	$settings = ministry_settings();
	return isset( $settings['entities'] ) && is_array( $settings['entities'] ) ? $settings['entities'] : array();
}

/**
 * Eyebrow, title, and intro for a public page.
 *
 * @param string $key Page key.
 * @return array{eyebrow: string, title: string, intro: string, notice: string}
 */
function ministry_page_copy( $key ) {
	$settings = ministry_settings();
	$pages    = isset( $settings['pages'] ) && is_array( $settings['pages'] ) ? $settings['pages'] : array();
	$copy     = isset( $pages[ $key ] ) && is_array( $pages[ $key ] ) ? $pages[ $key ] : array();
	return array(
		'eyebrow' => isset( $copy['eyebrow'] ) ? (string) $copy['eyebrow'] : '',
		'title'   => isset( $copy['title'] ) ? (string) $copy['title'] : '',
		'intro'   => isset( $copy['intro'] ) ? (string) $copy['intro'] : '',
		'notice'  => isset( $copy['notice'] ) ? (string) $copy['notice'] : '',
	);
}

/**
 * Published posts of a ministry type.
 *
 * @param string               $type Post type.
 * @param array<string, mixed> $args Extra get_posts args.
 * @return WP_Post[]
 */
function ministry_posts( $type, $args = array() ) {
	$query = array_merge(
		array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		),
		$args
	);
	return get_posts( $query );
}

/**
 * Public department pages and the description photo already on each one.
 *
 * @return array<string, array{label: string, src: string}>
 */
function ministry_department_design_pages() {
	static $pages = null;
	if ( null !== $pages ) {
		return $pages;
	}
	$pages = array();
	$dir   = get_template_directory() . '/site/';
	foreach ( (array) glob( $dir . 'department-*.html' ) as $file ) {
		$slug = substr( basename( $file, '.html' ), strlen( 'department-' ) );
		$html = (string) file_get_contents( $file );
		$label = $slug;
		if ( preg_match( '#<h1[^>]*>(.*?)</h1>#s', $html, $heading ) ) {
			$label = trim( wp_strip_all_tags( $heading[1] ) );
		}
		$src = '';
		if ( preg_match( '#<img\b[^>]*\bsrc="([^"]+)"[^>]*aspect-ratio:\s*16/6#', $html, $image ) ) {
			$src = get_template_directory_uri() . '/site/' . $image[1];
		}
		$pages[ $slug ] = array(
			'label' => $label,
			'src'   => $src,
		);
	}
	uasort(
		$pages,
		function ( $a, $b ) {
			return strcasecmp( $a['label'], $b['label'] );
		}
	);
	return $pages;
}

/**
 * Design-page slug for a department, when one is already known.
 *
 * @param WP_Post $post Department.
 */
function ministry_department_design_slug( $post ) {
	$pages  = ministry_department_design_pages();
	$stored = (string) get_post_meta( $post->ID, '_mom_design', true );
	if ( isset( $pages[ $stored ] ) ) {
		return $stored;
	}
	$name = $post->post_name;
	if ( isset( $pages[ $name ] ) ) {
		return $name;
	}
	if ( str_starts_with( $name, 'department-' ) ) {
		$short = substr( $name, strlen( 'department-' ) );
		if ( isset( $pages[ $short ] ) ) {
			return $short;
		}
	}
	return '';
}

/**
 * Head office and provincial offices.
 *
 * @param bool|null $provincial Null for all, true for offices, false for head office.
 * @return WP_Post[]
 */
function ministry_departments( $provincial = null ) {
	$args = array(
		'orderby' => 'menu_order title',
		'order'   => 'ASC',
	);
	if ( null !== $provincial ) {
		$args['meta_key']   = '_mom_provincial';
		$args['meta_value'] = $provincial ? '1' : '0';
	}
	return ministry_posts( 'mom_department', $args );
}

/**
 * Departments, Harare centres, or provincial offices in saved display order.
 *
 * @param string $kind department|centre|province.
 * @return WP_Post[]
 */
function ministry_structure_query( $kind ) {
	$args = array(
		'orderby' => 'menu_order title',
		'order'   => 'ASC',
	);
	if ( 'province' === $kind ) {
		$args['meta_key']   = '_mom_provincial';
		$args['meta_value'] = '1';
	} elseif ( 'centre' === $kind ) {
		$args['meta_key']   = '_mom_centre';
		$args['meta_value'] = '1';
	} else {
		$args['meta_query'] = array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'key'     => '_mom_provincial',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_mom_provincial',
					'value'   => '1',
					'compare' => '!=',
				),
			),
			array(
				'relation' => 'OR',
				array(
					'key'     => '_mom_centre',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_mom_centre',
					'value'   => '1',
					'compare' => '!=',
				),
			),
		);
	}
	return $args;
}

/**
 * @param string $kind department|centre|province.
 * @return WP_Post[]
 */
function ministry_structure_posts( $kind ) {
	return ministry_structure_apply_display_order( ministry_posts( 'mom_department', ministry_structure_query( $kind ) ) );
}

/**
 * Keep design order until an editor saves a custom order.
 *
 * @param WP_Post[] $posts Records.
 * @return WP_Post[]
 */
function ministry_structure_apply_display_order( $posts ) {
	if ( ! $posts || ministry_posts_have_menu_order( $posts ) ) {
		return $posts;
	}
	$html = '';
	foreach ( $posts as $post ) {
		$centre = '1' === (string) get_post_meta( $post->ID, '_mom_centre', true );
		$prov   = '1' === (string) get_post_meta( $post->ID, '_mom_provincial', true );
		$file   = $centre || $prov ? 'offices.html' : 'departments.html';
		$path   = get_template_directory() . '/site/' . $file;
		if ( is_readable( $path ) ) {
			$html = (string) file_get_contents( $path );
		}
		break;
	}
	usort(
		$posts,
		function ( $a, $b ) use ( $html ) {
			$pa = ministry_structure_html_pos( $html, $a );
			$pb = ministry_structure_html_pos( $html, $b );
			if ( $pa === $pb ) {
				return strcasecmp( $a->post_title, $b->post_title );
			}
			return $pa <=> $pb;
		}
	);
	return $posts;
}

/**
 * @param WP_Post[] $posts Records.
 */
function ministry_posts_have_menu_order( $posts ) {
	$orders = array();
	foreach ( $posts as $post ) {
		$orders[] = (int) $post->menu_order;
	}
	$unique = array_values( array_unique( $orders ) );
	return count( $unique ) > 1 || ( 1 === count( $unique ) && isset( $unique[0] ) && 0 !== $unique[0] );
}

/**
 * @param WP_Post $post Department or office.
 */
function ministry_structure_card_label( $post ) {
	$key  = (string) get_post_meta( $post->ID, '_mom_design_key', true );
	$base = ministry_design_baseline();
	if ( $key && isset( $base['departments'][ $key ]['list_title'] ) && '' !== (string) $base['departments'][ $key ]['list_title'] ) {
		return (string) $base['departments'][ $key ]['list_title'];
	}
	if ( $key && isset( $base['offices'][ $key ]['title'] ) && '' !== (string) $base['offices'][ $key ]['title'] ) {
		return (string) $base['offices'][ $key ]['title'];
	}
	return $post->post_title;
}

/**
 * @param string  $html Markup.
 * @param WP_Post $post Record.
 */
function ministry_structure_html_pos( $html, $post ) {
	$needles = array_unique( array_filter( array( ministry_structure_card_label( $post ), $post->post_title ) ) );
	$best    = PHP_INT_MAX;
	foreach ( $needles as $needle ) {
		$pos = $html ? strpos( $html, $needle ) : false;
		if ( false !== $pos && $pos < $best ) {
			$best = $pos;
		}
	}
	return $best;
}

/**
 * Photos stored on an album, in editor order.
 *
 * @param int $post_id Album ID.
 * @return array<int, array{id: int, url: string, full: string, caption: string, alt: string, home: bool}>
 */
function ministry_album_photos( $post_id ) {
	$raw = get_post_meta( $post_id, '_mom_photos', true );
	if ( ! is_array( $raw ) ) {
		return array();
	}
	$photos = array();
	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$id = isset( $row['id'] ) ? (int) $row['id'] : 0;
		if ( ! $id ) {
			continue;
		}
		$url  = wp_get_attachment_image_url( $id, 'large' );
		$full = wp_get_attachment_url( $id );
		if ( ! $url ) {
			$url = $full;
		}
		if ( ! $url || ! $full ) {
			continue;
		}
		$caption  = isset( $row['caption'] ) ? (string) $row['caption'] : '';
		$alt      = isset( $row['alt'] ) ? (string) $row['alt'] : '';
		$photos[] = array(
			'id'      => $id,
			'url'     => $url,
			'full'    => $full,
			'caption' => $caption,
			'alt'     => '' !== $alt ? $alt : $caption,
			'home'    => ! empty( $row['home'] ) && '0' !== (string) $row['home'],
		);
	}
	return $photos;
}

/**
 * Published albums in display order.
 *
 * @return array<int, array{id: int, title: string, slug: string, year: string, layout: string, photos: array<int, array<string, mixed>>}>
 */
function ministry_albums() {
	$posts  = ministry_posts(
		'mom_album',
		array(
			'orderby' => 'menu_order',
			'order'   => 'ASC',
		)
	);
	$albums = array();
	foreach ( $posts as $post ) {
		$layout   = (string) get_post_meta( $post->ID, '_mom_layout', true );
		$albums[] = array(
			'id'     => $post->ID,
			'title'  => $post->post_title,
			'slug'   => $post->post_name,
			'year'   => (string) get_post_meta( $post->ID, '_mom_year', true ),
			'layout' => 'wide' === $layout ? 'wide' : 'portrait',
			'photos' => ministry_album_photos( $post->ID ),
		);
	}
	return $albums;
}

/**
 * Album IDs a gallery photo belongs to.
 *
 * @param int $photo_id Attachment ID.
 * @return int[]
 */
function ministry_photo_album_ids( $photo_id ) {
	$ids = get_post_meta( $photo_id, '_mom_album_ids', true );
	if ( ! is_array( $ids ) ) {
		$ids = array();
	}
	return array_values( array_filter( array_map( 'absint', $ids ) ) );
}

/**
 * Mark a photo as part of the public gallery.
 *
 * @param int $photo_id Attachment ID.
 */
function ministry_photo_mark_gallery( $photo_id ) {
	update_post_meta( $photo_id, '_mom_gallery', '1' );
}

/**
 * Add a photo to an album's ordered list.
 *
 * @param int $photo_id Attachment ID.
 * @param int $album_id Album ID.
 */
function ministry_photo_add_to_album( $photo_id, $album_id ) {
	$photo_id = (int) $photo_id;
	$album_id = (int) $album_id;
	if ( ! $photo_id || ! $album_id ) {
		return;
	}
	ministry_photo_mark_gallery( $photo_id );
	$ids = ministry_photo_album_ids( $photo_id );
	if ( ! in_array( $album_id, $ids, true ) ) {
		$ids[] = $album_id;
		update_post_meta( $photo_id, '_mom_album_ids', $ids );
	}
	$photos = get_post_meta( $album_id, '_mom_photos', true );
	if ( ! is_array( $photos ) ) {
		$photos = array();
	}
	foreach ( $photos as $row ) {
		if ( is_array( $row ) && (int) $row['id'] === $photo_id ) {
			return;
		}
	}
	$photos[] = array(
		'id'      => $photo_id,
		'caption' => (string) get_post_field( 'post_excerpt', $photo_id ),
		'alt'     => (string) get_post_meta( $photo_id, '_wp_attachment_image_alt', true ),
		'home'    => '0',
	);
	update_post_meta( $album_id, '_mom_photos', $photos );
}

/**
 * Remove a photo from one album, keeping it in the gallery if it still has albums or is standalone.
 *
 * @param int $photo_id Attachment ID.
 * @param int $album_id Album ID.
 */
function ministry_photo_remove_from_album( $photo_id, $album_id ) {
	$photo_id = (int) $photo_id;
	$album_id = (int) $album_id;
	$ids      = array_values( array_diff( ministry_photo_album_ids( $photo_id ), array( $album_id ) ) );
	update_post_meta( $photo_id, '_mom_album_ids', $ids );
	ministry_photo_mark_gallery( $photo_id );
	$photos = get_post_meta( $album_id, '_mom_photos', true );
	if ( ! is_array( $photos ) ) {
		return;
	}
	$next = array();
	foreach ( $photos as $row ) {
		if ( is_array( $row ) && (int) $row['id'] !== $photo_id ) {
			$next[] = $row;
		}
	}
	update_post_meta( $album_id, '_mom_photos', $next );
}

/**
 * Replace the albums a photo belongs to.
 *
 * @param int   $photo_id Attachment ID.
 * @param int[] $album_ids Album IDs.
 */
function ministry_photo_set_albums( $photo_id, $album_ids ) {
	$photo_id  = (int) $photo_id;
	$album_ids = array_values( array_unique( array_filter( array_map( 'absint', $album_ids ) ) ) );
	$current   = ministry_photo_album_ids( $photo_id );
	foreach ( $current as $album_id ) {
		if ( ! in_array( $album_id, $album_ids, true ) ) {
			ministry_photo_remove_from_album( $photo_id, $album_id );
		}
	}
	foreach ( $album_ids as $album_id ) {
		ministry_photo_add_to_album( $photo_id, $album_id );
	}
	ministry_photo_mark_gallery( $photo_id );
	update_post_meta( $photo_id, '_mom_album_ids', $album_ids );
}

/**
 * Drop album membership when a photo is deleted.
 *
 * @param int $photo_id Attachment ID.
 */
function ministry_photo_detach( $photo_id ) {
	foreach ( ministry_photo_album_ids( $photo_id ) as $album_id ) {
		ministry_photo_remove_from_album( $photo_id, $album_id );
	}
	delete_post_meta( $photo_id, '_mom_gallery' );
	delete_post_meta( $photo_id, '_mom_album_ids' );
}

/**
 * Gallery photos, including those not in an album.
 *
 * @return WP_Post[]
 */
function ministry_gallery_photos() {
	foreach ( get_posts( array( 'post_type' => 'mom_album', 'post_status' => 'any', 'posts_per_page' => 200, 'fields' => 'ids' ) ) as $album_id ) {
		foreach ( ministry_album_photos( (int) $album_id ) as $photo ) {
			ministry_photo_add_to_album( (int) $photo['id'], (int) $album_id );
		}
	}
	return get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'posts_per_page' => 300,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_key'       => '_mom_gallery',
			'meta_value'     => '1',
		)
	);
}

/**
 * Homepage banners in menu order.
 *
 * @return WP_Post[]
 */
function ministry_banners() {
	return ministry_posts(
		'mom_banner',
		array(
			'orderby' => 'menu_order',
			'order'   => 'ASC',
		)
	);
}

/**
 * FAQs in menu order.
 *
 * @return WP_Post[]
 */
function ministry_faqs() {
	return ministry_posts(
		'mom_faq',
		array(
			'orderby' => 'menu_order',
			'order'   => 'ASC',
		)
	);
}

/**
 * Upcoming and recent events, soonest first.
 *
 * @return WP_Post[]
 */
function ministry_events() {
	return ministry_posts(
		'mom_event',
		array(
			'meta_key' => '_mom_starts_at',
			'orderby'  => 'meta_value',
			'order'    => 'ASC',
		)
	);
}

/**
 * Featured image URL, or an empty string.
 *
 * @param int    $post_id Post ID.
 * @param string $size    Image size.
 */
function ministry_thumb_url( $post_id, $size = 'large' ) {
	$url = get_the_post_thumbnail_url( $post_id, $size );
	return $url ? $url : '';
}

/**
 * Public file URL for a document.
 *
 * @param int $post_id Document ID.
 */
function ministry_document_url( $post_id ) {
	$file_id = (int) get_post_meta( $post_id, '_mom_file_id', true );
	if ( $file_id ) {
		$url = wp_get_attachment_url( $file_id );
		if ( $url ) {
			return $url;
		}
	}
	return (string) get_post_meta( $post_id, '_mom_file_url', true );
}

/**
 * Organogram tree. A holder of "@offices" expands to provincial offices.
 *
 * @return array<int, array<string, mixed>>
 */
function ministry_org_tree() {
	$settings = ministry_settings();
	$nodes    = isset( $settings['organogram'] ) && is_array( $settings['organogram'] ) ? $settings['organogram'] : array();
	$offices  = ministry_departments( true );
	$titles   = array();
	foreach ( $nodes as $node ) {
		if ( ! empty( $node['title'] ) ) {
			$titles[ $node['title'] ] = true;
		}
	}

	$build = function ( $node, $seen ) use ( &$build, $nodes, $offices ) {
		$seen[ $node['title'] ] = true;
		$children                = array();
		if ( isset( $node['holder'] ) && '@offices' === $node['holder'] ) {
			foreach ( $offices as $office ) {
				$label = (string) preg_replace( '/ Provincial Mining Office$/', '', $office->post_title );
				$children[] = array(
					'title'    => $label,
					'holder'   => 'Provincial Mining Director',
					'href'     => get_permalink( $office ),
					'children' => array(),
				);
			}
		} else {
			foreach ( $nodes as $child ) {
				if ( empty( $child['title'] ) || empty( $child['parent'] ) ) {
					continue;
				}
				if ( $child['parent'] !== $node['title'] || isset( $seen[ $child['title'] ] ) ) {
					continue;
				}
				$children[] = $build( $child, $seen );
			}
		}
		$holder = isset( $node['holder'] ) ? (string) $node['holder'] : '';
		if ( '@offices' === $holder ) {
			$holder = count( $offices ) . ' provincial offices';
		}
		return array(
			'title'    => (string) $node['title'],
			'holder'   => $holder,
			'href'     => '',
			'children' => $children,
		);
	};

	$roots = array();
	foreach ( $nodes as $node ) {
		if ( empty( $node['title'] ) ) {
			continue;
		}
		$parent = isset( $node['parent'] ) ? (string) $node['parent'] : '';
		if ( '' === $parent || ! isset( $titles[ $parent ] ) ) {
			$roots[] = $build( $node, array() );
		}
	}
	return $roots;
}

/**
 * Human label for a document category slug.
 *
 * @param string $slug Category key.
 */
function ministry_document_category_label( $slug ) {
	$labels = array(
		'policies'     => 'Policy documents',
		'legislation'  => 'Mines Act',
		'forms'        => 'Staff forms',
		'reports'      => 'Reports',
		'publications' => 'Public notices',
		'guidelines'   => 'Regulations',
	);
	return isset( $labels[ $slug ] ) ? $labels[ $slug ] : ucfirst( $slug );
}

/**
 * Keep site-relative paths, hashes, and absolute URLs.
 *
 * @param string $value Raw link.
 */
function ministry_sanitize_href( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value || '#' === $value ) {
		return $value;
	}
	if ( str_starts_with( $value, '#' ) ) {
		return '#' . sanitize_title( substr( $value, 1 ) );
	}
	if ( str_starts_with( $value, '/' ) ) {
		$path = wp_parse_url( $value );
		$out  = isset( $path['path'] ) ? '/' . ltrim( (string) $path['path'], '/' ) : '/';
		if ( ! empty( $path['fragment'] ) ) {
			$out .= '#' . sanitize_title( $path['fragment'] );
		}
		return $out;
	}
	if ( ! preg_match( '#^[a-z][a-z0-9+.-]*:#i', $value ) && preg_match( '#^(www\.)?[a-z0-9.-]+\.[a-z]{2,}#i', $value ) ) {
		$value = 'https://' . $value;
	}
	return esc_url_raw( $value );
}

/**
 * Format a stored datetime for display.
 *
 * @param string $mysql MySQL datetime.
 */
function ministry_format_date( $mysql ) {
	if ( ! $mysql ) {
		return '';
	}
	$ts = strtotime( $mysql );
	if ( ! $ts ) {
		return '';
	}
	return date_i18n( 'j F Y', $ts );
}

/**
 * Designed public URL for a desk/wp-admin Preview, or an empty string.
 *
 * @param WP_Post $post Record.
 */
function ministry_public_record_url( $post ) {
	if ( ! $post instanceof WP_Post ) {
		return '';
	}
	$site = function_exists( 'ministry_site_url' ) ? 'ministry_site_url' : null;
	$page = function ( $name ) use ( $site ) {
		return $site ? $site( $name ) : home_url( 'index' === $name ? '/' : '/' . $name . '/' );
	};
	$hash = function ( $name, $anchor ) use ( $page ) {
		$base = $page( $name );
		return $anchor ? $base . '#' . rawurlencode( $anchor ) : $base;
	};
	switch ( $post->post_type ) {
		case 'mom_news':
			return function_exists( 'ministry_front_news_link' ) ? ministry_front_news_link( $post ) : home_url( '/news/' . $post->post_name . '/' );
		case 'mom_department':
			$office = '1' === (string) get_post_meta( $post->ID, '_mom_provincial', true ) || '1' === (string) get_post_meta( $post->ID, '_mom_centre', true );
			if ( $office ) {
				return $hash( 'offices', ministry_public_anchor( $post ) );
			}
			return function_exists( 'ministry_front_department_link' ) ? ministry_front_department_link( $post ) : $page( 'departments' );
		case 'mom_service':
			return $hash( 'services', ministry_public_anchor( $post ) );
		case 'mom_event':
			return $hash( 'events', ministry_public_anchor( $post ) );
		case 'mom_album':
			return $hash( 'gallery', ministry_public_anchor( $post ) );
		case 'mom_document':
			return $hash( 'resources', ministry_public_anchor( $post ) );
		case 'mom_opportunity':
			$key     = function_exists( 'ministry_front_record_key' ) ? ministry_front_record_key( $post ) : '';
			$surface = '';
			if ( $key && function_exists( 'ministry_design_baseline' ) ) {
				$base = ministry_design_baseline();
				if ( isset( $base['opportunities'][ $key ]['surface'] ) ) {
					$surface = (string) $base['opportunities'][ $key ]['surface'];
				}
			}
			return $hash( 'home' === $surface ? 'index' : 'invest', ministry_public_anchor( $post ) );
		case 'mom_faq':
			return $hash( 'faq', ministry_public_anchor( $post ) );
		case 'mom_banner':
		case 'mom_media':
			return 'mom_banner' === $post->post_type ? $page( 'index' ) : $page( 'gallery' );
		case 'page':
			return ministry_public_wp_page_url( $post );
	}
	return '';
}

/**
 * Fragment used on listing pages.
 *
 * @param WP_Post $post Record.
 */
function ministry_public_anchor( $post ) {
	if ( ! $post instanceof WP_Post ) {
		return '';
	}
	if ( 'mom_department' === $post->post_type ) {
		$office = '1' === (string) get_post_meta( $post->ID, '_mom_provincial', true ) || '1' === (string) get_post_meta( $post->ID, '_mom_centre', true );
		if ( $office ) {
			$short = preg_replace( '/\s+(Provincial Mining Office|Mining Office)$/i', '', (string) $post->post_title );
			$slug  = sanitize_title( is_string( $short ) && '' !== $short ? $short : (string) $post->post_title );
			if ( $slug ) {
				return $slug;
			}
		}
	}
	if ( in_array( $post->post_type, array( 'mom_opportunity', 'mom_faq', 'mom_event', 'mom_service', 'mom_document' ), true ) ) {
		$slug = sanitize_title( $post->post_title );
		if ( $slug ) {
			return $slug;
		}
	}
	if ( $post->post_name ) {
		return $post->post_name;
	}
	return sanitize_title( $post->post_title );
}

/**
 * Designed URL for a WordPress page that owns a public section.
 *
 * @param WP_Post $post Page.
 */
function ministry_public_wp_page_url( $post ) {
	$slug = $post->post_name;
	$parent = $post->post_parent ? get_post( $post->post_parent ) : null;
	$path   = ( $parent instanceof WP_Post ) ? $parent->post_name . '/' . $slug : $slug;
	$map    = array(
		'home'                      => 'index',
		'about'                     => 'about',
		'ministry'                  => 'the-ministry',
		'entities'                  => 'parastatals',
		'invest'                    => 'invest',
		'services'                  => 'services',
		'contact'                   => 'contact',
		'faq'                       => 'faq',
		'resources'                 => 'resources',
		'news'                      => 'news',
		'events'                    => 'events',
		'gallery'                   => 'gallery',
		'departments'               => 'departments',
		'offices'                   => 'offices',
		'how-to-get-a-mining-claim' => 'how-to-get-a-mining-claim',
		'the-ministry'              => 'the-ministry',
		'parastatals'               => 'parastatals',
	);
	if ( 'structure' === $slug || 'about/structure' === $path ) {
		return home_url( '/about/#structure' );
	}
	if ( isset( $map[ $slug ] ) ) {
		return function_exists( 'ministry_site_url' ) ? ministry_site_url( $map[ $slug ] ) : home_url( '/' . $map[ $slug ] . '/' );
	}
	if ( isset( $map[ $path ] ) ) {
		return function_exists( 'ministry_site_url' ) ? ministry_site_url( $map[ $path ] ) : home_url( '/' . $map[ $path ] . '/' );
	}
	return '';
}
