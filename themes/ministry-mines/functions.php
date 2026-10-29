<?php
/**
 * Ministry Mines theme. Thin adapter: routes, assets, and layout shell.
 *
 * @package MinistryMines
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Keep in step with site/documents/.htaccess, which blocks direct requests for the files.
if ( ! defined( 'MINISTRY_DOWNLOADS_ENABLED' ) ) {
	define( 'MINISTRY_DOWNLOADS_ENABLED', true );
}

/**
 * Whether public PDF links should work.
 */
function ministry_downloads_on() {
	if ( function_exists( 'ministry_settings' ) ) {
		$settings = ministry_settings();
		if ( isset( $settings['downloadsEnabled'] ) && '' !== (string) $settings['downloadsEnabled'] ) {
			return '1' === (string) $settings['downloadsEnabled'];
		}
	}
	return (bool) MINISTRY_DOWNLOADS_ENABLED;
}

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'global-styles', 'classic-theme-styles', 'core-block-supports' ) as $handle ) {
			wp_dequeue_style( $handle );
		}
		wp_enqueue_script( 'ministry-tabs', get_template_directory_uri() . '/js/tabs.js', array(), wp_get_theme()->get( 'Version' ), true );
	},
	100
);
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );

add_action(
	'wp_head',
	function () {
		// Featured news photos are portrait; stop their natural height from stretching the card.
		$css = '.featured img{contain:size}';
		if ( ! ministry_downloads_on() ) {
			$css .= 'a[aria-disabled="true"]{cursor:not-allowed;opacity:.55;pointer-events:none}';
		}
		if ( ministry_site_assistant_config() ) {
			$css .= '#knowbot-widget-root:not(.is-open) iframe{opacity:0!important;visibility:hidden!important;pointer-events:none!important;width:0!important;height:0!important}'
				. '.mom-assistant-launch,.mom-assistant-close{position:fixed;z-index:10000;border:0;background:#1B2A4E;color:#fff;font:600 14px/1.2 inherit;cursor:pointer;box-shadow:0 8px 24px rgba(27,42,78,.35)}'
				. '.mom-assistant-launch:focus,.mom-assistant-close:focus{outline:2px solid #fff;outline-offset:3px}'
				. '.mom-assistant-launch{right:20px;bottom:20px;border-radius:999px;padding:12px 16px}'
				. '.mom-assistant-close{right:12px;bottom:604px;width:36px;height:36px;border-radius:999px}';
		}
		echo '<style>' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
);

/**
 * Folder holding the design pages and their assets.
 */
function ministry_site_dir() {
	return get_template_directory() . '/site/';
}

/**
 * Public URL of the design folder.
 */
function ministry_site_uri() {
	return get_template_directory_uri() . '/site/';
}

/**
 * WordPress path for a design page, without slashes.
 *
 * @param string $name File name without .html.
 */
function ministry_site_path( $name ) {
	if ( 'index' === $name ) {
		return '';
	}
	if ( str_starts_with( $name, 'department-' ) ) {
		return 'departments/' . substr( $name, strlen( 'department-' ) );
	}
	if ( str_starts_with( $name, 'news-' ) ) {
		return 'news/' . substr( $name, strlen( 'news-' ) );
	}
	return $name;
}

/**
 * Full URL for a design page.
 *
 * @param string $name File name without .html.
 */
function ministry_site_url( $name ) {
	$path = ministry_site_path( $name );
	return home_url( '' === $path ? '/' : '/' . $path . '/' );
}

/**
 * Request path => design file name.
 *
 * @return array<string, string>
 */
function ministry_site_routes() {
	static $routes = null;
	if ( null !== $routes ) {
		return $routes;
	}
	$routes = array();
	foreach ( (array) glob( ministry_site_dir() . '*.html' ) as $file ) {
		$name                                  = basename( $file, '.html' );
		$routes[ ministry_site_path( $name ) ] = $name;
	}
	return $routes;
}

/**
 * Point relative page links and asset paths at WordPress.
 *
 * @param string $html Design markup.
 */
function ministry_site_links( $html ) {
	$base = ministry_site_uri();
	return preg_replace_callback(
		'/\b(href|src|action)="(?![a-z][a-z0-9+.\-]*:|#|\/)([^"]*)"/i',
		function ( $m ) use ( $base ) {
			if ( preg_match( '/^([a-z0-9\-]+)\.html([?#].*)?$/i', $m[2], $page ) ) {
				$suffix = isset( $page[2] ) ? $page[2] : '';
				return $m[1] . '="' . esc_url( ministry_site_url( $page[1] ) ) . esc_attr( $suffix ) . '"';
			}
			if ( preg_match( '#^(css/styles\.css|js/main\.js)#', $m[2], $asset ) ) {
				$file = ministry_site_dir() . $asset[1];
				$ver  = is_readable( $file ) ? (string) filemtime( $file ) : (string) time();
				return $m[1] . '="' . esc_url( $base . $asset[1] ) . '?v=' . rawurlencode( $ver ) . '"';
			}
			return $m[1] . '="' . esc_url( $base . $m[2] ) . '"';
		},
		$html
	);
}

/**
 * Disable public file links while downloads are switched off.
 *
 * @param string $html Page markup.
 */
function ministry_site_downloads( $html ) {
	if ( ministry_downloads_on() ) {
		return $html;
	}
	$title = esc_attr__( 'Downloads are currently unavailable', 'ministry-mines' );
	$out   = preg_replace_callback(
		'/<a\b([^>]*)>/i',
		function ( $m ) use ( $title ) {
			$attrs = $m[1];
			$href  = '';
			if ( preg_match( '/\bhref="([^"]*)"/i', $attrs, $href_m ) ) {
				$href = $href_m[1];
			}
			$path          = strtolower( (string) wp_parse_url( $href, PHP_URL_PATH ) );
			$is_download   = (bool) preg_match( '/(?:^|\s)download(?:\s|=|$)/i', $attrs );
			$is_file       = (bool) preg_match( '/\.(?:pdf|docx?|xlsx?|zip)$/', $path );
			$is_documents  = (bool) preg_match( '#(?:^|/)documents/#', $path );
			if ( ! $is_download && ! $is_file && ! $is_documents ) {
				return $m[0];
			}
			$attrs = preg_replace( '/\s+(download|target|rel)(="[^"]*")?/i', '', $attrs );
			if ( preg_match( '/\bhref="/i', $attrs ) ) {
				$attrs = preg_replace( '/\bhref="[^"]*"/i', 'href="#"', $attrs );
			} else {
				$attrs .= ' href="#"';
			}
			if ( ! preg_match( '/aria-disabled=/i', $attrs ) ) {
				$attrs .= ' aria-disabled="true"';
			}
			if ( ! preg_match( '/\btitle=/i', $attrs ) ) {
				$attrs .= ' title="' . $title . '"';
			}
			return '<a' . $attrs . '>';
		},
		$html
	);
	return is_string( $out ) ? $out : $html;
}

/**
 * Head and body markup of a design page.
 *
 * @param string $name File name without .html.
 * @return array{head: string, body: string}
 */
function ministry_site_document( $name ) {
	$html = (string) file_get_contents( ministry_site_dir() . $name . '.html' );
	$head = preg_match( '#<head>(.*)</head>#s', $html, $m ) ? $m[1] : '';
	$body = preg_match( '#<body[^>]*>(.*)</body>#s', $html, $m ) ? $m[1] : '';
	return array(
		'head' => ministry_site_links( $head ),
		'body' => ministry_site_links( $body ),
	);
}

/**
 * Print a full page around design markup.
 *
 * @param string $head Head markup.
 * @param string $body Body markup.
 */
function ministry_site_output( $head, $body ) {
	$body = ministry_site_assistant_links( $body );
	$body = ministry_site_downloads( $body );
	?><!doctype html>
<html lang="en">
<head>
<?php
	echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	wp_head();
?>
</head>
<body<?php echo is_admin_bar_showing() ? ' class="admin-bar"' : ''; ?>>
<?php
	wp_body_open();
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	wp_footer();
?>
</body>
</html>
	<?php
}

/**
 * Public Ask Assistant settings, or null when the widget should not load.
 *
 * @return array{url: string, tenant: string}|null
 */
function ministry_site_assistant_config() {
	if ( is_admin() || ! function_exists( 'ministry_settings' ) ) {
		return null;
	}
	$settings = ministry_settings();
	$url      = isset( $settings['assistantScriptUrl'] ) ? (string) $settings['assistantScriptUrl'] : '';
	$tenant   = isset( $settings['assistantTenant'] ) ? (string) $settings['assistantTenant'] : '';
	if ( '1' !== (string) ( isset( $settings['assistantEnabled'] ) ? $settings['assistantEnabled'] : '' ) || ! $url || ! $tenant ) {
		return null;
	}
	return array(
		'url'    => $url,
		'tenant' => $tenant,
	);
}

/**
 * Point the header Ask Assistant control at the widget when it is on.
 *
 * @param string $body Body markup.
 */
function ministry_site_assistant_links( $body ) {
	if ( ! ministry_site_assistant_config() ) {
		return $body;
	}
	$updated = preg_replace(
		'#(<a class="btn btn-sm ask" href=")[^"]*"#',
		'$1#" role="button"',
		$body
	);
	return is_string( $updated ) ? $updated : $body;
}

add_action(
	'wp_footer',
	function () {
		$config = ministry_site_assistant_config();
		if ( ! $config ) {
			return;
		}
		echo '<script src="' . esc_url( $config['url'] ) . '" data-tenant="' . esc_attr( $config['tenant'] ) . '"></script>' . "\n";
		echo '<button type="button" id="mom-assistant-launch" class="mom-assistant-launch" aria-controls="knowbot-widget-root" aria-expanded="false">Ask Assistant</button>' . "\n";
		echo '<button type="button" id="mom-assistant-close" class="mom-assistant-close" hidden aria-label="Close assistant">&times;</button>' . "\n";
		echo '<script>(function(){function r(){return document.getElementById("knowbot-widget-root")}function b(){return document.getElementById("mom-assistant-launch")}function c(){return document.getElementById("mom-assistant-close")}function set(on){var el=r(),open=b(),close=c();if(el)el.classList.toggle("is-open",on);if(open){open.setAttribute("aria-expanded",on?"true":"false");open.hidden=on}if(close)close.hidden=!on}document.addEventListener("click",function(e){var t=e.target.closest&&e.target.closest("a.ask,#mom-assistant-launch,#mom-assistant-close");if(!t)return;e.preventDefault();set(t.id!=="mom-assistant-close")});document.addEventListener("keydown",function(e){if(e.key==="Escape")set(false)})})();</script>' . "\n";
	},
	5
);

/**
 * Make the contact page form save to Enquiries in wp-admin.
 *
 * @param string $body Contact page markup.
 */
function ministry_site_contact_form( $body ) {
	$fields = '<input type="hidden" name="action" value="ministry_enquiry">'
		. wp_nonce_field( 'ministry_enquiry', 'ministry_enquiry_nonce', true, false )
		. '<p hidden><label>Leave this empty <input name="company_website" tabindex="-1" autocomplete="off"></label></p>';
	$body   = str_replace(
		'<form class="form-card" onsubmit="event.preventDefault();this.querySelector(\'[data-sent]\').hidden=false;">',
		'<form class="form-card" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">' . $fields,
		$body
	);
	$types  = array( 'general', 'complaint', 'minister', 'compliment' );
	$i      = 0;
	$body   = preg_replace_callback(
		'/<input type="radio" name="etype"/',
		function () use ( &$i, $types ) {
			$value = isset( $types[ $i ] ) ? $types[ $i ] : 'general';
			++$i;
			return '<input type="radio" name="type" value="' . $value . '"';
		},
		$body
	);
	$body   = strtr(
		$body,
		array(
			'id="fn" required'     => 'id="fn" name="name" required',
			'id="em" type="email"' => 'id="em" name="email" type="email"',
			'id="ph" type="tel"'   => 'id="ph" name="phone" type="tel"',
			'id="pv">'             => 'id="pv" name="office">',
			'id="message" rows'    => 'id="message" name="message" rows',
		)
	);
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['enquiry'] ) && 'sent' === $_GET['enquiry'] ) {
		$body = str_replace( '<p data-sent hidden class="note"', '<p data-sent class="note" role="status"', $body );
	} else {
		$body = str_replace( '<p data-sent class="note"', '<p data-sent hidden class="note"', $body );
	}
	if ( isset( $_GET['enquiry_error'] ) ) {
		$body = str_replace(
			'<button class="btn btn-navy" type="submit">',
			'<p class="note" role="alert" style="margin:0 0 14px">Please complete every required field with a valid email address.</p><button class="btn btn-navy" type="submit">',
			$body
		);
	}
	// phpcs:enable
	return $body;
}

