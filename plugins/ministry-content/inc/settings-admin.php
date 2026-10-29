<?php
/**
 * Ministry Settings screen.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'Ministry Settings',
			'Ministry Settings',
			'manage_options',
			'ministry-settings',
			'ministry_render_settings_page',
			'dashicons-admin-generic',
			58
		);
	}
);

/**
 * Tabs and the setting keys each one edits.
 *
 * @return array<string, array{label: string, keys: string[]}>
 */
function ministry_settings_tabs() {
	return array(
		'brand'   => array(
			'label' => 'Branding & contact',
			'keys'  => array( 'ministryName', 'shortName', 'countryLine', 'tagline', 'logoId', 'headOfficeName', 'headOfficeAddress', 'headOfficeEmail', 'headOfficePhone', 'tollFree', 'officeHours', 'whatsappNumber', 'cadastrePortalUrl', 'footerNote', 'footerPortalTitle', 'footerPortalText', 'downloadsEnabled' ),
		),
		'home'    => array(
			'label' => 'Homepage',
			'keys'  => array( 'heroBadge', 'homeHero', 'heroPrimaryCta', 'heroCtaLabel', 'heroRotateSeconds', 'homeStats', 'homePillars', 'homeNewsEyebrow', 'homeNewsHeading', 'homeEventsEyebrow', 'homeEventsHeading', 'homeOpportunitiesEyebrow', 'homeOpportunitiesHeading', 'gatewaysHeading', 'gateways', 'homeTasks' ),
		),
		'pages'   => array(
			'label' => 'Page intros',
			'keys'  => array( 'pages' ),
		),
		'about'   => array(
			'label' => 'About & leaders',
			'keys'  => array( 'aboutSections', 'leaders', 'entities', 'mabMembers' ),
		),
		'org'     => array(
			'label' => 'Organogram',
			'keys'  => array( 'organogram' ),
		),
		'invest'  => array(
			'label' => 'Invest & services',
			'keys'  => array( 'investHighlightsHeading', 'investHighlights', 'licensingHeading', 'licensingSteps', 'investPortfolioEyebrow', 'investPortfolioHeading', 'investCta', 'feesHeading', 'feesReference', 'fees', 'serviceCards', 'claimNotice', 'claimSteps', 'claimChecklist', 'serviceStandards' ),
		),
		'footer'  => array(
			'label' => 'Footer & menu links',
			'keys'  => array( 'headerQuickLinks', 'footerColumns', 'navItems', 'legalLinks', 'verifyHref', 'socialLinks' ),
		),
		'assistant' => array(
			'label' => 'Ask Assistant',
			'keys'  => array( 'assistantEnabled', 'assistantScriptUrl', 'assistantTenant' ),
		),
	);
}

/**
 * Human labels for setting keys.
 *
 * @param string $key Setting key.
 */
function ministry_field_label( $key ) {
	$labels = array(
		'ministryName' => 'Ministry name',
		'shortName' => 'Short name',
		'countryLine' => 'Country line',
		'tagline' => 'Motto',
		'logoId' => 'Coat of arms',
		'headOfficeName' => 'Head office name',
		'headOfficeAddress' => 'Address',
		'headOfficeEmail' => 'Email',
		'headOfficePhone' => 'Phone',
		'tollFree' => 'Toll-free',
		'officeHours' => 'Office hours',
		'cadastrePortalUrl' => 'Cadastre portal URL',
		'footerNote' => 'Footer note',
		'footerPortalTitle' => 'Footer portal title',
		'footerPortalText' => 'Footer portal text',
		'heroBadge' => 'Hero badge',
		'homeHero' => 'Homepage hero',
		'heroPrimaryCta' => 'Hero button',
		'heroCtaLabel' => 'Slide button label',
		'heroRotateSeconds' => 'Slide seconds',
		'homeStats' => 'Key figures',
		'homePillars' => 'Performance lines',
		'homeNewsEyebrow' => 'News eyebrow',
		'homeNewsHeading' => 'News heading',
		'homeEventsEyebrow' => 'Events eyebrow',
		'homeEventsHeading' => 'Events heading',
		'homeOpportunitiesEyebrow' => 'Opportunities eyebrow',
		'homeOpportunitiesHeading' => 'Opportunities heading',
		'gatewaysHeading' => 'Gateways heading',
		'gateways' => 'Service gateways',
		'pages' => 'Page introductions',
		'aboutSections' => 'About sections',
		'leaders' => 'Leaders',
		'entities' => 'Parastatals & agencies',
		'organogram' => 'Organogram boxes',
		'investHighlightsHeading' => 'Why invest heading',
		'investHighlights' => 'Why invest cards',
		'licensingHeading' => 'Licensing heading',
		'licensingSteps' => 'Licensing steps',
		'investPortfolioEyebrow' => 'Portfolio eyebrow',
		'investPortfolioHeading' => 'Portfolio heading',
		'investCta' => 'Enquire box',
		'feesHeading' => 'Fees heading',
		'feesReference' => 'Fees reference',
		'fees' => 'Fees',
		'serviceCards' => 'Service cards',
		'headerQuickLinks' => 'Header buttons',
		'footerColumns' => 'Footer columns',
		'socialLinks' => 'Social links',
		'assistantEnabled' => 'Show Ask Assistant',
		'assistantScriptUrl' => 'Assistant script URL',
		'assistantTenant' => 'Assistant tenant',
		'googleMapsApiKey' => 'Maps Static API key',
		'whatsappNumber' => 'WhatsApp number',
		'downloadsEnabled' => 'Enable PDF downloads',
		'homeTasks' => 'I want to… tiles',
		'legalLinks' => 'Footer legal links',
		'verifyHref' => 'Verify official sites URL',
		'navItems' => 'Primary navigation',
		'mabMembers' => 'Mining Affairs Board members',
		'claimNotice' => 'Claim page notice',
		'claimSteps' => 'How to get a mining claim — steps',
		'claimChecklist' => 'Claim requirements checklist',
		'serviceStandards' => 'Customer-care standards',
	);
	return isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
}

