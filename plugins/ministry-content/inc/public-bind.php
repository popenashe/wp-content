<?php
/**
 * Bind implementation: replace design HTML from WordPress records.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'ministry_front_dynamic', 0 );

/**
 * Pages for records that do not have their own design file yet.
 */
function ministry_front_dynamic() {
	if ( ! function_exists( 'ministry_site_routes' ) || is_admin() ) {
		return;
	}
	global $wp;
	$path = trim( (string) $wp->request, '/' );
	if ( preg_match( '#^news/([^/]+)$#', $path, $match ) && ! isset( ministry_site_routes()[ $path ] ) ) {
		ministry_front_dynamic_news( $match[1] );
	}
	if ( preg_match( '#^departments/([^/]+)$#', $path, $match ) && ! isset( ministry_site_routes()[ $path ] ) ) {
		ministry_front_dynamic_department( $match[1] );
	}
}

/**
 * @param string $name Design file name.
 * @param string $head Head markup.
 */
function ministry_front_bind_head( $name, $head ) {
	if ( 'style-guide' === $name || ! get_option( 'mom_design_sync' ) ) {
		return $head;
	}
	$key  = ministry_front_page_key( $name );
	$base = ministry_design_baseline();
	if ( ! $key || empty( $base['pages'][ $key ]['title'] ) ) {
		return $head;
	}
	$copy = ministry_page_copy( $key );
	$head = ministry_front_text( $head, $base['pages'][ $key ]['title'], $copy['title'] );
	if ( 'about' === $key ) {
		$crumb = $copy['eyebrow'] ? $copy['eyebrow'] : 'About the ministry';
		$head  = ministry_front_text( $head, 'About Us |', $crumb . ' |' );
	}
	return $head;
}

/**
 * @param string $name Design file name.
 * @param string $body Body markup.
 */
function ministry_front_bind( $name, $body ) {
	if ( 'style-guide' === $name ) {
		return $body;
	}
	if ( get_option( 'mom_design_sync' ) ) {
		$body = ministry_front_identity( $body );
		$body = ministry_front_hero( $body, $name );
		$body = ministry_front_page_hero( $body, $name );
		$body = ministry_front_stats( $body, $name );
		$body = ministry_front_gateways( $body, $name );
		$body = ministry_front_about_copy( $body );
		$body = ministry_front_leaders( $body );
		$body = ministry_front_entities( $body );
		$body = ministry_front_news( $body, $name );
		$body = ministry_front_documents( $body, $name );
		$body = ministry_front_faqs( $body, $name );
		$body = ministry_front_events( $body, $name );
		$body = ministry_front_services( $body, $name );
		$body = ministry_front_opportunities( $body, $name );
		$body = ministry_front_departments( $body, $name );
		$body = ministry_front_offices( $body, $name );
		$body = ministry_front_invest( $body, $name );
		$body = ministry_front_banners( $body, $name );
		$body = ministry_front_sort_lists( $body, $name );
		$body = ministry_front_bind_more( $name, $body );
	}
	if ( 'offices' === $name ) {
		$body = ministry_front_office_maps( $body );
	}
	return $body;
}

/**
 * Use desk body copy on a design page after an editor changes it.
 *
 * @param string $name Design file name.
 * @param string $body Body markup.
 */
function ministry_front_edited_main( $name, $body ) {
	$post = ministry_front_record_for_design( $name );
	if ( ! $post ) {
		return $body;
	}
	if ( 'publish' !== $post->post_status ) {
		$gone = '<main id="main" class="site-main"><div class="wrap"><p>This page has been removed.</p></div></main>';
		return preg_replace( '#<main id="main"[^>]*>.*</main>#s', $gone, $body, 1 );
	}
	$content  = trim( (string) $post->post_content );
	$baseline = (string) get_post_meta( $post->ID, '_mom_design_html', true );
	if ( '' === $content || $content === $baseline ) {
		return $body;
	}
	$inner = '<main id="main" class="site-main">' . $content . '</main>';
	return preg_replace( '#<main id="main"[^>]*>.*</main>#s', $inner, $body, 1 );
}

/**
 * Page or news record that owns a design file.
 *
 * @param string $name Design file name.
 * @return WP_Post|null
 */
function ministry_front_record_for_design( $name ) {
	return function_exists( 'ministry_bind_record' ) ? ministry_bind_record( $name ) : null;
}

/**
 * @param string $name Design file name.
 */
function ministry_front_page_key( $name ) {
	$map = array(
		'news'                      => 'news',
		'events'                    => 'events',
		'gallery'                   => 'media',
		'resources'                 => 'resources',
		'about'                     => 'about',
		'the-ministry'              => 'ministry',
		'parastatals'               => 'entities',
		'departments'               => 'departments',
		'offices'                   => 'offices',
		'invest'                    => 'invest',
		'services'                  => 'services',
		'how-to-get-a-mining-claim' => 'claim',
		'contact'                   => 'contact',
		'faq'                       => 'faq',
	);
	return isset( $map[ $name ] ) ? $map[ $name ] : '';
}

/**
 * Replace text only when an editor has changed it.
 *
 * @param string $html Markup.
 * @param string $from Original text.
 * @param string $to   Current text.
 * @param int    $min  Shortest string that may be replaced.
 */
