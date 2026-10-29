<?php
/**
 * Staff desk. Replaces the default WordPress admin with the Ministry screens.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require MINISTRY_CONTENT_DIR . 'inc/desk-caps.php';
require MINISTRY_CONTENT_DIR . 'inc/desk-notices.php';
require MINISTRY_CONTENT_DIR . 'inc/desk-save.php';

add_action( 'init', 'ministry_desk_register', 12 );
add_action( 'admin_menu', 'ministry_desk_menus', 9 );
add_action( 'admin_menu', 'ministry_desk_hide_stock_menus', 999 );
add_action( 'admin_enqueue_scripts', 'ministry_desk_assets' );
add_action( 'admin_body_class', 'ministry_desk_body_class' );
add_action( 'in_admin_header', 'ministry_desk_chrome' );
add_action( 'load-index.php', 'ministry_desk_leave_dashboard' );
add_action( 'load-post.php', 'ministry_desk_redirect_editor' );
add_action( 'load-post-new.php', 'ministry_desk_redirect_editor' );
add_action( 'load-toplevel_page_ministry-settings', 'ministry_desk_redirect_settings' );
add_action( 'admin_init', 'ministry_desk_capture_language' );
add_action( 'admin_post_ministry_desk_save', 'ministry_desk_handle_save' );
add_action( 'login_enqueue_scripts', 'ministry_desk_login_assets' );
add_filter( 'login_body_class', 'ministry_desk_login_body' );
add_action( 'login_header', 'ministry_desk_login_open' );
add_action( 'login_footer', 'ministry_desk_login_close' );
add_filter( 'login_message', 'ministry_desk_login_message' );
add_filter( 'login_site_html_link', 'ministry_desk_login_help_link' );
add_filter( 'gettext', 'ministry_desk_login_labels', 10, 3 );
add_filter( 'login_redirect', 'ministry_desk_login_redirect', 10, 3 );
add_action( 'wp_head', 'ministry_desk_front_variation', 20 );

/**
 * Private service records edited only in the desk.
 */
function ministry_desk_register() {
	register_post_type(
		'mom_service',
		array(
			'labels'       => ministry_labels( 'Service', 'Services' ),
			'public'       => false,
			'show_ui'      => false,
			'show_in_menu' => false,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author' ),
		)
	);
	ministry_desk_sync_roles();
}

/**
 * Desk pages. The stock menu is hidden; this is what the sidebar links to.
 */
function ministry_desk_menus() {
	$cap = 'edit_posts';
	add_menu_page( 'Dashboard', 'Dashboard', $cap, 'mom-desk', 'ministry_desk_router', 'dashicons-admin-home', 2 );
	$pages = array(
		'mom-notices'   => 'Notices & News',
		'mom-documents' => 'Documents',
		'mom-services'  => 'Services',
		'mom-people'    => 'People & Structure',
		'mom-albums'    => 'Photo albums',
		'mom-events'    => 'Events',
		'mom-agencies'  => 'Agencies & Investments',
		'mom-enquiries' => 'Enquiries & Forms',
		'mom-facts'     => 'Website Facts & Settings',
		'mom-pages'     => 'Pages',
	);
	foreach ( $pages as $slug => $label ) {
		add_submenu_page( 'mom-desk', $label, $label, $cap, $slug, 'ministry_desk_router' );
	}
	add_submenu_page( 'mom-desk', 'Users & Roles', 'Users & Roles', 'manage_options', 'mom-users', 'ministry_desk_router' );
}

/**
 * Desk screens use the navy shell. Other admin screens (Wordfence, Plugins) keep the WordPress menu.
 */
function ministry_desk_is_desk_screen() {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	return 0 === strpos( $page, 'mom-' );
}

/**
 * Wordfence dashboard URL when that plugin is loaded, otherwise empty.
 */
function ministry_desk_wordfence_url() {
	if ( ! defined( 'WORDFENCE_VERSION' ) ) {
		return '';
	}
	return admin_url( 'admin.php?page=Wordfence' );
}

/**
 * Hide the WordPress menus the mockups keep off the staff sidebar.
 */
