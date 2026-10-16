<?php
/**
 * Fixture for IdentifierPlaceholderSniff.
 *
 * Each numbered case documents whether the sniff should flag it.
 *
 * @package WooCommerce\Tests\Tools\PHPCS
 */

// Case 1: unguarded %i in prepare() — FLAGGED.
function case_unguarded_single_line() {
	global $wpdb;
	return $wpdb->get_var( $wpdb->prepare( 'SELECT session_value FROM %i WHERE session_key = %s', $table, $key ) );
}

// Case 2: unguarded %i in a multi-line prepare() — FLAGGED.
function case_unguarded_multi_line() {
	global $wpdb;
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id
			FROM %i
			WHERE type = %s",
			$table,
			$type
		)
	);
}

// Case 3: %i guarded by has_cap( 'identifier_placeholders' ) in the same function — NOT flagged.
function case_guarded() {
	global $wpdb;
	if ( $wpdb->has_cap( 'identifier_placeholders' ) ) {
		return $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $id );
	}
	return $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id );
}

// Case 4: direct interpolation, no %i — NOT flagged.
function case_interpolated() {
	global $wpdb;
	return $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id );
}

// Case 5: %%i escaped literal and %H:%i:%s time specifier — NOT flagged.
function case_time_and_escape() {
	global $wpdb;
	$a = $wpdb->prepare( "SELECT DATE_FORMAT( created, '%%Y-%%m-%%d %%H:%%i:%%s' ) FROM {$table} WHERE id = %d", $id );
	$b = $wpdb->prepare( 'SELECT a %%i b FROM ' . $table, $id );
	return array( $a, $b );
}

// Case 6: %i in a non-prepare() query string — NOT flagged (only prepare() is targeted).
function case_not_prepare() {
	global $wpdb;
	return $wpdb->get_var( 'SELECT progress %i FROM somewhere' );
}
