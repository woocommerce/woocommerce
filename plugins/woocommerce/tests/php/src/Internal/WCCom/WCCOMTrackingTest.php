<?php

namespace Automattic\WooCommerce\Tests\Internal\ntegration;

use Automattic\WooCommerce\Internal\WCCom\TrackingController as WCCOMTracking;
use WP_UnitTestCase;

class WCCOMTrackingTest extends WP_UnitTestCase {

	/**
	 * @var WCCOMTracking $WCCOMTrackingIntegration
	 */
	private $WCCOMTrackingIntegration;

	/**
	 * {@inheritdoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->WCCOMTrackingIntegration = $this->getMockBuilder( WCCOMTracking::class )
			->onlyMethods( [ 'is_WCCom_Cookie_Terms_available', 'is_wccom_tracking_allowed' ] )
			->getMock();
	}

	public function test_wccom_tracking_not_allowed(): void {
		$this->WCCOMTrackingIntegration->method( 'is_wccom_cookie_terms_available' )->willReturn( true );
		$this->WCCOMTrackingIntegration->method( 'is_wccom_tracking_allowed' )->willReturn( false );
		$this->WCCOMTrackingIntegration->register();

		$this->assertFalse( apply_filters( 'wc_order_source_attribution_allow_tracking', true ) );
	}

	public function test_wccom_tracking_allowed(): void {
		$this->WCCOMTrackingIntegration->method( 'is_wccom_cookie_terms_available' )->willReturn( true );
		$this->WCCOMTrackingIntegration->method( 'is_wccom_tracking_allowed' )->willReturn( true );
		$this->WCCOMTrackingIntegration->register();

		$this->assertTrue( apply_filters( 'wc_order_source_attribution_allow_tracking', false ) );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function tearDown(): void {
		parent::tearDown();
		unset( $this->WCCOMTrackingIntegration );
	}
}
