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

		$this->expectOutputString( '<div class="wc-block-components-notice-banner is-info" role="alert"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"></path></svg><div class="wc-block-components-notice-banner__content">One True Notice</div></div><div class="wc-block-components-notice-banner is-info" data-id="second_notice" role="alert"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"></path></svg><div class="wc-block-components-notice-banner__content">Second True Notice</div></div>' );

		wc_print_notices();

		$this->assertEmpty( WC()->session->get( 'wc_notices' ) );
	}

	/**
	 * Test wc_print_notices() should return notices
	 * when first parameter is set to true.
	 */
	public function test_wc_print_notices_should_return_notices() {
		$expected_return = '<div class="wc-block-components-notice-banner is-info" role="alert"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"></path></svg><div class="wc-block-components-notice-banner__content">One True Notice</div></div>';

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

		$this->expectOutputString( '<div class="wc-block-components-notice-banner is-success" role="alert"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg><div class="wc-block-components-notice-banner__content">Success!</div></div>' );

		wc_print_notice( 'Success!' );
	}

	/**
	 * Test wc_print_notice() w/ notice type.
	 *
	 * @since 2.2
	 */
	public function test_wc_print_info_notice() {

		$this->expectOutputString( '<div class="wc-block-components-notice-banner is-info" role="alert"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"></path></svg><div class="wc-block-components-notice-banner__content">Info!</div></div>' );

		wc_print_notice( 'Info!', 'notice' );
	}

	/**
	 * Test wc_print_notice() w/ error type.
	 *
	 * @since 2.2
	 */
	public function test_wc_print_error_notice() {

		// Specific type.
		$this->expectOutputString( '<div class="wc-block-components-notice-banner is-error" role="alert"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"></path></svg><div class="wc-block-components-notice-banner__content">Error!</div></div>' );

		wc_print_notice( 'Error!', 'error' );
	}

	/**
	 * Test wc_print_notice() w/ data.
	 *
	 * @since 2.2
	 */
	public function test_wc_print_notice_data() {

		// Specific type.
		$this->expectOutputString( '<div class="wc-block-components-notice-banner is-error" role="alert" data-id="billing_postcode"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"></path></svg><div class="wc-block-components-notice-banner__content">Error!</div></div>' );

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
