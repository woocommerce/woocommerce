<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;


use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * AdditionalFields Controller Tests.
 *
 *
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r, WooCommerce.Commenting.CommentHooks.MissingHookComment
 */
class AdditionalFields extends MockeryTestCase {
	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		add_action( 'register_fields', array( $this, 'register_fields' ) );

		do_action( 'register_fields' );

		wp_set_current_user( 0 );
		$customer = get_user_by( 'email', 'testaccount@test.com' );

		if ( $customer ) {
			wp_delete_user( $customer->ID );
		}
		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();
		$fixtures->payments_enable_bacs();
		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'stock_status'  => 'instock',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
		);
		wc_empty_cart();
		$this->keys   = array();
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart( $this->products[1]->get_id(), 1 );
	}

		/**
		 * Tear down Rest API server.
		 */
	protected function tearDown(): void {
		parent::tearDown();
		global $wp_rest_server;
		$wp_rest_server = null;
		remove_action( 'register_fields', array( $this, 'register_fields' ) );
	}

	public function register_fields() {
		__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => 'plugin-namespace/gov-id',
				'label'    => 'Government ID',
				'location' => 'address',
				'type'     => 'text',
				'required' => true,
			),
		);
	}

	public function test_additional_fields_options() {
		$request  = new \WP_REST_Request( 'OPTIONS', '/wc/store/v1/checkout' );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals(
			array(
				'description' => 'Government ID',
				'type'        => 'string',
				'context'     => array(
					'view',
					'edit',
				),
				'required'    => true,
			),
			$data['schema']['properties']['billing_address']['properties']['plugin-namespace/gov-id'],
			print_r( $data['schema']['properties']['billing_address']['properties'], true )
		);
	}
}
