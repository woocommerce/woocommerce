<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Product_Attribute class.
 *
 * @package WooCommerce\Tests\Includes
 */
class WC_Product_Attribute_Test extends \WC_Unit_Test_Case {

	/**
	 * @testdox get_taxonomy_object() returns null without a warning after the global attribute is deleted.
	 */
	public function test_get_taxonomy_object_returns_null_without_warning_after_attribute_deletion(): void {
		$attribute = WC_Helper_Product::create_product_attribute_object( 'Stale Finish', array( 'Matte' ) );
		$this->assertSame( 'Stale Finish', $attribute->get_taxonomy_object()->attribute_label, 'The taxonomy object should be available before the deletion.' );

		wc_delete_attribute( $attribute->get_id() );

		$warnings = array();
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Capturing the warning is the assertion; PHPUnit would otherwise convert it to an exception.
		set_error_handler(
			static function ( int $errno, string $errstr ) use ( &$warnings ): bool {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- Reads the level only, to skip warnings silenced with @.
				if ( error_reporting() & $errno ) {
					$warnings[] = $errstr;
				}
				return true;
			}
		);
		try {
			$taxonomy_object = $attribute->get_taxonomy_object();
		} finally {
			restore_error_handler();
		}

		$this->assertNull( $taxonomy_object );
		$this->assertSame( array(), $warnings, 'Reading the taxonomy object of a deleted attribute should not raise a warning.' );
	}

	/**
	 * @testdox get_terms() still returns null after the attribute taxonomy is unregistered.
	 */
	public function test_get_terms_returns_null_after_attribute_deletion(): void {
		$attribute = WC_Helper_Product::create_product_attribute_object( 'Stale Finish', array( 'Matte' ) );
		$this->assertCount( 1, $attribute->get_terms(), 'The attribute should have its term before the deletion.' );

		wc_delete_attribute( $attribute->get_id() );

		$this->assertNull( $attribute->get_terms(), 'Callers rely on null to detect a taxonomy that is not registered.' );
	}
}
