<?php
/**
 * Stand-ins for the `WP_CLI\Utils` helper functions, which only exist inside a wp-cli runtime.
 *
 * Loaded by MockWPCLI.php, which cannot declare them itself: phpcs allows a file to hold either
 * an OO structure or function declarations, not both.
 *
 * @package WooCommerce\Tests\Internal\CLI\Migrator\Mocks
 */

declare( strict_types=1 );

namespace WP_CLI\Utils;

if ( ! function_exists( 'WP_CLI\\Utils\\format_items' ) ) {
	/**
	 * Stand in for WP-CLI's table formatter, recording the rows it was handed.
	 *
	 * @param string $format Output format, e.g. `table`.
	 * @param array  $items  Rows to render.
	 * @param array  $fields Columns to render.
	 */
	function format_items( $format, $items, $fields ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		\Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks\MockWPCLI::$last_table = $items;
	}
}

if ( ! function_exists( 'WP_CLI\\Utils\\make_progress_bar' ) ) {
	/**
	 * Stand in for WP-CLI's progress bar so command code that draws one can run under PHPUnit.
	 *
	 * @param string $message Progress bar label.
	 * @param int    $count   Total number of ticks.
	 * @return object
	 */
	function make_progress_bar( $message, $count ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		return new class() {
			/**
			 * Advance the bar. No-op.
			 *
			 * @param int $increment Number of items completed.
			 */
			public function tick( $increment = 1 ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

			/**
			 * Finish the bar. No-op.
			 */
			public function finish() {}
		};
	}
}

if ( ! function_exists( 'WP_CLI\\Utils\\get_flag_value' ) ) {
	/**
	 * Stand in for WP-CLI's flag reader.
	 *
	 * @param array  $assoc_args    Associative arguments.
	 * @param string $flag          Flag name.
	 * @param mixed  $default_value Value to return when the flag is absent.
	 * @return mixed
	 */
	function get_flag_value( $assoc_args, $flag, $default_value = null ) {
		return isset( $assoc_args[ $flag ] ) ? $assoc_args[ $flag ] : $default_value;
	}
}
