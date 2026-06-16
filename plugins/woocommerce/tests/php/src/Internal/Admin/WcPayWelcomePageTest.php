<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestionIncentives;
use Automattic\WooCommerce\Internal\Admin\WcPayWelcomePage;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;

/**
 * WcPayWelcomePage tests.
 *
 * @class WcPayWelcomePage
 */
class WcPayWelcomePageTest extends WC_Unit_Test_Case {
	/**
	 * Suggestion incentives mock.
	 *
	 * @var PaymentsExtensionSuggestionIncentives|MockObject
	 */
	private $suggestion_incentives;

	/**
	 * WooPayments legacy runtime mock.
	 *
	 * @var WooPaymentsLegacyRuntime|MockObject
	 */
	private $legacy_runtime;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->suggestion_incentives = $this->createMock( PaymentsExtensionSuggestionIncentives::class );
		$this->legacy_runtime        = $this->getMockBuilder( WooPaymentsLegacyRuntime::class )
			->onlyMethods( array( 'is_loaded' ) )
			->getMock();
	}

	/**
	 * @testdox Should read WooPayments active state through WooPaymentsLegacyRuntime.
	 */
	public function test_wcpay_active_check_uses_legacy_runtime(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read a local source file for a boundary assertion.
		$source = (string) file_get_contents( WC()->plugin_path() . '/src/Internal/Admin/WcPayWelcomePage.php' );

		$this->assertStringNotContainsString( "class_exists( '\\WC_Payments' )", $source, 'WcPayWelcomePage should ask WooPaymentsLegacyRuntime whether WooPayments is loaded.' );
	}

	/**
	 * @testdox Should not fetch incentives when WooPayments is already loaded.
	 */
	public function test_has_incentive_returns_false_when_woopayments_is_loaded(): void {
		$this->legacy_runtime
			->expects( $this->once() )
			->method( 'is_loaded' )
			->willReturn( true );
		$this->suggestion_incentives
			->expects( $this->never() )
			->method( 'get_incentive' );

		$sut = new WcPayWelcomePage( $this->suggestion_incentives, $this->legacy_runtime );

		$this->assertFalse( $sut->has_incentive() );
	}
}
