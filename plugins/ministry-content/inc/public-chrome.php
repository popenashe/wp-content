<?php
/**
 * Nav, legal pages, organogram, and extra public markup.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'ministry_front_copy_routes', 0 );

/**
 * Legal, sitemap, and organogram pages.
 */
function ministry_front_copy_routes() {
	if ( ! function_exists( 'ministry_site_routes' ) || is_admin() ) {
		return;
	}
	global $wp;
	$path = trim( (string) $wp->request, '/' );
	$map  = array(
		'privacy'         => 'privacy',
		'accessibility'   => 'accessibility',
		'information'     => 'information',
		'terms'           => 'terms',
		'verify'          => 'verify',
		'sitemap'         => 'sitemap',
		'about/structure' => 'structure',
	);
	if ( isset( $map[ $path ] ) ) {
		ministry_front_output_copy_page( $map[ $path ] );
	}
}

/**
 * @param string $key Page copy key.
 */
function ministry_front_output_copy_page( $key ) {
	$copy  = ministry_page_copy( $key );
	$title = $copy['title'] ? $copy['title'] : ucfirst( $key );
	$inner = '<section class="page-hero"><div class="copy"><nav class="crumbs" aria-label="Breadcrumb"><a href="' . esc_url( home_url( '/' ) ) . '">Home</a><span aria-hidden="true">&rsaquo;</span><span aria-current="page">' . esc_html( $title ) . '</span></nav>';
	if ( $copy['eyebrow'] ) {
		$inner .= '<p class="eyebrow">' . esc_html( $copy['eyebrow'] ) . '</p>';
	}
	$inner .= '<h1>' . esc_html( $title ) . '</h1>';
	if ( $copy['intro'] ) {
		$inner .= '<p class="lead">' . esc_html( $copy['intro'] ) . '</p>';
	}
	$inner .= '</div></section><section class="section"><div class="wrap">';
	if ( 'sitemap' === $key ) {
		$inner .= ministry_front_sitemap_html();
	} elseif ( 'structure' === $key ) {
		$inner .= ministry_front_organogram_html();
	} elseif ( $copy['notice'] ) {
		$inner .= wp_kses_post( wpautop( $copy['notice'] ) );
	} else {
		$inner .= '<p class="muted">' . esc_html__( 'This page is maintained from wp-admin → Pages. Add the official wording there.', 'ministry-content' ) . '</p>';
	}
	$inner .= '</div></section>';
	$doc = function_exists( 'ministry_bind_wrap' ) ? ministry_bind_wrap( $key, $inner ) : array( 'head' => '', 'body' => $inner );
	status_header( 200 );
	ministry_site_output( $doc['head'], $doc['body'] );
	exit;
}

/**
 * Extra binds that sit on top of the design HTML.
 *
 * @param string $name Design file name.
 * @param string $body Markup.
 */
function ministry_front_bind_more( $name, $body ) {
	$body = ministry_front_nav( $body, $name );
	$body = ministry_front_legal( $body );
	$body = ministry_front_home_extras( $body, $name );
	$body = ministry_front_claim( $body, $name );
	$body = ministry_front_invest_more( $body, $name );
	$body = ministry_front_contact_tables( $body, $name );
	$body = ministry_front_leaders_more( $body, $name );
	$body = ministry_front_entities_more( $body, $name );
	$body = ministry_front_offices_more( $body, $name );
	$body = ministry_front_services_more( $body, $name );
	return $body;
}

/**
 * @param string $body Markup.
 * @param string $name Design file.
 */
function ministry_front_nav( $body, $name ) {
	$items = isset( ministry_settings()['navItems'] ) ? ministry_settings()['navItems'] : array();
	if ( ! is_array( $items ) || ! $items ) {
		$items = ministry_default_settings()['navItems'];
	}
	if ( ! is_array( $items ) || ! $items ) {
		return $body;
	}
	$path = ministry_site_path( $name );
	$html = '';
	foreach ( $items as $item ) {
		$html .= ministry_front_nav_item( $item, $path );
	}
	if ( ! $html ) {
		return $body;
	}
	$updated = preg_replace( '#<ul id="primary-nav">.*?</ul>(?=\s*<a class="btn)#s', '<ul id="primary-nav">' . $html . '</ul>', $body, 1 );
	return is_string( $updated ) ? $updated : $body;
}

/**
 * @param mixed  $item Nav item.
 * @param string $path Current design path.
 */
