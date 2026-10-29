<?php
/**
 * Default site settings, copied from the Ministry reference defaults.
 *
 * @package MinistryContent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Official cadastre portal.
 */
function ministry_cadastre_url() {
	return 'https://portal.zimcadastre.co.zw';
}

/**
 * Page keys that have eyebrow, title, and intro copy.
 *
 * @return string[]
 */
function ministry_page_keys() {
	return array(
		'news',
		'events',
		'media',
		'resources',
		'about',
		'ministry',
		'structure',
		'entities',
		'departments',
		'offices',
		'invest',
		'claim',
		'services',
		'contact',
		'faq',
		'search',
		'privacy',
		'accessibility',
		'information',
		'terms',
		'verify',
		'sitemap',
	);
}

/**
 * Default settings used when nothing is stored, and as the shape check.
 *
 * @return array<string, mixed>
 */
function ministry_default_settings() {
	$cadastre = ministry_cadastre_url();
	return array(
		'ministryName'       => 'Ministry of Mines and Mining Development',
		'shortName'          => 'MMMD',
		'countryLine'        => 'Republic of Zimbabwe',
		'tagline'            => 'Sustainable Mining: Our Legacy',
		'logoUrl'            => '',
		'logoId'             => 0,
		'navItems'           => array(
			array( 'label' => 'Home', 'href' => '/', 'children' => array() ),
			array( 'label' => 'About the ministry', 'href' => '/about', 'children' => array() ),
			array( 'label' => 'Departments', 'href' => '/departments', 'children' => array() ),
			array( 'label' => 'Offices', 'href' => '/offices', 'children' => array() ),
			array( 'label' => 'Services', 'href' => '/services', 'children' => array() ),
			array( 'label' => 'Invest', 'href' => '/invest', 'children' => array() ),
			array( 'label' => 'News & Gallery', 'href' => '/news', 'children' => array() ),
			array( 'label' => 'Resources', 'href' => '/resources', 'children' => array() ),
			array( 'label' => 'Contact Us', 'href' => '/contact', 'children' => array() ),
		),
		'legalLinks'         => array(
			array( 'label' => 'Privacy & data protection', 'href' => '/privacy' ),
			array( 'label' => 'Accessibility statement', 'href' => '/accessibility' ),
			array( 'label' => 'Access to information', 'href' => '/information' ),
			array( 'label' => 'Terms of use', 'href' => '/terms' ),
			array( 'label' => 'Sitemap', 'href' => '/sitemap' ),
		),
		'verifyHref'         => '/verify',
		'whatsappNumber'     => '',
		'downloadsEnabled'   => '1',
		'homeTasks'          => array(
			array( 'title' => 'How to get a mining claim', 'desc' => 'Step-by-step: requirements, forms, fees and where to apply', 'href' => '/how-to-get-a-mining-claim' ),
			array( 'title' => 'Check claim availability', 'desc' => 'Search titles and boundaries on the Mining Cadastre', 'href' => $cadastre ),
			array( 'title' => 'Find your provincial office', 'desc' => '8 mining provinces plus 4 head-office centres in Harare', 'href' => '/offices' ),
			array( 'title' => 'Download the Mines Act & policies', 'desc' => 'Mines and Minerals Act, Strategic Plan, Client Charter', 'href' => '/resources' ),
			array( 'title' => 'See our service standards', 'desc' => 'Prospecting licences issued on the day of application', 'href' => '/services#standards' ),
			array( 'title' => 'Ask the Minister', 'desc' => 'askminister@mines.gov.zw · toll-free 08004577', 'href' => '/contact#enquiry' ),
		),
		'claimNotice'        => 'Draft structure. Steps follow the Mines and Minerals Act and Client Service Charter standards. The Ministry will supply the approved procedure, forms and gazetted fees before publication.',
		'claimChecklist'     => array(
			'Valid national ID or company documents',
			'Prospecting licence',
			'Completed registration form',
			'Claim diagram / sketch plan',
			'Prescribed fees paid',
			'EMA approval (before mining)',
		),
		'claimSteps'         => array(
			array( 'title' => 'Check availability', 'desc' => 'Confirm the ground is open for pegging on the Mining Cadastre portal, or ask the Provincial Mining Office.', 'where' => 'Online / PMO', 'timeline' => 'Same day', 'need' => 'Mining Cadastre portal' ),
			array( 'title' => 'Obtain a prospecting licence', 'desc' => 'Apply at your Provincial Mining Office with your identity document and the prescribed fee. Prospecting licences are issued on the date the application is received.', 'where' => 'Provincial Mining Office', 'timeline' => 'Same day', 'need' => "Prospecting licence application form\nNational ID / company registration" ),
			array( 'title' => 'Peg the claim', 'desc' => 'Peg the claim on the ground in accordance with the Mines and Minerals Act [Chapter 21:05], displaying the prospecting licence number.', 'where' => 'On site', 'timeline' => '—', 'need' => 'Mines and Minerals Act, Part on pegging' ),
			array( 'title' => 'Register the claim', 'desc' => 'Lodge the registration application and claim diagram at the Provincial Mining Office within the period set out in the Act, and pay the registration fee.', 'where' => 'Provincial Mining Office', 'timeline' => 'Determination within 3 months', 'need' => "Registration application form\nClaim diagram / sketch plan\nRegistration fee" ),
			array( 'title' => 'Inspection & survey', 'desc' => 'The Ministry may inspect the claim. Survey certificates are issued within 30 working days.', 'where' => 'PMO · Mining Cadastre', 'timeline' => '30 working days', 'need' => 'Survey certificate' ),
			array( 'title' => 'Certificate of registration', 'desc' => 'On approval, a certificate of registration is issued. You must then meet the ongoing obligations of a title holder.', 'where' => 'Provincial Mining Office', 'timeline' => 'Within 3 working days of approval', 'need' => 'Certificate of registration' ),
			array( 'title' => 'Stay compliant', 'desc' => 'Maintain inspection certificates, submit production returns, pay royalties and obtain environmental (EMA) approvals.', 'where' => 'PMO · EMA', 'timeline' => 'Ongoing', 'need' => "Production returns\nEMA environmental approval" ),
		),
		'mabMembers'         => array(
			array( 'name' => 'Dr T. U. Wushe', 'role' => 'Chairperson' ),
			array( 'name' => 'Engineer C. S. Tahwa', 'role' => '' ),
			array( 'name' => 'Mr S. Dhlamini', 'role' => '' ),
			array( 'name' => 'Mrs N. Makoho', 'role' => '' ),
			array( 'name' => 'Mr A. Mashingaidze', 'role' => '' ),
			array( 'name' => 'Engineer M. J. Munodawafa', 'role' => '' ),
			array( 'name' => 'Mr F. Mugumbate', 'role' => '' ),
			array( 'name' => 'Mr H. Greaves', 'role' => '' ),
			array( 'name' => 'Mrs B. Hungwe-Nharara', 'role' => '' ),
			array( 'name' => 'Mrs M. Chigunduru', 'role' => '' ),
			array( 'name' => 'Mr D. Nhukarume', 'role' => '' ),
			array( 'name' => 'Ms E. M. Muguti', 'role' => 'Secretary to the Board' ),
		),
		'serviceStandards'   => array(
			array( 'label' => 'Visitors’ reception', 'value' => 'Within 2 minutes of arrival' ),
			array( 'label' => 'Telephone calls', 'value' => 'Answered by the 3rd ring' ),
			array( 'label' => 'Routine correspondence', 'value' => 'Replied within 7 days' ),
			array( 'label' => 'Technical correspondence', 'value' => 'Acknowledged immediately · replied within 30 days' ),
			array( 'label' => 'Enquiry by email', 'value' => 'Within 24 hours' ),
			array( 'label' => 'Face-to-face enquiry', 'value' => 'Immediate response' ),
			array( 'label' => 'Licences, permits & certificates', 'value' => 'Within 3 working days' ),
			array( 'label' => 'Payment requests', 'value' => 'Processed within 5 working days' ),
		),
		'headerQuickLinks'   => array(
			array( 'label' => 'Mining Cadastre', 'href' => $cadastre ),
			array( 'label' => 'Contact Us', 'href' => '/contact' ),
		),
		'footerColumns'      => array(
			array(
				'title' => 'Services & Offices',
				'links' => array(
					array( 'label' => 'How to get a mining claim', 'href' => '/how-to-get-a-mining-claim' ),
					array( 'label' => 'Ministry services', 'href' => '/services' ),
					array( 'label' => 'Online Mining Cadastre', 'href' => $cadastre ),
					array( 'label' => 'Head office & provinces', 'href' => '/offices' ),
				),
			),
			array(
				'title' => 'Information',
				'links' => array(
					array( 'label' => 'Mines Act, policies & regulations', 'href' => '/resources' ),
					array( 'label' => 'News, notices & gallery', 'href' => '/news' ),
					array( 'label' => 'Client Service Charter', 'href' => '/resources' ),
					array( 'label' => 'Frequently asked questions', 'href' => '/faq' ),
				),
			),
		),
		'footerPortalTitle'  => 'Stakeholder Portal',
		'footerPortalText'   => 'Report issues or concerns to the Ministry. We acknowledge complaints within 3 working days. Follow @MinesZimbabwe.',
		'socialLinks'        => array(
			array( 'label' => 'Facebook', 'href' => '#' ),
			array( 'label' => 'X', 'href' => '#' ),
			array( 'label' => 'LinkedIn', 'href' => '#' ),
			array( 'label' => 'YouTube', 'href' => '#' ),
			array( 'label' => 'TikTok', 'href' => '#' ),
		),
		'headOfficeName'     => 'ZIMRE Centre',
		'headOfficeAddress'  => 'Cnr Leopold Takawira St & Kwame Nkrumah Ave, Private Bag 7709, Causeway, Harare',
		'headOfficeEmail'    => 'mmmd@mines.gov.zw',
		'ministerEmail'      => 'askminister@mines.gov.zw',
		'headOfficePhone'    => '+263 242 777022-4',
		'tollFree'           => '08004577',
		'officeHours'        => 'Mon – Fri: 08:00 to 16:30 CAT',
		'footerNote'         => '© 2026 Ministry of Mines and Mining Development, Zimbabwe.',
		'heroBadge'          => 'Official portal · Republic of Zimbabwe',
		'homeHero'           => array(
			'eyebrow'        => 'Official portal · Republic of Zimbabwe',
			'title'          => 'Unlocking Zimbabwe’s mineral wealth',
			'text'           => 'For sustainable growth and prosperity — building a strong, transparent and competitive mining sector.',
			'primaryLabel'   => 'How to get a mining claim',
			'primaryHref'    => '/how-to-get-a-mining-claim',
			'secondaryLabel' => 'Online Mining Cadastre',
		),
		'heroPrimaryCta'     => array( 'label' => 'Online Mining Cadastre', 'href' => $cadastre ),
		'heroCtaLabel'       => 'Explore',
		'gatewaysHeading'    => 'Ministry Service Gateways',
		'gateways'           => array(
			array(
				'title' => 'Mining Cadastre Portal',
				'desc'  => 'Official Trimble Landfolio portal: spatial map, title verification & claim boundaries',
				'href'  => $cadastre,
				'tag'   => 'GIS Mapping',
				'icon'  => 'map',
			),
			array(
				'title' => 'Licensing & Rights',
				'desc'  => 'Applications for EPOs, special grants, renewals & permits',
				'href'  => '/services',
				'tag'   => 'Regulation',
				'icon'  => 'document',
			),
			array(
				'title' => 'Investments',
				'desc'  => 'How to invest in Zimbabwe mining: Lithium, Platinum, Gold & Nickel',
				'href'  => '/invest',
				'tag'   => 'FDI Hub',
				'icon'  => 'chart',
			),
			array(
				'title' => 'Document Centre',
				'desc'  => 'Mining Acts, Statutory Instruments, reports & guidelines',
				'href'  => '/resources',
				'tag'   => 'Transparency',
				'icon'  => 'archive',
			),
		),
		'homePillars'        => array(
			'Improved governance and administration',
			'Increased mineral output',
			'Improved environmentally sustainable mining',
			'Increased exports of beneficiated minerals',
		),
		'homeStats'          => array(
			array( 'label' => 'Vision 2030', 'value' => 'Stable & sustainable', 'hint' => 'A competitive mining industry' ),
			array( 'label' => 'Mining provinces', 'value' => '8 Provinces', 'hint' => 'Provincial mining offices' ),
			array( 'label' => 'Head office', 'value' => '4 Harare centres', 'hint' => 'ZIMRE, Survey, Metallurgy, Mines Yard' ),
			array( 'label' => 'Prospecting licence', 'value' => 'Same day', 'hint' => 'Issued on the day you apply' ),
		),
		'homeNewsEyebrow'    => 'Official notices',
		'homeNewsHeading'    => 'News & public notices',
		'homeEventsEyebrow'  => 'Schedule',
		'homeEventsHeading'  => 'Upcoming Events',
		'homeOpportunitiesEyebrow' => 'Strategic minerals',
		'homeOpportunitiesHeading' => 'Investment opportunities',
		'heroRotateSeconds'  => 6,
		'aboutSections'      => array(
			array( 'title' => 'Vision', 'desc' => 'A sustainable, transparent and globally competitive mining sector that drives Zimbabwe’s economic growth.' ),
			array( 'title' => 'Mission', 'desc' => 'To promote, regulate and facilitate the exploration, exploitation and beneficiation of mineral resources for the benefit of all Zimbabweans.' ),
			array( 'title' => 'Mandate', 'desc' => 'Administration of the Mines and Minerals Act, mining title allocation, geological survey, mine safety inspection, and mineral investment promotion.' ),
			array( 'title' => 'Core values', 'desc' => 'Integrity, transparency, accountability, environmental stewardship and service excellence.' ),
		),
		'leaders'            => array(
			array(
				'title'    => 'Minister of Mines and Mining Development',
				'name'     => 'Hon. Dr. Eng. Polite Kambamura',
				'bio'      => 'Appointed Minister on 8 December 2025, having served as Deputy Minister of Mines and Mining Development from 2018. Member of Parliament for Sanyati since 2018. A mining engineer (BSc, University of Zimbabwe) who worked in mines in Zimbabwe and South Africa, with a Master’s in Public Policy and Governance from Africa University.',
				'imageUrl' => '',
			),
			array(
				'title'    => 'Deputy Minister of Mines and Mining Development',
				'name'     => 'Hon. Eng. Fred Moyo',
				'bio'      => 'Appointed Deputy Minister on 17 December 2025, returning to the post he held from 2013 to 2017. A mining engineer and former Managing Director of Hwange Colliery Company.',
				'imageUrl' => '',
			),
			array(
				'title'    => 'Deputy Minister — Oil & Gas Research and Strategic Minerals Exploration',
				'name'     => 'Hon. Dr. Eng. Caleb Makwiranzou',
				'bio'      => 'Deputy Minister since 13 February 2025, responsible for oil and gas research and the exploration of other strategic minerals.',
				'imageUrl' => '',
			),
			array(
				'title'    => 'Permanent Secretary',
				'name'     => 'Dr. Thomas Utete Wushe',
				'bio'      => 'Permanent Secretary since 1 May 2026, previously Permanent Secretary for Industry and Commerce. Holds a doctorate from Nottingham Business School and an MBA from the University of Zimbabwe, with prior experience at Deloitte, Crown Agents and Mobil Oil. Chairs the Mining Affairs Board.',
				'imageUrl' => '',
			),
		),
		'organogram'         => array(
			array( 'title' => 'Minister of Mines and Mining Development', 'holder' => 'Hon. Dr. Eng. Polite Kambamura', 'parent' => '' ),
			array( 'title' => 'Deputy Minister', 'holder' => 'Hon. Eng. Fred Moyo', 'parent' => 'Minister of Mines and Mining Development' ),
			array( 'title' => 'Deputy Minister (Oil, Gas & Strategic Minerals)', 'holder' => 'Hon. Dr. Eng. Caleb Makwiranzou', 'parent' => 'Minister of Mines and Mining Development' ),
			array( 'title' => 'Permanent Secretary', 'holder' => 'Dr. Thomas Utete Wushe', 'parent' => 'Minister of Mines and Mining Development' ),
			array( 'title' => 'Mining Engineering & Technical Services', 'holder' => 'Chief Director', 'parent' => 'Permanent Secretary' ),
			array( 'title' => 'Chief Government Mining Engineer', 'holder' => 'Mine safety & inspectorate', 'parent' => 'Mining Engineering & Technical Services' ),
			array( 'title' => 'Geological Survey', 'holder' => 'Director', 'parent' => 'Mining Engineering & Technical Services' ),
			array( 'title' => 'Metallurgy', 'holder' => 'Director', 'parent' => 'Mining Engineering & Technical Services' ),
			array( 'title' => 'Mining Cadastre & Titles', 'holder' => 'Director', 'parent' => 'Mining Engineering & Technical Services' ),
			array( 'title' => 'Mining Promotion & Development', 'holder' => 'Chief Director', 'parent' => 'Permanent Secretary' ),
			array( 'title' => 'Investment Promotion', 'holder' => 'Director', 'parent' => 'Mining Promotion & Development' ),
			array( 'title' => 'Small-Scale & Artisanal Mining', 'holder' => 'Director', 'parent' => 'Mining Promotion & Development' ),
			array( 'title' => 'Policy, Research & Planning', 'holder' => 'Director', 'parent' => 'Mining Promotion & Development' ),
			array( 'title' => 'Corporate Services', 'holder' => 'Chief Director', 'parent' => 'Permanent Secretary' ),
			array( 'title' => 'Human Resources', 'holder' => 'Director', 'parent' => 'Corporate Services' ),
			array( 'title' => 'Finance & Administration', 'holder' => 'Director', 'parent' => 'Corporate Services' ),
			array( 'title' => 'ICT', 'holder' => 'Deputy Director', 'parent' => 'Corporate Services' ),
			array( 'title' => 'Communications & Public Relations', 'holder' => 'Deputy Director', 'parent' => 'Corporate Services' ),
			array( 'title' => 'Legal Services', 'holder' => 'Director', 'parent' => 'Permanent Secretary' ),
			array( 'title' => 'Internal Audit', 'holder' => 'Chief Internal Auditor', 'parent' => 'Permanent Secretary' ),
			array( 'title' => 'Provincial Mining Directors', 'holder' => '@offices', 'parent' => 'Permanent Secretary' ),
		),
		'entities'           => array(
			array( 'name' => 'Zimbabwe Mining Development Corporation', 'acronym' => 'ZMDC', 'desc' => 'State investment arm in the mining sector, holding and developing mining assets on behalf of Government.', 'href' => '' ),
			array( 'name' => 'Minerals Marketing Corporation of Zimbabwe', 'acronym' => 'MMCZ', 'desc' => 'Statutory marketing and export agent for minerals produced in Zimbabwe.', 'href' => '' ),
			array( 'name' => 'Fidelity Gold Refinery', 'acronym' => 'FGR', 'desc' => 'Gold buying, assaying and refining.', 'href' => '' ),
			array( 'name' => 'Zimbabwe Consolidated Diamond Company', 'acronym' => 'ZCDC', 'desc' => 'Diamond mining operations in the Marange fields.', 'href' => '' ),
			array( 'name' => 'Zimbabwe School of Mines', 'acronym' => 'ZSM', 'desc' => 'Technical training for mining, metallurgy, geology and surveying.', 'href' => '' ),
			array( 'name' => 'Mining Affairs Board', 'acronym' => 'MAB', 'desc' => 'Statutory board under the Mines and Minerals Act advising on mining title applications and disputes.', 'href' => '' ),
		),
		'investHighlightsHeading' => 'Why invest in Zimbabwe mining',
		'investHighlights'   => array(
			array( 'title' => 'World-class geology', 'desc' => 'The Great Dyke hosts the world’s second-largest platinum group metal reserves, alongside extensive gold greenstone belts.' ),
			array( 'title' => 'Battery minerals', 'desc' => 'Africa’s leading lithium deposits, with active projects in Masvingo, Mashonaland and Matabeleland.' ),
			array( 'title' => 'Clear title system', 'desc' => 'Mining rights are registered and verifiable through the national mining cadastre portal.' ),
			array( 'title' => 'Investor support', 'desc' => 'The Ministry facilitates site visits, data access and introductions to provincial mining offices.' ),
		),
		'licensingHeading'   => 'How to invest: licensing steps',
		'licensingSteps'     => array(
			array( 'title' => 'Prospecting Licence', 'desc' => 'Reconnaissance & ground exploration rights' ),
			array( 'title' => 'EPO Application', 'desc' => 'Exclusive prospecting order documentation' ),
			array( 'title' => 'Mining Lease / Grant', 'desc' => 'Commercial exploitation title granting' ),
			array( 'title' => 'EMA & Community', 'desc' => 'Environmental approvals & stakeholder accords' ),
			array( 'title' => 'Statutory Reporting', 'desc' => 'Regular mineral production & royalties return' ),
		),
		'investPortfolioEyebrow' => 'Strategic Portfolio',
		'investPortfolioHeading' => 'Priority investment areas',
		'investCta'          => array(
			'title' => 'Interested in investing?',
			'text'  => 'Tell us which mineral, region or project you are interested in and the Investment Promotion desk will respond.',
			'label' => 'Enquire',
			'href'  => '/contact',
		),
		'feesHeading'        => 'Gazetted Fee Schedule',
		'feesReference'      => 'Statutory Instrument 185/2021 Reference',
		'fees'               => array(
			array( 'service' => 'Prospecting licence', 'amount' => 'USD 150' ),
			array( 'service' => 'Exclusive prospecting order — application', 'amount' => 'USD 2,500' ),
			array( 'service' => 'Mining lease — application', 'amount' => 'USD 5,000' ),
			array( 'service' => 'Special grant — application', 'amount' => 'USD 7,500' ),
			array( 'service' => 'Title search / printout', 'amount' => 'USD 20' ),
		),
		'serviceCards'       => array(
			array(
				'tag'   => 'Regulations',
				'title' => 'Application Guidance & Forms',
				'desc'  => 'Download verified statutory application guidelines and checklist forms for concessions.',
				'href'  => '/resources',
				'cta'   => 'Browse Document Centre',
			),
			array(
				'tag'   => 'Helpline',
				'title' => 'Contact us & Help Desk',
				'desc'  => 'Direct institutional escalation for licensing issues, title conflicts, and provincial inquiries.',
				'href'  => '/contact',
				'cta'   => 'Open Stakeholder Enquiry',
			),
		),
		'cadastrePortalUrl'  => $cadastre,
		'assistantEnabled'   => '1',
		'assistantScriptUrl' => 'https://chatbot.vision-ai.tech/widget.js',
		'assistantTenant'    => 'ministryofmines',
		'googleMapsApiKey'   => '',
		'pages'              => array(
			'news'        => array( 'eyebrow' => 'Press & publications', 'title' => 'News & public notices', 'intro' => 'Official statements, public notices and press releases from the Ministry of Mines and Mining Development.', 'notice' => '' ),
			'events'      => array( 'eyebrow' => 'Schedule', 'title' => 'Mining summits, conferences & events', 'intro' => 'Stakeholder meetings, conferences and training days hosted or supported by the Ministry.', 'notice' => '' ),
			'media'       => array( 'eyebrow' => 'Pictures', 'title' => 'Photo gallery', 'intro' => 'Pictures from Ministry events, visits and official engagements.', 'notice' => '' ),
			'resources'   => array( 'eyebrow' => 'Downloads', 'title' => 'Resources', 'intro' => 'The Mines and Minerals Act, policy documents and regulations — and downloadable staff forms.', 'notice' => '' ),
			'about'       => array( 'eyebrow' => 'About the ministry', 'title' => 'Ministry of Mines and Mining Development', 'intro' => 'Unlocking Zimbabwe’s mineral wealth for sustainable growth and prosperity. “Sustainable Mining: Our Legacy”.', 'notice' => '' ),
			'ministry'    => array( 'eyebrow' => 'About us', 'title' => 'The Ministry', 'intro' => 'Political and administrative leadership of the Ministry of Mines and Mining Development.', 'notice' => '' ),
			'structure'   => array( 'eyebrow' => 'About us', 'title' => 'Organisational structure', 'intro' => 'How the Ministry is organised, from the Minister through the Permanent Secretary to head-office departments and provincial mining offices.', 'notice' => '' ),
			'entities'    => array( 'eyebrow' => 'About us', 'title' => 'Parastatals & agencies', 'intro' => 'State enterprises, statutory bodies and grant-aided institutions supervised and coordinated by the Ministry.', 'notice' => '' ),
			'departments' => array( 'eyebrow' => 'Organisation', 'title' => 'Departments', 'intro' => 'Directorates of the Ministry that issue, regulate and administer services.', 'notice' => '' ),
			'offices'     => array( 'eyebrow' => 'Head office & provinces', 'title' => 'Our offices', 'intro' => 'The Ministry’s head office operates from four centres in Harare, supported by Provincial Mining Offices.', 'notice' => '' ),
			'invest'      => array( 'eyebrow' => 'Investor information', 'title' => 'Investments', 'intro' => 'Zimbabwe holds the world’s second-largest proven platinum reserves, significant gold deposits, and growing lithium and diamond opportunities.', 'notice' => '' ),
			'services'    => array( 'eyebrow' => 'Public services', 'title' => 'Ministry services', 'intro' => 'Services offered by the Ministry, with the service standards we commit to in our Client Service Charter.', 'notice' => '' ),
			'claim'       => array( 'eyebrow' => 'Step-by-step guide', 'title' => 'How to get a mining claim', 'intro' => 'Requirements, forms, fees, where to apply and each processing stage for registering a mining claim.', 'notice' => '' ),
			'contact'     => array( 'eyebrow' => 'Contact us', 'title' => 'Contact the Ministry', 'intro' => 'Head office, provincial offices and the channels for an enquiry or a complaint.', 'notice' => '' ),
			'faq'         => array( 'eyebrow' => 'Public information', 'title' => 'Frequently asked questions', 'intro' => 'Answers about mining claims, licences, service timelines, complaints and where to find the law.', 'notice' => '' ),
			'search'      => array( 'eyebrow' => '', 'title' => 'Search', 'intro' => 'Enter a term to search news, events, documents, and opportunities.', 'notice' => '' ),
			'privacy'     => array( 'eyebrow' => 'Legal', 'title' => 'Privacy & data protection', 'intro' => 'How the Ministry handles personal information submitted through this website.', 'notice' => '' ),
			'accessibility' => array( 'eyebrow' => 'Legal', 'title' => 'Accessibility statement', 'intro' => 'How we work to make this website usable by everyone.', 'notice' => '' ),
			'information' => array( 'eyebrow' => 'Legal', 'title' => 'Access to information', 'intro' => 'How to request official information from the Ministry.', 'notice' => '' ),
			'terms'       => array( 'eyebrow' => 'Legal', 'title' => 'Terms of use', 'intro' => 'Conditions for using the Ministry of Mines website.', 'notice' => '' ),
			'verify'      => array( 'eyebrow' => 'Security', 'title' => 'How to verify official sites', 'intro' => 'Use mines.gov.zw and published social accounts @MinesZimbabwe. Do not share passwords or pay anyone who contacts you claiming to be the Ministry.', 'notice' => '' ),
			'sitemap'     => array( 'eyebrow' => 'Find a page', 'title' => 'Sitemap', 'intro' => 'All public pages on this website.', 'notice' => '' ),
		),
	);
}

