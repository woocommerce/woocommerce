<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\LegacyAssets;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\LegacyAssets\LegacySelect2UsageTracker;
use WC_Unit_Test_Case;

/**
 * Tests for the LegacySelect2UsageTracker class.
 */
class LegacySelect2UsageTrackerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var LegacySelect2UsageTracker
	 */
	private LegacySelect2UsageTracker $sut;

	/**
	 * Script handles registered by the tests.
	 *
	 * @var array<int, string>
	 */
	private array $test_script_handles = array(
		'my-extension-admin',
		'my-extension-footer',
		'intermediate-select2-wrapper',
		'my-extension-transitive',
	);

	/**
	 * Original request URI.
	 *
	 * @var string|null
	 */
	private ?string $original_request_uri = null;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Test fixture preserves the raw request URI for restoration.
		$this->original_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : null;
		$_SERVER['REQUEST_URI']     = '/';
		$this->reset_scripts();
		$this->register_legacy_select2_scripts();
		$this->sut = new LegacySelect2UsageTracker();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->reset_scripts();
		set_current_screen( 'front' );

		if ( null === $this->original_request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->original_request_uri;
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should track a plugin-owned header dependency on select2.
	 */
	public function test_tracks_plugin_owned_header_dependency_on_select2(): void {
		set_current_screen( 'woocommerce_page_wc-settings' );
		$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=wc-settings';
		wp_register_script(
			'my-extension-admin',
			$this->get_my_extension_asset_url( 'admin.js' ),
			array( 'select2' ),
			'1.0.0',
			false
		);
		wp_enqueue_script( 'my-extension-admin' );

		$this->print_script_group( 0 );

		$this->assertSame(
			array(
				'context'            => 'admin',
				'page_type'          => 'woocommerce_page_wc-settings',
				'handles'            => 'select2',
				'dependents'         => 'my-extension-admin',
				'dependents_sources' => 'my-extension/assets/admin.js',
				'handles_sources'    => $this->get_expected_wc_select2_source(),
			),
			$this->sut->get_usage_event( 'admin' ),
			'Admin usage should include only the expected event properties.'
		);
	}

	/**
	 * @testdox Should track a plugin-owned footer dependency on wc-select2.
	 */
	public function test_tracks_plugin_owned_footer_dependency_on_wc_select2(): void {
		wp_register_script(
			'my-extension-footer',
			$this->get_my_extension_asset_url( 'footer.js' ),
			array( 'wc-select2' ),
			'1.0.0',
			true
		);
		wp_enqueue_script( 'my-extension-footer' );

		$this->print_script_group( 1 );

		$this->assertSame(
			array(
				'context'            => 'frontend',
				'page_type'          => $this->get_expected_frontend_page_type(),
				'handles'            => 'wc-select2',
				'dependents'         => 'my-extension-footer',
				'dependents_sources' => 'my-extension/assets/footer.js',
				'handles_sources'    => $this->get_expected_wc_select2_source(),
			),
			$this->sut->get_usage_event( 'frontend' ),
			'Frontend usage should report the direct wc-select2 handle.'
		);
	}

	/**
	 * @testdox Should not track transitive dependencies reaching select2.
	 */
	public function test_does_not_track_transitive_dependency_reaching_select2(): void {
		wp_register_script(
			'intermediate-select2-wrapper',
			$this->get_my_extension_asset_url( 'wrapper.js' ),
			array( 'select2' ),
			'1.0.0',
			true
		);
		wp_register_script(
			'my-extension-transitive',
			$this->get_my_extension_asset_url( 'transitive.js' ),
			array( 'intermediate-select2-wrapper' ),
			'1.0.0',
			true
		);
		wp_enqueue_script( 'my-extension-transitive' );

		$this->print_script_group( 1 );

		$this->assertSame( array(), $this->sut->get_usage_event( 'frontend' ), 'Transitive dependencies are intentionally ignored to keep tracking bounded.' );
	}

	/**
	 * @testdox Should track direct legacy handle enqueue without an attributable plugin.
	 */
	public function test_tracks_direct_legacy_handle_enqueue_without_attributable_plugin(): void {
		wp_enqueue_script( 'select2' );

		$this->print_script_group( 1 );

		$this->assertSame(
			array(
				'context'            => 'frontend',
				'page_type'          => $this->get_expected_frontend_page_type(),
				'handles'            => 'select2',
				'dependents'         => 'select2',
				'dependents_sources' => '',
				'handles_sources'    => $this->get_expected_wc_select2_source(),
			),
			$this->sut->get_usage_event( 'frontend' ),
			'Direct Select2 enqueue should report the requested handle.'
		);
	}

	/**
	 * @testdox Should not track selectWoo usage alone.
	 */
	public function test_does_not_track_selectwoo_alone(): void {
		wp_register_script(
			'selectWoo',
			plugins_url( 'woocommerce/assets/js/selectWoo/selectWoo.full.js' ),
			array( 'jquery' ),
			'1.0.0',
			true
		);
		wp_enqueue_script( 'selectWoo' );

		$this->print_script_group( 1 );

		$this->assertSame( array(), $this->sut->get_usage_event( 'frontend' ), 'selectWoo alone should not trigger legacy Select2 usage tracking.' );
	}

	/**
	 * @testdox Should not track queued dependencies before they are printed.
	 */
	public function test_does_not_track_queued_dependencies_before_they_are_printed(): void {
		set_current_screen( 'woocommerce_page_wc-settings' );
		wp_register_script(
			'my-extension-admin',
			$this->get_my_extension_asset_url( 'admin.js' ),
			array( 'select2' ),
			'1.0.0',
			false
		);
		wp_enqueue_script( 'my-extension-admin' );

		$this->assertSame( array(), $this->sut->get_usage_event( 'admin' ), 'Queued scripts should not be reported before WordPress prints them.' );
	}

	/**
	 * @testdox Should report footer dependencies after footer scripts are printed.
	 */
	public function test_reports_footer_dependencies_after_footer_scripts_are_printed(): void {
		wp_register_script(
			'my-extension-footer',
			$this->get_my_extension_asset_url( 'footer.js' ),
			array( 'wc-select2' ),
			'1.0.0',
			true
		);
		wp_enqueue_script( 'my-extension-footer' );

		$this->print_script_group( 0 );

		$this->assertSame( array(), $this->sut->get_usage_event( 'frontend' ), 'Footer usage should not be reported before footer scripts are printed.' );

		$this->print_script_group( 1 );

		$this->assertSame(
			array(
				'context'            => 'frontend',
				'page_type'          => $this->get_expected_frontend_page_type(),
				'handles'            => 'wc-select2',
				'dependents'         => 'my-extension-footer',
				'dependents_sources' => 'my-extension/assets/footer.js',
				'handles_sources'    => $this->get_expected_wc_select2_source(),
			),
			$this->sut->get_usage_event( 'frontend' ),
			'Footer dependencies should be reported after footer scripts are printed.'
		);
	}

	/**
	 * @testdox Should not track registered plugin dependencies that are not enqueued.
	 */
	public function test_does_not_track_registered_plugin_dependencies_that_are_not_enqueued(): void {
		wp_register_script(
			'my-extension-footer',
			$this->get_my_extension_asset_url( 'footer.js' ),
			array( 'wc-select2' ),
			'1.0.0',
			true
		);

		$this->assertSame( array(), $this->sut->get_usage_event( 'frontend' ), 'Registered scripts should not be reported until they are enqueued.' );
	}

	/**
	 * @testdox Should register frontend analytics independently of the core Tracks opt-in.
	 */
	public function test_registers_frontend_analytics_independently_of_core_tracks(): void {
		add_filter( 'woocommerce_apply_user_tracking', '__return_false' );

		$this->sut->register();

		$this->assertSame(
			PHP_INT_MAX,
			has_action( 'wp_print_footer_scripts', array( $this->sut, 'handle_wp_print_footer_scripts' ) ),
			'Frontend analytics should be registered when core Tracks is disabled.'
		);
		$this->assertFalse(
			has_action( 'admin_print_footer_scripts', array( $this->sut, 'handle_admin_print_footer_scripts' ) ),
			'Admin tracking should remain disabled when core Tracks is disabled.'
		);

		remove_action( 'wp_print_footer_scripts', array( $this->sut, 'handle_wp_print_footer_scripts' ), PHP_INT_MAX );
		remove_filter( 'woocommerce_apply_user_tracking', '__return_false' );
	}

	/**
	 * @testdox Should record each detected usage event only once per week.
	 */
	public function test_records_each_detected_usage_event_only_once_per_week(): void {
		$original_request_uri   = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : null;
		$_SERVER['REQUEST_URI'] = '/shop/?filter=featured';
		$event                  = array(
			'context'            => 'frontend',
			'page_type'          => $this->get_expected_frontend_page_type(),
			'handles'            => 'wc-select2',
			'dependents'         => 'my-extension-footer',
			'dependents_sources' => 'my-extension/assets/footer.js',
			'handles_sources'    => $this->get_expected_wc_select2_source(),
		);

		$this->delete_usage_event_transient( $event );

		$sut = new class() extends LegacySelect2UsageTracker {
			/**
			 * The number of times the usage event was built.
			 *
			 * @var int
			 */
			public int $usage_event_calls = 0;

			/**
			 * Recorded frontend analytics events.
			 *
			 * @var array<int, array{name: string, properties: array<string, string>}>
			 */
			public array $recorded_frontend_events = array();

			/**
			 * Get a legacy Select2 usage event for the current script registry.
			 *
			 * @internal
			 *
			 * @param string $context The request context.
			 * @return array<string, string>
			 */
			public function get_usage_event( string $context ): array {
				++$this->usage_event_calls;

				return parent::get_usage_event( $context );
			}

			/**
			 * Add a storefront event to the WooCommerce Analytics client queue.
			 *
			 * @param string                $event_name Event name.
			 * @param array<string, string> $properties Event properties.
			 * @return void
			 */
			protected function record_frontend_event( string $event_name, array $properties ): void {
				$this->recorded_frontend_events[] = array(
					'name'       => $event_name,
					'properties' => $properties,
				);
			}

			/**
			 * Make frontend analytics available for this test double.
			 *
			 * @return bool
			 */
			protected function is_frontend_tracking_available(): bool {
				return true;
			}
		};

		wp_register_script(
			'my-extension-footer',
			$this->get_my_extension_asset_url( 'footer.js' ),
			array( 'wc-select2' ),
			'1.0.0',
			true
		);
		wp_enqueue_script( 'my-extension-footer' );

		$this->print_script_group( 1 );
		$sut->handle_wp_print_footer_scripts();

		$_SERVER['REQUEST_URI'] = '/product/hoodie/';

		$sut->handle_wp_print_footer_scripts();

		$this->assertSame(
			array(
				array(
					'name'       => LegacySelect2UsageTracker::EVENT_NAME,
					'properties' => $event,
				),
			),
			$sut->recorded_frontend_events,
			'Repeated detections of the same legacy Select2 usage event should be rate limited.'
		);
		$this->assertSame( 2, $sut->usage_event_calls, 'Each detection should scan the script registry before rate limiting the exact usage event.' );

		$this->delete_usage_event_transient( $event );

		if ( null === $original_request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $original_request_uri;
		}
	}

	/**
	 * Register WooCommerce's legacy Select2 handles.
	 */
	private function register_legacy_select2_scripts(): void {
		if ( ! class_exists( 'WC_Admin_Assets' ) && defined( 'WC_ABSPATH' ) ) {
			include_once WC_ABSPATH . 'includes/admin/class-wc-admin-assets.php';
		}

		$admin_assets_reflection = new \ReflectionClass( \WC_Admin_Assets::class );
		$admin_assets            = $admin_assets_reflection->newInstanceWithoutConstructor();
		$admin_assets->register_scripts();
	}

	/**
	 * Reset the global scripts registry runtime state.
	 */
	private function reset_scripts(): void {
		$wp_scripts = wp_scripts();

		$wp_scripts->queue     = array();
		$wp_scripts->to_do     = array();
		$wp_scripts->done      = array();
		$wp_scripts->groups    = array();
		$wp_scripts->in_footer = array();
		$wp_scripts->args      = array();

		foreach ( $this->test_script_handles as $handle ) {
			wp_deregister_script( $handle );
		}
	}

	/**
	 * Print queued scripts for a script group while discarding script output.
	 *
	 * @param int $group Script group. 0 for header, 1 for footer.
	 */
	private function print_script_group( int $group ): void {
		ob_start();

		if ( 0 === $group ) {
			wp_scripts()->do_head_items();
		} else {
			wp_scripts()->do_footer_items();
		}

		ob_end_clean();
	}

	/**
	 * Get a plugin fixture asset URL.
	 *
	 * @param string $asset Asset filename.
	 * @return string
	 */
	private function get_my_extension_asset_url( string $asset ): string {
		return plugins_url( 'my-extension/assets/' . $asset );
	}

	/**
	 * Get the frontend page type expected for the current PHPUnit process.
	 *
	 * Some tests render cart flows that define WOOCOMMERCE_CART. Since PHP
	 * constants cannot be undefined, this test must accept the inherited cart
	 * page type when it runs later in the same process.
	 *
	 * @return string
	 */
	private function get_expected_frontend_page_type(): string {
		return Constants::is_defined( 'WOOCOMMERCE_CART' ) ? 'cart' : 'other';
	}

	/**
	 * Get the expected WooCommerce Select2 source path.
	 *
	 * @return string
	 */
	private function get_expected_wc_select2_source(): string {
		$suffix = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';

		return 'woocommerce/assets/js/select2/select2.full' . $suffix . '.js';
	}

	/**
	 * Delete the transient used to rate limit a usage event.
	 *
	 * @param array<string, string> $event Usage event.
	 */
	private function delete_usage_event_transient( array $event ): void {
		ksort( $event );

		$event_json = wp_json_encode( $event );

		delete_transient(
			'wc_legacy_select2_check_' . md5( is_string( $event_json ) ? $event_json : '' )
		);
	}
}
