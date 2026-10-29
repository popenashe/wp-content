<?php
/**
 * First-run content copied from the Ministry reference data.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create pages, records, and the primary menu once.
 */
function ministry_seed() {
	if ( get_option( 'mom_seeded' ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$images = ministry_seed_images();
	ministry_seed_settings( $images );
	ministry_seed_departments();
	ministry_seed_news( $images );
	ministry_seed_events( $images );
	ministry_seed_documents();
	ministry_seed_gallery( $images );
	ministry_seed_albums();
	ministry_seed_banners( $images );
	ministry_seed_opportunities( $images );
	ministry_seed_faqs();
	$pages = ministry_seed_pages();
	ministry_seed_menu( $pages );

	update_option( 'blogname', 'Ministry of Mines and Mining Development' );
	update_option( 'blogdescription', 'Sustainable Mining: Our Legacy' );
	update_option( 'mom_seeded', 1 );
}

/**
 * Public folder from the Ministry reference project, if it is on this machine.
 */
function ministry_reference_public_dir() {
	$dir = '/Users/vision-ai/Desktop/Ministry2/Ministry/Mainsite/mainsite/public';
	return is_dir( $dir ) ? $dir : '';
}

/**
 * Import reference images once per request.
 *
 * @return array<string, int>
 */
function ministry_seed_images() {
	$files = array(
		'coat-of-arms.png',
		'images/open-pit.jpg',
		'images/dump-trucks.jpg',
		'images/excavators.jpg',
		'images/excavator-pit.jpg',
		'images/quarry.jpg',
		'images/haul-truck.jpg',
		'images/tunnel.jpg',
		'images/gold.jpg',
		'images/ore.jpg',
		'images/terraces.jpg',
		'g1.jpg',
		'g2.jpg',
		'g3.jpg',
		'g4.jpg',
		'g5.jpg',
		'leaders/kambamura.webp',
		'leaders/moyo.jpeg',
		'leaders/makwiranzou.webp',
		'leaders/wushe.webp',
	);
	$ids  = array();
	$base = ministry_reference_public_dir();
	foreach ( $files as $file ) {
		$ids[ $file ] = 0;
		if ( ! $base ) {
			continue;
		}
		$path = $base . '/' . $file;
		if ( ! file_exists( $path ) ) {
			continue;
		}
		$tmp = wp_tempnam( $path );
		if ( ! $tmp || ! copy( $path, $tmp ) ) {
			continue;
		}
		$loaded = media_handle_sideload(
			array(
				'name'     => basename( $path ),
				'tmp_name' => $tmp,
			),
			0,
			basename( $path )
		);
		if ( is_wp_error( $loaded ) ) {
			wp_delete_file( $tmp );
			continue;
		}
		$ids[ $file ] = (int) $loaded;
	}
	return $ids;
}

/**
 * Store default settings and point portraits at imported media.
 *
 * @param array<string, int> $images Attachment map.
 */
function ministry_seed_settings( $images ) {
	$settings = ministry_default_settings();
	if ( ! empty( $images['coat-of-arms.png'] ) ) {
		$settings['logoId'] = $images['coat-of-arms.png'];
		$url                 = wp_get_attachment_url( $images['coat-of-arms.png'] );
		if ( $url ) {
			$settings['logoUrl'] = $url;
		}
	}
	$portraits = array(
		'Hon. Dr. Eng. Polite Kambamura' => 'leaders/kambamura.webp',
		'Hon. Eng. Fred Moyo'            => 'leaders/moyo.jpeg',
		'Hon. Dr. Eng. Caleb Makwiranzou' => 'leaders/makwiranzou.webp',
		'Dr. Thomas Utete Wushe'         => 'leaders/wushe.webp',
	);
	foreach ( $settings['leaders'] as $index => $leader ) {
		$file = isset( $portraits[ $leader['name'] ] ) ? $portraits[ $leader['name'] ] : '';
		if ( $file && ! empty( $images[ $file ] ) ) {
			$url = wp_get_attachment_url( $images[ $file ] );
			if ( $url ) {
				$settings['leaders'][ $index ]['imageUrl'] = $url;
			}
		}
	}
	update_option( 'mom_site_settings', $settings );
}

/**
 * @param array<string, mixed> $args Post fields.
 * @param array<string, string> $meta Meta values.
 * @param int                   $thumb Attachment ID.
 */
function ministry_seed_post( $args, $meta = array(), $thumb = 0 ) {
	$args['post_status'] = 'publish';
	$id                  = wp_insert_post( $args );
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	foreach ( $meta as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}
	if ( $thumb ) {
		set_post_thumbnail( $id, $thumb );
	}
	return (int) $id;
}

/**
 * Head office and provincial offices.
 */
function ministry_seed_departments() {
	$rows = array(
		array( 'head-office', 'Head Office — Policy & Administration', 'Coordinate national mineral policy, investor relations, and inter-ministerial programmes.', 'Policy guidance; investor facilitation; parliamentary liaison; public communications.', 'R. Moyo', 'info@mines.gov.zw', '+263 242 777 914', 'Mukwati Building, Harare', '0' ),
		array( 'geological-survey', 'Geological Survey Department', 'Map Zimbabwe’s geology, maintain the national geoscience archive, and support exploration planning.', 'Geological maps; borehole logs; mineral occurrence records; laboratory referrals.', 'Dr T. Ncube', 'gsd@mines.gov.zw', '+263 242 777 920', 'Fifth Street, Harare', '0' ),
		array( 'metallurgy', 'Metallurgy Department', 'Promote beneficiation, value addition, and metallurgical research for Zimbabwean ores.', 'Assay guidance; beneficiation notes; plant inspection liaison; training circulars.', 'E. Chirwa', 'metallurgy@mines.gov.zw', '+263 242 777 933', 'Causeway, Harare', '0' ),
		array( 'midlands-provincial-office', 'Midlands Provincial Mining Office', 'Local licensing support, inspections, and stakeholder engagement in the Midlands.', 'Title queries; inspection scheduling; community liaison; provincial returns.', 'S. Dube', 'midlands@mines.gov.zw', '+263 54 222 110', 'Gweru', '1' ),
		array( 'manicaland-provincial-office', 'Manicaland Provincial Mining Office', 'Support mining operations and artisanal formalisation in Manicaland.', 'Permit guidance; environmental referrals; stakeholder meetings.', 'P. Mutasa', 'manicaland@mines.gov.zw', '+263 20 666 221', 'Mutare', '1' ),
	);
	$provinces = array(
		array( 'Harare Metropolitan', 'harare', 'Harare', '+263 242 700 110' ),
		array( 'Bulawayo Metropolitan', 'bulawayo', 'Bulawayo', '+263 292 260 110' ),
		array( 'Mashonaland Central', 'mashonaland-central', 'Bindura', '+263 271 700 110' ),
		array( 'Mashonaland East', 'mashonaland-east', 'Marondera', '+263 279 230 110' ),
		array( 'Mashonaland West', 'mashonaland-west', 'Chinhoyi', '+263 267 212 110' ),
		array( 'Masvingo', 'masvingo', 'Masvingo', '+263 392 262 110' ),
		array( 'Matabeleland North', 'matabeleland-north', 'Hwange', '+263 281 322 110' ),
		array( 'Matabeleland South', 'matabeleland-south', 'Gwanda', '+263 284 222 110' ),
	);
	foreach ( $provinces as $province ) {
		$rows[] = array(
			$province[1] . '-provincial-office',
			$province[0] . ' Provincial Mining Office',
			'Licensing support, inspections and stakeholder engagement in ' . $province[0] . '.',
			'Title queries; permit guidance; inspection scheduling; provincial returns.',
			'Provincial Mining Director',
			$province[1] . '@mines.gov.zw',
			$province[3],
			$province[2],
			'1',
		);
	}
	foreach ( $rows as $row ) {
		ministry_seed_post(
			array(
				'post_type'  => 'mom_department',
				'post_title' => $row[1],
				'post_name'  => $row[0],
			),
			array(
				'_mom_mandate'      => $row[2],
				'_mom_services'     => $row[3],
				'_mom_contact_name' => $row[4],
				'_mom_email'        => $row[5],
				'_mom_phone'        => $row[6],
				'_mom_location'     => $row[7],
				'_mom_provincial'   => $row[8],
			)
		);
	}
}

/**
 * @param array<string, int> $images Attachment map.
 */
function ministry_seed_news( $images ) {
	$items = array(
		array(
			'investor-roundtable-harare-2026',
			'Ministry hosts mining investor roundtable in Harare',
			'Officials outline lithium, platinum, and gold pipelines for 2026–2028.',
			"The Ministry of Mines and Mineral Development convened an investor roundtable in Harare to walk through priority minerals, licensing steps, and community engagement expectations.\n\nZimbabwe holds the world’s second-largest proven platinum reserves and significant gold, lithium, and diamond endowments.\n\nStakeholders asked about cadastre transparency, beneficiation incentives, and turnaround times for title queries. The Ministry reiterated that the public portal will become the primary channel for news, documents, and enquiries.",
			'images/dump-trucks.jpg',
			'2026-09-12 09:00:00',
		),
		array(
			'lithium-value-addition-guidelines',
			'Draft lithium value-addition guidelines released for comment',
			'Industry is invited to comment on beneficiation milestones.',
			"A draft circular on lithium concentrate and chemical-grade processing has been posted to the resource centre for public comment.\n\nComments may be submitted through the online enquiry form (type: investor).",
			'images/ore.jpg',
			'2026-08-28 09:00:00',
		),
		array(
			'artisanal-gold-formalisation',
			'Artisanal gold formalisation clinics scheduled nationwide',
			'Provincial offices will host walk-in clinics on registration and mercury-free processing.',
			'Provincial mining offices will host clinics on registration, environmental referrals, and safer processing methods. Dates appear on the events calendar.',
			'images/gold.jpg',
			'2026-08-10 09:00:00',
		),
	);
	foreach ( $items as $item ) {
		ministry_seed_post(
			array(
				'post_type'    => 'mom_news',
				'post_name'    => $item[0],
				'post_title'   => $item[1],
				'post_excerpt' => $item[2],
				'post_content' => $item[3],
				'post_date'    => $item[5],
			),
			array(),
			isset( $images[ $item[4] ] ) ? $images[ $item[4] ] : 0
		);
	}
}

/**
 * @param array<string, int> $images Attachment map.
 */
function ministry_seed_events( $images ) {
	$items = array(
		array( 'zimbabwe-mining-indaba-preview', 'Zimbabwe Mining Indaba preview', 'Briefing for exhibitors and provincial teams ahead of the regional showcase.', 'Briefings for exhibitors and provincial teams, with sessions on licensing, investment and mine safety.', 'Harare International Conference Centre', 'Conference', '2026-10-14 08:00:00', '2026-10-16 17:00:00', '42', 'images/excavators.jpg' ),
		array( 'geological-survey-open-day', 'Geological Survey open day', 'Maps, core library, and student outreach.', 'Visit the Geological Survey Department for an open day programme.', 'Geological Survey, Harare', 'Training', '2026-11-05 08:00:00', '2026-11-05 16:00:00', '18', 'images/ore.jpg' ),
		array( 'midlands-stakeholder-meeting', 'Midlands stakeholder meeting', 'Community, industry, and inspectorate liaison.', 'Provincial engagement session hosted by the Midlands office.', 'Gweru Civic Theatre', 'Stakeholder meeting', '2026-10-02 09:00:00', '2026-10-02 13:00:00', '27', 'images/haul-truck.jpg' ),
	);
	foreach ( $items as $item ) {
		ministry_seed_post(
			array(
				'post_type'    => 'mom_event',
				'post_name'    => $item[0],
				'post_title'   => $item[1],
				'post_excerpt' => $item[2],
				'post_content' => $item[3],
			),
			array(
				'_mom_location'   => $item[4],
				'_mom_category'   => $item[5],
				'_mom_starts_at'  => $item[6],
				'_mom_ends_at'    => $item[7],
				'_mom_rsvp_count' => $item[8],
			),
			isset( $images[ $item[9] ] ) ? $images[ $item[9] ] : 0
		);
	}
}

/**
 * Document centre records.
 */
function ministry_seed_documents() {
	$items = array(
		array( 'Mines and Minerals Act — excerpt', 'legislation', 'Key provisions of the Mines and Minerals Act [Chapter 21:05].', 'https://www.parlzim.gov.zw/', '2026-01-15 00:00:00' ),
		array( 'Mining title application guidance', 'guidelines', 'Step-by-step outline of prospecting licences, EPOs, and mining leases.', '/resources', '2026-06-01 00:00:00' ),
		array( 'Annual mining sector brief 2025', 'reports', 'Production and investment statistics for platinum, gold, lithium, and diamonds.', '/invest', '2026-03-20 00:00:00' ),
		array( 'Form MM-01 — general enquiry', 'forms', 'General enquiry form. You can also use the online contact form.', '/contact', '2026-04-01 00:00:00' ),
		array( 'National mineral policy summary', 'policies', 'High-level policy themes for investment and sustainability.', '/about', '2026-02-10 00:00:00' ),
	);
	foreach ( $items as $item ) {
		ministry_seed_post(
			array(
				'post_type'    => 'mom_document',
				'post_title'   => $item[0],
				'post_excerpt' => $item[2],
				'post_date'    => $item[4],
			),
			array(
				'_mom_category' => $item[1],
				'_mom_file_url' => $item[3],
			)
		);
	}
}

/**
 * @param array<string, int> $images Attachment map.
 */
function ministry_seed_gallery( $images ) {
	$items = array(
		array( 'Ministry partnership handover', 'g4.jpg' ),
		array( 'Mine safety inspection briefing', 'g2.jpg' ),
		array( 'Investment engagement meeting', 'g3.jpg' ),
		array( 'Site tour by Ministry officials', 'g1.jpg' ),
		array( 'Community engagement', 'g5.jpg' ),
	);
	foreach ( $items as $item ) {
		ministry_seed_post(
			array(
				'post_type'  => 'mom_media',
				'post_title' => $item[0],
			),
			array(),
			isset( $images[ $item[1] ] ) ? $images[ $item[1] ] : 0
		);
	}
}

/**
 * @param array<string, int> $images Attachment map.
 */
function ministry_seed_banners( $images ) {
	$items = array(
		array( 'Invest in Zimbabwe mining', 'Platinum, gold, lithium, and diamonds — explore investment opportunities.', '/invest', 'images/open-pit.jpg', 0 ),
		array( 'Transparent titles and services', 'Licensing guidance, fees, and provincial offices.', '/services', 'images/haul-truck.jpg', 1 ),
		array( 'News from the Ministry', 'Press releases, events, and the media centre.', '/news', 'images/dump-trucks.jpg', 2 ),
	);
	foreach ( $items as $item ) {
		ministry_seed_post(
			array(
				'post_type'  => 'mom_banner',
				'post_title' => $item[0],
				'menu_order' => $item[4],
			),
			array(
				'_mom_subtitle' => $item[1],
				'_mom_href'     => $item[2],
			),
			isset( $images[ $item[3] ] ) ? $images[ $item[3] ] : 0
		);
	}
}

/**
 * @param array<string, int> $images Attachment map.
 */
function ministry_seed_opportunities( $images ) {
	$items = array(
		array( 'great-dyke-pgm', 'Great Dyke PGM partnership window', 'Platinum group metals', 'Midlands / Mashonaland West', 'Zimbabwe holds the world’s second-largest proven platinum reserves.', 'Partnership opportunities along the Great Dyke for PGM exploration, mining and refining.', 'images/terraces.jpg' ),
		array( 'bikita-lithium-corridor', 'Lithium exploration & chemicals', 'Lithium', 'Masvingo / southern belt', 'Downstream chemicals and concentrate offtake', 'Lithium exploration, concentrate production and chemical processing opportunities in the southern belt.', 'images/quarry.jpg' ),
		array( 'gold-greenstone-belts', 'Gold — greenstone belt joint ventures', 'Gold', 'Multiple provinces', 'Significant gold endowment with artisanal and industrial operators.', 'Joint-venture opportunities across Zimbabwe’s gold-bearing greenstone belts.', 'images/gold.jpg' ),
		array( 'diamond-recovery', 'Diamond recovery technology partners', 'Diamonds', 'Manicaland', 'Value-addition and recovery efficiency', 'Partnerships in diamond recovery technology and value addition.', 'images/excavators.jpg' ),
	);
	foreach ( $items as $item ) {
		ministry_seed_post(
			array(
				'post_type'    => 'mom_opportunity',
				'post_name'    => $item[0],
				'post_title'   => $item[1],
				'post_excerpt' => $item[4],
				'post_content' => $item[5],
			),
			array(
				'_mom_mineral' => $item[2],
				'_mom_region'  => $item[3],
			),
			isset( $images[ $item[6] ] ) ? $images[ $item[6] ] : 0
		);
	}
}

/**
 * FAQ answers used by the public page and the chat widget.
 */
function ministry_seed_faqs() {
	$items = array(
		array( 'How do I apply for a mining title?', 'Review the licensing steps under Digital Services, download guidance from the resource centre, then submit an investor enquiry.', 'licence license title apply permit epo lease', 'Head Office — Policy & Administration', 0 ),
		array( 'Where is the mining cadastre?', 'Use the Mining Cadastre link in the header to open the official Zimbabwe mining cadastre portal and check claim availability.', 'cadastre map title spatial gis claims', 'Geological Survey Department', 1 ),
		array( 'What minerals is Zimbabwe known for?', 'Zimbabwe is a leading source of platinum group metals, with significant gold, lithium, and diamond resources.', 'platinum gold lithium diamond minerals pgm', 'Geological Survey Department', 2 ),
		array( 'How do I contact a provincial office?', 'See Departments & Offices for provincial mining offices, or send an enquiry via Contact.', 'provincial office contact gweru mutare harare', 'Head Office — Policy & Administration', 3 ),
		array( 'How do I lodge a complaint?', 'Use the contact form and choose Complaints. Complaints are handled confidentially by the Ministry.', 'complaint grievance dispute community', 'Head Office — Policy & Administration', 4 ),
	);
	foreach ( $items as $item ) {
		ministry_seed_post(
			array(
				'post_type'    => 'mom_faq',
				'post_title'   => $item[0],
				'post_content' => $item[1],
				'menu_order'   => $item[4],
			),
			array(
				'_mom_keywords'   => $item[2],
				'_mom_department' => $item[3],
			)
		);
	}
}

/**
 * Static pages and the front page.
 *
 * @return array<string, int>
 */
function ministry_seed_pages() {
	$about = ministry_seed_page( 'About Us', 'about', 0, 'page-templates/about.php' );
	$pages = array(
		'home'        => ministry_seed_page( 'Home', 'home', 0, '' ),
		'about'       => $about,
		'ministry'    => ministry_seed_page( 'The Ministry', 'ministry', $about, 'page-templates/ministry.php' ),
		'structure'   => ministry_seed_page( 'Organisational Structure', 'structure', $about, 'page-templates/structure.php' ),
		'entities'    => ministry_seed_page( 'Parastatals & Agencies', 'entities', $about, 'page-templates/entities.php' ),
		'invest'      => ministry_seed_page( 'Investments', 'invest', 0, 'page-templates/invest.php' ),
		'services'    => ministry_seed_page( 'Services', 'services', 0, 'page-templates/services.php' ),
		'contact'     => ministry_seed_page( 'Contact', 'contact', 0, 'page-templates/contact.php' ),
		'faq'         => ministry_seed_page( 'FAQ', 'faq', 0, 'page-templates/faq.php' ),
		'resources'   => ministry_seed_page( 'Resources', 'resources', 0, 'page-templates/resources.php' ),
	);
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $pages['home'] );
	return $pages;
}

/**
 * @param string $title    Page title.
 * @param string $slug     Slug.
 * @param int    $parent   Parent page ID.
 * @param string $template Theme template.
 */
function ministry_seed_page( $title, $slug, $parent, $template ) {
	$id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_name'   => $slug,
			'post_parent' => $parent,
		)
	);
	if ( $template && $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_wp_page_template', $template );
	}
	return (int) $id;
}

