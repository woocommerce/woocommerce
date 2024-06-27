<?php

use Automattic\WooCommerce\Utilities\RestApiUtil;

/**
 * Unit tests for the WooCommerce class.
 */
class WooCommerce_Test extends \WC_Unit_Test_Case {
	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Test that the $api property is defined and holds an instance of RestApiUtil
	 * (the Legacy REST API was removed in WooCommerce 9.0).
	 */
	public function test_api_property(): void {
		$this->assertInstanceOf( RestApiUtil::class, WC()->api );
	}
}
