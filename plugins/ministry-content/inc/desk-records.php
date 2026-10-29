<?php
/**
 * People, agencies, pages, facts, and other Desk screens.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Documents list with the add panel beside it.
 */
function ministry_desk_screen_documents() {
	$id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$post  = $id ? get_post( $id ) : null;
	$state = isset( $_GET['state'] ) ? sanitize_key( wp_unslash( $_GET['state'] ) ) : '';
	$posts = ministry_desk_posts( 'mom_document', array( 'orderby' => 'title', 'order' => 'ASC' ) );
	$counts = array( 'all' => count( $posts ), 'publish' => 0, 'draft' => 0, 'pending' => 0, 'missing' => 0 );
	foreach ( $posts as $item ) {
		if ( isset( $counts[ $item->post_status ] ) ) {
			$counts[ $item->post_status ]++;
		}
		if ( ! ministry_document_url( $item->ID ) ) {
			$counts['missing']++;
		}
	}
	ministry_desk_head( 'Documents', 'Documents', 'Every file on the website lives here. Pages link to documents — they never upload their own copies.' );
	echo '<div class="mom-pills">';
	foreach ( array( '' => array( 'All', $counts['all'] ), 'publish' => array( 'Published', $counts['publish'] ), 'draft' => array( 'Drafts', $counts['draft'] ), 'pending' => array( 'Awaiting review', $counts['pending'] ), 'missing' => array( 'Awaiting file', $counts['missing'] ) ) as $key => $pill ) {
		echo '<a class="mom-pill' . ( $state === $key ? ' is-on' : '' ) . '" href="' . esc_url( ministry_desk_url( 'mom-documents', $key ? array( 'state' => $key ) : array() ) ) . '">' . esc_html( $pill[0] . ' (' . $pill[1] . ')' ) . '</a>';
	}
	echo '</div><div class="mom-split"><div class="mom-table-card"><table class="mom-table"><thead><tr><th>Title</th><th>Category</th><th>File</th><th>Status</th><th>Date</th></tr></thead><tbody>';
	foreach ( $posts as $item ) {
		$missing = ! ministry_document_url( $item->ID );
		if ( 'missing' === $state && ! $missing ) {
			continue;
		}
		if ( $state && 'missing' !== $state && $item->post_status !== $state ) {
			continue;
		}
		$file = (int) get_post_meta( $item->ID, '_mom_file_id', true );
		$status = $missing ? array( 'Awaiting file', 'red' ) : ministry_desk_status_label( $item );
		echo '<tr><td><a class="title" href="' . esc_url( ministry_desk_url( 'mom-documents', array( 'id' => $item->ID ) ) ) . '">' . esc_html( $item->post_title ) . '</a>';
		echo '<div class="mom-row-links"><a href="' . esc_url( ministry_desk_url( 'mom-documents', array( 'id' => $item->ID ) ) ) . '">Edit</a> · <a href="' . esc_url( ministry_desk_preview_url( $item ) ) . '" target="_blank" rel="noopener">Preview</a>';
		ministry_desk_delete_link( $item->ID, ministry_desk_url( 'mom-documents' ) );
		echo '</div>';
		if ( '1' === get_post_meta( $item->ID, '_mom_draft_label', true ) ) {
			echo '<div class="mom-home-flag">Shown publicly as “Draft — subject to change”</div>';
		}
		echo '</td><td>' . esc_html( ministry_document_category_label( (string) get_post_meta( $item->ID, '_mom_category', true ) ) ) . '</td>';
		echo '<td>' . esc_html( $file ? strtoupper( (string) pathinfo( (string) get_attached_file( $file ), PATHINFO_EXTENSION ) ) : '—' ) . '</td><td>';
		ministry_desk_badge( $status[0], $status[1] );
		echo '</td><td>' . esc_html( ministry_desk_date( $item->post_date ) ) . '</td></tr>';
	}
	echo '</tbody></table></div><div id="add">';
	ministry_desk_form_open( 'document' );
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $id ) . '">';
	echo '<section class="mom-side-card"><h2>' . esc_html( $post ? 'Edit document' : 'Add document' ) . '</h2>';
	echo '<div class="mom-drop"><div>Drop a file or <button type="button" class="mom-btn mom-btn-small mom-pick" data-target="mom-doc-file">browse</button><span>PDF, DOCX, XLSX or JPG · up to 50 MB</span></div></div>';
	$file = $post ? (int) get_post_meta( $id, '_mom_file_id', true ) : 0;
	echo '<input type="hidden" id="mom-doc-file" name="file_id" value="' . esc_attr( (string) $file ) . '"><p class="mom-file-name">' . esc_html( $file ? basename( (string) get_attached_file( $file ) ) : '' ) . '</p>';
	ministry_desk_field( 'Title', 'post_title', $post ? $post->post_title : '', array( 'required' => true ) );
	$cats = array( 'legislation' => 'Mines Act', 'policies' => 'Policy documents', 'guidelines' => 'Regulations', 'publications' => 'Public notices', 'forms' => 'Staff forms', 'reports' => 'Reports' );
	ministry_desk_field( 'Category', 'category', $post ? (string) get_post_meta( $id, '_mom_category', true ) : 'guidelines', array( 'type' => 'select', 'required' => true, 'options' => $cats ) );
	ministry_desk_field( 'Version / date', 'version', $post ? $post->post_excerpt : '' );
	echo '<label class="mom-toggle"><input type="checkbox" name="is_draft" value="1" ' . checked( $post && '1' === get_post_meta( $id, '_mom_draft_label', true ), true, false ) . '> This is a draft — label it “Draft — subject to change”</label>';
	echo '<p class="mom-actions" style="margin-top:12px">' . ( $post ? ministry_desk_preview_action( ministry_desk_preview_url( $post ) ) : '' ) . '<button class="mom-btn mom-save-draft" name="intent" value="draft">Save draft</button><button class="mom-btn mom-btn-primary" name="intent" value="' . ( ministry_desk_can_publish() ? 'publish' : 'pending' ) . '">' . ( ministry_desk_can_publish() ? 'Publish' : 'Submit for review' ) . '</button>';
	ministry_desk_delete_button( $post );
	echo '</p>';
	echo '<p class="mom-hint">Old versions stay downloadable but point people to the new one.</p></section></form></div></div>';
}

/**
 * Services list.
 */
