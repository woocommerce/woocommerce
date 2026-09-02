<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\RemoteFreeExtensions;

use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\DefaultFreeExtensions;
use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\EvaluateExtension;
use WC_Unit_Test_Case;

/**
 * DefaultFreeExtensions test.
 *
 * @class DefaultFreeExtensionsTest
 */
class DefaultFreeExtensionsTest extends WC_Unit_Test_Case {
	/**
	 * Raw option states before the test fixture mutates them.
	 *
	 * @var array<string, array{exists: bool, value: string|null, autoload: string|null}>
	 */
	private array $initial_option_states = array();


	/**
	 * Mock of bundles of extensions to recommend.
	 *
	 * We will test the `is_visible` conditions on the plugins themselves.
	 *
	 * @var array
	 */
	private $bundles_mock;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		foreach ( $this->get_restored_option_names() as $option_name ) {
			$this->initial_option_states[ $option_name ] = $this->get_raw_option_state( $option_name );
		}

		update_option( 'woocommerce_default_country', 'US:CA' );

		/*
		 * Required for the BaseLocationCountryRuleProcessor
		 * to not return false for "US:CA" country-state combo.
		 */
		update_option( 'woocommerce_store_address', 'foo' );

		update_option( 'active_plugins', array( 'foo/foo.php' ) );