/**
 * Primary menu matching the Ministry navigation.
 *
 * @param array<string, int> $pages Page IDs.
 */
function ministry_seed_menu( $pages ) {
	$menu_id = wp_create_nav_menu( 'Primary' );
	if ( is_wp_error( $menu_id ) ) {
		return;
	}
	$add = function ( $title, $url, $parent, $object_id = 0, $object = 'page' ) use ( $menu_id ) {
		$args = array(
			'menu-item-title'     => $title,
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent,
		);
		if ( $object_id ) {
			$args['menu-item-type']      = 'post_type';
			$args['menu-item-object']    = $object;
			$args['menu-item-object-id'] = $object_id;
		} else {
			$args['menu-item-type'] = 'custom';
			$args['menu-item-url']  = $url;
		}
		$result = wp_update_nav_menu_item( $menu_id, 0, $args );
		if ( is_wp_error( $result ) ) {
			return 0;
		}
		if ( str_starts_with( $url, '#' ) ) {
			update_post_meta( (int) $result, '_menu_item_url', $url );
		}
		return (int) $result;
	};

	$add( 'Home', home_url( '/' ), 0, $pages['home'] );
	$about = $add( 'About Us', '', 0, $pages['about'] );
	$add( 'Overview', '', $about, $pages['about'] );
	$add( 'The Ministry', '', $about, $pages['ministry'] );
	$add( 'Organisational Structure', '', $about, $pages['structure'] );
	$add( 'Parastatals & Agencies', '', $about, $pages['entities'] );
	$add( 'Provincial Offices', home_url( '/departments/#offices' ), $about );

	$departments = $add( 'Departments', home_url( '/departments/' ), 0 );
	$add( 'Departments', '#departments', $departments );
	$add( 'Offices', '#offices', $departments );

	$media = $add( 'Media Center', home_url( '/news/' ), 0 );
	$add( 'News', home_url( '/news/' ), $media );
	$add( 'Gallery', home_url( '/media/' ), $media );

	$add( 'Events', home_url( '/events/' ), 0 );
	$add( 'Resources', '', 0, $pages['resources'] );
	$add( 'Investments', '', 0, $pages['invest'] );
	$add( 'Services', '', 0, $pages['services'] );
	$add( 'Contact', '', 0, $pages['contact'] );

	update_option( 'mom_primary_menu', (int) $menu_id );
}

