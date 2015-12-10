<?php

namespace WooCommerce\Tests\Util;

/**
 * Class Notice_Functions.
 * @package WooCommerce\Tests\Util
 * @since 2.2
 */
class Notice_Functions extends \WC_Unit_Test_Case {

	/**
	 * Clear out notices after each test.
	 *
	 * @since 2.2
	 */
	public function tearDown() {

		WC()->session->set( 'wc_notices', null );
	}

	/**
	 * Test wc_notice_count().
	 *
	 * @since 2.2
	 */
	function test_wc_notice_count() {

		// no error notices
		$this->assertEquals( 0, wc_notice_count( 'error' ) );

		// single notice
		wc_add_notice( 'Bogus Notice', 'success' );
		$this->assertEquals( 1, wc_notice_count() );

		// specific notice
		wc_add_notice( 'Bogus Error Notice', 'error' );
		$this->assertEquals( 1, wc_notice_count( 'error' ) );

		// multiple notices of different types
		wc_add_notice( 'Bogus Notice 2', 'success' );
		wc_add_notice( 'Bogus Error Notice 2', 'error' );
		$this->assertEquals( 4, wc_notice_count() );
	}

	/**
	 * Test wc_has_notice().
	 *
	 * @since 2.2
	 */
	function test_wc_has_notice() {

		// negative
		wc_add_notice( 'Bogus Notice', 'success' );
		$this->assertFalse( wc_has_notice( 'Legit Notice' ) );

		// positive
		wc_add_notice( 'One True Notice', 'notice' );
		$this->assertTrue( wc_has_notice( 'One True Notice', 'notice' ) );
	}

	/**
	 * Test wc_notice_add_notice().
	 *
	 * @since 2.2
	 */
	function test_wc_add_notice() {

		// default type
		wc_add_notice( 'Test Notice' );
		$notices = wc_get_notices();
		$this->assertArrayHasKey( 'success', $notices );
		$this->assertEquals( 'Test Notice', $notices['success'][0] );

		// clear notices
		WC()->session->set( 'wc_notices', null );

		// specific type
		wc_add_notice( 'Test Error Notice', 'error' );
		$notices = wc_get_notices();
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertEquals( 'Test Error Notice', $notices['error'][0] );
	}

	/**
	 * Test wc_clear_notices().
	 *
	 * @since 2.2
	 */
	function test_wc_clear_notices() {

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

		$this->expectOutputString( '<div class="woocommerce-info">One True Notice</div>' );

		wc_print_notices();

		$this->assertEmpty( WC()->session->get( 'wc_notices' ) );
	}

	/**
	 * Test actions that print the notices.
	 *
	 * @since 2.2
	 */
	public function test_wc_print_notices_actions() {

		$this->assertNotFalse( has_action( 'woocommerce_before_shop_loop', 'wc_print_notices' ) );
		$this->assertNotFalse( has_action( 'woocommerce_before_single_product', 'wc_print_notices' ) );
	}

	/**
	 * Test wc_print_notice() w/ success type.
	 *
	 * @since 2.2
	 */
	public function test_wc_print_success_notice() {

		$this->expectOutputString( '<div class="woocommerce-message">Success!</div>' );

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

		// specific type
		$this->expectOutputString( '<ul class="woocommerce-error"><li>Error!</li></ul>' );

		wc_print_notice( 'Error!', 'error' );
	}

	/**
	 * Test wc_get_notices().
	 *
	 * @since 2.2
	 */
	public function test_wc_get_notices() {

		// no notices
		$notices = wc_get_notices();
		$this->assertInternalType( 'array', $notices );
		$this->assertEmpty( $notices );

		// default type
		wc_add_notice( 'Another Notice' );
		$this->assertEquals( array( 'success' => array( 'Another Notice' ) ), wc_get_notices() );

		// specific type
		wc_add_notice( 'Error Notice', 'error' );
		$this->assertEquals( array( 'Error Notice' ), wc_get_notices( 'error' ) );

		// invalid type
		$notices = wc_get_notices( 'bogus_type' );
		$this->assertInternalType( 'array', $notices );
		$this->assertEmpty( $notices );
	}

}
