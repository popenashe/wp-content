<?php
/**
 * Public Bind module. Theme asks for a page key; surgery stays inside.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full public document for a design page key.
 *
 * @param string $name Design file name without .html.
 * @return array{head: string, body: string}
 */
function ministry_bind_page( $name ) {
	if ( 'style-guide' === $name && function_exists( 'ministry_site_document' ) ) {
		$doc = ministry_site_document( $name );
		return apply_filters( 'ministry_document', $doc, $name );
	}
	$bound = ministry_bind_wrap( $name, ministry_bind_main( $name ) );
	if ( 'contact' === $name && function_exists( 'ministry_site_contact_form' ) ) {
		$bound['body'] = ministry_site_contact_form( $bound['body'] );
	}
	if ( function_exists( 'ministry_site_albums' ) ) {
		$bound['body'] = ministry_site_albums( $bound['body'], $name );
	}
	if ( function_exists( 'ministry_site_department_photo' ) ) {
		$bound['body'] = ministry_site_department_photo( $bound['body'], $name );
	}
	return apply_filters( 'ministry_document', $bound, $name );
}

/**
 * Layout chrome around a main fragment, then live binds.
 *
 * @param string $name Design or copy page key.
 * @param string $main Inner HTML of #main.
 * @return array{head: string, body: string}
 */
function ministry_bind_wrap( $name, $main ) {
	$layout = ministry_bind_layout();
	$head   = isset( $layout['head'] ) ? (string) $layout['head'] : '';
	$top    = isset( $layout['top'] ) ? (string) $layout['top'] : '';
	$end    = isset( $layout['end'] ) ? (string) $layout['end'] : '';
	if ( function_exists( 'ministry_site_links' ) ) {
		$head = ministry_site_links( $head );
		$top  = ministry_site_links( $top );
		$end  = ministry_site_links( $end );
	}
	$body = $top . '<main id="main" tabindex="-1">' . $main . '</main>' . $end;
	$head = ministry_bind_title( $name, $head );
	return ministry_bind( $name, $head, $body );
}

/**
 * Put this page's title in the shared layout <title>.
 *
 * @param string $name Design file name.
 * @param string $head Head markup.
 */
function ministry_bind_title( $name, $head ) {
	$label = '';
	$copy  = array();
	$key   = function_exists( 'ministry_front_page_key' ) ? ministry_front_page_key( $name ) : '';
	if ( ! $key ) {
		$key = $name;
	}
	if ( $key && function_exists( 'ministry_page_copy' ) ) {
		$copy  = ministry_page_copy( $key );
		$label = isset( $copy['title'] ) ? (string) $copy['title'] : '';
	}
	if ( '' === $label ) {
		$post  = ministry_bind_record( $name );
		$label = $post ? (string) $post->post_title : '';
	}
	if ( $label && $label === get_bloginfo( 'name' ) && ! empty( $copy['eyebrow'] ) ) {
		$label = (string) $copy['eyebrow'];
	}
	if ( '' === $label || 'index' === $name ) {
		if ( 'index' === $name ) {
			$label = __( 'Home', 'ministry-content' );
		} else {
			return $head;
		}
	}
	$full    = $label . ' | ' . get_bloginfo( 'name' );
	$updated = preg_replace( '#<title>.*?</title>#s', '<title>' . esc_html( $full ) . '</title>', $head, 1 );
	return is_string( $updated ) ? $updated : $head;
}

/**
 * Fill chrome and lists from WordPress records.
 *
 * @param string $name Design file name without .html.
 * @param string $head Head markup.
 * @param string $body Body markup.
 * @return array{head: string, body: string}
 */
function ministry_bind( $name, $head, $body ) {
	$head = ministry_front_bind_head( $name, $head );
	$body = ministry_front_bind( $name, $body );
	return array(
		'head' => $head,
		'body' => $body,
	);
}

/**
 * Shared header/footer chrome. Captured from the design at import.
 *
 * @return array{head: string, top: string, end: string}
 */