function ministry_front_nav_item( $item, $path ) {
	if ( ! is_array( $item ) || empty( $item['label'] ) ) {
		return '';
	}
	$href     = ministry_front_href( isset( $item['href'] ) ? (string) $item['href'] : '/' );
	$on       = ministry_front_nav_current( $href, $path );
	$kids     = isset( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array();
	$sub      = '';
	$child_on = false;
	foreach ( $kids as $kid ) {
		if ( ! is_array( $kid ) || empty( $kid['label'] ) ) {
			continue;
		}
		$kid_href = ministry_front_href( isset( $kid['href'] ) ? (string) $kid['href'] : '/' );
		$kid_on   = ministry_front_nav_current( $kid_href, $path );
		$child_on = $child_on || $kid_on;
		$sub     .= '<li><a class="navlink" href="' . esc_url( $kid_href ) . '"' . ( $kid_on ? ' aria-current="page"' : '' ) . '>' . esc_html( (string) $kid['label'] ) . '</a></li>';
	}
	$class = trim( ( $sub ? 'has-sub' : '' ) . ( $on || $child_on ? ' is-current' : '' ) );
	$html  = '<li' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>';
	$html .= '<a class="navlink" href="' . esc_url( $href ) . '"' . ( $sub ? ' aria-haspopup="true"' : '' ) . ( $on && ! $child_on ? ' aria-current="page"' : '' ) . '>' . esc_html( (string) $item['label'] ) . '</a>';
	if ( $sub ) {
		$html .= '<ul class="sub-nav">' . $sub . '</ul>';
	}
	return $html . '</li>';
}

/**
 * @param string $href Stored URL.
 * @param string $path Current design path.
 */
function ministry_front_nav_current( $href, $path ) {
	$need = trim( (string) wp_parse_url( $href, PHP_URL_PATH ), '/' );
	$have = (string) $path;
	if ( '' === $need && '' === $have ) {
		return true;
	}
	return $need && ( $need === $have || str_starts_with( $have, $need . '/' ) );
}

/**
 * Footer legal links and the verify strip.
 *
 * @param string $body Markup.
 */
function ministry_front_legal( $body ) {
	$settings = ministry_settings();
	$links    = isset( $settings['legalLinks'] ) && is_array( $settings['legalLinks'] ) ? $settings['legalLinks'] : array();
	foreach ( $links as $link ) {
		if ( ! is_array( $link ) || empty( $link['label'] ) || empty( $link['href'] ) || '#' === $link['href'] ) {
			continue;
		}
		$body = ministry_front_set_link( $body, (string) $link['label'], (string) $link['href'] );
	}
	$verify = isset( $settings['verifyHref'] ) ? (string) $settings['verifyHref'] : '';
	if ( $verify && '#' !== $verify ) {
		$body = ministry_front_set_link( $body, 'How to verify official sites', $verify );
	}
	$wa = isset( $settings['whatsappNumber'] ) ? trim( (string) $settings['whatsappNumber'] ) : '';
	if ( $wa ) {
		$tel = preg_replace( '/\s+/', '', $wa );
		$tag = '<a href="https://wa.me/' . esc_attr( ltrim( $tel, '+' ) ) . '">' . esc_html( $wa ) . '</a>';
		$body = str_replace( '<span class="tbc-tag">number to be confirmed</span>', $tag, $body );
	}
	return $body;
}

/**
 * Homepage tasks, headings, hero destinations, rotate, agency strip.
 *
 * @param string $body Markup.
 * @param string $name Design file.
 */
function ministry_front_home_extras( $body, $name ) {
	if ( 'index' !== $name ) {
		return $body;
	}
	$settings = ministry_settings();
	$base     = ministry_design_baseline();
	$saved    = isset( $base['settings'] ) && is_array( $base['settings'] ) ? $base['settings'] : array();
	foreach ( array( 'homeNewsEyebrow', 'homeNewsHeading', 'homeOpportunitiesEyebrow', 'homeOpportunitiesHeading', 'gatewaysHeading' ) as $key ) {
		$from = isset( $saved[ $key ] ) ? (string) $saved[ $key ] : ( isset( ministry_default_settings()[ $key ] ) ? (string) ministry_default_settings()[ $key ] : '' );
		$to   = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
		$body = ministry_front_text( $body, $from, $to, 4 );
	}
	$hero = isset( $settings['homeHero'] ) && is_array( $settings['homeHero'] ) ? $settings['homeHero'] : array();
	if ( ! empty( $hero['primaryHref'] ) ) {
		$body = preg_replace_callback(
			'#(<div class="actions">\s*<a class="btn btn-gold"[^>]*href=")[^"]*#',
			function ( $match ) use ( $hero ) {
				return $match[1] . esc_url( ministry_front_href( (string) $hero['primaryHref'] ) );
			},
			$body,
			1
		);
	}
	$cadastre = isset( $settings['cadastrePortalUrl'] ) ? (string) $settings['cadastrePortalUrl'] : '';
	$cta      = isset( $settings['heroPrimaryCta'] ) && is_array( $settings['heroPrimaryCta'] ) ? $settings['heroPrimaryCta'] : array();
	$second   = ! empty( $cta['href'] ) ? (string) $cta['href'] : $cadastre;
	if ( $second ) {
		$body = preg_replace_callback(
			'#(<a class="btn btn-ghost-light"[^>]*href=")[^"]*#',
			function ( $match ) use ( $second ) {
				return $match[1] . esc_url( ministry_front_href( $second ) );
			},
			$body,
			1
		);
	}
	$seconds = isset( $settings['heroRotateSeconds'] ) ? (int) $settings['heroRotateSeconds'] : 6;
	if ( $seconds < 3 ) {
		$seconds = 3;
	}
	$body = preg_replace( '#<section class="hero2"#', '<section class="hero2" data-rotate="' . esc_attr( (string) $seconds ) . '"', $body, 1 );
	$tasks = isset( $settings['homeTasks'] ) && is_array( $settings['homeTasks'] ) ? $settings['homeTasks'] : array();
	$from  = isset( $saved['homeTasks'] ) && is_array( $saved['homeTasks'] ) ? $saved['homeTasks'] : ministry_default_settings()['homeTasks'];
	if ( $tasks && $tasks !== $from ) {
		$html = '';
		foreach ( $tasks as $task ) {
			if ( ! is_array( $task ) || empty( $task['title'] ) ) {
				continue;
			}
			$html .= '<a class="task" href="' . esc_url( ministry_front_href( isset( $task['href'] ) ? (string) $task['href'] : '#' ) ) . '"><span class="well">' . ministry_front_pin_svg() . '</span><span><strong>' . esc_html( (string) $task['title'] ) . '</strong><span>' . esc_html( isset( $task['desc'] ) ? (string) $task['desc'] : '' ) . '</span></span></a>';
		}
		if ( $html ) {
			$updated = preg_replace( '#(<div class="tasks">).*?(</div>)#s', '$1' . $html . '$2', $body, 1 );
			$body    = is_string( $updated ) ? $updated : $body;
		}
	}
	return ministry_front_home_agencies( $body, $settings );
}

/**
 * @param string               $body     Markup.
 * @param array<string, mixed> $settings Settings.
 */
function ministry_front_home_agencies( $body, $settings ) {
	$entities = isset( $settings['entities'] ) && is_array( $settings['entities'] ) ? $settings['entities'] : array();
	$used     = array();
	$body     = preg_replace_callback(
		'#<a class="agency"[^>]*>.*?</a>#s',
		function ( $match ) use ( $entities, &$used ) {
			$block = $match[0];
			foreach ( $entities as $index => $entity ) {
				if ( isset( $used[ $index ] ) || ! is_array( $entity ) || ( isset( $entity['show'] ) && '0' === (string) $entity['show'] ) ) {
					continue;
				}
				$acro = isset( $entity['acronym'] ) ? (string) $entity['acronym'] : '';
				if ( $acro && ( str_contains( $block, '>' . $acro . '<' ) || str_contains( $block, '#' . strtolower( $acro ) ) ) ) {
					$used[ $index ] = true;
					return ministry_front_home_agency_tile( $entity );
				}
			}
			return $block;
		},
		$body
	);
	$extra = '';
	foreach ( $entities as $index => $entity ) {
		if ( isset( $used[ $index ] ) || ! is_array( $entity ) || empty( $entity['acronym'] ) ) {
			continue;
		}
		if ( isset( $entity['show'] ) && '0' === (string) $entity['show'] ) {
			continue;
		}
		if ( 0 === strcasecmp( (string) $entity['acronym'], 'MAB' ) ) {
			continue;
		}
		$extra .= ministry_front_home_agency_tile( $entity );
	}
	if ( $extra ) {
		$updated = preg_replace( '#(<div class="grid g6">)(.*?)(</div>)#s', '$1$2' . $extra . '$3', $body, 1 );
		$body    = is_string( $updated ) ? $updated : $body;
	}
	return is_string( $body ) ? $body : '';
}

/**
 * Homepage parastatal tile, with logo when one is stored.
 *
 * @param array<string, mixed> $entity Agency.
 */
function ministry_front_home_agency_tile( $entity ) {
	$acro = isset( $entity['acronym'] ) ? (string) $entity['acronym'] : '';
	$href = ministry_site_url( 'parastatals' ) . '#' . sanitize_title( $acro );
	$logo = function_exists( 'ministry_front_entity_image' ) ? ministry_front_entity_image( $entity, 'logo' ) : '';
	$html = '<a class="agency' . ( $logo ? ' has-logo' : '' ) . '" href="' . esc_url( $href ) . '">';
	if ( $logo ) {
		$html .= '<span class="agency-logo"><img src="' . esc_url( $logo ) . '" alt="' . esc_attr( $acro . ' logo' ) . '"></span>';
	} else {
		$html .= '<span class="agency-logo ph">' . esc_html( $acro ) . '</span>';
	}
	$html .= '<strong>' . esc_html( $acro ) . '</strong><span>' . esc_html( (string) $entity['name'] ) . '</span></a>';
	return $html;
}

/**
 * Claim steps, checklist, and fee schedule.
 *
 * @param string $body Markup.
 * @param string $name Design file.
 */
function ministry_front_claim( $body, $name ) {
	if ( 'how-to-get-a-mining-claim' !== $name ) {
		return $body;
	}
	$settings = ministry_settings();
	$defaults = ministry_default_settings();
	$notice   = isset( $settings['claimNotice'] ) ? (string) $settings['claimNotice'] : '';
	$from     = isset( $defaults['claimNotice'] ) ? (string) $defaults['claimNotice'] : '';
	if ( $notice && $notice !== $from ) {
		$updated = preg_replace( '#(<div class="note"[^>]*>.*?<span>).*?(</span></div>)#s', '$1' . esc_html( $notice ) . '$2', $body, 1 );
		$body    = is_string( $updated ) ? $updated : $body;
	}
	$steps = isset( $settings['claimSteps'] ) && is_array( $settings['claimSteps'] ) ? $settings['claimSteps'] : array();
	$base  = isset( $defaults['claimSteps'] ) ? $defaults['claimSteps'] : array();
	if ( $steps && $steps !== $base ) {
		$html = '';
		foreach ( $steps as $i => $step ) {
			if ( ! is_array( $step ) || empty( $step['title'] ) ) {
				continue;
			}
			$needs = array_filter( array_map( 'trim', explode( "\n", isset( $step['need'] ) ? (string) $step['need'] : '' ) ) );
			$list  = '';
			foreach ( $needs as $need ) {
				$list .= '<li>' . esc_html( $need ) . '</li>';
			}
			$html .= '<div class="claim-step"><span class="n">' . esc_html( (string) ( $i + 1 ) ) . '</span><div><h3>' . esc_html( (string) $step['title'] ) . '</h3><p>' . esc_html( isset( $step['desc'] ) ? (string) $step['desc'] : '' ) . '</p></div>';
			$html .= '<div class="info"><div>' . ministry_front_pin_svg() . '<span><strong>Where:</strong> ' . esc_html( isset( $step['where'] ) ? (string) $step['where'] : '' ) . '</span></div>';
			$html .= '<div>' . ministry_front_pin_svg() . '<span><strong>Timeline:</strong> ' . esc_html( isset( $step['timeline'] ) ? (string) $step['timeline'] : '' ) . '</span></div>';
			$html .= '<strong>You need:</strong><ul>' . $list . '</ul></div></div>';
		}
		if ( $html ) {
			$updated = preg_replace( '#(<div class="claim-steps">).*?(</div>\s*<aside)#s', '$1' . $html . '</div><aside', $body, 1 );
			$body    = is_string( $updated ) ? $updated : $body;
		}
	}
	$list = isset( $settings['claimChecklist'] ) && is_array( $settings['claimChecklist'] ) ? $settings['claimChecklist'] : array();
	$old  = isset( $defaults['claimChecklist'] ) ? $defaults['claimChecklist'] : array();
	if ( $list && $list !== $old ) {
		$items = '';
		foreach ( $list as $item ) {
			$items .= '<li>' . esc_html( (string) $item ) . '</li>';
		}
		$updated = preg_replace( '#(<ul class="req-list">).*?(</ul>)#s', '$1' . $items . '$2', $body, 1 );
		$body    = is_string( $updated ) ? $updated : $body;
	}
	return ministry_front_fees_box( $body, $settings, $defaults );
}

/**
 * Replace the claim-page TBC fees box once the schedule differs from defaults.
 *
 * @param string               $body     Markup.
 * @param array<string, mixed> $settings Live.
 * @param array<string, mixed> $defaults Defaults.
 */
function ministry_front_fees_box( $body, $settings, $defaults ) {
	$fees = isset( $settings['fees'] ) && is_array( $settings['fees'] ) ? $settings['fees'] : array();
	$from = isset( $defaults['fees'] ) ? $defaults['fees'] : array();
	if ( ! $fees || $fees === $from ) {
		return $body;
	}
	$rows = '';
	foreach ( $fees as $fee ) {
		if ( ! is_array( $fee ) || empty( $fee['service'] ) ) {
			continue;
		}
		$rows .= '<tr><td>' . esc_html( (string) $fee['service'] ) . '</td><td>' . esc_html( isset( $fee['amount'] ) ? (string) $fee['amount'] : '' ) . '</td></tr>';
	}
	$heading = isset( $settings['feesHeading'] ) ? (string) $settings['feesHeading'] : 'Fees';
	$ref     = isset( $settings['feesReference'] ) ? (string) $settings['feesReference'] : '';
	$box     = '<div class="side-card" style="background:#fff"><h3>' . esc_html( $heading ) . '</h3>';
	if ( $ref ) {
		$box .= '<p class="small muted">' . esc_html( $ref ) . '</p>';
	}
	$box .= '<div class="table-wrap"><table><tbody>' . $rows . '</tbody></table></div></div>';
	$updated = preg_replace( '#<div class="side-card" style="background:#fff"><h3>Fees</h3>.*?</div>#s', $box, $body, 1 );
	return is_string( $updated ) ? $updated : $body;
}

/**
 * Invest headings, CTA, and flagged agencies.
 *
 * @param string $body Markup.
 * @param string $name Design file.
 */
function ministry_front_invest_more( $body, $name ) {
	if ( 'invest' !== $name ) {
		return $body;
	}
	$settings = ministry_settings();
	$defaults = ministry_default_settings();
	foreach ( array( 'investHighlightsHeading', 'licensingHeading', 'investPortfolioEyebrow', 'investPortfolioHeading' ) as $key ) {
		$body = ministry_front_text( $body, (string) $defaults[ $key ], (string) $settings[ $key ], 4 );
	}
	$cta = isset( $settings['investCta'] ) && is_array( $settings['investCta'] ) ? $settings['investCta'] : array();
	$old = $defaults['investCta'];
	if ( is_array( $cta ) && is_array( $old ) ) {
		$body = ministry_front_text( $body, (string) $old['title'], (string) $cta['title'] );
		$body = ministry_front_text( $body, (string) $old['text'], (string) $cta['text'] );
		$body = ministry_front_text( $body, (string) $old['label'], (string) $cta['label'], 2 );
		if ( ! empty( $cta['href'] ) && $cta['href'] !== $old['href'] ) {
			$body = ministry_front_set_link( $body, (string) $cta['label'], (string) $cta['href'] );
		}
	}
	$cards = '';
	foreach ( isset( $settings['entities'] ) && is_array( $settings['entities'] ) ? $settings['entities'] : array() as $entity ) {
		if ( ! is_array( $entity ) || empty( $entity['invest'] ) || '1' !== (string) $entity['invest'] ) {
			continue;
		}
		$cards .= ministry_front_home_agency_tile( $entity );
	}
	if ( $cards ) {
		$body = str_replace( '<div class="promo"', '<div class="grid g6" style="margin:0 0 28px">' . $cards . '</div><div class="promo"', $body );
	}
	return $body;
}

/**
 * @param string $body Markup.
 * @param string $name Design file.
 */
function ministry_front_contact_tables( $body, $name ) {
	if ( 'contact' !== $name ) {
		return $body;
	}
	$centres = ministry_posts( 'mom_department', ministry_structure_query( 'centre' ) );
	$provs   = ministry_posts( 'mom_department', ministry_structure_query( 'province' ) );
	if ( $centres ) {
		$rows = '';
		foreach ( $centres as $post ) {
			$rows .= ministry_front_contact_row( $post, false );
		}
		$body = ministry_front_replace_tbody( $body, $rows, 0 );
	}
	if ( $provs ) {
		$rows = '';
		foreach ( $provs as $post ) {
			$rows .= ministry_front_contact_row( $post, true );
		}
		$body = ministry_front_replace_tbody( $body, $rows, 1 );
	}
	return $body;
}

/**
 * @param WP_Post $post Office.
 * @param bool    $link Link heading to offices page.
 */
function ministry_front_contact_row( $post, $link ) {
	$title   = $post->post_title;
	$summary = (string) get_post_meta( $post->ID, '_mom_summary', true );
	$address = ministry_front_meta_text( $post->ID, '_mom_address' );
	$phone   = ministry_front_meta_text( $post->ID, '_mom_phone' );
	$cell    = $link ? '<a href="' . esc_url( ministry_site_url( 'offices' ) . '#' . $post->post_name ) . '"><strong>' . esc_html( $title ) . '</strong></a>' : '<strong>' . esc_html( $title ) . '</strong>';
	if ( $summary ) {
		$cell .= '<br><span class="small muted">' . esc_html( $summary ) . '</span>';
	}
	return '<tr><td>' . $cell . '</td><td class="small">' . esc_html( $address ? $address : '—' ) . '</td><td class="small">' . esc_html( $phone ? $phone : '—' ) . '</td></tr>';
}

/**
 * @param string $body Markup.
 * @param string $rows Tbody rows.
 * @param int    $index Table index.
 */
function ministry_front_replace_tbody( $body, $rows, $index ) {
	$count = 0;
	$updated = preg_replace_callback(
		'#(<div class="table-wrap"><table>.*?<tbody>).*?(</tbody>)#s',
		function ( $match ) use ( $rows, $index, &$count ) {
			$current = $count;
			++$count;
			if ( $current !== (int) $index ) {
				return $match[0];
			}
			return $match[1] . $rows . $match[2];
		},
		$body
	);
	return is_string( $updated ) ? $updated : $body;
}

/**
 * Leader facts, email, acting, PDF, and extra people cards.
 *
 * @param string $body Markup.
 * @param string $name Design file.
 */
function ministry_front_leaders_more( $body, $name ) {
	$base    = ministry_design_baseline();
	$saved   = isset( $base['leaders'] ) && is_array( $base['leaders'] ) ? $base['leaders'] : array();
	$leaders = ministry_leaders();
	if ( ! is_array( $leaders ) ) {
		return $body;
	}
	$used = array();
	foreach ( $saved as $index => $leader ) {
		if ( ! is_array( $leader ) || empty( $leader['name'] ) ) {
			continue;
		}
		$now = ministry_front_leader_row( $leaders, (string) $leader['name'], $used );
		if ( ! $now && isset( $leaders[ $index ] ) && is_array( $leaders[ $index ] ) && ! isset( $used[ $index ] ) ) {
			$now            = $leaders[ $index ];
			$used[ $index ] = true;
		}
		if ( ! is_array( $now ) ) {
			continue;
		}
		$body = ministry_front_region(
			$body,
			'article',
			(string) $leader['name'],
			function ( $region ) use ( $now ) {
				return ministry_front_leader_fields( $region, $now );
			}
		);
	}
	if ( 'the-ministry' === $name || 'index' === $name ) {
		foreach ( $leaders as $index => $leader ) {
			if ( isset( $used[ $index ] ) || ! is_array( $leader ) || empty( $leader['name'] ) ) {
				continue;
			}
			if ( isset( $leader['status'] ) && 'former' === $leader['status'] ) {
				continue;
			}
			if ( 'index' === $name && isset( $leader['home'] ) && '0' === (string) $leader['home'] ) {
				continue;
			}
			if ( 'index' === $name ) {
				$body = str_replace( '<div class="people">', '<div class="people">' . ministry_front_person_tile( $leader ), $body );
			} else {
				$body = str_replace( '<div class="cta-strip">', ministry_front_leader_card( $leader ) . '<div class="cta-strip">', $body );
			}
		}
	}
	return $body;
}

/**
 * @param string               $region Card markup.
 * @param array<string, mixed> $now    Person.
 */
function ministry_front_leader_fields( $region, $now ) {
	if ( ! empty( $now['acting'] ) && '1' === (string) $now['acting'] && ! str_contains( $region, 'Acting ' ) ) {
		$region = preg_replace( '#(<p class="eyebrow"[^>]*>)([^<]+)#', '$1Acting $2', $region, 1 );
	}
	if ( ! empty( $now['facts'] ) && is_array( $now['facts'] ) ) {
		$dl = ministry_front_facts_dl( $now['facts'] );
		if ( $dl ) {
			$replaced = preg_replace( '#<dl class="facts">.*?</dl>#s', $dl, $region, 1 );
			$region   = is_string( $replaced ) ? $replaced : $region;
		}
	}
	if ( ! empty( $now['showEmail'] ) && '1' === (string) $now['showEmail'] && ! empty( $now['email'] ) ) {
		$mail = '<p class="small"><a href="mailto:' . esc_attr( (string) $now['email'] ) . '">' . esc_html( (string) $now['email'] ) . '</a></p>';
		if ( ! str_contains( $region, (string) $now['email'] ) ) {
			$region = str_replace( '<dl class="facts">', $mail . '<dl class="facts">', $region );
		}
	}
	$pdf = '';
	if ( ! empty( $now['profileId'] ) ) {
		$pdf = wp_get_attachment_url( (int) $now['profileId'] );
	}
	if ( ! $pdf && ! empty( $now['profileUrl'] ) ) {
		$pdf = (string) $now['profileUrl'];
	}
	if ( $pdf ) {
		$region = preg_replace( '#href="[^"]*profile-[^"]+\.pdf"#', 'href="' . esc_url( $pdf ) . '"', $region, 1 );
	}
	return $region;
}

/**
 * @param array<int, string> $facts Lines.
 */
function ministry_front_facts_dl( $facts ) {
	$html = '<dl class="facts">';
	$n    = 0;
	foreach ( $facts as $fact ) {
		$fact = trim( (string) $fact );
		if ( '' === $fact ) {
			continue;
		}
		$label = $fact;
		$value = '';
		if ( str_contains( $fact, '|' ) ) {
			$parts = array_map( 'trim', explode( '|', $fact, 2 ) );
			$label = $parts[0];
			$value = isset( $parts[1] ) ? $parts[1] : '';
		} elseif ( str_contains( $fact, ':' ) ) {
			$parts = array_map( 'trim', explode( ':', $fact, 2 ) );
			$label = $parts[0];
			$value = isset( $parts[1] ) ? $parts[1] : '';
		}
		$html .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
		++$n;
	}
	$html .= '</dl>';
	return $n ? $html : '';
}

/**
 * @param array<string, mixed> $leader Person.
 */
function ministry_front_person_tile( $leader ) {
	$photo = isset( $leader['imageUrl'] ) ? (string) $leader['imageUrl'] : '';
	$img   = $photo ? '<img src="' . esc_url( $photo ) . '" alt="' . esc_attr( (string) $leader['name'] ) . '" loading="lazy">' : '';
	return '<a class="person" href="' . esc_url( ministry_site_url( 'the-ministry' ) ) . '#' . esc_attr( sanitize_title( (string) $leader['name'] ) ) . '">' . $img . '<div class="card-body"><h3>' . esc_html( (string) $leader['name'] ) . '</h3><p class="small muted" style="margin:0">' . esc_html( (string) $leader['title'] ) . '</p></div></a>';
}

/**
 * @param array<string, mixed> $leader Person.
 */
function ministry_front_leader_card( $leader ) {
	$photo = isset( $leader['imageUrl'] ) ? (string) $leader['imageUrl'] : '';
	$img   = $photo ? '<img src="' . esc_url( $photo ) . '" alt="' . esc_attr( (string) $leader['name'] ) . '" loading="lazy">' : '';
	$html  = '<article class="leader-card" id="' . esc_attr( sanitize_title( (string) $leader['name'] ) ) . '"><div class="portrait">' . $img . '</div><div>';
	$html .= '<p class="eyebrow" style="color:var(--green)">' . esc_html( (string) $leader['title'] ) . '</p><h2>' . esc_html( (string) $leader['name'] ) . '</h2>';
	$html .= '<p>' . esc_html( (string) $leader['bio'] ) . '</p>';
	if ( ! empty( $leader['facts'] ) && is_array( $leader['facts'] ) ) {
		$html .= ministry_front_facts_dl( $leader['facts'] );
	}
	$html .= '</div></article>';
	return $html;
}

/**
 * Agency chair/email, MAB members, extra agency cards.
 *
 * @param string $body Markup.
 * @param string $name Design file.
 */
function ministry_front_entities_more( $body, $name ) {
	$entities = ministry_entities();
	if ( ! is_array( $entities ) ) {
		return $body;
	}
	$base = ministry_design_baseline();
	$saved = isset( $base['entities'] ) && is_array( $base['entities'] ) ? $base['entities'] : array();
	$used  = array();
	foreach ( $saved as $index => $entity ) {
		$now = is_array( $entity ) ? ministry_front_entity_row( $entities, (string) $entity['name'], isset( $entity['acronym'] ) ? (string) $entity['acronym'] : '', $used ) : null;
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
			function ( $region ) use ( $now ) {
				$bits = '';
				if ( ! empty( $now['chair'] ) ) {
					$bits .= '<p class="small"><strong>Chairperson:</strong> ' . esc_html( (string) $now['chair'] ) . '</p>';
				}
				if ( ! empty( $now['email'] ) ) {
					$bits .= '<p class="small"><a href="mailto:' . esc_attr( (string) $now['email'] ) . '">' . esc_html( (string) $now['email'] ) . '</a></p>';
				}
				if ( $bits && ! str_contains( $region, 'Chairperson:' ) ) {
					$region = str_replace( '<div style="display:flex;justify-content:space-between', $bits . '<div style="display:flex;justify-content:space-between', $region );
				}
				return $region;
			}
		);
	}
	$mab = null;
	foreach ( $entities as $entity ) {
		if ( is_array( $entity ) && isset( $entity['acronym'] ) && 0 === strcasecmp( (string) $entity['acronym'], 'MAB' ) ) {
			$mab = $entity;
			break;
		}
	}
	$members = ( $mab && ! empty( $mab['members'] ) && is_array( $mab['members'] ) ) ? $mab['members'] : array();
	if ( ! $members && ! empty( ministry_settings()['mabMembers'] ) && is_array( ministry_settings()['mabMembers'] ) ) {
		$members = ministry_settings()['mabMembers'];
	}
	$default_members = ministry_default_settings()['mabMembers'];
	if ( is_array( $members ) && $members && $members !== $default_members ) {
		$html = '';
		foreach ( $members as $row ) {
			if ( ! is_array( $row ) || empty( $row['name'] ) ) {
				continue;
			}
			$role  = isset( $row['role'] ) ? (string) $row['role'] : '';
			$class = $role ? ' class="hl"' : '';
			$html .= '<div' . $class . '>' . esc_html( (string) $row['name'] ) . ( $role ? '<small>' . esc_html( $role ) . '</small>' : '' ) . '</div>';
		}
		if ( $html ) {
			$updated = preg_replace( '#(<div class="board">).*?(</div>)#s', '$1' . $html . '$2', $body, 1 );
			$body    = is_string( $updated ) ? $updated : $body;
		}
	}
	if ( 'parastatals' === $name ) {
		$extra = '';
		foreach ( $entities as $index => $entity ) {
			if ( isset( $used[ $index ] ) || ! is_array( $entity ) || empty( $entity['name'] ) ) {
				continue;
			}
			if ( isset( $entity['show'] ) && '0' === (string) $entity['show'] ) {
				continue;
			}
			if ( isset( $entity['acronym'] ) && 0 === strcasecmp( (string) $entity['acronym'], 'MAB' ) ) {
				continue;
			}
			$extra .= ministry_front_agency_card( $entity );
		}
		if ( $extra ) {
			$body = str_replace( '<div class="board-wrap"', $extra . '<div class="board-wrap"', $body );
		}
	}
	return $body;
}