/**
 * Wire the public registration form.
 *
 * @param string $body Register page markup.
 */
function ministry_site_register_form( $body ) {
	$fields = '<input type="hidden" name="action" value="ministry_register">'
		. wp_nonce_field( 'ministry_register', 'ministry_register_nonce', true, false )
		. '<p hidden><label>Leave this empty <input name="company_website" tabindex="-1" autocomplete="off"></label></p>';
	$event_id    = isset( $_GET['event'] ) ? absint( wp_unslash( $_GET['event'] ) ) : 0;
	$event_title = '';
	if ( $event_id ) {
		$event = get_post( $event_id );
		if ( $event && 'mom_event' === $event->post_type ) {
			$event_title = $event->post_title;
			$fields     .= '<input type="hidden" name="event_id" value="' . esc_attr( (string) $event_id ) . '">';
		}
	}
	$body = str_replace(
		'<form class="form-card" id="mom-register-form" method="post">',
		'<form class="form-card" id="mom-register-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">' . $fields,
		$body
	);
	if ( $event_title ) {
		$body = str_replace(
			'id="reg-event" name="event_title" required>',
			'id="reg-event" name="event_title" required value="' . esc_attr( $event_title ) . '">',
			$body
		);
	}
	if ( isset( $_GET['registration'] ) && 'sent' === $_GET['registration'] ) {
		$body = str_replace( '<p data-sent hidden class="note"', '<p data-sent class="note" role="status"', $body );
	}
	if ( isset( $_GET['registration_error'] ) ) {
		$body = str_replace(
			'<button class="btn btn-navy" type="submit">',
			'<p class="note" role="alert" style="margin:0 0 14px">Please complete every required field with a valid email address.</p><button class="btn btn-navy" type="submit">',
			$body
		);
	}
	return $body;
}