		$this->bundles_mock = array(
			array(
				'key'     => 'foo',
				'title'   => 'Test bundle',
				'plugins' => array(
					DefaultFreeExtensions::get_plugin( 'woocommerce-shipping' ),
					DefaultFreeExtensions::get_plugin( 'woocommerce-services:tax' ),
				),
			),
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			parent::tearDown();
		} finally {
			$failures = $this->restore_initial_option_states();
			$this->assert_initial_option_states_restored( $failures );
		}
	}

	/**
	 * Tests the default behavior of recommending WCS&T as the tax solution.
	 *
	 * @return void
	 */
	public function test_wcservices_is_recommended_for_tax() {
		$recommended_plugin_slugs = $this->get_recommended_plugin_slugs( $this->bundles_mock );

		$this->assertContains( 'woocommerce-services:tax', $recommended_plugin_slugs );
	}

	/**
	 * Tests the default behavior of recommending WC Shipping as the shipping solution.
	 *
	 * @return void
	 */
	public function test_wcshipping_is_recommended_for_shipping() {
		$recommended_plugin_slugs = $this->get_recommended_plugin_slugs( $this->bundles_mock );

		$this->assertContains( 'woocommerce-shipping', $recommended_plugin_slugs );
	}

	/**
	 * Asserts WCS&T is not recommended in unsupported countries.
	 *
	 * @return void
	 */
	public function test_wcservices_is_not_recommended_if_in_an_unsupported_country() {
		update_option( 'woocommerce_default_country', 'FOO' );

		$recommended_plugin_slugs = $this->get_recommended_plugin_slugs( $this->bundles_mock );

		$this->assertNotContains( 'woocommerce-services:tax', $recommended_plugin_slugs );
	}

	/**
	 * Asserts WC Shipping is not recommended in unsupported countries.
	 *
	 * @return void
	 */
	public function test_wcshipping_is_not_recommended_if_in_an_unsupported_country() {
		update_option( 'woocommerce_default_country', 'FOO' );

		$recommended_plugin_slugs = $this->get_recommended_plugin_slugs( $this->bundles_mock );

		$this->assertNotContains( 'woocommerce-shipping', $recommended_plugin_slugs );
	}

	/**
	 * Asserts WCS&T is still recommended if WooCommerce Shipping is active.
	 *
	 * @return void
	 */
	public function test_wcservices_is_recommended_if_woocommerce_shipping_is_active() {
		// Arrange.
		// Make sure the plugin passes as active.
		$shipping_plugin_file = 'woocommerce-shipping/woocommerce-shipping.php';
		// To pass the validation, we need to the plugin file to exist.
		$shipping_plugin_file_path = WP_PLUGIN_DIR . '/' . $shipping_plugin_file;
		self::touch( $shipping_plugin_file_path );
		update_option( 'active_plugins', array( $shipping_plugin_file ) );

		// Act.
		$recommended_plugin_slugs = $this->get_recommended_plugin_slugs( $this->bundles_mock );

		// Assert.
		$this->assertContains( 'woocommerce-services:tax', $recommended_plugin_slugs );

		// Clean up.
		self::rmdir( dirname( $shipping_plugin_file_path ) );
		self::delete_folders( dirname( $shipping_plugin_file_path ) );
	}

	/**
	 * @testdox Core profiler defaults should exclude Facebook from the growth plugin rotation.
	 */
	public function test_core_profiler_excludes_facebook_from_growth_plugin_rotation(): void {
		$plugin_slugs = array_map(
			function ( $plugin ) {
				return $plugin->key;
			},
			$this->get_core_profiler_plugins()
		);

		$this->assertNotContains(
			'facebook-for-woocommerce',
			$plugin_slugs,
			'Facebook should not be included in the core profiler defaults.'
		);
	}

	/**
	 * @testdox Core profiler defaults should split the growth plugin rotation between TikTok and Pinterest.
	 */
	public function test_core_profiler_splits_growth_plugin_rotation_between_tiktok_and_pinterest(): void {
		$tiktok    = $this->get_core_profiler_plugin_by_slug( 'tiktok-for-business' );
		$pinterest = $this->get_core_profiler_plugin_by_slug( 'pinterest-for-woocommerce' );

		$this->assertSame(
			array( 1, 60 ),
			$tiktok->is_visible[0]->value,
			'TikTok should cover the first half of the shared rotation.'
		);
		$this->assertSame(
			array( 61, 120 ),
			$pinterest->is_visible[0]->value,
			'Pinterest should cover the second half of the shared rotation.'
		);
	}

	/**
	 * @testdox Core profiler WooPayments visibility should follow the store country.
	 * @dataProvider core_profiler_woocommerce_payments_visibility_provider
	 *
	 * @param string $country        Store country and optional state.
	 * @param bool   $should_include Whether WooPayments should be recommended.
	 */
	public function test_core_profiler_woocommerce_payments_visibility_by_country( string $country, bool $should_include ): void {
		update_option( 'woocommerce_default_country', $country );
		update_option( 'woocommerce_store_address', '1 Test Street' );
		update_option( 'woocommerce_remote_variant_assignment', 60 );
		update_option( 'active_plugins', array() );
		update_option( 'woocommerce_onboarding_profile', array() );

		$results = EvaluateExtension::evaluate_bundles(
			DefaultFreeExtensions::get_all(),
			array( 'obw/core-profiler' )
		);

		$this->assertSame( array(), $results['errors'], 'The real core profiler bundle should evaluate without errors.' );
		$this->assertCount( 1, $results['bundles'], 'Only the core profiler bundle should be evaluated.' );
		$plugin_slugs = array_map(
			static function ( $plugin ) {
				return $plugin->key;
			},
			$results['bundles'][0]['plugins']
		);

		if ( $should_include ) {
			$this->assertContains( 'woocommerce-payments', $plugin_slugs );
		} else {
			$this->assertNotContains( 'woocommerce-payments', $plugin_slugs );
		}
	}

	/**
	 * Store countries for core profiler WooPayments visibility.
	 *
	 * @return array<string, array{string, bool}>
	 */
	public function core_profiler_woocommerce_payments_visibility_provider(): array {
		return array(
			'AU:NT' => array( 'AU:NT', true ),
			'AF'    => array( 'AF', false ),
		);
	}

	/**
	 * Evaluates bundles passed as argument and extracts keys of recommended plugins.
	 *
	 * @param array $bundles Array of bundles to evaluate.
	 *
	 * @return array
	 */
	private function get_recommended_plugin_slugs( $bundles ) {
		/*
		 * The json_decode( json_encode() ) call is a trick that
		 * DefaultFreeExtensions::get_all uses to convert the entire
		 * associative array into an object.
		 */
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- We're duplicating what the tested class does.
		$bundles = json_decode( json_encode( $bundles ) );
		$results = EvaluateExtension::evaluate_bundles( $bundles );

		return array_map(
			function ( $plugin ) {
				return $plugin->key;
			},
			$results['bundles'][0]['plugins']
		);
	}

	/**
	 * Gets default core profiler plugin specs.
	 *
	 * @return array
	 */
	private function get_core_profiler_plugins(): array {
		foreach ( DefaultFreeExtensions::get_all() as $bundle ) {
			if ( 'obw/core-profiler' === $bundle->key ) {
				return $bundle->plugins;
			}
		}

		$this->fail( 'Core profiler bundle was not found.' );
	}

	/**
	 * Gets a default core profiler plugin by slug.
	 *
	 * @param string $slug Plugin slug.
	 * @return object
	 */
	private function get_core_profiler_plugin_by_slug( string $slug ): object {
		foreach ( $this->get_core_profiler_plugins() as $plugin ) {
			if ( $slug === $plugin->key ) {
				return $plugin;
			}
		}

		$this->fail( "Plugin {$slug} was not found." );
	}

	/**
	 * Get options whose exact rows must survive each test.
	 *
	 * @return string[]
	 */
	private function get_restored_option_names(): array {
		return array(
			'woocommerce_default_country',
			'woocommerce_store_address',
			'woocommerce_remote_variant_assignment',
			'active_plugins',
			'woocommerce_onboarding_profile',
		);
	}

	/**
	 * Read an option without default filters or value coercion.
	 *
	 * @param string $option_name Option name.
	 * @return array{exists: bool, value: string|null, autoload: string|null}
	 */
	private function get_raw_option_state( string $option_name ): array {
		global $wpdb;

		$wpdb->last_error = '';
		$row              = $wpdb->get_row(
			$wpdb->prepare( "SELECT option_value, autoload FROM {$wpdb->options} WHERE option_name = %s", $option_name ),
			ARRAY_A
		);
		if ( '' !== $wpdb->last_error ) {
			throw new \RuntimeException( esc_html( "Failed to read option {$option_name}: {$wpdb->last_error}" ) );
		}

		return null === $row
			? array(
				'exists'   => false,
				'value'    => null,
				'autoload' => null,
			)
			: array(
				'exists'   => true,
				'value'    => $row['option_value'],
				'autoload' => $row['autoload'],
			);
	}

	/**
	 * Restore every captured option and invalidate its caches.
	 *
	 * @return string[] Restoration failures.
	 */
	private function restore_initial_option_states(): array {
		$failures = array();
		foreach ( $this->initial_option_states as $option_name => $state ) {
			try {
				if ( ! $this->restore_raw_option_state( $option_name, $state ) ) {
					$failures[] = "Database write failed for {$option_name}.";
				}
			} catch ( \Throwable $error ) {
				$failures[] = $error->getMessage();
			}
		}

		return $failures;
	}

	/**
	 * Verify raw rows and option caches after restoration.
	 *
	 * @param string[] $failures Existing restoration failures.
	 */
	private function assert_initial_option_states_restored( array $failures ): void {
		foreach ( $this->initial_option_states as $option_name => $state ) {
			if ( $state !== $this->get_raw_option_state( $option_name ) ) {
				$failures[] = "Restored row does not match the captured state for {$option_name}.";
			}
			$expected = $state['exists'] ? maybe_unserialize( $state['value'] ) : false;
			if ( maybe_serialize( get_option( $option_name ) ) !== maybe_serialize( $expected ) ) {
				$failures[] = "Option cache does not match the captured state for {$option_name}.";
			}
		}

		$this->assertSame( array(), $failures, implode( ' ', $failures ) );
	}

	/**
	 * Restore an option without invoking setting sanitizers.
	 *
	 * @param string                                                         $option_name Option name.
	 * @param array{exists: bool, value: string|null, autoload: string|null} $state Raw option state.
	 * @return bool Whether the database operation succeeded.
	 */
	private function restore_raw_option_state( string $option_name, array $state ): bool {
		global $wpdb;

		try {
			if ( ! $state['exists'] ) {
				$result = $wpdb->delete( $wpdb->options, array( 'option_name' => $option_name ) );
			} elseif ( $this->get_raw_option_state( $option_name )['exists'] ) {
				$result = $wpdb->update(
					$wpdb->options,
					array(
						'option_value' => $state['value'],
						'autoload'     => $state['autoload'],
					),
					array( 'option_name' => $option_name )
				);
			} else {
				$result = $wpdb->insert(
					$wpdb->options,
					array(
						'option_name'  => $option_name,
						'option_value' => $state['value'],
						'autoload'     => $state['autoload'],
					)
				);
			}

			return false !== $result;
		} finally {
			wp_cache_delete( $option_name, 'options' );
			wp_cache_delete( 'alloptions', 'options' );
			wp_cache_delete( 'notoptions', 'options' );
		}
	}
}