/**
 * Drop stored values whose shape no longer matches the defaults.
 *
 * @param mixed $stored   Stored value.
 * @param mixed $fallback Default value.
 */
function ministry_same_shape( $stored, $fallback ) {
	if ( is_array( $fallback ) ) {
		if ( ! is_array( $stored ) ) {
			return false;
		}
		$sample = null;
		foreach ( $fallback as $item ) {
			$sample = $item;
			break;
		}
		if ( null === $sample ) {
			return true;
		}
		$is_list = array_is_list( $fallback );
		if ( ! $is_list ) {
			foreach ( $fallback as $key => $value ) {
				if ( ! array_key_exists( $key, $stored ) || ! ministry_same_shape( $stored[ $key ], $value ) ) {
					return false;
				}
			}
			return true;
		}
		if ( ! $stored ) {
			return true;
		}
		foreach ( $stored as $item ) {
			if ( ! ministry_same_shape( $item, $sample ) ) {
				return false;
			}
		}
		return true;
	}
	return gettype( $stored ) === gettype( $fallback );
}

/**
 * Merge stored settings onto defaults.
 *
 * @param array<string, mixed> $stored Stored option.
 * @return array<string, mixed>
 */
function ministry_merge_settings( $stored ) {
	$base   = ministry_default_settings();
	$merged = $base;
	foreach ( $base as $key => $value ) {
		if ( 'pages' === $key || ! array_key_exists( $key, $stored ) ) {
			continue;
		}
		if ( ministry_same_shape( $stored[ $key ], $value ) ) {
			if ( is_array( $value ) && array_is_list( $value ) && $value && is_array( $stored[ $key ] ) && ! $stored[ $key ] ) {
				continue;
			}
			$merged[ $key ] = $stored[ $key ];
		}
	}
	$pages = $base['pages'];
	if ( isset( $stored['pages'] ) && is_array( $stored['pages'] ) ) {
		foreach ( ministry_page_keys() as $key ) {
			$page = isset( $stored['pages'][ $key ] ) && is_array( $stored['pages'][ $key ] ) ? $stored['pages'][ $key ] : array();
			$pages[ $key ] = array(
				'eyebrow' => isset( $page['eyebrow'] ) ? (string) $page['eyebrow'] : $base['pages'][ $key ]['eyebrow'],
				'title'   => isset( $page['title'] ) ? (string) $page['title'] : $base['pages'][ $key ]['title'],
				'intro'   => isset( $page['intro'] ) ? (string) $page['intro'] : $base['pages'][ $key ]['intro'],
				'notice'  => isset( $page['notice'] ) ? (string) $page['notice'] : $base['pages'][ $key ]['notice'],
			);
		}
	}
	$merged['pages'] = $pages;
	if ( isset( $merged['pages']['about']['eyebrow'] ) && 0 === strcasecmp( trim( (string) $merged['pages']['about']['eyebrow'] ), 'About us' ) ) {
		$merged['pages']['about']['eyebrow'] = $base['pages']['about']['eyebrow'];
	}
	$merged['navItems'] = ministry_nav_normalize( isset( $merged['navItems'] ) && is_array( $merged['navItems'] ) ? $merged['navItems'] : $base['navItems'] );
	return $merged;
}

