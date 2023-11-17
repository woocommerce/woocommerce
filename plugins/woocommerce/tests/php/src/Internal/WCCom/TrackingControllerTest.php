<?php
/**
 * Class TrackingControllerTest
 */

namespace Automattic\WooCommerce\Tests\Internal\WCCom;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\WCCom\TrackingController;
use WP_UnitTestCase;

/**
 * Class TrackingControllerTest
 *
 * Contains tests for the TrackingController class.
 */
class TrackingControllerTest extends WP_UnitTestCase {

	/**
	 * System under test.
	 *
	 * @var TrackingController
	 */
	private $sut;

	/**
	 * Set up the test fixture.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new TrackingController();
		$this->sut = $this->getMockBuilder( TrackingController::class )
			->onlyMethods( array( 'is_wccom_cookie_terms_available' ) )
			->getMock();
	}

	/**
	 * Test that the controller registers the filters and actions.
	 *
	 * @return void
	 */
	public function test_controller_does_register_filters_and_actions() {
		$features_mock = $this->createMock( FeaturesController::class );
		$features_mock
			->method( 'feature_is_enabled' )
			->with( 'order_source_attribution' )
			->willReturn( true );
		$this->sut->init( $features_mock );
		$this->sut->method( 'is_wccom_cookie_terms_available' )
			->willReturn( true );

		$this->sut->register();

		$filter = has_filter(
			'wc_order_source_attribution_allow_tracking',
			array( $this->sut, 'is_wccom_tracking_allowed' )
		);
		$this->assertEquals( 10, $filter );

		$action = has_action(
			'wp_enqueue_scripts',
			array( $this->sut, 'enqueue_scripts' )
		);
		$this->assertEquals( 10, $action );
	}

	/**
	 * Test that the controller does not register any filters and actions.
	 *
	 * @return void
	 */
	public function test_controller_does_not_register_any_filters_and_actions() {
		$features_mock = $this->createMock( FeaturesController::class );
		$features_mock
			->method( 'feature_is_enabled' )
			->with( 'order_source_attribution' )
			->willReturn( false );

		$this->sut->init( $features_mock );
		$this->sut->method( 'is_wccom_cookie_terms_available' )
			->willReturn( false );

		$this->sut->register();

		$filter = has_filter(
			'wc_order_source_attribution_allow_tracking',
			array( $this->sut, 'is_wccom_tracking_allowed' )
		);
		$this->assertFalse( $filter );

		$action = has_action(
			'wp_enqueue_scripts',
			array( $this->sut, 'enqueue_scripts' )
		);
		$this->assertFalse( $action );
	}
}
