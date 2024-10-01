<?php
/**
 * Class Notice_Functions.
 * @package WooCommerce\Tests\Util
 * @since 2.2
 */

/**
 * WC_Tests_Notice_Functions class.
 */
class WC_Tests_Notice_Functions extends WC_Unit_Test_Case {

	/**
	 * Clear out notices after each test.
	 *
	 * @since 2.2
	 */
	public function tearDown(): void {

		WC()->session->set( 'wc_notices', null );
	}

	/**
	 * Test wc_notice_count().
	 *
	 * @since 2.2
	 */
	public function test_wc_notice_count() {

		// No error notices.
		$this->assertEquals( 0, wc_notice_count( 'error' ) );

		// Single notice.
		wc_add_notice( 'Bogus Notice', 'success' );
		$this->assertEquals( 1, wc_notice_count() );

		// Specific notice.
		wc_add_notice( 'Bogus Error Notice', 'error' );
		$this->assertEquals( 1, wc_notice_count( 'error' ) );

		// Multiple notices of different types.
		wc_clear_notices();
		wc_add_notice( 'Bogus 1', 'success' );
		wc_add_notice( 'Bogus 2', 'success' );
		wc_add_notice( 'Bogus Notice 1', 'notice' );
		wc_add_notice( 'Bogus Notice 2', 'notice' );
		wc_add_notice( 'Bogus Error Notice 1', 'error' );
		wc_add_notice( 'Bogus Error Notice 2', 'error' );
		$this->assertEquals( 6, wc_notice_count() );

		// repeat with duplicates.
		wc_add_notice( 'Bogus 1', 'success' );
		wc_add_notice( 'Bogus 2', 'success' );
		wc_add_notice( 'Bogus Notice 1', 'notice' );
		wc_add_notice( 'Bogus Notice 2', 'notice' );
		wc_add_notice( 'Bogus Error Notice 1', 'error' );
		wc_add_notice( 'Bogus Error Notice 2', 'error' );
		$this->assertEquals( 12, wc_notice_count() );
	}

	/**
	 * Test wc_has_notice().
	 *
	 * @since 2.2
	 */
	public function test_wc_has_notice() {

		// Negative.
		wc_add_notice( 'Bogus Notice', 'success' );
		$this->assertFalse( wc_has_notice( 'Legit Notice' ) );

		// Positive.
		wc_add_notice( 'One True Notice', 'notice' );
		$this->assertTrue( wc_has_notice( 'One True Notice', 'notice' ) );
	}

	/**
	 * Test wc_notice_add_notice().
	 *
	 * @since 2.2
	 */
	public function test_wc_add_notice() {

		// Default type.
		wc_add_notice( 'Test Notice' );
		$notices = wc_get_notices();
		$this->assertArrayHasKey( 'success', $notices );
		$this->assertEquals( 'Test Notice', $notices['success'][0]['notice'] );

		// Clear notices.
		WC()->session->set( 'wc_notices', null );

		// Specific type.
		wc_add_notice( 'Test Error Notice', 'error', array( 'id' => 'billing_postcode' ) );
		$notices = wc_get_notices();
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertEquals( 'Test Error Notice', $notices['error'][0]['notice'] );
		$this->assertEquals( array( 'id' => 'billing_postcode' ), $notices['error'][0]['data'] );
	}

	/**
	 * Test wc_clear_notices().
	 *
	 * @since 2.2
	 */
	public function test_wc_clear_notices() {

		wc_add_notice( 'Test Notice' );
		wc_clear_notices();
		$this->assertEmpty( WC()->session->get( 'wc_notices' ) );
	}

	/**
	 * Test wc_print_notices().
	 *
	 * @since 2.2
	 */
	public function test_wc_print_notices() {
		wc_add_notice( 'One True Notice', 'notice' );
		wc_add_notice( 'Second True Notice', 'notice', array( 'id' => 'second_notice' ) );

		$this->expectOutputString( '<div class="woocommerce-info">One True Notice</div><div class="woocommerce-info" data-id="second_notice">Second True Notice</div>' );

		wc_print_notices();

		$this->assertEmpty( WC()->session->get( 'wc_notices' ) );
	}

	/**
	 * Test wc_print_notices() should return notices
	 * when first parameter is set to true.
	 */
	public function test_wc_print_notices_should_return_notices() {
		$expected_return = "\n	<div class=\"woocommerce-info\">\n		One True Notice	</div>\n";

		wc_add_notice( 'One True Notice', 'notice' );

		$actual_return = wc_print_notices( true );
		$normalized_actual_return = preg_replace('/\s+/', '', $actual_return);
		$normalized_expected_return = preg_replace('/\s+/', '', $expected_return);

		$this->assertEquals($normalized_expected_return, $normalized_actual_return);
	}

	/**
	 * Test wc_print_notice() w/ success type.
	 *
	 * @since 2.2
	 */
	public function test_wc_print_success_notice() {

		$this->expectOutputString( '<div class="woocommerce-message" role="alert">Success!</div>' );

		wc_print_notice( 'Success!' );
	}

	/**
	 * Test wc_print_notice() w/ notice type.
	 *
	 * @since 2.2
	 */
	public function test_wc_print_info_notice() {

		$this->expectOutputString( '<div class="woocommerce-info">Info!</div>' );

		wc_print_notice( 'Info!', 'notice' );
	}

	/**
	 * Test wc_print_notice() w/ error type.
	 *
	 * @since 2.2
	 */
	public function test_wc_print_error_notice() {

		// Specific type.
		$this->expectOutputString( '<ul class="woocommerce-error" role="alert"><li>Error!</li></ul>' );

		wc_print_notice( 'Error!', 'error' );
	}

	/**
	 * Test wc_print_notice() w/ data.
	 *
	 * @since 2.2
	 */
	public function test_wc_print_notice_data() {

		// Specific type.
		$this->expectOutputString( '<ul class="woocommerce-error" role="alert"><li data-id="billing_postcode">Error!</li></ul>' );

		wc_print_notice( 'Error!', 'error', array( 'id' => 'billing_postcode' ) );
	}

	/**
	 * Test wc_get_notices().
	 *
	 * @since 2.2
	 */
	public function test_wc_get_notices() {

		// No notices.
		$notices = wc_get_notices();
		$this->assertIsArray( $notices );
		$this->assertEmpty( $notices );

		// Default type.
		wc_add_notice( 'Another Notice' );
		$this->assertEquals(
			array(
				'success' => array(
					array(
						'notice' => 'Another Notice',
						'data'   => array(),
					),
				),
			),
			wc_get_notices()
		);

		// Specific type.
		wc_add_notice( 'Error Notice', 'error', array( 'id' => 'billing_email' ) );
		$this->assertEquals(
			array(
				array(
					'notice' => 'Error Notice',
					'data'   => array( 'id' => 'billing_email' ),
				),
			),
			wc_get_notices( 'error' )
		);

		// Invalid type.
		$notices = wc_get_notices( 'bogus_type' );
		$this->assertIsArray( $notices );
		$this->assertEmpty( $notices );
	}
}
