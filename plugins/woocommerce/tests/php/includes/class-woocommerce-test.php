<?php

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
	 * Test that the $api property is defined, public and is null
	 * (the Legacy REST API was removed in WooCommerce 9.0).
	 */
	public function test_api_property(): void {
		$property = new ReflectionProperty( WooCommerce::class, 'api' );

		$this->assertTrue( $property->isPublic() );
		$this->assertNull( $property->getValue( WC() ) );
	}
}
