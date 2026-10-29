<?php
/**
 * Copy the public design pages into the wp-admin records once.
 *
 * The pages stay visually the same. Later edits in wp-admin are what change them.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Baseline captured from the design pages.
 *
 * @return array<string, mixed>
 */
function ministry_design_baseline() {
	$base = get_option( 'mom_design_baseline', array() );
	return is_array( $base ) ? $base : array();
}

/**
 * Import the design pages when that has not been done yet.
 */
function ministry_design_sync() {
	if ( '1' === get_option( 'mom_design_sync' ) || ! function_exists( 'ministry_settings' ) ) {
		return;
	}
	$baseline = array(
		'pages' => array(),
		'keys'  => array(),
	);
	ministry_design_sync_settings( $baseline );
	ministry_design_sync_leaders( $baseline );
	ministry_design_sync_entities( $baseline );
	ministry_design_sync_departments( $baseline );
	ministry_design_sync_offices( $baseline );
	ministry_design_sync_news( $baseline );
	ministry_design_sync_documents( $baseline );
	ministry_design_sync_faqs( $baseline );
	ministry_design_sync_events( $baseline );
	ministry_design_sync_services( $baseline );
	ministry_design_sync_opportunities( $baseline );
	$banners = function_exists( 'ministry_banners' ) ? ministry_banners() : array();
	$shots   = array();
	foreach ( $banners as $banner ) {
		$shots[] = array(
			'id'    => $banner->ID,
			'thumb' => (int) get_post_thumbnail_id( $banner ),
		);
	}
	$baseline['banners'] = $shots;
	update_option( 'mom_design_baseline', $baseline, false );
	update_option( 'mom_design_logo_id', (int) ministry_settings()['logoId'], false );
	update_option( 'mom_design_sync', '1', false );
}

/**
 * @param string $name Design file name without .html.
 * @return DOMXPath|null
 */
function ministry_design_xpath( $name ) {
	$path = get_template_directory() . '/site/' . $name . '.html';
	if ( ! is_readable( $path ) ) {
		return null;
	}
	$doc  = new DOMDocument();
	$prev = libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="UTF-8">' . (string) file_get_contents( $path ) );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );
	return new DOMXPath( $doc );
}

/**
 * @param DOMXPath $xpath XPath.
 * @param string   $class Class token.
 * @param string   $tag   Element name.
 * @return DOMNodeList<DOMNode>|false
 */
function ministry_design_nodes( $xpath, $class, $tag = '*' ) {
	return $xpath->query( '//' . $tag . '[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]' );
}

/**
 * @param DOMNode|null $node Node.
 */
function ministry_design_text( $node ) {
	if ( ! $node ) {
		return '';
	}
	$text = preg_replace( '/\s+/u', ' ', $node->textContent );
	return trim( (string) $text );
}

/**
 * Direct text of a node, skipping child elements such as icons.
 *
 * @param DOMNode|null $node Node.
 */
function ministry_design_own_text( $node ) {
	if ( ! $node ) {
		return '';
	}
	$text = '';
	foreach ( $node->childNodes as $child ) {
		if ( XML_TEXT_NODE === $child->nodeType ) {
			$text .= $child->textContent;
		}
	}
	$text = preg_replace( '/\s+/u', ' ', $text );
	return trim( (string) $text );
}

/**
 * Section that contains a heading with this exact text.
 *
 * @param DOMXPath $xpath   XPath.
 * @param string   $heading Heading text.
 * @return DOMNode|null
 */
function ministry_design_section( $xpath, $heading ) {
	foreach ( $xpath->query( '//h1|//h2|//h3' ) as $node ) {
		if ( ministry_design_text( $node ) === $heading && $node instanceof DOMElement ) {
			$section = $node;
			while ( $section && 'section' !== $section->nodeName && 'body' !== $section->nodeName ) {
				$section = $section->parentNode;
			}
			return $section instanceof DOMElement ? $section : null;
		}
	}
	return null;
}

/**
 * @param string               $type   Post type.
 * @param string               $key    Stable design key.
 * @param array<string, mixed> $fields Post fields.
 * @param array<string, mixed> $meta   Meta values.
 */
function ministry_design_upsert( $type, $key, $fields, $meta = array() ) {
	$found = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_key'       => '_mom_design_key',
			'meta_value'     => $key,
		)
	);
	if ( ! $found ) {
		$found = get_posts(
			array(
				'post_type'      => $type,
				'name'           => $key,
				'post_status'    => 'any',
				'posts_per_page' => 1,
			)
		);
	}
	if ( ! $found && 'mom_department' === $type ) {
		$found = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => '_mom_design',
				'meta_value'     => $key,
			)
		);
	}
	$postarr = array_merge(
		array(
			'post_type'   => $type,
			'post_status' => 'publish',
			'post_name'   => $key,
		),
		$fields
	);
	if ( $found ) {
		$postarr['ID'] = $found[0]->ID;
		$id            = wp_update_post( $postarr, true );
	} else {
		$id = wp_insert_post( $postarr, true );
	}
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	update_post_meta( $id, '_mom_design_key', $key );
	foreach ( $meta as $meta_key => $value ) {
		update_post_meta( $id, $meta_key, $value );
	}
	return (int) $id;
}

/**
 * Draft published records that are not on the design pages.
 *
 * @param string   $type Post type.
 * @param string[] $keys Design keys that stay published.
 */
function ministry_design_retire( $type, $keys ) {
	$posts = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);
	foreach ( $posts as $post ) {
		$key = (string) get_post_meta( $post->ID, '_mom_design_key', true );
		if ( ! in_array( $key, $keys, true ) ) {
			wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_status' => 'draft',
				)
			);
		}
	}
}

/**
 * @param array<string, mixed> $baseline Baseline, passed by reference.
 */
