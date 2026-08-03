<?php
/**
 * Settings UI feature flag tests.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

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
		$this->assertStringContainsString( 'Settings UI schema generation failed.', $settings_page_notices[0]['message'] );
	}

	/**
	 * @testdox Should resolve Settings UI script handles once per context.
	 */
	public function test_settings_ui_script_handles_are_resolved_once_per_context(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );

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
}