/**
 * Settings screen.
 */
function ministry_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tabs    = ministry_settings_tabs();
	$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'brand';
	if ( ! isset( $tabs[ $current ] ) ) {
		$current = 'brand';
	}
	$settings = ministry_settings();
	echo '<div class="wrap"><h1>Ministry Settings</h1>';
	if ( isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
	}
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $tabs as $id => $tab ) {
		$url   = admin_url( 'admin.php?page=ministry-settings&tab=' . $id );
		$class = $id === $current ? 'nav-tab nav-tab-active' : 'nav-tab';
		echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $tab['label'] ) . '</a>';
	}
	echo '</nav>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="ministry_save_settings">';
	echo '<input type="hidden" name="tab" value="' . esc_attr( $current ) . '">';
	wp_nonce_field( 'ministry_save_settings' );
	echo '<div class="mom-settings">';
	foreach ( $tabs[ $current ]['keys'] as $key ) {
		echo '<div class="mom-field"><h2>' . esc_html( ministry_field_label( $key ) ) . '</h2>';
		ministry_render_setting_field( $key, $settings[ $key ] );
		echo '</div>';
	}
	echo '</div>';
	submit_button( 'Save settings' );
	echo '</form></div>';
}

/**
 * Print one setting control.
 *
 * @param string $key   Setting key.
 * @param mixed  $value Current value.
 */
function ministry_render_setting_field( $key, $value ) {
	$name = 'mom[' . $key . ']';
	if ( 'logoId' === $key ) {
		$id   = (int) $value;
		$url  = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
		echo '<input type="hidden" id="mom-logo-id" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $id ) . '">';
		echo '<button type="button" class="button mom-pick-image" data-target="mom-logo-id">Select image</button> ';
		echo '<button type="button" class="button mom-clear-image" data-target="mom-logo-id">Remove</button>';
		echo '<p class="mom-logo-preview">';
		if ( $url ) {
			echo '<img src="' . esc_url( $url ) . '" alt="" style="max-height:72px">';
		}
		echo '</p>';
		return;
	}
	if ( 'pages' === $key && is_array( $value ) ) {
		foreach ( ministry_page_keys() as $page_key ) {
			$page = isset( $value[ $page_key ] ) ? $value[ $page_key ] : array();
			echo '<fieldset class="mom-repeater-row"><legend>' . esc_html( $page_key ) . '</legend>';
			foreach ( array( 'eyebrow', 'title', 'intro' ) as $part ) {
				$field = isset( $page[ $part ] ) ? (string) $page[ $part ] : '';
				echo '<p><label>' . esc_html( ucfirst( $part ) ) . '<br>';
				if ( 'intro' === $part ) {
					echo '<textarea class="widefat" rows="2" name="' . esc_attr( $name . '[' . $page_key . '][' . $part . ']' ) . '">' . esc_textarea( $field ) . '</textarea>';
				} else {
					echo '<input class="widefat" type="text" name="' . esc_attr( $name . '[' . $page_key . '][' . $part . ']' ) . '" value="' . esc_attr( $field ) . '">';
				}
				echo '</label></p>';
			}
			echo '</fieldset>';
		}
		return;
	}
	if ( is_int( $value ) ) {
		echo '<input type="number" min="3" max="60" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
		return;
	}
	if ( is_string( $value ) ) {
		$long = strlen( $value ) > 90 || in_array( $key, array( 'headOfficeAddress', 'footerPortalText' ), true );
		if ( $long ) {
			echo '<textarea class="widefat" rows="3" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
		} else {
			echo '<input class="widefat" type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
		}
		return;
	}
	if ( ! is_array( $value ) ) {
		return;
	}
	if ( ! array_is_list( $value ) ) {
		echo '<div class="mom-repeater-row">';
		foreach ( $value as $sub => $sub_value ) {
			echo '<p><label>' . esc_html( ucfirst( (string) $sub ) ) . '<br>';
			$sub_name = $name . '[' . $sub . ']';
			if ( is_string( $sub_value ) && strlen( $sub_value ) > 80 ) {
				echo '<textarea class="widefat" rows="2" name="' . esc_attr( $sub_name ) . '">' . esc_textarea( $sub_value ) . '</textarea>';
			} else {
				echo '<input class="widefat" type="text" name="' . esc_attr( $sub_name ) . '" value="' . esc_attr( (string) $sub_value ) . '">';
			}
			echo '</label></p>';
		}
		echo '</div>';
		return;
	}
	ministry_render_repeater( $name, $value );
}

