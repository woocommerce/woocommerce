<?php

use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;

/**
 * Tests relating to WC_REST_Orders_V1_Controller.
 */
class WC_REST_Customer_Downloads_V1_Controller_Tests extends WC_Unit_Test_Case {
	/**
	 * Describes the basic expectations for permission checks, as implemented for the customer downloads endpoint.
	 *
	 * @return void
	 */
	public function test_get_items_permissions_check(): void {
		$sut                 = new WC_REST_Customer_Downloads_V1_Controller();
		$average_user_id     = $this->factory->user->create();
		$shop_manager_id     = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		$valid_customer_id   = $this->factory->user->create();
		$invalid_customer_id = $valid_customer_id + 100;

		wp_set_current_user( $shop_manager_id );
		$request = new WP_REST_Request( 'GET', '/wc/v1/orders' );
		$request->set_query_params( array( 'customer_id' => $valid_customer_id ) );

		$this->assertTrue(
			$sut->get_items_permissions_check( $request ),
			'Requests (here initiated by a shop manager) that specify an valid customer/user will be approved.'
		);

		$request->set_query_params( array() );
		$this->assertWPError(
			$sut->get_items_permissions_check( $request ),
			'Requests that do not specify a customer/user will be rejected.'
		);

		$request->set_query_params( array( 'customer_id' => $invalid_customer_id ) );
		$this->assertWPError(
			$sut->get_items_permissions_check( $request ),
			'Requests that specify an invalid customer/user will be rejected.'
		);

		wp_set_current_user( $average_user_id );
		$this->assertWPError(
			$sut->get_items_permissions_check( $request ),
			'Valid requests specifying an valid customer/user will be rejected if the initiating user does not have the required permissions.'
		);
	}

	/**
	 * @testDox Validated that the permission check for the customer downloads endpoint is multisite-aware.
	 */
	public function test_get_items_permissions_check_multisite() {
		$blog_customer     = $this->factory->user->create( array( 'role' => 'customer' ) );
		$non_blog_customer = $this->factory->user->create( array( 'role' => 'customer' ) );
		$shop_manager      = $this->factory->user->create( array( 'role' => 'shop_manager' ) );

		wp_set_current_user( $shop_manager );

		/** @var MockableLegacyProxy $legacy_proxy_mock Plugged in LegacyProxy instance.*/
		$legacy_proxy_mock = wc_get_container()->get( LegacyProxy::class );
		$legacy_proxy_mock->register_function_mocks(
			array(
				'is_multisite'           => function () {
					return true;
				},
				'is_user_member_of_blog' => function ( $user_id ) use ( $blog_customer ) {
					return $user_id === $blog_customer;
				},
			)
		);

		$sut = new WC_REST_Customer_Downloads_V1_Controller();

		$request = new WP_REST_Request( 'GET', '/wc/v1/customers/' . $blog_customer . '/downloads' );

		$request->set_param( 'customer_id', $blog_customer );
		$this->assertTrue( $sut->get_items_permissions_check( $request ) );

		$request->set_param( 'customer_id', $non_blog_customer );
		$this->assertWPError( $sut->get_items_permissions_check( $request ) );

		$legacy_proxy_mock->reset();
	}
}
