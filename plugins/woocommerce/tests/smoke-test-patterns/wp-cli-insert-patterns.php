<?php
/**
 * WooCommerce Block Patterns Smoke Test — WP-CLI Script
 *
 * Creates a draft page with all registered WooCommerce block patterns.
 *
 * Usage:
 *   wp eval-file wp-content/plugins/woocommerce/tests/smoke-test-patterns/wp-cli-insert-patterns.php
 *
 * @package WooCommerce\Tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure block patterns are registered.
if ( ! class_exists( 'WP_Block_Patterns_Registry' ) ) {
	WP_CLI::error( 'Block patterns registry not available. Requires WordPress 5.5+.' );
}

$registry     = WP_Block_Patterns_Registry::get_instance();
$all_patterns = $registry->get_all_registered();

if ( empty( $all_patterns ) ) {
	WP_CLI::error( 'No block patterns registered. Make sure WooCommerce is active.' );
}

// Filter to WooCommerce patterns, excluding email patterns.
$woo_patterns = array_filter(
	$all_patterns,
	function ( $pattern ) {
		$name = $pattern['name'];
		return ( str_starts_with( $name, 'woocommerce-blocks/' ) || str_starts_with( $name, 'woocommerce/' ) )
			&& false === strpos( $name, 'email' );
	}
);

if ( empty( $woo_patterns ) ) {
	WP_CLI::error( 'No WooCommerce block patterns found.' );
}

WP_CLI::log( sprintf( 'Found %d WooCommerce patterns. Building page...', count( $woo_patterns ) ) );

$content = '';

foreach ( $woo_patterns as $pattern ) {
	$name = esc_html( $pattern['name'] );

	// Heading separator.
	$content .= <<<BLOCK
<!-- wp:heading {"level":2,"style":{"color":{"background":"#7f54b3","text":"#ffffff"},"spacing":{"padding":{"top":"10px","bottom":"10px","left":"15px","right":"15px"}}}} -->
<h2 class="wp-block-heading has-text-color has-background" style="color:#ffffff;background-color:#7f54b3;padding-top:10px;padding-right:15px;padding-bottom:10px;padding-left:15px">{$name}</h2>
<!-- /wp:heading -->

BLOCK;

	// Pattern content.
	$content .= $pattern['content'] . "\n\n";

	// Spacer.
	$content .= <<<BLOCK
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

BLOCK;
}

$page_id = wp_insert_post(
	array(
		'post_title'   => 'WooCommerce Patterns Smoke Test',
		'post_content' => $content,
		'post_status'  => 'draft',
		'post_type'    => 'page',
	)
);

if ( is_wp_error( $page_id ) ) {
	WP_CLI::error( 'Failed to create page: ' . $page_id->get_error_message() );
}

WP_CLI::success(
	sprintf(
		'Created draft page ID %d with %d patterns. Edit: %s',
		$page_id,
		count( $woo_patterns ),
		admin_url( 'post.php?post=' . $page_id . '&action=edit' )
	)
);