/**
 * @param array<string, mixed> $entity Agency.
 */
function ministry_front_agency_card( $entity ) {
	$acro  = isset( $entity['acronym'] ) ? (string) $entity['acronym'] : '';
	$logo  = ministry_front_entity_image( $entity, 'logo' );
	$cover = ministry_front_entity_image( $entity, 'cover' );
	$img   = $cover ? esc_url( $cover ) : esc_url( get_template_directory_uri() . '/site/images/open-pit.jpg' );
	$badge = $logo ? '<span class="logo-ph has-logo"><img src="' . esc_url( $logo ) . '" alt=""></span>' : '<span class="logo-ph">' . esc_html( $acro ) . '<br>LOGO</span>';
	$html  = '<article class="card img-card agency-card" id="' . esc_attr( sanitize_title( $acro ) ) . '"><div class="thumb"><img src="' . $img . '" alt="" loading="lazy">' . $badge . '</div>';
	$html .= '<div class="card-body"><p class="eyebrow">' . esc_html( isset( $entity['type'] ) ? (string) $entity['type'] : 'State enterprise' ) . '</p>';
	$html .= '<h2>' . esc_html( $acro ) . '</h2><p class="small muted">' . esc_html( (string) $entity['name'] ) . '</p>';
	$html .= '<p>' . esc_html( wp_strip_all_tags( (string) $entity['desc'] ) ) . '</p>';
	$href  = isset( $entity['href'] ) ? (string) $entity['href'] : '#';
	$html .= '<div style="display:flex;justify-content:space-between;gap:10px;border-top:1px solid var(--line);padding-top:12px;margin-top:auto;font-size:.82rem;font-weight:700;align-items:center">';
	$html .= '<a href="' . esc_url( ministry_front_href( $href ) ) . '">Visit website</a></div></div></article>';
	return $html;
}

