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
		$this->assertNotFalse( has_filter( 'wc_fulfillment_translate_meta_key', array( $manager, 'translate_fulfillment_meta_key' ) ) );
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
			'wc_fulfillment_meta_key_translations',
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
			'wc_fulfillment_meta_key_translations',
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
		$translated_key = apply_filters( 'wc_fulfillment_translate_meta_key', 'custom_meta_key' );
		$this->assertEquals( __( 'Custom Meta Key', 'woocommerce' ), $translated_key );
	}

	/**
	 * Test that the initial shipping providers are loaded correctly.
	 */
	public function test_get_initial_shipping_providers() {
		/**
		 * Filter to get initial shipping providers
		 *
		 * @since 9.9.0
		 */
		$shipping_providers = apply_filters( 'wc_fulfillment_shipping_providers', array() );
		// Check if the shipping providers are loaded correctly.
		$this->assertIsArray( $shipping_providers );
		$this->assertNotEmpty( $shipping_providers );
	}

	/**
	 * Test that the initial shipping providers can be extended.
	 */
	public function test_extend_initial_shipping_providers() {
		// Extend the shipping providers.
		add_filter(
			'wc_fulfillment_shipping_providers',
			function ( $providers ) {
				$providers['custom_provider'] = array(
					'label' => __( 'Custom Provider', 'woocommerce' ),
					'icon'  => 'custom-icon',
					'value' => 'custom_provider',
				);
				return $providers;
			}
		);

		/**
		 * Filter to get initial shipping providers.
		 *
		 * @since 9.9.0
		 */
		$shipping_providers = apply_filters( 'wc_fulfillment_shipping_providers', array() );

		// Check if the custom provider is included.
		$this->assertArrayHasKey( 'custom_provider', $shipping_providers );
		$this->assertIsArray( $shipping_providers['custom_provider'] );
		$this->assertArrayHasKey( 'label', $shipping_providers['custom_provider'] );
		$this->assertEquals( __( 'Custom Provider', 'woocommerce' ), $shipping_providers['custom_provider']['label'] );
	}
}