add_action( 'template_redirect', 'ministry_site_route', 1 );

/**
 * Serve design pages, redirect retired addresses, and send old records to their listing.
 */
function ministry_site_route() {
	global $wp;
	if ( is_feed() || is_robots() || is_trackback() || is_search() ) {
		return;
	}
	if ( is_preview() && function_exists( 'ministry_public_record_url' ) ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$url = ministry_public_record_url( $post );
			if ( $url ) {
				wp_safe_redirect( $url, 302 );
				exit;
			}
		}
	}
	$path   = trim( (string) $wp->request, '/' );
	$legacy = array(
		'about/ministry' => 'the-ministry',
		'about/entities' => 'parastatals',
		'media'          => 'gallery',
	);
	if ( isset( $legacy[ $path ] ) ) {
		wp_safe_redirect( ministry_site_url( $legacy[ $path ] ), 301 );
		exit;
	}
	$routes = ministry_site_routes();
	if ( isset( $routes[ $path ] ) ) {
		$name = $routes[ $path ];
		$doc  = function_exists( 'ministry_bind_page' ) ? ministry_bind_page( $name ) : ministry_site_document( $name );
		status_header( 200 );
		ministry_site_output( $doc['head'], $doc['body'] );
		exit;
	}
	if ( is_singular() && function_exists( 'ministry_public_record_url' ) ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$url = ministry_public_record_url( $post );
			if ( $url ) {
				wp_safe_redirect( $url, 301 );
				exit;
			}
		}
	}
	$listings = array(
		'mom_news'        => 'news',
		'mom_department'  => 'departments',
		'mom_event'       => 'events',
		'mom_document'    => 'resources',
		'mom_opportunity' => 'invest',
		'mom_media'       => 'gallery',
		'mom_album'       => 'gallery',
		'mom_service'     => 'services',
		'mom_faq'         => 'faq',
		'mom_banner'      => 'index',
	);
	foreach ( $listings as $type => $name ) {
		if ( is_post_type_archive( $type ) ) {
			wp_safe_redirect( ministry_site_url( $name ) );
			exit;
		}
	}
}

