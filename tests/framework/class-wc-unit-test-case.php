<?php
/**
 * WC Unit Test Case
 *
 * Provides WooCommerce-specific setup/tear down/assert methods, custom factories,
 * and helper functions
 *
 * @since 2.2
 */
class WC_Unit_Test_Case extends WP_UnitTestCase {

	/** @var \WC_Unit_Test_Factory instance */
	protected $factory;

	/**
	 * Setup test case
	 *
	 * @since 2.2
	 */
	public function setUp() {

		parent::setUp();

		// add custom factories
		$this->factory = new WC_Unit_Test_Factory();
	}

	/**
	 * Asserts thing is not WP_Error
	 *
	 * @since 2.2
	 * @param mixed $actual
	 * @param string $message
	 */
	public function assertNotWPError( $actual, $message = '' ) {
		$this->assertNotInstanceOf( 'WP_Error', $actual, $message );
	}

}