/**
 * Keep Departments as a top-level item, not a submenu under About.
 *
 * @param array<int, mixed> $items Nav rows.
 * @return array<int, mixed>
 */
function ministry_nav_normalize( $items ) {
	$about_i       = null;
	$has_top_dept  = false;
	$dept_from_sub = null;
	foreach ( $items as $i => $item ) {
		if ( ! is_array( $item ) || empty( $item['label'] ) ) {
			continue;
		}
		$label = strtolower( trim( (string) $item['label'] ) );
		$href  = isset( $item['href'] ) ? untrailingslashit( (string) $item['href'] ) : '';
		$path  = (string) wp_parse_url( $href, PHP_URL_PATH );
		$path  = untrailingslashit( $path ? $path : $href );
		if ( in_array( $label, array( 'about us', 'about the ministry' ), true ) || '/about' === $path ) {
			$about_i = $i;
		}
		if ( 'departments' === $label || '/departments' === $path ) {
			$has_top_dept = true;
		}
	}
	if ( null !== $about_i && isset( $items[ $about_i ] ) && is_array( $items[ $about_i ] ) ) {
		if ( 0 === strcasecmp( trim( (string) $items[ $about_i ]['label'] ), 'About Us' ) ) {
			$items[ $about_i ]['label'] = 'About the ministry';
		}
		$children = isset( $items[ $about_i ]['children'] ) && is_array( $items[ $about_i ]['children'] ) ? $items[ $about_i ]['children'] : array();
		$kept     = array();
		foreach ( $children as $kid ) {
			if ( ! is_array( $kid ) || empty( $kid['label'] ) ) {
				continue;
			}
			$kid_label = strtolower( trim( (string) $kid['label'] ) );
			$kid_href  = isset( $kid['href'] ) ? untrailingslashit( (string) $kid['href'] ) : '';
			$kid_path  = (string) wp_parse_url( $kid_href, PHP_URL_PATH );
			$kid_path  = untrailingslashit( $kid_path ? $kid_path : $kid_href );
			if ( 'departments' === $kid_label || '/departments' === $kid_path ) {
				$dept_from_sub = $kid;
				continue;
			}
			$kept[] = $kid;
		}
		$items[ $about_i ]['children'] = $kept;
	}
	if ( ! $has_top_dept ) {
		$dept = is_array( $dept_from_sub ) ? $dept_from_sub : array(
			'label'    => 'Departments',
			'href'     => '/departments',
			'children' => array(),
		);
		$dept['children'] = array();
		$insert_at        = null !== $about_i ? $about_i + 1 : count( $items );
		array_splice( $items, $insert_at, 0, array( $dept ) );
	}
	return array_values( $items );
}
