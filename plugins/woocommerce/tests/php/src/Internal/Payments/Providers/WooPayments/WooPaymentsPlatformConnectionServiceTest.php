<?php
/**
 * WooPaymentsPlatformConnectionService tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPlatformConnectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPayments platform connection readiness service.
 */
class WooPaymentsPlatformConnectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should report no cutover failures when the native platform transport is ready.
	 */
	public function test_get_cutover_preflight_failures_returns_empty_for_ready_transport(): void {
		$manager = $this->create_connection_manager( true, 1, true );
		$sut     = new TestableWooPaymentsPlatformConnectionService();

		$sut->manager = $manager;
		$sut->blog_id = 123;

		$this->assertSame( array(), $sut->get_cutover_preflight_failures() );
	}

	/**
	 * @testdox Should report granular local WPCOM platform readiness failures.
	 */
	public function test_get_cutover_preflight_failures_reports_granular_platform_codes(): void {
		$manager = $this->create_connection_manager( false, 0, false );
		$sut     = new TestableWooPaymentsPlatformConnectionService();

		$sut->manager = $manager;
		$sut->blog_id = null;

		$this->assertSame(
			array(
				'wpcom_connection_unavailable',
				'wpcom_blog_id_unavailable',
				'wpcom_connection_owner_unavailable',
			),
			$sut->get_cutover_preflight_failures()
		);
	}

	/**
	 * @testdox Should require a connection-owner user token for cutover readiness.
	 */
	public function test_get_cutover_preflight_failures_requires_connection_owner_user_token(): void {
		$manager = $this->create_connection_manager( true, 1, false );
		$sut     = new TestableWooPaymentsPlatformConnectionService();

		$sut->manager = $manager;
		$sut->blog_id = 123;

		$this->assertSame(
			array( 'wpcom_connection_owner_user_token_unavailable' ),
			$sut->get_cutover_preflight_failures()
		);
	}

	/**
	 * Create a Jetpack connection manager mock.
	 *
	 * @param bool $is_connected                Whether the store is connected.
	 * @param int  $connection_owner_id         Connection owner ID.
	 * @param bool $connection_owner_connected  Whether the connection owner has a user token.
	 * @return JetpackConnectionManager
	 */
	private function create_connection_manager( bool $is_connected, int $connection_owner_id, bool $connection_owner_connected ): JetpackConnectionManager {
		$manager = $this->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_connected', 'get_connection_owner_id', 'is_user_connected' ) )
			->getMock();

		$manager
			->method( 'is_connected' )
			->willReturn( $is_connected );
		$manager
			->method( 'get_connection_owner_id' )
			->willReturn( $connection_owner_id );
		$manager
			->method( 'is_user_connected' )
			->willReturn( $connection_owner_connected );

		return $manager;
	}
}
