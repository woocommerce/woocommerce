<?php
/**
 * Helper methods for extracting database schema.
 *
 * @package WooCommerce
 */

// phpcs:disable PHPCompatibility.Classes.NewLateStaticBinding.OutsideClassScope
/**
 * Get database schema.
 */
function wc_get_schema() {
	$schema = function () {
		return static::get_schema();
	};

	return $schema->call( new \WC_Install() );
}

echo( esc_sql( wc_get_schema() ) );
