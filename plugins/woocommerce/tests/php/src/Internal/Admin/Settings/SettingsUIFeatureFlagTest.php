<?php
/**
 * Settings UI feature flag tests.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\Admin\Settings;
use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIRequestContext;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use WC_Unit_Test_Case;

/**
 * Tests for the settings UI feature flag boundary.
 */
class SettingsUIFeatureFlagTest extends WC_Unit_Test_Case {

	/**
	 * Original request globals.
	 *
	 * @var array
	 */
	private array $original_get = array();

	/**
	 * Original current settings section.
	 *
	 * @var mixed
	 */
	private $original_current_section = null;

	/**
	 * Original current settings tab.
	 *
	 * @var mixed
	 */
	private $original_current_tab = null;

	/**
	 * Whether the hide save button global existed before the test.
	 *
	 * @var bool
	 */
	private bool $original_hide_save_button_exists = false;

	/**
	 * Original hide save button global value.
	 *
	 * @var mixed
	 */
	private $original_hide_save_button = null;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		include_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
		include_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';

		global $current_section, $current_tab;

		$this->original_get                     = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->original_current_section         = $current_section ?? null;
		$this->original_current_tab             = $current_tab ?? null;
		$this->original_hide_save_button_exists = array_key_exists( 'hide_save_button', $GLOBALS );
		$this->original_hide_save_button        = $this->original_hide_save_button_exists ? $GLOBALS['hide_save_button'] : null;
		unset( $GLOBALS['hide_save_button'] );
		SettingsUIRequestContext::reset();
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		global $current_section, $current_tab;

		$_GET            = $this->original_get;
		$current_section = $this->original_current_section;
		$current_tab     = $this->original_current_tab;

		if ( $this->original_hide_save_button_exists ) {
			$GLOBALS['hide_save_button'] = $this->original_hide_save_button;
		} else {
			unset( $GLOBALS['hide_save_button'] );
		}

		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		remove_filter( 'woocommerce_admin_features', array( $this, 'disable_settings_ui_feature' ) );
		wp_dequeue_script( 'settings-ui-counting-handle' );
		wp_deregister_script( 'settings-ui-counting-handle' );
		wp_dequeue_script( 'settings-ui-registered-handle' );
		wp_deregister_script( 'settings-ui-registered-handle' );
		delete_option( 'woocommerce_settings_ui_flag_test' );
		SettingsUIRequestContext::reset();