/**
 * Extra office cards.
 *
 * @param string $body Markup.
 * @param string $name Design file.
 */
function ministry_front_offices_more( $body, $name ) {
	if ( 'offices' !== $name ) {
		return $body;
	}
	$base = ministry_design_baseline();
	$keys = isset( $base['keys']['mom_department'] ) && is_array( $base['keys']['mom_department'] ) ? $base['keys']['mom_department'] : array();
	foreach ( array( 'centre' => 'ho-card', 'province' => 'prov-card' ) as $kind => $class ) {
		$extra = '';
		foreach ( ministry_posts( 'mom_department', ministry_structure_query( $kind ) ) as $post ) {
			$key = (string) get_post_meta( $post->ID, '_mom_design_key', true );
			if ( $key && in_array( $key, $keys, true ) ) {
				continue;
			}
			$extra .= ministry_front_office_inject( $post, $kind );
		}
		if ( $extra ) {
			$body = ministry_front_insert_after_last_article( $body, $class, $extra );
		}
	}
	return $body;
}

/**
 * @param string $html  Markup.
 * @param string $class Card class.
 * @param string $extra Cards to insert.
 */
function ministry_front_insert_after_last_article( $html, $class, $extra ) {
	$needle = 'class="' . $class . '"';
	$pos    = strrpos( $html, $needle );
	if ( false === $pos ) {
		$needle = "class='" . $class . "'";
		$pos    = strrpos( $html, $needle );
	}
	if ( false === $pos ) {
		return $html;
	}
	$end = strpos( $html, '</article>', $pos );
	if ( false === $end ) {
		return $html;
	}
	$end += strlen( '</article>' );
	return substr( $html, 0, $end ) . $extra . substr( $html, $end );
}