/**
 * Copy the design gallery into albums once, so wp-admin starts from the live page.
 */
function ministry_seed_albums() {
	if ( get_option( 'mom_albums_seeded' ) ) {
		return;
	}
	$existing = get_posts(
		array(
			'post_type'      => 'mom_album',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( $existing ) {
		update_option( 'mom_albums_seeded', 1 );
		return;
	}
	$dir = trailingslashit( get_template_directory() ) . 'site/images/';
	if ( ! is_dir( $dir ) ) {
		return;
	}

	$heads = array(
		array( 'heads/tahwa.jpg', 'Eng. C. S. Tahwa, Chief Director, Technical Services' ),
		array( 'heads/mugumbate.jpg', 'Mr F. Mugumbate, Director, Geological Survey' ),
		array( 'heads/mungate.jpg', 'Mrs P. Mungate, Deputy Registrar, Mining Cadastre' ),
		array( 'heads/munodawafa.jpg', 'Eng. M. J. Munodawafa, Chief Government Mining Engineer' ),
		array( 'heads/godza.jpg', 'Eng. Godza, Director, Non-Energy Minerals' ),
		array( 'heads/makuza.jpg', 'Eng. T. Makuza, Director, Value Addition and Beneficiation' ),
		array( 'heads/mazemo.jpg', 'Mr Mazemo, Acting Director, Value Addition and Beneficiation' ),
		array( 'heads/singizi.jpg', 'Mr Singizi, Director, ICT' ),
		array( 'heads/chirenjani.jpg', 'Mr A. Chirenjani, Director, Finance and Administration' ),
		array( 'heads/shuva.jpg', 'Mr Shuva, Director, Internal Audit' ),
	);
	$leaders = array(
		array( 'leaders/kambamura.jpg', 'Hon. Dr. Eng. Polite Kambamura, Minister' ),
		array( 'leaders/moyo.jpg', 'Hon. Eng. Fred Moyo, Deputy Minister' ),
		array( 'leaders/makwiranzou.jpg', 'Dr. Eng. Caleb Makwiranzou, Deputy Minister' ),
		array( 'leaders/wushe.jpg', 'Dr. Thomas Utete Wushe, Permanent Secretary' ),
	);
	$profiles = array(
		array( 'gallery/minister-profile.jpg', 'Minister’s profile', true ),
		array( 'gallery/vision-mission-values.jpg', 'Vision, mission and core values', true ),
		array( 'gallery/ministry-functions.jpg', 'Our functions', false ),
		array( 'gallery/deputy-minister-moyo-profile.jpg', 'Deputy Minister Hon. Eng. Fred Moyo — profile', true ),
		array( 'gallery/deputy-minister-makwiranzou-profile.jpg', 'Deputy Minister Dr. Eng. Caleb Makwiranzou — profile', false ),
	);
	$albums = array(
		array(
			'title'  => 'Official portraits — Ministry leadership',
			'slug'   => 'official-portraits',
			'year'   => '2026',
			'layout' => 'portrait',
			'order'  => 0,
			'photos' => array_merge( $leaders, $heads ),
		),
		array(
			'title'  => 'Heads of department',
			'slug'   => 'heads-of-department',
			'year'   => '2026',
			'layout' => 'portrait',
			'order'  => 1,
			'photos' => $heads,
		),
		array(
			'title'  => 'Ministry profile releases',
			'slug'   => 'ministry-profile-releases',
			'year'   => '2026',
			'layout' => 'wide',
			'order'  => 2,
			'photos' => $profiles,
		),
	);

	$imported = 0;
	foreach ( $albums as $album ) {
		$photos = array();
		foreach ( $album['photos'] as $photo ) {
			$file = ministry_import_theme_image( $dir . $photo[0], $photo[1] );
			if ( ! $file ) {
				continue;
			}
			$photos[] = array(
				'id'      => $file,
				'caption' => $photo[1],
				'alt'     => '',
				'home'    => ! empty( $photo[2] ) ? '1' : '0',
			);
			++$imported;
		}
		ministry_seed_post(
			array(
				'post_type'  => 'mom_album',
				'post_title' => $album['title'],
				'post_name'  => $album['slug'],
				'menu_order' => $album['order'],
			),
			array(
				'_mom_year'   => $album['year'],
				'_mom_layout' => $album['layout'],
				'_mom_photos' => $photos,
			)
		);
	}
	if ( $imported ) {
		update_option( 'mom_albums_seeded', 1 );
	}
}

/**
 * Copy one theme image into the media library.
 *
 * @param string $path    Absolute file path.
 * @param string $caption Alt text stored on the attachment.
 */
function ministry_import_theme_image( $path, $caption ) {
	static $cache = array();
	if ( isset( $cache[ $path ] ) ) {
		return $cache[ $path ];
	}
	$cache[ $path ] = 0;
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( $path );
	if ( ! $tmp || ! copy( $path, $tmp ) ) {
		return 0;
	}
	$loaded = media_handle_sideload(
		array(
			'name'     => basename( $path ),
			'tmp_name' => $tmp,
		),
		0,
		$caption
	);
	if ( is_wp_error( $loaded ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( (int) $loaded, '_wp_attachment_image_alt', $caption );
	$cache[ $path ] = (int) $loaded;
	return $cache[ $path ];
}
