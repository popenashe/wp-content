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
