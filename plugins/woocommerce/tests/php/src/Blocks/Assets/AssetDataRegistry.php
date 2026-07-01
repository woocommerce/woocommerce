<?php

namespace Automattic\WooCommerce\Tests\Blocks\Assets;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Utilities\UpdateDetection;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
use Automattic\WooCommerce\Tests\Internal\Utilities\UpdateDetectionStub;
use Automattic\WooCommerce\Blocks\Package;
use InvalidArgumentException;

/**
 * Tests for the AssetDataRegistry class.
 *
 * @since $VID:$
 */
class AssetDataRegistry extends \WP_UnitTestCase {
	private $registry;

	protected function setUp(): void {
		parent::setUp();

		$this->registry = new AssetDataRegistryMock(
			Package::container()->get( API::class )
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		wc_get_container()->reset_replacement( UpdateDetection::class );
		wp_dequeue_script( 'wc-settings' );
		parent::tearDown();
	}

	/**
	 * Create an UpdateDetection stub with a controllable update-window state that records log calls.
	 *
	 * @param bool $in_progress The value 'is_update_in_progress' should report.
	 * @return UpdateDetectionStub The stub, already registered as a container replacement.
	 */
	private function replace_update_detection_with_stub( bool $in_progress ): UpdateDetectionStub {
		$stub = new UpdateDetectionStub( $in_progress );
		wc_get_container()->replace( UpdateDetection::class, $stub );

		return $stub;
	}

	/**
	 * @testdox A lazy data callback that throws is dropped and logged without breaking sibling keys.
	 */
	public function test_lazy_data_callback_failure_does_not_break_sibling_keys() {
		$stub = $this->replace_update_detection_with_stub( false );

		$this->registry->add( 'healthy', fn () => 'healthy-value' );
		$this->registry->add(
			'broken',
			function () {
				throw new \Error( 'Class "Automattic\WooCommerce\Some\NewClass" not found' );
			}
		);
		$this->registry->add( 'alsoHealthy', fn () => 'also-healthy-value' );

		$this->registry->execute_lazy_data();
		$data = $this->registry->get();

		$this->assertSame( 'healthy-value', $data['healthy'], 'Keys before the failing one should be populated' );
		$this->assertSame( 'also-healthy-value', $data['alsoHealthy'], 'Keys after the failing one should be populated' );
		$this->assertArrayNotHasKey( 'broken', $data, 'The failing key should be dropped' );
		$this->assertCount( 1, $stub->logged, 'The failure should be logged' );
		$this->assertSame( 'asset_data_registry:broken', $stub->logged[0]['context'] );
	}

	/**
	 * @testdox Lazy data callbacks are skipped and logged when an update window is active.
	 */
	public function test_enqueue_asset_data_skips_lazy_data_during_update_window() {
		$stub = $this->replace_update_detection_with_stub( true );

		$executed = false;
		$this->registry->add(
			'lazyKey',
			function () use ( &$executed ) {
				$executed = true;
				return 'lazy-value';
			}
		);

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter,WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_script( 'wc-settings', '' );
		wp_enqueue_script( 'wc-settings' );

		$this->registry->enqueue_asset_data();

		$this->assertFalse( $executed, 'Lazy callbacks should not execute during an update window' );
		$this->assertArrayNotHasKey( 'lazyKey', $this->registry->get(), 'Lazy data should not be present during an update window' );
		$this->assertCount( 1, $stub->logged, 'The skip should be logged' );
		$this->assertSame( 'asset_data_registry_lazy_data', $stub->logged[0]['context'] );
	}

	/**
	 * @testdox Lazy data callbacks execute normally when no update window is active.
	 */
	public function test_enqueue_asset_data_executes_lazy_data_without_update_window() {
		$this->replace_update_detection_with_stub( false );

		$executed = false;
		$this->registry->add(
			'lazyKey',
			function () use ( &$executed ) {
				$executed = true;
				return 'lazy-value';
			}
		);

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter,WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_script( 'wc-settings', '' );
		wp_enqueue_script( 'wc-settings' );

		$this->registry->enqueue_asset_data();

		$this->assertTrue( $executed, 'Lazy callbacks should execute when no update window is active' );
		$this->assertSame( 'lazy-value', $this->registry->get()['lazyKey'] );
	}

	public function test_initial_data() {
		$this->assertEmpty( $this->registry->get() );
	}

	public function test_add_data() {
		$this->registry->add( 'test', 'foo' );
		$this->assertEquals( [ 'test' => 'foo' ], $this->registry->get() );
	}

	/**
	 * @testdox Deprecated key check argument triggers deprecation notice for explicit values.
	 *
	 * @dataProvider deprecated_key_check_argument_values
	 *
	 * @param bool $check_key_exists Deprecated key check argument value.
	 */
	public function test_add_data_with_deprecated_key_check_argument_triggers_deprecation( $check_key_exists ) {
		$this->setExpectedDeprecated( 'Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::add()' );

		$this->registry->add( 'test', 'foo', $check_key_exists );

		$this->assertEquals( [ 'test' => 'foo' ], $this->registry->get() );
	}

	/**
	 * Provides explicit deprecated key check argument values.
	 *
	 * @return array[]
	 */
	public function deprecated_key_check_argument_values() {
		return [
			'true'  => [ true ],
			'false' => [ false ],
		];
	}

	public function test_data_exists() {
		$this->registry->add( 'foo', 'lorem-ipsum' );
		$this->assertEquals( true, $this->registry->exists( 'foo' ) );
		$this->assertEquals( false, $this->registry->exists( 'bar' ) );
	}

	public function test_add_lazy_data() {
		$lazy = function () {
			return 'bar';
		};
		$this->registry->add( 'foo', $lazy );
		// should not be in data yet
		$this->assertEmpty( $this->registry->get() );
		$this->registry->execute_lazy_data();
		// should be in data now
		$this->assertEquals( [ 'foo' => 'bar' ], $this->registry->get() );
	}

	public function test_invalid_key_on_adding_data() {
		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		$this->registry->add( [ 'some_value' ], 'foo' );
	}

	/**
	 * @testdox Hydrating data does not trigger deprecation notice when key check argument is omitted.
	 */
	public function test_hydrate_data_from_api_request_without_key_check_argument_does_not_trigger_deprecation() {
		$this->registry->hydrate_data_from_api_request( 'test', '/wc/store/v1/test' );

		$this->assertEmpty( $this->registry->get() );
	}

	/**
	 * @testdox Hydrating data with deprecated key check argument triggers deprecation notice.
	 */
	public function test_hydrate_data_from_api_request_with_deprecated_key_check_argument_triggers_deprecation() {
		$this->setExpectedDeprecated( 'Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::hydrate_data_from_api_request()' );

		$this->registry->hydrate_data_from_api_request( 'test', '/wc/store/v1/test', false );

		$this->assertEmpty( $this->registry->get() );
	}

	/**
	 * This tests the 'woocommerce_shared_settings' filter.
	 */
	public function test_woocommerce_filter_with_protected_data() {
		$this->registry->initialize_core_data();
		$original_data = $this->registry->get();
		add_filter( 'woocommerce_shared_settings', [ self::class, 'pdatcallback' ] );
		$data = $this->registry->get();
		$this->registry->initialize_core_data();
		$this->assertEquals( $original_data, $data );
		remove_filter( 'woocommerce_shared_settings', [ self::class, 'pdatcallback' ] );
	}

	public static function pdatcallback( $existing_data ) {
		$existing_data['locale']['siteLocale'] = 'cheeseburger';
		return $existing_data;
	}

	public static function ndcallback( $existing_data ) {
		$existing_data['cheeseburger'] = 'fries';
		return $existing_data;
	}

	public function test_woocommerce_filter_with_new_data() {
		$this->registry->initialize_core_data();
		$original_data = $this->registry->get();
		add_filter( 'woocommerce_shared_settings', [ self::class, 'ndcallback' ] );
		$this->registry->initialize_core_data();
		$data = $this->registry->get();
		$original_data['cheeseburger'] = 'fries';
		$this->assertEquals( $original_data, $data );
		remove_filter( 'woocommerce_shared_settings', [ self::class, 'ndcallback' ] );
	}

	/**
	 * @testdox `experimentalCartSaveForLater` is registered as true when the `cart_save_for_later` feature is enabled.
	 */
	public function test_experimental_cart_save_for_later_setting_is_true_when_feature_enabled() {
		$features_controller = wc_get_container()->get( FeaturesController::class );
		$original_enabled    = $features_controller->feature_is_enabled( 'cart_save_for_later' );

		$features_controller->change_feature_enable( 'cart_save_for_later', true );
		try {
			$this->registry->initialize_core_data();
			$data = $this->registry->get();

			$this->assertArrayHasKey( 'experimentalCartSaveForLater', $data );
			$this->assertTrue( $data['experimentalCartSaveForLater'] );
		} finally {
			$features_controller->change_feature_enable( 'cart_save_for_later', $original_enabled );
		}
	}

	/**
	 * @testdox `experimentalCartSaveForLater` is registered as false when the `cart_save_for_later` feature is disabled.
	 */
	public function test_experimental_cart_save_for_later_setting_is_false_when_feature_disabled() {
		$features_controller = wc_get_container()->get( FeaturesController::class );
		$original_enabled    = $features_controller->feature_is_enabled( 'cart_save_for_later' );

		$features_controller->change_feature_enable( 'cart_save_for_later', false );
		try {
			$this->registry->initialize_core_data();
			$data = $this->registry->get();

			$this->assertArrayHasKey( 'experimentalCartSaveForLater', $data );
			$this->assertFalse( $data['experimentalCartSaveForLater'] );
		} finally {
			$features_controller->change_feature_enable( 'cart_save_for_later', $original_enabled );
		}
	}
}
