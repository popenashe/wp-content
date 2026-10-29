<?php
/**
 * Desk permissions: preset roles and per-user custom ticks.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All desk permissions. `users` cannot be granted on a Custom account.
 *
 * @return array<string, array{label: string, group: string, admin_only?: bool}>
 */
function ministry_desk_cap_catalog() {
	return array(
		'notices'        => array( 'label' => 'Notices & News', 'group' => 'areas' ),
		'documents'      => array( 'label' => 'Documents', 'group' => 'areas' ),
		'events'         => array( 'label' => 'Events', 'group' => 'areas' ),
		'services'       => array( 'label' => 'Services & FAQs', 'group' => 'areas' ),
		'albums'         => array( 'label' => 'Photo albums', 'group' => 'areas' ),
		'agencies'       => array( 'label' => 'Agencies & Investments', 'group' => 'areas' ),
		'enquiries'      => array( 'label' => 'Enquiries & Forms', 'group' => 'areas' ),
		'pages'          => array( 'label' => 'Pages', 'group' => 'areas' ),
		'people'         => array( 'label' => 'People, departments, offices, and structure', 'group' => 'areas' ),
		'facts'          => array( 'label' => 'Website facts and settings', 'group' => 'areas' ),
		'publish'        => array( 'label' => 'Publish and approve', 'group' => 'actions' ),
		'notice_numbers' => array( 'label' => 'Change notice numbers', 'group' => 'actions' ),
		'translate'      => array( 'label' => 'Translate', 'group' => 'actions' ),
		'users'          => array( 'label' => 'Add users, plugins, themes', 'group' => 'admin', 'admin_only' => true ),
	);
}

/**
 * Preset ticks for a WordPress role key.
 *
 * @param string $role author|mom_translator|editor|administrator|mom_custom.
 * @return string[]
 */
function ministry_desk_role_preset( $role ) {
	$officer = array( 'notices', 'documents', 'events', 'services', 'albums', 'agencies', 'enquiries', 'pages' );
	if ( 'author' === $role ) {
		return $officer;
	}
	if ( 'mom_translator' === $role ) {
		return array( 'translate' );
	}
	if ( 'editor' === $role ) {
		return array_merge( $officer, array( 'people', 'facts', 'publish', 'notice_numbers', 'translate' ) );
	}
	if ( 'administrator' === $role ) {
		return array_keys( ministry_desk_cap_catalog() );
	}
	return array();
}

/**
 * Desk role keys used in the add/edit form.
 *
 * @return array<string, string>
 */
function ministry_desk_role_options() {
	return array(
		'author'         => 'Content Officer',
		'mom_translator' => 'Translator (Shona / Ndebele)',
		'editor'         => 'Communications Director',
		'mom_custom'     => 'Custom',
		'administrator'  => 'Administrator',
	);
}

/**
 * Primary desk role for a user.
 *
 * @param WP_User|null $user User.
 */
function ministry_desk_user_role( $user = null ) {
	if ( ! $user instanceof WP_User ) {
		$user = wp_get_current_user();
	}
	$roles = (array) $user->roles;
	foreach ( array( 'administrator', 'editor', 'mom_custom', 'mom_translator', 'author' ) as $role ) {
		if ( in_array( $role, $roles, true ) ) {
			return $role;
		}
	}
	return $roles ? (string) $roles[0] : 'author';
}

/**
 * Permission keys this user currently has.
 *
 * @param WP_User|null $user User.
 * @return string[]
 */
function ministry_desk_user_caps( $user = null ) {
	if ( ! $user instanceof WP_User ) {
		$user = wp_get_current_user();
	}
	if ( ! $user->ID ) {
		return array();
	}
	$role = ministry_desk_user_role( $user );
	if ( 'administrator' === $role ) {
		return array_keys( ministry_desk_cap_catalog() );
	}
	if ( 'mom_custom' === $role ) {
		$stored = get_user_meta( $user->ID, 'mom_desk_caps', true );
		$caps   = is_array( $stored ) ? array_values( array_map( 'strval', $stored ) ) : array();
		return array_values( array_intersect( $caps, array_keys( ministry_desk_cap_catalog() ) ) );
	}
	return ministry_desk_role_preset( $role );
}

/**
 * @param string       $cap  Catalog key.
 * @param WP_User|null $user User.
 */
function ministry_desk_can( $cap, $user = null ) {
	return in_array( $cap, ministry_desk_user_caps( $user ), true );
}

/**
 * Can open the staff desk at all.
 *
 * @param WP_User|null $user User.
 */
function ministry_desk_can_open( $user = null ) {
	if ( ! $user instanceof WP_User ) {
		$user = wp_get_current_user();
	}
	if ( user_can( $user, 'manage_options' ) ) {
		return true;
	}
	$role = ministry_desk_user_role( $user );
	return isset( ministry_desk_role_options()[ $role ] );
}

/**
 * Desk page slug => permission key. Empty means dashboard (any desk user).
 *
 * @param string $page Page slug.
 */
function ministry_desk_page_cap( $page ) {
	$map = array(
		'mom-notices'   => 'notices',
		'mom-documents' => 'documents',
		'mom-services'  => 'services',
		'mom-people'    => 'people',
		'mom-albums'    => 'albums',
		'mom-events'    => 'events',
		'mom-agencies'  => 'agencies',
		'mom-enquiries' => 'enquiries',
		'mom-facts'     => 'facts',
		'mom-pages'     => 'pages',
		'mom-users'     => 'users',
	);
	return isset( $map[ $page ] ) ? $map[ $page ] : '';
}

