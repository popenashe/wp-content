<?php
/**
 * Custom post types for the public portal.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register every content type the public site reads.
 */
function ministry_register_post_types() {
	$common = array(
		'public'       => true,
		'show_in_rest' => false,
		'has_archive'  => true,
		'menu_icon'    => 'dashicons-admin-site-alt3',
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
	);

	register_post_type(
		'mom_news',
		array_merge(
			$common,
			array(
				'labels'      => ministry_labels( 'News article', 'News' ),
				'rewrite'     => array( 'slug' => 'news' ),
				'menu_icon'   => 'dashicons-megaphone',
			)
		)
	);

	register_post_type(
		'mom_event',
		array_merge(
			$common,
			array(
				'labels'    => ministry_labels( 'Event', 'Events' ),
				'rewrite'   => array( 'slug' => 'events' ),
				'menu_icon' => 'dashicons-calendar-alt',
			)
		)
	);

	register_post_type(
		'mom_document',
		array_merge(
			$common,
			array(
				'labels'      => ministry_labels( 'Document', 'Documents' ),
				'has_archive' => false,
				'rewrite'     => array( 'slug' => 'document' ),
				'menu_icon'   => 'dashicons-media-document',
				'supports'    => array( 'title', 'excerpt', 'revisions' ),
			)
		)
	);

	register_post_type(
		'mom_department',
		array_merge(
			$common,
			array(
				'labels'    => ministry_labels( 'Department', 'Departments' ),
				'rewrite'   => array( 'slug' => 'departments' ),
				'menu_icon' => 'dashicons-building',
				'supports'  => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			)
		)
	);

	register_post_type(
		'mom_media',
		array_merge(
			$common,
			array(
				'labels'       => ministry_labels( 'Gallery image', 'Gallery' ),
				'rewrite'      => array( 'slug' => 'media' ),
				'show_in_menu' => false,
				'supports'     => array( 'title', 'thumbnail' ),
			)
		)
	);

	register_post_type(
		'mom_album',
		array(
			'labels'       => array_merge(
				ministry_labels( 'Album', 'Albums' ),
				array(
					'menu_name' => 'Gallery',
					'add_new'   => 'Add album',
				)
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'menu_icon'    => 'dashicons-format-gallery',
			'menu_position'=> 22,
			'supports'     => array( 'title' ),
		)
	);

	register_post_type(
		'mom_banner',
		array(
			'labels'       => ministry_labels( 'Banner', 'Banners' ),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'menu_icon'    => 'dashicons-images-alt2',
			'supports'     => array( 'title', 'thumbnail', 'page-attributes' ),
		)
	);

	register_post_type(
		'mom_opportunity',
		array(
			'labels'             => ministry_labels( 'Opportunity', 'Opportunities' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'has_archive'        => false,
			'exclude_from_search'=> false,
			'rewrite'            => array( 'slug' => 'opportunity' ),
			'menu_icon'          => 'dashicons-chart-line',
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
		)
	);

	register_post_type(
		'mom_faq',
		array(
			'labels'      => ministry_labels( 'FAQ', 'FAQs' ),
			'public'      => false,
			'show_ui'     => true,
			'menu_icon'   => 'dashicons-editor-help',
			'supports'    => array( 'title', 'editor', 'page-attributes' ),
		)
	);
}

/**
 * Cadastre shortcut used by the public menu.
 */
function ministry_register_rewrites() {
	add_rewrite_rule( '^invest/cadastre/?$', 'index.php?mom_cadastre=1', 'top' );
}

/**
 * Build labels for a post type.
 *
 * @param string $singular Singular label.
 * @param string $plural   Plural label.
 * @return array<string, string>
 */
function ministry_labels( $singular, $plural ) {
	return array(
		'name'          => $plural,
		'singular_name' => $singular,
		'add_new_item'  => 'Add ' . $singular,
		'edit_item'     => 'Edit ' . $singular,
		'menu_name'     => $plural,
	);
}
