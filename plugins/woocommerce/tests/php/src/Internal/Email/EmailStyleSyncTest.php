<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Email;

use Automattic\WooCommerce\Internal\Email\EmailStyleSync;
use WC_Unit_Test_Case;

/**
 * EmailStyleSync test.
 *
 * @covers \Automattic\WooCommerce\Internal\Email\EmailStyleSync
 */
class EmailStyleSyncTest extends WC_Unit_Test_Case {
	/**
	 * "System Under Test", an instance of the class to be tested.
	 *
	 * @var EmailStyleSync
	 */
	private $sut;

	/**
	 * Original option values to restore after tests.
	 *
	 * @var array
	 */
	private $original_options;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new EmailStyleSync();

		// Store original option values.
		$this->original_options = array(
			EmailStyleSync::AUTO_SYNC_OPTION          => get_option( EmailStyleSync::AUTO_SYNC_OPTION, false ),
			'woocommerce_email_base_color'            => get_option( 'woocommerce_email_base_color', '' ),
			'woocommerce_email_background_color'      => get_option( 'woocommerce_email_background_color', '' ),
			'woocommerce_email_body_background_color' => get_option( 'woocommerce_email_body_background_color', '' ),
			'woocommerce_email_text_color'            => get_option( 'woocommerce_email_text_color', '' ),
			'woocommerce_email_footer_text_color'     => get_option( 'woocommerce_email_footer_text_color', '' ),
		);

		// Ensure we have a clean state for each test.
		delete_option( EmailStyleSync::AUTO_SYNC_OPTION );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Restore original option values.
		foreach ( $this->original_options as $option_name => $option_value ) {
			if ( $option_value ) {
				update_option( $option_name, $option_value );
			} else {
				delete_option( $option_name );
			}
		}
	}

	/**
	 * Test auto-sync is disabled by default.
	 */
	public function test_auto_sync_disabled_by_default() {
		$this->assertFalse( $this->sut->is_auto_sync_enabled() );
	}

	/**
	 * Test setting auto-sync option.
	 */
	public function test_set_auto_sync() {
		$this->sut->set_auto_sync( false );
		$this->assertFalse( $this->sut->is_auto_sync_enabled() );

		$this->sut->set_auto_sync( true );
		$this->assertTrue( $this->sut->is_auto_sync_enabled() );
	}

	/**
	 * Test maybe_sync_on_option_update triggers sync when auto-sync is enabled.
	 */
	public function test_maybe_sync_on_option_update_when_enabled() {
		$mock = $this->getMockBuilder( EmailStyleSync::class )
			->onlyMethods( array( 'update_email_colors' ) )
			->getMock();

		$mock->expects( $this->once() )
			->method( 'update_email_colors' );

		$mock->maybe_sync_on_option_update( 'no', 'yes', EmailStyleSync::AUTO_SYNC_OPTION );
	}

	/**
	 * Test maybe_sync_on_option_update doesn't trigger sync when auto-sync is disabled.
	 */
	public function test_maybe_sync_on_option_update_when_disabled() {
		$mock = $this->getMockBuilder( EmailStyleSync::class )
			->onlyMethods( array( 'update_email_colors' ) )
			->getMock();

		$mock->expects( $this->never() )
			->method( 'update_email_colors' );

		$mock->maybe_sync_on_option_update( 'yes', 'no', EmailStyleSync::AUTO_SYNC_OPTION );
	}

	/**
	 * Test maybe_sync_on_option_update doesn't trigger sync when auto-sync value doesn't change.
	 */
	public function test_maybe_sync_on_option_update_when_unchanged() {
		$mock = $this->getMockBuilder( EmailStyleSync::class )
			->onlyMethods( array( 'update_email_colors' ) )
			->getMock();

		$mock->expects( $this->never() )
			->method( 'update_email_colors' );

		// Test when already enabled.
		$mock->maybe_sync_on_option_update( 'yes', 'yes', EmailStyleSync::AUTO_SYNC_OPTION );

		// Test when already disabled.
		$mock->maybe_sync_on_option_update( 'no', 'no', EmailStyleSync::AUTO_SYNC_OPTION );
	}

	/**
	 * A hook for returning false in filters, safe to add and remove in tests.
	 */
	public function return_false() {
		return false;
	}
}
