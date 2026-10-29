<?php
/**
 * One-shot import from design files into WordPress records.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_post_ministry_content_import', 'ministry_content_import_request' );

/**
 * Import design files into WordPress records. Call from a button or WP-CLI, never from init.
 *
 * @param bool $force Run even if a previous import finished.
 */
function ministry_content_import( $force = false ) {
	if ( ! $force && '1' === get_option( 'mom_content_imported' ) ) {
		return;
	}
	if ( function_exists( 'ministry_design_sync' ) ) {
		ministry_design_sync();
	}
	ministry_desk_hydrate_from_design();
	if ( function_exists( 'ministry_bind_capture_layout' ) ) {
		ministry_bind_capture_layout();
	}
	update_option( 'mom_content_imported', '1', false );
}

/**
 * Dashboard button handler.
 */
function ministry_content_import_request() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You cannot import the design.', 'ministry-content' ) );
	}
	check_admin_referer( 'ministry_content_import' );
	ministry_content_import( true );
	wp_safe_redirect( add_query_arg( 'imported', '1', admin_url( 'admin.php?page=mom-desk' ) ) );
	exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'ministry import',
		function () {
			ministry_content_import( true );
			WP_CLI::success( 'Imported design files into WordPress records.' );
		}
	);
}

/**
 * Fill empty WordPress records from the design pages they already display.
 */
function ministry_desk_hydrate_from_design() {
	if ( ! function_exists( 'ministry_site_document' ) ) {
		return;
	}
	$map = array(
		'index'                     => array( 'home', 'Home' ),
		'about'                     => array( 'about', 'About Us' ),
		'the-ministry'              => array( 'ministry', 'The Ministry' ),
		'parastatals'               => array( 'entities', 'Parastatals & Agencies' ),
		'departments'               => array( 'departments', 'Departments' ),
		'offices'                   => array( 'offices', 'Offices' ),
		'invest'                    => array( 'invest', 'Investments' ),
		'services'                  => array( 'services', 'Services' ),
		'how-to-get-a-mining-claim' => array( 'how-to-get-a-mining-claim', 'How to get a mining claim' ),
		'news'                      => array( 'news', 'News & public notices' ),
		'events'                    => array( 'events', 'Events' ),
		'gallery'                   => array( 'gallery', 'Photo gallery' ),
		'resources'                 => array( 'resources', 'Resources' ),
		'contact'                   => array( 'contact', 'Contact' ),
		'faq'                       => array( 'faq', 'FAQ' ),
	);
	foreach ( $map as $file => $row ) {
		$page = ministry_desk_ensure_page( $row[0], $row[1] );
		if ( ! $page instanceof WP_Post ) {
			continue;
		}
		ministry_desk_hydrate_post_from_file( $page, $file );
	}
	$news = get_posts(
		array(
			'post_type'      => 'mom_news',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 200,
		)
	);
	foreach ( $news as $post ) {
		$key = (string) get_post_meta( $post->ID, '_mom_design_key', true );
		if ( $key ) {
			ministry_desk_hydrate_post_from_file( $post, 'news-' . $key );
		}
	}
	$units = get_posts(
		array(
			'post_type'      => 'mom_department',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 200,
		)
	);
	foreach ( $units as $post ) {
		$slug = (string) get_post_meta( $post->ID, '_mom_design', true );
		if ( $slug ) {
			$file = 0 === strpos( $slug, 'department-' ) ? $slug : 'department-' . $slug;
			ministry_desk_hydrate_post_from_file( $post, $file );
		}
	}
}

/**
 * @param WP_Post $post Record.
 * @param string  $file Design file name without .html.
 */
function ministry_desk_hydrate_post_from_file( $post, $file ) {
	if ( get_post_meta( $post->ID, '_mom_design_html', true ) ) {
		return;
	}
	$html = ministry_desk_design_main_html( $file );
	if ( '' === $html ) {
		return;
	}
	$plain  = trim( wp_strip_all_tags( $post->post_content ) );
	$design = trim( wp_strip_all_tags( $html ) );
	if ( '' !== $plain && strlen( $plain ) >= min( 400, (int) ( strlen( $design ) * 0.45 ) ) ) {
		update_post_meta( $post->ID, '_mom_design_html', $html );
		return;
	}
	wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_content' => $html,
		)
	);
	update_post_meta( $post->ID, '_mom_design_html', $html );
}

/**
 * Inner HTML of #main from a design file.
 *
 * @param string $name File name without .html.
 */
function ministry_desk_design_main_html( $name ) {
	if ( ! function_exists( 'ministry_site_document' ) || ! function_exists( 'ministry_site_dir' ) ) {
		return '';
	}
	$path = ministry_site_dir() . $name . '.html';
	if ( ! is_readable( $path ) ) {
		return '';
	}
	$doc = ministry_site_document( $name );
	if ( empty( $doc['body'] ) ) {
		return '';
	}
	if ( ! preg_match( '#<main id="main"[^>]*>(.*)</main>#s', (string) $doc['body'], $match ) ) {
		return '';
	}
	return trim( $match[1] );
}

/**
 * Publish a page for a design slug if it is missing.
 *
 * @param string $slug  Page slug.
 * @param string $title Page title.
 * @return WP_Post|null
 */
function ministry_desk_ensure_page( $slug, $title ) {
	$page = get_page_by_path( $slug );
	if ( $page instanceof WP_Post ) {
		return $page;
	}
	$id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_name'   => $slug,
		)
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return null;
	}
	$page = get_post( $id );
	return $page instanceof WP_Post ? $page : null;
}

/**
 * WordPress page that owns a design file, if any.
 *
 * @param string $name Design file name.
 * @return WP_Post|null
 */
function ministry_desk_page_for_design( $name ) {
	$map = array(
		'index'                     => 'home',
		'about'                     => 'about',
		'the-ministry'              => 'ministry',
		'parastatals'               => 'entities',
		'departments'               => 'departments',
		'offices'                   => 'offices',
		'invest'                    => 'invest',
		'services'                  => 'services',
		'how-to-get-a-mining-claim' => 'how-to-get-a-mining-claim',
		'news'                      => 'news',
		'events'                    => 'events',
		'gallery'                   => 'gallery',
		'resources'                 => 'resources',
		'contact'                   => 'contact',
		'faq'                       => 'faq',
	);
	if ( ! isset( $map[ $name ] ) ) {
		return null;
	}
	$page = get_page_by_path( $map[ $name ] );
	return $page instanceof WP_Post ? $page : null;
}