function ministry_design_sync_settings( &$baseline ) {
	$home = ministry_design_xpath( 'index' );
	if ( ! $home ) {
		return;
	}
	$settings = ministry_settings();
	$brand    = ministry_design_nodes( $home, 'brand' );
	if ( $brand && $brand->item( 0 ) ) {
		$strong = ( new DOMXPath( $brand->item( 0 )->ownerDocument ) )->query( './/strong', $brand->item( 0 ) )->item( 0 );
		$em     = ( new DOMXPath( $brand->item( 0 )->ownerDocument ) )->query( './/em', $brand->item( 0 ) )->item( 0 );
		if ( $strong ) {
			$settings['ministryName'] = ministry_design_text( $strong );
		}
		if ( $em ) {
			$settings['tagline'] = trim( ministry_design_text( $em ), "\"“”" );
		}
	}
	$toll = ministry_design_nodes( $home, 'tollfree', 'a' );
	if ( $toll && $toll->item( 0 ) && preg_match( '/(\d{6,})/', ministry_design_text( $toll->item( 0 ) ), $number ) ) {
		$settings['tollFree'] = $number[1];
	}
	$lines = ministry_design_nodes( $home, 'contact-line', 'p' );
	if ( $lines ) {
		foreach ( $lines as $line ) {
			$text = ministry_design_text( $line );
			if ( str_contains( $text, 'ZIMRE' ) || str_contains( $text, 'Harare' ) && str_contains( $text, 'Centre' ) ) {
				$settings['headOfficeAddress'] = $text;
			}
			if ( preg_match( '/\+263[^·]*/', $text, $phone ) ) {
				$settings['headOfficePhone'] = trim( $phone[0] );
			}
			if ( preg_match_all( '/[\w.+-]+@mines\.gov\.zw/', $text, $mails ) ) {
				foreach ( $mails[0] as $mail ) {
					if ( str_starts_with( $mail, 'ask' ) ) {
						$settings['ministerEmail'] = $mail;
					} else {
						$settings['headOfficeEmail'] = $mail;
					}
				}
			}
		}
	}
	$hours_file = (string) file_get_contents( get_template_directory() . '/site/department-geological-survey.html' );
	if ( preg_match( '/Mon[^<]{5,80}/', $hours_file, $hours ) ) {
		$settings['officeHours'] = trim( html_entity_decode( $hours[0], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}
	$social = array();
	foreach ( ministry_design_nodes( $home, 'socials' ) as $wrap ) {
		foreach ( ( new DOMXPath( $wrap->ownerDocument ) )->query( './/a', $wrap ) as $link ) {
			if ( ! $link instanceof DOMElement ) {
				continue;
			}
			$social[] = array(
				'label' => $link->getAttribute( 'aria-label' ),
				'href'  => $link->getAttribute( 'href' ),
			);
		}
	}
	if ( $social ) {
		$settings['socialLinks'] = $social;
	}
	$hero = ministry_design_nodes( $home, 'hero2', 'section' );
	if ( $hero && $hero->item( 0 ) ) {
		$xpath = new DOMXPath( $hero->item( 0 )->ownerDocument );
		$copy  = $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " copy ")]', $hero->item( 0 ) )->item( 0 );
		$settings['homeHero'] = array(
			'eyebrow'        => ministry_design_text( $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " eyebrow ")]', $copy )->item( 0 ) ),
			'title'          => ministry_design_text( $xpath->query( './/h1', $copy )->item( 0 ) ),
			'text'           => ministry_design_text( $xpath->query( './/p[not(contains(@class, "eyebrow"))]', $copy )->item( 0 ) ),
			'primaryLabel'   => 'How to get a mining claim',
			'primaryHref'    => '/how-to-get-a-mining-claim',
			'secondaryLabel' => 'Online Mining Cadastre',
		);
		$settings['heroBadge'] = $settings['homeHero']['eyebrow'];
		$anchors = $xpath->query( './/a', $copy );
		if ( $anchors->item( 0 ) ) {
			$settings['homeHero']['primaryLabel'] = ministry_design_text( $anchors->item( 0 ) );
		}
		if ( $anchors->item( 1 ) ) {
			$settings['homeHero']['secondaryLabel'] = ministry_design_text( $anchors->item( 1 ) );
			$href                                   = $anchors->item( 1 )->getAttribute( 'href' );
			if ( $href ) {
				$settings['cadastrePortalUrl'] = $href;
			}
		}
	}
	$stats = array();
	foreach ( ministry_design_nodes( $home, 'stat' ) as $stat ) {
		$xpath    = new DOMXPath( $stat->ownerDocument );
		$stats[]  = array(
			'label' => ministry_design_text( $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " eyebrow ")]', $stat )->item( 0 ) ),
			'value' => ministry_design_text( $xpath->query( './/strong', $stat )->item( 0 ) ),
			'hint'  => ministry_design_text( $xpath->query( './/span', $stat )->item( 0 ) ),
		);
	}
	if ( $stats ) {
		$settings['homeStats'] = $stats;
	}
	$alert = ministry_design_nodes( $home, 'alert-bar' );
	if ( $alert && $alert->item( 0 ) ) {
		$baseline['alert'] = ministry_design_text( ( new DOMXPath( $alert->item( 0 )->ownerDocument ) )->query( './/p', $alert->item( 0 ) )->item( 0 ) );
	}
	$gateways = array();
	$icons    = array( 'map', 'document', 'chart', 'archive' );
	$index    = 0;
	foreach ( ministry_design_nodes( $home, 'gateway', 'a' ) as $gateway ) {
		$xpath      = new DOMXPath( $gateway->ownerDocument );
		$gateways[] = array(
			'title' => ministry_design_text( $xpath->query( './/h3', $gateway )->item( 0 ) ),
			'desc'  => ministry_design_text( $xpath->query( './/p[not(contains(@class, "eyebrow"))]', $gateway )->item( 0 ) ),
			'href'  => $gateway->getAttribute( 'href' ),
			'tag'   => ministry_design_text( $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " eyebrow ")]', $gateway )->item( 0 ) ),
			'icon'  => $icons[ $index % 4 ],
		);
		++$index;
	}
	if ( $gateways ) {
		$settings['gateways'] = $gateways;
		$heading              = ministry_design_section( $home, $gateways[0]['title'] );
		if ( $heading ) {
			$h2 = ( new DOMXPath( $heading->ownerDocument ) )->query( './/h2', $heading )->item( 0 );
			if ( $h2 ) {
				$settings['gatewaysHeading'] = ministry_design_text( $h2 );
			}
		}
	}
	$about_home = ministry_design_section( $home, 'A stable and sustainable mining industry by 2030.' );
	$vision     = 'A stable and sustainable mining industry by 2030.';
	$mission    = '';
	if ( $about_home ) {
		foreach ( ( new DOMXPath( $about_home->ownerDocument ) )->query( './/p', $about_home ) as $paragraph ) {
			$text = ministry_design_text( $paragraph );
			if ( str_starts_with( $text, 'To promote' ) ) {
				$mission = $text;
			}
		}
	}
	$pillars = array();
	foreach ( ministry_design_nodes( $home, 'perf' ) as $perf ) {
		foreach ( $perf->childNodes as $child ) {
			if ( $child instanceof DOMElement ) {
				$pillars[] = ministry_design_text( $child );
			}
		}
	}
	$settings['homePillars'] = array_values( array_filter( $pillars ) );
	$sections                = array(
		array( 'title' => 'Vision', 'desc' => $vision ),
		array( 'title' => 'Mission', 'desc' => $mission ),
	);
	$about = ministry_design_xpath( 'about' );
	if ( $about ) {
		foreach ( ministry_design_nodes( $about, 'value-card' ) as $card ) {
			$xpath      = new DOMXPath( $card->ownerDocument );
			$sections[] = array(
				'title' => ministry_design_text( $xpath->query( './/h3', $card )->item( 0 ) ),
				'desc'  => ministry_design_text( $xpath->query( './/p', $card )->item( 0 ) ),
			);
		}
		foreach ( ministry_design_nodes( $about, 'num-list' ) as $list ) {
			foreach ( ( new DOMXPath( $list->ownerDocument ) )->query( './/li', $list ) as $item ) {
				$sections[] = array(
					'title' => ministry_design_text( $item ),
					'desc'  => '',
				);
			}
		}
	}
	$settings['aboutSections'] = $sections;
	$pages                     = $settings['pages'];
	$map                       = array(
		'news'                     => 'news',
		'events'                   => 'events',
		'gallery'                  => 'media',
		'resources'                => 'resources',
		'about'                    => 'about',
		'the-ministry'             => 'ministry',
		'parastatals'              => 'entities',
		'departments'              => 'departments',
		'offices'                  => 'offices',
		'invest'                   => 'invest',
		'services'                 => 'services',
		'how-to-get-a-mining-claim'=> 'claim',
		'contact'                  => 'contact',
		'faq'                      => 'faq',
	);
	foreach ( $map as $file => $key ) {
		$xpath = ministry_design_xpath( $file );
		if ( ! $xpath ) {
			continue;
		}
		$hero = ministry_design_nodes( $xpath, 'page-hero', 'section' );
		if ( ! $hero || ! $hero->item( 0 ) ) {
			continue;
		}
		$doc                  = new DOMXPath( $hero->item( 0 )->ownerDocument );
		$pages[ $key ]        = array(
			'eyebrow' => ministry_design_text( $doc->query( './/*[contains(concat(" ", normalize-space(@class), " "), " eyebrow ")]', $hero->item( 0 ) )->item( 0 ) ),
			'title'   => ministry_design_text( $doc->query( './/h1', $hero->item( 0 ) )->item( 0 ) ),
			'intro'   => ministry_design_text( $doc->query( './/*[contains(concat(" ", normalize-space(@class), " "), " lead ")]', $hero->item( 0 ) )->item( 0 ) ),
			'notice'  => isset( $pages[ $key ]['notice'] ) ? (string) $pages[ $key ]['notice'] : '',
		);
	}
	$settings['pages'] = $pages;
	$invest            = ministry_design_xpath( 'invest' );
	if ( $invest ) {
		$why = ministry_design_section( $invest, 'Why invest in Zimbabwe mining' );
		if ( $why ) {
			$highlights = array();
			foreach ( ( new DOMXPath( $why->ownerDocument ) )->query( './/article', $why ) as $card ) {
				$doc          = new DOMXPath( $card->ownerDocument );
				$highlights[] = array(
					'title' => ministry_design_text( $doc->query( './/h3', $card )->item( 0 ) ),
					'desc'  => ministry_design_text( $doc->query( './/p', $card )->item( 0 ) ),
				);
			}
			if ( $highlights ) {
				$settings['investHighlights'] = $highlights;
			}
		}
		$steps_section = ministry_design_section( $invest, 'How to invest: licensing steps' );
		if ( $steps_section ) {
			$steps = array();
			foreach ( ( new DOMXPath( $steps_section->ownerDocument ) )->query( './/*[contains(concat(" ", normalize-space(@class), " "), " step ")]', $steps_section ) as $step ) {
				$doc     = new DOMXPath( $step->ownerDocument );
				$steps[] = array(
					'title' => ministry_design_text( $doc->query( './/h3', $step )->item( 0 ) ),
					'desc'  => ministry_design_text( $doc->query( './/p', $step )->item( 0 ) ),
				);
			}
			if ( $steps ) {
				$settings['licensingSteps'] = $steps;
			}
		}
	}
	$patch_keys = array( 'ministryName', 'tagline', 'tollFree', 'headOfficePhone', 'headOfficeEmail', 'ministerEmail', 'headOfficeAddress', 'officeHours', 'cadastrePortalUrl', 'socialLinks', 'homeHero', 'heroBadge', 'homeStats', 'gateways', 'gatewaysHeading', 'homePillars', 'aboutSections', 'pages', 'investHighlights', 'licensingSteps' );
	$patch      = array();
	foreach ( $patch_keys as $key ) {
		if ( array_key_exists( $key, $settings ) ) {
			$patch[ $key ] = $settings[ $key ];
		}
	}
	ministry_desk_patch_settings( $patch );
	$baseline['settings'] = $patch;
	$baseline['pages']    = $pages;
	$baseline['homeHero'] = $settings['homeHero'];
}