/**
 * Use a department's saved description photo on its public page.
 *
 * @param string $body Page markup.
 * @param string $name Design file name.
 */
function ministry_site_department_photo( $body, $name ) {
	if ( ! str_starts_with( $name, 'department-' ) ) {
		return $body;
	}
	$slug  = substr( $name, strlen( 'department-' ) );
	$posts = get_posts(
		array(
			'post_type'      => 'mom_department',
			'post_status'    => 'publish',
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
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			)
		);
	}
	if ( ! $posts ) {
		return $body;
	}
	$photo = (int) get_post_meta( $posts[0]->ID, '_mom_photo', true );
	$url   = $photo ? wp_get_attachment_image_url( $photo, 'large' ) : '';
	if ( ! $url ) {
		return $body;
	}
	$alt = (string) get_post_meta( $photo, '_wp_attachment_image_alt', true );
	$img = '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" style="width:100%;aspect-ratio:16/6;object-fit:cover;border-radius:6px;margin-bottom:24px">';
	$updated = preg_replace( '#<img\b[^>]*aspect-ratio:\s*16/6[^>]*>#', $img, $body, 1, $count );
	if ( $count ) {
		return $updated;
	}
	return preg_replace( '#(<div class="mandate-box">.*?</div>)#s', '$1' . $img, $body, 1 );
}

