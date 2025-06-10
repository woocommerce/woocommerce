<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsManager;

/**
 * Tests for Fulfillment object.
 */
class FulfillmentsManagerTest extends \WC_Unit_Test_Case {

	/**
	 * Test hooks.
	 */
	public function test_hooks() {
		$manager = new FulfillmentsManager();
		$this->assertNotFalse( has_filter( 'woocommmerce_fulfillment_translate_meta_key', array( $manager, 'translate_fulfillment_meta_key' ) ) );
	}

	/**
	 * Test the translate_fulfillment_meta_key method.
	 */
	public function test_translate_fulfillment_meta_key() {
		$manager = new FulfillmentsManager();

		// Test with a known meta key.
		$translated_key = $manager->translate_fulfillment_meta_key( 'fulfillment_status' );
		$this->assertEquals( __( 'Fulfillment Status', 'woocommerce' ), $translated_key );

		// Test with an unknown meta key.
		$translated_key = $manager->translate_fulfillment_meta_key( 'unknown_meta_key' );
		$this->assertEquals( 'unknown_meta_key', $translated_key );
	}

	/**
	 * Test extending the translation of a fulfillment meta key.
	 */
	public function test_extend_translate_fulfillment_meta_key() {
		$manager = new FulfillmentsManager();

		// Extend the translations.
		add_filter(
			'woocommerce_fulfillment_meta_key_translations',
			function ( $translations ) {
				$translations['custom_meta_key'] = __( 'Custom Meta Key', 'woocommerce' );
				return $translations;
			}
		);

		// Test the extended translation.
		$translated_key = $manager->translate_fulfillment_meta_key( 'custom_meta_key' );
		$this->assertEquals( __( 'Custom Meta Key', 'woocommerce' ), $translated_key );
	}

	/**
	 * Test that the filter for translating fulfillment meta keys works correctly.
	 */
	public function test_translate_fulfillment_meta_key_with_filter() {
		new FulfillmentsManager();

		// Add a filter to modify the translations.
		add_filter(
			'woocommerce_fulfillment_meta_key_translations',
			function ( $translations ) {
				$translations['custom_meta_key'] = __( 'Custom Meta Key', 'woocommerce' );
				return $translations;
			}
		);

		/**
		 * Filter to translate fulfillment meta keys.
		 *
		 * @since 9.9.0
		 */
		$translated_key = apply_filters( 'woocommmerce_fulfillment_translate_meta_key', 'custom_meta_key' );
		$this->assertEquals( __( 'Custom Meta Key', 'woocommerce' ), $translated_key );
	}
}