		parent::tearDown();
	}

	/**
	 * It keeps opted-in pages on the legacy renderer when the feature flag is disabled.
	 */
	public function test_opted_in_page_uses_legacy_output_when_feature_flag_is_disabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_settings_ui_feature' ) );

		global $current_section;
		$current_section = '';
		$page            = $this->get_settings_ui_test_page();

		ob_start();
		$page->output();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'name="woocommerce_settings_ui_flag_test"', $output );
		$this->assertStringNotContainsString( 'data-wc-settings-ui="1"', $output );
		$this->assertArrayNotHasKey( 'hide_save_button', $GLOBALS );
	}

	/**
	 * It renders the settings UI mount point only when the feature flag is enabled.
	 */
	public function test_opted_in_page_uses_settings_ui_output_when_feature_flag_is_enabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );

		global $current_section;
		$current_section = '';
		$page            = $this->get_settings_ui_test_page();

		ob_start();
		$page->output();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-wc-settings-ui="1"', $output );
		$this->assertStringContainsString( 'data-wc-settings-page="settings_ui_flag_test"', $output );
		$this->assertStringNotContainsString( 'name="woocommerce_settings_ui_flag_test"', $output );
		$this->assertTrue( $GLOBALS['hide_save_button'] );
	}

	/**
	 * It emits developer feedback when settings UI rendering falls back to legacy output.
	 */
	public function test_settings_ui_fallback_emits_doing_it_wrong_notice(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		$this->setExpectedIncorrectUsage( 'WC_Settings_Page::output' );

		$notices = array();
		$action  = function ( $function_name, $message, $version ) use ( &$notices ) {
			$notices[] = array(
				'function_name' => $function_name,
				'message'       => $message,
				'version'       => $version,
			);
		};
		add_action( 'doing_it_wrong_run', $action, 10, 3 );

		global $current_section;
		$current_section = 'advanced';
		$page            = $this->get_settings_ui_test_page_with_failing_script_handles();

		try {
			ob_start();
			$page->output();
			$output = ob_get_clean();
		} finally {
			remove_action( 'doing_it_wrong_run', $action, 10 );
			remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		}

		$settings_page_notices = $this->get_settings_page_output_notices( $notices );

		$this->assertStringContainsString( 'name="woocommerce_settings_ui_flag_test"', $output );
		$this->assertStringNotContainsString( 'data-wc-settings-ui="1"', $output );
		$this->assertNotEmpty( $settings_page_notices );
		$this->assertSame( '10.9.0', $settings_page_notices[0]['version'] );
		$this->assertStringContainsString( 'settings_ui_flag_test', $settings_page_notices[0]['message'] );
		$this->assertStringContainsString( 'advanced', $settings_page_notices[0]['message'] );
		$this->assertStringContainsString( 'Unable to load extension script handles.', $settings_page_notices[0]['message'] );
	}

	/**
	 * It emits developer feedback when settings UI schema generation has failed.
	 */
	public function test_settings_ui_schema_failure_fallback_emits_doing_it_wrong_notice(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		$this->setExpectedIncorrectUsage( 'WC_Settings_Page::output' );

		$notices = array();
		$action  = function ( $function_name, $message, $version ) use ( &$notices ) {
			$notices[] = array(
				'function_name' => $function_name,
				'message'       => $message,
				'version'       => $version,
			);
		};
		add_action( 'doing_it_wrong_run', $action, 10, 3 );

		global $current_section;
		$current_section = 'advanced';
		$page            = $this->get_settings_ui_test_page_with_failing_schema();

		try {
			ob_start();
			$page->output();
			$output = ob_get_clean();
		} finally {
			remove_action( 'doing_it_wrong_run', $action, 10 );
			remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		}

		$settings_page_notices = $this->get_settings_page_output_notices( $notices );

		$this->assertStringContainsString( 'name="woocommerce_settings_ui_flag_test"', $output );
		$this->assertStringNotContainsString( 'data-wc-settings-ui="1"', $output );
		$this->assertNotEmpty( $settings_page_notices );
		$this->assertSame( '10.9.0', $settings_page_notices[0]['version'] );
		$this->assertStringContainsString( 'settings_ui_flag_test', $settings_page_notices[0]['message'] );
		$this->assertStringContainsString( 'advanced', $settings_page_notices[0]['message'] );
		$this->assertStringContainsString( 'Unable to build settings UI schema.', $settings_page_notices[0]['message'] );
	}

	/**
	 * @testdox Should resolve Settings UI script handles once per context.
	 */
	public function test_settings_ui_script_handles_are_resolved_once_per_context(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		wp_register_script( 'settings-ui-counting-handle', false, array(), '1.0.0', true );

		global $current_section;
		$current_section = '';
		$page            = $this->get_settings_ui_test_page_with_counting_script_handles();
		$context         = SettingsUIRequestContext::for_settings_page( $page, '' );

		$this->assertSame( array( 'settings-ui-counting-handle' ), $context->get_script_handles() );

		ob_start();
		$page->output();
		ob_get_clean();

		$this->assertSame( 1, $this->get_script_handle_resolution_count( $page ), 'Script handles should be resolved once for a page and section context.' );
	}

	/**
	 * @testdox Should render the complete classic page once with a precise diagnostic when schema validation fails.
	 */
	public function test_invalid_schema_uses_complete_classic_fallback_once_without_changing_the_option(): void {
		global $current_section, $current_tab, $wpdb;

		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		$this->setExpectedIncorrectUsage( 'WC_Settings_Page::output' );

		update_option( 'woocommerce_settings_ui_flag_test', '02' );
		$stored_before = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				'woocommerce_settings_ui_flag_test'
			)
		);

		$notices = array();
		$action  = static function ( $function_name, $message, $version ) use ( &$notices ): void {
			$notices[] = array(
				'function_name' => $function_name,
				'message'       => $message,
				'version'       => $version,
			);
		};
		add_action( 'doing_it_wrong_run', $action, 10, 3 );

		$current_section = '';
		$current_tab     = 'settings_ui_flag_test';
		$page            = $this->get_settings_ui_test_page_with_invalid_schema();
		$context         = SettingsUIRequestContext::for_settings_page( $page, '' );

		try {
			$classes = $page->add_settings_ui_body_class( 'existing-class' );
			$output  = $this->render_settings_view( $page );
		} finally {
			remove_action( 'doing_it_wrong_run', $action, 10 );
			remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		}

		$stored_after          = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				'woocommerce_settings_ui_flag_test'
			)
		);
		$settings_page_notices = $this->get_settings_page_output_notices( $notices );

		$this->assertSame( 'existing-class', $classes, 'Classic body classes should remain unchanged.' );
		$this->assertStringContainsString( 'name="woocommerce_settings_ui_flag_test"', $output );
		$this->assertStringContainsString( 'class="woocommerce-save-button', $output );
		$this->assertStringNotContainsString( 'data-wc-settings-ui="1"', $output );
		$this->assertTrue( empty( $GLOBALS['hide_save_button'] ), 'The classic Save button should remain visible.' );
		$this->assertNull( $context->get_schema(), 'An invalid schema must not be available for the shared settings asset.' );
		$this->assertTrue( $context->has_schema_failed() );
		$this->assertSame( 1, $this->get_schema_resolution_count( $page ), 'Schema validation should run once per request context.' );
		$this->assertCount( 1, $settings_page_notices, 'The fallback diagnostic should be emitted once.' );
		$this->assertStringContainsString( 'woocommerce_settings_ui_flag_test', $settings_page_notices[0]['message'] );
		$this->assertStringContainsString( 'unsupported type "future-control"', $settings_page_notices[0]['message'] );
		$this->assertSame( $stored_before, $stored_after, 'Classic fallback must preserve the raw stored option representation.' );
	}

	/**
	 * @testdox Should render classic settings without saving when value canonicalization fails.
	 */
	public function test_invalid_value_canonicalization_uses_classic_fallback_without_saving(): void {
		global $current_section, $current_tab, $wpdb;

		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		$this->setExpectedIncorrectUsage( 'WC_Settings_Page::output' );

		update_option( 'woocommerce_settings_ui_flag_test', '9007199254740992' );
		$stored_before = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				'woocommerce_settings_ui_flag_test'
			)
		);

		$current_section = '';
		$current_tab     = 'settings_ui_flag_test';
		$page            = $this->get_settings_ui_test_page_with_invalid_canonical_value();
		$context         = SettingsUIRequestContext::for_settings_page( $page, '' );
		$save_calls      = 0;
		$save_listener   = static function ( $value ) use ( &$save_calls ) {
			++$save_calls;
			return $value;
		};
		add_filter( 'woocommerce_admin_settings_sanitize_option', $save_listener );

		try {
			$output = $this->render_settings_view( $page );
		} finally {
			remove_filter( 'woocommerce_admin_settings_sanitize_option', $save_listener );
			remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		}

		$stored_after = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				'woocommerce_settings_ui_flag_test'
			)
		);

		$this->assertNull( $context->get_schema() );
		$this->assertTrue( $context->has_schema_failed() );
		$this->assertStringContainsString( 'outside the JavaScript safe integer range', $context->get_schema_failure_reason() );
		$this->assertStringContainsString( 'name="woocommerce_settings_ui_flag_test"', $output );
		$this->assertStringContainsString( 'class="woocommerce-save-button', $output );
		$this->assertStringNotContainsString( 'data-wc-settings-ui="1"', $output );
		$this->assertSame( 0, $save_calls, 'Rendering classic fallback must not invoke the settings save pipeline.' );
		$this->assertSame( $stored_before, $stored_after );
	}

	/**
	 * @testdox Should fall back to classic settings when a declared script handle is not registered.
	 */
	public function test_unregistered_script_handle_uses_classic_fallback_with_precise_reason(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		$this->setExpectedIncorrectUsage( 'WC_Settings_Page::output' );

		$notices = array();
		$action  = static function ( $function_name, $message, $version ) use ( &$notices ): void {
			$notices[] = array(
				'function_name' => $function_name,
				'message'       => $message,
				'version'       => $version,
			);
		};
		add_action( 'doing_it_wrong_run', $action, 10, 3 );

		global $current_section;
		$current_section = '';
		$page            = $this->get_settings_ui_test_page_with_script_handles( array( 'settings-ui-missing-handle' ) );

		try {
			ob_start();
			$page->output();
			$output = ob_get_clean();
		} finally {
			remove_action( 'doing_it_wrong_run', $action, 10 );
			remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		}

		$settings_page_notices = $this->get_settings_page_output_notices( $notices );

		$this->assertStringContainsString( 'name="woocommerce_settings_ui_flag_test"', $output );
		$this->assertStringNotContainsString( 'data-wc-settings-ui="1"', $output );
		$this->assertTrue( empty( $GLOBALS['hide_save_button'] ), 'The classic Save button should remain visible.' );
		$this->assertCount( 1, $settings_page_notices );
		$this->assertStringContainsString( 'settings-ui-missing-handle', $settings_page_notices[0]['message'] );
		$this->assertStringContainsString( 'not registered', $settings_page_notices[0]['message'] );
	}

	/**
	 * @testdox Should reject a declared extension script handle that is not a string.
	 */
	public function test_non_string_script_handle_is_rejected_before_schema_emission(): void {
		$page    = $this->get_settings_ui_test_page_with_script_handles( array( 42 ) );
		$context = SettingsUIRequestContext::for_settings_page( $page, '' );

		$this->assertTrue( $context->has_script_handles_failed() );
		$this->assertNull( $context->get_schema() );
		$this->assertStringContainsString( 'must be non-empty strings', $context->get_script_handles_failure_reason() );
	}

	/**
	 * @testdox Should enqueue each registered extension script handle before rendering the Settings UI mount.
	 */
	public function test_registered_script_handle_is_enqueued_before_settings_ui_mount(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		wp_register_script( 'settings-ui-registered-handle', false, array(), '1.0.0', true );

		global $current_section;
		$current_section = '';
		$page            = $this->get_settings_ui_test_page_with_script_handles( array( 'settings-ui-registered-handle' ) );

		ob_start();
		$page->output();
		$output = ob_get_clean();

		$this->assertTrue( wp_script_is( 'settings-ui-registered-handle', 'enqueued' ) );
		$this->assertStringContainsString( 'data-wc-settings-ui="1"', $output );
		$this->assertTrue( $GLOBALS['hide_save_button'] );
	}

	/**
	 * @testdox Should use classic settings only for a request carrying the namespaced override.
	 */
	public function test_classic_request_override_preserves_routing_without_changing_the_feature_flag(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );

		global $current_section, $current_tab;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only rendering override under test.
		$current_section        = 'test_gateway';
		$current_tab            = 'checkout';
		$_GET['page']           = 'wc-settings';
		$_GET['tab']            = 'checkout';
		$_GET['section']        = 'test_gateway';
		$_GET['wc_settings_ui'] = 'classic';
		$expected_query         = $_GET;
		$page                   = $this->get_settings_ui_test_page_for_drill_down();

		$classes = $page->add_settings_ui_body_class( 'existing-class' );
		ob_start();
		$page->output();
		$classic_output = ob_get_clean();

		$this->assertSame( $expected_query, $_GET, 'The override must not alter page or section routing.' );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$this->assertSame( 'existing-class', $classes );
		$this->assertStringContainsString( 'name="woocommerce_settings_ui_drill_down_test"', $classic_output );
		$this->assertStringNotContainsString( 'data-wc-settings-ui="1"', $classic_output );
		$this->assertTrue( empty( $GLOBALS['hide_save_button'] ), 'The classic Save button should remain visible.' );
		$this->assertTrue( Features::is_enabled( 'settings-ui' ), 'The request override must not persistently disable the feature.' );

		unset( $_GET['wc_settings_ui'] );
		SettingsUIRequestContext::reset();
		unset( $GLOBALS['hide_save_button'] );

		ob_start();
		$page->output();
		$settings_ui_output = ob_get_clean();

		$this->assertStringContainsString( 'data-wc-settings-ui="1"', $settings_ui_output );
		$this->assertTrue( Features::is_enabled( 'settings-ui' ) );
	}

	/**
	 * @testdox Should clear shell section navigation for top-level pages, which keep the classic section links.
	 */
	public function test_request_context_clears_section_navigation_for_top_level_pages(): void {
		$page    = $this->get_settings_ui_page_with_sections();
		$context = SettingsUIRequestContext::for_settings_page( $page, '' );

		$schema = $context->get_schema();

		$this->assertSame( 'Settings UI flag test', $schema['shell']['title'] );
		$this->assertArrayNotHasKey( 'breadcrumbs', $schema['shell'] );
		$this->assertArrayNotHasKey( 'navigation', $schema['shell'] );
		$this->assertSame( array(), $schema['shell']['sectionNavigation'] );
	}

	/**
	 * @testdox Should hide the shell header for pages registered at the top level of settings.
	 */
	public function test_request_context_hides_shell_header_for_top_level_pages(): void {
		$page    = $this->get_settings_ui_test_page();
		$context = SettingsUIRequestContext::for_settings_page( $page, '' );

		$schema = $context->get_schema();

		$this->assertSame( 'hidden', $schema['shell']['header'] );
	}

	/**
	 * @testdox Should override a schema-provided shell header for top-level pages.
	 */
	public function test_request_context_overrides_a_schema_provided_shell_header(): void {
		$page    = $this->get_settings_ui_test_page_with_visible_shell_header();
		$context = SettingsUIRequestContext::for_settings_page( $page, '' );

		$schema = $context->get_schema();

		$this->assertSame( 'hidden', $schema['shell']['header'], 'Top-level pages cannot opt into the shell header.' );
	}

	/**
	 * @testdox Should show the shell header for payments drill-down pages.
	 */
	public function test_request_context_shows_shell_header_for_drill_down_pages(): void {
		$page    = $this->get_settings_ui_test_page_for_drill_down();
		$context = SettingsUIRequestContext::for_settings_page( $page, 'test_gateway' );

		$schema = $context->get_schema();

		$this->assertTrue( $context->is_drill_down() );
		$this->assertSame( 'visible', $schema['shell']['header'] );
		$this->assertSame( array(), $schema['shell']['sectionNavigation'], 'Drill-down pages default to no section navigation.' );
	}

	/**
	 * @testdox Should default drill-down breadcrumbs to the parent settings tab.
	 */
	public function test_request_context_defaults_drill_down_breadcrumbs_to_the_parent_tab(): void {
		$page    = $this->get_settings_ui_test_page_for_drill_down();
		$context = SettingsUIRequestContext::for_settings_page( $page, 'test_gateway' );

		$schema = $context->get_schema();

		$this->assertCount( 1, $schema['shell']['breadcrumbs'] );
		$this->assertSame( 'Payments drill-down test', $schema['shell']['breadcrumbs'][0]['label'] );
		$this->assertStringContainsString( 'tab=checkout', $schema['shell']['breadcrumbs'][0]['href'] );
	}

	/**
	 * @testdox Should keep schema-provided breadcrumbs on drill-down pages.
	 */
	public function test_request_context_keeps_schema_breadcrumbs_on_drill_down_pages(): void {
		$breadcrumbs = array( array( 'label' => 'Custom crumb' ) );
		$page        = $this->get_settings_ui_test_page_for_drill_down( $breadcrumbs );
		$context     = SettingsUIRequestContext::for_settings_page( $page, 'test_gateway' );

		$schema = $context->get_schema();

		$this->assertSame( $breadcrumbs, $schema['shell']['breadcrumbs'] );
	}

	/**
	 * @testdox Should treat the default payments section as a top-level page.
	 */
	public function test_request_context_hides_shell_header_for_default_payments_section(): void {
		$page    = $this->get_settings_ui_test_page_for_drill_down();
		$context = SettingsUIRequestContext::for_settings_page( $page, '' );

		$schema = $context->get_schema();

		$this->assertFalse( $context->is_drill_down() );
		$this->assertSame( 'hidden', $schema['shell']['header'] );
	}

	/**
	 * It does not inject settings UI shared data when the feature flag is disabled.
	 */
	public function test_shared_settings_are_not_injected_when_feature_flag_is_disabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_settings_ui_feature' ) );

		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'products';

		$settings = $this->invoke_private_method( new Settings(), 'add_settings_ui_schema', array( array() ) );

		$this->assertArrayNotHasKey( 'settingsUI', $settings );
	}

	/**
	 * It does not add settings UI script dependencies when the feature flag is disabled.
	 */
	public function test_settings_ui_script_dependencies_are_empty_when_feature_flag_is_disabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_settings_ui_feature' ) );

		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'products';

		$dependencies = $this->invoke_private_method( new WCAdminAssets(), 'get_settings_ui_script_dependencies' );

		$this->assertSame( array(), $dependencies );
	}

	/**
	 * It does not resolve a current request context when the feature flag is disabled.
	 */
	public function test_current_request_context_is_null_when_feature_flag_is_disabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_settings_ui_feature' ) );

		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'products';

		$this->assertNull( SettingsUIRequestContext::get_current() );
	}

	/**
	 * It does not resolve a current request context without the manage_woocommerce capability.
	 */
	public function test_current_request_context_is_null_without_manage_woocommerce_capability(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );

		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'products';

		$original_user_id = get_current_user_id();
		wp_set_current_user( 0 );

		try {
			$this->assertNull( SettingsUIRequestContext::get_current() );
		} finally {
			wp_set_current_user( $original_user_id );
		}
	}

	/**
	 * It does not add the settings UI body class when the feature flag is disabled.
	 */
	public function test_settings_ui_body_class_is_not_added_when_feature_flag_is_disabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_settings_ui_feature' ) );

		global $current_tab;
		$current_tab = 'settings_ui_flag_test';
		$page        = $this->get_settings_ui_test_page();

		$classes = $page->add_settings_ui_body_class( 'existing-class' );

		$this->assertSame( 'existing-class', $classes );
	}

	/**
	 * @testdox Should add only the top-level Settings UI body class for top-level pages.
	 */
	public function test_settings_ui_body_class_is_added_when_feature_flag_is_enabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );

		global $current_tab;
		$current_tab = 'settings_ui_flag_test';
		$page        = $this->get_settings_ui_test_page();

		$classes = $page->add_settings_ui_body_class( 'existing-class' );

		$this->assertStringContainsString( 'existing-class', $classes );
		$this->assertStringContainsString( 'woocommerce-settings-ui-page', $classes );
		$this->assertStringNotContainsString( 'woocommerce-settings-ui-drill-down', $classes );
	}

	/**
	 * @testdox Should add the drill-down body class for Settings UI drill-down pages.
	 */
	public function test_settings_ui_drill_down_body_class_is_added_for_drill_down_pages(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );

		global $current_section, $current_tab;
		$current_section = 'test_gateway';
		$current_tab     = 'checkout';
		$page            = $this->get_settings_ui_test_page_for_drill_down();

		$classes = $page->add_settings_ui_body_class( 'existing-class woocommerce-settings-ui-page' );

		$this->assertStringContainsString( 'existing-class', $classes );
		$this->assertSame( 1, substr_count( $classes, 'woocommerce-settings-ui-page' ) );
		$this->assertSame( 1, substr_count( $classes, 'woocommerce-settings-ui-drill-down' ) );
	}

	/**
	 * @testdox Should add the exact Settings UI body classes even when a similarly prefixed class is already present.
	 */
	public function test_settings_ui_body_classes_use_exact_token_matching_against_prefixed_classes(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );

		global $current_section, $current_tab;
		$current_section = 'test_gateway';
		$current_tab     = 'checkout';
		$page            = $this->get_settings_ui_test_page_for_drill_down();

		$classes      = $page->add_settings_ui_body_class( 'existing-class woocommerce-settings-ui-page-preview' );
		$body_classes = explode( ' ', $classes );

		$this->assertContains( 'woocommerce-settings-ui-page-preview', $body_classes );
		$this->assertCount( 1, array_keys( $body_classes, 'woocommerce-settings-ui-page', true ) );
		$this->assertCount( 1, array_keys( $body_classes, 'woocommerce-settings-ui-drill-down', true ) );
	}

	/**
	 * @testdox Should not add the Settings UI body classes when schema generation falls back to legacy rendering.
	 */
	public function test_settings_ui_body_classes_are_not_added_when_schema_generation_fails(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );

		global $current_section, $current_tab;
		$current_section = 'test_gateway';
		$current_tab     = 'checkout';
		$page            = $this->get_settings_ui_test_page_with_failing_schema( 'checkout' );

		$classes = $page->add_settings_ui_body_class( 'existing-class' );

		$this->assertSame( 'existing-class', $classes, 'The fallback page should keep the classic body classes so the legacy Save button stays visible' );
	}

	/**
	 * @testdox Should not add the Settings UI body classes when script handle resolution falls back to legacy rendering.
	 */
	public function test_settings_ui_body_classes_are_not_added_when_script_handle_resolution_fails(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );

		global $current_section, $current_tab;
		$current_section = 'test_gateway';
		$current_tab     = 'checkout';
		$page            = $this->get_settings_ui_test_page_with_failing_script_handles( 'checkout' );

		$classes = $page->add_settings_ui_body_class( 'existing-class' );

		$this->assertSame( 'existing-class', $classes, 'The fallback page should keep the classic body classes so the legacy Save button stays visible' );
	}

	/**
	 * @testdox Should not add the Settings UI body class when a top-level page falls back to legacy rendering.
	 */
	public function test_settings_ui_body_class_is_not_added_when_a_top_level_page_falls_back(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );

		global $current_section, $current_tab;
		$current_section = 'failing_section';
		$current_tab     = 'settings_ui_flag_test';
		$page            = $this->get_settings_ui_test_page_with_failing_schema();

		$classes = $page->add_settings_ui_body_class( 'existing-class' );

		$this->assertSame( 'existing-class', $classes, 'The fallback page should keep the classic body classes so the legacy Save button stays visible' );
	}

	/**
	 * Enable the settings UI feature flag.
	 *
	 * @param array $features Feature flags.
	 * @return array
	 */
	public function enable_settings_ui_feature( array $features ): array {
		$features[] = 'settings-ui';
		return array_values( array_unique( $features ) );
	}

	/**
	 * Disable the settings UI feature flag.
	 *
	 * @param array $features Feature flags.
	 * @return array
	 */
	public function disable_settings_ui_feature( array $features ): array {
		return array_values( array_diff( $features, array( 'settings-ui' ) ) );
	}

	/**
	 * Build a settings page that opts into the settings UI renderer.
	 *
	 * @return \WC_Settings_Page
	 */
	private function get_settings_ui_test_page(): \WC_Settings_Page {
		return new class() extends \WC_Settings_Page {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id    = 'settings_ui_flag_test';
				$this->label = 'Settings UI flag test';
			}

			/**
			 * Get the settings UI page adapter.
			 *
			 * @return \Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface|null
			 */
			public function get_settings_ui_page(): ?\Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface {
				return new \Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter( $this );
			}

			/**
			 * Get settings for the default section.
			 *
			 * @return array
			 */
			protected function get_settings_for_default_section() {
				return array(
					array(
						'id'    => 'woocommerce_settings_ui_flag_test',
						'type'  => 'text',
						'title' => 'Settings UI flag test',
					),
				);
			}
		};
	}

	/**
	 * Build a settings page whose settings UI schema asks for a visible shell header.
	 *
	 * @return \WC_Settings_Page
	 */
	private function get_settings_ui_test_page_with_visible_shell_header(): \WC_Settings_Page {
		return new class() extends \WC_Settings_Page {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id    = 'settings_ui_flag_test';
				$this->label = 'Settings UI flag test';
			}

			/**
			 * Get the settings UI page adapter.
			 *
			 * @return \Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface|null
			 */
			public function get_settings_ui_page(): ?\Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface {
				return new class( $this ) extends \Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter {
					/**
					 * Get the schema for a section.
					 *
					 * @param string $section Section id.
					 * @return array
					 */
					public function get_schema( string $section ): array {
						$schema                    = parent::get_schema( $section );
						$schema['shell']['header'] = 'visible';

						return $schema;
					}
				};
			}

			/**
			 * Get settings for the default section.
			 *
			 * @return array
			 */
			protected function get_settings_for_default_section() {
				return array(
					array(
						'id'    => 'woocommerce_settings_ui_flag_test',
						'type'  => 'text',
						'title' => 'Settings UI flag test',
					),
				);
			}
		};
	}

	/**
	 * Build a payments-tab settings page that opts into the settings UI renderer.
	 *
	 * @param array|null $breadcrumbs Optional schema-provided breadcrumbs.
	 * @return \WC_Settings_Page
	 */
	private function get_settings_ui_test_page_for_drill_down( ?array $breadcrumbs = null ): \WC_Settings_Page {
		return new class( $breadcrumbs ) extends \WC_Settings_Page {
			/**
			 * Schema-provided breadcrumbs, if any.
			 *
			 * @var array|null
			 */
			private ?array $breadcrumbs;

			/**
			 * Constructor.
			 *
			 * @param array|null $breadcrumbs Optional schema-provided breadcrumbs.
			 */
			public function __construct( ?array $breadcrumbs ) {
				$this->id          = 'checkout';
				$this->label       = 'Payments drill-down test';
				$this->breadcrumbs = $breadcrumbs;
			}

			/**
			 * Get the settings UI page adapter.
			 *
			 * @return \Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface|null
			 */
			public function get_settings_ui_page(): ?\Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface {
				return new class( $this, $this->breadcrumbs ) extends \Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter {
					/**
					 * Schema-provided breadcrumbs, if any.
					 *
					 * @var array|null
					 */
					private ?array $breadcrumbs;

					/**
					 * Constructor.
					 *
					 * @param \WC_Settings_Page $settings_page Settings page.
					 * @param array|null        $breadcrumbs Optional schema-provided breadcrumbs.
					 */
					public function __construct( \WC_Settings_Page $settings_page, ?array $breadcrumbs ) {
						parent::__construct( $settings_page );
						$this->breadcrumbs = $breadcrumbs;
					}

					/**
					 * Get the schema for a section.
					 *
					 * @param string $section Section id.
					 * @return array
					 */
					public function get_schema( string $section ): array {
						$schema = parent::get_schema( $section );

						if ( null !== $this->breadcrumbs ) {
							$schema['shell']['breadcrumbs'] = $this->breadcrumbs;
						}

						return $schema;
					}
				};
			}

			/**
			 * Get settings for any section.
			 *
			 * @param string $section_id Section id.
			 * @return array
			 */
			protected function get_settings_for_section_core( $section_id ) {
				// Avoid parameter not used PHPCS errors.
				unset( $section_id );

				return array(
					array(
						'id'    => 'woocommerce_settings_ui_drill_down_test',
						'type'  => 'text',
						'title' => 'Drill-down test',
					),
				);
			}
		};
	}

	/**
	 * Get captured doing-it-wrong notices emitted by the settings page output method.
	 *
	 * @param array $notices Captured doing-it-wrong notices.
	 * @return array
	 */
	private function get_settings_page_output_notices( array $notices ): array {
		return array_values(
			array_filter(
				$notices,
				static function ( array $notice ): bool {
					return 'WC_Settings_Page::output' === $notice['function_name'];
				}
			)
		);
	}

	/**
	 * Build a settings page whose settings UI adapter cannot provide script handles.
	 *
	 * @param string $page_id Page id.
	 * @return \WC_Settings_Page
	 */
	private function get_settings_ui_test_page_with_failing_script_handles( string $page_id = 'settings_ui_flag_test' ): \WC_Settings_Page {
		return new class( $page_id ) extends \WC_Settings_Page {
			/**
			 * Constructor.
			 *
			 * @param string $page_id Page id.
			 */
			public function __construct( string $page_id ) {
				$this->id    = $page_id;
				$this->label = 'Settings UI flag test';
			}

			/**
			 * Get the settings UI page adapter.
			 *
			 * @return \Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface|null
			 */
			public function get_settings_ui_page(): ?\Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface {
				return new class( $this ) extends \Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter {
					/**
					 * Get script handles.
					 *
					 * @param string $section_id Section id.
					 * @return array
					 */
					public function get_script_handles( string $section_id ): array {
						if ( '' !== $section_id ) {
							throw new \RuntimeException( 'Unable to load extension script handles.' );
						}

						return array();
					}
				};
			}

			/**
			 * Get settings for a section.
			 *
			 * @param string $section_id Section id.
			 * @return array
			 */
			protected function get_settings_for_section_core( $section_id ) {
				return array(
					array(
						'id'    => 'woocommerce_settings_ui_flag_test',
						'type'  => 'text',
						'title' => 'Settings UI flag test',
					),
				);
			}
		};
	}

	/**
	 * Build a settings page whose settings UI adapter cannot provide a schema.
	 *
	 * @param string $page_id Page id.
	 * @return \WC_Settings_Page
	 */
	private function get_settings_ui_test_page_with_failing_schema( string $page_id = 'settings_ui_flag_test' ): \WC_Settings_Page {
		return new class( $page_id ) extends \WC_Settings_Page {
			/**
			 * Constructor.
			 *
			 * @param string $page_id Page id.
			 */
			public function __construct( string $page_id ) {
				$this->id    = $page_id;
				$this->label = 'Settings UI flag test';
			}

			/**
			 * Get the settings UI page adapter.
			 *
			 * @return \Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface|null
			 */
			public function get_settings_ui_page(): ?\Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface {
				return new class( $this ) extends \Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter {
					/**
					 * Build the schema.
					 *
					 * @param string $section_id Section id.
					 * @return array
					 */
					public function get_schema( string $section_id ): array {
						if ( '' !== $section_id ) {
							throw new \RuntimeException( 'Unable to build settings UI schema.' );
						}

						return parent::get_schema( $section_id );
					}
				};
			}

			/**
			 * Get settings for a section.
			 *
			 * @param string $section_id Section id.
			 * @return array
			 */
			protected function get_settings_for_section_core( $section_id ) {
				return array(
					array(
						'id'    => 'woocommerce_settings_ui_flag_test',
						'type'  => 'text',
						'title' => 'Settings UI flag test',
					),
				);
			}
		};
	}

	/**
	 * Build a settings page whose Settings UI schema contains an unsupported field type.
	 *
	 * @return \WC_Settings_Page
	 */
	private function get_settings_ui_test_page_with_invalid_schema(): \WC_Settings_Page {
		return new class() extends \WC_Settings_Page {
			/**
			 * Schema resolution count.
			 *
			 * @var int
			 */
			private int $schema_resolution_count = 0;

			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id    = 'settings_ui_flag_test';
				$this->label = 'Settings UI flag test';
			}

			/**
			 * Get the settings UI page adapter.
			 *
			 * @return \Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface|null
			 */
			public function get_settings_ui_page(): ?\Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface {
				return new class( $this ) extends \Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter {
					/**
					 * Build an invalid schema.
					 *
					 * @param string $section_id Section id.
					 * @return array
					 */
					public function get_schema( string $section_id ): array {
						$schema = parent::get_schema( $section_id );
						$this->settings_page->increment_schema_resolution_count();
						$schema['groups']['default']['fields'][0]['type'] = 'future-control';

						return $schema;
					}
				};
			}

			/**
			 * Increment the schema resolution count.
			 */
			public function increment_schema_resolution_count(): void {
				++$this->schema_resolution_count;
			}

			/**
			 * Get the schema resolution count.
			 *
			 * @return int
			 */
			public function get_schema_resolution_count(): int {
				return $this->schema_resolution_count;
			}

			/**
			 * Get settings for the default section.
			 *
			 * @return array
			 */
			protected function get_settings_for_default_section() {
				return array(
					array(
						'id'    => 'woocommerce_settings_ui_flag_test',
						'type'  => 'text',
						'title' => 'Settings UI flag test',
					),
				);
			}
		};
	}

	/**
	 * Build a settings page whose numeric value cannot cross the JavaScript boundary safely.
	 *
	 * @return \WC_Settings_Page
	 */
	private function get_settings_ui_test_page_with_invalid_canonical_value(): \WC_Settings_Page {
		return new class() extends \WC_Settings_Page {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id    = 'settings_ui_flag_test';
				$this->label = 'Settings UI flag test';
			}

			/**
			 * Get the settings UI page adapter.
			 *
			 * @return \Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface|null
			 */
			public function get_settings_ui_page(): ?\Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface {
				return new \Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter( $this );
			}

			/**
			 * Get settings for the default section.
			 *
			 * @return array
			 */
			protected function get_settings_for_default_section() {
				return array(
					array(
						'id'                => 'woocommerce_settings_ui_flag_test',
						'type'              => 'number',
						'title'             => 'Settings UI flag test',
						'custom_attributes' => array( 'step' => 1 ),
					),
				);
			}
		};
	}

	/**
	 * Build a settings page declaring extension script handles.
	 *
	 * @param array $script_handles Script handles.
	 * @return \WC_Settings_Page
	 */
	private function get_settings_ui_test_page_with_script_handles( array $script_handles ): \WC_Settings_Page {
		return new class( $script_handles ) extends \WC_Settings_Page {
			/**
			 * Extension script handles.
			 *
			 * @var array
			 */
			private array $script_handles;

			/**
			 * Constructor.
			 *
			 * @param array $script_handles Script handles.
			 */
			public function __construct( array $script_handles ) {
				$this->id             = 'settings_ui_flag_test';
				$this->label          = 'Settings UI flag test';
				$this->script_handles = $script_handles;
			}

			/**
			 * Get the settings UI page adapter.
			 *
			 * @return \Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface|null
			 */
			public function get_settings_ui_page(): ?\Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface {
				return new class( $this, $this->script_handles ) extends \Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter {
					/**
					 * Extension script handles.
					 *
					 * @var array
					 */
					private array $script_handles;

					/**
					 * Constructor.
					 *
					 * @param \WC_Settings_Page $settings_page Settings page.
					 * @param array             $script_handles Script handles.
					 */
					public function __construct( \WC_Settings_Page $settings_page, array $script_handles ) {
						parent::__construct( $settings_page );
						$this->script_handles = $script_handles;
					}

					/**
					 * Get script handles.
					 *
					 * @param string $section_id Section id.
					 * @return array
					 */
					public function get_script_handles( string $section_id ): array {
						unset( $section_id );

						return $this->script_handles;
					}
				};
			}

			/**
			 * Get settings for the default section.
			 *
			 * @return array
			 */
			protected function get_settings_for_default_section() {
				return array(
					array(
						'id'    => 'woocommerce_settings_ui_flag_test',
						'type'  => 'text',
						'title' => 'Settings UI flag test',
					),
				);
			}
		};
	}

	/**
	 * Get the script handle resolution count for a counting test page.
	 *
	 * @param \WC_Settings_Page $page Settings page.
	 * @return int
	 */
	private function get_script_handle_resolution_count( \WC_Settings_Page $page ): int {
		$method = new \ReflectionMethod( $page, 'get_script_handle_resolution_count' );
		$method->setAccessible( true );

		return (int) $method->invoke( $page );
	}

	/**
	 * Get the schema resolution count for a counting test page.
	 *
	 * @param \WC_Settings_Page $page Settings page.
	 * @return int
	 */
	private function get_schema_resolution_count( \WC_Settings_Page $page ): int {
		$method = new \ReflectionMethod( $page, 'get_schema_resolution_count' );
		$method->setAccessible( true );

		return (int) $method->invoke( $page );
	}

	/**
	 * Build a settings page with counting script handles.
	 *
	 * @return \WC_Settings_Page
	 */
	private function get_settings_ui_test_page_with_counting_script_handles(): \WC_Settings_Page {
		return new class() extends \WC_Settings_Page {
			/**
			 * Script handle resolution count.
			 *
			 * @var int
			 */
			private int $script_handle_resolution_count = 0;

			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id    = 'settings_ui_flag_test';
				$this->label = 'Settings UI flag test';
			}

			/**
			 * Get the settings UI page adapter.
			 *
			 * @return \Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface|null
			 */
			public function get_settings_ui_page(): ?\Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface {
				return new class( $this ) extends \Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter {
					/**
					 * Get script handles.
					 *
					 * @param string $section_id Section id.
					 * @return array
					 */
					public function get_script_handles( string $section_id ): array {
						$this->settings_page->increment_script_handle_resolution_count();
						return array( 'settings-ui-counting-handle' );
					}
				};
			}

			/**
			 * Increment the script handle resolution count.
			 */
			public function increment_script_handle_resolution_count(): void {
				++$this->script_handle_resolution_count;
			}

			/**
			 * Get the script handle resolution count.
			 *
			 * @return int
			 */
			public function get_script_handle_resolution_count(): int {
				return $this->script_handle_resolution_count;
			}

			/**
			 * Get settings for the default section.
			 *
			 * @return array
			 */
			protected function get_settings_for_default_section() {
				return array(
					array(
						'id'    => 'woocommerce_settings_ui_flag_test',
						'type'  => 'text',
						'title' => 'Settings UI flag test',
					),
				);
			}
		};
	}

	/**
	 * Build a settings page with multiple sections.
	 *
	 * @return \WC_Settings_Page
	 */
	private function get_settings_ui_page_with_sections(): \WC_Settings_Page {
		return new class() extends \WC_Settings_Page {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id    = 'settings_ui_flag_test';
				$this->label = 'Settings UI flag test';
			}

			/**
			 * Get the settings UI page adapter.
			 *
			 * @return \Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface|null
			 */
			public function get_settings_ui_page(): ?\Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface {
				return new \Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter( $this );
			}

			/**
			 * Get sections for this test page.
			 *
			 * @return array
			 */
			protected function get_own_sections() {
				return array(
					''          => 'General',
					'inventory' => 'Inventory',
				);
			}
		};
	}

	/**
	 * Invoke a private method for focused feature-flag assertions.
	 *
	 * @param object $target Object instance.
	 * @param string $method_name Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed
	 */
	private function invoke_private_method( object $target, string $method_name, array $arguments = array() ) {
		$method = new \ReflectionMethod( $target, $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $target, $arguments );
	}

	/**
	 * Render the complete classic settings view for a test page.
	 *
	 * @param \WC_Settings_Page $page Settings page.
	 * @return string
	 */
	private function render_settings_view( \WC_Settings_Page $page ): string {
		global $current_tab;

		$tabs   = array( $current_tab => $page->get_label() );
		$action = array( $page, 'output' );
		add_action( 'woocommerce_settings_' . $current_tab, $action );

		try {
			ob_start();
			include WC_ABSPATH . 'includes/admin/views/html-admin-settings.php';
			return (string) ob_get_clean();
		} finally {
			remove_action( 'woocommerce_settings_' . $current_tab, $action );
		}
	}
}