/**
 * @param array<string, mixed> $baseline Baseline.
 */
function ministry_design_sync_leaders( &$baseline ) {
	$xpath = ministry_design_xpath( 'the-ministry' );
	if ( ! $xpath ) {
		return;
	}
	$leaders = array();
	foreach ( ministry_design_nodes( $xpath, 'leader-card', 'article' ) as $card ) {
		$doc        = new DOMXPath( $card->ownerDocument );
		$paragraphs = array();
		foreach ( $doc->query( './div/p[not(contains(@class, "eyebrow")) and not(contains(@class, "small"))]', $card ) as $paragraph ) {
			$paragraphs[] = ministry_design_text( $paragraph );
		}
		$facts = array();
		foreach ( $doc->query( './/dl[contains(@class, "facts")]/div', $card ) as $fact ) {
			$facts[] = ministry_design_text( $fact );
		}
		$leaders[] = array(
			'name'     => ministry_design_text( $doc->query( './/h2', $card )->item( 0 ) ),
			'title'    => ministry_design_text( $doc->query( './/*[contains(concat(" ", normalize-space(@class), " "), " eyebrow ")]', $card )->item( 0 ) ),
			'bio'      => isset( $paragraphs[0] ) ? $paragraphs[0] : '',
			'fullBio'  => implode( "\n\n", $paragraphs ),
			'facts'    => $facts,
			'imageUrl' => '',
			'home'     => '1',
			'status'   => 'current',
		);
	}
	if ( $leaders ) {
		ministry_desk_patch_settings( array( 'leaders' => $leaders ) );
		$baseline['leaders'] = $leaders;
	}
}