/**
 * Header and footer from the homepage design, around custom main content.
 *
 * @param string $main Markup for inside main.
 */
function ministry_site_shell( $main ) {
	$layout = function_exists( 'ministry_bind_layout' ) ? ministry_bind_layout() : array();
	$head   = isset( $layout['head'] ) ? (string) $layout['head'] : '';
	$top    = isset( $layout['top'] ) ? (string) $layout['top'] : '';
	$end    = isset( $layout['end'] ) ? (string) $layout['end'] : '';
	$head   = preg_replace( '#<title>.*?</title>#s', '<title>' . esc_html( wp_get_document_title() ) . '</title>', $head );
	$head   = preg_replace( '#<meta name="description"[^>]*>\s*#', '', $head );
	$top    = preg_replace( '#<div class="alert-bar".*?</div>\s*</div>\s*#s', '', $top );
	$top    = str_replace( ' aria-current="page"', '', $top );
	if ( function_exists( 'ministry_bind' ) ) {
		$doc = ministry_bind( 'index', $head, $top . '<main id="main" tabindex="-1">' . $main . '</main>' . $end );
		ministry_site_output( $doc['head'], $doc['body'] );
		return;
	}
	ministry_site_output( $head, $top . '<main id="main" tabindex="-1">' . $main . '</main>' . $end );
}

