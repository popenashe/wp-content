<?php
/**
 * Primary menu helpers.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turn stored menu items into a tree the theme can print.
 * Items whose URL is #departments or #offices expand to department posts.
 *
 * @return array<int, array<string, mixed>>
 */
function ministry_menu_tree() {
	$locations = get_nav_menu_locations();
	$menu_id   = isset( $locations['primary'] ) ? (int) $locations['primary'] : (int) get_option( 'mom_primary_menu' );
	if ( ! $menu_id ) {
		return array();
	}
	$items = wp_get_nav_menu_items( $menu_id );
	if ( ! is_array( $items ) ) {
		return array();
	}

	$nodes = array();
	foreach ( $items as $item ) {
		$url    = (string) $item->url;
		$expand = ministry_menu_expand_key( $url );
		$nodes[ (int) $item->ID ] = array(
			'id'       => (int) $item->ID,
			'parent'   => (int) $item->menu_item_parent,
			'title'    => $item->title,
			'url'      => $url,
			'expand'   => $expand,
			'children' => array(),
		);
	}

	$tree = array();
	foreach ( $nodes as $id => $node ) {
		if ( $node['parent'] && isset( $nodes[ $node['parent'] ] ) ) {
			$nodes[ $node['parent'] ]['children'][] = $id;
		} else {
			$tree[] = $id;
		}
	}

	$build = function ( $id ) use ( &$build, &$nodes ) {
		$node = $nodes[ $id ];
		$kids = array();
		foreach ( $node['children'] as $child_id ) {
			$kids[] = $build( $child_id );
		}
		$node['children'] = $kids;
		return $node;
	};

	$out = array();
	foreach ( $tree as $id ) {
		$out[] = $build( $id );
	}
	return $out;
}

/**
 * Expand a menu URL only when it is a bare #departments or #offices placeholder.
 * /departments#offices stays a normal link.
 *
 * @param string $url Menu URL.
 */
function ministry_menu_expand_key( $url ) {
	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['fragment'] ) ) {
		return '';
	}
	$path = isset( $parts['path'] ) ? rtrim( (string) $parts['path'], '/' ) : '';
	if ( '' !== $path && '/' !== $path ) {
		return '';
	}
	if ( 'departments' === $parts['fragment'] ) {
		return 'departments';
	}
	if ( 'offices' === $parts['fragment'] ) {
		return 'offices';
	}
	return '';
}

/**
 * Links for a menu placeholder.
 *
 * @param string $which departments or offices.
 * @return array<int, array{title: string, url: string}>
 */
function ministry_menu_expand_links( $which ) {
	$posts = ministry_departments( 'offices' === $which );
	$links = array();
	foreach ( $posts as $post ) {
		$links[] = array(
			'title' => $post->post_title,
			'url'   => get_permalink( $post ),
		);
	}
	return $links;
}