/**
 * @param array<string, mixed> $baseline Baseline.
 */
function ministry_design_sync_entities( &$baseline ) {
	$xpath = ministry_design_xpath( 'parastatals' );
	if ( ! $xpath ) {
		return;
	}
	$entities = array();
	$order    = 1;
	foreach ( ministry_design_nodes( $xpath, 'agency-card', 'article' ) as $card ) {
		$doc  = new DOMXPath( $card->ownerDocument );
		$name = ministry_design_text( $doc->query( './/h3', $card )->item( 0 ) );
		$acro = '';
		if ( preg_match( '/\(([A-Z]{2,8})\)\s*$/', $name, $match ) ) {
			$acro = $match[1];
			$name = trim( substr( $name, 0, -strlen( $match[0] ) ) );
		}
		$mandate    = ministry_design_text( $doc->query( './/p', $card )->item( 0 ) );
		$mandate    = preg_replace( '/^Mandate:\s*/', '', $mandate );
		$entities[] = array(
			'name'    => $name,
			'acronym' => $acro,
			'desc'    => $mandate,
			'href'    => '',
			'type'    => ministry_design_text( $doc->query( './/*[contains(concat(" ", normalize-space(@class), " "), " eyebrow ")]', $card )->item( 0 ) ),
			'show'    => '1',
			'order'   => (string) $order,
		);
		++$order;
	}
	$board = $xpath->query( '//*[@id="mab"]' )->item( 0 );
	if ( $board ) {
		$doc        = new DOMXPath( $board->ownerDocument );
		$entities[] = array(
			'name'    => 'Mining Affairs Board',
			'acronym' => 'MAB',
			'desc'    => ministry_design_text( $doc->query( './/p', $board )->item( 0 ) ),
			'href'    => '',
			'type'    => 'Statutory body',
			'show'    => '1',
			'order'   => (string) $order,
		);
	}
	if ( $entities ) {
		ministry_desk_patch_settings( array( 'entities' => $entities ) );
		$baseline['entities'] = $entities;
	}
}

/**
 * @param array<string, mixed> $baseline Baseline.
 */
