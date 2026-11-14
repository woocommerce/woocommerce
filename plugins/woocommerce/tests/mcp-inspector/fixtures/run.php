<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration -- `wp eval-file` evaluates this file, where a declare statement is fatal.
/**
 * Runs a fixture: `wp eval-file run.php <name> [<json args>]` calls the closure `<name>.php` returns and prints its result.
 *
 * Test files seed in parallel, so a database lock keeps two of them from creating the same record.
 *
 * @package WooCommerce\Tests\McpInspector
 */

global $wpdb;

$mcp_fixture_name = $args[0] ?? '';
if ( 'run' === $mcp_fixture_name || ! preg_match( '/^[a-z-]+$/', $mcp_fixture_name ) || ! file_exists( __DIR__ . "/{$mcp_fixture_name}.php" ) ) {
	WP_CLI::error( "Unknown fixture: {$mcp_fixture_name}" );
}

$mcp_fixture = require __DIR__ . "/{$mcp_fixture_name}.php";

$wpdb->get_var( "SELECT GET_LOCK( 'woocommerce_mcp_inspector_fixtures', 60 )" );
try {
	$mcp_fixture_result = $mcp_fixture( json_decode( $args[1] ?? '{}', true ) ?? array() );
} finally {
	$wpdb->get_var( "SELECT RELEASE_LOCK( 'woocommerce_mcp_inspector_fixtures' )" );
}

echo "\nMCP_FIXTURE=" . wp_json_encode( $mcp_fixture_result ) . "\n";