/**
 * Inner page header in the design's style.
 *
 * @param string $title Page title.
 * @param string $lead  Optional introduction.
 */
function ministry_site_page_hero( $title, $lead = '' ) {
	$html  = '<section class="page-hero"><div class="copy">';
	$html .= '<nav class="crumbs" aria-label="Breadcrumb"><a href="' . esc_url( home_url( '/' ) ) . '">Home</a><span aria-hidden="true">&rsaquo;</span><span aria-current="page">' . esc_html( $title ) . '</span></nav>';
	$html .= '<h1>' . esc_html( $title ) . '</h1>';
	if ( $lead ) {
		$html .= '<p class="lead">' . esc_html( $lead ) . '</p>';
	}
	$html .= '</div><div class="media"><img src="' . esc_url( ministry_site_uri() . 'images/harare-city.jpg' ) . '" alt=""></div></section>';
	return $html;
}

/**
 * Replace the static gallery with albums from wp-admin when any exist.
 *
 * @param string $body Page markup.
 * @param string $name Design file name.
 */
function ministry_site_albums( $body, $name ) {
	if ( ! function_exists( 'ministry_albums' ) ) {
		return $body;
	}
	$albums = ministry_albums();
	if ( ! $albums ) {
		return $body;
	}
	if ( 'gallery' === $name ) {
		$markup  = ministry_gallery_albums_markup( $albums );
		$updated = preg_replace_callback(
			'#(<nav class="tabs" aria-label="News sections">.*?</nav>\s*).*?(\s*</div></section>\s*</main>)#s',
			function ( $matches ) use ( $markup ) {
				return $matches[1] . $markup . $matches[2];
			},
			$body,
			1,
			$count
		);
		return $count ? $updated : $body;
	}
	if ( 'index' === $name ) {
		$markup  = ministry_gallery_home_markup( $albums );
		$updated = preg_replace_callback(
			'#(<aside class="gallery-panel">.*?<a class="small" href="[^"]*">All albums</a></div>).*?(</aside>)#s',
			function ( $matches ) use ( $markup ) {
				return $matches[1] . $markup . $matches[2];
			},
			$body,
			1,
			$count
		);
		return $count ? $updated : $body;
	}
	return $body;
}

/**
 * Album cards, filters, and photo grids.
 *
 * @param array<int, array<string, mixed>> $albums Albums from ministry_albums().
 */
function ministry_gallery_albums_markup( $albums ) {
	$years = array();
	foreach ( $albums as $album ) {
		if ( '' !== $album['year'] ) {
			$years[ $album['year'] ] = true;
		}
	}
	$years = array_keys( $years );
	rsort( $years );

	$html  = '<div class="section-head" style="align-items:center"><h2 style="margin:0">Albums</h2>';
	$html .= '<div class="filter-row" style="margin:0"><label class="small" for="g-year"><strong>Year</strong></label>';
	$html .= '<select class="field" id="g-year" style="width:auto;min-height:38px">';
	if ( count( $years ) !== 1 ) {
		$html .= '<option value="all">All years</option>';
	}
	foreach ( $years as $year ) {
		$html .= '<option value="' . esc_attr( $year ) . '">' . esc_html( $year ) . '</option>';
	}
	$html .= '</select>';
	$html .= '<label class="small" for="g-event"><strong>Event</strong></label>';
	$html .= '<select class="field" id="g-event" style="width:auto;min-height:38px"><option value="all">All events</option>';
	foreach ( $albums as $album ) {
		$html .= '<option value="' . esc_attr( $album['slug'] ) . '">' . esc_html( $album['title'] ) . '</option>';
	}
	$html .= '</select></div></div>';
	$html .= '<p id="album-empty" hidden>No albums match these filters.</p>';
	$html .= '<div class="album-cards">';
	foreach ( $albums as $album ) {
		$html .= ministry_gallery_album_card( $album );
	}
	$html .= '</div>';
	foreach ( $albums as $album ) {
		$html .= ministry_gallery_album_section( $album );
	}
	return $html;
}