/**
 * @param WP_Post $post Office.
 * @param string  $kind centre|province.
 */
function ministry_front_office_inject( $post, $kind ) {
	$title   = $post->post_title;
	$summary = (string) get_post_meta( $post->ID, '_mom_summary', true );
	$address = ministry_front_meta_text( $post->ID, '_mom_address' );
	$phone   = ministry_front_meta_text( $post->ID, '_mom_phone' );
	$email   = ministry_front_meta_text( $post->ID, '_mom_email' );
	$town    = ministry_front_meta_text( $post->ID, '_mom_town' );
	$head    = (string) get_post_meta( $post->ID, '_mom_contact_name', true );
	if ( 'centre' === $kind ) {
		return '<article class="ho-card"><div class="map-ph"><span class="pin">' . ministry_front_pin_svg() . '</span><b>' . esc_html( $title ) . '</b></div><div class="card-body"><h3>' . esc_html( $title ) . '</h3><p class="small muted" style="margin:0">' . esc_html( $summary ) . '</p><div class="row">' . ministry_front_pin_svg() . '<span>' . esc_html( $address ) . '</span></div><div class="row">' . ministry_front_pin_svg() . '<span>' . esc_html( $phone ) . '</span></div><div class="row">' . ministry_front_pin_svg() . '<span>' . esc_html( $email ) . '</span></div></div></article>';
	}
	return '<article class="prov-card" id="' . esc_attr( $post->post_name ) . '"><div class="head"><div><h3>' . esc_html( $title ) . '</h3><span class="small muted">' . esc_html( $town ) . '</span></div></div><div class="pmd"><small>Provincial Mining Director</small>' . ( $head ? '<strong>' . esc_html( $head ) . '</strong>' : '<span class="tbc-tag">Director TBC</span>' ) . '</div><div class="row" style="display:flex;gap:8px">' . ministry_front_pin_svg() . '<span>' . esc_html( $address ) . '</span></div><div class="row" style="display:flex;gap:8px">' . ministry_front_pin_svg() . '<span>' . esc_html( $phone ) . '</span></div><div class="row" style="display:flex;gap:8px">' . ministry_front_pin_svg() . '<span>' . esc_html( $email ) . '</span></div></article>';
}

