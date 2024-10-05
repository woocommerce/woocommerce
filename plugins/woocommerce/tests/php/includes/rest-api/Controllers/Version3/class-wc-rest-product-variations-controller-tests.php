<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;

/**
 * Variations Controller tests for V3 REST API.
 */
class WC_REST_Product_Variations_Controller_Tests extends WC_REST_Unit_Test_Case {
	use CogsAwareUnitTestSuiteTrait;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Products_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->disable_cogs_feature();
	}

	/**
	 * @testdox The variation GET endpoint returns no Cost of Goods information for variations when the feature is disabled.
	 */
	public function test_cogs_values_not_received_for_variation_with_feature_disabled() {
		$this->disable_cogs_feature();

		$parent_product = WC_Helper_Product::create_variation_product();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', "/wc/v3/products/{$parent_product->get_id()}/variations/{$parent_product->get_children()[0]}" ) );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayNotHasKey( 'cost_of_goods_sold', $data );
	}

	/**
	 * @testdox The variation GET endpoint returns the expected Cost of Goods information for a variation when the feature is enabled.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $set_override_flag Value of the "override parent" flag to use.
	 */
	public function test_cogs_values_received_for_variation_with_feature_enabled( bool $set_override_flag ) {
		$this->enable_cogs_feature();

		$parent_product = WC_Helper_Product::create_variation_product();
		$parent_product->set_cogs_value( 12.34 );
		$parent_product->save();

		$variation = wc_get_product( $parent_product->get_children()[0] );
		$variation->set_cogs_value( 56.78 );
		$variation->set_cogs_value_overrides_parent( $set_override_flag );
		$variation->save();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', "/wc/v3/products/{$parent_product->get_id()}/variations/{$variation->get_id()}" ) );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$expected_effective_value = $set_override_flag ? 56.78 : 12.34 + 56.78;
		$expected                 = array(
			'values'                         => array(
				array(
					'defined_value'   => 56.78,
					'effective_value' => $expected_effective_value,
				),
			),
			'defined_value_overrides_parent' => $set_override_flag,
			'total_value'                    => $expected_effective_value,
		);

		$this->assertEquals( $expected, $data['cost_of_goods_sold'] );
	}

	/**
	 * @testdox The variation POST endpoint properly updates the Cost of Goods information for a variation.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $set_override_flag The value of the "override parent value" flag to use.
	 */
	public function test_set_cogs_value_for_variation_via_post_request( bool $set_override_flag ) {
		$this->enable_cogs_feature();

		$variation = wc_get_product( ( WC_Helper_Product::create_variation_product() )->get_children()[0] );
		$variation->set_cogs_value_overrides_parent( $set_override_flag );
		$variation->save();
		$this->assertEquals( 0, $variation->get_cogs_value() );

		$request_body = array(
			'cost_of_goods_sold' => array(
				'values' => array(
					array(
						'defined_value' => 12.34,
					),
					array(
						'defined_value' => 56.78,
					),
				),
			),
		);

		$this->update_variation_via_post_request( $variation, $request_body );

		$variation = wc_get_product( $variation->get_id() );
		$this->assertEquals( 12.34 + 56.78, $variation->get_cogs_value() );
		$this->assertEquals( $set_override_flag, $variation->get_cogs_value_overrides_parent() );
	}

	/**
	 * @testdox The variation POST endpoint properly updates the Cost of Goods information for a variation.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $set_override_flag The value of the "override parent value" flag to use.
	 */
	public function test_set_cogs_override_field_for_variation_via_post_request( bool $set_override_flag ) {
		$this->enable_cogs_feature();

		$variation = wc_get_product( ( WC_Helper_Product::create_variation_product() )->get_children()[0] );
		$variation->set_cogs_value( 12.34 );
		$variation->set_cogs_value_overrides_parent( ! $set_override_flag );
		$variation->save();

		$request_body = array(
			'cost_of_goods_sold' => array(
				'defined_value_overrides_parent' => $set_override_flag,
			),
		);

		$this->update_variation_via_post_request( $variation, $request_body );

		$variation = wc_get_product( $variation->get_id() );
		$this->assertEquals( 12.34, $variation->get_cogs_value() );
		$this->assertEquals( $set_override_flag, $variation->get_cogs_value_overrides_parent() );
	}

	/**
	 * Perform a REST POST request to update a variation.
	 *
	 * @param WC_Product_Variation $variation The variation to update.
	 * @param array                $request_body Data to be sent (JSON-encoded) as the body of the request.
	 */
	private function update_variation_via_post_request( WC_Product_Variation $variation, array $request_body ) {
		$request = new WP_REST_Request( 'POST', "/wc/v3/products/{$variation->get_parent_id()}/variations/{$variation->get_id()}" );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $request_body ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}
}
