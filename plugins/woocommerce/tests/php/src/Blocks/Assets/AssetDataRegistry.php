<?php

namespace Automattic\WooCommerce\Tests\Blocks\Assets;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
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
		$this->registry = new AssetDataRegistryMock(
			Package::container()->get( API::class )
		);
	}

	public function test_initial_data() {
		$this->assertEmpty( $this->registry->get() );
	}

	public function test_add_data() {
		$this->registry->add( 'test', 'foo' );
		$this->assertEquals( [ 'test' => 'foo' ], $this->registry->get() );
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
	 * Regression test for woocommerce/woocommerce#54657:
	 *
	 * In wp-admin the registry should hook `enqueue_asset_data` on
	 * `admin_enqueue_scripts` (so data-generation side effects happen during
	 * the enqueue phase rather than during footer printing) while keeping
	 * the historical `admin_print_footer_scripts` hook as a fallback for
	 * late `add()` calls.
	 */
	public function test_admin_hooks_are_registered_for_enqueue_asset_data(): void {
		if ( ! is_admin() ) {
			set_current_screen( 'dashboard' );
		}

		$registry = new AssetDataRegistryMock(
			Package::container()->get( Api::class )
		);

		$this->assertNotFalse(
			has_action( 'admin_enqueue_scripts', array( $registry, 'enqueue_asset_data' ) ),
			'enqueue_asset_data must be hooked on admin_enqueue_scripts so shipping methods are not loaded too late.'
		);
		$this->assertNotFalse(
			has_action( 'admin_print_footer_scripts', array( $registry, 'enqueue_asset_data' ) ),
			'admin_print_footer_scripts fallback must remain so late add() calls still emit data.'
		);
	}

	/**
	 * `enqueue_asset_data` must be idempotent: invoking it twice (once from
	 * the admin_enqueue_scripts hook, again from the admin_print_footer_scripts
	 * fallback) must not emit `wcSettings` twice.
	 */
	public function test_enqueue_asset_data_is_idempotent(): void {
		// Register and enqueue the wc-settings handle so the inline-script
		// guard inside enqueue_asset_data() is satisfied.
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter,WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_script( 'wc-settings', '' );
		wp_enqueue_script( 'wc-settings' );

		$this->registry->add( 'test_key', 'test_value' );

		$this->registry->enqueue_asset_data();
		$this->registry->enqueue_asset_data();

		$inline = wp_scripts()->get_data( 'wc-settings', 'before' );
		$inline = is_array( $inline ) ? implode( '', $inline ) : (string) $inline;

		$this->assertSame(
			1,
			substr_count( $inline, 'var wcSettings = JSON.parse' ),
			'wcSettings must only be emitted once even if enqueue_asset_data() runs multiple times.'
		);

		wp_dequeue_script( 'wc-settings' );
		wp_deregister_script( 'wc-settings' );
	}
}