function ministry_desk_screen_services() {
	if ( 'opportunities' === ministry_desk_view() ) {
		ministry_desk_screen_agencies();
		return;
	}
	$posts = ministry_desk_posts( 'mom_service' );
	ministry_desk_head( 'Services', 'Services', 'What the Ministry does for the public: licences, permits, and where to apply.', '<a class="mom-btn mom-btn-primary" href="' . esc_url( ministry_desk_url( 'mom-services', array( 'view' => 'edit' ) ) ) . '">+ Add service</a>' );
	if ( ! $posts ) {
		echo '<section class="mom-card"><p>No services yet. Add the prospecting licence, claim registration, and the other services from the public site.</p></section>';
		return;
	}
	echo '<div class="mom-table-card"><table class="mom-table"><thead><tr><th>Service</th><th>Category</th><th>Turnaround</th><th>Status</th></tr></thead><tbody>';
	foreach ( $posts as $post ) {
		$data = get_post_meta( $post->ID, '_mom_service', true );
		$data = is_array( $data ) ? $data : array();
		$status = ministry_desk_status_label( $post );
		echo '<tr><td><a class="title" href="' . esc_url( ministry_desk_url( 'mom-services', array( 'view' => 'edit', 'id' => $post->ID ) ) ) . '">' . esc_html( $post->post_title ) . '</a>';
		echo '<div class="mom-row-links"><a href="' . esc_url( ministry_desk_url( 'mom-services', array( 'view' => 'edit', 'id' => $post->ID ) ) ) . '">Edit</a> · <a href="' . esc_url( ministry_desk_preview_url( $post ) ) . '" target="_blank" rel="noopener">Preview</a>';
		ministry_desk_delete_link( $post->ID, ministry_desk_url( 'mom-services' ) );
		echo '</div></td>';
		echo '<td>' . esc_html( isset( $data['category'] ) ? (string) $data['category'] : '' ) . '</td>';
		echo '<td>' . esc_html( trim( ( isset( $data['number'] ) ? $data['number'] : '' ) . ' ' . ( isset( $data['unit'] ) ? $data['unit'] : '' ) ) ) . '</td><td>';
		ministry_desk_badge( $status[0], $status[1] );
		echo '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/**
 * Service editor.
 */
function ministry_desk_screen_service() {
	$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$post = $id ? get_post( $id ) : null;
	$data = $post ? get_post_meta( $id, '_mom_service', true ) : array();
	$data = is_array( $data ) ? $data : array();
	$needs = isset( $data['needs'] ) && is_array( $data['needs'] ) ? $data['needs'] : array( '' );
	$steps = isset( $data['steps'] ) && is_array( $data['steps'] ) ? $data['steps'] : array( '' );
	$fees  = isset( $data['fees'] ) && is_array( $data['fees'] ) ? $data['fees'] : array( array( 'label' => '', 'amount' => '', 'note' => '' ) );
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-services' ) ) . '">Services</a> / Edit service', $post ? $post->post_title : 'New service' );
	ministry_desk_form_open( 'service' );
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $id ) . '">';
	echo '<div class="mom-split"><div class="mom-main-col"><section class="mom-card"><h2>Overview</h2>';
	ministry_desk_field( 'Service name', 'post_title', $post ? $post->post_title : '', array( 'required' => true ) );
	ministry_desk_field( 'What is this service?', 'post_excerpt', $post ? $post->post_excerpt : '', array( 'type' => 'textarea', 'required' => true, 'hint' => 'One or two plain sentences. Shown on the Services card.' ) );
	$icon = isset( $data['icon'] ) ? (string) $data['icon'] : 'doc';
	echo '<div class="mom-field"><label>Icon</label><div class="mom-icons"><input type="hidden" name="icon" value="' . esc_attr( $icon ) . '">';
	foreach ( array( 'doc', 'clock', 'people', 'home', 'mail', 'spark' ) as $name ) {
		echo '<button type="button" class="' . ( $icon === $name ? 'is-on' : '' ) . '" data-icon="' . esc_attr( $name ) . '">' . ministry_desk_icon( $name ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div></div>';
	ministry_desk_field( 'Category', 'category', isset( $data['category'] ) ? (string) $data['category'] : 'Licences & permits' );
	echo '<div class="mom-grid-3">';
	ministry_desk_field( 'Number', 'turn_number', isset( $data['number'] ) ? (string) $data['number'] : '', array( 'required' => true ) );
	ministry_desk_field( 'Unit', 'turn_unit', isset( $data['unit'] ) ? (string) $data['unit'] : 'Same day', array( 'hint' => 'Saved as a number and a unit, so the at-a-glance table stays consistent.' ) );
	echo '<div class="mom-field"><label>Show in “Turnaround at a glance”</label><label class="mom-toggle"><input type="checkbox" name="glance" value="1" ' . checked( ! isset( $data['glance'] ) || '1' === $data['glance'], true, false ) . '> Yes</label></div></div></section>';
	echo '<section class="mom-card" data-repeater><h2>What you need</h2>';
	foreach ( array_values( $needs ) as $index => $need ) {
		echo '<div class="mom-rowline" data-row><span class="grip">⋮⋮</span><input name="needs[' . esc_attr( (string) $index ) . ']" value="' . esc_attr( (string) $need ) . '"><button type="button" data-remove-row aria-label="Remove">&times;</button></div>';
	}
	echo '<button type="button" class="mom-btn mom-btn-small" data-add-row>+ Add requirement</button></section>';
	echo '<section class="mom-card" data-repeater><h2>Steps</h2>';
	foreach ( array_values( $steps ) as $index => $step ) {
		echo '<div class="mom-rowline" data-row><span class="grip">⋮⋮</span><input name="steps[' . esc_attr( (string) $index ) . ']" value="' . esc_attr( (string) $step ) . '"><button type="button" data-remove-row aria-label="Remove">&times;</button></div>';
	}
	echo '<button type="button" class="mom-btn mom-btn-small" data-add-row>+ Add step</button></section>';
	echo '<section class="mom-card" data-repeater><h2>Fees</h2>';
	foreach ( array_values( $fees ) as $index => $fee ) {
		$fee = is_array( $fee ) ? $fee : array();
		echo '<div class="mom-rowline" data-row><span class="grip">⋮⋮</span><input name="fees[' . esc_attr( (string) $index ) . '][label]" value="' . esc_attr( isset( $fee['label'] ) ? (string) $fee['label'] : '' ) . '" placeholder="Fee name"><input name="fees[' . esc_attr( (string) $index ) . '][amount]" value="' . esc_attr( isset( $fee['amount'] ) ? (string) $fee['amount'] : '' ) . '" placeholder="US$"><input name="fees[' . esc_attr( (string) $index ) . '][note]" value="' . esc_attr( isset( $fee['note'] ) ? (string) $fee['note'] : '' ) . '" placeholder="Note"><button type="button" data-remove-row aria-label="Remove">&times;</button></div>';
	}
	echo '<button type="button" class="mom-btn mom-btn-small" data-add-row>+ Add fee line</button>';
	ministry_desk_field( 'Source of fees', 'fee_source', isset( $data['fee_source'] ) ? (string) $data['fee_source'] : '' );
	echo '<div class="mom-note mom-note-blue"><p>Fees with no amount are hidden on the website and shown as “Contact your Provincial Mining Office for current fees”.</p></div></section></div>';
	echo '<div class="mom-side-col">';
	ministry_desk_publish_card( $post, 'Changes go to the Communications Director for review.' );
	echo '<section class="mom-side-card"><h2>Where to apply</h2>';
	ministry_desk_field( 'Office', 'office', isset( $data['office'] ) ? (string) $data['office'] : '' );
	ministry_desk_field( 'Responsible department', 'department', isset( $data['department'] ) ? (string) $data['department'] : '' );
	echo '</section>';
	ministry_desk_languages_card( $id, 'mom-services' );
	echo '</div></div></form>';
}

/**
 * FAQ editor. The open question is edited in place.
 */
function ministry_desk_screen_faqs() {
	$id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$topic = isset( $_GET['topic'] ) ? sanitize_text_field( wp_unslash( $_GET['topic'] ) ) : '';
	$posts = ministry_desk_posts( 'mom_faq', array( 'orderby' => 'menu_order', 'order' => 'ASC', 'posts_per_page' => 200 ) );
	$missing = 0;
	foreach ( $posts as $post ) {
		if ( '' === trim( $post->post_content ) ) {
			$missing++;
		}
	}
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-services' ) ) . '">Services</a> / FAQs', 'FAQs', 'Collapse a question to drag others. Each card saves on its own.', '<a class="mom-btn mom-btn-primary" href="' . esc_url( ministry_desk_url( 'mom-services', array( 'view' => 'faqs', 'new' => '1' ) ) ) . '">+ Add question</a>' );
	echo '<div class="mom-split"><div><div class="mom-pills">';
	echo '<a class="mom-pill' . ( '' === $topic ? ' is-on' : '' ) . '" href="' . esc_url( ministry_desk_url( 'mom-services', array( 'view' => 'faqs' ) ) ) . '">All (' . esc_html( (string) count( $posts ) ) . ')</a>';
	echo '<a class="mom-pill' . ( 'missing' === $topic ? ' is-on' : '' ) . '" href="' . esc_url( ministry_desk_url( 'mom-services', array( 'view' => 'faqs', 'topic' => 'missing' ) ) ) . '">Answer missing (' . esc_html( (string) $missing ) . ')</a></div>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mom-faq-order">';
	echo '<input type="hidden" name="action" value="ministry_desk_save"><input type="hidden" name="form" value="faq">';
	echo '<input type="hidden" name="back" value="' . esc_attr( ministry_desk_url( 'mom-services', array( 'view' => 'faqs' ) ) ) . '">';
	wp_nonce_field( 'ministry_desk_save' );
	echo '<input type="hidden" name="order" value="' . esc_attr( implode( ',', wp_list_pluck( $posts, 'ID' ) ) ) . '">';
	echo '<p><button class="mom-btn" name="intent" value="keep">Save order</button></p></form>';
	if ( isset( $_GET['new'] ) ) {
		ministry_desk_form_open( 'faq' );
		echo '<section class="mom-card mom-faq-card is-open"><h2>New question</h2>';
		echo '<input name="post_title" placeholder="New question">';
		ministry_desk_editor( 'post_content', '' );
		ministry_desk_field( 'Topic', 'topic', '' );
		ministry_desk_field( 'Source department', 'department', '' );
		echo '<p class="mom-actions"><button class="mom-btn mom-save-draft" name="intent" value="draft">Save draft</button><button class="mom-btn mom-btn-primary" name="intent" value="keep">Publish</button></p></section></form>';
	}
	echo '<div class="mom-sortable mom-faq-list" data-order-field="order" data-order-form=".mom-faq-order">';
	foreach ( $posts as $post ) {
		if ( 'missing' === $topic && '' !== trim( $post->post_content ) ) {
			continue;
		}
		$open = $id === $post->ID;
		echo '<details class="mom-faq-card" data-id="' . esc_attr( (string) $post->ID ) . '"' . ( $open ? ' open' : '' ) . '>';
		echo '<summary><span class="grip">⋮⋮</span><strong>' . esc_html( $post->post_title ? $post->post_title : 'Untitled question' ) . '</strong>';
		if ( '' === trim( $post->post_content ) ) {
			echo '<span class="mom-badge mom-badge-red">Answer missing</span>';
		}
		echo '<span class="mom-row-links"><a href="' . esc_url( ministry_desk_preview_url( $post ) ) . '" target="_blank" rel="noopener">Preview</a> · ';
		ministry_desk_delete_link( $post->ID, ministry_desk_url( 'mom-services', array( 'view' => 'faqs' ) ) );
		echo '</span></summary>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="ministry_desk_save"><input type="hidden" name="form" value="faq">';
		echo '<input type="hidden" name="back" value="' . esc_attr( ministry_desk_url( 'mom-services', array( 'view' => 'faqs' ) ) ) . '">';
		echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $post->ID ) . '">';
		wp_nonce_field( 'ministry_desk_save' );
		echo '<div class="mom-field"><label>Question</label><input name="post_title" value="' . esc_attr( $post->post_title ) . '"></div>';
		echo '<label class="mom-label">Answer</label>';
		ministry_desk_editor( 'post_content', $post->post_content );
		echo '<div class="mom-grid-2">';
		ministry_desk_field( 'Topic', 'topic', (string) get_post_meta( $post->ID, '_mom_topic', true ) );
		ministry_desk_field( 'Source department', 'department', (string) get_post_meta( $post->ID, '_mom_department', true ) );
		echo '</div><p class="mom-actions"><button class="mom-btn mom-save-draft" name="intent" value="draft">Save draft</button><button class="mom-btn mom-btn-primary" name="intent" value="keep">Publish</button></p></form></details>';
	}
	echo '</div></div><div class="mom-side-col"><section class="mom-side-card"><h2>Before publishing</h2>';
	if ( $missing ) {
		echo '<div class="mom-note mom-note-red"><p><strong>' . esc_html( (string) $missing ) . ' questions have no answer yet.</strong> Unanswered questions are hidden from the public FAQ page.</p></div>';
	} else {
		echo '<p>Every question has an answer.</p>';
	}
	echo '<p class="mom-hint">Collapse a card to see the rest of the list and drag it into a new order, then save order.</p></section></div></div>';
}

/**
 * Side-by-side translation. English stays read-only.
 */
function ministry_desk_screen_translate() {
	$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$lang = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : 'sn';
	if ( ! in_array( $lang, array( 'sn', 'nd' ), true ) ) {
		$lang = 'sn';
	}
	$post = $id ? get_post( $id ) : null;
	if ( ! $post ) {
		ministry_desk_head( '', 'Translate', 'Open a notice, service, or page and choose Add translation.' );
		return;
	}
	$i18n  = ministry_desk_i18n( $id );
	$label = 'sn' === $lang ? 'ChiShona' : 'Ndebele';
	$page  = 'mom_service' === $post->post_type ? 'mom-services' : ( 'mom_news' === $post->post_type ? 'mom-notices' : 'mom-pages' );
	ministry_desk_head( 'Translate', 'Translate: ' . $post->post_title, 'English → ' . $label . '. Only the words need translating.', '<button class="mom-btn" form="mom-desk-form" name="intent" value="draft">Save draft</button><button class="mom-btn mom-btn-primary" form="mom-desk-form">Submit translation</button>' );
	ministry_desk_form_open( 'translate' );
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $id ) . '"><input type="hidden" name="lang" value="' . esc_attr( $lang ) . '">';
	echo '<div class="mom-split"><div><div class="mom-compare"><section class="pane"><h2>English</h2><p class="mom-hint">Original — read only</p>';
	echo '<div class="mom-field"><label>Title</label><input type="text" value="' . esc_attr( $post->post_title ) . '" readonly></div>';
	echo '<div class="mom-field"><label>Summary</label><textarea readonly>' . esc_textarea( $post->post_excerpt ) . '</textarea></div>';
	echo '<div class="mom-field"><label>Body</label><textarea readonly rows="8">' . esc_textarea( wp_strip_all_tags( $post->post_content ) ) . '</textarea></div></section>';
	echo '<section class="pane"><h2>' . esc_html( $label ) . '</h2><p class="mom-hint">Your translation</p>';
	ministry_desk_field( 'Title', 'tr_title', $i18n[ $lang ]['title'] );
	ministry_desk_field( 'Summary', 'tr_excerpt', $i18n[ $lang ]['excerpt'], array( 'type' => 'textarea' ) );
	ministry_desk_field( 'Body', 'tr_content', $i18n[ $lang ]['content'], array( 'type' => 'textarea', 'rows' => 8 ) );
	echo '</section></div>';
	echo '<div class="mom-note mom-note-gold"><p>Shona and Ndebele text must be supplied or checked by a qualified translator.</p></div></div>';
	echo '<div class="mom-side-col"><section class="mom-side-card"><h2>If English changes later</h2><p>The translation is marked out of date on the Dashboard and the changed sentences are highlighted for the translator.</p></section>';
	echo '<section class="mom-side-card"><h2>Glossary</h2><p><strong>Mining claim</strong> — use the agreed ' . esc_html( $label ) . ' term.</p><p><strong>Prospecting licence</strong> — use the agreed term.</p><p class="mom-hint">A shared glossary keeps terms consistent across pages.</p></section></div></div></form>';
}

/**
 * Events list.
 */
function ministry_desk_screen_events() {
	$posts = ministry_desk_posts( 'mom_event', array( 'meta_key' => '_mom_starts_at', 'orderby' => 'meta_value', 'order' => 'ASC' ) );
	ministry_desk_head( 'Events', 'Events', 'Shows, workshops, tours, and stakeholder meetings.', '<a class="mom-btn" href="' . esc_url( ministry_desk_url( 'mom-events', array( 'view' => 'registrations' ) ) ) . '">Registrations</a> <a class="mom-btn mom-btn-primary" href="' . esc_url( ministry_desk_url( 'mom-events', array( 'view' => 'edit' ) ) ) . '">+ Add event</a>' );
	echo '<div class="mom-table-card"><table class="mom-table"><thead><tr><th>Event</th><th>When</th><th>Where</th><th>Type</th><th>Status</th></tr></thead><tbody>';
	foreach ( $posts as $post ) {
		$status = ministry_desk_status_label( $post );
		echo '<tr><td><a class="title" href="' . esc_url( ministry_desk_url( 'mom-events', array( 'view' => 'edit', 'id' => $post->ID ) ) ) . '">' . esc_html( $post->post_title ) . '</a>';
		echo '<div class="mom-row-links"><a href="' . esc_url( ministry_desk_url( 'mom-events', array( 'view' => 'edit', 'id' => $post->ID ) ) ) . '">Edit</a> · <a href="' . esc_url( ministry_desk_preview_url( $post ) ) . '" target="_blank" rel="noopener">Preview</a>';
		ministry_desk_delete_link( $post->ID, ministry_desk_url( 'mom-events' ) );
		echo '</div></td>';
		echo '<td>' . esc_html( ministry_desk_date( (string) get_post_meta( $post->ID, '_mom_starts_at', true ) ) ) . '</td>';
		echo '<td>' . esc_html( (string) get_post_meta( $post->ID, '_mom_location', true ) ) . '</td>';
		echo '<td>' . esc_html( (string) get_post_meta( $post->ID, '_mom_category', true ) ) . '</td><td>';
		ministry_desk_badge( $status[0], $status[1] );
		echo '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/**
 * People who registered for events.
 */
function ministry_desk_screen_registrations() {
	$id       = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
	$all      = array();
	$events   = array();
	foreach ( ministry_desk_posts( 'mom_enquiry', array( 'posts_per_page' => 500 ) ) as $post ) {
		if ( ! ministry_desk_is_registration_type( (string) get_post_meta( $post->ID, '_mom_type', true ) ) ) {
			continue;
		}
		$all[]  = $post;
		$linked = (int) get_post_meta( $post->ID, '_mom_event_id', true );
		$label  = (string) get_post_meta( $post->ID, '_mom_event', true );
		if ( $linked && $label ) {
			$events[ $linked ] = $label;
		}
	}
	$posts = array();
	foreach ( $all as $post ) {
		if ( $event_id && (int) get_post_meta( $post->ID, '_mom_event_id', true ) !== $event_id ) {
			continue;
		}
		$posts[] = $post;
	}
	$export_args = array(
		'action'   => 'ministry_desk_save',
		'form'     => 'export',
		'type'     => 'registration',
		'_wpnonce' => wp_create_nonce( 'ministry_desk_save' ),
	);
	if ( $event_id ) {
		$export_args['event_id'] = $event_id;
	}
	$back = array( 'view' => 'registrations' );
	if ( $event_id ) {
		$back['event_id'] = $event_id;
	}
	ministry_desk_head(
		'<a href="' . esc_url( ministry_desk_url( 'mom-events' ) ) . '">Events</a> / Registrations',
		'Registrations',
		'People who registered for events on the website. Personal details stay in this desk.',
		'<a class="mom-btn" href="' . esc_url( admin_url( 'admin-post.php?' . http_build_query( $export_args ) ) ) . '">Export CSV</a>'
	);
	if ( $events ) {
		echo '<div class="mom-pills">';
		echo '<a class="mom-pill' . ( ! $event_id ? ' is-on' : '' ) . '" href="' . esc_url( ministry_desk_url( 'mom-events', array( 'view' => 'registrations' ) ) ) . '">All (' . esc_html( (string) count( $all ) ) . ')</a>';
		foreach ( $events as $eid => $label ) {
			echo '<a class="mom-pill' . ( $event_id === (int) $eid ? ' is-on' : '' ) . '" href="' . esc_url( ministry_desk_url( 'mom-events', array( 'view' => 'registrations', 'event_id' => $eid ) ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</div>';
	}
	if ( ! $posts ) {
		echo '<section class="mom-card"><p>No registrations yet. When someone uses the Register form, they appear here.</p></section>';
		return;
	}
	echo '<div class="mom-split"><div class="mom-table-card"><table class="mom-table"><thead><tr><th>Name</th><th>Event</th><th>Email</th><th>Phone</th><th>Received</th></tr></thead><tbody>';
	foreach ( $posts as $post ) {
		$detail         = $back;
		$detail['id']   = $post->ID;
		$detail_url     = ministry_desk_url( 'mom-events', $detail );
		echo '<tr><td><a class="title" href="' . esc_url( $detail_url ) . '">' . esc_html( (string) get_post_meta( $post->ID, '_mom_name', true ) ) . '</a>';
		echo '<div class="mom-row-links"><a href="' . esc_url( $detail_url ) . '">View</a>';
		ministry_desk_delete_link( $post->ID, ministry_desk_url( 'mom-events', $back ) );
		echo '</div></td>';
		echo '<td>' . esc_html( (string) get_post_meta( $post->ID, '_mom_event', true ) ) . '</td>';
		echo '<td>' . esc_html( (string) get_post_meta( $post->ID, '_mom_email', true ) ) . '</td>';
		echo '<td>' . esc_html( (string) get_post_meta( $post->ID, '_mom_phone', true ) ) . '</td>';
		echo '<td>' . esc_html( human_time_diff( strtotime( $post->post_date ) ) ) . ' ago</td></tr>';
	}
	echo '</tbody></table></div>';
	$current = $id ? get_post( $id ) : null;
	echo '<div>';
	if ( $current && 'mom_enquiry' === $current->post_type && ministry_desk_is_registration_type( (string) get_post_meta( $current->ID, '_mom_type', true ) ) ) {
		ministry_desk_form_open( 'enquiry' );
		echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $current->ID ) . '">';
		echo '<section class="mom-side-card"><h2>' . esc_html( (string) get_post_meta( $current->ID, '_mom_name', true ) ) . '</h2>';
		echo '<p><strong>Event</strong><br>' . esc_html( (string) get_post_meta( $current->ID, '_mom_event', true ) ) . '</p>';
		echo '<p><strong>Received</strong><br>' . esc_html( ministry_desk_date( $current->post_date ) ) . '</p>';
		echo '<p><strong>Email</strong><br>' . esc_html( (string) get_post_meta( $current->ID, '_mom_email', true ) ) . '</p>';
		echo '<p><strong>Phone</strong><br>' . esc_html( (string) get_post_meta( $current->ID, '_mom_phone', true ) ) . '</p>';
		$org = (string) get_post_meta( $current->ID, '_mom_org', true );
		if ( $org ) {
			echo '<p><strong>Organisation</strong><br>' . esc_html( $org ) . '</p>';
		}
		if ( $current->post_content ) {
			echo '<p><strong>Notes</strong></p><div class="mom-quote">' . esc_html( $current->post_content ) . '</div>';
		}
		ministry_desk_field( 'Internal note', 'note', (string) get_post_meta( $current->ID, '_mom_note', true ), array( 'type' => 'textarea', 'hint' => 'Only staff can see this.' ) );
		$email = (string) get_post_meta( $current->ID, '_mom_email', true );
		echo '<p class="mom-actions"><a class="mom-btn mom-btn-primary" href="mailto:' . esc_attr( $email ) . '?subject=' . rawurlencode( $current->post_title ) . '">Reply by email</a>';
		echo '<button class="mom-btn" name="mark" value="done">Mark done</button></p>';
		echo '<p class="mom-row-links">';
		ministry_desk_delete_link( $current->ID, ministry_desk_url( 'mom-events', $back ) );
		echo '</p></section></form>';
	}
	echo '</div></div>';
}

/**
 * Event editor.
 */
function ministry_desk_screen_event() {
	$id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$post  = $id ? get_post( $id ) : null;
	$start = $post ? (string) get_post_meta( $id, '_mom_starts_at', true ) : '';
	$date  = $start ? substr( $start, 0, 10 ) : '';
	$time  = $start && strlen( $start ) > 11 ? substr( $start, 11, 5 ) : '';
	$type  = $post ? (string) get_post_meta( $id, '_mom_category', true ) : 'Conference';
	$thumb = $post ? (int) get_post_thumbnail_id( $post ) : 0;
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-events' ) ) . '">Events</a> / Edit', $post ? $post->post_title : 'New event' );
	ministry_desk_form_open( 'event' );
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $id ) . '">';
	echo '<div class="mom-split"><div class="mom-main-col"><section class="mom-card"><h2>When and where</h2>';
	ministry_desk_field( 'Event name', 'post_title', $post ? $post->post_title : '', array( 'required' => true ) );
	echo '<div class="mom-grid-3">';
	ministry_desk_field( 'Start date', 'start_date', $date, array( 'type' => 'date', 'required' => true ) );
	ministry_desk_field( 'Start time', 'start_time', $time, array( 'type' => 'time', 'required' => true, 'hint' => $time ? '' : 'Time is required for upcoming events — the website currently shows “[Time]”.' ) );
	ministry_desk_field( 'End', 'end_date', $post ? substr( (string) get_post_meta( $id, '_mom_ends_at', true ), 0, 10 ) : '', array( 'type' => 'date' ) );
	echo '</div><div class="mom-grid-2">';
	ministry_desk_field( 'Venue', 'venue', $post ? (string) get_post_meta( $id, '_mom_location', true ) : '', array( 'required' => true ) );
	ministry_desk_field( 'Province', 'province', $post ? (string) get_post_meta( $id, '_mom_province', true ) : '' );
	echo '</div>';
	$day = $date ? wp_date( 'l', strtotime( $date ) ) : '';
	echo '<p>Day of week <span class="mom-auto">AUTO</span></p><input readonly value="' . esc_attr( $day ) . '"><p class="mom-hint">Worked out from the date — so the listing can never show the wrong day.</p></section>';
	echo '<section class="mom-card"><h2>About the event</h2><div class="mom-field"><label>Type <span class="mom-req">*</span></label><div class="mom-steps"><input type="hidden" name="event_type" value="' . esc_attr( $type ) . '">';
	foreach ( array( 'Conference', 'Stakeholder meeting', 'Training', 'Exhibition / show' ) as $choice ) {
		echo '<button type="button" class="' . ( $type === $choice ? 'is-on' : '' ) . '" data-value="' . esc_attr( $choice ) . '">' . esc_html( $choice ) . '</button>';
	}
	echo '</div></div>';
	ministry_desk_field( 'Short description', 'post_excerpt', $post ? $post->post_excerpt : '', array( 'type' => 'textarea', 'required' => true ) );
	ministry_desk_field( 'Who should attend', 'who', $post ? $post->post_content : '' );
	echo '</section><section class="mom-card"><h2>Registration</h2>';
	echo '<label class="mom-toggle"><input type="checkbox" name="accept_reg" value="1" ' . checked( ! $post || '1' === get_post_meta( $id, '_mom_accept_reg', true ), true, false ) . '> Accept registrations on the website</label>';
	ministry_desk_field( 'Off-site registration link', 'reg_url', $post ? (string) get_post_meta( $id, '_mom_reg_url', true ) : '', array( 'type' => 'url', 'hint' => 'Use this when people register somewhere else. Leave empty to use the on-site form.' ) );
	echo '<div class="mom-grid-2">';
	ministry_desk_field( 'Places available', 'places', $post ? (string) get_post_meta( $id, '_mom_places', true ) : '' );
	ministry_desk_field( 'Registrations close', 'reg_close', $post ? (string) get_post_meta( $id, '_mom_reg_close', true ) : '', array( 'type' => 'date' ) );
	echo '</div><p class="mom-hint">On-site registrations appear under Events → Registrations and can be exported as CSV.</p>';
	if ( $post ) {
		echo '<p><a class="mom-btn" href="' . esc_url( ministry_desk_url( 'mom-events', array( 'view' => 'registrations', 'event_id' => $post->ID ) ) ) . '">View people who registered</a></p>';
	}
	echo '<label class="mom-toggle"><input type="checkbox" name="calendar" value="1" ' . checked( ! $post || '0' !== (string) get_post_meta( $id, '_mom_calendar', true ), true, false ) . '> Offer “Add to calendar” (.ics) download</label></section></div>';
	echo '<div class="mom-side-col">';
	ministry_desk_publish_card( $post, 'After the event date it moves to Past events automatically, and you’ll be asked to add photos.' );
	echo '<section class="mom-side-card"><h2>Event photo</h2><input type="hidden" id="mom-event-photo" name="thumbnail_id" value="' . esc_attr( (string) $thumb ) . '"><div class="mom-pick-preview">';
	if ( $thumb ) {
		echo wp_get_attachment_image( $thumb, 'medium' );
	}
	echo '</div><p><button type="button" class="mom-btn mom-btn-small mom-pick" data-target="mom-event-photo" data-library="image" data-aspect="16:9">Choose photo</button></p>';
	ministry_desk_field( 'Alt text', 'alt', $thumb ? (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ) : '', array( 'required' => true ) );
	echo '</section>';
	ministry_desk_languages_card( $id, 'mom-events' );
	echo '</div></div></form>';
}

/**
 * Album list.
 */
function ministry_desk_screen_albums() {
	$albums = ministry_desk_posts( 'mom_album', array( 'orderby' => 'menu_order', 'order' => 'ASC', 'posts_per_page' => 200 ) );
	ministry_desk_head(
		'Photo albums',
		'Photo albums',
		'Create an album first, then add photos — or upload pictures on their own.',
		'<a class="mom-btn" href="' . esc_url( ministry_desk_url( 'mom-albums', array( 'view' => 'new' ) ) ) . '">New album</a> <a class="mom-btn mom-btn-primary" href="' . esc_url( ministry_desk_url( 'mom-albums', array( 'view' => 'upload' ) ) ) . '">Upload photos</a>'
	);
	if ( ! $albums ) {
		echo '<section class="mom-card"><p>No albums yet. Create an album or upload standalone photos.</p></section>';
		return;
	}
	echo '<div class="mom-grid-3">';
	foreach ( $albums as $album ) {
		$photos = ministry_album_photos( $album->ID );
		$cover  = $photos ? $photos[0]['url'] : '';
		$count  = count( $photos );
		$warn   = $count ? 'This album has ' . $count . ' photo(s). Deleting it keeps those photos as unassigned pictures. Continue?' : 'Delete this album?';
		echo '<div class="mom-card">';
		echo '<a href="' . esc_url( ministry_desk_url( 'mom-albums', array( 'view' => 'edit', 'id' => $album->ID ) ) ) . '">';
		if ( $cover ) {
			echo '<img src="' . esc_url( $cover ) . '" alt="" style="height:140px;width:100%;object-fit:cover;border-radius:8px">';
		}
		echo '<h2>' . esc_html( $album->post_title ) . '</h2></a>';
		echo '<p class="mom-hint">' . esc_html( (string) $count ) . ' photos · ' . esc_html( (string) get_post_meta( $album->ID, '_mom_year', true ) ) . '</p>';
		echo '<p class="mom-row-links"><a href="' . esc_url( ministry_desk_url( 'mom-albums', array( 'view' => 'edit', 'id' => $album->ID ) ) ) . '">Edit</a> · <a href="' . esc_url( ministry_desk_preview_url( $album ) ) . '" target="_blank" rel="noopener">Preview</a>';
		ministry_desk_delete_link( $album->ID, ministry_desk_url( 'mom-albums' ), array( 'confirm' => $warn ) );
		echo '</p></div>';
	}
	echo '</div>';
}

/**
 * Album editor with the photo grid.
 */
function ministry_desk_screen_album() {
	$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$post   = $id ? get_post( $id ) : null;
	$photos = $id ? ministry_album_photos( $id ) : array();
	$missing = 0;
	foreach ( $photos as $photo ) {
		if ( '' === trim( (string) $photo['alt'] ) ) {
			$missing++;
		}
	}
	$warn = $photos ? 'This album has ' . count( $photos ) . ' photo(s). Deleting it keeps those photos as unassigned pictures. Continue?' : 'Delete this album?';
	$album_actions = ( $post ? ministry_desk_preview_action( ministry_desk_preview_url( $post ) ) : '' ) . '<button class="mom-btn mom-save-draft" form="mom-desk-form" name="intent" value="draft">Save draft</button><button class="mom-btn mom-btn-primary" form="mom-desk-form" name="intent" value="publish">Publish</button>';
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-albums' ) ) . '">Photo albums</a>', $post ? $post->post_title : 'New album', $post ? count( $photos ) . ' photos' . ( $missing ? ' · ' . $missing . ' need alt text before publishing' : '' ) : 'Add photos, then write a short description for each one.', $album_actions );
	ministry_desk_form_open( 'album' );
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $id ) . '">';
	echo '<div class="mom-split"><div class="mom-main-col">';
	echo '<button type="button" class="mom-drop mom-add-photos"><span>Upload photos to this album<span>JPG or PNG · resized automatically</span></span></button>';
	if ( $missing ) {
		echo '<div class="mom-note mom-note-red"><p><strong>' . esc_html( (string) $missing ) . ' photos are missing alt text.</strong> The album can’t be submitted until every photo has a short description.</p></div>';
	}
	echo '<p><label><input type="checkbox" id="mom-missing-only"> Show only photos needing attention</label></p>';
	echo '<div class="mom-photo-grid mom-sortable">';
	foreach ( $photos as $index => $photo ) {
		$bad = '' === trim( (string) $photo['alt'] );
		echo '<article class="mom-photo' . ( $bad ? ' is-bad' : '' ) . '" data-id="' . esc_attr( (string) $photo['id'] ) . '"><figure><img src="' . esc_url( $photo['url'] ) . '" alt="">';
		if ( 0 === $index ) {
			echo '<span class="mom-cover-flag">Album cover</span>';
		}
		echo '<span class="grip">⋮⋮</span></figure>';
		echo '<input type="hidden" class="mom-photo-id" name="mom_photos[' . esc_attr( (string) $index ) . '][id]" value="' . esc_attr( (string) $photo['id'] ) . '">';
		echo '<input name="mom_photos[' . esc_attr( (string) $index ) . '][caption]" value="' . esc_attr( $photo['caption'] ) . '" placeholder="Caption">';
		echo '<input class="mom-alt" name="mom_photos[' . esc_attr( (string) $index ) . '][alt]" value="' . esc_attr( $photo['alt'] ) . '" placeholder="Describe the photo for people who can’t see it">';
		if ( $bad ) {
			echo '<p class="mom-bad">Alt text is required</p>';
		}
		echo '<button type="button" class="mom-btn mom-btn-small mom-remove-photo">Remove</button></article>';
	}
	echo '</div></div><div class="mom-side-col"><section class="mom-side-card"><h2>Album details</h2>';
	ministry_desk_field( 'Album category', 'album_cat', $post ? (string) get_post_meta( $id, '_mom_album_cat', true ) : '', array( 'required' => true ) );
	ministry_desk_field( 'Event name', 'post_title', $post ? $post->post_title : '', array( 'required' => true ) );
	ministry_desk_field( 'Event date', 'event_date', $post ? (string) get_post_meta( $id, '_mom_year', true ) : '', array( 'required' => true ) );
	ministry_desk_field( 'Location', 'location', $post ? (string) get_post_meta( $id, '_mom_location', true ) : '' );
	ministry_desk_field( 'Default credit', 'credit', $post ? (string) get_post_meta( $id, '_mom_credit', true ) : 'MMMD Communications', array( 'hint' => 'Applied to all photos in this album; change any single photo if needed.' ) );
	echo '<label class="mom-toggle"><input type="checkbox" name="show_credit" value="1" ' . checked( ! $post || '1' === get_post_meta( $id, '_mom_show_credit', true ), true, false ) . '> Show credit under photos</label>';
	if ( $post ) {
		echo '<p class="mom-row-links">';
		ministry_desk_delete_link( $post->ID, ministry_desk_url( 'mom-albums' ), array( 'confirm' => $warn ) );
		echo '</p>';
	}
	echo '</section></div></div></form>';
	echo '<script type="text/template" id="mom-photo-template"><article class="mom-photo is-bad"><figure><img src="" alt=""><span class="grip">⋮⋮</span></figure><input type="hidden" class="mom-photo-id" name="mom_photos[__i__][id]" value=""><input name="mom_photos[__i__][caption]" placeholder="Caption"><input class="mom-alt" name="mom_photos[__i__][alt]" placeholder="Describe the photo for people who can’t see it"><button type="button" class="mom-btn mom-btn-small mom-remove-photo">Remove</button></article></script>';
}

/**
 * Create an empty album.
 */
function ministry_desk_screen_album_new() {
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-albums' ) ) . '">Photo albums</a> / New album', 'New album', 'Name the album first. You can add photos after it exists.' );
	ministry_desk_form_open( 'album-create' );
	echo '<div class="mom-split"><div class="mom-main-col"><section class="mom-card"><h2>Album</h2>';
	ministry_desk_field( 'Album name', 'post_title', '', array( 'required' => true ) );
	echo '<div class="mom-grid-2">';
	ministry_desk_field( 'Album category', 'album_cat', '' );
	ministry_desk_field( 'Year or event date', 'event_date', wp_date( 'Y' ) );
	echo '</div>';
	ministry_desk_field( 'Location', 'location', '' );
	ministry_desk_field( 'Default credit', 'credit', 'MMMD Communications' );
	echo '</section></div><div class="mom-side-col"><section class="mom-side-card"><h2>Create</h2>';
	echo '<button class="mom-btn mom-save-draft" name="intent" value="draft">Save draft</button>';
	echo '<button class="mom-btn mom-btn-primary" name="intent" value="publish">Publish</button>';
	echo '<p class="mom-hint">After saving you can upload photos into this album or leave it empty.</p></section></div></div></form>';
}

/**
 * All gallery photos, including pictures not in an album.
 */
function ministry_desk_screen_photos() {
	$photos = ministry_gallery_photos();
	$albums = ministry_desk_posts( 'mom_album', array( 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
	ministry_desk_head( 'Photo albums / All photos', 'All photos', 'Assign pictures to albums or keep them standalone.', '<a class="mom-btn mom-btn-primary" href="' . esc_url( ministry_desk_url( 'mom-albums', array( 'view' => 'upload' ) ) ) . '">Upload photos</a>' );
	if ( ! $photos ) {
		echo '<section class="mom-card"><p>No gallery photos yet.</p></section>';
		return;
	}
	ministry_desk_form_open( 'photos' );
	echo '<p class="mom-actions"><button class="mom-btn mom-btn-primary">Save assignments</button></p>';
	echo '<div class="mom-photo-grid">';
	foreach ( $photos as $index => $photo ) {
		$url     = wp_get_attachment_image_url( $photo->ID, 'medium' );
		$ids     = ministry_photo_album_ids( $photo->ID );
		$caption = (string) $photo->post_excerpt;
		$alt     = (string) get_post_meta( $photo->ID, '_wp_attachment_image_alt', true );
		echo '<article class="mom-photo"><figure>';
		if ( $url ) {
			echo '<img src="' . esc_url( $url ) . '" alt="">';
		}
		echo '</figure>';
		echo '<input type="hidden" name="photos[' . esc_attr( (string) $index ) . '][id]" value="' . esc_attr( (string) $photo->ID ) . '">';
		echo '<input name="photos[' . esc_attr( (string) $index ) . '][caption]" value="' . esc_attr( $caption ) . '" placeholder="Caption">';
		echo '<input name="photos[' . esc_attr( (string) $index ) . '][alt]" value="' . esc_attr( $alt ) . '" placeholder="Alt text">';
		echo '<label class="mom-label">Albums</label><select name="photos[' . esc_attr( (string) $index ) . '][albums][]" multiple size="4">';
		foreach ( $albums as $album ) {
			echo '<option value="' . esc_attr( (string) $album->ID ) . '" ' . selected( in_array( $album->ID, $ids, true ), true, false ) . '>' . esc_html( $album->post_title ) . '</option>';
		}
		echo '</select>';
		if ( ! $ids ) {
			echo '<p class="mom-hint">Standalone — not in an album</p>';
		}
		echo '<p class="mom-row-links">';
		ministry_desk_delete_link( 0, ministry_desk_url( 'mom-albums', array( 'view' => 'photos' ) ), array( 'attachment_id' => $photo->ID, 'confirm' => 'Delete this photo from the website?' ) );
		echo '</p></article>';
	}
	echo '</div><p class="mom-actions"><button class="mom-btn mom-btn-primary">Save assignments</button></p></form>';
}

/**
 * Upload photos that can stay standalone or go into albums.
 */
function ministry_desk_screen_photo_upload() {
	$albums = ministry_desk_posts( 'mom_album', array( 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-albums' ) ) . '">Photo albums</a> / Upload', 'Upload photos', 'Leave albums unticked to keep pictures standalone.' );
	ministry_desk_form_open( 'photos' );
	echo '<div class="mom-split"><div class="mom-main-col">';
	echo '<button type="button" class="mom-drop mom-add-photos"><span>Upload photos<span>JPG or PNG · they stay standalone unless you assign an album</span></span></button>';
	echo '<div class="mom-photo-grid mom-sortable"></div>';
	echo '</div><div class="mom-side-col"><section class="mom-side-card"><h2>Optional album</h2>';
	echo '<p class="mom-hint">Choose albums for every photo in this upload. You can change this later on All photos.</p>';
	foreach ( $albums as $album ) {
		echo '<label class="mom-toggle"><input type="checkbox" class="mom-upload-album" value="' . esc_attr( (string) $album->ID ) . '"> ' . esc_html( $album->post_title ) . '</label>';
	}
	echo '<button class="mom-btn mom-btn-primary">Upload</button></section></div></div></form>';
	echo '<script type="text/template" id="mom-photo-template"><article class="mom-photo"><figure><img src="" alt=""></figure><input type="hidden" class="mom-photo-id" name="photos[__i__][id]" value=""><input name="photos[__i__][caption]" placeholder="Caption"><input class="mom-alt" name="photos[__i__][alt]" placeholder="Describe the photo"><div class="mom-upload-albums"></div></article></script>';
}

/**
 * People, departments, or offices, depending on the view.
 */
function ministry_desk_screen_people() {
	$view = ministry_desk_view();
	if ( 'departments' === $view || 'offices' === $view ) {
		ministry_desk_screen_org_list( 'offices' === $view );
		return;
	}
	$leaders = ministry_settings()['leaders'];
	$save    = ministry_desk_can( 'people' ) ? '<button class="mom-btn mom-btn-primary" form="mom-desk-form">Save order</button> ' : '';
	ministry_desk_head( 'Leadership', 'Leadership', 'Portraits, titles, and biographies used across the site. Drag to change the order on The Ministry and the homepage.', $save . '<a class="mom-btn mom-btn-primary" href="' . esc_url( ministry_desk_url( 'mom-people', array( 'view' => 'person' ) ) ) . '">+ Add person</a>' );
	ministry_desk_form_open( 'people-order' );
	echo '<input type="hidden" name="order" value="' . esc_attr( implode( ',', array_keys( is_array( $leaders ) ? $leaders : array() ) ) ) . '">';
	echo '<div class="mom-split"><div class="mom-sortable">';
	foreach ( $leaders as $index => $leader ) {
		if ( ! is_array( $leader ) ) {
			continue;
		}
		$photo = ministry_desk_leader_photo( $leader );
		$on_home = ! isset( $leader['home'] ) || '0' !== (string) $leader['home'];
		$former  = isset( $leader['status'] ) && 'former' === $leader['status'];
		echo '<article class="mom-person" data-id="' . esc_attr( (string) $index ) . '" data-home="' . ( $on_home ? '1' : '0' ) . '" data-former="' . ( $former ? '1' : '0' ) . '"><span class="grip">⋮⋮</span>';
		if ( $photo ) {
			echo '<img src="' . esc_url( $photo ) . '" alt="">';
		} else {
			echo '<span class="mom-ph">No photo</span>';
		}
		echo '<a class="who" href="' . esc_url( ministry_desk_url( 'mom-people', array( 'view' => 'person', 'index' => $index ) ) ) . '"><b>' . esc_html( (string) $leader['name'] ) . '</b>' . esc_html( (string) $leader['title'] ) . '</a>';
		$person_preview = home_url( '/the-ministry/' );
		if ( ! empty( $leader['name'] ) ) {
			$person_preview .= '#' . rawurlencode( sanitize_title( (string) $leader['name'] ) );
		}
		echo '<label class="mom-toggle"><input type="checkbox" name="home[' . esc_attr( (string) $index ) . ']" value="1" ' . checked( $on_home, true, false ) . '> Show on Home</label>';
		echo '<span class="mom-row-links"><a href="' . esc_url( $person_preview ) . '" target="_blank" rel="noopener">Preview</a> · ';
		ministry_desk_delete_link( 0, ministry_desk_url( 'mom-people' ), array( 'leader_index' => $index ) );
		echo '</span></article>';
	}
	echo '</div><div class="mom-side-col"><section class="mom-side-card"><h2>Preview — Home page</h2><div class="mom-grid-2" id="mom-home-preview"></div><p class="mom-hint">People who appear on the homepage, in this list order. Drag, then save, to change the public homepage.</p></section></div></div></form>';
}

/**
 * @param bool $offices Provincial offices when true.
 */
function ministry_desk_screen_org_list( $offices ) {
	$title = $offices ? 'Offices' : 'Departments';
	$view  = $offices ? 'office' : 'department';
	$form  = $offices ? 'offices-order' : 'departments-order';
	$save  = ministry_desk_can( 'people' ) ? '<button class="mom-btn mom-btn-primary" form="mom-desk-form">Save order</button> ' : '';
	$hint  = $offices ? 'Harare centres and provincial mining offices. Drag within each group to change the public Offices page.' : 'Head-office departments. Drag to change the public Departments page.';
	ministry_desk_head( 'Leadership / ' . $title, $title, $hint, $save . '<a class="mom-btn mom-btn-primary" href="' . esc_url( ministry_desk_url( 'mom-people', array( 'view' => $view ) ) ) . '">+ Add</a>' );
	ministry_desk_form_open( $form );
	if ( $offices ) {
		$centres   = ministry_structure_apply_display_order( ministry_desk_posts( 'mom_department', array_merge( ministry_structure_query( 'centre' ), array( 'posts_per_page' => 200 ) ) ) );
		$provinces = ministry_structure_apply_display_order( ministry_desk_posts( 'mom_department', array_merge( ministry_structure_query( 'province' ), array( 'posts_per_page' => 200 ) ) ) );
		echo '<input type="hidden" name="order_centres" value="' . esc_attr( implode( ',', wp_list_pluck( $centres, 'ID' ) ) ) . '">';
		echo '<input type="hidden" name="order" value="' . esc_attr( implode( ',', wp_list_pluck( $provinces, 'ID' ) ) ) . '">';
		ministry_desk_order_table( 'Head office centres', $centres, 'office', 'order_centres' );
		ministry_desk_order_table( 'Provincial offices', $provinces, 'office', 'order' );
	} else {
		$posts = ministry_structure_apply_display_order( ministry_desk_posts( 'mom_department', array_merge( ministry_structure_query( 'department' ), array( 'posts_per_page' => 200 ) ) ) );
		echo '<input type="hidden" name="order" value="' . esc_attr( implode( ',', wp_list_pluck( $posts, 'ID' ) ) ) . '">';
		ministry_desk_order_table( '', $posts, 'department', 'order' );
	}
	echo '</form>';
}

/**
 * @param string    $heading Section heading.
 * @param WP_Post[] $posts   Records.
 * @param string    $view    department|office.
 * @param string    $field   Hidden order field name.
 */
function ministry_desk_order_table( $heading, $posts, $view, $field ) {
	if ( $heading ) {
		echo '<h2>' . esc_html( $heading ) . '</h2>';
	}
	echo '<div class="mom-table-card"><table class="mom-table"><thead><tr><th></th><th>Name</th><th>Location</th><th>Contact</th></tr></thead><tbody class="mom-sortable" data-order-field="' . esc_attr( $field ) . '">';
	foreach ( $posts as $post ) {
		echo '<tr data-id="' . esc_attr( (string) $post->ID ) . '"><td><span class="grip">⋮⋮</span></td>';
		echo '<td><a class="title" href="' . esc_url( ministry_desk_url( 'mom-people', array( 'view' => $view, 'id' => $post->ID ) ) ) . '">' . esc_html( $post->post_title ) . '</a>';
		echo '<div class="mom-row-links"><a href="' . esc_url( ministry_desk_url( 'mom-people', array( 'view' => $view, 'id' => $post->ID ) ) ) . '">Edit</a> · <a href="' . esc_url( ministry_desk_preview_url( $post ) ) . '" target="_blank" rel="noopener">Preview</a>';
		ministry_desk_delete_link( $post->ID, ministry_desk_url( 'mom-people', array( 'view' => 'office' === $view ? 'offices' : 'departments' ) ) );
		echo '</div></td>';
		$place = (string) get_post_meta( $post->ID, '_mom_location', true );
		if ( 'office' === $view && '' === $place ) {
			$place = trim( (string) get_post_meta( $post->ID, '_mom_town', true ) . ' ' . (string) get_post_meta( $post->ID, '_mom_province_name', true ) );
		}
		$contact = (string) get_post_meta( $post->ID, '_mom_contact_name', true );
		if ( 'office' === $view ) {
			$contact = (string) get_post_meta( $post->ID, '_mom_email', true );
			if ( '' === $contact ) {
				$contact = (string) get_post_meta( $post->ID, '_mom_phone', true );
			}
		}
		echo '<td>' . esc_html( $place ) . '</td>';
		echo '<td>' . esc_html( $contact ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/**
 * One person.
 */
function ministry_desk_screen_person() {
	$index   = isset( $_GET['index'] ) ? (int) $_GET['index'] : -1;
	$leaders = ministry_settings()['leaders'];
	$leader  = ( $index >= 0 && isset( $leaders[ $index ] ) && is_array( $leaders[ $index ] ) ) ? $leaders[ $index ] : array( 'name' => '', 'title' => '', 'bio' => '', 'imageUrl' => '' );
	$photo   = ministry_desk_leader_photo( $leader );
	$surname = isset( $leader['surname'] ) ? (string) $leader['surname'] : (string) $leader['name'];
	$facts   = isset( $leader['facts'] ) && is_array( $leader['facts'] ) ? $leader['facts'] : array( '' );
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-people' ) ) . '">Leadership</a> / Edit', $leader['name'] ? (string) $leader['name'] : 'New person', $leader['name'] ? 'Person' : 'Add a portrait, title, and short biography.' );
	ministry_desk_form_open( 'person' );
	if ( $index >= 0 ) {
		echo '<input type="hidden" name="index" value="' . esc_attr( (string) $index ) . '">';
	}
	echo '<div class="mom-split"><div class="mom-main-col"><section class="mom-card"><h2>Portrait</h2>';
	if ( $photo ) {
		echo '<img src="' . esc_url( $photo ) . '" alt="" style="width:160px;border-radius:8px">';
		echo '<div class="mom-note"><p class="mom-ok">Portrait accepted when it is a 4:5 head-and-shoulders photo.</p></div>';
	}
	echo '<input type="hidden" id="mom-person-photo" name="image_id" value="' . esc_attr( isset( $leader['imageId'] ) ? (string) $leader['imageId'] : '' ) . '">';
	echo '<p><button type="button" class="mom-btn mom-btn-small mom-pick" data-target="mom-person-photo" data-library="image" data-aspect="4:5">Replace</button></p>';
	echo '<p class="mom-hint">Portraits must be 4:5 and at least 800 × 1000 px, head and shoulders, plain background.</p></section>';
	echo '<section class="mom-card"><h2>Name and role</h2><div class="mom-grid-3">';
	ministry_desk_field( 'Title', 'honorific', isset( $leader['honorific'] ) ? (string) $leader['honorific'] : '' );
	ministry_desk_field( 'Initials', 'initials', isset( $leader['initials'] ) ? (string) $leader['initials'] : '', array( 'required' => true ) );
	ministry_desk_field( 'Surname', 'surname', $surname, array( 'required' => true ) );
	echo '</div><div class="mom-grid-2">';
	ministry_desk_field( 'Job title', 'job', (string) $leader['title'], array( 'required' => true ) );
	ministry_desk_field( 'Department', 'department', isset( $leader['department'] ) ? (string) $leader['department'] : '', array( 'hint' => 'Picked from the list, so names never drift.' ) );
	echo '</div>';
	echo '<label class="mom-toggle"><input type="checkbox" name="former" value="1" ' . checked( isset( $leader['status'] ) && 'former' === $leader['status'], true, false ) . '> Former — kept for history but hidden from current listings</label>';
	echo '<label class="mom-toggle"><input type="checkbox" name="acting" value="1" ' . checked( isset( $leader['acting'] ) && '1' === $leader['acting'], true, false ) . '> This person is acting in this role</label></section>';
	echo '<section class="mom-card"><h2>Biography</h2>';
	ministry_desk_field( 'Short bio', 'short_bio', (string) $leader['bio'], array( 'type' => 'textarea', 'hint' => 'Up to 60 words. Used on cards.' ) );
	ministry_desk_field( 'Full biography (optional)', 'full_bio', isset( $leader['fullBio'] ) ? (string) $leader['fullBio'] : '', array( 'type' => 'textarea', 'rows' => 6 ) );
	echo '<div data-repeater><label class="mom-label">Key facts</label>';
	foreach ( array_values( $facts ) as $i => $fact ) {
		echo '<div class="mom-rowline" data-row><span class="grip">⋮⋮</span><input name="facts[' . esc_attr( (string) $i ) . ']" value="' . esc_attr( (string) $fact ) . '"><button type="button" data-remove-row>&times;</button></div>';
	}
	echo '<button type="button" class="mom-btn mom-btn-small" data-add-row>+ Add a key fact</button></div></section>';
	echo '<section class="mom-card"><h2>Contact (optional)</h2>';
	ministry_desk_field( 'Work email', 'email', isset( $leader['email'] ) ? (string) $leader['email'] : '', array( 'type' => 'email' ) );
	echo '<label class="mom-toggle"><input type="checkbox" name="show_email" value="1" ' . checked( ! empty( $leader['showEmail'] ) && '1' === $leader['showEmail'], true, false ) . '> Show email on the website</label><p class="mom-hint">Personal numbers are never shown.</p>';
	$pdf_id  = isset( $leader['profileId'] ) ? (int) $leader['profileId'] : 0;
	$pdf_url = $pdf_id ? wp_get_attachment_url( $pdf_id ) : ( isset( $leader['profileUrl'] ) ? (string) $leader['profileUrl'] : '' );
	echo '<div class="mom-field"><label>Full profile (PDF)</label><p class="mom-hint">Replaces the Download full profile link on The Ministry.</p>';
	echo '<input type="hidden" id="mom-person-profile" name="profile_id" value="' . esc_attr( (string) $pdf_id ) . '">';
	echo $pdf_url ? '<p class="mom-ok"><a href="' . esc_url( $pdf_url ) . '">Current file</a></p>' : '';
	echo '<button type="button" class="mom-btn mom-btn-small mom-pick" data-target="mom-person-profile" data-library="file">Choose PDF</button></div></section></div>';
	echo '<div class="mom-side-col">';
	echo '<section class="mom-side-card"><h2>Publish</h2><p>' . ( ministry_desk_can_publish() ? 'Changes to leadership go live when you update.' : 'Changes to leadership go to the Communications Director for review.' ) . '</p>';
	echo '<button class="mom-btn mom-btn-primary" name="intent" value="' . ( ministry_desk_can_publish() ? 'keep' : 'pending' ) . '">' . ( ministry_desk_can_publish() ? 'Update' : 'Submit for review' ) . '</button>';
	if ( $index >= 0 ) {
		echo '<input type="hidden" name="leader_index" value="' . esc_attr( (string) $index ) . '">';
		echo '<p><button class="mom-btn mom-btn-danger" name="form" value="delete" onclick="return confirm(\'Remove this person from the website?\')">Delete</button></p>';
	}
	echo '</section>';
	$person_preview = home_url( '/the-ministry/' );
	if ( ! empty( $leader['name'] ) ) {
		$person_preview .= '#' . rawurlencode( sanitize_title( (string) $leader['name'] ) );
	}
	echo '<section class="mom-side-card"><h2>This person appears on</h2><p><a href="' . esc_url( $person_preview ) . '" target="_blank" rel="noopener">The Ministry</a></p><p><a href="' . esc_url( home_url( '/contact/' ) ) . '" target="_blank" rel="noopener">Contact</a></p>' . ministry_desk_preview_action( $person_preview ) . '</section>';
	echo '</div></div></form>';
}

/**
 * Home-page leadership order.
 */
function ministry_desk_screen_leadership() {
	$leaders = ministry_settings()['leaders'];
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-people' ) ) . '">Leadership</a> / Homepage order', 'Homepage order', 'Choose who appears in “The Ministry” section of the home page and on the leadership page, and in what order.', '<button class="mom-btn mom-btn-primary" form="mom-desk-form">Save order</button>' );
	ministry_desk_form_open( 'leadership' );
	echo '<input type="hidden" name="order" value="' . esc_attr( implode( ',', array_keys( $leaders ) ) ) . '">';
	echo '<div class="mom-split"><div class="mom-sortable">';
	$n = 1;
	foreach ( $leaders as $index => $leader ) {
		if ( ! is_array( $leader ) ) {
			continue;
		}
		$photo = ministry_desk_leader_photo( $leader );
		echo '<article class="mom-person" data-id="' . esc_attr( (string) $index ) . '"><span class="grip">⋮⋮</span><strong>' . esc_html( (string) $n ) . '</strong>';
		if ( $photo ) {
			echo '<img src="' . esc_url( $photo ) . '" alt="">';
		}
		echo '<span class="who"><b>' . esc_html( (string) $leader['name'] ) . '</b>' . esc_html( (string) $leader['title'] ) . '</span>';
		echo '<label class="mom-toggle"><input type="checkbox" name="home[' . esc_attr( (string) $index ) . ']" value="1" ' . checked( ! isset( $leader['home'] ) || '0' !== (string) $leader['home'], true, false ) . '> Show on Home</label>';
		echo '<a href="' . esc_url( ministry_desk_url( 'mom-people', array( 'view' => 'person', 'index' => $index ) ) ) . '">Edit person</a></article>';
		$n++;
	}
	echo '</div><div class="mom-side-col"><section class="mom-side-card"><h2>Preview — Home page</h2><div class="mom-grid-2" id="mom-home-preview">';
	$shown = 0;
	foreach ( $leaders as $leader ) {
		if ( ! is_array( $leader ) || ( isset( $leader['status'] ) && 'former' === $leader['status'] ) ) {
			continue;
		}
		if ( isset( $leader['home'] ) && '0' === (string) $leader['home'] ) {
			continue;
		}
		if ( $shown >= 4 ) {
			break;
		}
		$photo = ministry_desk_leader_photo( $leader );
		echo '<div>';
		if ( $photo ) {
			echo '<img src="' . esc_url( $photo ) . '" alt="" style="width:100%;aspect-ratio:4/5;object-fit:cover;border-radius:8px">';
		}
		echo '<p><strong>' . esc_html( (string) $leader['name'] ) . '</strong></p></div>';
		++$shown;
	}
	echo '</div><p class="mom-hint">People marked Show on Home, in the order of the list. Drag or tick a box and this preview updates.</p></section>';
	foreach ( $leaders as $leader ) {
		if ( is_array( $leader ) && str_contains( strtolower( (string) $leader['name'] ), 'wushe' ) ) {
			echo '<div class="mom-note mom-note-gold"><p><strong>Title wording:</strong> 2026 documents use both “Permanent Secretary” and “Secretary for Mines” for Dr Wushe. Pick one in his People record and it changes everywhere.</p></div>';
			break;
		}
	}
	echo '</div></div></form>';
}

/**
 * Organisational structure.
 */
function ministry_desk_screen_structure() {
	$leaders = ministry_settings()['leaders'];
	$meta    = ministry_desk_meta();
	$org     = isset( $meta['org'] ) && is_array( $meta['org'] ) ? $meta['org'] : array();
	$groups  = ministry_desk_department_groups();
	$buckets = array( '1' => array(), '2' => array(), '3' => array(), '4' => array() );
	foreach ( ministry_desk_posts( 'mom_department', array( 'posts_per_page' => 200, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $post ) {
		$buckets[ ministry_desk_department_group( $post ) ][] = $post;
	}
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-people' ) ) . '">Leadership</a> / Organisational structure', 'Organisational structure', 'Drag groups and departments to reorder. The chart on About the ministry redraws itself.', ministry_desk_preview_action( home_url( '/about/#structure' ) ) . '<button class="mom-btn mom-btn-primary" form="mom-desk-form">Save changes</button>' );
	ministry_desk_form_open( 'structure' );
	echo '<div class="mom-split"><div class="mom-main-col"><section class="mom-card"><h2>Top of the structure</h2><div class="mom-grid-2">';
	foreach ( array_slice( $leaders, 0, 4 ) as $leader ) {
		if ( ! is_array( $leader ) ) {
			continue;
		}
		echo '<div class="mom-chip">' . esc_html( (string) $leader['name'] ) . '<br><span class="mom-hint">' . esc_html( (string) $leader['title'] ) . '</span></div>';
	}
	echo '</div><div class="mom-note mom-note-gold"><p>Departments are picked from the Departments list, so a name change there updates the chart automatically.</p></div></section>';
	foreach ( $groups as $key => $fallback ) {
		$group = isset( $org[ $key ] ) && is_array( $org[ $key ] ) ? $org[ $key ] : array();
		$title = isset( $group['title'] ) && $group['title'] ? $group['title'] : $fallback;
		echo '<section class="mom-card"><div class="mom-rowline"><strong>0' . esc_html( $key ) . '</strong><input name="org[' . esc_attr( $key ) . '][title]" value="' . esc_attr( $title ) . '"></div>';
		ministry_desk_field( 'Reports to', 'org[' . $key . '][reports]', isset( $group['reports'] ) ? (string) $group['reports'] : 'Permanent Secretary' );
		ministry_desk_field( 'Chief Director', 'org[' . $key . '][chief]', isset( $group['chief'] ) ? (string) $group['chief'] : '', array( 'hint' => 'Leave blank to show “to be confirmed”.' ) );
		echo '<div class="mom-sortable">';
		foreach ( $buckets[ $key ] as $post ) {
			$is_office = '1' === get_post_meta( $post->ID, '_mom_provincial', true ) || '1' === get_post_meta( $post->ID, '_mom_centre', true );
			echo '<div class="mom-rowline" data-id="' . esc_attr( (string) $post->ID ) . '"><span class="grip">⋮⋮</span><input type="hidden" name="org[' . esc_attr( $key ) . '][depts][]" value="' . esc_attr( (string) $post->ID ) . '"><strong>' . esc_html( $post->post_title ) . '</strong> ';
			if ( ! $is_office ) {
				$head = (string) get_post_meta( $post->ID, '_mom_contact_name', true );
				ministry_desk_badge( $head ? 'Head set' : 'Head missing', $head ? 'green' : 'gold' );
			}
			echo ' <a href="' . esc_url( ministry_desk_url( 'mom-people', array( 'view' => $is_office ? 'office' : 'department', 'id' => $post->ID ) ) ) . '">Edit</a></div>';
		}
		echo '</div></section>';
	}
	echo '</div><div class="mom-side-col"><section class="mom-side-card"><h2>How this works</h2><ol><li>Groups are the coloured bands on the chart.</li><li>Each department belongs to one group.</li><li>Heads come from People — update a person once and every page follows.</li><li>Former staff can’t be chosen.</li></ol></section>';
	echo '<section class="mom-side-card"><h2>Who can edit</h2><p>Administrators and the Communications Director. Content Officers can view only.</p></section></div></div></form>';
}

/**
 * Department editor.
 */
function ministry_desk_screen_department() {
	ministry_desk_screen_unit( false );
}

/**
 * Provincial office editor.
 */
function ministry_desk_screen_office() {
	ministry_desk_screen_unit( true );
}

/**
 * @param bool $office Office layout when true.
 */
function ministry_desk_screen_unit( $office ) {
	$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$post = $id ? get_post( $id ) : null;
	$form = $office ? 'office' : 'department';
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-people', array( 'view' => $office ? 'offices' : 'departments' ) ) ) . '">' . ( $office ? 'Offices' : 'Departments' ) . '</a> / Edit', $post ? $post->post_title : ( $office ? 'New office' : 'New department' ) );
	ministry_desk_form_open( $form );
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $id ) . '">';
	$functions = $post ? get_post_meta( $id, '_mom_functions', true ) : array();
	if ( ! is_array( $functions ) || ! $functions ) {
		$legacy = $post ? (string) get_post_meta( $id, '_mom_services', true ) : '';
		$functions = $legacy ? array_map( 'trim', explode( ';', $legacy ) ) : array( '' );
	}
	echo '<div class="mom-split"><div class="mom-main-col">';
	if ( $office ) {
		echo '<section class="mom-card"><h2>Location</h2>';
		ministry_desk_field( 'Office name', 'post_title', $post ? $post->post_title : '', array( 'required' => true ) );
		echo '<div class="mom-grid-2">';
		ministry_desk_field( 'Province', 'province', $post ? (string) get_post_meta( $id, '_mom_province_name', true ) : '', array( 'required' => true ) );
		ministry_desk_field( 'Town', 'town', $post ? (string) get_post_meta( $id, '_mom_town', true ) : ( $post ? (string) get_post_meta( $id, '_mom_location', true ) : '' ), array( 'required' => true ) );
		echo '</div>';
		ministry_desk_field(
			'Street address',
			'address',
			$post ? (string) get_post_meta( $id, '_mom_address', true ) : '',
			array(
				'required' => true,
				'hint'     => 'The Offices page map thumbnail and the Google Maps link both follow this address.',
			)
		);
		echo '</section>';
		echo '<section class="mom-card" data-repeater><h2>Contact</h2>';
		$phones = $post ? get_post_meta( $id, '_mom_phones', true ) : array();
		if ( ! is_array( $phones ) || ! $phones ) {
			$phones = array( array( 'number' => $post ? (string) get_post_meta( $id, '_mom_phone', true ) : '', 'label' => 'Main line' ) );
		}
		foreach ( array_values( $phones ) as $i => $phone ) {
			$phone = is_array( $phone ) ? $phone : array();
			echo '<div class="mom-rowline" data-row><input name="phones[' . esc_attr( (string) $i ) . '][number]" value="' . esc_attr( isset( $phone['number'] ) ? (string) $phone['number'] : '' ) . '"><input name="phones[' . esc_attr( (string) $i ) . '][label]" value="' . esc_attr( isset( $phone['label'] ) ? (string) $phone['label'] : '' ) . '"><button type="button" data-remove-row>&times;</button></div>';
		}
		echo '<button type="button" class="mom-btn mom-btn-small" data-add-row>+ Add number</button>';
		ministry_desk_field( 'Email', 'email', $post ? (string) get_post_meta( $id, '_mom_email', true ) : '' );
		echo '</section><section class="mom-card"><h2>About the province</h2>';
		echo '<div class="mom-field"><label>Main minerals</label>';
		ministry_desk_chips( 'minerals', $post && is_array( get_post_meta( $id, '_mom_minerals', true ) ) ? get_post_meta( $id, '_mom_minerals', true ) : array() );
		echo '</div>';
		ministry_desk_field( 'Mining profile', 'profile', $post ? (string) get_post_meta( $id, '_mom_profile', true ) : '', array( 'type' => 'textarea' ) );
		echo '</section>';
	} else {
		echo '<section class="mom-card"><h2>Basics</h2>';
		ministry_desk_field( 'Department name', 'post_title', $post ? $post->post_title : '', array( 'required' => true ) );
		echo '<div class="mom-grid-3">';
		ministry_desk_field( 'Short name', 'short', $post ? (string) get_post_meta( $id, '_mom_short', true ) : '', array( 'hint' => 'Used in menus and badges.' ) );
		ministry_desk_field( 'Group', 'group', $post ? ministry_desk_department_group( $post ) : '1', array( 'type' => 'select', 'options' => ministry_desk_department_groups() ) );
		echo '</div></section><section class="mom-card"><h2>What the department does</h2>';
		ministry_desk_field( 'Purpose', 'purpose', $post ? (string) get_post_meta( $id, '_mom_mandate', true ) : '', array( 'type' => 'textarea', 'required' => true, 'hint' => 'Shown under the department name on its page.' ) );
		ministry_desk_field( 'List summary', 'summary', $post ? (string) get_post_meta( $id, '_mom_summary', true ) : '', array( 'type' => 'textarea', 'hint' => 'Shorter mandate on the Departments list.' ) );
		echo '<div data-repeater><label class="mom-label">Functions</label>';
		foreach ( array_values( $functions ) as $i => $line ) {
			echo '<div class="mom-rowline" data-row><span class="grip">⋮⋮</span><input name="functions[' . esc_attr( (string) $i ) . ']" value="' . esc_attr( (string) $line ) . '"><button type="button" data-remove-row>&times;</button></div>';
		}
		echo '<button type="button" class="mom-btn mom-btn-small" data-add-row>+ Add function</button></div>';
		$standards = $post ? get_post_meta( $id, '_mom_standards', true ) : array();
		if ( ! is_array( $standards ) || ! $standards ) {
			$standards = array( array( 'label' => '', 'time' => '' ) );
		}
		echo '<div data-repeater style="margin-top:16px"><label class="mom-label">Service standards</label>';
		foreach ( array_values( $standards ) as $i => $standard ) {
			$standard = is_array( $standard ) ? $standard : array();
			echo '<div class="mom-rowline" data-row><input name="standards[' . esc_attr( (string) $i ) . '][label]" value="' . esc_attr( isset( $standard['label'] ) ? (string) $standard['label'] : '' ) . '" placeholder="Service"><input name="standards[' . esc_attr( (string) $i ) . '][time]" value="' . esc_attr( isset( $standard['time'] ) ? (string) $standard['time'] : '' ) . '" placeholder="Standard"><button type="button" data-remove-row>&times;</button></div>';
		}
		echo '<button type="button" class="mom-btn mom-btn-small" data-add-row>+ Add standard</button></div>';
		$photo  = $post ? (int) get_post_meta( $id, '_mom_photo', true ) : 0;
		$design = $post ? ministry_department_design_slug( $post ) : '';
		$pages  = ministry_department_design_pages();
		echo '<div class="mom-field" style="margin-top:16px"><label>Description photo</label>';
		echo '<p class="mom-hint">The wide photo under the mandate. The portrait beside the head of department is separate.</p>';
		echo '<select name="design_page" id="mom-dept-page">';
		echo '<option value="">Choose the department page</option>';
		foreach ( $pages as $slug => $page ) {
			echo '<option value="' . esc_attr( $slug ) . '" data-photo="' . esc_url( $page['src'] ) . '" ' . selected( $design, $slug, false ) . '>' . esc_html( $page['label'] ) . '</option>';
		}
		echo '</select>';
		echo '<input type="hidden" id="mom-dept-photo" name="photo_id" value="' . esc_attr( (string) $photo ) . '"><div class="mom-pick-preview">';
		if ( $photo ) {
			echo wp_get_attachment_image( $photo, 'large' );
		} elseif ( $design && ! empty( $pages[ $design ]['src'] ) ) {
			echo '<img src="' . esc_url( $pages[ $design ]['src'] ) . '" alt="">';
		}
		echo '</div><button type="button" class="mom-btn mom-btn-small mom-pick" data-target="mom-dept-photo" data-library="image" data-aspect="16:9">Change photo</button></div>';
		if ( $post && str_contains( $post->post_name, 'geological' ) ) {
			echo '<div class="mom-note mom-note-gold"><p>The 2026 update lists “administer mining titles” under this department — the Mining Cadastre now does this. Check before publishing.</p></div>';
		}
		echo '</section>';
	}
	echo '</div><div class="mom-side-col">';
	ministry_desk_publish_card( $post, $office ? 'Address and phone changes are checked by the Communications Director before going live.' : 'Department names are used across the site — renaming here updates every page.' );
	if ( ! $office ) {
		echo '<section class="mom-side-card"><h2>Head of department</h2>';
		ministry_desk_field( 'Name', 'head', $post ? (string) get_post_meta( $id, '_mom_contact_name', true ) : '' );
		ministry_desk_field( 'Title shown', 'head_title', $post ? (string) get_post_meta( $id, '_mom_head_title', true ) : '' );
		$thumb = $post ? (int) get_post_thumbnail_id( $post ) : 0;
		echo '<input type="hidden" id="mom-unit-photo" name="thumbnail_id" value="' . esc_attr( (string) $thumb ) . '"><div class="mom-pick-preview">';
		if ( $thumb ) {
			echo wp_get_attachment_image( $thumb, 'medium' );
		}
		echo '</div><button type="button" class="mom-btn mom-btn-small mom-pick" data-target="mom-unit-photo" data-library="image" data-aspect="4:5">Portrait</button>';
		ministry_desk_field( 'Alt text', 'alt', $thumb ? (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ) : '', array( 'required' => true ) );
		ministry_desk_field( 'Phone', 'phone', $post ? (string) get_post_meta( $id, '_mom_phone', true ) : '' );
		ministry_desk_field( 'Email', 'email', $post ? (string) get_post_meta( $id, '_mom_email', true ) : '' );
		ministry_desk_field( 'Location', 'location', $post ? (string) get_post_meta( $id, '_mom_location', true ) : '' );
		echo '</section>';
	}
	echo '</div></div></form>';
}

/**
 * Agencies list, or the opportunities list.
 */
function ministry_desk_screen_agencies() {
	if ( 'opportunities' === ministry_desk_view() ) {
		$posts = ministry_desk_posts( 'mom_opportunity' );
		ministry_desk_head( 'Agencies & Investments', 'Investment opportunities', 'Projects shown on the Investments page.', '<a class="mom-btn mom-btn-primary" href="' . esc_url( ministry_desk_url( 'mom-agencies', array( 'view' => 'opportunity' ) ) ) . '">+ Add opportunity</a>' );
		echo '<div class="mom-table-card"><table class="mom-table"><thead><tr><th>Project</th><th>Mineral</th><th>Progress</th><th>Target</th></tr></thead><tbody>';
		foreach ( $posts as $post ) {
			echo '<tr><td><a class="title" href="' . esc_url( ministry_desk_url( 'mom-agencies', array( 'view' => 'opportunity', 'id' => $post->ID ) ) ) . '">' . esc_html( $post->post_title ) . '</a>';
		echo '<div class="mom-row-links"><a href="' . esc_url( ministry_desk_url( 'mom-agencies', array( 'view' => 'opportunity', 'id' => $post->ID ) ) ) . '">Edit</a> · <a href="' . esc_url( ministry_desk_preview_url( $post ) ) . '" target="_blank" rel="noopener">Preview</a>';
		ministry_desk_delete_link( $post->ID, ministry_desk_url( 'mom-agencies', array( 'view' => 'opportunities' ) ) );
		echo '</div></td>';
			echo '<td>' . esc_html( (string) get_post_meta( $post->ID, '_mom_mineral', true ) ) . '</td>';
			echo '<td>' . esc_html( (string) get_post_meta( $post->ID, '_mom_progress', true ) ) . '%</td>';
			echo '<td>' . esc_html( (string) get_post_meta( $post->ID, '_mom_target', true ) ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
		return;
	}
	$entities = ministry_settings()['entities'];
	ministry_desk_head( 'Agencies & Investments', 'Agencies', 'State enterprises and agencies supervised by the Ministry.', '<a class="mom-btn mom-btn-primary" href="' . esc_url( ministry_desk_url( 'mom-agencies', array( 'view' => 'edit' ) ) ) . '">+ Add agency</a>' );
	foreach ( $entities as $index => $entity ) {
		if ( ! is_array( $entity ) ) {
			continue;
		}
		echo '<div class="mom-person"><a href="' . esc_url( ministry_desk_url( 'mom-agencies', array( 'view' => 'edit', 'index' => $index ) ) ) . '"><span class="mom-avatar">' . esc_html( (string) $entity['acronym'] ) . '</span><span class="who"><b>' . esc_html( (string) $entity['name'] ) . '</b>' . esc_html( (string) $entity['desc'] ) . '</span></a>';
		$entity_preview = ministry_site_url( 'parastatals' );
		if ( ! empty( $entity['acronym'] ) ) {
			$entity_preview .= '#' . rawurlencode( sanitize_title( (string) $entity['acronym'] ) );
		}
		echo '<span class="mom-row-links"><a href="' . esc_url( $entity_preview ) . '" target="_blank" rel="noopener">Preview</a> · ';
		ministry_desk_delete_link( 0, ministry_desk_url( 'mom-agencies' ), array( 'entity_index' => $index ) );
		echo '</span></div>';
	}
}

/**
 * Agency editor.
 */
function ministry_desk_screen_agency() {
	$index    = isset( $_GET['index'] ) ? (int) $_GET['index'] : -1;
	$entities = ministry_settings()['entities'];
	$entity   = ( $index >= 0 && isset( $entities[ $index ] ) && is_array( $entities[ $index ] ) ) ? $entities[ $index ] : array( 'name' => '', 'acronym' => '', 'desc' => '', 'href' => '' );
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-agencies' ) ) . '">Agencies</a> / Edit', $entity['name'] ? (string) $entity['name'] : 'New agency' );
	ministry_desk_form_open( 'entity' );
	if ( $index >= 0 ) {
		echo '<input type="hidden" name="index" value="' . esc_attr( (string) $index ) . '">';
	}
	echo '<div class="mom-split"><div class="mom-main-col"><section class="mom-card"><h2>Identity</h2><div class="mom-grid-2">';
	ministry_desk_field( 'Name', 'name', (string) $entity['name'], array( 'required' => true ) );
	ministry_desk_field( 'Short name', 'acronym', (string) $entity['acronym'], array( 'required' => true ) );
	ministry_desk_field( 'Type', 'etype', isset( $entity['type'] ) ? (string) $entity['type'] : 'State enterprise', array( 'required' => true ) );
	ministry_desk_field( 'Website', 'website', (string) $entity['href'], array( 'type' => 'url', 'hint' => 'Used by Visit website on the Parastatals page. Include https:// or we add it.' ) );
	ministry_desk_field( 'Enabling Act', 'act', isset( $entity['act'] ) ? (string) $entity['act'] : '' );
	echo '</div></section><section class="mom-card"><h2>Logo and cover photo</h2><div class="mom-grid-2">';
	$logo_id  = isset( $entity['logoId'] ) ? (int) $entity['logoId'] : 0;
	$logo_src = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : ( isset( $entity['logoUrl'] ) ? (string) $entity['logoUrl'] : '' );
	$cover_id = isset( $entity['coverId'] ) ? (int) $entity['coverId'] : 0;
	$cover_src = $cover_id ? wp_get_attachment_image_url( $cover_id, 'medium' ) : ( isset( $entity['coverUrl'] ) ? (string) $entity['coverUrl'] : '' );
	echo '<div class="mom-field"><label>Logo</label><p class="mom-hint">Replaces the dashed logo box on the Parastatals card.</p>';
	echo '<input type="hidden" id="mom-entity-logo" name="logo_id" value="' . esc_attr( (string) $logo_id ) . '"><div class="mom-pick-preview">';
	echo $logo_src ? '<img src="' . esc_url( $logo_src ) . '" alt="">' : '';
	echo '</div><button type="button" class="mom-btn mom-btn-small mom-pick" data-target="mom-entity-logo" data-library="image" data-aspect="1:1">Choose logo</button></div>';
	echo '<div class="mom-field"><label>Cover photo</label><p class="mom-hint">Wide photo at the top of the card.</p>';
	echo '<input type="hidden" id="mom-entity-cover" name="cover_id" value="' . esc_attr( (string) $cover_id ) . '"><div class="mom-pick-preview">';
	echo $cover_src ? '<img src="' . esc_url( $cover_src ) . '" alt="">' : '';
	echo '</div><button type="button" class="mom-btn mom-btn-small mom-pick" data-target="mom-entity-cover" data-library="image" data-aspect="16:9">Choose photo</button></div>';
	echo '</div></section><section class="mom-card"><h2>Mandate</h2>';
	ministry_desk_editor( 'mandate', (string) $entity['desc'] );
	echo '</section></div><div class="mom-side-col"><section class="mom-side-card"><h2>Publish</h2><div class="mom-note mom-note-gold"><p>Entity details are checked by the Communications Director.</p></div>';
	$agency_preview = ministry_site_url( 'parastatals' );
	if ( ! empty( $entity['acronym'] ) ) {
		$agency_preview .= '#' . rawurlencode( sanitize_title( (string) $entity['acronym'] ) );
	}
	echo ministry_desk_preview_action( $agency_preview );
	echo '<button class="mom-btn mom-btn-primary">Update</button>';
	if ( $index >= 0 ) {
		echo '<input type="hidden" name="entity_index" value="' . esc_attr( (string) $index ) . '">';
		echo '<p><button class="mom-btn mom-btn-danger" name="form" value="delete" onclick="return confirm(\'Remove this agency from the website?\')">Delete</button></p>';
	}
	echo '</section><section class="mom-side-card"><h2>Display</h2>';
	echo '<label class="mom-toggle"><input type="checkbox" name="show" value="1" ' . checked( ! isset( $entity['show'] ) || '1' === $entity['show'], true, false ) . '> Show on Parastatals & agencies</label>';
	echo '<label class="mom-toggle"><input type="checkbox" name="invest" value="1" ' . checked( ! empty( $entity['invest'] ), true, false ) . '> Show on Investment entities</label>';
	ministry_desk_field( 'Order', 'sort', isset( $entity['order'] ) ? (string) $entity['order'] : '1', array( 'type' => 'number' ) );
	echo '</section><section class="mom-side-card"><h2>Board & contact</h2>';
	ministry_desk_field( 'Chairperson', 'chair', isset( $entity['chair'] ) ? (string) $entity['chair'] : '' );
	ministry_desk_field( 'Contact email', 'email', isset( $entity['email'] ) ? (string) $entity['email'] : '', array( 'type' => 'email' ) );
	$members = isset( $entity['members'] ) && is_array( $entity['members'] ) ? $entity['members'] : array();
	if ( ! $members && isset( $entity['acronym'] ) && 0 === strcasecmp( (string) $entity['acronym'], 'MAB' ) ) {
		$members = isset( ministry_settings()['mabMembers'] ) && is_array( ministry_settings()['mabMembers'] ) ? ministry_settings()['mabMembers'] : ministry_default_settings()['mabMembers'];
	}
	if ( ! $members ) {
		$members = array( array( 'name' => '', 'role' => '' ) );
	}
	echo '<div data-repeater><label class="mom-label">Board members</label><p class="mom-hint">Used on the Parastatals page for the Mining Affairs Board. Role can be Chairperson or Secretary.</p>';
	foreach ( array_values( $members ) as $i => $row ) {
		$row = is_array( $row ) ? $row : array();
		echo '<div class="mom-rowline" data-row><input name="members[' . esc_attr( (string) $i ) . '][name]" value="' . esc_attr( isset( $row['name'] ) ? (string) $row['name'] : '' ) . '" placeholder="Name"><input name="members[' . esc_attr( (string) $i ) . '][role]" value="' . esc_attr( isset( $row['role'] ) ? (string) $row['role'] : '' ) . '" placeholder="Role"><button type="button" data-remove-row>&times;</button></div>';
	}
	echo '<button type="button" class="mom-btn mom-btn-small" data-add-row>+ Add member</button></div>';
	echo '</section></div></div></form>';
}

/**
 * One investment opportunity.
 */
function ministry_desk_screen_opportunity() {
	$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$post = $id ? get_post( $id ) : null;
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-agencies', array( 'view' => 'opportunities' ) ) ) . '">Investment opportunities</a> / Edit', $post ? $post->post_title : 'New opportunity' );
	ministry_desk_form_open( 'opportunity' );
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $id ) . '"><section class="mom-card">';
	ministry_desk_field( 'Project', 'post_title', $post ? $post->post_title : '', array( 'required' => true ) );
	ministry_desk_field( 'Summary', 'post_excerpt', $post ? $post->post_excerpt : '', array( 'type' => 'textarea' ) );
	echo '<div class="mom-grid-2">';
	ministry_desk_field( 'Mineral', 'mineral', $post ? (string) get_post_meta( $id, '_mom_mineral', true ) : '' );
	ministry_desk_field( 'Region', 'region', $post ? (string) get_post_meta( $id, '_mom_region', true ) : '' );
	ministry_desk_field( 'Progress %', 'progress', $post ? (string) get_post_meta( $id, '_mom_progress', true ) : '', array( 'type' => 'number' ) );
	ministry_desk_field( 'Target', 'target', $post ? (string) get_post_meta( $id, '_mom_target', true ) : '' );
	ministry_desk_field( 'Agency short name', 'entity', $post ? (string) get_post_meta( $id, '_mom_entity', true ) : '' );
	echo '</div><p class="mom-actions">' . ( $post ? ministry_desk_preview_action( ministry_desk_preview_url( $post ) ) : '' ) . '<button class="mom-btn mom-save-draft" name="intent" value="draft">Save draft</button><button class="mom-btn mom-btn-primary" name="intent" value="' . ( ministry_desk_can_publish() ? 'publish' : 'pending' ) . '">' . ( ministry_desk_can_publish() ? 'Publish' : 'Submit for review' ) . '</button></p></section></form>';
}

/**
 * Enquiry inbox.
 */
function ministry_desk_screen_enquiries() {
	$type  = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
	$id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	if ( $id ) {
		$opened = get_post( $id );
		if ( $opened && ministry_desk_is_registration_type( (string) get_post_meta( $opened->ID, '_mom_type', true ) ) ) {
			wp_safe_redirect( ministry_desk_url( 'mom-events', array( 'view' => 'registrations', 'id' => $id ) ) );
			exit;
		}
	}
	$raw    = ministry_desk_posts( 'mom_enquiry', array( 'posts_per_page' => 200 ) );
	$posts  = array();
	foreach ( $raw as $post ) {
		if ( ministry_desk_is_registration_type( (string) get_post_meta( $post->ID, '_mom_type', true ) ) ) {
			continue;
		}
		$posts[] = $post;
	}
	$labels = array(
		'minister'   => 'Ask the Minister',
		'complaint'  => 'Complaint',
		'investor'   => 'Investor interest',
		'general'    => 'General',
		'compliment' => 'Compliment',
	);
	$export = admin_url( 'admin-post.php?action=ministry_desk_save&form=export&_wpnonce=' . wp_create_nonce( 'ministry_desk_save' ) . ( $type ? '&type=' . rawurlencode( $type ) : '' ) );
	ministry_desk_head( 'Enquiries & Forms', 'Enquiries & Forms', 'Messages from the website’s forms. Personal details stay in this desk.', '<a class="mom-btn" href="' . esc_url( $export ) . '">Export CSV</a>' );
	echo '<div class="mom-pills">';
	echo '<a class="mom-pill' . ( '' === $type ? ' is-on' : '' ) . '" href="' . esc_url( ministry_desk_url( 'mom-enquiries' ) ) . '">All (' . esc_html( (string) count( $posts ) ) . ')</a>';
	foreach ( $labels as $key => $label ) {
		echo '<a class="mom-pill' . ( $type === $key ? ' is-on' : '' ) . '" href="' . esc_url( ministry_desk_url( 'mom-enquiries', array( 'type' => $key ) ) ) . '">' . esc_html( $label ) . '</a>';
	}
	echo '</div><div class="mom-split"><div class="mom-table-card"><table class="mom-table"><thead><tr><th>Form</th><th>Subject</th><th>From</th><th>Status</th><th>Received</th></tr></thead><tbody>';
	foreach ( $posts as $post ) {
		$item_type = (string) get_post_meta( $post->ID, '_mom_type', true );
		if ( $type && $item_type !== $type ) {
			continue;
		}
		$status = (string) get_post_meta( $post->ID, '_mom_status', true );
		$tone   = 'done' === $status || 'replied' === $status ? 'green' : ( 'assigned' === $status ? 'blue' : 'gold' );
		echo '<tr><td>' . esc_html( isset( $labels[ $item_type ] ) ? $labels[ $item_type ] : 'Enquiry' ) . '</td>';
		echo '<td><a class="title" href="' . esc_url( ministry_desk_url( 'mom-enquiries', array( 'id' => $post->ID, 'type' => $type ) ) ) . '">' . esc_html( $post->post_title ) . '</a>';
		echo '<div class="mom-row-links"><a href="' . esc_url( ministry_desk_url( 'mom-enquiries', array( 'id' => $post->ID, 'type' => $type ) ) ) . '">View</a>';
		ministry_desk_delete_link( $post->ID, ministry_desk_url( 'mom-enquiries', $type ? array( 'type' => $type ) : array() ) );
		echo '</div></td>';
		echo '<td>' . esc_html( (string) get_post_meta( $post->ID, '_mom_name', true ) ) . '<div class="mom-hint">' . esc_html( (string) get_post_meta( $post->ID, '_mom_email', true ) ) . '</div></td><td>';
		ministry_desk_badge( $status ? ucfirst( $status ) : 'New', $tone );
		echo '</td><td>' . esc_html( human_time_diff( strtotime( $post->post_date ) ) ) . ' ago</td></tr>';
	}
	echo '</tbody></table></div>';
	$current = $id ? get_post( $id ) : null;
	echo '<div>';
	if ( $current && 'mom_enquiry' === $current->post_type ) {
		ministry_desk_form_open( 'enquiry' );
		echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $current->ID ) . '">';
		$item_type = (string) get_post_meta( $current->ID, '_mom_type', true );
		echo '<section class="mom-side-card"><h2>' . esc_html( $current->post_title ) . '</h2>';
		echo '<p><strong>Form</strong><br>' . esc_html( isset( $labels[ $item_type ] ) ? $labels[ $item_type ] : $item_type ) . '</p>';
		echo '<p><strong>Received</strong><br>' . esc_html( ministry_desk_date( $current->post_date ) ) . '</p>';
		echo '<p><strong>Name</strong><br>' . esc_html( (string) get_post_meta( $current->ID, '_mom_name', true ) ) . '</p>';
		echo '<p><strong>Email</strong><br>' . esc_html( (string) get_post_meta( $current->ID, '_mom_email', true ) ) . '</p>';
		echo '<p><strong>Phone</strong><br>' . esc_html( (string) get_post_meta( $current->ID, '_mom_phone', true ) ) . '</p>';
		echo '<p><strong>Office</strong><br>' . esc_html( (string) get_post_meta( $current->ID, '_mom_office', true ) ) . '</p>';
		$org = (string) get_post_meta( $current->ID, '_mom_org', true );
		if ( $org ) {
			echo '<p><strong>Organisation</strong><br>' . esc_html( $org ) . '</p>';
		}
		$event = (string) get_post_meta( $current->ID, '_mom_event', true );
		if ( $event ) {
			echo '<p><strong>Event</strong><br>' . esc_html( $event ) . '</p>';
		}
		echo '<p><strong>Message</strong></p><div class="mom-quote">' . esc_html( $current->post_content ) . '</div>';
		ministry_desk_field( 'Reply due by', 'due', (string) get_post_meta( $current->ID, '_mom_due', true ), array( 'type' => 'date' ) );
		ministry_desk_field( 'Internal note', 'note', (string) get_post_meta( $current->ID, '_mom_note', true ), array( 'type' => 'textarea', 'hint' => 'Only staff can see this.' ) );
		$email = (string) get_post_meta( $current->ID, '_mom_email', true );
		echo '<p class="mom-actions"><a class="mom-btn mom-btn-primary" href="mailto:' . esc_attr( $email ) . '?subject=' . rawurlencode( $current->post_title ) . '">Reply by email</a>';
		echo '<button class="mom-btn" name="mark" value="done">Mark done</button></p>';
		echo '<p class="mom-row-links">';
		ministry_desk_delete_link( $current->ID, ministry_desk_url( 'mom-enquiries', $type ? array( 'type' => $type ) : array() ) );
		echo '</p></section></form>';
	}
	echo '</div></div>';
	echo '<div class="mom-note mom-note-blue"><p>Forms collect only what’s needed, ask for consent, and are protected by spam checks — in line with the Cyber and Data Protection Act [Chapter 12:07].</p></div>';
}

/**
 * Website facts.
 */
function ministry_desk_screen_facts() {
	if ( ! ministry_desk_can( 'facts' ) ) {
		echo '<div class="mom-note mom-note-gold"><p>Website facts are edited by the Communications Director and administrators. You can view this screen.</p></div>';
	}
	$settings = ministry_settings();
	$meta     = ministry_desk_meta();
	$stats    = isset( $settings['homeStats'] ) && is_array( $settings['homeStats'] ) ? $settings['homeStats'] : array();
	$social   = isset( $settings['socialLinks'] ) && is_array( $settings['socialLinks'] ) ? $settings['socialLinks'] : array();
	$save = ministry_desk_can( 'facts' ) ? '<button class="mom-btn mom-btn-primary" form="mom-desk-form">Save settings</button>' : '';
	ministry_desk_head( 'Website Facts & Settings', 'Website Facts & Settings', 'Facts entered once here are used everywhere they appear on the site.', $save );
	ministry_desk_form_open( 'facts' );
	echo '<div class="mom-split"><div class="mom-main-col">';
	echo '<section class="mom-card" id="facts"><h2>Mining sector figures (Home page)</h2>';
	ministry_desk_field( 'Ministry name', 'ministryName', (string) $settings['ministryName'] );
	echo '<div class="mom-table-card"><table class="mom-table"><thead><tr><th>Figure</th><th>Label</th><th>Source</th><th>Last checked</th></tr></thead><tbody>';
	foreach ( array_values( $stats ) as $i => $stat ) {
		if ( ! is_array( $stat ) ) {
			continue;
		}
		echo '<tr><td><input name="stats[' . esc_attr( (string) $i ) . '][value]" value="' . esc_attr( (string) $stat['value'] ) . '"></td>';
		echo '<td><input name="stats[' . esc_attr( (string) $i ) . '][label]" value="' . esc_attr( (string) $stat['label'] ) . '"><input type="hidden" name="stats[' . esc_attr( (string) $i ) . '][hint]" value="' . esc_attr( isset( $stat['hint'] ) ? (string) $stat['hint'] : '' ) . '"></td>';
		echo '<td><input name="stats[' . esc_attr( (string) $i ) . '][source]" value="' . esc_attr( isset( $stat['source'] ) ? (string) $stat['source'] : '' ) . '"></td>';
		echo '<td><input name="stats[' . esc_attr( (string) $i ) . '][checked]" value="' . esc_attr( isset( $stat['checked'] ) ? (string) $stat['checked'] : '' ) . '"></td></tr>';
	}
	echo '</tbody></table></div><p class="mom-hint">Each figure needs a source. Figures older than 12 months are flagged on the Dashboard.</p></section>';
	echo '<section class="mom-card" id="contact"><h2>Contact details</h2><div class="mom-grid-2">';
	ministry_desk_field( 'Main phone', 'phone', (string) $settings['headOfficePhone'] );
	ministry_desk_field( 'Fax', 'fax', (string) $meta['fax'] );
	ministry_desk_field( 'Toll-free line', 'tollFree', (string) $settings['tollFree'], array( 'hint' => 'Shown as 08004577 · landline only.' ) );
	ministry_desk_field( 'General email', 'email', (string) $settings['headOfficeEmail'], array( 'type' => 'email' ) );
	ministry_desk_field( 'Ask the Minister email', 'ministerEmail', (string) $settings['ministerEmail'], array( 'type' => 'email' ) );
	ministry_desk_field( 'WhatsApp number', 'whatsappNumber', isset( $settings['whatsappNumber'] ) ? (string) $settings['whatsappNumber'] : '', array( 'hint' => 'Shown on Contact when set. Leave empty to keep “number to be confirmed”.' ) );
	ministry_desk_field( 'Head office floor', 'floor', (string) $meta['floor'] );
	echo '</div><div class="mom-note mom-note-gold"><p>2026 documents say both 6th and 7th floor — please confirm.</p></div>';
	ministry_desk_field( 'Opening hours', 'officeHours', (string) $settings['officeHours'], array( 'hint' => 'Used by every office unless the office sets its own.' ) );
	echo '<div data-repeater><label class="mom-label">Social media</label>';
	foreach ( array_values( $social ) as $i => $link ) {
		if ( ! is_array( $link ) ) {
			continue;
		}
		echo '<div class="mom-rowline" data-row><input name="social[' . esc_attr( (string) $i ) . '][label]" value="' . esc_attr( (string) $link['label'] ) . '"><input name="social[' . esc_attr( (string) $i ) . '][href]" value="' . esc_attr( (string) $link['href'] ) . '"></div>';
	}
	echo '</div></section>';
	echo '<section class="mom-card" id="home"><h2>Homepage slider</h2>';
	$banners = ministry_banners();
	if ( ! $banners ) {
		$banners = array( null, null, null );
	}
	foreach ( $banners as $i => $banner ) {
		$bid = $banner ? $banner->ID : 0;
		$img = $bid ? (int) get_post_thumbnail_id( $bid ) : 0;
		echo '<div class="mom-rowline"><span>' . esc_html( (string) ( $i + 1 ) ) . '</span><input type="hidden" name="banners[' . esc_attr( (string) $i ) . '][id]" value="' . esc_attr( (string) $bid ) . '"><input type="hidden" id="mom-banner-' . esc_attr( (string) $i ) . '" name="banners[' . esc_attr( (string) $i ) . '][image]" value="' . esc_attr( (string) $img ) . '"><input name="banners[' . esc_attr( (string) $i ) . '][title]" value="' . esc_attr( $banner ? $banner->post_title : '' ) . '"><button type="button" class="mom-btn mom-btn-small mom-pick" data-target="mom-banner-' . esc_attr( (string) $i ) . '" data-library="image" data-aspect="16:9">Photo</button></div>';
	}
	echo '<p class="mom-hint">3 slides maximum. Slides do not move on their own unless the visitor presses play.</p></section>';
	echo '<section class="mom-card" id="look"><h2>Site appearance</h2><div class="mom-swatches">';
	foreach ( array( 'a' => 'Navy blue (Variation A)', 'b' => 'Black (Variation B)' ) as $key => $label ) {
		$on = $meta['variation'] === $key;
		echo '<label class="mom-swatch' . ( $on ? ' is-on' : '' ) . '"><span class="bar mom-bar-' . esc_attr( $key ) . '"></span><input type="radio" name="variation" value="' . esc_attr( $key ) . '" ' . checked( $on, true, false ) . '> ' . esc_html( $label ) . ( 'a' === $key ? ' <span class="mom-badge mom-badge-green">Live</span>' : '' ) . '</label>';
	}
	echo '</div><p class="mom-hint">Switching changes colours only, never content. Both meet WCAG 2.2 AA contrast.</p></section>';
	echo '<section class="mom-card" id="assistant"><h2>Ask Assistant</h2>';
	ministry_desk_field(
		'Show on the public site',
		'assistantEnabled',
		isset( $settings['assistantEnabled'] ) ? (string) $settings['assistantEnabled'] : '1',
		array(
			'type'    => 'select',
			'options' => array(
				'1' => 'On — widget in the bottom right, header button opens it',
				'0' => 'Off — header button goes to Frequently asked questions',
			),
		)
	);
	ministry_desk_field( 'Script address', 'assistantScriptUrl', isset( $settings['assistantScriptUrl'] ) ? (string) $settings['assistantScriptUrl'] : '', array( 'type' => 'url', 'hint' => 'The widget.js file from the assistant host.' ) );
	ministry_desk_field( 'Tenant', 'assistantTenant', isset( $settings['assistantTenant'] ) ? (string) $settings['assistantTenant'] : '', array( 'hint' => 'data-tenant on the embed, currently ministryofmines.' ) );
	echo '</section>';
	echo '<section class="mom-card" id="maps"><h2>Maps</h2>';
	echo '<p class="mom-hint">Office cards show an OpenFreeMap preview of the street address. Clicking the thumbnail opens that same address in Google Maps.</p>';
	echo '</section>';
	echo '<section class="mom-card" id="downloads"><h2>Downloads</h2>';
	$downloads_on = ( isset( $settings['downloadsEnabled'] ) ? (string) $settings['downloadsEnabled'] : '1' ) === '1';
	echo '<label class="mom-toggle"><input type="checkbox" name="downloadsEnabled" value="1" ' . checked( $downloads_on, true, false ) . '> Allow PDF downloads on the public site</label>';
	echo '<p class="mom-hint">When this is off, download buttons stay visible but do not start a file.</p></section></div>';
	echo '<div class="mom-side-col"><section class="mom-side-card"><h2>Where these appear</h2><p>Figures: Home. Phone and email: header, footer, Contact. Hours: Offices, Contact. Assistant: every public page.</p></section>';
	echo '<section class="mom-side-card"><h2>Who can edit</h2><p>Administrators and the Communications Director. Every change is logged with name and time.</p>';
	if ( ministry_desk_can( 'users' ) ) {
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=ministry-settings&classic=1' ) ) . '">More settings</a> — page intros and menus.</p>';
	}
	echo '</section><section class="mom-side-card"><h2>Ministry identity</h2><p>' . esc_html( (string) $settings['ministryName'] ) . '</p>';
	$logo = ! empty( $settings['logoUrl'] ) ? $settings['logoUrl'] : get_template_directory_uri() . '/site/images/logo-full.png';
	echo '<p>Logo</p><img src="' . esc_url( $logo ) . '" alt="" style="width:72px"></section></div></div></form>';
}

/**
 * WordPress pages.
 */
function ministry_desk_screen_pages() {
	$posts = ministry_desk_posts( 'page', array( 'orderby' => 'title', 'order' => 'ASC' ) );
	ministry_desk_head( 'Pages', 'Pages', 'Standing pages such as About, Contact, and Services.', '<a class="mom-btn mom-btn-primary" href="' . esc_url( ministry_desk_url( 'mom-pages', array( 'view' => 'edit' ) ) ) . '">+ Add page</a>' );
	echo '<div class="mom-table-card"><table class="mom-table"><thead><tr><th>Page</th><th>Status</th><th>Updated</th></tr></thead><tbody>';
	foreach ( $posts as $post ) {
		$status = ministry_desk_status_label( $post );
		echo '<tr><td><a class="title" href="' . esc_url( ministry_desk_url( 'mom-pages', array( 'view' => 'edit', 'id' => $post->ID ) ) ) . '">' . esc_html( $post->post_title ) . '</a>';
		echo '<div class="mom-row-links"><a href="' . esc_url( ministry_desk_url( 'mom-pages', array( 'view' => 'edit', 'id' => $post->ID ) ) ) . '">Edit</a>';
		ministry_desk_delete_link( $post->ID, ministry_desk_url( 'mom-pages' ) );
		echo '</div></td><td>';
		ministry_desk_badge( $status[0], $status[1] );
		echo '</td><td>' . esc_html( ministry_desk_date( $post->post_modified ) ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/**
 * Simple page editor.
 */
function ministry_desk_screen_page() {
	$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$post = $id ? get_post( $id ) : null;
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-pages' ) ) . '">Pages</a> / Edit', $post ? $post->post_title : 'New page' );
	ministry_desk_form_open( 'page' );
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $id ) . '"><div class="mom-split"><div class="mom-main-col"><section class="mom-card">';
	ministry_desk_field( 'Title', 'post_title', $post ? $post->post_title : '', array( 'required' => true ) );
	ministry_desk_field( 'Summary', 'post_excerpt', $post ? $post->post_excerpt : '', array( 'type' => 'textarea' ) );
	echo '<label class="mom-label">Body</label>';
	ministry_desk_editor( 'post_content', $post ? $post->post_content : '' );
	echo '</section></div><div class="mom-side-col">';
	ministry_desk_publish_card( $post, 'Page intros for the designed sections also live under Website Facts → More settings.' );
	ministry_desk_languages_card( $id, 'mom-pages' );
	echo '</div></div></form>';
}

/**
 * Users, the permission matrix, and the activity log.
 */
function ministry_desk_screen_users() {
	if ( ! ministry_desk_can( 'users' ) ) {
		wp_die( esc_html__( 'Only an administrator can open Users & Roles.', 'ministry-content' ) );
	}
	$edit_id = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
	$edit    = $edit_id ? get_userdata( $edit_id ) : null;
	$users   = get_users( array( 'number' => 80 ) );
	ministry_desk_head( 'Users & Roles', 'Users & Roles', 'Preset roles follow the table. Custom lets you tick permissions for one person.', '<a class="mom-btn mom-btn-primary" href="#add-user">+ Add user</a>' );
	echo '<div class="mom-split"><div><div class="mom-table-card"><table class="mom-table"><thead><tr><th>Name</th><th>Role</th><th>Email</th><th>Last login</th><th></th></tr></thead><tbody>';
	foreach ( $users as $user ) {
		$last = (int) get_user_meta( $user->ID, 'last_login', true );
		echo '<tr><td><strong>' . esc_html( $user->display_name ) . '</strong></td><td>';
		ministry_desk_badge( ministry_desk_role_label( $user ), 'blue' );
		echo '</td><td>' . esc_html( $user->user_email ) . '</td><td>' . esc_html( $last ? ministry_desk_date( gmdate( 'Y-m-d H:i:s', $last ) ) : '—' ) . '</td>';
		echo '<td><a href="' . esc_url( ministry_desk_url( 'mom-users', array( 'user' => $user->ID ) ) ) . '#add-user">Edit</a></td></tr>';
	}
	echo '</tbody></table></div>';
	$role    = $edit ? ministry_desk_user_role( $edit ) : 'author';
	$checked = $edit ? ministry_desk_user_caps( $edit ) : ministry_desk_role_preset( 'author' );
	echo '<section class="mom-card" id="add-user"><h2>' . ( $edit ? 'Edit user' : 'Add user' ) . '</h2>';
	ministry_desk_form_open( 'user' );
	if ( $edit ) {
		echo '<input type="hidden" name="user_id" value="' . esc_attr( (string) $edit->ID ) . '">';
		echo '<p class="mom-hint"><a href="' . esc_url( ministry_desk_url( 'mom-users' ) ) . '#add-user">Cancel and add someone new</a></p>';
	}
	ministry_desk_field( 'Name', 'name', $edit ? $edit->display_name : '' );
	ministry_desk_field( 'Work email', 'email', $edit ? $edit->user_email : '', array( 'type' => 'email', 'required' => true, 'hint' => $edit ? 'Saving keeps this account and applies the role below.' : 'They receive a link to set a password.' ) );
	ministry_desk_field( 'Role', 'role', $role, array( 'type' => 'select', 'options' => ministry_desk_role_options() ) );
	echo '<div class="mom-perm-box" data-custom-caps' . ( 'mom_custom' === $role ? '' : ' hidden' ) . '><h3>Custom permissions</h3><p class="mom-hint">Tick what this person may do. “Add users, plugins, themes” stays Administrator-only.</p><div class="mom-perm-grid">';
	foreach ( ministry_desk_cap_catalog() as $key => $row ) {
		if ( ! empty( $row['admin_only'] ) ) {
			continue;
		}
		echo '<label class="mom-perm"><input type="checkbox" name="caps[]" value="' . esc_attr( $key ) . '" ' . checked( in_array( $key, $checked, true ), true, false ) . '> ' . esc_html( $row['label'] ) . '</label>';
	}
	echo '</div></div>';
	echo '<button class="mom-btn mom-btn-primary">' . ( $edit ? 'Save user' : 'Add user' ) . '</button></form></section>';
	$roles = array( 'author', 'mom_translator', 'editor', 'administrator' );
	echo '<section class="mom-card"><h2>What each role can do</h2><div class="mom-table-card"><table class="mom-table"><thead><tr><th>Permission</th><th>Content Officer</th><th>Translator</th><th>Comms Director</th><th>Administrator</th></tr></thead><tbody>';
	foreach ( ministry_desk_cap_catalog() as $key => $row ) {
		echo '<tr><td>' . esc_html( $row['label'] ) . '</td>';
		foreach ( $roles as $role_key ) {
			$yes = in_array( $key, ministry_desk_role_preset( $role_key ), true );
			echo '<td>' . ( $yes ? '<span class="mom-ok">✓</span>' : '—' ) . '</td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table></div><p class="mom-hint">Custom is set on the person, not as a shared role. Changing a preset role takes effect the next time they open a desk screen.</p></section></div><div class="mom-side-col"><section class="mom-side-card"><h2>Security</h2><ul class="mom-check"><li><span class="mom-ok">✓</span> Sign-in asks for a work email and a password</li><li><span class="mom-ok">✓</span> Accounts use ministry email addresses</li><li><span class="mom-ok">✓</span> Sessions end when the browser is closed</li><li><span class="mom-ok">✓</span> Leavers are disabled, never deleted — their work stays attributed</li></ul></section>';
	echo '<section class="mom-side-card"><h2>Activity log</h2>';
	$log = array_reverse( (array) ministry_desk_meta()['log'] );
	if ( ! $log ) {
		echo '<p class="mom-hint">Saves from this desk will be listed here.</p>';
	}
	foreach ( array_slice( $log, 0, 12 ) as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}
		echo '<div class="mom-wait"><strong>' . esc_html( wp_date( 'j M, H:i', (int) $entry['t'] ) ) . '</strong><br>' . esc_html( (string) $entry['m'] ) . '</div>';
	}
	echo '</section></div></div>';
}

/**
 * Compare a submission with what is live.
 */
function ministry_desk_screen_review() {
	if ( isset( $_GET['person'] ) ) {
		ministry_desk_screen_review_person( sanitize_text_field( wp_unslash( $_GET['person'] ) ) );
		return;
	}
	$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$post = $id ? get_post( $id ) : null;
	if ( ! $post ) {
		ministry_desk_head( '', 'Review', 'That item is no longer waiting.' );
		return;
	}
	$revs = wp_get_post_revisions( $id );
	$prev = $revs ? reset( $revs ) : null;
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-desk' ) ) . '">Dashboard</a> / Review', 'Review: ' . $post->post_title, 'Submitted by ' . get_the_author_meta( 'display_name', $post->post_author ) );
	ministry_desk_form_open( 'review' );
	echo '<input type="hidden" name="post_id" value="' . esc_attr( (string) $id ) . '">';
	echo '<div class="mom-split"><div class="mom-main-col"><p class="mom-actions">';
	if ( ministry_desk_can_publish() ) {
		echo '<button class="mom-btn" name="decision" value="changes">Request changes</button><button class="mom-btn mom-btn-primary" name="decision" value="approve">Approve & publish</button>';
	}
	echo '</p><div class="mom-compare"><section class="pane"><h2>Live now</h2>';
	echo $prev ? '<p class="mom-diff">' . ministry_desk_word_diff( $prev->post_excerpt, '' ) . '</p>' : '<p class="mom-hint">Nothing is live yet.</p>';
	echo '</section><section class="pane"><h2>Proposed</h2><p>' . esc_html( $post->post_excerpt ) . '</p><div>' . wp_kses_post( wpautop( $post->post_content ) ) . '</div></section></div>';
	if ( $prev ) {
		echo '<section class="mom-card"><h2>Text changes</h2><p class="mom-diff">' . ministry_desk_word_diff( $prev->post_excerpt . ' ' . wp_strip_all_tags( $prev->post_content ), $post->post_excerpt . ' ' . wp_strip_all_tags( $post->post_content ) ) . '</p></section>';
	}
	echo '<section class="mom-card"><h2>Comment to the author</h2><textarea name="note" placeholder="Optional — what should they change?"></textarea></section></div>';
	echo '<div class="mom-side-col"><section class="mom-side-card"><h2>Checks</h2><ul class="mom-check"><li><span class="mom-ok">✓</span> Title is present</li><li><span class="' . ( $post->post_excerpt ? 'mom-ok' : 'mom-warn' ) . '">✓</span> Summary</li></ul></section>';
	echo '<section class="mom-side-card"><h2>Audit trail</h2><p>Every approval is recorded with name, time, and what changed.</p></section></div></div></form>';
}

/**
 * @param string $key Pending person key.
 */
function ministry_desk_screen_review_person( $key ) {
	$meta    = ministry_desk_meta();
	$pending = isset( $meta['person_pending'][ $key ] ) ? $meta['person_pending'][ $key ] : null;
	if ( ! is_array( $pending ) || empty( $pending['row'] ) ) {
		ministry_desk_head( '', 'Review', 'That person is no longer waiting.' );
		return;
	}
	$row     = $pending['row'];
	$leaders = ministry_settings()['leaders'];
	$live    = is_numeric( $key ) && isset( $leaders[ (int) $key ] ) ? $leaders[ (int) $key ] : array( 'title' => '', 'bio' => '', 'name' => '' );
	ministry_desk_head( '<a href="' . esc_url( ministry_desk_url( 'mom-desk' ) ) . '">Dashboard</a> / Review', 'Review: ' . (string) $row['name'], 'Changes submitted by ' . (string) $pending['user'] );
	ministry_desk_form_open( 'review' );
	echo '<input type="hidden" name="person_key" value="' . esc_attr( $key ) . '">';
	echo '<div class="mom-split"><div class="mom-main-col"><p class="mom-actions">';
	if ( ministry_desk_can_publish() ) {
		echo '<button class="mom-btn" name="decision" value="changes">Request changes</button><button class="mom-btn mom-btn-primary" name="decision" value="approve">Approve & publish</button>';
	}
	echo '</p><div class="mom-compare"><section class="pane"><h2>Live now</h2>';
	$live_photo = is_array( $live ) ? ministry_desk_leader_photo( $live ) : '';
	echo $live_photo ? '<img src="' . esc_url( $live_photo ) . '" alt="">' : '<p class="mom-hint">Photo to be supplied</p>';
	echo '</section><section class="pane"><h2>Proposed</h2>';
	$new_photo = ministry_desk_leader_photo( $row );
	echo $new_photo ? '<img src="' . esc_url( $new_photo ) . '" alt="">' : '<p class="mom-hint">Photo to be supplied</p>';
	echo '</section></div><section class="mom-card"><h2>Text changes</h2>';
	echo '<p><strong>Job title</strong><br><span class="mom-diff">' . ministry_desk_word_diff( isset( $live['title'] ) ? (string) $live['title'] : '', (string) $row['title'] ) . '</span></p>';
	echo '<p><strong>Short bio</strong><br><span class="mom-diff">' . ministry_desk_word_diff( isset( $live['bio'] ) ? (string) $live['bio'] : '', (string) $row['bio'] ) . '</span></p></section>';
	echo '<section class="mom-card"><h2>Comment to the author</h2><textarea name="note" placeholder="Optional"></textarea></section></div>';
	echo '<div class="mom-side-col"><section class="mom-side-card"><h2>Audit trail</h2><p>Every approval is recorded with name, time, and what changed — kept for 7 years in line with government record-keeping.</p></section></div></div></form>';
}