function ministry_design_sync_departments( &$baseline ) {
	$cards = array();
	$list  = ministry_design_xpath( 'departments' );
	if ( $list ) {
		foreach ( ministry_design_nodes( $list, 'dept-card', 'article' ) as $card ) {
			$doc  = new DOMXPath( $card->ownerDocument );
			$link = $doc->query( './/a[contains(@href, "department-")]', $card )->item( 0 );
			if ( ! $link instanceof DOMElement ) {
				continue;
			}
			$slug           = preg_replace( '/^department-|\.html$/', '', $link->getAttribute( 'href' ) );
			$cards[ $slug ] = array(
				'group'      => $card->getAttribute( 'data-cat' ),
				'short'      => ministry_design_text( $doc->query( './/*[contains(@class, "abbr")]', $card )->item( 0 ) ),
				'summary'    => preg_replace( '/^Mandate:\s*/', '', ministry_design_text( $doc->query( './/*[contains(@class, "mandate")]', $card )->item( 0 ) ) ),
				'head'       => ministry_design_text( $doc->query( './/*[contains(@class, "who")]//strong', $card )->item( 0 ) ),
				'list_title' => ministry_design_text( $doc->query( './/h3', $card )->item( 0 ) ),
			);
		}
	}
	$keys   = array();
	$stored = array();
	foreach ( glob( get_template_directory() . '/site/department-*.html' ) as $file ) {
		$slug  = substr( basename( $file, '.html' ), strlen( 'department-' ) );
		$xpath = ministry_design_xpath( 'department-' . $slug );
		if ( ! $xpath ) {
			continue;
		}
		$functions = array();
		foreach ( ministry_design_nodes( $xpath, 'check-list' ) as $ul ) {
			foreach ( ( new DOMXPath( $ul->ownerDocument ) )->query( './/li/span', $ul ) as $span ) {
				$functions[] = ministry_design_text( $span );
			}
			break;
		}
		$standards = array();
		foreach ( ministry_design_nodes( $xpath, 'std-table' ) as $table ) {
			foreach ( ( new DOMXPath( $table->ownerDocument ) )->query( './/tr', $table ) as $row ) {
				$cells = ( new DOMXPath( $row->ownerDocument ) )->query( './/td', $row );
				if ( $cells->item( 0 ) && $cells->item( 1 ) ) {
					$standards[] = array(
						'label' => ministry_design_text( $cells->item( 0 ) ),
						'time'  => ministry_design_text( $cells->item( 1 ) ),
					);
				}
			}
		}
		$head = ministry_design_nodes( $xpath, 'hod-card' );
		$doc  = $head && $head->item( 0 ) ? new DOMXPath( $head->item( 0 )->ownerDocument ) : $xpath;
		$card = isset( $cards[ $slug ] ) ? $cards[ $slug ] : array();
		$group_map = array(
			'technical'  => '3',
			'investment' => '2',
			'corporate'  => '1',
			'provincial' => '4',
		);
		$group = isset( $card['group'], $group_map[ $card['group'] ] ) ? $group_map[ $card['group'] ] : '3';
		$row   = array(
			'title'      => ministry_design_text( $xpath->query( '//h1' )->item( 0 ) ),
			'mandate'    => ministry_design_text( $xpath->query( '//*[contains(@class, "mandate-box")]/p[not(contains(@class, "eyebrow"))]' )->item( 0 ) ),
			'summary'    => isset( $card['summary'] ) ? $card['summary'] : '',
			'functions'  => $functions,
			'standards'  => $standards,
			'head'       => $head && $head->item( 0 ) ? ministry_design_text( $doc->query( './/h3', $head->item( 0 ) )->item( 0 ) ) : '',
			'head_title' => $head && $head->item( 0 ) ? ministry_design_text( $doc->query( './/p', $head->item( 0 ) )->item( 0 ) ) : '',
			'short'      => isset( $card['short'] ) ? $card['short'] : '',
			'list_title' => isset( $card['list_title'] ) ? $card['list_title'] : '',
			'group'      => $group,
		);
		if ( '' === $row['head'] && ! empty( $card['head'] ) ) {
			$row['head'] = $card['head'];
		}
		$contact = ministry_design_nodes( $xpath, 'contact-navy' );
		if ( $contact && $contact->item( 0 ) ) {
			$spans = ( new DOMXPath( $contact->item( 0 )->ownerDocument ) )->query( './/li', $contact->item( 0 ) );
			$bits  = array();
			foreach ( $spans as $item ) {
				$bits[] = ministry_design_text( $item );
			}
			$row['location'] = isset( $bits[0] ) ? $bits[0] : '';
			$row['phone']    = isset( $bits[1] ) ? $bits[1] : '';
			$row['email']    = isset( $bits[2] ) ? $bits[2] : '';
			$row['hours']    = isset( $bits[3] ) ? $bits[3] : '';
		}
		$dept_id = ministry_design_upsert(
			'mom_department',
			$slug,
			array( 'post_title' => $row['title'] ),
			array(
				'_mom_design'     => $slug,
				'_mom_mandate'    => $row['mandate'],
				'_mom_summary'    => $row['summary'],
				'_mom_functions'  => $row['functions'],
				'_mom_standards'  => $row['standards'],
				'_mom_contact_name' => $row['head'],
				'_mom_head_title' => $row['head_title'],
				'_mom_short'      => $row['short'],
				'_mom_group'      => $row['group'],
				'_mom_location'   => isset( $row['location'] ) ? $row['location'] : '',
				'_mom_phone'      => isset( $row['phone'] ) ? $row['phone'] : '',
				'_mom_email'      => isset( $row['email'] ) ? $row['email'] : '',
				'_mom_provincial' => '0',
			)
		);
		$row['thumb']    = (int) get_post_thumbnail_id( $dept_id );
		$keys[]          = $slug;
		$stored[ $slug ] = $row;
	}
	$baseline['departments'] = $stored;
	$baseline['keys']['mom_department'] = $keys;
}

/**
 * @param array<string, mixed> $baseline Baseline.
 */