/**
 * Summary card for one album.
 *
 * @param array<string, mixed> $album Album.
 */
function ministry_gallery_album_card( $album ) {
	$photos = $album['photos'];
	$count  = count( $photos );
	$label  = 1 === $count ? '1 photo' : $count . ' photos';
	$meta   = $album['year'] ? $album['year'] . ' · ' . $label : $label;
	$html   = '<a class="album-card" href="#' . esc_attr( $album['slug'] ) . '" data-album data-year="' . esc_attr( $album['year'] ) . '" data-slug="' . esc_attr( $album['slug'] ) . '">';
	$html  .= '<div class="mosaic">';
	foreach ( array_slice( $photos, 0, 4 ) as $photo ) {
		$html .= '<img src="' . esc_url( $photo['url'] ) . '" alt="" loading="lazy">';
	}
	if ( $count > 4 ) {
		$html .= '<span class="more">+' . esc_html( (string) ( $count - 4 ) ) . '</span>';
	}
	$html .= '</div><div class="card-body"><div><h3>' . esc_html( $album['title'] ) . '</h3>';
	$html .= '<span class="small muted">' . esc_html( $meta ) . '</span></div>';
	$html .= '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';
	$html .= '</div></a>';
	return $html;
}

/**
 * Photo grid for one album.
 *
 * @param array<string, mixed> $album Album.
 */
function ministry_gallery_album_section( $album ) {
	$wide  = 'wide' === $album['layout'] ? ' wide' : '';
	$eyebrow = $album['year'] ? 'Album · ' . $album['year'] : 'Album';
	$html  = '<section id="' . esc_attr( $album['slug'] ) . '" data-album data-year="' . esc_attr( $album['year'] ) . '" data-slug="' . esc_attr( $album['slug'] ) . '" style="margin-top:44px">';
	$html .= '<div class="section-head" style="margin-bottom:14px"><div><p class="eyebrow">' . esc_html( $eyebrow ) . '</p>';
	$html .= '<h2 style="font-size:1.5rem">' . esc_html( $album['title'] ) . '</h2></div>';
	$html .= '<span class="small muted">Select a photo to view full size and download</span></div>';
	$html .= '<div class="portrait-grid' . $wide . '">';
	foreach ( $album['photos'] as $photo ) {
		$html .= '<figure><a href="' . esc_url( $photo['full'] ) . '" target="_blank" rel="noopener">';
		$html .= '<img src="' . esc_url( $photo['url'] ) . '" alt="' . esc_attr( $photo['alt'] ) . '" loading="lazy"></a>';
		$html .= '<figcaption>' . esc_html( $photo['caption'] ) . '</figcaption></figure>';
	}
	$html .= '</div></section>';
	return $html;
}

/**
 * Homepage pictures ticked in an album.
 *
 * @param array<int, array<string, mixed>> $albums Albums from ministry_albums().
 */
function ministry_gallery_home_markup( $albums ) {
	$html    = '';
	$gallery = ministry_site_url( 'gallery' );
	foreach ( $albums as $album ) {
		foreach ( $album['photos'] as $photo ) {
			if ( empty( $photo['home'] ) ) {
				continue;
			}
			$html .= '<a href="' . esc_url( $gallery . '#' . $album['slug'] ) . '"><figure>';
			$html .= '<img src="' . esc_url( $photo['url'] ) . '" alt="' . esc_attr( $photo['alt'] ) . '" loading="lazy">';
			$html .= '<figcaption>' . esc_html( $photo['caption'] ) . '</figcaption></figure></a>';
		}
	}
	return $html;
}