function ministry_desk_hide_stock_menus() {
	if ( ! ministry_desk_can_open() ) {
		return;
	}
	$slugs = array(
		'index.php',
		'edit.php',
		'upload.php',
		'edit.php?post_type=page',
		'edit-comments.php',
		'ministry-settings',
	);
	if ( ! ministry_desk_can( 'users' ) ) {
		$slugs[] = 'themes.php';
		$slugs[] = 'plugins.php';
		$slugs[] = 'users.php';
		$slugs[] = 'tools.php';
		$slugs[] = 'options-general.php';
	}
	foreach ( array( 'mom_news', 'mom_event', 'mom_document', 'mom_department', 'mom_album', 'mom_banner', 'mom_opportunity', 'mom_faq', 'mom_enquiry', 'mom_media' ) as $type ) {
		$slugs[] = 'edit.php?post_type=' . $type;
	}
	foreach ( $slugs as $slug ) {
		remove_menu_page( $slug );
	}
}

/**
 * Styles and scripts for every admin screen, so the shell is consistent.
 */
function ministry_desk_assets() {
	if ( ! is_admin() || ! ministry_desk_can_open() || ! ministry_desk_is_desk_screen() ) {
		return;
	}
	$css = MINISTRY_CONTENT_DIR . 'assets/desk.css';
	$js  = MINISTRY_CONTENT_DIR . 'assets/desk.js';
	wp_enqueue_style( 'ministry-desk-fonts', 'https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap', array(), null );
	wp_enqueue_style( 'ministry-desk', MINISTRY_CONTENT_URL . 'assets/desk.css', array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_style( 'imgareaselect' );
	wp_enqueue_script( 'imgareaselect' );
	wp_enqueue_media();
	wp_enqueue_script( 'ministry-desk', MINISTRY_CONTENT_URL . 'assets/desk.js', array( 'jquery', 'jquery-ui-sortable', 'imgareaselect' ), (string) filemtime( $js ), true );
	wp_localize_script(
		'ministry-desk',
		'ministryDesk',
		array(
			'ajax'      => admin_url( 'admin-ajax.php' ),
			'cropNonce' => wp_create_nonce( 'ministry_desk_crop' ),
		)
	);
}

add_action( 'wp_ajax_ministry_desk_crop', 'ministry_desk_crop_image' );

/**
 * Crop an uploaded image and return a new media item.
 */
function ministry_desk_crop_image() {
	check_ajax_referer( 'ministry_desk_crop', 'nonce' );
	if ( ! current_user_can( 'upload_files' ) || ! ministry_desk_can_open() ) {
		wp_send_json_error( array( 'message' => 'You cannot crop images.' ), 403 );
	}
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$x  = isset( $_POST['x'] ) ? (int) wp_unslash( $_POST['x'] ) : 0;
	$y  = isset( $_POST['y'] ) ? (int) wp_unslash( $_POST['y'] ) : 0;
	$w  = isset( $_POST['w'] ) ? (int) wp_unslash( $_POST['w'] ) : 0;
	$h  = isset( $_POST['h'] ) ? (int) wp_unslash( $_POST['h'] ) : 0;
	if ( ! $id || $w < 8 || $h < 8 ) {
		wp_send_json_error( array( 'message' => 'Choose an area to crop.' ), 400 );
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$max = 1600;
	$dw  = $w > $max ? $max : $w;
	$dh  = (int) round( $h * ( $dw / $w ) );
	$file = wp_crop_image( $id, $x, $y, $w, $h, $dw, $dh );
	if ( ! $file || is_wp_error( $file ) ) {
		wp_send_json_error( array( 'message' => 'Crop failed.' ), 500 );
	}
	$filetype = wp_check_filetype( basename( $file ), null );
	$parent   = wp_get_post_parent_id( $id );
	$new_id   = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_file_name( pathinfo( $file, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'post_parent'    => $parent ? $parent : 0,
		),
		$file
	);
	if ( ! $new_id || is_wp_error( $new_id ) ) {
		wp_send_json_error( array( 'message' => 'Could not save the cropped image.' ), 500 );
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $new_id, wp_generate_attachment_metadata( $new_id, $file ) );
	$url = wp_get_attachment_url( $new_id );
	wp_send_json_success(
		array(
			'id'       => $new_id,
			'url'      => $url,
			'filename' => basename( $file ),
		)
	);
}

/**
 * @param string $classes Body classes.
 */
function ministry_desk_body_class( $classes ) {
	if ( ! ministry_desk_can_open() || ! ministry_desk_is_desk_screen() ) {
		return $classes;
	}
	return $classes . ' mom-desk-ui';
}

/**
 * Send the default dashboard to the staff desk.
 */
function ministry_desk_leave_dashboard() {
	if ( ! ministry_desk_can_open() ) {
		return;
	}
	wp_safe_redirect( admin_url( 'admin.php?page=mom-desk' ) );
	exit;
}

/**
 * Open ministry records in the desk editors.
 */
function ministry_desk_redirect_editor() {
	if ( ! empty( $_GET['classic'] ) ) {
		return;
	}
	$type = '';
	$id   = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	if ( $id ) {
		$type = (string) get_post_type( $id );
	} elseif ( isset( $_GET['post_type'] ) ) {
		$type = sanitize_key( wp_unslash( $_GET['post_type'] ) );
	}
	$map = array(
		'mom_news'        => array( 'mom-notices', 'edit' ),
		'mom_event'       => array( 'mom-events', 'edit' ),
		'mom_document'    => array( 'mom-documents', '' ),
		'mom_album'       => array( 'mom-albums', 'edit' ),
		'mom_faq'         => array( 'mom-services', 'faqs' ),
		'mom_opportunity' => array( 'mom-agencies', 'opportunity' ),
		'mom_service'     => array( 'mom-services', 'edit' ),
		'mom_banner'      => array( 'mom-facts', '' ),
		'mom_enquiry'     => array( 'mom-enquiries', '' ),
	);
	if ( 'mom_department' === $type ) {
		$view = ( $id && '1' === get_post_meta( $id, '_mom_provincial', true ) ) ? 'office' : 'department';
		$map['mom_department'] = array( 'mom-people', $view );
	}
	if ( ! isset( $map[ $type ] ) ) {
		return;
	}
	$args = array( 'page' => $map[ $type ][0] );
	if ( $map[ $type ][1] ) {
		$args['view'] = $map[ $type ][1];
	}
	if ( $id ) {
		$args['id'] = $id;
	}
	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * The old settings screen stays available with ?classic=1.
 */
function ministry_desk_redirect_settings() {
	if ( ! empty( $_GET['classic'] ) || ! ministry_desk_can_open() ) {
		return;
	}
	wp_safe_redirect( admin_url( 'admin.php?page=mom-facts' ) );
	exit;
}

/**
 * Remember the editing language from the top bar.
 */
function ministry_desk_capture_language() {
	if ( ! is_user_logged_in() || ! isset( $_GET['desk_lang'] ) ) {
		return;
	}
	$lang = sanitize_key( wp_unslash( $_GET['desk_lang'] ) );
	if ( ! in_array( $lang, array( 'en', 'sn', 'nd' ), true ) ) {
		return;
	}
	update_user_meta( get_current_user_id(), 'mom_desk_lang', $lang );
	wp_safe_redirect( remove_query_arg( 'desk_lang' ) );
	exit;
}

/**
 * Sidebar and top bar.
 */
function ministry_desk_chrome() {
	if ( ! ministry_desk_can_open() || ! ministry_desk_is_desk_screen() ) {
		return;
	}
	ministry_desk_sidebar();
	ministry_desk_topbar();
}

/**
 * @param string $name Icon name.
 */
function ministry_desk_icon( $name ) {
	$paths = array(
		'grid'      => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
		'megaphone' => '<path d="M4 10v4h3l5 4V6L7 10H4z"/><path d="M16 9a4 4 0 0 1 0 6"/>',
		'doc'       => '<path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v6h6"/>',
		'clock'     => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/>',
		'people'    => '<circle cx="9" cy="8" r="3"/><path d="M3 19c1-3 3.5-4 6-4s5 1 6 4"/><circle cx="17" cy="9" r="2"/><path d="M17 13c2 0 4 1 4 3"/>',
		'camera'    => '<path d="M4 8h3l2-2h6l2 2h3v10H4z"/><circle cx="12" cy="13" r="3"/>',
		'calendar'  => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/>',
		'home'      => '<path d="M4 11l8-7 8 7v9H4z"/><path d="M10 20v-6h4v6"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 7l8 6 8-6"/>',
		'spark'     => '<path d="M12 2l1.5 6.5L20 10l-6.5 1.5L12 18l-1.5-6.5L4 10l6.5-1.5z"/>',
		'pages'     => '<path d="M6 3h9l4 4v14H6z"/><path d="M15 3v5h5"/>',
		'lock'      => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
		'eye'       => '<path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="2.5"/>',
		'search'    => '<circle cx="11" cy="11" r="6"/><path d="M16 16l5 5"/>',
		'bell'      => '<path d="M6 16V11a6 6 0 1 1 12 0v5l1 2H5l1-2z"/><path d="M10 19a2 2 0 0 0 4 0"/>',
		'plus'      => '<path d="M12 5v14M5 12h14"/>',
		'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
	);
	$inner = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['doc'];
	return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">' . $inner . '</svg>';
}

/**
 * @param string               $page Page slug.
 * @param array<string, mixed> $args Query args.
 */
function ministry_desk_url( $page, $args = array() ) {
	$args['page'] = $page;
	return add_query_arg( $args, admin_url( 'admin.php' ) );
}

/**
 * @return string
 */
function ministry_desk_page() {
	return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
}

/**
 * @return string
 */
function ministry_desk_view() {
	return isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
}

/**
 * @param WP_User $user User.
 */
function ministry_desk_role_label( $user ) {
	$options = ministry_desk_role_options();
	$role    = ministry_desk_user_role( $user );
	if ( isset( $options[ $role ] ) ) {
		return 'mom_translator' === $role ? 'Translator' : $options[ $role ];
	}
	return 'Content Officer';
}

/**
 * @param WP_User $user User.
 */
function ministry_desk_initials( $user ) {
	$parts = preg_split( '/\s+/', trim( $user->display_name ) );
	$letters = '';
	foreach ( (array) $parts as $part ) {
		if ( '' !== $part ) {
			$letters .= strtoupper( substr( $part, 0, 1 ) );
		}
		if ( strlen( $letters ) >= 2 ) {
			break;
		}
	}
	return $letters ? $letters : 'MM';
}

/**
 * @return bool
 */
function ministry_desk_can_publish() {
	return ministry_desk_can( 'publish' );
}

/**
 * @return string en|sn|nd
 */
function ministry_desk_lang() {
	$lang = (string) get_user_meta( get_current_user_id(), 'mom_desk_lang', true );
	return in_array( $lang, array( 'sn', 'nd' ), true ) ? $lang : 'en';
}

/**
 * @return string
 */
function ministry_desk_lang_label() {
	$labels = array(
		'en' => 'English',
		'sn' => 'ChiShona',
		'nd' => 'Ndebele',
	);
	$lang = ministry_desk_lang();
	return $labels[ $lang ];
}

/**
 * @return array<string, mixed>
 */
function ministry_desk_meta() {
	$stored = get_option( 'mom_desk_meta', array() );
	$base   = array(
		'fax'            => '+263 242 777044',
		'floor'          => '7th Floor, ZIMRE Centre',
		'variation'      => 'a',
		'person_pending' => array(),
		'org'            => array(),
		'log'            => array(),
	);
	return array_merge( $base, is_array( $stored ) ? $stored : array() );
}

/**
 * @param array<string, mixed> $patch Values to merge.
 */
function ministry_desk_update_meta( $patch ) {
	update_option( 'mom_desk_meta', array_merge( ministry_desk_meta(), $patch ) );
}

/**
 * @param string $message What happened, without the person's name.
 */
function ministry_desk_log( $message ) {
	$meta  = ministry_desk_meta();
	$user  = wp_get_current_user();
	$entry = array(
		't' => time(),
		'm' => $user->display_name . ' ' . $message,
	);
	$log   = isset( $meta['log'] ) && is_array( $meta['log'] ) ? $meta['log'] : array();
	$log[] = $entry;
	$meta['log'] = array_slice( $log, -40 );
	update_option( 'mom_desk_meta', $meta );
}

/**
 * @param array<string, mixed> $patch Setting keys.
 */
function ministry_desk_patch_settings( $patch ) {
	$current = ministry_settings();
	foreach ( $patch as $key => $value ) {
		$current[ $key ] = $value;
	}
	unset( $current['logoUrl'] );
	update_option( 'mom_site_settings', $current );
}

/**
 * @param string               $type Post type.
 * @param array<string, mixed> $args Extra query args.
 * @return WP_Post[]
 */
function ministry_desk_posts( $type, $args = array() ) {
	$query = array_merge(
		array(
			'post_type'      => $type,
			'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
			'posts_per_page' => 100,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		),
		$args
	);
	return get_posts( $query );
}

/**
 * @return WP_Post[]
 */
function ministry_desk_pending() {
	return ministry_desk_posts(
		array( 'mom_news', 'mom_event', 'mom_document', 'mom_department', 'mom_album', 'mom_faq', 'mom_service', 'mom_opportunity', 'page' ),
		array(
			'post_status'    => 'pending',
			'posts_per_page' => 20,
		)
	);
}

/**
 * @param int $post_id Post ID.
 * @return array<string, array<string, mixed>>
 */
function ministry_desk_i18n( $post_id ) {
	$stored = get_post_meta( $post_id, '_mom_i18n', true );
	$empty  = array(
		'title'   => '',
		'excerpt' => '',
		'content' => '',
		'fields'  => array(),
	);
	$out = array(
		'sn' => $empty,
		'nd' => $empty,
	);
	if ( ! is_array( $stored ) ) {
		return $out;
	}
	foreach ( array( 'sn', 'nd' ) as $lang ) {
		$row = isset( $stored[ $lang ] ) && is_array( $stored[ $lang ] ) ? $stored[ $lang ] : array();
		$out[ $lang ] = array(
			'title'   => isset( $row['title'] ) ? (string) $row['title'] : '',
			'excerpt' => isset( $row['excerpt'] ) ? (string) $row['excerpt'] : '',
			'content' => isset( $row['content'] ) ? (string) $row['content'] : '',
			'fields'  => isset( $row['fields'] ) && is_array( $row['fields'] ) ? $row['fields'] : array(),
		);
	}
	return $out;
}

/**
 * @param WP_Post $post Post.
 */
function ministry_desk_kind( $post ) {
	$kind = (string) get_post_meta( $post->ID, '_mom_kind', true );
	$allowed = array( 'notice', 'press', 'speech', 'tender', 'vacancy', 'news' );
	return in_array( $kind, $allowed, true ) ? $kind : 'news';
}

/**
 * @param string $kind Kind key.
 */
function ministry_desk_kind_label( $kind ) {
	$labels = array(
		'notice'  => 'Public notice',
		'press'   => 'Press statement',
		'speech'  => 'Speech',
		'tender'  => 'Tender',
		'vacancy' => 'Vacancy',
		'news'    => 'News',
	);
	return isset( $labels[ $kind ] ) ? $labels[ $kind ] : 'News';
}

/**
 * @param WP_Post $post Post.
 */
function ministry_desk_status_label( $post ) {
	$kind     = ministry_desk_kind( $post );
	$deadline = (string) get_post_meta( $post->ID, '_mom_deadline', true );
	if ( 'tender' === $kind && $deadline && strtotime( $deadline ) < time() && 'publish' === $post->post_status ) {
		return array( 'Closed', 'slate' );
	}
	if ( 'future' === $post->post_status ) {
		return array( 'Scheduled · ' . ministry_desk_date( $post->post_date ), 'blue' );
	}
	$map = array(
		'publish' => array( 'Published', 'green' ),
		'pending' => array( 'Pending review', 'gold' ),
		'draft'   => array( 'Draft', 'gold' ),
		'private' => array( 'Private', 'slate' ),
	);
	return isset( $map[ $post->post_status ] ) ? $map[ $post->post_status ] : array( ucfirst( $post->post_status ), 'slate' );
}

/**
 * @param string $mysql MySQL datetime.
 */
function ministry_desk_date( $mysql ) {
	$stamp = strtotime( (string) $mysql );
	if ( ! $stamp ) {
		return '—';
	}
	return wp_date( 'j M Y', $stamp );
}

/**
 * Short time for the recent-changes list.
 *
 * @param string $mysql MySQL datetime.
 */
function ministry_desk_when( $mysql ) {
	$stamp = strtotime( (string) $mysql );
	if ( ! $stamp ) {
		return '—';
	}
	$day = wp_date( 'Y-m-d', $stamp );
	if ( $day === wp_date( 'Y-m-d' ) ) {
		return wp_date( 'H:i', $stamp );
	}
	if ( $day === wp_date( 'Y-m-d', strtotime( '-1 day' ) ) ) {
		return 'Yesterday';
	}
	return wp_date( 'j M', $stamp );
}

/**
 * @return string
 */
function ministry_desk_next_notice_number() {
	$year  = wp_date( 'Y' );
	$posts = get_posts(
		array(
			'post_type'      => 'mom_news',
			'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_mom_kind',
			'meta_value'     => 'notice',
		)
	);
	$max = 0;
	foreach ( $posts as $id ) {
		$number = (string) get_post_meta( $id, '_mom_number', true );
		if ( preg_match( '/^(\d+)\/' . $year . '$/', $number, $match ) ) {
			$max = max( $max, (int) $match[1] );
		}
	}
	return sprintf( '%02d/%s', $max + 1, $year );
}

/**
 * @param string $before Previous text.
 * @param string $after  Proposed text.
 */
function ministry_desk_word_diff( $before, $after ) {
	$old    = '' === trim( $before ) ? array() : preg_split( '/\s+/', trim( $before ) );
	$new    = '' === trim( $after ) ? array() : preg_split( '/\s+/', trim( $after ) );
	$prefix = 0;
	$limit  = min( count( $old ), count( $new ) );
	while ( $prefix < $limit && $old[ $prefix ] === $new[ $prefix ] ) {
		$prefix++;
	}
	$suffix = 0;
	while ( $suffix < count( $old ) - $prefix && $suffix < count( $new ) - $prefix && $old[ count( $old ) - 1 - $suffix ] === $new[ count( $new ) - 1 - $suffix ] ) {
		$suffix++;
	}
	$html = esc_html( implode( ' ', array_slice( $old, 0, $prefix ) ) );
	$del  = array_slice( $old, $prefix, count( $old ) - $prefix - $suffix );
	$ins  = array_slice( $new, $prefix, count( $new ) - $prefix - $suffix );
	if ( $del ) {
		$html .= ' <del>' . esc_html( implode( ' ', $del ) ) . '</del>';
	}
	if ( $ins ) {
		$html .= ' <ins>' . esc_html( implode( ' ', $ins ) ) . '</ins>';
	}
	if ( $suffix ) {
		$html .= ' ' . esc_html( implode( ' ', array_slice( $old, count( $old ) - $suffix ) ) );
	}
	return trim( $html );
}

/**
 * @param array<string, mixed> $leader Leader row.
 */
function ministry_desk_leader_photo( $leader ) {
	if ( ! empty( $leader['imageUrl'] ) ) {
		return (string) $leader['imageUrl'];
	}
	$name = strtolower( isset( $leader['name'] ) ? (string) $leader['name'] : '' );
	$map  = array(
		'kambamura'   => 'kambamura.jpg',
		'fred moyo'   => 'moyo.jpg',
		'makwiranzou' => 'makwiranzou.jpg',
		'wushe'       => 'wushe.jpg',
	);
	foreach ( $map as $needle => $file ) {
		if ( str_contains( $name, $needle ) ) {
			return get_template_directory_uri() . '/site/images/leaders/' . $file;
		}
	}
	return '';
}

/**
 * @param WP_Post $post Department.
 */
function ministry_desk_department_group( $post ) {
	$set = (string) get_post_meta( $post->ID, '_mom_group', true );
	if ( in_array( $set, array( '1', '2', '3', '4' ), true ) ) {
		return $set;
	}
	if ( '1' === get_post_meta( $post->ID, '_mom_provincial', true ) ) {
		return '4';
	}
	$slug = $post->post_name;
	foreach ( array( 'geological', 'metallurgy', 'mining-engineering' ) as $bit ) {
		if ( str_contains( $slug, $bit ) ) {
			return '3';
		}
	}
	foreach ( array( 'energy', 'non-energy', 'value', 'beneficiation' ) as $bit ) {
		if ( str_contains( $slug, $bit ) ) {
			return '2';
		}
	}
	return '1';
}

/**
 * Route the current desk page.
 */
function ministry_desk_router() {
	if ( ! ministry_desk_can_open() ) {
		wp_die( esc_html__( 'You cannot open the staff desk.', 'ministry-content' ) );
	}
	if ( ! ministry_desk_can_page( ministry_desk_page(), ministry_desk_view() ) ) {
		wp_die( esc_html__( 'Your role cannot open this screen.', 'ministry-content' ) );
	}
	if ( isset( $_GET['saved'] ) ) {
		echo '<p class="mom-flash" role="status">Saved.</p>';
	}
	$page = ministry_desk_page();
	$view = ministry_desk_view();
	if ( 'mom-desk' === $page && 'review' === $view ) {
		ministry_desk_screen_review();
		return;
	}
	if ( 'mom-notices' === $page && 'edit' === $view ) {
		ministry_desk_screen_notice();
		return;
	}
	if ( 'mom-notices' === $page && 'news' === $view ) {
		ministry_desk_screen_news();
		return;
	}
	if ( 'mom-notices' === $page && 'translate' === $view ) {
		ministry_desk_screen_translate();
		return;
	}
	if ( 'mom-services' === $page && 'edit' === $view ) {
		ministry_desk_screen_service();
		return;
	}
	if ( 'mom-services' === $page && 'faqs' === $view ) {
		ministry_desk_screen_faqs();
		return;
	}
	if ( 'mom-services' === $page && 'translate' === $view ) {
		ministry_desk_screen_translate();
		return;
	}
	if ( 'mom-events' === $page && 'edit' === $view ) {
		ministry_desk_screen_event();
		return;
	}
	if ( 'mom-albums' === $page && 'edit' === $view ) {
		ministry_desk_screen_album();
		return;
	}
	if ( 'mom-people' === $page && 'person' === $view ) {
		ministry_desk_screen_person();
		return;
	}
	if ( 'mom-people' === $page && 'leadership' === $view ) {
		ministry_desk_screen_leadership();
		return;
	}
	if ( 'mom-people' === $page && 'structure' === $view ) {
		ministry_desk_screen_structure();
		return;
	}
	if ( 'mom-people' === $page && 'department' === $view ) {
		ministry_desk_screen_department();
		return;
	}
	if ( 'mom-people' === $page && 'office' === $view ) {
		ministry_desk_screen_office();
		return;
	}
	if ( 'mom-agencies' === $page && 'edit' === $view ) {
		ministry_desk_screen_agency();
		return;
	}
	if ( 'mom-agencies' === $page && 'opportunity' === $view ) {
		ministry_desk_screen_opportunity();
		return;
	}
	if ( 'mom-pages' === $page && 'edit' === $view ) {
		ministry_desk_screen_page();
		return;
	}
	$map = array(
		'mom-desk'      => 'ministry_desk_screen_dashboard',
		'mom-notices'   => 'ministry_desk_screen_notices',
		'mom-documents' => 'ministry_desk_screen_documents',
		'mom-services'  => 'ministry_desk_screen_services',
		'mom-people'    => 'ministry_desk_screen_people',
		'mom-albums'    => 'ministry_desk_screen_albums',
		'mom-events'    => 'ministry_desk_screen_events',
		'mom-agencies'  => 'ministry_desk_screen_agencies',
		'mom-enquiries' => 'ministry_desk_screen_enquiries',
		'mom-facts'     => 'ministry_desk_screen_facts',
		'mom-pages'     => 'ministry_desk_screen_pages',
		'mom-users'     => 'ministry_desk_screen_users',
	);
	if ( isset( $map[ $page ] ) ) {
		call_user_func( $map[ $page ] );
	}
}


/**
 * Variation B shifts the public navy toward black. Content stays the same.
 */
function ministry_desk_front_variation() {
	if ( is_admin() || 'b' !== ministry_desk_meta()['variation'] ) {
		return;
	}
	echo '<style>:root{--green:#161616;--deep:#0c0c0c;--night:#000}</style>' . "\n";
}

/**
 * @param string[] $classes Login body classes.
 * @return string[]
 */
function ministry_desk_login_body( $classes ) {
	$classes[] = 'mom-login-page';
	return $classes;
}

/**
 * Login styles.
 */
function ministry_desk_login_assets() {
	$css = MINISTRY_CONTENT_DIR . 'assets/desk.css';
	$js  = MINISTRY_CONTENT_DIR . 'assets/desk.js';
	wp_enqueue_style( 'ministry-desk-fonts', 'https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap', array(), null );
	wp_enqueue_style( 'ministry-desk', MINISTRY_CONTENT_URL . 'assets/desk.css', array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'ministry-desk', MINISTRY_CONTENT_URL . 'assets/desk.js', array( 'jquery' ), (string) filemtime( $js ), true );
}

/**
 * Open the split sign-in layout.
 */
function ministry_desk_login_open() {
	$image = get_template_directory_uri() . '/site/images/haul-truck.jpg';
	echo '<div class="mom-login">';
	echo '<aside class="mom-login-hero" style="background-image:url(' . esc_url( $image ) . ')">';
	echo '<div class="mom-login-caption"><i></i><h1>Website staff area</h1><p>For authorised Ministry staff only. Activity is logged.</p></div>';
	echo '</aside><div class="mom-login-pane">';
}

/**
 * Close the split sign-in layout.
 */
function ministry_desk_login_close() {
	echo '</div></div>';
}

/**
 * Crest and heading above the WordPress form.
 *
 * @param string $message Existing message.
 */
function ministry_desk_login_message( $message ) {
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login';
	if ( 'login' !== $action && '' !== $action ) {
		return $message;
	}
	$logo = get_template_directory_uri() . '/site/images/logo-full.png';
	$html = '<div class="mom-login-brand"><img src="' . esc_url( $logo ) . '" alt=""><div><span>Republic of Zimbabwe</span><b>Ministry of Mines and Mining Development</b></div></div>';
	$html .= '<h2 class="mom-login-title">Sign in</h2>';
	return $html . $message;
}

/**
 * @param string $link Existing go-to-site link.
 */
function ministry_desk_login_help_link( $link ) {
	$email = 'mmmd@mines.gov.zw';
	if ( function_exists( 'ministry_settings' ) ) {
		$settings = ministry_settings();
		if ( ! empty( $settings['headOfficeEmail'] ) ) {
			$email = (string) $settings['headOfficeEmail'];
		}
	}
	return '<a href="mailto:' . esc_attr( $email ) . '">Need access? Contact ICT</a>';
}

/**
 * @param string $translated Translated text.
 * @param string $text       Original text.
 * @param string $domain     Text domain.
 */
function ministry_desk_login_labels( $translated, $text, $domain ) {
	if ( ! isset( $GLOBALS['pagenow'] ) || 'wp-login.php' !== $GLOBALS['pagenow'] ) {
		return $translated;
	}
	$map = array(
		'Username or Email Address' => 'Work email',
		'Log In'                    => 'Sign in',
		'Lost your password?'       => 'Forgot password?',
	);
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}

/**
 * Land staff on the desk.
 *
 * @param string           $redirect  Redirect URL.
 * @param string           $requested Requested URL.
 * @param WP_User|WP_Error $user      User.
 */
function ministry_desk_login_redirect( $redirect, $requested, $user ) {
	if ( $user instanceof WP_User && user_can( $user, 'edit_posts' ) ) {
		return admin_url( 'admin.php?page=mom-desk' );
	}
	return $redirect;
}
