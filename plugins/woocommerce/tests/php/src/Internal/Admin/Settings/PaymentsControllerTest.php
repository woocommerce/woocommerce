<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;

/**
 * PaymentsController tests.
 *
 * @class PaymentsController
 */
class PaymentsControllerTest extends WC_Unit_Test_Case {
	/**
	 * Payments service mock.
	 *
	 * @var Payments|MockObject
	 */
	private $payments;

	/**
	 * WooPayments legacy runtime mock.
	 *
	 * @var WooPaymentsLegacyRuntime|MockObject
	 */
	private $legacy_runtime;

	/**
	 * System under test.
	 *
	 * @var PaymentsController
	 */
	private PaymentsController $sut;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->payments       = $this->createMock( Payments::class );
		$this->legacy_runtime = $this->getMockBuilder( WooPaymentsLegacyRuntime::class )
			->onlyMethods( array( 'is_loaded', 'is_account_onboarded_from_cache' ) )
			->getMock();
		$this->sut            = new PaymentsController();
		$this->sut->init( $this->payments, $this->legacy_runtime );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * @testdox Should read WooPayments account ownership through the injected runtime.
	 */
	public function test_woopayments_account_ownership_uses_injected_runtime(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read a local source file for a boundary assertion.
		$source = (string) file_get_contents( WC()->plugin_path() . '/src/Internal/Admin/Settings/PaymentsController.php' );

		$this->assertStringNotContainsString( "class_exists( '\\WC_Payments' )", $source, 'PaymentsController should ask WooPaymentsLegacyRuntime whether WooPayments is loaded.' );
		$this->assertStringNotContainsString( "'wcpay_account_data'", $source, 'PaymentsController should read WooPayments account cache through WooPaymentsLegacyRuntime.' );
	}

	/**
	 * @testdox Should skip Core Payments menu when WooPayments owns the onboarded account menu.
	 */
	public function test_add_menu_skips_core_payments_menu_when_woopayments_account_is_onboarded(): void {
		global $menu;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Isolate admin menu assertions in this test.
		$menu = array();

		$this->legacy_runtime
			->expects( $this->once() )
			->method( 'is_loaded' )
			->willReturn( true );
		$this->legacy_runtime
			->expects( $this->once() )
			->method( 'is_account_onboarded_from_cache' )
			->willReturn( true );

		$this->sut->add_menu();

		$payment_menu_items = array_filter(
			$menu,
			static fn( $menu_item ) => is_array( $menu_item ) && isset( $menu_item[2] ) && str_starts_with( (string) $menu_item[2], 'admin.php?page=wc-settings&tab=checkout' )
		);

		$this->assertSame( array(), array_values( $payment_menu_items ) );
	}
}
