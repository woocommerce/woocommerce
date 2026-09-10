<?php
declare( strict_types = 1 );

/**
 * Tests for the REST API v1 products controller.
 *
 * @package WooCommerce\Tests\RestApi
 */
class WC_REST_Products_V1_Controller_Test extends WC_REST_Unit_Test_Case {

	/**
	 * Set up an administrator for the requests.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * @testdox Getting a product loaded before its global attribute is deleted uses the attribute slug as its name.
	 */
	public function test_get_item_for_product_loaded_before_its_global_attribute_is_deleted(): void {
		update_option( 'woocommerce_feature_product_instance_caching_enabled', 'yes' );
		$attribute = WC_Helper_Product::create_product_attribute_object( 'Stale Finish', array( 'Matte' ) );
		$product   = new WC_Product_Variable();
		$product->set_name( 'Stale attribute product' );
		$product->set_attributes( array( $attribute ) );
		$product->set_default_attributes( array( $attribute->get_name() => 'matte' ) );
		$product->save();
		wc_get_product( $product->get_id() );

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
			$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/products/' . $product->get_id() ) );
		} finally {
			restore_error_handler();
		}

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array(), $warnings, 'Serializing the product should not raise warnings.' );
		$data = $response->get_data();
		$this->assertSame( array( 'stale-finish' ), wp_list_pluck( $data['attributes'], 'name' ) );
		$this->assertSame( array( 'stale-finish' ), wp_list_pluck( $data['default_attributes'], 'name' ) );
	}
}
