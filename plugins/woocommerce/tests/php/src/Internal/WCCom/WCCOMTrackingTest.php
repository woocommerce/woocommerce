<?php

namespace Automattic\WooCommerce\Tests\Internal\ntegration;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\WCCom\TrackingController as WCCOMTracking;
use WP_UnitTestCase;

/**
 * Tests for WCCOMTracking.
 *
 * @since x.x.x
 */
class WCCOMTrackingTest extends WP_UnitTestCase {

	/**
	 * @var WCCOMTracking $wccom_tracking_integration
	 */
	private $wccom_tracking_integration;

	/**
	 * {@inheritdoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->wccom_tracking_integration = $this->getMockBuilder( WCCOMTracking::class )
												->onlyMethods( array( 'is_WCCom_Cookie_Terms_available', 'is_wccom_tracking_allowed' ) )
												->getMock();

		$mock_features_controller = $this->getMockBuilder( FeaturesController::class )->getMock();
		$mock_features_controller->method( 'feature_is_enabled' )->with( 'order_source_attribution' )->willReturn( true );

		$this->wccom_tracking_integration->init( $mock_features_controller );
	}

	/**
	 * Test that the filter returns false if tracking is not allowed.
	 */
	public function test_wccom_tracking_not_allowed(): void {
		$this->wccom_tracking_integration->method( 'is_wccom_cookie_terms_available' )->willReturn( true );
		$this->wccom_tracking_integration->method( 'is_wccom_tracking_allowed' )->willReturn( false );
		$this->wccom_tracking_integration->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- test code.
		$this->assertFalse( apply_filters( 'wc_order_source_attribution_allow_tracking', true ) );
	}

	/**
	 * Test that the filter returns true if tracking is allowed.
	 */
	public function test_wccom_tracking_allowed(): void {
		$this->wccom_tracking_integration->method( 'is_wccom_cookie_terms_available' )->willReturn( true );
		$this->wccom_tracking_integration->method( 'is_wccom_tracking_allowed' )->willReturn( true );
		$this->wccom_tracking_integration->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- test code.
		$this->assertTrue( apply_filters( 'wc_order_source_attribution_allow_tracking', false ) );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function tearDown(): void {
		parent::tearDown();
		unset( $this->wccom_tracking_integration );
	}
}
