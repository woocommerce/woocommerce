<?php
/**
 * Modern settings feature flag tests.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use WC_Unit_Test_Case;

/**
 * Tests for the modern settings feature flag boundary.
 */
class ModernSettingsFeatureFlagTest extends WC_Unit_Test_Case {

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
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		include_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
		include_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';

		global $current_section, $current_tab;

		$this->original_get             = $_GET;
		$this->original_current_section = $current_section ?? null;
		$this->original_current_tab     = $current_tab ?? null;
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		global $current_section, $current_tab;

		$_GET           = $this->original_get;
		$current_section = $this->original_current_section;
		$current_tab     = $this->original_current_tab;
		unset( $GLOBALS['hide_save_button'] );

		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_modern_settings_feature' ) );
		remove_filter( 'woocommerce_admin_features', array( $this, 'disable_modern_settings_feature' ) );

		parent::tearDown();
	}

	/**
	 * It keeps opted-in pages on the legacy renderer when the feature flag is disabled.
	 */
	public function test_opted_in_page_uses_legacy_output_when_feature_flag_is_disabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_modern_settings_feature' ) );

		global $current_section;
		$current_section = '';
		$page            = $this->get_modern_settings_test_page();

		ob_start();
		$page->output();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'name="woocommerce_modern_settings_flag_test"', $output );
		$this->assertStringNotContainsString( 'data-wc-modern-settings="1"', $output );
		$this->assertArrayNotHasKey( 'hide_save_button', $GLOBALS );
	}

	/**
	 * It renders the modern mount point only when the feature flag is enabled.
	 */
	public function test_opted_in_page_uses_modern_output_when_feature_flag_is_enabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_modern_settings_feature' ) );

		global $current_section;
		$current_section = '';
		$page            = $this->get_modern_settings_test_page();

		ob_start();
		$page->output();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-wc-modern-settings="1"', $output );
		$this->assertStringContainsString( 'data-wc-settings-page="modern_settings_flag_test"', $output );
		$this->assertStringNotContainsString( 'name="woocommerce_modern_settings_flag_test"', $output );
		$this->assertTrue( $GLOBALS['hide_save_button'] );
	}

	/**
	 * It exposes section navigation metadata from legacy settings pages.
	 */
	public function test_legacy_adapter_adds_shell_navigation_metadata(): void {
		$page    = $this->get_modern_settings_page_with_sections();
		$adapter = new \Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter( $page );
		$schema  = $adapter->get_schema( '' );

		$this->assertSame( 'Modern settings flag test', $schema['shell']['title'] );
		$this->assertArrayNotHasKey( 'breadcrumbs', $schema['shell'] );
		$this->assertArrayNotHasKey( 'navigation', $schema['shell'] );
		$this->assertSame( 'General', $schema['shell']['sectionNavigation'][0]['label'] );
		$this->assertTrue( $schema['shell']['sectionNavigation'][0]['active'] );
		$this->assertSame( 'inventory', $schema['shell']['sectionNavigation'][1]['id'] );
	}

	/**
	 * It does not inject modern settings shared data when the feature flag is disabled.
	 */
	public function test_shared_settings_are_not_injected_when_feature_flag_is_disabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_modern_settings_feature' ) );

		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'products';

		$settings = $this->invoke_private_method( new Settings(), 'add_modern_settings_schema', array( array() ) );

		$this->assertArrayNotHasKey( 'modernSettings', $settings );
	}

	/**
	 * It does not add modern settings script dependencies when the feature flag is disabled.
	 */
	public function test_modern_settings_script_dependencies_are_empty_when_feature_flag_is_disabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_modern_settings_feature' ) );

		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'products';

		$dependencies = $this->invoke_private_method( new WCAdminAssets(), 'get_modern_settings_script_dependencies' );

		$this->assertSame( array(), $dependencies );
	}

	/**
	 * It does not add the modern settings body class when the feature flag is disabled.
	 */
	public function test_modern_settings_body_class_is_not_added_when_feature_flag_is_disabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_modern_settings_feature' ) );

		global $current_tab;
		$current_tab = 'modern_settings_flag_test';
		$page        = $this->get_modern_settings_test_page();

		$classes = $page->add_modern_settings_body_class( 'existing-class' );

		$this->assertSame( 'existing-class', $classes );
	}

	/**
	 * It adds the modern settings body class when the feature flag is enabled.
	 */
	public function test_modern_settings_body_class_is_added_when_feature_flag_is_enabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_modern_settings_feature' ) );

		global $current_tab;
		$current_tab = 'modern_settings_flag_test';
		$page        = $this->get_modern_settings_test_page();

		$classes = $page->add_modern_settings_body_class( 'existing-class' );

		$this->assertStringContainsString( 'existing-class', $classes );
		$this->assertStringContainsString( 'woocommerce-modern-settings-page', $classes );
	}

	/**
	 * Enable the modern settings feature flag.
	 *
	 * @param array $features Feature flags.
	 * @return array
	 */
	public function enable_modern_settings_feature( array $features ): array {
		$features[] = 'modern-settings';
		return array_values( array_unique( $features ) );
	}

	/**
	 * Disable the modern settings feature flag.
	 *
	 * @param array $features Feature flags.
	 * @return array
	 */
	public function disable_modern_settings_feature( array $features ): array {
		return array_values( array_diff( $features, array( 'modern-settings' ) ) );
	}

	/**
	 * Build a settings page that opts into the modern renderer.
	 *
	 * @return \WC_Settings_Page
	 */
	private function get_modern_settings_test_page(): \WC_Settings_Page {
		return new class() extends \WC_Settings_Page {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id    = 'modern_settings_flag_test';
				$this->label = 'Modern settings flag test';
			}

			/**
			 * Get the modern settings page adapter.
			 *
			 * @return \Automattic\WooCommerce\Admin\Settings\ModernSettingsPageInterface|null
			 */
			public function get_modern_settings_page(): ?\Automattic\WooCommerce\Admin\Settings\ModernSettingsPageInterface {
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
						'id'    => 'woocommerce_modern_settings_flag_test',
						'type'  => 'text',
						'title' => 'Modern settings flag test',
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
	private function get_modern_settings_page_with_sections(): \WC_Settings_Page {
		return new class() extends \WC_Settings_Page {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id    = 'modern_settings_flag_test';
				$this->label = 'Modern settings flag test';
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
	 * @param object $object Object instance.
	 * @param string $method_name Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed
	 */
	private function invoke_private_method( object $object, string $method_name, array $arguments = array() ) {
		$method = new \ReflectionMethod( $object, $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $arguments );
	}
}