function ministry_design_sync_offices( &$baseline ) {
	$xpath = ministry_design_xpath( 'offices' );
	if ( ! $xpath ) {
		return;
	}
	$keys   = isset( $baseline['keys']['mom_department'] ) ? $baseline['keys']['mom_department'] : array();
	$stored = array();
	foreach ( ministry_design_nodes( $xpath, 'ho-card', 'article' ) as $card ) {
		$doc   = new DOMXPath( $card->ownerDocument );
		$title = ministry_design_text( $doc->query( './/h3', $card )->item( 0 ) );
		$key   = 'centre-' . sanitize_title( $title );
		$rows  = array();
		foreach ( $doc->query( './/*[contains(@class, "row")]', $card ) as $row ) {
			$rows[] = ministry_design_text( $row );
		}
		$record = array(
			'title'   => $title,
			'summary' => ministry_design_text( $doc->query( './/p', $card )->item( 0 ) ),
			'address' => isset( $rows[0] ) ? $rows[0] : '',
			'phone'   => isset( $rows[1] ) ? $rows[1] : '',
			'email'   => isset( $rows[2] ) ? $rows[2] : '',
		);
		ministry_design_upsert(
			'mom_department',
			$key,
			array( 'post_title' => $title ),
			array(
				'_mom_centre'     => '1',
				'_mom_provincial' => '0',
				'_mom_summary'    => $record['summary'],
				'_mom_address'    => $record['address'],
				'_mom_location'   => $record['address'],
				'_mom_phone'      => $record['phone'],
				'_mom_email'      => $record['email'],
			)
		);
		$keys[]         = $key;
		$stored[ $key ] = $record;
	}
	foreach ( ministry_design_nodes( $xpath, 'prov-card', 'article' ) as $card ) {
		if ( ! $card instanceof DOMElement ) {
			continue;
		}
		$doc   = new DOMXPath( $card->ownerDocument );
		$title = ministry_design_text( $doc->query( './/h3', $card )->item( 0 ) );
		$town  = ministry_design_text( $doc->query( './/*[contains(@class, "small")]', $card )->item( 0 ) );
		$key   = 'province-' . $card->getAttribute( 'id' );
		$rows  = array();
		foreach ( $doc->query( './/*[contains(@class, "row")]//span', $card ) as $span ) {
			$rows[] = ministry_design_text( $span );
		}
		$director = ministry_design_text( $doc->query( './/*[contains(@class, "pmd")]/span', $card )->item( 0 ) );
		$record   = array(
			'title'    => $title,
			'town'     => $town,
			'director' => $director,
			'address'  => isset( $rows[0] ) ? $rows[0] : '',
			'phone'    => isset( $rows[1] ) ? $rows[1] : '',
			'email'    => isset( $rows[2] ) ? $rows[2] : '',
		);
		ministry_design_upsert(
			'mom_department',
			$key,
			array( 'post_title' => $title ),
			array(
				'_mom_provincial'    => '1',
				'_mom_province_name' => $title,
				'_mom_town'          => $town,
				'_mom_contact_name'  => $director,
				'_mom_address'       => $record['address'],
				'_mom_location'      => $town,
				'_mom_phone'         => $record['phone'],
				'_mom_email'         => $record['email'],
			)
		);
		$keys[]         = $key;
		$stored[ $key ] = $record;
	}
	$baseline['offices']                = $stored;
	$baseline['keys']['mom_department'] = $keys;
	ministry_design_retire( 'mom_department', $keys );
}

/**
 * @param array<string, mixed> $baseline Baseline.
 */
function ministry_design_sync_news( &$baseline ) {
	$xpath = ministry_design_xpath( 'news' );
	if ( ! $xpath ) {
		return;
	}
	$keys   = array();
	$stored = array();
	foreach ( ministry_design_nodes( $xpath, 'featured', 'article' ) as $card ) {
		ministry_design_sync_news_card( $card, true, $keys, $stored, $baseline );
	}
	foreach ( ministry_design_nodes( $xpath, 'release', 'article' ) as $card ) {
		ministry_design_sync_news_card( $card, false, $keys, $stored, $baseline );
	}
	$baseline['news']            = $stored;
	$baseline['keys']['mom_news'] = $keys;
	ministry_design_retire( 'mom_news', $keys );
}

/**
 * @param DOMNode              $card     Article node.
 * @param bool                 $featured Featured story.
 * @param string[]             $keys     Keys, by reference.
 * @param array<string, mixed> $stored   Stored rows, by reference.
 * @param array<string, mixed> $baseline Baseline.
 */
function ministry_design_sync_news_card( $card, $featured, &$keys, &$stored, $baseline ) {
	$doc  = new DOMXPath( $card->ownerDocument );
	$link = $doc->query( './/a[contains(@href, "news-")]', $card )->item( 0 );
	$href = $link instanceof DOMElement ? $link->getAttribute( 'href' ) : '';
	$key  = $href ? preg_replace( '/^news-|\.html$/', '', $href ) : sanitize_title( ministry_design_text( $doc->query( './/h2|.//h3', $card )->item( 0 ) ) );
	$title = ministry_design_text( $doc->query( './/h2|.//h3', $card )->item( 0 ) );
	$excerpt = '';
	foreach ( $doc->query( './/p', $card ) as $paragraph ) {
		if ( $paragraph instanceof DOMElement && ! str_contains( ' ' . $paragraph->getAttribute( 'class' ) . ' ', ' meta ' ) ) {
			$excerpt = ministry_design_text( $paragraph );
			break;
		}
	}
	$detail  = $href ? ministry_design_xpath( 'news-' . $key ) : null;
	$content = $excerpt;
	if ( $detail ) {
		$stand = ministry_design_nodes( $detail, 'standfirst' );
		if ( $stand && $stand->item( 0 ) ) {
			$content = ministry_design_text( $stand->item( 0 ) );
		}
		$detail_title = ministry_design_text( $detail->query( '//h1' )->item( 0 ) );
		if ( $detail_title ) {
			$title = $detail_title;
		}
	}
	$kind = $card instanceof DOMElement && str_contains( $card->getAttribute( 'data-cat' ), 'notice' ) ? 'notice' : 'press';
	$meta = array(
		'_mom_kind'    => $kind,
		'_mom_surface' => $featured ? 'featured' : 'list',
	);
	if ( 'notice' === $kind && preg_match( '/(\d+)\s*(?:of|\/)\s*(\d{4})/', ministry_design_text( $doc->query( './/*[contains(@class, "tag")]', $card )->item( 0 ) ), $number ) ) {
		$meta['_mom_number'] = $number[1] . '/' . $number[2];
	}
	if ( $featured && ! empty( $baseline['alert'] ) ) {
		$meta['_mom_banner']      = '1';
		$meta['_mom_banner_text'] = $baseline['alert'];
	}
	ministry_design_upsert(
		'mom_news',
		$key,
		array(
			'post_title'   => $title,
			'post_excerpt' => $excerpt,
			'post_content' => $content,
		),
		$meta
	);
	$keys[]        = $key;
	$stored[ $key ] = array(
		'title'   => $title,
		'excerpt' => $excerpt,
		'content' => $content,
		'alert'   => $featured && ! empty( $baseline['alert'] ) ? $baseline['alert'] : '',
	);
}

