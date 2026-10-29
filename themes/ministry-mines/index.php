<?php
/**
 * Pages that are not part of the design, search results, and not found.
 *
 * @package MinistryMines
 */

ob_start();
if ( is_404() || ! have_posts() ) {
	status_header( 404 );
	echo ministry_site_page_hero( 'Page not found', 'The page you were looking for has moved or no longer exists.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<section class="section"><div class="wrap"><a class="btn btn-primary" href="' . esc_url( home_url( '/' ) ) . '">Go to the homepage</a></div></section>';
} elseif ( is_singular() ) {
	the_post();
	echo ministry_site_page_hero( get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<section class="section"><div class="wrap">';
	the_content();
	echo '</div></section>';
} else {
	echo ministry_site_page_hero( is_search() ? 'Search results' : wp_get_document_title() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<section class="section"><div class="wrap link-list">';
	while ( have_posts() ) {
		the_post();
		echo '<a class="link-row" href="' . esc_url( get_permalink() ) . '"><span><strong>' . esc_html( get_the_title() ) . '</strong><span>' . esc_html( wp_trim_words( get_the_excerpt(), 24 ) ) . '</span></span></a>';
	}
	echo '</div></section>';
}
ministry_site_shell( (string) ob_get_clean() );
