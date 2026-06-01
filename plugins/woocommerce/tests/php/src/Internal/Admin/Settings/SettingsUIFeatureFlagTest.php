<?php
/**
 * Settings UI feature flag tests.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings;
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
	 * It exposes section navigation metadata from legacy settings pages.
	 */
	public function test_legacy_adapter_adds_shell_navigation_metadata(): void {
		$page    = $this->get_settings_ui_page_with_sections();
		$adapter = new \Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter( $page );
		$schema  = $adapter->get_schema( '' );

		$this->assertSame( 'Settings UI flag test', $schema['shell']['title'] );
		$this->assertArrayNotHasKey( 'breadcrumbs', $schema['shell'] );
		$this->assertArrayNotHasKey( 'navigation', $schema['shell'] );
		$this->assertSame( 'General', $schema['shell']['sectionNavigation'][0]['label'] );
		$this->assertTrue( $schema['shell']['sectionNavigation'][0]['active'] );
		$this->assertSame( 'inventory', $schema['shell']['sectionNavigation'][1]['id'] );
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
	 * It adds the settings UI body class when the feature flag is enabled.
	 */
	public function test_settings_ui_body_class_is_added_when_feature_flag_is_enabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );

		global $current_tab;
		$current_tab = 'settings_ui_flag_test';
		$page        = $this->get_settings_ui_test_page();

		$classes = $page->add_settings_ui_body_class( 'existing-class' );

		$this->assertStringContainsString( 'existing-class', $classes );
		$this->assertStringContainsString( 'woocommerce-settings-ui-page', $classes );
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
