<?php
/**
 * Plugin Name: MCP Inspector mail sink
 * Description: The environment has no mail server, so mail is accepted without being sent.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

add_filter( 'pre_wp_mail', '__return_true' );