function ministry_bind_layout() {
	$stored = get_option( 'mom_layout', array() );
	if ( is_array( $stored ) && ! empty( $stored['top'] ) ) {
		return $stored;
	}
	$from_file = ministry_bind_layout_from_file();
	if ( ! empty( $from_file['top'] ) ) {
		update_option( 'mom_layout', $from_file, false );
	}
	return $from_file;
}

/**
 * Header/footer chrome from the homepage design file.
 *
 * @return array{head: string, top: string, end: string}
 */
function ministry_bind_layout_from_file() {
	if ( ! function_exists( 'ministry_site_document' ) ) {
		return array(
			'head' => '',
			'top'  => '',
			'end'  => '',
		);
	}
	$doc   = ministry_site_document( 'index' );
	$parts = preg_split( '#<main id="main"[^>]*>.*</main>#s', (string) $doc['body'] );
	return array(
		'head' => (string) $doc['head'],
		'top'  => isset( $parts[0] ) ? (string) $parts[0] : '',
		'end'  => isset( $parts[1] ) ? (string) $parts[1] : '',
	);
}

/**
 * Store header/footer chrome from the homepage design file (import only).
 *
 * @return array{head: string, top: string, end: string}
 */
function ministry_bind_capture_layout() {
	$layout = ministry_bind_layout_from_file();
	if ( ! empty( $layout['top'] ) ) {
		update_option( 'mom_layout', $layout, false );
	}
	return $layout;
}

/**
 * Inner HTML of #main from the WordPress record for this page.
 *
 * @param string $name Design file name.
 */
function ministry_bind_main( $name ) {
	$post = ministry_bind_record( $name );
	if ( $post && 'publish' !== $post->post_status ) {
		return '<div class="wrap"><p>' . esc_html__( 'This page has been removed.', 'ministry-content' ) . '</p></div>';
	}
	$content = $post ? (string) $post->post_content : '';
	if ( '' !== trim( wp_strip_all_tags( $content ) ) ) {
		return wp_kses_post( $content );
	}
	return ministry_bind_main_from_file( $name );
}

/**
 * Inner HTML of #main from a design file, used when WordPress has no copy yet.
 *
 * @param string $name Design file name.
 */
function ministry_bind_main_from_file( $name ) {
	if ( function_exists( 'ministry_desk_design_main_html' ) ) {
		return ministry_desk_design_main_html( $name );
	}
	if ( ! function_exists( 'ministry_site_document' ) ) {
		return '';
	}
	$doc = ministry_site_document( $name );
	if ( preg_match( '#<main id="main"[^>]*>(.*)</main>#s', (string) $doc['body'], $match ) ) {
		return trim( $match[1] );
	}
	return '';
}

/**
 * WordPress record that owns a design page key.
 *
 * @param string $name Design file name.
 * @return WP_Post|null
 */
function ministry_bind_record( $name ) {
	if ( 0 === strpos( $name, 'news-' ) ) {
		$key   = substr( $name, 5 );
		$posts = get_posts(
			array(
				'post_type'      => 'mom_news',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'trash' ),
				'meta_key'       => '_mom_design_key',
				'meta_value'     => $key,
				'posts_per_page' => 1,
			)
		);
		return $posts ? $posts[0] : null;
	}
	if ( 0 === strpos( $name, 'department-' ) ) {
		$slug  = substr( $name, strlen( 'department-' ) );
		$posts = get_posts(
			array(
				'post_type'      => 'mom_department',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'trash' ),
				'posts_per_page' => 1,
				'meta_key'       => '_mom_design',
				'meta_value'     => $slug,
			)
		);
		if ( ! $posts ) {
			$posts = get_posts(
				array(
					'post_type'      => 'mom_department',
					'name'           => $slug,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'trash' ),
					'posts_per_page' => 1,
				)
			);
		}
		return $posts ? $posts[0] : null;
	}
	return function_exists( 'ministry_desk_page_for_design' ) ? ministry_desk_page_for_design( $name ) : null;
}
