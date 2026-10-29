<?php
/**
 * Staff desk screens.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string $crumb   Breadcrumb HTML.
 * @param string $title   Page title.
 * @param string $lede    Intro.
 * @param string $actions Action HTML.
 */
function ministry_desk_head( $crumb, $title, $lede = '', $actions = '' ) {
	if ( $crumb ) {
		echo '<p class="mom-crumb">' . $crumb . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '<div class="mom-head-row"><div><h1>' . esc_html( $title ) . '</h1>';
	if ( $lede ) {
		echo '<p class="mom-lede">' . esc_html( $lede ) . '</p>';
	}
	echo '</div>';
	if ( $actions ) {
		echo '<div class="mom-actions">' . $actions . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div>';
}

/**
 * @param string $form Form key.
 */
function ministry_desk_form_open( $form ) {
	echo '<form id="mom-desk-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="ministry_desk_save">';
	echo '<input type="hidden" name="form" value="' . esc_attr( $form ) . '">';
	echo '<input type="hidden" name="back" value="' . esc_attr( remove_query_arg( 'saved' ) ) . '">';
	wp_nonce_field( 'ministry_desk_save' );
}

/**
 * @param string               $label Label.
 * @param string               $name  Field name.
 * @param string               $value Value.
 * @param array<string, mixed> $args  type, hint, required, options, rows.
 */
function ministry_desk_field( $label, $name, $value, $args = array() ) {
	$type = isset( $args['type'] ) ? $args['type'] : 'text';
	echo '<div class="mom-field"><label>' . esc_html( $label );
	if ( ! empty( $args['required'] ) ) {
		echo ' <span class="mom-req">*</span>';
	}
	foreach ( (array) ( isset( $args['badges'] ) ? $args['badges'] : array() ) as $badge ) {
		echo ' <span class="mom-auto">' . esc_html( (string) $badge ) . '</span>';
	}
	echo '</label>';
	if ( 'textarea' === $type ) {
		echo '<textarea name="' . esc_attr( $name ) . '" rows="' . esc_attr( (string) ( isset( $args['rows'] ) ? $args['rows'] : 3 ) ) . '">' . esc_textarea( $value ) . '</textarea>';
	} elseif ( 'select' === $type ) {
		echo '<select name="' . esc_attr( $name ) . '">';
		foreach ( (array) $args['options'] as $key => $text ) {
			echo '<option value="' . esc_attr( (string) $key ) . '" ' . selected( $value, (string) $key, false ) . '>' . esc_html( (string) $text ) . '</option>';
		}
		echo '</select>';
	} else {
		echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . ( ! empty( $args['readonly'] ) ? ' readonly' : '' ) . '>';
	}
	if ( ! empty( $args['hint'] ) ) {
		echo '<p class="mom-hint">' . esc_html( $args['hint'] ) . '</p>';
	}
	echo '</div>';
}

/**
 * @param string $name  Textarea name.
 * @param string $value HTML or text.
 */
function ministry_desk_editor( $name, $value ) {
	echo '<div class="mom-editor"><div class="mom-editor-bar"><div class="mom-editor-tools">';
	foreach ( array( 'strong' => 'B', 'em' => 'I', 'h2' => 'H2', 'h3' => 'H3', 'li' => 'List', 'link' => 'Link' ) as $token => $label ) {
		echo '<button type="button" data-wrap="' . esc_attr( $token ) . '">' . esc_html( $label ) . '</button>';
	}
	echo '</div><span>Formatting limited to keep the site consistent</span></div>';
	echo '<textarea name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea></div>';
}

/**
 * @param string   $name   Input name.
 * @param string[] $values Chip values.
 */
function ministry_desk_chips( $name, $values ) {
	echo '<div data-chips data-name="' . esc_attr( $name ) . '"><div class="mom-chips">';
	foreach ( $values as $value ) {
		if ( '' === (string) $value ) {
			continue;
		}
		echo '<span class="mom-chip">' . esc_html( (string) $value ) . ' <input type="hidden" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( (string) $value ) . '"><button type="button" data-chip-remove aria-label="Remove">&times;</button></span>';
	}
	echo '</div><p class="mom-rowline"><input class="mom-inline" data-chip-input type="text" placeholder="Add"><button type="button" class="mom-btn mom-btn-small" data-chip-add>Add</button></p></div>';
}

/**
 * @param string $label Badge text.
 * @param string $tone  green|gold|red|blue|slate.
 */
function ministry_desk_badge( $label, $tone = 'slate' ) {
	echo '<span class="mom-badge mom-badge-' . esc_attr( $tone ) . '">' . esc_html( $label ) . '</span>';
}

/**
 * @param WP_Post|null $post Post.
 * @param string       $note Yellow note.
 */
function ministry_desk_publish_card( $post, $note ) {
	$can    = ministry_desk_can_publish();
	$status = $post ? ministry_desk_status_label( $post )[0] : 'Draft — not submitted';
	echo '<section class="mom-side-card"><h2>Publish</h2>';
	echo '<p class="mom-meta-line">' . ministry_desk_icon( 'doc' ) . '<span>Status: <strong>' . esc_html( $status ) . '</strong></span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<p class="mom-meta-line">' . ministry_desk_icon( 'eye' ) . '<span>Visibility: <strong>Public</strong></span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	if ( $post && 'publish' === $post->post_status ) {
		echo '<p class="mom-meta-line">' . ministry_desk_icon( 'calendar' ) . '<span>Published: <strong>' . esc_html( ministry_desk_date( $post->post_date ) ) . '</strong></span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo '<p class="mom-meta-line">' . ministry_desk_icon( 'calendar' ) . '<span>Publish: <strong>' . ( $can ? 'When you update' : 'As soon as approved' ) . '</strong></span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( $note ) {
		echo '<div class="mom-note mom-note-gold"><p>' . esc_html( $note ) . '</p></div>';
	}
	echo '<div class="mom-publish-actions">';
	if ( $post && 'publish' === $post->post_status ) {
		echo '<a class="mom-btn mom-btn-ghost" href="' . esc_url( get_permalink( $post ) ) . '" target="_blank" rel="noopener">' . ministry_desk_icon( 'eye' ) . ' Preview</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( $can && $post && 'publish' === $post->post_status ) {
		echo '<button class="mom-btn mom-btn-primary" name="intent" value="keep">Update</button>';
	} elseif ( $can ) {
		echo '<button class="mom-btn mom-btn-primary" name="intent" value="publish">Publish</button>';
	} else {
		echo '<button class="mom-btn mom-btn-primary" name="intent" value="pending">Submit for review</button>';
	}
	echo '</div><button class="mom-save-draft" name="intent" value="draft">Save draft</button>';
	ministry_desk_delete_button( $post );
	echo '</section>';
}

/**
 * @param int    $post_id Post ID.
 * @param string $page    Desk page slug.
 */
function ministry_desk_languages_card( $post_id, $page ) {
	$i18n = $post_id ? ministry_desk_i18n( $post_id ) : array( 'sn' => array( 'title' => '' ), 'nd' => array( 'title' => '' ) );
	echo '<section class="mom-side-card"><h2>Languages</h2><div class="mom-lang-row"><strong>EN English</strong> <span class="mom-ok">✓ This version</span></div>';
	foreach ( array( 'sn' => 'SN Shona', 'nd' => 'ND Ndebele' ) as $code => $label ) {
		$done = $post_id && '' !== $i18n[ $code ]['title'];
		$url  = $post_id ? ministry_desk_url( $page, array( 'view' => 'translate', 'id' => $post_id, 'lang' => $code ) ) : '#';
		echo '<div class="mom-lang-row"><strong>' . esc_html( $label ) . '</strong> ';
		if ( $done ) {
			echo '<span class="mom-ok">✓ Published</span>';
		} else {
			echo '<a href="' . esc_url( $url ) . '">+ Add translation</a>';
		}
		echo '</div>';
	}
	echo '<p class="mom-hint">Dates, numbers, photos and links are copied to translations automatically. Only text needs translating.</p></section>';
}

/**
 * Navy sidebar.
 */
function ministry_desk_sidebar() {
	$page = ministry_desk_page();
	$view = ministry_desk_view();
	$kind = isset( $_GET['kind'] ) ? sanitize_key( wp_unslash( $_GET['kind'] ) ) : '';
	$type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
	$items = array(
		array( 'page' => 'mom-desk', 'label' => 'Dashboard', 'icon' => 'grid' ),
		array(
			'page' => 'mom-notices',
			'label' => 'Notices & News',
			'icon' => 'megaphone',
			'children' => array(
				array( 'label' => 'Public notices', 'args' => array( 'kind' => 'notice' ) ),
				array( 'label' => 'News & statements', 'args' => array( 'kind' => 'news' ) ),
			),
		),
		array(
			'page' => 'mom-documents',
			'label' => 'Documents',
			'icon' => 'doc',
			'children' => array(
				array( 'label' => 'All documents', 'args' => array() ),
				array( 'label' => 'Add document', 'args' => array( 'add' => '1' ) ),
			),
		),
		array(
			'page' => 'mom-services',
			'label' => 'Services',
			'icon' => 'clock',
			'children' => array(
				array( 'label' => 'Services', 'args' => array() ),
				array( 'label' => 'FAQs', 'args' => array( 'view' => 'faqs' ) ),
			),
		),
		array(
			'page' => 'mom-people',
			'label' => 'People & Structure',
			'icon' => 'people',
			'children' => array(
				array( 'label' => 'People', 'args' => array() ),
				array( 'label' => 'Departments', 'args' => array( 'view' => 'departments' ) ),
				array( 'label' => 'Offices', 'args' => array( 'view' => 'offices' ) ),
				array( 'label' => 'Organisational structure', 'args' => array( 'view' => 'structure' ) ),
				array( 'label' => 'Leadership team', 'args' => array( 'view' => 'leadership' ) ),
			),
		),
		array(
			'page' => 'mom-albums',
			'label' => 'Photo albums',
			'icon' => 'camera',
			'children' => array(
				array( 'label' => 'All albums', 'args' => array() ),
				array( 'label' => 'Upload photos', 'args' => array( 'view' => 'edit' ) ),
			),
		),
		array(
			'page' => 'mom-events',
			'label' => 'Events',
			'icon' => 'calendar',
			'children' => array(
				array( 'label' => 'All events', 'args' => array() ),
				array( 'label' => 'Add event', 'args' => array( 'view' => 'edit' ) ),
			),
		),
		array(
			'page' => 'mom-agencies',
			'label' => 'Agencies & Investments',
			'icon' => 'home',
			'children' => array(
				array( 'label' => 'Agencies', 'args' => array() ),
				array( 'label' => 'Investment opportunities', 'args' => array( 'view' => 'opportunities' ) ),
			),
		),
		array(
			'page' => 'mom-enquiries',
			'label' => 'Enquiries & Forms',
			'icon' => 'mail',
			'children' => array(
				array( 'label' => 'Ask the Minister', 'args' => array( 'type' => 'minister' ) ),
				array( 'label' => 'Complaints', 'args' => array( 'type' => 'complaint' ) ),
				array( 'label' => 'Investor interest', 'args' => array( 'type' => 'investor' ) ),
				array( 'label' => 'Event registrations', 'args' => array( 'type' => 'event' ) ),
			),
		),
		array(
			'page' => 'mom-facts',
			'label' => 'Website Facts & Settings',
			'icon' => 'spark',
			'children' => array(
				array( 'label' => 'Ministry facts', 'args' => array() ),
				array( 'label' => 'Contact details', 'args' => array( 'part' => 'contact' ) ),
				array( 'label' => 'Homepage', 'args' => array( 'part' => 'home' ) ),
				array( 'label' => 'Site appearance', 'args' => array( 'part' => 'look' ) ),
			),
		),
		array( 'page' => 'mom-pages', 'label' => 'Pages', 'icon' => 'pages' ),
	);
	if ( ministry_desk_can( 'users' ) ) {
		$items[] = array( 'page' => 'mom-users', 'label' => 'Users & Roles', 'icon' => 'lock' );
	}
	echo '<aside class="mom-side"><nav class="mom-nav" aria-label="Staff desk">';
	foreach ( $items as $item ) {
		if ( ! ministry_desk_can_page( $item['page'] ) ) {
			continue;
		}
		$on = $page === $item['page'];
		$url = ministry_desk_url( $item['page'] );
		if ( empty( $item['children'] ) ) {
			echo '<a class="' . ( $on ? 'is-on' : '' ) . '" href="' . esc_url( $url ) . '">' . ministry_desk_icon( $item['icon'] ) . esc_html( $item['label'] ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			continue;
		}
		echo '<details ' . ( $on ? 'open class="is-on"' : '' ) . '><summary>' . ministry_desk_icon( $item['icon'] ) . esc_html( $item['label'] ) . '</summary><div class="mom-sub">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		foreach ( $item['children'] as $child ) {
			$child_on = $on;
			foreach ( $child['args'] as $key => $value ) {
				$current = 'view' === $key ? $view : ( 'kind' === $key ? $kind : ( 'type' === $key ? $type : ( isset( $_GET[ $key ] ) ? sanitize_key( wp_unslash( $_GET[ $key ] ) ) : '' ) ) );
				if ( $current !== $value ) {
					$child_on = false;
				}
			}
			if ( ! $child['args'] ) {
				$child_on = $on && '' === $view && '' === $kind && '' === $type;
			}
			echo '<a class="' . ( $child_on ? 'is-on' : '' ) . '" href="' . esc_url( ministry_desk_url( $item['page'], $child['args'] ) ) . '">' . esc_html( $child['label'] ) . '</a>';
		}
		echo '</div></details>';
	}
	echo '</nav><p class="mom-side-note">Hidden for staff: Appearance, Plugins, Tools, Comments. Users &amp; Roles: Administrators only.';
	if ( ministry_desk_can( 'users' ) ) {
		$wordfence = function_exists( 'ministry_desk_wordfence_url' ) ? ministry_desk_wordfence_url() : '';
		echo '<br>';
		if ( $wordfence ) {
			echo '<a href="' . esc_url( $wordfence ) . '">Wordfence</a> · ';
		} elseif ( file_exists( WP_PLUGIN_DIR . '/wordfence/wordfence.php' ) ) {
			echo '<a href="' . esc_url( admin_url( 'plugins.php?s=wordfence&plugin_status=inactive' ) ) . '">Activate Wordfence</a> · ';
		}
		echo '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">Plugins</a> · <a href="' . esc_url( admin_url( 'themes.php' ) ) . '">Appearance</a> · <a href="' . esc_url( admin_url( 'tools.php' ) ) . '">Tools</a>';
	}
	echo '</p></aside>';
}

/**
 * Top bar.
 */
function ministry_desk_topbar() {
	$user    = wp_get_current_user();
	$pending = count( ministry_desk_pending() ) + count( (array) ministry_desk_meta()['person_pending'] );
	$logo    = get_template_directory_uri() . '/site/images/logo-full.png';
	$settings = function_exists( 'ministry_settings' ) ? ministry_settings() : array();
	if ( ! empty( $settings['logoUrl'] ) ) {
		$logo = $settings['logoUrl'];
	}
	$lang = ministry_desk_lang();
	echo '<header class="mom-top"><div class="mom-top-links">';
	echo '<button type="button" class="mom-burger" aria-label="Menu">' . ministry_desk_icon( 'grid' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<a class="mom-brand" href="' . esc_url( ministry_desk_url( 'mom-desk' ) ) . '"><img src="' . esc_url( $logo ) . '" alt=""><span class="mom-brand-long">Ministry of Mines and Mining Development</span><span class="mom-brand-short">MMMD website</span></a>';
	echo '<a href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">' . ministry_desk_icon( 'eye' ) . ' Visit site</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$news = array(
		'Public notice' => array( 'notices', ministry_desk_url( 'mom-notices', array( 'view' => 'edit', 'kind' => 'notice' ) ) ),
		'News item'     => array( 'notices', ministry_desk_url( 'mom-notices', array( 'view' => 'news' ) ) ),
		'Document'      => array( 'documents', ministry_desk_url( 'mom-documents', array( 'add' => '1' ) ) ),
		'Event'         => array( 'events', ministry_desk_url( 'mom-events', array( 'view' => 'edit' ) ) ),
		'Photo album'   => array( 'albums', ministry_desk_url( 'mom-albums', array( 'view' => 'edit' ) ) ),
		'Person'        => array( 'people', ministry_desk_url( 'mom-people', array( 'view' => 'person' ) ) ),
		'FAQ'           => array( 'services', ministry_desk_url( 'mom-services', array( 'view' => 'faqs' ) ) ),
	);
	$new_links = '';
	foreach ( $news as $label => $row ) {
		if ( ! ministry_desk_can( $row[0] ) ) {
			continue;
		}
		$new_links .= '<a href="' . esc_url( $row[1] ) . '">' . esc_html( $label ) . '</a>';
	}
	if ( $new_links ) {
		echo '<details class="mom-new"><summary>' . ministry_desk_icon( 'plus' ) . ' New</summary><div class="mom-new-menu">' . $new_links . '</div></details>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div><div class="mom-top-meta">';
	echo '<details class="mom-lang"><summary>Editing in: <strong>' . esc_html( ministry_desk_lang_label() ) . '</strong></summary><div class="mom-lang-menu">';
	foreach ( array( 'en' => 'English', 'sn' => 'ChiShona', 'nd' => 'Ndebele' ) as $code => $label ) {
		echo '<a class="' . ( $lang === $code ? 'is-on' : '' ) . '" href="' . esc_url( add_query_arg( 'desk_lang', $code ) ) . '">' . esc_html( $label ) . '</a>';
	}
	echo '</div></details>';
	echo '<a class="mom-icon-btn" href="' . esc_url( ministry_desk_url( 'mom-desk' ) . '#waiting' ) . '" aria-label="Waiting for approval">' . ministry_desk_icon( 'bell' ) . ( $pending ? '<span class="mom-count">' . esc_html( (string) $pending ) . '</span>' : '' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<a class="mom-who" href="' . esc_url( ministry_desk_can( 'users' ) ? ministry_desk_url( 'mom-users' ) : admin_url( 'profile.php' ) ) . '"><span class="mom-avatar">' . esc_html( ministry_desk_initials( $user ) ) . '</span><span>' . esc_html( $user->display_name ) . '<small>' . esc_html( ministry_desk_role_label( $user ) ) . '</small></span></a>';
	echo '<a class="mom-logout" href="' . esc_url( wp_logout_url( wp_login_url() ) ) . '">' . ministry_desk_icon( 'logout' ) . ' Log out</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div></header>';
}

/**
 * Dashboard.
 */
function ministry_desk_screen_dashboard() {
	$user     = wp_get_current_user();
	$first    = strtok( (string) $user->display_name, ' ' );
	$hour     = (int) wp_date( 'G' );
	$hello    = $hour < 12 ? 'Good morning' : ( $hour < 17 ? 'Good afternoon' : 'Good evening' );
	$settings = ministry_settings();
	$pending  = ministry_desk_pending();
	$people   = (array) ministry_desk_meta()['person_pending'];
	$news     = (int) wp_count_posts( 'mom_news' )->publish;
	$docs     = (int) wp_count_posts( 'mom_document' )->publish;
	$albums   = (int) wp_count_posts( 'mom_album' )->publish;
	$offices  = count( ministry_departments( true ) );
	$depts    = count( ministry_departments( false ) );
	$leaders  = isset( $settings['leaders'] ) && is_array( $settings['leaders'] ) ? count( $settings['leaders'] ) : 0;
	$recent   = ministry_desk_posts( array( 'mom_news', 'mom_document', 'mom_event', 'mom_department', 'mom_album', 'mom_faq' ), array( 'posts_per_page' => 6 ) );
	echo '<div class="mom-only-phone">';
	echo '<p class="mom-crumb">MMMD website</p><h1>Waiting for approval</h1>';
	ministry_desk_approval_cards( $pending, $people );
	echo '<p class="mom-lede">The admin works on phones, so urgent notices can be approved away from the office.</p></div>';
	echo '<div class="mom-only-desk">';
	ministry_desk_head( '', $hello . ', ' . ( $first ? $first : 'there' ), 'Here’s what needs your attention on ' . wp_parse_url( home_url(), PHP_URL_HOST ) . '.', '<a class="mom-btn mom-btn-ghost" href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">' . ministry_desk_icon( 'eye' ) . ' View the website</a>' );
	$actions = array(
		array( 'Post a public notice', 'Numbered automatically', ministry_desk_url( 'mom-notices', array( 'view' => 'edit', 'kind' => 'notice' ) ), 'megaphone', '' ),
		array( 'Add news or a statement', 'Press releases, speeches', ministry_desk_url( 'mom-notices', array( 'view' => 'news' ) ), 'doc', 'gold' ),
		array( 'Upload a document', 'Acts, forms, reports', ministry_desk_url( 'mom-documents', array( 'add' => '1' ) ), 'doc', 'gold' ),
		array( 'Upload photos', 'Add to an album', ministry_desk_url( 'mom-albums', array( 'view' => 'edit' ) ), 'camera', '' ),
		array( 'Add an event', 'Shows, workshops, tours', ministry_desk_url( 'mom-events', array( 'view' => 'edit' ) ), 'calendar', 'gold' ),
		array( 'Update a person', 'Portraits, titles, bios', ministry_desk_url( 'mom-people', array( 'view' => 'person' ) ), 'people', 'gold' ),
	);
	echo '<div class="mom-grid-3" style="margin-bottom:12px">';
	foreach ( $actions as $action ) {
		echo '<a class="mom-quick" href="' . esc_url( $action[2] ) . '"><span class="mom-ico ' . esc_attr( $action[4] ) . '">' . ministry_desk_icon( $action[3] ) . '</span><span><b>' . esc_html( $action[0] ) . '</b><span>' . esc_html( $action[1] ) . '</span></span></a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div><div class="mom-grid-6" style="margin-bottom:16px">';
	foreach ( array( array( $docs, 'Documents' ), array( $news, 'Notices this year' ), array( $leaders, 'People' ), array( $depts, 'Departments' ), array( $offices, 'Provincial offices' ), array( $albums, 'Photo albums' ) ) as $stat ) {
		echo '<div class="mom-stat"><strong>' . esc_html( (string) $stat[0] ) . '</strong><span>' . esc_html( $stat[1] ) . '</span></div>';
	}
	echo '</div><div class="mom-split"><div class="mom-main-col">';
	$waiting = count( $pending ) + count( $people );
	echo '<section class="mom-card" id="waiting"><div class="mom-card-head"><h2>Waiting for approval</h2>';
	if ( $waiting ) {
		echo '<span class="mom-badge mom-badge-gold">' . esc_html( (string) $waiting ) . ' ' . esc_html( _n( 'item', 'items', $waiting, 'ministry-content' ) ) . '</span>';
	}
	echo '</div>';
	if ( ! $waiting ) {
		echo '<p class="mom-lede">Nothing is waiting. New submissions from content officers will appear here.</p>';
	}
	foreach ( $pending as $post ) {
		$who = get_the_author_meta( 'display_name', $post->post_author );
		echo '<div class="mom-wait"><div class="mom-wait-copy"><div class="mom-wait-meta"><span class="mom-kicker">' . esc_html( ministry_desk_kind_label( ministry_desk_kind( $post ) ) ) . '</span> by ' . esc_html( $who ) . ' · ' . esc_html( ministry_desk_when( $post->post_modified ) ) . '</div>';
		echo '<strong>' . esc_html( $post->post_title ) . '</strong></div>';
		echo '<a class="mom-btn mom-btn-primary mom-btn-small" href="' . esc_url( ministry_desk_url( 'mom-desk', array( 'view' => 'review', 'id' => $post->ID ) ) ) . '">Review</a></div>';
	}
	foreach ( $people as $key => $item ) {
		$row = isset( $item['row'] ) ? $item['row'] : array();
		echo '<div class="mom-wait"><div class="mom-wait-copy"><div class="mom-wait-meta"><span class="mom-kicker">Person</span> by ' . esc_html( isset( $item['user'] ) ? (string) $item['user'] : 'Staff' ) . '</div>';
		echo '<strong>' . esc_html( isset( $row['name'] ) ? (string) $row['name'] : 'Person' ) . '</strong></div>';
		echo '<a class="mom-btn mom-btn-primary mom-btn-small" href="' . esc_url( ministry_desk_url( 'mom-desk', array( 'view' => 'review', 'person' => (string) $key ) ) ) . '">Review</a></div>';
	}
	echo '</section><section class="mom-card"><h2>Recent changes</h2>';
	foreach ( $recent as $post ) {
		$who = get_the_author_meta( 'display_name', $post->post_author );
		echo '<div class="mom-recent"><time>' . esc_html( ministry_desk_when( $post->post_modified ) ) . '</time><span><strong>' . esc_html( $who ) . '</strong> updated <a href="' . esc_url( get_edit_post_link( $post->ID, 'raw' ) ) . '">' . esc_html( $post->post_title ) . '</a></span></div>';
	}
	echo '</section></div><div class="mom-side-col">';
	ministry_desk_expiring_card();
	ministry_desk_health_card();
	echo '</div></div></div>';
}

/**
 * @param WP_Post[]            $pending Posts.
 * @param array<string, mixed> $people  Pending people.
 */
function ministry_desk_approval_cards( $pending, $people ) {
	if ( ! $pending && ! $people ) {
		echo '<article class="mom-approval"><h2>Nothing is waiting</h2><p>You are up to date.</p></article>';
	}
	foreach ( array_slice( $pending, 0, 3 ) as $post ) {
		echo '<article class="mom-approval"><span class="mom-kicker">' . esc_html( get_post_type_object( $post->post_type )->labels->singular_name ) . '</span>';
		echo '<h2>' . esc_html( $post->post_title ) . '</h2>';
		echo '<p>by ' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) ) . '</p>';
		if ( $post->post_excerpt ) {
			echo '<div class="mom-quote">' . esc_html( wp_trim_words( $post->post_excerpt, 28 ) ) . '</div>';
		}
		echo '<p class="mom-actions"><a class="mom-btn" href="' . esc_url( ministry_desk_url( 'mom-desk', array( 'view' => 'review', 'id' => $post->ID ) ) ) . '">Request changes</a>';
		echo '<a class="mom-btn mom-btn-primary" href="' . esc_url( ministry_desk_url( 'mom-desk', array( 'view' => 'review', 'id' => $post->ID ) ) ) . '">Approve</a></p></article>';
	}
}

/**
 * Deadlines coming up.
 */
function ministry_desk_expiring_card() {
	echo '<section class="mom-side-card"><h2>Expiring this week</h2>';
	$shown = false;
	foreach ( ministry_desk_posts( 'mom_news', array( 'posts_per_page' => 20 ) ) as $post ) {
		$until = (string) get_post_meta( $post->ID, '_mom_banner_until', true );
		if ( $until && strtotime( $until ) < time() + WEEK_IN_SECONDS && strtotime( $until ) > time() - DAY_IN_SECONDS ) {
			$edit = ministry_desk_url( 'mom-notices', array( 'view' => 'notice' === ministry_desk_kind( $post ) ? 'edit' : 'news', 'id' => $post->ID ) );
			echo '<div class="mom-line"><div><strong>Homepage banner ends</strong><br>' . esc_html( $post->post_title ) . ' — ' . esc_html( ministry_desk_date( $until ) ) . '</div><a class="mom-act" href="' . esc_url( $edit ) . '">Extend</a></div>';
			$shown = true;
		}
	}
	foreach ( ministry_events() as $event ) {
		$start = (string) get_post_meta( $event->ID, '_mom_starts_at', true );
		if ( $start && strtotime( $start ) > time() && strtotime( $start ) < time() + WEEK_IN_SECONDS ) {
			echo '<div class="mom-line"><div><strong>Event is soon</strong><br>' . esc_html( $event->post_title ) . ' — ' . esc_html( ministry_desk_date( $start ) ) . '</div><a class="mom-act" href="' . esc_url( ministry_desk_url( 'mom-events', array( 'view' => 'edit', 'id' => $event->ID ) ) ) . '">View</a></div>';
			$shown = true;
		}
	}
	if ( ! $shown ) {
		echo '<p class="mom-lede">No banners or events expire in the next seven days.</p>';
	}
	echo '</section>';
}

/**
 * Content that still needs a file, portrait, or translation.
 */
function ministry_desk_health_card() {
	$missing_photo = 0;
	foreach ( ministry_settings()['leaders'] as $leader ) {
		if ( ! is_array( $leader ) || '' === ministry_desk_leader_photo( $leader ) ) {
			$missing_photo++;
		}
	}
	$missing_file = 0;
	foreach ( ministry_desk_posts( 'mom_document', array( 'posts_per_page' => 200 ) ) as $doc ) {
		if ( ! ministry_document_url( $doc->ID ) ) {
			$missing_file++;
		}
	}
	$missing_alt = 0;
	foreach ( ministry_albums() as $album ) {
		foreach ( $album['photos'] as $photo ) {
			if ( '' === trim( (string) $photo['alt'] ) ) {
				$missing_alt++;
			}
		}
	}
	$stale = 0;
	foreach ( ministry_desk_posts( array( 'mom_news', 'mom_service', 'mom_faq', 'page' ), array( 'posts_per_page' => 200 ) ) as $post ) {
		if ( '1' === get_post_meta( $post->ID, '_mom_i18n_stale', true ) ) {
			$stale++;
		}
	}
	$rows = array(
		array( $missing_photo, 'people have no portrait', ministry_desk_url( 'mom-people' ), $missing_photo > 0 ),
		array( $missing_file, 'documents waiting for a file', ministry_desk_url( 'mom-documents' ), $missing_file > 0 ),
		array( $missing_alt, 'photos missing alt text', ministry_desk_url( 'mom-albums' ), $missing_alt > 0 ),
		array( $stale, 'translations out of date', ministry_desk_url( 'mom-services', array( 'view' => 'faqs' ) ), $stale > 0 ),
	);
	echo '<section class="mom-side-card"><h2>Content health</h2><div class="mom-health">';
	foreach ( $rows as $row ) {
		echo '<a class="' . ( $row[3] ? 'is-bad' : '' ) . '" href="' . esc_url( $row[2] ) . '"><span><b>' . esc_html( (string) $row[0] ) . '</b> ' . esc_html( $row[1] ) . '</span><span>›</span></a>';
	}
	echo '</div><p class="mom-hint">Checked from the live records. Click a line to see the items.</p></section>';
	if ( ministry_desk_can( 'users' ) ) {
		ministry_desk_import_card();
	}
}

/**
 * Manual design-file import. Never runs on ordinary page loads.
 */
function ministry_desk_import_card() {
	$done = '1' === get_option( 'mom_content_imported' );
	echo '<section class="mom-side-card"><h2>Design import</h2>';
	if ( isset( $_GET['imported'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<p class="mom-lede">' . esc_html__( 'Design files were copied into WordPress records. The public site now uses those records.', 'ministry-content' ) . '</p>';
	} elseif ( $done ) {
		echo '<p class="mom-lede">' . esc_html__( 'Theme HTML is import-only. Re-run only if you replaced the design files.', 'ministry-content' ) . '</p>';
	} else {
		echo '<p class="mom-lede">' . esc_html__( 'Copy the theme HTML into WordPress once. Until then, public pages have no stored layout.', 'ministry-content' ) . '</p>';
	}
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="ministry_content_import">';
	wp_nonce_field( 'ministry_content_import' );
	echo '<button class="mom-btn mom-btn-primary" type="submit">' . esc_html( $done ? 'Re-run import' : 'Import design files' ) . '</button>';
	echo '</form></section>';
}

/**
 * Notices and news list.
 */
function ministry_desk_screen_notices() {
	$kind  = isset( $_GET['kind'] ) ? sanitize_key( wp_unslash( $_GET['kind'] ) ) : '';
	$state = isset( $_GET['state'] ) ? sanitize_key( wp_unslash( $_GET['state'] ) ) : '';
	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$posts = ministry_desk_posts( 'mom_news', array( 'orderby' => 'date', 's' => $search ) );
	$counts = array( 'all' => count( $posts ), 'notice' => 0, 'press' => 0, 'speech' => 0, 'tender' => 0, 'vacancy' => 0, 'pending' => 0, 'draft' => 0 );
	foreach ( $posts as $post ) {
		$item_kind = ministry_desk_kind( $post );
		if ( isset( $counts[ $item_kind ] ) ) {
			$counts[ $item_kind ]++;
		}
		if ( 'pending' === $post->post_status ) {
			$counts['pending']++;
		}
		if ( 'draft' === $post->post_status ) {
			$counts['draft']++;
		}
	}
	$actions = '<a class="mom-btn mom-btn-primary" href="' . esc_url( ministry_desk_url( 'mom-notices', array( 'view' => 'edit', 'kind' => 'notice' ) ) ) . '">+ New public notice</a>';
	$actions .= '<a class="mom-btn" href="' . esc_url( ministry_desk_url( 'mom-notices', array( 'view' => 'news' ) ) ) . '">+ New news item</a>';
	ministry_desk_head( 'Notices & News', 'Notices & News', 'Public notices are numbered automatically per year. Press statements, speeches and tenders sit in the same list.', $actions );
	echo '<div class="mom-pills">';
	$pills = array(
		''        => array( 'All', $counts['all'] ),
		'notice'  => array( 'Public notices', $counts['notice'] ),
		'press'   => array( 'Press statements', $counts['press'] ),
		'speech'  => array( 'Speeches', $counts['speech'] ),
		'tender'  => array( 'Tenders', $counts['tender'] ),
		'vacancy' => array( 'Vacancies', $counts['vacancy'] ),
	);
	foreach ( $pills as $key => $pill ) {
		$on = $kind === $key && '' === $state;
		echo '<a class="mom-pill' . ( $on ? ' is-on' : '' ) . '" href="' . esc_url( ministry_desk_url( 'mom-notices', $key ? array( 'kind' => $key ) : array() ) ) . '">' . esc_html( $pill[0] . ' (' . $pill[1] . ')' ) . '</a>';
	}
	echo '<a class="mom-pill' . ( 'pending' === $state ? ' is-on' : '' ) . '" href="' . esc_url( ministry_desk_url( 'mom-notices', array( 'state' => 'pending' ) ) ) . '">Waiting for review (' . esc_html( (string) $counts['pending'] ) . ')</a>';
	echo '<a class="mom-pill' . ( 'draft' === $state ? ' is-on' : '' ) . '" href="' . esc_url( ministry_desk_url( 'mom-notices', array( 'state' => 'draft' ) ) ) . '">Drafts (' . esc_html( (string) $counts['draft'] ) . ')</a>';
	echo '</div>';
	ministry_desk_form_open( 'bulk' );
	echo '<div class="mom-toolbar"><input class="mom-search" type="search" name="s" form="mom-notice-search" value="' . esc_attr( $search ) . '" placeholder="Search notices and news…">';
	echo '<select name="bulk"><option value="">Bulk actions</option><option value="pending">Submit for review</option><option value="publish">Publish</option><option value="draft">Move to draft</option><option value="trash">Delete</option></select>';
	echo '<button class="mom-btn" name="intent" value="bulk">Apply</button>';
	echo '<span class="mom-hint">Columns: EN / SN / ND show which languages are live</span></div>';
	echo '<div class="mom-table-card"><table class="mom-table"><thead><tr><th></th><th>Type</th><th>No.</th><th>Title</th><th>Status</th><th>Author</th><th>Date</th><th>Languages</th></tr></thead><tbody>';
	foreach ( $posts as $post ) {
		$item_kind = ministry_desk_kind( $post );
		if ( $kind && $item_kind !== $kind && ! ( 'news' === $kind && 'notice' !== $item_kind ) ) {
			continue;
		}
		if ( 'news' === $kind && 'notice' === $item_kind ) {
			continue;
		}
		if ( $state && $post->post_status !== $state ) {
			continue;
		}
		$status = ministry_desk_status_label( $post );
		$edit   = 'notice' === $item_kind ? array( 'view' => 'edit', 'id' => $post->ID ) : array( 'view' => 'news', 'id' => $post->ID );
		$i18n   = ministry_desk_i18n( $post->ID );
		$number = (string) get_post_meta( $post->ID, '_mom_number', true );
		echo '<tr><td><input type="checkbox" name="ids[]" value="' . esc_attr( (string) $post->ID ) . '"></td>';
		echo '<td>';
		ministry_desk_badge( ministry_desk_kind_label( $item_kind ), 'notice' === $item_kind ? 'blue' : 'slate' );
		echo '</td>';
		echo '<td><strong>' . esc_html( $number ? $number : '—' ) . '</strong></td>';
		echo '<td><a class="title" href="' . esc_url( ministry_desk_url( 'mom-notices', $edit ) ) . '">' . esc_html( $post->post_title ) . '</a>';
		if ( '1' === get_post_meta( $post->ID, '_mom_banner', true ) ) {
			echo '<div class="mom-home-flag">Homepage banner</div>';
		}
		echo '<div class="mom-row-links"><a href="' . esc_url( ministry_desk_url( 'mom-notices', $edit ) ) . '">Edit</a> · <a href="' . esc_url( get_permalink( $post ) ) . '">Preview</a> · <a href="' . esc_url( ministry_desk_url( 'mom-notices', array( 'view' => 'translate', 'id' => $post->ID, 'lang' => 'sn' ) ) ) . '">Translate</a>';
		ministry_desk_delete_link( $post->ID, ministry_desk_url( 'mom-notices' ) );
		echo '</div></td>';
		echo '<td>';
		ministry_desk_badge( $status[0], $status[1] );
		echo '</td><td>' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) ) . '</td><td>' . esc_html( ministry_desk_date( $post->post_date ) ) . '</td>';
		echo '<td class="mom-langs"><span class="is-live">EN</span> <span class="' . ( $i18n['sn']['title'] ? 'is-live' : 'is-off' ) . '">SN</span> <span class="' . ( $i18n['nd']['title'] ? 'is-live' : 'is-off' ) . '">ND</span></td></tr>';
	}
	echo '</tbody></table></div></form>';
	echo '<form id="mom-notice-search" method="get"><input type="hidden" name="page" value="mom-notices">' . ( $kind ? '<input type="hidden" name="kind" value="' . esc_attr( $kind ) . '">' : '' ) . '</form>';
	echo '<div class="mom-notes-2"><div class="mom-note mom-note-blue"><p><strong>Why a separate “No.” column?</strong> The site keeps a running register (01/' . esc_html( wp_date( 'Y' ) ) . ', 02/' . esc_html( wp_date( 'Y' ) ) . '…). Numbers can’t be reused or skipped, which keeps the public record trustworthy.</p></div>';
	echo '<div class="mom-note mom-note-blue"><p><strong>Tenders close themselves</strong> on their closing date and move to “Closed”.</p></div></div>';
}

/**
 * Public notice editor.
 */
function ministry_desk_screen_notice() {
	$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$post = $id ? get_post( $id ) : null;
	$number = $post ? (string) get_post_meta( $id, '_mom_number', true ) : ministry_desk_next_notice_number();
	$title = $post ? $post->post_title : '';
	ministry_desk_head(
		'<a href="' . esc_url( ministry_desk_url( 'mom-notices' ) ) . '">Notices & News</a> / <a href="' . esc_url( ministry_desk_url( 'mom-notices', array( 'kind' => 'notice' ) ) ) . '">Public notices</a> / Edit',
		$title ? $title : 'New public notice'
	);
	ministry_desk_form_open( 'notice' );
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $id ) . '">';
	echo '<div class="mom-split"><div class="mom-main-col">';
	echo '<section class="mom-card"><h2>Notice details</h2><div class="mom-grid-3">';
	ministry_desk_field( 'Notice number', 'notice_number', $number, array( 'readonly' => ! ministry_desk_can( 'notice_numbers' ), 'badges' => array( 'AUTO', 'Director only' ), 'hint' => 'Next number in sequence. Only a role that can change notice numbers may edit this.' ) );
	ministry_desk_field( 'Type', 'kind_label', 'Public notice', array( 'readonly' => true ) );
	ministry_desk_field( 'Date issued', 'issued', $post ? (string) get_post_meta( $id, '_mom_issued', true ) : wp_date( 'Y-m-d' ), array( 'type' => 'date', 'required' => true ) );
	echo '</div><div class="mom-grid-3">';
	ministry_desk_field( 'Takes effect', 'effect', $post ? (string) get_post_meta( $id, '_mom_effect', true ) : 'Immediately' );
	ministry_desk_field( 'Deadline (optional)', 'deadline', $post ? (string) get_post_meta( $id, '_mom_deadline', true ) : '', array( 'type' => 'date', 'hint' => 'Shown as a highlighted deadline on the notice.' ) );
	echo '<div class="mom-field"><label>Applies to</label>';
	$applies = $post ? (array) get_post_meta( $id, '_mom_applies', true ) : array( 'All provinces' );
	ministry_desk_chips( 'applies', $applies );
	echo '</div></div>';
	ministry_desk_field( 'Short summary', 'post_excerpt', $post ? $post->post_excerpt : '', array( 'type' => 'textarea', 'required' => true, 'hint' => 'Shown on cards and search results. 160 characters maximum.' ) );
	echo '</section><section class="mom-card"><h2>Notice text</h2>';
	ministry_desk_editor( 'post_content', $post ? $post->post_content : '' );
	echo '</section><section class="mom-card"><h2>Signed and attached</h2><div class="mom-grid-2">';
	ministry_desk_field( 'Signed by', 'signed_by', $post ? (string) get_post_meta( $id, '_mom_signed_by', true ) : '', array( 'required' => true, 'hint' => 'Name and title are saved with the notice, so they stay correct if the person later changes role.' ) );
	$pdf = $post ? (int) get_post_meta( $id, '_mom_pdf_id', true ) : 0;
	echo '<div class="mom-field"><label>Signed PDF <span class="mom-req">*</span></label><input type="hidden" id="mom-pdf" name="pdf_id" value="' . esc_attr( (string) $pdf ) . '">';
	echo '<button type="button" class="mom-btn mom-pick" data-target="mom-pdf">Choose file</button> <span class="mom-file-name">' . esc_html( $pdf ? basename( (string) get_attached_file( $pdf ) ) : 'No file yet' ) . '</span>';
	echo '<p class="mom-hint">Upload the scanned, signed copy. It is also added to Documents.</p></div></div></section>';
	$banner = $post && '1' === get_post_meta( $id, '_mom_banner', true );
	echo '<section class="mom-card"><h2>Homepage banner</h2>';
	echo '<label class="mom-toggle"><input type="checkbox" name="banner" value="1" ' . checked( $banner, true, false ) . '> Show this notice as a banner across the top of every page</label>';
	echo '<div class="mom-grid-2" style="margin-top:12px">';
	ministry_desk_field( 'Banner text', 'banner_text', $post ? (string) get_post_meta( $id, '_mom_banner_text', true ) : '', array( 'hint' => 'Keep under 90 characters.' ) );
	ministry_desk_field( 'Remove banner on', 'banner_until', $post ? (string) get_post_meta( $id, '_mom_banner_until', true ) : '', array( 'type' => 'date', 'hint' => 'The banner switches off by itself. You’ll get a reminder 2 days before.' ) );
	echo '</div>';
	$banner_text = $post ? (string) get_post_meta( $id, '_mom_banner_text', true ) : '';
	echo '<p class="mom-hint">PREVIEW</p><div class="mom-banner-preview" data-banner-preview><span>' . esc_html( $banner_text ? $banner_text : 'Banner text shows here.' ) . '</span><span>Read notice</span></div>';
	echo '<p class="mom-hint">Only one banner can show at a time. Turning this on replaces the current banner.</p></section>';
	echo '</div><div class="mom-side-col">';
	$note = ministry_desk_can_publish() ? 'Edits to a published notice are logged.' : 'Your role can’t publish notices. The Communications Director reviews and publishes. You’ll be emailed when it’s live.';
	ministry_desk_publish_card( $post, $note );
	ministry_desk_languages_card( $id, 'mom-notices' );
	echo '<section class="mom-side-card"><h2>Checklist</h2><ul class="mom-check">';
	echo '<li><span class="' . ( $title && $post && $post->post_excerpt ? 'mom-ok' : 'mom-warn' ) . '">✓</span> Title and summary</li>';
	echo '<li><span class="' . ( $post && get_post_meta( $id, '_mom_signed_by', true ) ? 'mom-ok' : 'mom-warn' ) . '">✓</span> Signed by</li>';
	echo '<li><span class="' . ( $pdf ? 'mom-ok' : 'mom-warn' ) . '">✓</span> Signed PDF attached</li>';
	echo '<li><span class="mom-warn">!</span> Social share image (optional)</li></ul></section>';
	if ( $post ) {
		$revs = wp_get_post_revisions( $id );
		echo '<section class="mom-side-card"><h2>Revisions</h2><p>' . esc_html( (string) count( $revs ) ) . ' saved versions.</p></section>';
	}
	echo '</div></div></form>';
}

/**
 * News, speech, tender, or vacancy editor.
 */
function ministry_desk_screen_news() {
	$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$post = $id ? get_post( $id ) : null;
	$kind = $post ? ministry_desk_kind( $post ) : 'press';
	if ( 'notice' === $kind ) {
		$kind = 'press';
	}
	ministry_desk_head(
		'<a href="' . esc_url( ministry_desk_url( 'mom-notices' ) ) . '">Notices & News</a> / Edit',
		$post ? $post->post_title : 'New news item'
	);
	ministry_desk_form_open( 'news' );
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $id ) . '">';
	$thumb = $post ? (int) get_post_thumbnail_id( $post ) : 0;
	echo '<div class="mom-split"><div class="mom-main-col"><section class="mom-card"><h2>Story</h2>';
	ministry_desk_field( 'Headline', 'post_title', $post ? $post->post_title : '', array( 'required' => true ) );
	ministry_desk_field( 'Standfirst (summary)', 'post_excerpt', $post ? $post->post_excerpt : '', array( 'type' => 'textarea', 'required' => true, 'hint' => 'Shown under the headline and on cards. Up to 40 words.' ) );
	echo '<label class="mom-label">Body</label>';
	ministry_desk_editor( 'post_content', $post ? $post->post_content : '' );
	echo '</section><section class="mom-card"><h2>Featured photo</h2><div class="mom-photo-edit">';
	echo '<div><input type="hidden" id="mom-thumb" name="thumbnail_id" value="' . esc_attr( (string) $thumb ) . '">';
	echo '<div class="mom-pick-preview">';
	if ( $thumb ) {
		echo wp_get_attachment_image( $thumb, 'medium' );
	}
	echo '</div><p><button type="button" class="mom-btn mom-pick" data-target="mom-thumb" data-library="image" data-aspect="16:9">Choose photo</button></p></div>';
	echo '<div><div class="mom-note mom-note-blue"><p>Use the Ministry’s own photo where possible. Stock photos must be credited.</p></div>';
	ministry_desk_field( 'Photo description (alt text)', 'alt', $thumb ? (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ) : '', array( 'required' => true ) );
	ministry_desk_field( 'Caption / credit', 'caption', $post ? (string) get_post_meta( $id, '_mom_caption', true ) : '' );
	echo '</div></div></section></div><div class="mom-side-col">';
	ministry_desk_publish_card( $post, 'Edits to a published statement are logged. Major corrections should be marked as a correction below.' );
	echo '<section class="mom-side-card"><h2>Classification</h2>';
	ministry_desk_field( 'Type', 'kind', $kind, array( 'type' => 'select', 'required' => true, 'options' => array( 'press' => 'Press statement', 'speech' => 'Speech', 'tender' => 'Tender', 'vacancy' => 'Vacancy', 'news' => 'News' ) ) );
	echo '<div class="mom-field"><label>Topics</label>';
	ministry_desk_chips( 'topics', $post ? (array) get_post_meta( $id, '_mom_topics', true ) : array() );
	echo '</div><div class="mom-field"><label>Provinces</label>';
	ministry_desk_chips( 'provinces', $post ? (array) get_post_meta( $id, '_mom_provinces', true ) : array() );
	echo '</div><label class="mom-toggle"><input type="checkbox" name="correction" value="1" ' . checked( $post && '1' === get_post_meta( $id, '_mom_correction', true ), true, false ) . '> Mark as a correction</label></section>';
	ministry_desk_languages_card( $id, 'mom-notices' );
	echo '</div></div></form>';
}

require MINISTRY_CONTENT_DIR . 'inc/desk-records.php';