/**
 * @param array<string, mixed> $baseline Baseline.
 */
function ministry_design_sync_documents( &$baseline ) {
	$xpath = ministry_design_xpath( 'resources' );
	if ( ! $xpath ) {
		return;
	}
	$keys  = array();
	$rows  = array();
	$order = 0;
	foreach ( $xpath->query( '//table//tr[td]' ) as $row ) {
		$doc   = new DOMXPath( $row->ownerDocument );
		$title = ministry_design_text( $doc->query( './/*[contains(@class, "doc-title")]', $row )->item( 0 ) );
		if ( '' === $title ) {
			continue;
		}
		$link = $doc->query( './/a[contains(@href, ".pdf")]', $row )->item( 0 );
		$file = $link instanceof DOMElement ? basename( $link->getAttribute( 'href' ) ) : '';
		$cat  = $row instanceof DOMElement ? $row->getAttribute( 'data-cat' ) : '';
		$map  = array(
			'Mines Act'         => 'legislation',
			'Policy documents'  => 'policies',
			'Regulations'       => 'guidelines',
			'Public notices'    => 'publications',
			'Staff forms'       => 'forms',
		);
		$slug = isset( $map[ $cat ] ) ? $map[ $cat ] : 'guidelines';
		$blurb = ministry_design_text( $doc->query( './/*[contains(@class, "muted")]', $row )->item( 0 ) );
		$version = ministry_design_text( $doc->query( './/td[contains(@class, "small")]', $row )->item( 0 ) );
		$key = sanitize_title( $title );
		ministry_design_upsert(
			'mom_document',
			$key,
			array(
				'post_title'   => $title,
				'post_excerpt' => $version,
				'menu_order'   => $order,
			),
			array(
				'_mom_category' => $slug,
				'_mom_file_url' => $file ? 'documents/' . $file : '',
				'_mom_blurb'    => $blurb,
				'_mom_staff'    => '0',
			)
		);
		$keys[]       = $key;
		$rows[ $key ] = array(
			'title'    => $title,
			'category' => $cat,
			'blurb'    => $blurb,
			'version'  => $version,
			'file'     => $file,
		);
		++$order;
	}
	foreach ( ministry_design_nodes( $xpath, 'staff-forms' ) as $wrap ) {
		foreach ( ( new DOMXPath( $wrap->ownerDocument ) )->query( './/a', $wrap ) as $link ) {
			if ( ! $link instanceof DOMElement ) {
				continue;
			}
			$title = ministry_design_text( ( new DOMXPath( $link->ownerDocument ) )->query( './/strong', $link )->item( 0 ) );
			$file  = basename( $link->getAttribute( 'href' ) );
			$key   = sanitize_title( $title );
			ministry_design_upsert(
				'mom_document',
				$key,
				array(
					'post_title' => $title,
					'menu_order' => $order,
				),
				array(
					'_mom_category' => 'forms',
					'_mom_file_url' => 'documents/' . $file,
					'_mom_staff'    => '1',
				)
			);
			$keys[]       = $key;
			$rows[ $key ] = array(
				'title'    => $title,
				'category' => 'Staff forms',
				'file'     => $file,
				'blurb'    => '',
				'version'  => '',
			);
			++$order;
		}
	}
	$baseline['documents']           = $rows;
	$baseline['keys']['mom_document'] = $keys;
	ministry_design_retire( 'mom_document', $keys );
}

/**
 * @param array<string, mixed> $baseline Baseline.
 */
function ministry_design_sync_faqs( &$baseline ) {
	$xpath = ministry_design_xpath( 'faq' );
	if ( ! $xpath ) {
		return;
	}
	$keys  = array();
	$rows  = array();
	$order = 0;
	foreach ( ministry_design_nodes( $xpath, 'faq', 'details' ) as $item ) {
		$doc      = new DOMXPath( $item->ownerDocument );
		$question = ministry_design_own_text( $doc->query( './/summary', $item )->item( 0 ) );
		$answer   = ministry_design_text( $doc->query( './/*[contains(@class, "ans")]/p[1]', $item )->item( 0 ) );
		$key      = sanitize_title( $question );
		ministry_design_upsert(
			'mom_faq',
			$key,
			array(
				'post_title'   => $question,
				'post_content' => $answer,
				'menu_order'   => $order,
			)
		);
		$keys[]      = $key;
		$rows[ $key ] = array(
			'question' => $question,
			'answer'   => $answer,
		);
		++$order;
	}
	$baseline['faqs']            = $rows;
	$baseline['keys']['mom_faq'] = $keys;
	ministry_design_retire( 'mom_faq', $keys );
}

/**
 * @param array<string, mixed> $baseline Baseline.
 */
function ministry_design_sync_events( &$baseline ) {
	$xpath = ministry_design_xpath( 'events' );
	if ( ! $xpath ) {
		return;
	}
	$keys = array();
	$rows = array();
	foreach ( ministry_design_nodes( $xpath, 'event-card', 'article' ) as $card ) {
		$doc      = new DOMXPath( $card->ownerDocument );
		$title    = ministry_design_text( $doc->query( './/h3', $card )->item( 0 ) );
		$category = ministry_design_text( $doc->query( './/*[contains(concat(" ", normalize-space(@class), " "), " eyebrow ")]', $card )->item( 0 ) );
		$excerpt  = ministry_design_text( $doc->query( './/p[contains(@class, "muted")]', $card )->item( 0 ) );
		$meta     = array();
		foreach ( $doc->query( './/*[contains(@class, "meta-row")]/span', $card ) as $span ) {
			$meta[] = ministry_design_text( $span );
		}
		$when = isset( $meta[0] ) ? $meta[0] : '';
		$stamp = $when ? strtotime( $when ) : false;
		$key   = sanitize_title( $title );
		ministry_design_upsert(
			'mom_event',
			$key,
			array(
				'post_title'   => $title,
				'post_excerpt' => $excerpt,
			),
			array(
				'_mom_category'  => $category,
				'_mom_location'  => isset( $meta[2] ) ? $meta[2] : '',
				'_mom_starts_at' => $stamp ? gmdate( 'Y-m-d H:i:s', $stamp ) : '',
			)
		);
		$keys[]       = $key;
		$rows[ $key ] = array(
			'title'    => $title,
			'excerpt'  => $excerpt,
			'category' => $category,
			'when'     => $when,
			'where'    => isset( $meta[2] ) ? $meta[2] : '',
		);
	}
	$baseline['events']            = $rows;
	$baseline['keys']['mom_event'] = $keys;
	ministry_design_retire( 'mom_event', $keys );
}