/**
 * Service fee lines and customer-care standards.
 *
 * @param string $body Markup.
 * @param string $name Design file.
 */
function ministry_front_services_more( $body, $name ) {
	if ( 'services' !== $name ) {
		return $body;
	}
	$settings = ministry_settings();
	$defaults = ministry_default_settings();
	$std      = isset( $settings['serviceStandards'] ) && is_array( $settings['serviceStandards'] ) ? $settings['serviceStandards'] : array();
	$from     = isset( $defaults['serviceStandards'] ) ? $defaults['serviceStandards'] : array();
	if ( $std && $std !== $from ) {
		$html = '';
		foreach ( $std as $row ) {
			if ( ! is_array( $row ) || empty( $row['label'] ) ) {
				continue;
			}
			$html .= '<div class="svc-std"><span>' . esc_html( (string) $row['label'] ) . '</span><strong>' . esc_html( isset( $row['value'] ) ? (string) $row['value'] : '' ) . '</strong></div>';
		}
		if ( $html ) {
			$updated = preg_replace( '#(<div class="care">.*?</header>).*?(</div>\s*<aside)#s', '$1' . $html . '</div><aside', $body, 1 );
			$body    = is_string( $updated ) ? $updated : $body;
		}
	}
	return $body;
}

/**
 * Full service copy shown after a card is opened.
 *
 * @param WP_Post $post Service.
 */