/**
 * Save form key => permission key.
 *
 * @param string $form Form key.
 */
function ministry_desk_form_cap( $form ) {
	$map = array(
		'notice'            => 'notices',
		'news'              => 'notices',
		'document'          => 'documents',
		'service'           => 'services',
		'faq'               => 'services',
		'event'             => 'events',
		'album'             => 'albums',
		'person'            => 'people',
		'leadership'        => 'people',
		'people-order'      => 'people',
		'departments-order' => 'people',
		'offices-order'     => 'people',
		'structure'         => 'people',
		'department'        => 'people',
		'office'            => 'people',
		'entity'            => 'agencies',
		'opportunity'       => 'agencies',
		'facts'             => 'facts',
		'enquiry'           => 'enquiries',
		'export'            => 'enquiries',
		'user'              => 'users',
		'page'              => 'pages',
		'translate'         => 'translate',
		'review'            => 'publish',
		'bulk'              => 'notices',
	);
	return isset( $map[ $form ] ) ? $map[ $form ] : '';
}

/**
 * Permission for a WordPress record type.
 *
 * @param string $type Post type.
 */
function ministry_desk_type_cap( $type ) {
	$map = array(
		'mom_news'        => 'notices',
		'mom_document'    => 'documents',
		'mom_event'       => 'events',
		'mom_service'     => 'services',
		'mom_faq'         => 'services',
		'mom_album'       => 'albums',
		'mom_department'  => 'people',
		'mom_opportunity' => 'agencies',
		'mom_enquiry'     => 'enquiries',
		'mom_banner'      => 'facts',
		'page'            => 'pages',
	);
	return isset( $map[ $type ] ) ? $map[ $type ] : '';
}

/**
 * Whether this user may open a desk screen.
 *
 * @param string $page Page slug.
 * @param string $view Current view.
 */
function ministry_desk_can_page( $page, $view = '' ) {
	if ( 'mom-desk' === $page ) {
		if ( 'review' === $view ) {
			return ministry_desk_can( 'publish' );
		}
		return ministry_desk_can_open();
	}
	$cap = ministry_desk_page_cap( $page );
	if ( ! $cap ) {
		return ministry_desk_can_open();
	}
	if ( ministry_desk_can( $cap ) ) {
		return true;
	}
	if ( ministry_desk_can( 'translate' ) && in_array( $cap, array( 'notices', 'services', 'pages' ), true ) ) {
		return in_array( $view, array( '', 'translate' ), true );
	}
	return false;
}

/**
 * Keep WordPress roles in step with the desk matrix.
 */
function ministry_desk_sync_roles() {
	$author = get_role( 'author' );
	if ( $author ) {
		$author->remove_cap( 'publish_posts' );
		$author->add_cap( 'edit_posts' );
		$author->add_cap( 'upload_files' );
		$author->add_cap( 'read' );
	}
	$editor = get_role( 'editor' );
	if ( $editor ) {
		$editor->add_cap( 'publish_posts' );
		$editor->add_cap( 'edit_posts' );
		$editor->add_cap( 'upload_files' );
	}
	$translator = get_role( 'mom_translator' );
	if ( ! $translator ) {
		add_role(
			'mom_translator',
			'Translator',
			array(
				'read'       => true,
				'edit_posts' => true,
			)
		);
	} else {
		$translator->remove_cap( 'publish_posts' );
		$translator->remove_cap( 'upload_files' );
		$translator->add_cap( 'read' );
		$translator->add_cap( 'edit_posts' );
	}
	if ( ! get_role( 'mom_custom' ) ) {
		add_role(
			'mom_custom',
			'Custom',
			array(
				'read'         => true,
				'edit_posts'   => true,
				'upload_files' => true,
			)
		);
	}
}

/**
 * Store custom ticks and matching WordPress caps on one user.
 *
 * @param int      $user_id User ID.
 * @param string[] $caps    Catalog keys.
 */
function ministry_desk_store_custom_caps( $user_id, $caps ) {
	$allowed = array();
	foreach ( ministry_desk_cap_catalog() as $key => $row ) {
		if ( ! empty( $row['admin_only'] ) ) {
			continue;
		}
		if ( in_array( $key, $caps, true ) ) {
			$allowed[] = $key;
		}
	}
	update_user_meta( $user_id, 'mom_desk_caps', $allowed );
	$user = new WP_User( $user_id );
	if ( in_array( 'publish', $allowed, true ) ) {
		$user->add_cap( 'publish_posts' );
	} else {
		$user->remove_cap( 'publish_posts' );
	}
	$upload_areas = array( 'notices', 'documents', 'events', 'services', 'albums', 'agencies', 'pages', 'people' );
	if ( array_intersect( $allowed, $upload_areas ) ) {
		$user->add_cap( 'upload_files' );
	} else {
		$user->remove_cap( 'upload_files' );
	}
}

/**
 * Posted custom ticks, minus administrator-only keys.
 *
 * @return string[]
 */
function ministry_desk_posted_caps() {
	$raw = isset( $_POST['caps'] ) && is_array( $_POST['caps'] ) ? wp_unslash( $_POST['caps'] ) : array();
	$out = array();
	foreach ( $raw as $cap ) {
		$cap = sanitize_key( $cap );
		if ( $cap ) {
			$out[] = $cap;
		}
	}
	return $out;
}