function ministry_front_text( $html, $from, $to, $min = 8 ) {
	$from = (string) $from;
	$to   = (string) $to;
	if ( '' === $from || '' === $to || $from === $to || strlen( $from ) < $min ) {
		return $html;
	}
	$safe = esc_html( $to );
	$needles = array( $from, htmlspecialchars( $from, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	foreach ( array_unique( $needles ) as $needle ) {
		if ( '' !== $needle && str_contains( $html, $needle ) ) {
			$html = str_replace( $needle, $safe, $html );
		}
	}
	return $html;
}

/**
 * Run a callback on each tag that contains the anchor text.
 *
 * @param string   $html     Markup.
 * @param string   $tag      Element name.
 * @param string   $anchor   Text that identifies the element.
 * @param callable $callback Receives the element markup.
 */
function ministry_front_region( $html, $tag, $anchor, $callback ) {
	if ( '' === $anchor ) {
		return $html;
	}
	$encoded = htmlspecialchars( $anchor, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return preg_replace_callback(
		'#<' . preg_quote( $tag, '#' ) . '\b[^>]*>.*?</' . preg_quote( $tag, '#' ) . '>#s',
		function ( $match ) use ( $anchor, $encoded, $callback ) {
			if ( ! str_contains( $match[0], $anchor ) && ! str_contains( $match[0], $encoded ) ) {
				return $match[0];
			}
			return $callback( $match[0] );
		},
		$html
	);
}

/**
 * @param string $body Body markup.
 */
function ministry_front_identity( $body ) {
	$base     = ministry_design_baseline();
	$settings = ministry_settings();
	$saved    = isset( $base['settings'] ) && is_array( $base['settings'] ) ? $base['settings'] : array();
	foreach ( array( 'ministryName', 'shortName', 'countryLine', 'tagline', 'tollFree', 'headOfficePhone', 'headOfficeEmail', 'ministerEmail', 'headOfficeAddress', 'headOfficeName', 'officeHours', 'cadastrePortalUrl', 'footerPortalText', 'footerPortalTitle', 'footerNote' ) as $key ) {
		if ( isset( $saved[ $key ], $settings[ $key ] ) ) {
			$body = ministry_front_text( $body, (string) $saved[ $key ], (string) $settings[ $key ], 4 );
		}
	}
	if ( isset( $saved['cadastrePortalUrl'], $settings['cadastrePortalUrl'] ) && $saved['cadastrePortalUrl'] !== $settings['cadastrePortalUrl'] ) {
		$body = str_replace( (string) $saved['cadastrePortalUrl'], esc_url( (string) $settings['cadastrePortalUrl'] ), $body );
	}
	$body = ministry_front_bind_nav_links( $body, isset( $saved['headerQuickLinks'] ) ? $saved['headerQuickLinks'] : array(), isset( $settings['headerQuickLinks'] ) ? $settings['headerQuickLinks'] : array() );
	$body = ministry_front_bind_nav_links( $body, isset( $saved['footerColumns'] ) ? $saved['footerColumns'] : array(), isset( $settings['footerColumns'] ) ? $settings['footerColumns'] : array() );
	if ( ! empty( $saved['socialLinks'] ) && ! empty( $settings['socialLinks'] ) && is_array( $saved['socialLinks'] ) ) {
		foreach ( $saved['socialLinks'] as $index => $link ) {
			$next  = isset( $settings['socialLinks'][ $index ]['href'] ) ? (string) $settings['socialLinks'][ $index ]['href'] : '';
			$old   = isset( $link['href'] ) ? (string) $link['href'] : '';
			$label = isset( $settings['socialLinks'][ $index ]['label'] ) ? (string) $settings['socialLinks'][ $index ]['label'] : ( isset( $link['label'] ) ? (string) $link['label'] : '' );
			if ( $next && '#' !== $next && $next !== $old ) {
				if ( $old && '#' !== $old ) {
					$body = str_replace( 'href="' . esc_url( $old ) . '"', 'href="' . esc_url( ministry_front_href( $next ) ) . '"', $body );
					$body = str_replace( 'href="' . $old . '"', 'href="' . esc_url( ministry_front_href( $next ) ) . '"', $body );
				} elseif ( $label ) {
					$body = ministry_front_set_link( $body, $label, $next );
				}
			}
		}
	}
	$logo_base = (int) get_option( 'mom_design_logo_id', 0 );
	$logo_id   = (int) $settings['logoId'];
	if ( $logo_id && $logo_id !== $logo_base ) {
		$url = wp_get_attachment_image_url( $logo_id, 'full' );
		if ( $url ) {
			$body = preg_replace( '#src="[^"]*logo-full\.png"#', 'src="' . esc_url( $url ) . '"', $body );
		}
	}
	return ministry_front_alert( $body );
}

/**
 * @param string $body Body markup.
 */
function ministry_front_alert( $body ) {
	$base   = ministry_design_baseline();
	$alert  = isset( $base['alert'] ) ? (string) $base['alert'] : '';
	$banner = '';
	foreach ( ministry_posts( 'mom_news' ) as $post ) {
		if ( '1' === (string) get_post_meta( $post->ID, '_mom_banner', true ) ) {
			$banner = (string) get_post_meta( $post->ID, '_mom_banner_text', true );
			break;
		}
	}
	if ( '' === $banner || $banner === $alert || ! str_contains( $body, 'alert-bar' ) ) {
		return $body;
	}
	$updated = preg_replace( '#(<div class="alert-bar".*?<p>).*?(</p>)#s', '$1' . esc_html( $banner ) . '$2', $body, 1 );
	return is_string( $updated ) ? $updated : $body;
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_hero( $body, $name ) {
	if ( 'index' !== $name ) {
		return $body;
	}
	$base = ministry_design_baseline();
	$hero = isset( $base['homeHero'] ) && is_array( $base['homeHero'] ) ? $base['homeHero'] : array();
	$now  = ministry_settings()['homeHero'];
	if ( ! is_array( $now ) ) {
		return $body;
	}
	foreach ( array( 'eyebrow', 'title', 'text', 'primaryLabel', 'secondaryLabel' ) as $part ) {
		if ( isset( $hero[ $part ], $now[ $part ] ) ) {
			$body = ministry_front_text( $body, (string) $hero[ $part ], (string) $now[ $part ] );
		}
	}
	return $body;
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_page_hero( $body, $name ) {
	$key = ministry_front_page_key( $name );
	if ( ! $key ) {
		return $body;
	}
	$base = ministry_design_baseline();
	$from = isset( $base['pages'][ $key ] ) && is_array( $base['pages'][ $key ] ) ? $base['pages'][ $key ] : array();
	$copy = ministry_page_copy( $key );
	foreach ( array( 'eyebrow', 'title', 'intro' ) as $part ) {
		$source = 'intro' === $part ? 'intro' : $part;
		if ( isset( $from[ $source ] ) ) {
			$body = ministry_front_text( $body, (string) $from[ $source ], (string) $copy[ $source ] );
		}
	}
	if ( 'about' === $key ) {
		$crumb    = $copy['eyebrow'] ? $copy['eyebrow'] : 'About the ministry';
		$replaced = preg_replace(
			'#(<nav class="crumbs"[^>]*>.*?<span aria-current="page">)About Us(</span>)#s',
			'$1' . esc_html( $crumb ) . '$2',
			$body,
			1
		);
		if ( is_string( $replaced ) ) {
			$body = $replaced;
		}
	}
	return $body;
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_stats( $body, $name ) {
	if ( 'index' !== $name ) {
		return $body;
	}
	$base  = ministry_design_baseline();
	$saved = isset( $base['settings']['homeStats'] ) ? $base['settings']['homeStats'] : array();
	$stats = ministry_settings()['homeStats'];
	if ( ! is_array( $saved ) || ! is_array( $stats ) ) {
		return $body;
	}
	foreach ( $saved as $index => $stat ) {
		if ( ! is_array( $stat ) || ! isset( $stats[ $index ] ) || ! is_array( $stats[ $index ] ) ) {
			continue;
		}
		foreach ( array( 'label', 'value', 'hint' ) as $part ) {
			if ( isset( $stat[ $part ], $stats[ $index ][ $part ] ) ) {
				$body = ministry_front_text( $body, (string) $stat[ $part ], (string) $stats[ $index ][ $part ], 3 );
			}
		}
	}
	$pillars = isset( $base['settings']['homePillars'] ) ? $base['settings']['homePillars'] : array();
	$now     = isset( ministry_settings()['homePillars'] ) ? ministry_settings()['homePillars'] : array();
	if ( is_array( $pillars ) && is_array( $now ) ) {
		foreach ( $pillars as $index => $line ) {
			if ( isset( $now[ $index ] ) ) {
				$body = ministry_front_text( $body, (string) $line, (string) $now[ $index ] );
			}
		}
	}
	return $body;
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_gateways( $body, $name ) {
	if ( 'index' !== $name ) {
		return $body;
	}
	$base = ministry_design_baseline();
	$saved = isset( $base['settings']['gateways'] ) ? $base['settings']['gateways'] : array();
	$now   = ministry_settings()['gateways'];
	if ( isset( $base['settings']['gatewaysHeading'], ministry_settings()['gatewaysHeading'] ) ) {
		$body = ministry_front_text( $body, (string) $base['settings']['gatewaysHeading'], (string) ministry_settings()['gatewaysHeading'] );
	}
	if ( ! is_array( $saved ) || ! is_array( $now ) ) {
		return $body;
	}
	foreach ( $saved as $index => $gateway ) {
		if ( ! is_array( $gateway ) || empty( $gateway['title'] ) || ! isset( $now[ $index ] ) || ! is_array( $now[ $index ] ) ) {
			continue;
		}
		$current = $now[ $index ];
		$body    = ministry_front_region(
			$body,
			'a',
			(string) $gateway['title'],
			function ( $region ) use ( $gateway, $current ) {
				$region = ministry_front_text( $region, (string) $gateway['title'], (string) $current['title'], 2 );
				if ( isset( $gateway['desc'], $current['desc'] ) ) {
					$region = ministry_front_text( $region, (string) $gateway['desc'], (string) $current['desc'], 2 );
				}
				if ( isset( $gateway['tag'], $current['tag'] ) ) {
					$region = ministry_front_text( $region, (string) $gateway['tag'], (string) $current['tag'], 2 );
				}
				if ( isset( $gateway['href'], $current['href'] ) && $gateway['href'] !== $current['href'] ) {
					$region = preg_replace( '#href="[^"]*"#', 'href="' . esc_url( ministry_front_href( (string) $current['href'] ) ) . '"', $region, 1 );
				}
				return $region;
			}
		);
	}
	return $body;
}

/**
 * @param string $href Stored link.
 */
function ministry_front_href( $href ) {
	$href = trim( (string) $href );
	if ( '' === $href || '#' === $href || str_starts_with( $href, 'mailto:' ) || str_starts_with( $href, 'tel:' ) || str_starts_with( $href, '#' ) ) {
		return $href;
	}
	if ( str_starts_with( $href, 'http://' ) || str_starts_with( $href, 'https://' ) ) {
		return $href;
	}
	if ( preg_match( '#^(www\.)?[a-z0-9.-]+\.[a-z]{2,}#i', $href ) ) {
		return 'https://' . $href;
	}
	return home_url( '/' . ltrim( $href, '/' ) );
}

/**
 * Point an anchor that contains $label at $href.
 *
 * @param string $html  Markup.
 * @param string $label Visible link text.
 * @param string $href  Destination.
 */
function ministry_front_set_link( $html, $label, $href ) {
	$href = ministry_front_href( $href );
	if ( '' === $href || '#' === $href || '' === $label ) {
		return $html;
	}
	$safe = esc_url( $href );
	$home  = home_url();
	$extra = ( str_starts_with( $href, 'http://' ) || str_starts_with( $href, 'https://' ) ) && ! str_starts_with( $href, $home ) ? ' target="_blank" rel="noopener"' : '';
	$encoded = htmlspecialchars( $label, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$updated = preg_replace_callback(
		'#<a\b[^>]*>.*?</a>#s',
		function ( $match ) use ( $label, $encoded, $safe, $extra ) {
			if ( ! str_contains( $match[0], $label ) && ! str_contains( $match[0], $encoded ) ) {
				return $match[0];
			}
			$tag = preg_replace( '#\s+href="[^"]*"#', ' href="' . $safe . '"', $match[0], 1 );
			if ( $extra && ! str_contains( $tag, 'target=' ) ) {
				$tag = preg_replace( '#<a\b#', '<a' . $extra, $tag, 1 );
			}
			return is_string( $tag ) ? $tag : $match[0];
		},
		$html
	);
	return is_string( $updated ) ? $updated : $html;
}

/**
 * Apply stored header/footer link destinations.
 *
 * @param string $body  Markup.
 * @param mixed  $saved Baseline rows.
 * @param mixed  $now   Live rows.
 */
function ministry_front_bind_nav_links( $body, $saved, $now ) {
	if ( ! is_array( $saved ) || ! is_array( $now ) ) {
		return $body;
	}
	foreach ( $saved as $index => $row ) {
		if ( ! is_array( $row ) || ! isset( $now[ $index ] ) || ! is_array( $now[ $index ] ) ) {
			continue;
		}
		if ( isset( $row['links'] ) ) {
			$body = ministry_front_bind_nav_links( $body, $row['links'], isset( $now[ $index ]['links'] ) ? $now[ $index ]['links'] : array() );
			continue;
		}
		$label = isset( $row['label'] ) ? (string) $row['label'] : '';
		$old   = isset( $row['href'] ) ? (string) $row['href'] : '';
		$href  = isset( $now[ $index ]['href'] ) ? (string) $now[ $index ]['href'] : '';
		if ( $label && $href && '#' !== $href && $href !== $old ) {
			$body = ministry_front_set_link( $body, $label, $href );
		}
	}
	return $body;
}

/**
 * @param string $body Body markup.
 */
function ministry_front_about_copy( $body ) {
	$base = ministry_design_baseline();
	$saved = isset( $base['settings']['aboutSections'] ) ? $base['settings']['aboutSections'] : array();
	$now   = ministry_settings()['aboutSections'];
	if ( ! is_array( $saved ) || ! is_array( $now ) ) {
		return $body;
	}
	foreach ( $saved as $index => $section ) {
		if ( ! is_array( $section ) || ! isset( $now[ $index ] ) || ! is_array( $now[ $index ] ) ) {
			continue;
		}
		if ( ! empty( $section['title'] ) && isset( $now[ $index ]['title'] ) ) {
			$body = ministry_front_text( $body, (string) $section['title'], (string) $now[ $index ]['title'] );
		}
		if ( ! empty( $section['desc'] ) && isset( $now[ $index ]['desc'] ) ) {
			$body = ministry_front_text( $body, (string) $section['desc'], (string) $now[ $index ]['desc'] );
		}
	}
	$saved_steps = isset( $base['settings']['investHighlights'] ) ? $base['settings']['investHighlights'] : array();
	$steps       = ministry_settings()['investHighlights'];
	if ( is_array( $saved_steps ) && is_array( $steps ) ) {
		foreach ( $saved_steps as $index => $card ) {
			if ( ! is_array( $card ) || ! isset( $steps[ $index ] ) || ! is_array( $steps[ $index ] ) ) {
				continue;
			}
			$body = ministry_front_text( $body, (string) $card['title'], (string) $steps[ $index ]['title'] );
			$body = ministry_front_text( $body, (string) $card['desc'], (string) $steps[ $index ]['desc'] );
		}
	}
	$saved_licence = isset( $base['settings']['licensingSteps'] ) ? $base['settings']['licensingSteps'] : array();
	$licence       = ministry_settings()['licensingSteps'];
	if ( is_array( $saved_licence ) && is_array( $licence ) ) {
		foreach ( $saved_licence as $index => $step ) {
			if ( ! is_array( $step ) || ! isset( $licence[ $index ] ) || ! is_array( $licence[ $index ] ) ) {
				continue;
			}
			$body = ministry_front_text( $body, (string) $step['title'], (string) $licence[ $index ]['title'] );
			$body = ministry_front_text( $body, (string) $step['desc'], (string) $licence[ $index ]['desc'] );
		}
	}
	return $body;
}

/**
 * @param string $body Body markup.
 */
function ministry_front_leaders( $body ) {
	$base    = ministry_design_baseline();
	$saved   = isset( $base['leaders'] ) && is_array( $base['leaders'] ) ? $base['leaders'] : array();
	$leaders = ministry_leaders();
	if ( ! is_array( $leaders ) ) {
		return $body;
	}
	$used = array();
	foreach ( $saved as $index => $leader ) {
		if ( ! is_array( $leader ) ) {
			continue;
		}
		$now = ministry_front_leader_row( $leaders, isset( $leader['name'] ) ? (string) $leader['name'] : '', $used );
		if ( ! $now && isset( $leaders[ $index ] ) && is_array( $leaders[ $index ] ) && ! isset( $used[ $index ] ) ) {
			$now            = $leaders[ $index ];
			$used[ $index ] = true;
		}
		if ( ! is_array( $now ) ) {
			continue;
		}
		$body = ministry_front_text( $body, (string) $leader['name'], (string) $now['name'], 4 );
		$body = ministry_front_text( $body, (string) $leader['title'], (string) $now['title'], 4 );
		if ( ! empty( $leader['bio'] ) && isset( $now['bio'] ) ) {
			$body = ministry_front_text( $body, (string) $leader['bio'], (string) $now['bio'] );
		}
		if ( ! empty( $leader['fullBio'] ) && isset( $now['fullBio'] ) && $leader['fullBio'] !== $now['fullBio'] ) {
			$parts = preg_split( "/\n\n/", (string) $leader['fullBio'] );
			$first = isset( $parts[0] ) ? $parts[0] : '';
			if ( $first && $first !== (string) $now['fullBio'] && false === strpos( (string) $now['bio'], $first ) ) {
				$body = ministry_front_text( $body, $first, (string) $now['fullBio'] );
			}
		}
		$image = isset( $now['imageUrl'] ) ? (string) $now['imageUrl'] : '';
		$old   = isset( $leader['imageUrl'] ) ? (string) $leader['imageUrl'] : '';
		if ( $image && $image !== $old ) {
			$name = (string) $leader['name'];
			$body = preg_replace( '#(<img[^>]*alt="' . preg_quote( esc_attr( $name ), '#' ) . '"[^>]*src=")[^"]*#', '$1' . esc_url( $image ), $body );
			$body = preg_replace( '#(<img[^>]*src=")[^"]*("[^>]*alt="' . preg_quote( esc_attr( $name ), '#' ) . '")#', '$1' . esc_url( $image ) . '$2', $body );
		}
	}
	return $body;
}

/**
 * @param string $body Body markup.
 */
function ministry_front_entities( $body ) {
	$base     = ministry_design_baseline();
	$saved    = isset( $base['entities'] ) && is_array( $base['entities'] ) ? $base['entities'] : array();
	$entities = ministry_entities();
	if ( ! is_array( $entities ) ) {
		return $body;
	}
	$used = array();
	foreach ( $saved as $index => $entity ) {
		if ( ! is_array( $entity ) || empty( $entity['name'] ) ) {
			continue;
		}
		$now = ministry_front_entity_row( $entities, (string) $entity['name'], isset( $entity['acronym'] ) ? (string) $entity['acronym'] : '', $used );
		if ( ! $now && isset( $entities[ $index ] ) && is_array( $entities[ $index ] ) && ! isset( $used[ $index ] ) ) {
			$now            = $entities[ $index ];
			$used[ $index ] = true;
		}
		if ( ! is_array( $now ) ) {
			continue;
		}
		$anchor = (string) $entity['name'];
		$body   = ministry_front_region(
			$body,
			'article',
			$anchor,
			function ( $region ) use ( $entity, $now ) {
				if ( isset( $now['show'] ) && '0' === (string) $now['show'] ) {
					return '';
				}
				$region = ministry_front_text( $region, (string) $entity['name'], (string) $now['name'] );
				$region = ministry_front_text( $region, (string) $entity['desc'], wp_strip_all_tags( (string) $now['desc'] ) );
				if ( ! empty( $entity['acronym'] ) && isset( $now['acronym'] ) && $entity['acronym'] !== $now['acronym'] ) {
					$region = str_replace( '>' . $entity['acronym'] . '<', '>' . esc_html( (string) $now['acronym'] ) . '<', $region );
					$region = str_replace( '(' . $entity['acronym'] . ')', '(' . esc_html( (string) $now['acronym'] ) . ')', $region );
				}
				if ( ! empty( $entity['type'] ) && isset( $now['type'] ) ) {
					$region = ministry_front_text( $region, (string) $entity['type'], (string) $now['type'], 4 );
				}
				$logo  = ministry_front_entity_image( $now, 'logo' );
				$cover = ministry_front_entity_image( $now, 'cover' );
				if ( $logo ) {
					$acro    = isset( $now['acronym'] ) ? (string) $now['acronym'] : (string) $now['name'];
					$badge   = '<span class="logo-ph has-logo"><img src="' . esc_url( $logo ) . '" alt="' . esc_attr( $acro . ' logo' ) . '"></span>';
					$replaced = preg_replace( '#<span class="logo-ph">.*?</span>#s', $badge, $region, 1 );
					$region   = is_string( $replaced ) ? $replaced : $region;
				}
				if ( $cover ) {
					$replaced = preg_replace( '#(<div class="thumb">\s*<img[^>]*\ssrc=")[^"]*#', '$1' . esc_url( $cover ), $region, 1 );
					$region   = is_string( $replaced ) ? $replaced : $region;
				}
				if ( ! empty( $now['href'] ) && '#' !== (string) $now['href'] ) {
					$region = ministry_front_set_link( $region, 'Visit website', (string) $now['href'] );
				}
				if ( $logo || ( ! empty( $now['href'] ) && '#' !== (string) $now['href'] ) ) {
					$region = str_replace( '<span class="tbc-tag">Logo &amp; link from Ministry</span>', '', $region );
					$region = str_replace( '<span class="tbc-tag">Logo & link from Ministry</span>', '', $region );
				}
				return $region;
			}
		);
	}
	return $body;
}

/**
 * @param array<int, mixed> $entities Agencies.
 * @param string            $name     Full name.
 * @param string            $acronym  Short name.
 * @param array<int, bool>  $used     Claimed indexes.
 * @return array<string, mixed>|null
 */
function ministry_front_entity_row( $entities, $name, $acronym, &$used ) {
	foreach ( $entities as $index => $row ) {
		if ( isset( $used[ $index ] ) || ! is_array( $row ) ) {
			continue;
		}
		if ( $name && isset( $row['name'] ) && strcasecmp( (string) $row['name'], $name ) === 0 ) {
			$used[ $index ] = true;
			return $row;
		}
		if ( $acronym && isset( $row['acronym'] ) && strcasecmp( (string) $row['acronym'], $acronym ) === 0 ) {
			$used[ $index ] = true;
			return $row;
		}
	}
	return null;
}

/**
 * @param array<string, mixed> $entity Agency.
 * @param string               $kind   logo|cover.
 */
function ministry_front_entity_image( $entity, $kind ) {
	$id_key  = 'logo' === $kind ? 'logoId' : 'coverId';
	$url_key = 'logo' === $kind ? 'logoUrl' : 'coverUrl';
	$size    = 'logo' === $kind ? 'medium' : 'large';
	if ( ! empty( $entity[ $id_key ] ) ) {
		$url = wp_get_attachment_image_url( (int) $entity[ $id_key ], $size );
		if ( $url ) {
			return $url;
		}
	}
	return isset( $entity[ $url_key ] ) ? (string) $entity[ $url_key ] : '';
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_news( $body, $name ) {
	$base = ministry_design_baseline();
	$rows = isset( $base['news'] ) && is_array( $base['news'] ) ? $base['news'] : array();
	$by   = ministry_front_keyed_posts( 'mom_news' );
	foreach ( $rows as $key => $source ) {
		if ( ! is_array( $source ) ) {
			continue;
		}
		if ( ! isset( $by[ $key ] ) ) {
			$body = ministry_front_region( $body, 'article', (string) $source['title'], function () {
				return '';
			} );
			continue;
		}
		$post = $by[ $key ];
		$body = ministry_front_region(
			$body,
			'article',
			(string) $source['title'],
			function ( $region ) use ( $source, $post ) {
				$region = ministry_front_text( $region, (string) $source['title'], $post->post_title, 2 );
				$region = ministry_front_text( $region, (string) $source['excerpt'], $post->post_excerpt, 2 );
				$stored = (string) get_post_meta( $post->ID, '_mom_design_html', true );
				if ( '' === $stored ) {
					$region = ministry_front_text( $region, (string) $source['content'], $post->post_content, 2 );
				}
				return $region;
			}
		);
	}
	if ( 'news' === $name || 'index' === $name ) {
		foreach ( ministry_front_extra_posts( 'mom_news' ) as $post ) {
			$card = ministry_front_news_card( $post, 'index' === $name );
			if ( 'index' === $name ) {
				$body = preg_replace( '#(<div class="grid g2">)#', '$1' . $card, $body, 1 );
			} else {
				$body = str_replace( '<div class="note-bar">', $card . '<div class="note-bar">', $body );
			}
		}
	}
	return $body;
}

/**
 * @param WP_Post $post    News record.
 * @param bool    $compact Homepage card.
 */
function ministry_front_news_card( $post, $compact ) {
	$url   = ministry_front_news_link( $post );
	$kind  = (string) get_post_meta( $post->ID, '_mom_kind', true );
	$label = 'notice' === $kind ? 'Public notice' : 'News';
	$image = ministry_thumb_url( $post->ID, 'large' );
	$img   = $image ? '<img src="' . esc_url( $image ) . '" alt="" loading="lazy">' : '';
	if ( $compact ) {
		return '<article class="notice-card"><p class="meta"><span class="tag gold">' . esc_html( $label ) . '</span> ' . esc_html( ministry_format_date( $post->post_date ) ) . '</p><h3>' . esc_html( $post->post_title ) . '</h3><p class="small muted">' . esc_html( $post->post_excerpt ) . '</p><div class="links"><a href="' . esc_url( $url ) . '">Read notice</a></div></article>';
	}
	return '<article class="release" data-cat="' . esc_attr( $kind ? $kind : 'press' ) . '">' . $img . '<div><p class="meta"><span class="tag">' . esc_html( $label ) . '</span> ' . esc_html( ministry_format_date( $post->post_date ) ) . '</p><h3><a href="' . esc_url( $url ) . '">' . esc_html( $post->post_title ) . '</a></h3><p class="muted small">' . esc_html( $post->post_excerpt ) . '</p></div></article>';
}

/**
 * @param WP_Post $post News record.
 */
function ministry_front_news_link( $post ) {
	$key  = (string) get_post_meta( $post->ID, '_mom_design_key', true );
	$file = get_template_directory() . '/site/news-' . $key . '.html';
	if ( $key && is_readable( $file ) ) {
		return ministry_site_url( 'news-' . $key );
	}
	return home_url( '/news/' . $post->post_name . '/' );
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_documents( $body, $name ) {
	if ( 'resources' !== $name ) {
		return $body;
	}
	$base = ministry_design_baseline();
	$rows = isset( $base['documents'] ) && is_array( $base['documents'] ) ? $base['documents'] : array();
	$by   = ministry_front_keyed_posts( 'mom_document' );
	foreach ( $rows as $key => $source ) {
		if ( ! is_array( $source ) || empty( $source['title'] ) ) {
			continue;
		}
		if ( ! isset( $by[ $key ] ) ) {
			$body = ministry_front_region( $body, 'tr', (string) $source['title'], function () {
				return '';
			} );
			continue;
		}
		$post = $by[ $key ];
		$body = ministry_front_region(
			$body,
			'tr',
			(string) $source['title'],
			function ( $region ) use ( $source, $post ) {
				$region = ministry_front_text( $region, (string) $source['title'], $post->post_title, 2 );
				if ( ! empty( $source['blurb'] ) ) {
					$region = ministry_front_text( $region, (string) $source['blurb'], (string) get_post_meta( $post->ID, '_mom_blurb', true ), 2 );
				}
				$label = ministry_document_category_label( (string) get_post_meta( $post->ID, '_mom_category', true ) );
				if ( ! empty( $source['category'] ) && $source['category'] !== $label ) {
					$region = ministry_front_text( $region, (string) $source['category'], $label, 2 );
				}
				$url  = ministry_document_url( $post->ID );
				$file = isset( $source['file'] ) ? (string) $source['file'] : '';
				if ( $url && $file && ! str_contains( $url, $file ) ) {
					$region = preg_replace( '#href="[^"]*' . preg_quote( $file, '#' ) . '"#', 'href="' . esc_url( $url ) . '"', $region );
				}
				return $region;
			}
		);
	}
	foreach ( ministry_front_extra_posts( 'mom_document' ) as $post ) {
		$url  = ministry_document_url( $post->ID );
		$link = $url ? '<a class="doc-title" href="' . esc_url( $url ) . '">' . esc_html( $post->post_title ) . '</a>' : '<span class="doc-title">' . esc_html( $post->post_title ) . '</span>';
		$file = $url ? '<a href="' . esc_url( $url ) . '" download><strong>Download</strong></a>' : '<span class="await">Awaiting file</span>';
		$row  = '<tr><td><span class="fmt">PDF</span></td><td>' . $link . '</td><td><span class="tag grey">' . esc_html( ministry_document_category_label( (string) get_post_meta( $post->ID, '_mom_category', true ) ) ) . '</span></td><td class="small">' . esc_html( $post->post_excerpt ) . '</td><td style="text-align:right">' . $file . '</td></tr>';
		$body = str_replace( '</tbody>', $row . '</tbody>', $body );
	}
	return $body;
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_faqs( $body, $name ) {
	if ( 'faq' !== $name ) {
		return $body;
	}
	$base = ministry_design_baseline();
	$rows = isset( $base['faqs'] ) && is_array( $base['faqs'] ) ? $base['faqs'] : array();
	$by   = ministry_front_keyed_posts( 'mom_faq' );
	foreach ( $rows as $key => $source ) {
		if ( ! is_array( $source ) ) {
			continue;
		}
		if ( ! isset( $by[ $key ] ) ) {
			$body = ministry_front_region( $body, 'details', (string) $source['question'], function () {
				return '';
			} );
			continue;
		}
		$post = $by[ $key ];
		$body = ministry_front_region(
			$body,
			'details',
			(string) $source['question'],
			function ( $region ) use ( $source, $post ) {
				$region = ministry_front_text( $region, (string) $source['question'], $post->post_title, 2 );
				$region = ministry_front_text( $region, (string) $source['answer'], wp_strip_all_tags( $post->post_content ), 2 );
				return $region;
			}
		);
	}
	foreach ( ministry_front_extra_posts( 'mom_faq' ) as $post ) {
		$card = '<details class="faq"><summary>' . esc_html( $post->post_title ) . '<span class="pm" aria-hidden="true">+</span></summary><div class="ans"><p>' . esc_html( wp_strip_all_tags( $post->post_content ) ) . '</p></div></details>';
		$body = preg_replace( '#(<div>\s*<label class="lbl" for="faq-search">)#', $card . '$1', $body, 1 );
		if ( ! str_contains( $body, esc_html( $post->post_title ) ) ) {
			$body = str_replace( '<aside class="aside-stack">', $card . '<aside class="aside-stack">', $body );
		}
	}
	return $body;
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_events( $body, $name ) {
	if ( 'events' !== $name ) {
		return $body;
	}
	$base = ministry_design_baseline();
	$rows = isset( $base['events'] ) && is_array( $base['events'] ) ? $base['events'] : array();
	$by   = ministry_front_keyed_posts( 'mom_event' );
	foreach ( $rows as $key => $source ) {
		if ( ! is_array( $source ) ) {
			continue;
		}
		if ( ! isset( $by[ $key ] ) ) {
			$body = ministry_front_region( $body, 'article', (string) $source['title'], function () {
				return '';
			} );
			continue;
		}
		$post = $by[ $key ];
		$body = ministry_front_region(
			$body,
			'article',
			(string) $source['title'],
			function ( $region ) use ( $source, $post ) {
				$region = ministry_front_text( $region, (string) $source['title'], $post->post_title, 2 );
				$region = ministry_front_text( $region, (string) $source['excerpt'], $post->post_excerpt, 2 );
				$region = ministry_front_text( $region, (string) $source['category'], (string) get_post_meta( $post->ID, '_mom_category', true ), 2 );
				$region = ministry_front_text( $region, (string) $source['where'], (string) get_post_meta( $post->ID, '_mom_location', true ), 2 );
				$when   = (string) get_post_meta( $post->ID, '_mom_starts_at', true );
				if ( $when && ! empty( $source['when'] ) ) {
					$region = ministry_front_text( $region, (string) $source['when'], ministry_format_date( $when ), 2 );
				}
				return $region;
			}
		);
	}
	foreach ( ministry_front_extra_posts( 'mom_event' ) as $post ) {
		$when = (string) get_post_meta( $post->ID, '_mom_starts_at', true );
		$card = '<article class="event-card" data-cat="upcoming"><div class="card-body"><p class="eyebrow">' . esc_html( (string) get_post_meta( $post->ID, '_mom_category', true ) ) . '</p><h3>' . esc_html( $post->post_title ) . '</h3><p class="small muted">' . esc_html( $post->post_excerpt ) . '</p><p class="small">' . esc_html( trim( ministry_format_date( $when ) . ' · ' . (string) get_post_meta( $post->ID, '_mom_location', true ), ' ·' ) ) . '</p><a class="btn btn-navy btn-sm" href="' . esc_url( ministry_site_url( 'contact' ) ) . '#enquiry">Register</a></div></article>';
		$body = str_replace( '<aside class="aside-stack">', $card . '<aside class="aside-stack">', $body );
	}
	return $body;
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_services( $body, $name ) {
	if ( 'services' !== $name ) {
		return $body;
	}
	$base = ministry_design_baseline();
	$rows = isset( $base['services'] ) && is_array( $base['services'] ) ? $base['services'] : array();
	$by   = ministry_front_keyed_posts( 'mom_service' );
	foreach ( $rows as $key => $source ) {
		if ( ! is_array( $source ) ) {
			continue;
		}
		if ( ! isset( $by[ $key ] ) ) {
			$body = ministry_front_region( $body, 'article', (string) $source['title'], function () {
				return '';
			} );
			continue;
		}
		$post    = $by[ $key ];
		$service = get_post_meta( $post->ID, '_mom_service', true );
		$cat     = is_array( $service ) && isset( $service['category'] ) ? (string) $service['category'] : '';
		$body    = ministry_front_region(
			$body,
			'article',
			(string) $source['title'],
			function ( $region ) use ( $source, $post, $cat ) {
				$region = ministry_front_text( $region, (string) $source['title'], $post->post_title, 2 );
				$region = ministry_front_text( $region, (string) $source['excerpt'], $post->post_excerpt, 2 );
				if ( $cat ) {
					$region = ministry_front_text( $region, (string) $source['category'], $cat, 2 );
				}
				return $region;
			}
		);
	}
	foreach ( ministry_front_extra_posts( 'mom_service' ) as $post ) {
		$card = '<article class="svc"><div class="svc-top"><span class="tag grey">' . esc_html( $post->post_excerpt ? '' : 'Service' ) . '</span></div><h3>' . esc_html( $post->post_title ) . '</h3><p>' . esc_html( $post->post_excerpt ) . '</p></article>';
		$body = str_replace( '<p class="small muted" style="margin-top:14px">', $card . '<p class="small muted" style="margin-top:14px">', $body );
	}
	return $body;
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_opportunities( $body, $name ) {
	if ( 'index' !== $name && 'invest' !== $name ) {
		return $body;
	}
	$base = ministry_design_baseline();
	$rows = isset( $base['opportunities'] ) && is_array( $base['opportunities'] ) ? $base['opportunities'] : array();
	$by   = ministry_front_keyed_posts( 'mom_opportunity' );
	foreach ( $rows as $key => $source ) {
		if ( ! is_array( $source ) ) {
			continue;
		}
		$surface = isset( $source['surface'] ) ? $source['surface'] : '';
		if ( ( 'home' === $surface && 'index' !== $name ) || ( 'invest' === $surface && 'invest' !== $name ) ) {
			continue;
		}
		if ( ! isset( $by[ $key ] ) ) {
			$tag = 'home' === $surface ? 'a' : 'article';
			$body = ministry_front_region( $body, $tag, (string) $source['title'], function () {
				return '';
			} );
			continue;
		}
		$post = $by[ $key ];
		$tag  = 'home' === $surface ? 'a' : 'article';
		$body = ministry_front_region(
			$body,
			$tag,
			(string) $source['title'],
			function ( $region ) use ( $source, $post ) {
				$region = ministry_front_text( $region, (string) $source['title'], $post->post_title, 2 );
				if ( ! empty( $source['excerpt'] ) ) {
					$region = ministry_front_text( $region, (string) $source['excerpt'], $post->post_excerpt, 2 );
				}
				$region = ministry_front_text( $region, (string) $source['mineral'], (string) get_post_meta( $post->ID, '_mom_mineral', true ), 2 );
				return $region;
			}
		);
	}
	if ( 'invest' === $name ) {
		foreach ( ministry_front_extra_posts( 'mom_opportunity' ) as $post ) {
			$card = '<article class="area"><div class="card-body"><p class="meta"><span class="tag gold">' . esc_html( (string) get_post_meta( $post->ID, '_mom_mineral', true ) ) . '</span> ' . esc_html( (string) get_post_meta( $post->ID, '_mom_region', true ) ) . '</p><h3>' . esc_html( $post->post_title ) . '</h3><p class="muted small">' . esc_html( $post->post_excerpt ) . '</p><a class="link-arrow" href="' . esc_url( ministry_site_url( 'contact' ) ) . '#enquiry">Register interest</a></div></article>';
			$body = str_replace( '<div class="promo"', $card . '<div class="promo"', $body );
		}
	}
	return $body;
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_departments( $body, $name ) {
	$base = ministry_design_baseline();
	$rows = isset( $base['departments'] ) && is_array( $base['departments'] ) ? $base['departments'] : array();
	if ( str_starts_with( $name, 'department-' ) ) {
		$slug = substr( $name, strlen( 'department-' ) );
		return isset( $rows[ $slug ] ) ? ministry_front_department_page( $body, $slug, $rows[ $slug ] ) : $body;
	}
	if ( 'departments' !== $name ) {
		return $body;
	}
	$by = ministry_front_keyed_posts( 'mom_department' );
	foreach ( $rows as $key => $source ) {
		if ( ! is_array( $source ) ) {
			continue;
		}
		$anchor = ! empty( $source['list_title'] ) ? $source['list_title'] : $source['title'];
		if ( ! isset( $by[ $key ] ) ) {
			$body = ministry_front_region( $body, 'article', (string) $anchor, function () {
				return '';
			} );
			continue;
		}
		$post = $by[ $key ];
		$body = ministry_front_region(
			$body,
			'article',
			(string) $anchor,
			function ( $region ) use ( $source, $post ) {
				if ( $post->post_title !== $source['title'] && ! empty( $source['list_title'] ) ) {
					$region = ministry_front_text( $region, (string) $source['list_title'], $post->post_title, 2 );
				}
				$summary = (string) get_post_meta( $post->ID, '_mom_summary', true );
				if ( ! empty( $source['summary'] ) ) {
					$region = ministry_front_text( $region, (string) $source['summary'], $summary, 2 );
				}
				$head = (string) get_post_meta( $post->ID, '_mom_contact_name', true );
				if ( ! empty( $source['head'] ) ) {
					$region = ministry_front_text( $region, (string) $source['head'], $head, 2 );
				}
				return $region;
			}
		);
	}
	foreach ( ministry_front_extra_posts( 'mom_department' ) as $post ) {
		if ( '1' === (string) get_post_meta( $post->ID, '_mom_provincial', true ) || '1' === (string) get_post_meta( $post->ID, '_mom_centre', true ) ) {
			continue;
		}
		$url  = ministry_front_department_link( $post );
		$card = '<article class="dept-card"><div><div class="top"><h3>' . esc_html( $post->post_title ) . '</h3></div><p class="mandate"><strong>Mandate:</strong> ' . esc_html( (string) get_post_meta( $post->ID, '_mom_summary', true ) ) . '</p><div class="foot"><a href="' . esc_url( $url ) . '"><strong>Department page</strong></a></div></div></article>';
		$body = str_replace( '<div class="dept-grid" id="dept-list">', '<div class="dept-grid" id="dept-list">' . $card, $body );
	}
	return $body;
}

/**
 * @param array<int, mixed>  $leaders People records.
 * @param string             $name    Display name.
 * @param array<int, bool>   $used    Indexes already claimed.
 * @return array<string, mixed>|null
 */
function ministry_front_leader_row( $leaders, $name, &$used ) {
	foreach ( $leaders as $index => $row ) {
		if ( isset( $used[ $index ] ) || ! is_array( $row ) ) {
			continue;
		}
		if ( $name && isset( $row['name'] ) && (string) $row['name'] === $name ) {
			$used[ $index ] = true;
			return $row;
		}
	}
	return null;
}

/**
 * Rearrange people, department, and office cards when a saved order exists.
 *
 * @param string $body Markup.
 * @param string $name Design file name.
 */
function ministry_front_sort_lists( $body, $name ) {
	if ( 'index' === $name || 'the-ministry' === $name ) {
		$body = ministry_front_sort_people( $body, 'index' === $name );
	}
	if ( 'departments' === $name ) {
		$body = ministry_front_sort_structure( $body, 'article', 'dept-card', 'department' );
	}
	if ( 'offices' === $name ) {
		$body = ministry_front_sort_structure( $body, 'article', 'ho-card', 'centre' );
		$body = ministry_front_sort_structure( $body, 'article', 'prov-card', 'province' );
	}
	return $body;
}

/**
 * @param string $body Homepage or The Ministry markup.
 * @param bool   $home Homepage people strip when true.
 */
function ministry_front_sort_people( $body, $home ) {
	$base    = ministry_design_baseline();
	$saved   = isset( $base['leaders'] ) && is_array( $base['leaders'] ) ? $base['leaders'] : array();
	$leaders = ministry_leaders();
	if ( ! is_array( $leaders ) ) {
		return $body;
	}
	$saved_names = array();
	foreach ( $saved as $row ) {
		if ( is_array( $row ) && ! empty( $row['name'] ) ) {
			$saved_names[] = (string) $row['name'];
		}
	}
	$live_names = array();
	$needles    = array();
	foreach ( $leaders as $row ) {
		if ( ! is_array( $row ) || empty( $row['name'] ) ) {
			continue;
		}
		$live_names[] = (string) $row['name'];
		if ( isset( $row['status'] ) && 'former' === $row['status'] ) {
			continue;
		}
		if ( $home && isset( $row['home'] ) && '0' === (string) $row['home'] ) {
			continue;
		}
		$needles[] = (string) $row['name'];
	}
	if ( $home ) {
		return ministry_front_sort_class( $body, 'a', 'person', $needles );
	}
	if ( $live_names === $saved_names ) {
		return $body;
	}
	return ministry_front_sort_class( $body, 'article', 'leader-card', $needles );
}

/**
 * @param string $body Markup.
 * @param string $tag  article|a.
 * @param string $class Card class.
 * @param string $kind  department|centre|province.
 */
function ministry_front_sort_structure( $body, $tag, $class, $kind ) {
	$posts = ministry_posts( 'mom_department', ministry_structure_query( $kind ) );
	if ( ! ministry_posts_have_menu_order( $posts ) ) {
		return $body;
	}
	$titles = array();
	foreach ( $posts as $post ) {
		$titles[] = ministry_structure_card_label( $post );
	}
	return ministry_front_sort_class( $body, $tag, $class, $titles );
}

/**
 * @param string $card Markup for one card.
 * @param string $name Display name.
 */
function ministry_front_card_has_name( $card, $name ) {
	$name = (string) $name;
	if ( '' === $name ) {
		return false;
	}
	$encoded = htmlspecialchars( $name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	if ( str_contains( $card, $name ) || str_contains( $card, $encoded ) ) {
		return true;
	}
	$parts = preg_split( '/\s+/', $name );
	$last  = is_array( $parts ) ? (string) end( $parts ) : '';
	return strlen( $last ) >= 4 && str_contains( $card, $last );
}

/**
 * @param string   $html    Markup.
 * @param string   $tag     Element name.
 * @param string   $class   Class on the element.
 * @param string[] $needles Text that identifies each card in the desired order.
 */
function ministry_front_sort_class( $html, $tag, $class, $needles ) {
	$needles = array_values( array_filter( array_map( 'strval', $needles ) ) );
	if ( count( $needles ) < 2 ) {
		return $html;
	}
	$pattern = '#<' . preg_quote( $tag, '#' ) . '\b[^>]*class="[^"]*\b' . preg_quote( $class, '#' ) . '\b[^"]*"[^>]*>.*?</' . preg_quote( $tag, '#' ) . '>#s';
	if ( ! preg_match_all( $pattern, $html, $found ) ) {
		return $html;
	}
	$cards   = $found[0];
	$used    = array();
	$ordered = array();
	foreach ( $needles as $needle ) {
		foreach ( $cards as $index => $card ) {
			if ( isset( $used[ $index ] ) ) {
				continue;
			}
			if ( ministry_front_card_has_name( $card, $needle ) ) {
				$ordered[]      = $card;
				$used[ $index ] = true;
				break;
			}
		}
	}
	if ( count( $ordered ) < 2 ) {
		return $html;
	}
	$matched = array();
	foreach ( $cards as $index => $card ) {
		if ( isset( $used[ $index ] ) ) {
			$matched[] = $card;
		}
	}
	if ( $matched === $ordered ) {
		return $html;
	}
	$queue = $ordered;
	foreach ( $cards as $index => $card ) {
		if ( ! isset( $used[ $index ] ) ) {
			$queue[] = $card;
		}
	}
	$cursor = 0;
	$updated = preg_replace_callback(
		$pattern,
		function () use ( &$cursor, $queue ) {
			$next = isset( $queue[ $cursor ] ) ? $queue[ $cursor ] : '';
			++$cursor;
			return $next;
		},
		$html
	);
	return is_string( $updated ) ? $updated : $html;
}

/**
 * @param string               $body   Body markup.
 * @param string               $slug   Design slug.
 * @param array<string, mixed> $source Baseline row.
 */
function ministry_front_department_page( $body, $slug, $source ) {
	$posts = get_posts(
		array(
			'post_type'      => 'mom_department',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_mom_design_key',
			'meta_value'     => $slug,
		)
	);
	if ( ! $posts ) {
		return $body;
	}
	$post = $posts[0];
	$body = ministry_front_text( $body, (string) $source['title'], $post->post_title );
	$body = ministry_front_text( $body, (string) $source['mandate'], (string) get_post_meta( $post->ID, '_mom_mandate', true ) );
	$body = ministry_front_text( $body, (string) $source['head'], (string) get_post_meta( $post->ID, '_mom_contact_name', true ), 2 );
	$body = ministry_front_text( $body, (string) $source['head_title'], (string) get_post_meta( $post->ID, '_mom_head_title', true ), 2 );
	foreach ( array( 'phone', 'email', 'location' ) as $field ) {
		$meta = 'location' === $field ? '_mom_location' : '_mom_' . $field;
		if ( ! empty( $source[ $field ] ) ) {
			$body = ministry_front_text( $body, (string) $source[ $field ], ministry_front_meta_text( $post->ID, $meta ), 4 );
		}
	}
	$functions = get_post_meta( $post->ID, '_mom_functions', true );
	if ( is_array( $functions ) && isset( $source['functions'] ) && $functions !== $source['functions'] ) {
		$body = ministry_front_swap_spans( $body, 'check-list', $functions );
	}
	$standards = get_post_meta( $post->ID, '_mom_standards', true );
	if ( is_array( $standards ) && isset( $source['standards'] ) && $standards !== $source['standards'] ) {
		$body = ministry_front_swap_standards( $body, $standards );
	}
	$thumb = (int) get_post_thumbnail_id( $post );
	if ( $thumb && $thumb !== (int) $source['thumb'] ) {
		$url = wp_get_attachment_image_url( $thumb, 'medium' );
		if ( $url ) {
			$updated = preg_replace( '#(<div class="hod-card".*?<img[^>]*\ssrc=")[^"]*#s', '$1' . esc_url( $url ), $body, 1 );
			if ( is_string( $updated ) ) {
				$body = $updated;
			}
		}
	}
	return $body;
}

/**
 * @param string   $html  Markup.
 * @param string   $class List class.
 * @param string[] $lines New lines.
 */
function ministry_front_swap_spans( $html, $class, $lines ) {
	$updated = preg_replace_callback(
		'#(<ul class="' . preg_quote( $class, '#' ) . '">)(.*?)(</ul>)#s',
		function ( $match ) use ( $lines ) {
			preg_match_all( '#<li\b[^>]*>.*?</li>#s', $match[2], $items );
			$template = isset( $items[0][0] ) ? $items[0][0] : '<li><span></span></li>';
			$out      = '';
			foreach ( array_values( $lines ) as $index => $line ) {
				$item = isset( $items[0][ $index ] ) ? $items[0][ $index ] : $template;
				$next = preg_replace( '#(<span>)(.*?)(</span>)#s', '$1' . esc_html( (string) $line ) . '$3', $item, 1 );
				$out .= is_string( $next ) ? $next : $item;
			}
			return $match[1] . $out . $match[3];
		},
		$html,
		1
	);
	return is_string( $updated ) ? $updated : $html;
}

/**
 * @param string                              $html Markup.
 * @param array<int, array<string, string>>   $rows Standards.
 */
function ministry_front_swap_standards( $html, $rows ) {
	$updated = preg_replace_callback(
		'#(<table class="std-table">.*?<tbody>)(.*?)(</tbody>)#s',
		function ( $match ) use ( $rows ) {
			$out = '';
			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$out .= '<tr><td>' . esc_html( isset( $row['label'] ) ? (string) $row['label'] : '' ) . '</td><td>' . esc_html( isset( $row['time'] ) ? (string) $row['time'] : '' ) . '</td></tr>';
			}
			return $match[1] . $out . $match[3];
		},
		$html,
		1
	);
	return is_string( $updated ) ? $updated : $html;
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_offices( $body, $name ) {
	if ( 'offices' !== $name ) {
		return $body;
	}
	$base = ministry_design_baseline();
	$rows = isset( $base['offices'] ) && is_array( $base['offices'] ) ? $base['offices'] : array();
	$by   = ministry_front_keyed_posts( 'mom_department' );
	foreach ( $rows as $key => $source ) {
		if ( ! is_array( $source ) || empty( $source['title'] ) ) {
			continue;
		}
		if ( ! isset( $by[ $key ] ) ) {
			continue;
		}
		$post = $by[ $key ];
		$body = ministry_front_region(
			$body,
			'article',
			(string) $source['title'],
			function ( $region ) use ( $source, $post ) {
				$region = ministry_front_text( $region, (string) $source['title'], $post->post_title, 2 );
				foreach ( array( 'summary' => '_mom_summary', 'address' => '_mom_address', 'phone' => '_mom_phone', 'email' => '_mom_email', 'town' => '_mom_town', 'director' => '_mom_contact_name' ) as $field => $meta ) {
					if ( ! empty( $source[ $field ] ) ) {
						$region = ministry_front_text( $region, (string) $source[ $field ], ministry_front_meta_text( $post->ID, $meta ), 2 );
					}
				}
				return $region;
			}
		);
	}
	return $body;
}

/**
 * Clickable Google Maps previews on office cards.
 *
 * @param string $body Offices markup.
 */
function ministry_front_office_maps( $body ) {
	$posts = ministry_posts( 'mom_department' );
	$updated = preg_replace_callback(
		'#<article class="(?:ho-card|prov-card)"[^>]*>.*?</article>#s',
		function ( $match ) use ( $posts ) {
			return ministry_front_office_card_map( $match[0], $posts );
		},
		$body
	);
	return is_string( $updated ) ? $updated : $body;
}

/**
 * @param string    $card  One office card.
 * @param WP_Post[] $posts Office records.
 */
function ministry_front_office_card_map( $card, $posts ) {
	$title = '';
	if ( preg_match( '#<h3>(.*?)</h3>#s', $card, $heading ) ) {
		$title = trim( wp_strip_all_tags( $heading[1] ) );
	}
	$address = '';
	$town    = '';
	$lat     = '';
	$lng     = '';
	$post    = ministry_front_office_card_post( $title, $posts );
	if ( $post ) {
		$address = (string) get_post_meta( $post->ID, '_mom_address', true );
		$town    = (string) get_post_meta( $post->ID, '_mom_town', true );
		$lat     = (string) get_post_meta( $post->ID, '_mom_lat', true );
		$lng     = (string) get_post_meta( $post->ID, '_mom_lng', true );
		if ( '' === $town && '1' === (string) get_post_meta( $post->ID, '_mom_centre', true ) ) {
			$town = 'Harare';
		}
		if ( '' === $title ) {
			$title = $post->post_title;
		}
	}
	if ( '' === $address && preg_match( '#<div class="row"[^>]*>.*?<span>(.*?)</span>\s*</div>#s', $card, $row ) ) {
		$address = trim( wp_strip_all_tags( $row[1] ) );
	}
	if ( '' === $town && preg_match( '#<span class="small muted">(.*?)</span>#s', $card, $muted ) ) {
		$town = trim( wp_strip_all_tags( $muted[1] ) );
	}
	$query = ministry_front_maps_query( $title, $address, $town );
	if ( $post ) {
		$stamp = (string) get_post_meta( $post->ID, '_mom_geocode_query', true );
		if ( $stamp !== ministry_maps_geocode_stamp( $query ) ) {
			$lat = '';
			$lng = '';
		}
	}
	$href  = ministry_front_maps_link( $query );
	$point = ministry_front_maps_point( $query, $lat, $lng );
	$inner = ministry_front_maps_preview_inner( $title, $point );
	if ( $post && $point && 16 === (int) $point[2] ) {
		ministry_office_store_point( $post->ID, $query, $point );
	}
	ministry_front_maps_mark_needed();
	$preview = '<a class="map-preview" href="' . esc_url( $href ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( sprintf( __( 'Open %s in Google Maps', 'ministry-content' ), $title ) ) . '">' . $inner . '</a>';
	$card    = preg_replace( '#href="https://www\.openstreetmap\.org/search\?query=[^"]*"#', 'href="' . esc_url( $href ) . '"', $card );
	if ( preg_match( '#<a class="map-preview"[^>]*>.*?</a>#s', $card ) ) {
		$card = preg_replace( '#<a class="map-preview"[^>]*>.*?</a>#s', $preview, $card, 1 );
	} elseif ( preg_match( '#<div class="map-ph">.*?</div>#s', $card ) ) {
		$card = preg_replace( '#<div class="map-ph">.*?</div>#s', $preview, $card, 1 );
	} else {
		$card = preg_replace( '#(<article\b[^>]*>)#', '$1' . $preview, $card, 1 );
	}
	return $card;
}

/**
 * @param string    $title Card heading.
 * @param WP_Post[] $posts Office records.
 * @return WP_Post|null
 */
function ministry_front_office_card_post( $title, $posts ) {
	if ( '' === $title ) {
		return null;
	}
	foreach ( $posts as $post ) {
		if ( strcasecmp( $post->post_title, $title ) === 0 ) {
			return $post;
		}
		$province = (string) get_post_meta( $post->ID, '_mom_province_name', true );
		if ( $province && strcasecmp( $province, $title ) === 0 ) {
			return $post;
		}
	}
	return null;
}

/**
 * @param string $title   Office name.
 * @param string $address Street address.
 * @param string $town    Town.
 */
function ministry_front_maps_query( $title, $address, $town ) {
	$parts = array();
	$skip  = $address && ( str_contains( strtolower( $address ), 'to be confirmed' ) || str_contains( strtoupper( $address ), 'TBC' ) );
	if ( $address && ! $skip ) {
		$parts[] = $address;
	}
	if ( $town ) {
		$parts[] = $town;
	}
	$parts[] = 'Zimbabwe';
	return implode( ', ', array_unique( array_filter( array_map( 'trim', $parts ) ) ) );
}

/**
 * @param string $query Search text from the office address.
 */
function ministry_front_maps_link( $query ) {
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query );
}

/**
 * @param string $lat Latitude.
 * @param string $lng Longitude.
 * @return array{0: float, 1: float}|null
 */
function ministry_front_maps_coords( $lat, $lng ) {
	if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
		return null;
	}
	$lat = (float) $lat;
	$lng = (float) $lng;
	if ( abs( $lat ) < 0.01 && abs( $lng ) < 0.01 ) {
		return null;
	}
	if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
		return null;
	}
	return array( $lat, $lng );
}

/**
 * @param string                        $label Visible name on the pin.
 * @param array{0: float, 1: float, 2: int}|null $point Map point.
 */
function ministry_front_maps_preview_inner( $label, $point ) {
	if ( $point ) {
		return '<div class="map-ph has-map" data-map-thumb data-lat="' . esc_attr( sprintf( '%.6F', $point[0] ) ) . '" data-lng="' . esc_attr( sprintf( '%.6F', $point[1] ) ) . '" data-zoom="' . esc_attr( (string) $point[2] ) . '"><div class="map-thumb" role="presentation"></div><span class="map-attr">© OpenFreeMap</span>' . ( $label ? '<b>' . esc_html( $label ) . '</b>' : '' ) . '</div>';
	}
	return '<div class="map-ph"><span class="pin"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg></span>' . ( $label ? '<b>' . esc_html( $label ) . '</b>' : '' ) . '</div>';
}

/**
 * @param string $query Search text.
 * @param string $lat   Latitude.
 * @param string $lng   Longitude.
 * @return array{0: float, 1: float, 2: int}|null
 */
function ministry_front_maps_point( $query, $lat = '', $lng = '' ) {
	$coords = ministry_front_maps_coords( $lat, $lng );
	if ( $coords ) {
		return array( $coords[0], $coords[1], 16 );
	}
	$geo = ministry_maps_geocode( $query );
	if ( $geo ) {
		return array( $geo[0], $geo[1], 16 );
	}
	$guess = ministry_front_maps_guess_coords( $query );
	if ( $guess ) {
		return array( $guess[0], $guess[1], 13 );
	}
	return null;
}

/**
 * Nominatim lookup for a street address. Cached.
 *
 * @param string $query Address search text.
 * @return array{0: float, 1: float}|null
 */
function ministry_maps_geocode( $query ) {
	$query = trim( preg_replace( '/\s+/', ' ', (string) $query ) );
	if ( '' === $query ) {
		return null;
	}
	$low = strtolower( $query );
	if ( str_contains( $low, 'to be confirmed' ) || preg_match( '/\btbc\b/', $low ) ) {
		return null;
	}
	$key    = 'mom_geocode_zw3_' . md5( strtolower( $query ) );
	$cached = get_transient( $key );
	if ( is_array( $cached ) && isset( $cached['lat'], $cached['lng'] ) ) {
		return ministry_front_maps_coords( (string) $cached['lat'], (string) $cached['lng'] );
	}
	if ( 'none' === $cached ) {
		return null;
	}
	foreach ( ministry_maps_geocode_candidates( $query ) as $try ) {
		$coords = ministry_maps_geocode_request( $try );
		if ( $coords && ministry_maps_near_town( $coords, $query ) ) {
			set_transient(
				$key,
				array(
					'lat' => $coords[0],
					'lng' => $coords[1],
				),
				MONTH_IN_SECONDS
			);
			return $coords;
		}
	}
	set_transient( $key, 'none', DAY_IN_SECONDS );
	return null;
}

/**
 * Expand abbreviations and split a typed address into Nominatim search attempts.
 *
 * @param string $query Address search text.
 * @return string[]
 */
function ministry_maps_geocode_candidates( $query ) {
	$norm = ministry_maps_normalize_address( $query );
	$out  = array( $query );
	if ( $norm !== $query ) {
		$out[] = $norm;
	}
	$parts = array_values( array_filter( array_map( 'trim', explode( ',', $norm ) ) ) );
	if ( count( $parts ) > 2 ) {
		$out[] = implode( ', ', array_slice( $parts, 1 ) );
	}
	$tail = array_slice( $parts, -2 );
	foreach ( $parts as $part ) {
		if ( ! preg_match( '/\b(street|avenue|road|drive|close|lane|way|crescent)\b/i', $part ) ) {
			continue;
		}
		$out[] = implode( ', ', array_merge( array( $part ), $tail ) );
	}
	$unique = array();
	foreach ( $out as $item ) {
		$item = trim( preg_replace( '/\s+/', ' ', $item ) );
		if ( '' === $item ) {
			continue;
		}
		$unique[ strtolower( $item ) ] = $item;
	}
	return array_slice( array_values( $unique ), 0, 4 );
}

/**
 * @param string $query Address text.
 */
function ministry_maps_normalize_address( $query ) {
	$query = str_replace( array( '&', '+' ), ',', $query );
	$query = preg_replace( '/\b(cnr|corner of|corner)\b/i', ',', $query );
	$repl  = array(
		'/\bstreet\b/i'  => 'Street',
		'/\bavenue\b/i'  => 'Avenue',
		'/\broad\b/i'    => 'Road',
		'/\bdrive\b/i'   => 'Drive',
		'/\bst\.?\b/i'   => 'Street',
		'/\bave\.?\b/i'  => 'Avenue',
		'/\brd\.?\b/i'   => 'Road',
		'/\bdr\.?\b/i'   => 'Drive',
	);
	foreach ( $repl as $pattern => $word ) {
		$query = preg_replace( $pattern, $word, $query );
	}
	$query = preg_replace( '/\s*,\s*/', ', ', $query );
	return trim( preg_replace( '/\s+/', ' ', $query ), " \t," );
}

/**
 * @param string $query One Nominatim search string.
 * @return array{0: float, 1: float}|null
 */
function ministry_maps_geocode_request( $query ) {
	$url      = add_query_arg(
		array(
			'q'            => $query,
			'format'       => 'jsonv2',
			'limit'        => '1',
			'countrycodes' => 'zw',
		),
		'https://nominatim.openstreetmap.org/search'
	);
	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 8,
			'headers' => array(
				'User-Agent' => 'MinistryOfMinesPortal/1.0 (https://ministryofmines.org; mmmd@mines.gov.zw)',
				'Accept'     => 'application/json',
			),
		)
	);
	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $body ) || empty( $body[0]['lat'] ) || empty( $body[0]['lon'] ) ) {
		return null;
	}
	$coords = ministry_front_maps_coords( (string) $body[0]['lat'], (string) $body[0]['lon'] );
	if ( ! $coords ) {
		return null;
	}
	if ( $coords[0] > -15.4 || $coords[0] < -22.6 || $coords[1] < 25.0 || $coords[1] > 33.6 ) {
		return null;
	}
	return $coords;
}

/**
 * Reject a pin that is far from the named town (same street names exist in several cities).
 *
 * @param array{0: float, 1: float} $coords Latitude, longitude.
 * @param string                    $query  Full address query.
 */
function ministry_maps_near_town( $coords, $query ) {
	$guess = ministry_front_maps_guess_coords( $query );
	if ( ! $guess ) {
		return true;
	}
	$dlat = $coords[0] - $guess[0];
	$dlng = $coords[1] - $guess[1];
	return ( ( $dlat * $dlat ) + ( $dlng * $dlng ) ) < 0.36;
}

/**
 * Geocode an office from its saved address after desk save.
 *
 * @param int $id Office post ID.
 */
function ministry_office_geocode_save( $id ) {
	$id = absint( $id );
	if ( ! $id ) {
		return;
	}
	$title   = get_the_title( $id );
	$address = (string) get_post_meta( $id, '_mom_address', true );
	$town    = (string) get_post_meta( $id, '_mom_town', true );
	if ( '' === $town && '1' === (string) get_post_meta( $id, '_mom_centre', true ) ) {
		$town = 'Harare';
	}
	$query = ministry_front_maps_query( $title, $address, $town );
	$stamp = (string) get_post_meta( $id, '_mom_geocode_query', true );
	$lat   = (string) get_post_meta( $id, '_mom_lat', true );
	$lng   = (string) get_post_meta( $id, '_mom_lng', true );
	if ( $stamp === ministry_maps_geocode_stamp( $query ) && ministry_front_maps_coords( $lat, $lng ) ) {
		return;
	}
	$point = ministry_front_maps_point( $query );
	if ( $point && 16 === (int) $point[2] ) {
		ministry_office_store_point( $id, $query, $point );
		return;
	}
	update_post_meta( $id, '_mom_geocode_query', ministry_maps_geocode_stamp( $query ) );
	delete_post_meta( $id, '_mom_lat' );
	delete_post_meta( $id, '_mom_lng' );
}

/**
 * @param int                           $id    Office post ID.
 * @param string                        $query Address search text.
 * @param array{0: float, 1: float, 2: int} $point Map point.
 */
function ministry_office_store_point( $id, $query, $point ) {
	update_post_meta( $id, '_mom_geocode_query', ministry_maps_geocode_stamp( $query ) );
	update_post_meta( $id, '_mom_lat', sprintf( '%.6F', $point[0] ) );
	update_post_meta( $id, '_mom_lng', sprintf( '%.6F', $point[1] ) );
}

/**
 * @param string $query Address search text.
 */
function ministry_maps_geocode_stamp( $query ) {
	return 'v3|' . $query;
}

/**
 * Town-level pin when an office has no saved coordinates.
 *
 * @param string $query Search text.
 * @return array{0: float, 1: float}|null
 */
function ministry_front_maps_guess_coords( $query ) {
	$hay = strtolower( $query );
	$places = array(
		'zimre'     => array( -17.8316, 31.0448 ),
		'mutare'    => array( -18.9707, 32.6709 ),
		'bindura'   => array( -17.3019, 31.3306 ),
		'marondera' => array( -18.1853, 31.5519 ),
		'chinhoyi'  => array( -17.3667, 30.2000 ),
		'masvingo'  => array( -20.0744, 30.8327 ),
		'bulawayo'  => array( -20.1486, 28.5806 ),
		'gwanda'    => array( -20.9333, 29.0000 ),
		'gweru'     => array( -19.4500, 29.8167 ),
		'harare'    => array( -17.8292, 31.0522 ),
	);
	foreach ( $places as $name => $coords ) {
		if ( str_contains( $hay, $name ) ) {
			return $coords;
		}
	}
	return null;
}

/**
 * Remember that the Offices page needs MapLibre.
 */
function ministry_front_maps_mark_needed() {
	$GLOBALS['ministry_front_maps'] = true;
}

/**
 * OpenFreeMap (MapLibre) assets for office thumbnails.
 */
function ministry_front_maps_assets() {
	global $wp;
	$path = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
	if ( empty( $GLOBALS['ministry_front_maps'] ) && 'offices' !== $path ) {
		return;
	}
	$ver = '5.6.1';
	$css = MINISTRY_CONTENT_DIR . 'assets/maps.css';
	$js  = MINISTRY_CONTENT_DIR . 'assets/maps.js';
	wp_enqueue_style( 'maplibre-gl', 'https://cdn.jsdelivr.net/npm/maplibre-gl@' . $ver . '/dist/maplibre-gl.css', array(), $ver );
	wp_enqueue_style( 'ministry-maps', MINISTRY_CONTENT_URL . 'assets/maps.css', array( 'maplibre-gl' ), (string) filemtime( $css ) );
	wp_enqueue_script( 'maplibre-gl', 'https://cdn.jsdelivr.net/npm/maplibre-gl@' . $ver . '/dist/maplibre-gl.js', array(), $ver, true );
	wp_enqueue_script( 'ministry-maps', MINISTRY_CONTENT_URL . 'assets/maps.js', array( 'maplibre-gl' ), (string) filemtime( $js ), true );
}

add_action( 'wp_enqueue_scripts', 'ministry_front_maps_assets' );

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_invest( $body, $name ) {
	return $body;
}

/**
 * @param string $body Body markup.
 * @param string $name Design file name.
 */
function ministry_front_banners( $body, $name ) {
	if ( 'index' !== $name ) {
		return $body;
	}
	$base = ministry_design_baseline();
	$saved = isset( $base['banners'] ) && is_array( $base['banners'] ) ? $base['banners'] : array();
	$banners = ministry_banners();
	foreach ( $banners as $index => $banner ) {
		$thumb = (int) get_post_thumbnail_id( $banner );
		$old   = isset( $saved[ $index ]['thumb'] ) ? (int) $saved[ $index ]['thumb'] : 0;
		if ( ! $thumb || $thumb === $old ) {
			continue;
		}
		$url = wp_get_attachment_image_url( $thumb, 'large' );
		if ( ! $url ) {
			continue;
		}
		$seen = 0;
		$body = preg_replace_callback(
			'#(<div class="hero-slide"[^>]*>\s*<img[^>]*\ssrc=")[^"]*#',
			function ( $match ) use ( &$seen, $index, $url ) {
				$current = $seen;
				++$seen;
				if ( $current !== $index ) {
					return $match[0];
				}
				return $match[1] . esc_url( $url );
			},
			$body
		);
	}
	return $body;
}

/**
 * Live value for a public field, using the phone list when the single phone is empty.
 *
 * @param int    $post_id Record ID.
 * @param string $meta    Meta key.
 */
function ministry_front_meta_text( $post_id, $meta ) {
	if ( '_mom_phone' === $meta ) {
		$phones = get_post_meta( $post_id, '_mom_phones', true );
		if ( is_array( $phones ) ) {
			$numbers = array();
			foreach ( $phones as $row ) {
				if ( is_array( $row ) && isset( $row['number'] ) && '' !== (string) $row['number'] ) {
					$numbers[] = (string) $row['number'];
				}
			}
			if ( $numbers ) {
				return implode( ' · ', $numbers );
			}
		}
	}
	return (string) get_post_meta( $post_id, $meta, true );
}

/**
 * Published records indexed by design key.
 *
 * @param string $type Post type.
 * @return array<string, WP_Post>
 */
function ministry_front_keyed_posts( $type ) {
	$out = array();
	foreach ( ministry_posts( $type ) as $post ) {
		$key = (string) get_post_meta( $post->ID, '_mom_design_key', true );
		if ( '' !== $key ) {
			$out[ $key ] = $post;
		}
	}
	return $out;
}

/**
 * Published records added after the design import.
 *
 * @param string $type Post type.
 * @return WP_Post[]
 */
function ministry_front_extra_posts( $type ) {
	$base = ministry_design_baseline();
	$keys = isset( $base['keys'][ $type ] ) && is_array( $base['keys'][ $type ] ) ? $base['keys'][ $type ] : array();
	$out  = array();
	foreach ( ministry_posts( $type ) as $post ) {
		$key = (string) get_post_meta( $post->ID, '_mom_design_key', true );
		if ( '' === $key || ! in_array( $key, $keys, true ) ) {
			$out[] = $post;
		}
	}
	return $out;
}

/**
 * @param WP_Post $post Department.
 */
function ministry_front_department_link( $post ) {
	$slug = (string) get_post_meta( $post->ID, '_mom_design', true );
	$file = get_template_directory() . '/site/department-' . $slug . '.html';
	if ( $slug && is_readable( $file ) ) {
		return ministry_site_url( 'department-' . $slug );
	}
	return home_url( '/departments/' . $post->post_name . '/' );
}

/**
 * @param string $slug News slug.
 */
function ministry_front_dynamic_news( $slug ) {
	$posts = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => 'mom_news',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		)
	);
	if ( ! $posts ) {
		return;
	}
	$post = $posts[0];
	$main = '<div class="wrap"><div style="padding-top:20px"><nav class="crumbs" aria-label="Breadcrumb"><a href="' . esc_url( home_url( '/' ) ) . '">Home</a><span aria-hidden="true">&rsaquo;</span><a href="' . esc_url( ministry_site_url( 'news' ) ) . '">News &amp; Gallery</a><span aria-hidden="true">&rsaquo;</span><span aria-current="page">' . esc_html( $post->post_title ) . '</span></nav></div>';
	$main .= '<div class="article-layout"><article class="article"><h1>' . esc_html( $post->post_title ) . '</h1>';
	$main .= '<p class="standfirst">' . esc_html( $post->post_excerpt ) . '</p>';
	$main .= wp_kses_post( wpautop( $post->post_content ) );
	$main .= '</article></div></div>';
	$doc = function_exists( 'ministry_bind_wrap' ) ? ministry_bind_wrap( 'news', $main ) : array( 'head' => '', 'body' => $main );
	status_header( 200 );
	ministry_site_output( $doc['head'], $doc['body'] );
	exit;
}

/**
 * @param string $slug Department slug.
 */
function ministry_front_dynamic_department( $slug ) {
	$posts = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => 'mom_department',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		)
	);
	if ( ! $posts ) {
		return;
	}
	$post = $posts[0];
	$fns  = get_post_meta( $post->ID, '_mom_functions', true );
	$list = '';
	if ( is_array( $fns ) ) {
		foreach ( $fns as $line ) {
			$list .= '<li><span>' . esc_html( (string) $line ) . '</span></li>';
		}
	}
	$main  = '<div class="wrap"><div style="padding-top:20px"><nav class="crumbs" aria-label="Breadcrumb"><a href="' . esc_url( home_url( '/' ) ) . '">Home</a><span aria-hidden="true">&rsaquo;</span><a href="' . esc_url( ministry_site_url( 'departments' ) ) . '">Departments</a><span aria-hidden="true">&rsaquo;</span><span aria-current="page">' . esc_html( $post->post_title ) . '</span></nav></div></div>';
	$main .= '<section class="section" style="padding-top:16px"><div class="wrap dept-detail"><div>';
	$main .= '<h1 style="font-size:clamp(1.8rem,3vw,2.5rem)">' . esc_html( $post->post_title ) . '</h1>';
	$main .= '<div class="mandate-box"><p class="eyebrow" style="margin-bottom:6px">Mandate</p><p>' . esc_html( (string) get_post_meta( $post->ID, '_mom_mandate', true ) ) . '</p></div>';
	if ( $list ) {
		$main .= '<h2 style="font-size:1.4rem">Key functions</h2><ul class="check-list">' . $list . '</ul>';
	}
	$main .= '</div><aside><div class="hod-card"><div class="card-body"><p class="eyebrow">Head of department</p><h3>' . esc_html( (string) get_post_meta( $post->ID, '_mom_contact_name', true ) ) . '</h3><p class="small muted">' . esc_html( (string) get_post_meta( $post->ID, '_mom_head_title', true ) ) . '</p></div></div></aside></div></section>';
	$doc   = function_exists( 'ministry_bind_wrap' ) ? ministry_bind_wrap( 'departments', $main ) : array( 'head' => '', 'body' => $main );
	status_header( 200 );
	ministry_site_output( $doc['head'], $doc['body'] );
	exit;
}