/**
 * Repeatable rows. Nested link lists are edited inside the row.
 *
 * @param string              $name  Input name.
 * @param array<int, mixed>   $rows  Current rows.
 */
function ministry_render_repeater( $name, $rows ) {
	$sample = array();
	foreach ( $rows as $row ) {
		$sample = $row;
		break;
	}
	if ( is_string( $sample ) || array() === $rows ) {
		echo '<div class="mom-repeater" data-repeater>';
		$items = $rows ? $rows : array( '' );
		foreach ( $items as $i => $item ) {
			echo '<p data-repeater-row><input class="widefat" type="text" name="' . esc_attr( $name . '[' . $i . ']' ) . '" value="' . esc_attr( (string) $item ) . '"> <button type="button" class="button-link-delete" data-remove-row>Remove</button></p>';
		}
		echo '<button type="button" class="button" data-add-row>Add row</button></div>';
		return;
	}
	echo '<div class="mom-repeater" data-repeater>';
	foreach ( $rows as $i => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		echo '<div class="mom-repeater-row" data-repeater-row>';
		foreach ( $row as $sub => $sub_value ) {
			$sub_name = $name . '[' . $i . '][' . $sub . ']';
			echo '<p><label>' . esc_html( ucfirst( (string) $sub ) );
			if ( 'icon' === $sub ) {
				echo '<br><select name="' . esc_attr( $sub_name ) . '">';
				foreach ( array( 'map', 'document', 'chart', 'archive', 'users', 'phone', 'calendar', 'news' ) as $icon ) {
					echo '<option value="' . esc_attr( $icon ) . '" ' . selected( (string) $sub_value, $icon, false ) . '>' . esc_html( $icon ) . '</option>';
				}
				echo '</select>';
			} elseif ( 'holder' === $sub ) {
				echo '<br><input class="widefat" type="text" name="' . esc_attr( $sub_name ) . '" value="' . esc_attr( (string) $sub_value ) . '">';
				echo '<span class="description">Use @offices to list provincial offices under this box.</span>';
			} elseif ( is_array( $sub_value ) ) {
				echo '</label></p>';
				ministry_render_repeater( $sub_name, $sub_value );
				continue;
			} elseif ( is_string( $sub_value ) && strlen( $sub_value ) > 90 ) {
				echo '<br><textarea class="widefat" rows="2" name="' . esc_attr( $sub_name ) . '">' . esc_textarea( $sub_value ) . '</textarea>';
			} else {
				echo '<br><input class="widefat" type="text" name="' . esc_attr( $sub_name ) . '" value="' . esc_attr( (string) $sub_value ) . '">';
			}
			echo '</label></p>';
		}
		echo '<button type="button" class="button-link-delete" data-remove-row>Remove</button></div>';
	}
	echo '<button type="button" class="button" data-add-row>Add row</button></div>';
}

add_action(
	'admin_post_ministry_save_settings',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot edit these settings.', 'ministry-content' ) );
		}
		check_admin_referer( 'ministry_save_settings' );
		$posted = isset( $_POST['mom'] ) && is_array( $_POST['mom'] ) ? wp_unslash( $_POST['mom'] ) : array();
		$tab    = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'brand';
		$tabs   = ministry_settings_tabs();
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'brand';
		}
		$current = ministry_settings();
		$clean   = ministry_sanitize_settings( $posted, $tabs[ $tab ]['keys'] );
		foreach ( $tabs[ $tab ]['keys'] as $key ) {
			if ( array_key_exists( $key, $clean ) ) {
				$current[ $key ] = $clean[ $key ];
			}
		}
		if ( ! empty( $current['logoId'] ) ) {
			$url = wp_get_attachment_url( (int) $current['logoId'] );
			if ( $url ) {
				$current['logoUrl'] = $url;
			}
		}
		update_option( 'mom_site_settings', $current );
		wp_safe_redirect( admin_url( 'admin.php?page=ministry-settings&tab=' . $tab . '&updated=1' ) );
		exit;
	}
);