function ministry_front_service_detail_html( $post ) {
	$data = get_post_meta( $post->ID, '_mom_service', true );
	$data = is_array( $data ) ? $data : array();
	$html = '';
	$turn = trim( ( isset( $data['number'] ) ? (string) $data['number'] : '' ) . ' ' . ( isset( $data['unit'] ) ? (string) $data['unit'] : '' ) );
	if ( $turn ) {
		$html .= '<p class="small"><strong>Turnaround:</strong> ' . esc_html( $turn ) . '</p>';
	}
	if ( ! empty( $data['category'] ) ) {
		$html .= '<p class="small"><strong>Category:</strong> ' . esc_html( (string) $data['category'] ) . '</p>';
	}
	if ( $post->post_content ) {
		$html .= '<div class="svc-body">' . wp_kses_post( $post->post_content ) . '</div>';
	}
	foreach ( array( 'needs' => 'What you need', 'steps' => 'How to apply' ) as $key => $label ) {
		$items = isset( $data[ $key ] ) && is_array( $data[ $key ] ) ? $data[ $key ] : array();
		$items = array_filter( array_map( 'strval', $items ) );
		if ( ! $items ) {
			continue;
		}
		$html .= '<h4>' . esc_html( $label ) . '</h4><ul>';
		foreach ( $items as $item ) {
			$html .= '<li>' . esc_html( $item ) . '</li>';
		}
		$html .= '</ul>';
	}
	$fees = isset( $data['fees'] ) && is_array( $data['fees'] ) ? $data['fees'] : array();
	$lines = '';
	foreach ( $fees as $fee ) {
		if ( ! is_array( $fee ) || empty( $fee['amount'] ) ) {
			continue;
		}
		$lines .= '<li>' . esc_html( (string) $fee['label'] ) . ': ' . esc_html( (string) $fee['amount'] ) . '</li>';
	}
	if ( $lines ) {
		$html .= '<h4>Fees</h4><ul class="fee-lines small">' . $lines . '</ul>';
	}
	if ( ! empty( $data['office'] ) ) {
		$html .= '<p class="small"><strong>Where:</strong> ' . esc_html( (string) $data['office'] ) . '</p>';
	}
	if ( ! empty( $data['department'] ) ) {
		$html .= '<p class="small"><strong>Department:</strong> ' . esc_html( (string) $data['department'] ) . '</p>';
	}
	if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
		$html = '<p class="small muted">Contact your Provincial Mining Office for this service.</p>';
	}
	return $html;
}