/**
 * @param array<string, mixed> $baseline Baseline.
 */
function ministry_design_sync_services( &$baseline ) {
	$xpath = ministry_design_xpath( 'services' );
	if ( ! $xpath ) {
		return;
	}
	$keys  = array();
	$rows  = array();
	$order = 0;
	foreach ( ministry_design_nodes( $xpath, 'svc', 'article' ) as $card ) {
		$doc      = new DOMXPath( $card->ownerDocument );
		$title    = ministry_design_text( $doc->query( './/h3', $card )->item( 0 ) );
		$excerpt  = ministry_design_text( $doc->query( './/p', $card )->item( 0 ) );
		$category = ministry_design_text( $doc->query( './/*[contains(@class, "tag")]', $card )->item( 0 ) );
		$key      = sanitize_title( $title );
		ministry_design_upsert(
			'mom_service',
			$key,
			array(
				'post_title'   => $title,
				'post_excerpt' => $excerpt,
				'menu_order'   => $order,
			),
			array(
				'_mom_service' => array(
					'category' => $category,
					'number'   => '',
					'unit'     => '',
				),
			)
		);
		$keys[]       = $key;
		$rows[ $key ] = array(
			'title'    => $title,
			'excerpt'  => $excerpt,
			'category' => $category,
		);
		++$order;
	}
	$standards = array();
	foreach ( ministry_design_nodes( $xpath, 'svc-std' ) as $row ) {
		$doc         = new DOMXPath( $row->ownerDocument );
		$standards[] = array(
			'label' => ministry_design_text( $doc->query( './/span', $row )->item( 0 ) ),
			'time'  => ministry_design_text( $doc->query( './/strong', $row )->item( 0 ) ),
		);
	}
	$baseline['services']            = $rows;
	$baseline['serviceStandards']    = $standards;
	$baseline['keys']['mom_service'] = $keys;
	ministry_design_retire( 'mom_service', $keys );
}

/**
 * @param array<string, mixed> $baseline Baseline.
 */
function ministry_design_sync_opportunities( &$baseline ) {
	$keys = array();
	$rows = array();
	$home = ministry_design_xpath( 'index' );
	if ( $home ) {
		$section = ministry_design_section( $home, 'Investment opportunities' );
		if ( $section ) {
			foreach ( ( new DOMXPath( $section->ownerDocument ) )->query( './/a[contains(@class, "img-card")]', $section ) as $card ) {
				$doc      = new DOMXPath( $card->ownerDocument );
				$title    = ministry_design_text( $doc->query( './/h3', $card )->item( 0 ) );
				$mineral  = ministry_design_text( $doc->query( './/*[contains(@class, "tag")]', $card )->item( 0 ) );
				$region   = ministry_design_text( $doc->query( './/*[contains(@class, "meta")]', $card )->item( 0 ) );
				$key      = 'home-' . sanitize_title( $title );
				ministry_design_upsert(
					'mom_opportunity',
					$key,
					array( 'post_title' => $title ),
					array(
						'_mom_mineral' => $mineral,
						'_mom_region'  => $region,
						'_mom_surface' => 'home',
					)
				);
				$keys[]       = $key;
				$rows[ $key ] = array(
					'title'   => $title,
					'mineral' => $mineral,
					'region'  => $region,
					'surface' => 'home',
				);
			}
		}
	}
	$invest = ministry_design_xpath( 'invest' );
	if ( $invest ) {
		$section = ministry_design_section( $invest, 'Priority investment areas' );
		if ( $section ) {
			foreach ( ( new DOMXPath( $section->ownerDocument ) )->query( './/article', $section ) as $card ) {
				$doc      = new DOMXPath( $card->ownerDocument );
				$title    = ministry_design_text( $doc->query( './/h3', $card )->item( 0 ) );
				$mineral  = ministry_design_text( $doc->query( './/*[contains(@class, "tag")]', $card )->item( 0 ) );
				$excerpt  = ministry_design_text( $doc->query( './/p[contains(@class, "muted")]', $card )->item( 0 ) );
				$region   = ministry_design_text( $doc->query( './/*[contains(@class, "meta")]', $card )->item( 0 ) );
				$key      = 'invest-' . sanitize_title( $title );
				ministry_design_upsert(
					'mom_opportunity',
					$key,
					array(
						'post_title'   => $title,
						'post_excerpt' => $excerpt,
					),
					array(
						'_mom_mineral' => $mineral,
						'_mom_region'  => $region,
						'_mom_surface' => 'invest',
					)
				);
				$keys[]       = $key;
				$rows[ $key ] = array(
					'title'   => $title,
					'excerpt' => $excerpt,
					'mineral' => $mineral,
					'region'  => $region,
					'surface' => 'invest',
				);
			}
		}
	}
	$baseline['opportunities']            = $rows;
	$baseline['keys']['mom_opportunity'] = $keys;
	ministry_design_retire( 'mom_opportunity', $keys );
}