/**
 * Sanitize the posted keys against the default shape.
 *
 * @param array<string, mixed> $posted Posted mom array.
 * @param string[]             $keys   Keys on this tab.
 * @return array<string, mixed>
 */
function ministry_sanitize_settings( $posted, $keys ) {
	$defaults = ministry_default_settings();
	$out      = array();
	foreach ( $keys as $key ) {
		if ( ! array_key_exists( $key, $defaults ) ) {
			continue;
		}
		$raw         = isset( $posted[ $key ] ) ? $posted[ $key ] : $defaults[ $key ];
		$out[ $key ] = ministry_sanitize_setting_value( $key, $raw, $defaults[ $key ] );
	}
	return $out;
}

/**
 * Sanitize one value using the default as the shape.
 *
 * @param string $key      Field key.
 * @param mixed  $raw      Posted value.
 * @param mixed  $fallback Default value.
 * @return mixed
 */
function ministry_sanitize_setting_value( $key, $raw, $fallback ) {
	if ( is_int( $fallback ) ) {
		$number = (int) $raw;
		if ( 'heroRotateSeconds' === $key ) {
			return max( 3, min( 60, $number ) );
		}
		return max( 0, $number );
	}
	if ( is_string( $fallback ) ) {
		if ( preg_match( '/(url|href)$/i', $key ) ) {
			return ministry_sanitize_href( (string) $raw );
		}
		return sanitize_textarea_field( (string) $raw );
	}
	if ( ! is_array( $fallback ) || ! is_array( $raw ) ) {
		return $fallback;
	}
	if ( 'pages' === $key ) {
		$pages = $fallback;
		foreach ( ministry_page_keys() as $page_key ) {
			$row = isset( $raw[ $page_key ] ) && is_array( $raw[ $page_key ] ) ? $raw[ $page_key ] : array();
			$pages[ $page_key ] = array(
				'eyebrow' => sanitize_text_field( isset( $row['eyebrow'] ) ? (string) $row['eyebrow'] : $fallback[ $page_key ]['eyebrow'] ),
				'title'   => sanitize_text_field( isset( $row['title'] ) ? (string) $row['title'] : $fallback[ $page_key ]['title'] ),
				'intro'   => sanitize_textarea_field( isset( $row['intro'] ) ? (string) $row['intro'] : $fallback[ $page_key ]['intro'] ),
				'notice'  => isset( $fallback[ $page_key ]['notice'] ) ? (string) $fallback[ $page_key ]['notice'] : '',
			);
		}
		return $pages;
	}
	if ( ! array_is_list( $fallback ) ) {
		$out = array();
		foreach ( $fallback as $sub => $sub_default ) {
			$out[ $sub ] = ministry_sanitize_setting_value( (string) $sub, isset( $raw[ $sub ] ) ? $raw[ $sub ] : $sub_default, $sub_default );
		}
		return $out;
	}
	$sample = null;
	foreach ( $fallback as $item ) {
		$sample = $item;
		break;
	}
	if ( 'navItems' === $key ) {
		$sample = array(
			'label'    => '',
			'href'     => '',
			'children' => array(
				array(
					'label' => '',
					'href'  => '',
				),
			),
		);
	}
	$out = array();
	foreach ( $raw as $item ) {
		if ( is_string( $sample ) ) {
			$text = sanitize_text_field( (string) $item );
			if ( '' !== $text ) {
				$out[] = $text;
			}
			continue;
		}
		if ( ! is_array( $sample ) || ! is_array( $item ) ) {
			continue;
		}
		$clean = ministry_sanitize_setting_value( $key, $item, $sample );
		$has   = false;
		foreach ( $clean as $part ) {
			if ( is_string( $part ) && '' !== $part ) {
				$has = true;
			}
			if ( is_array( $part ) && $part ) {
				$has = true;
			}
		}
		if ( $has ) {
			$out[] = $clean;
		}
	}
	return $out;
}

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'toplevel_page_ministry-settings' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'ministry-content-admin', MINISTRY_CONTENT_URL . 'assets/admin.css', array(), '1.1.0' );
		wp_enqueue_script( 'ministry-content-admin', MINISTRY_CONTENT_URL . 'assets/admin.js', array( 'jquery' ), '1.1.0', true );
	}
);