/**
 * Compact service card: title and one-line preview, with details hidden until opened.
 *
 * @param WP_Post $post   Service.
 * @param string  $region Existing card markup, used to keep the icon.
 */
function ministry_front_service_card_html( $post, $region = '' ) {
	$top = '';
	if ( $region && preg_match( '#<div class="svc-top">.*?</div>#s', $region, $match ) ) {
		$top = $match[0];
	} else {
		$top = '<div class="svc-top"><span class="tag grey">Service</span></div>';
	}
	$data = get_post_meta( $post->ID, '_mom_service', true );
	$cat  = is_array( $data ) && ! empty( $data['category'] ) ? (string) $data['category'] : '';
	if ( $cat ) {
		$top = preg_replace( '#(<span class="tag[^"]*">)[^<]*#', '$1' . esc_html( $cat ), $top, 1 );
	}
	return '<article class="svc" tabindex="0">' . $top . '<h3>' . esc_html( $post->post_title ) . '</h3><p>' . esc_html( $post->post_excerpt ) . '</p><span class="svc-more">View details</span><div class="svc-detail" aria-hidden="true">' . ministry_front_service_detail_html( $post ) . '</div></article>';
}

/**
 * Organogram boxes from settings.
 */
function ministry_front_organogram_html() {
	$rows = isset( ministry_settings()['organogram'] ) && is_array( ministry_settings()['organogram'] ) ? ministry_settings()['organogram'] : array();
	$html = '<div class="org-chart">';
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) || empty( $row['title'] ) ) {
			continue;
		}
		$html .= '<div class="org-box"><strong>' . esc_html( (string) $row['title'] ) . '</strong><span>' . esc_html( isset( $row['holder'] ) ? (string) $row['holder'] : '' ) . '</span>';
		if ( ! empty( $row['parent'] ) ) {
			$html .= '<em>' . esc_html( sprintf( /* translators: reports-to title */ __( 'Reports to %s', 'ministry-content' ), (string) $row['parent'] ) ) . '</em>';
		}
		$html .= '</div>';
	}
	$html .= '</div>';
	return $html;
}

/**
 * Public sitemap of design routes plus legal pages.
 */
function ministry_front_sitemap_html() {
	$skip = array( 'style-guide' => true );
	$html = '<ul class="req-list">';
	foreach ( ministry_site_routes() as $path => $name ) {
		if ( isset( $skip[ $name ] ) ) {
			continue;
		}
		$label = ucwords( str_replace( '-', ' ', $name ) );
		$html .= '<li><a href="' . esc_url( ministry_site_url( $name ) ) . '">' . esc_html( $label ) . '</a></li>';
	}
	foreach ( array( 'privacy' => 'Privacy & data protection', 'accessibility' => 'Accessibility statement', 'information' => 'Access to information', 'terms' => 'Terms of use', 'verify' => 'How to verify official sites', 'about/structure' => 'Organisational structure' ) as $path => $label ) {
		$html .= '<li><a href="' . esc_url( home_url( '/' . $path . '/' ) ) . '">' . esc_html( $label ) . '</a></li>';
	}
	$html .= '</ul>';
	return $html;
}

/**
 * Small map pin used on injected cards.
 */
function ministry_front_pin_svg() {
	return ministry_front_icon( 'pin' );
}

/**
 * Inline icons matching the live design files.
 *
 * @param string $name Icon key.
 */
function ministry_front_icon( $name ) {
	$paths = array(
		'pin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
		'arrow'    => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
		'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
		'file'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>',
		'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
		'x'        => '<path d="M4 4l16 16"/><path d="M20 4 4 20"/>',
		'linkedin' => '<rect x="2" y="2" width="20" height="20" rx="2"/><path d="M7 10v7"/><path d="M7 7h.01"/><path d="M11 17v-7"/><path d="M11 13a3 3 0 0 1 6 0v4"/>',
		'whatsapp' => '<path d="M3 21l1.7-5A9 9 0 1 1 8 19.3z"/>',
		'print'    => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
	);
	$inner = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['arrow'];
	return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}
