<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Notes;

use Automattic\WooCommerce\Internal\Admin\Notes\WooCommercePayments;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
use WC_Unit_Test_Case;

/**
 * WooCommercePayments note provider tests.
 *
 * @class WooCommercePayments
 */
class WooCommercePaymentsTest extends WC_Unit_Test_Case {
	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		$this->reset_container_replacements();
		wc_get_container()->reset_all_resolved();

		parent::tearDown();
	}

	/**
	 * @testdox Should read WooPayments active state through WooPaymentsLegacyRuntime.
	 */
	public function test_wcpay_active_check_uses_legacy_runtime(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read a local source file for a boundary assertion.
		$source = (string) file_get_contents( WC()->plugin_path() . '/src/Internal/Admin/Notes/WooCommercePayments.php' );

		$this->assertStringNotContainsString( "defined( 'WC_Payments' )", $source, 'WooCommercePayments note should ask WooPaymentsLegacyRuntime whether WooPayments is loaded.' );
	}

	/**
	 * @testdox Should treat WooPayments as installed when the runtime is loaded.
	 */
	public function test_is_installed_returns_true_when_woopayments_runtime_is_loaded(): void {
		$legacy_runtime = $this->getMockBuilder( WooPaymentsLegacyRuntime::class )
			->onlyMethods( array( 'is_loaded' ) )
			->getMock();
		$legacy_runtime
			->expects( $this->once() )
			->method( 'is_loaded' )
			->willReturn( true );
		wc_get_container()->replace( WooPaymentsLegacyRuntime::class, $legacy_runtime );
		wc_get_container()->reset_all_resolved();

		$is_installed = new \ReflectionMethod( WooCommercePayments::class, 'is_installed' );
		$is_installed->setAccessible( true );

		$this->assertTrue( $is_installed->invoke( null ) );
	}
}
