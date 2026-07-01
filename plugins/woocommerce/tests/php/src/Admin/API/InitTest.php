<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Admin\API\Init;
use Automattic\WooCommerce\Admin\API\Notice;
use Automattic\WooCommerce\Internal\Utilities\UpdateDetection;
use Automattic\WooCommerce\Tests\Internal\Utilities\UpdateDetectionStub;
use WC_Unit_Test_Case;

/**
 * Tests for the REST API Init class.
 */
class InitTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wc_get_container()->reset_replacement( UpdateDetection::class );
		remove_all_filters( 'woocommerce_admin_rest_controllers' );
		parent::tearDown();
	}

	/**
	 * @testdox REST controller registration is skipped and logged while a WooCommerce update window is active.
	 */
	public function test_rest_api_init_skips_registration_during_update_window(): void {
		$stub = new UpdateDetectionStub( true );
		wc_get_container()->replace( UpdateDetection::class, $stub );

		$sut = new Init();
		$sut->rest_api_init();

		$this->assertCount( 1, $stub->logged, 'The skipped registration should be logged' );
		$this->assertSame( 'wc_admin_rest_controllers', $stub->logged[0]['context'] );
	}

	/**
	 * @testdox A controller class that cannot be loaded is skipped and logged instead of fataling, without affecting other controllers.
	 */
	public function test_unloadable_rest_controller_is_skipped_and_logged(): void {
		$this->setExpectedIncorrectUsage( 'register_rest_route' );

		$stub = new UpdateDetectionStub( false );
		wc_get_container()->replace( UpdateDetection::class, $stub );

		$missing_controller = 'Automattic\WooCommerce\Admin\API\ClassThatDoesNotExist';
		add_filter(
			'woocommerce_admin_rest_controllers',
			function ( $controllers ) use ( $missing_controller ) {
				$controllers[] = $missing_controller;
				return $controllers;
			}
		);

		$sut = new Init();
		$sut->rest_api_init_wc_admin();

		$skip_contexts = array_column( $stub->logged, 'context' );
		$this->assertContains( 'rest_controller:' . $missing_controller, $skip_contexts, 'The unloadable controller should be logged as skipped' );
		$this->assertInstanceOf( Notice::class, $sut->{Notice::class}, 'Loadable controllers should still be registered' );
	}
}
