<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Settings;

use Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter;
use Automattic\WooCommerce\Admin\Settings\SettingsSection;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;
use Automattic\WooCommerce\Admin\Settings\SettingsUISchema;
use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIRequestContext;
use WC_Unit_Test_Case;

/**
 * Tests that the deprecated Settings UI classes still load, do nothing and report their use.
 */
class DeprecatedSettingsUITest extends WC_Unit_Test_Case {

	/**
	 * Load the settings page class and reset the registry.
	 */
	public function setUp(): void {
		parent::setUp();
		include_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';
		SettingsSectionRegistry::get_instance()->unregister_all();
	}

	/**
	 * Reset the registry.
	 */
	public function tearDown(): void {
		SettingsSectionRegistry::get_instance()->unregister_all();
		parent::tearDown();
	}

	/**
	 * @testdox The public adapter keeps working for subclasses, returns neutral values and reports its use as deprecated.
	 */
	public function test_adapter_returns_neutral_values(): void {
		$this->setExpectedDeprecated( 'Automattic\WooCommerce\Internal\Admin\Settings\LegacySettingsPageAdapter::get_schema' );
		$this->setExpectedDeprecated( 'Automattic\WooCommerce\Internal\Admin\Settings\LegacySettingsPageAdapter::get_script_handles' );
		$this->setExpectedDeprecated( 'Automattic\WooCommerce\Internal\Admin\Settings\LegacySettingsPageAdapter::get_save_adapter' );

		$page    = $this->get_settings_page();
		$adapter = new class( $page ) extends LegacySettingsPageAdapter {
			/**
			 * Call the parent, as extensions that subclass the adapter do.
			 *
			 * @param string $section Section id.
			 * @return array
			 */
			public function get_schema( string $section ): array {
				return array_merge( parent::get_schema( $section ), array( 'extended' => true ) );
			}
		};

		$this->assertSame( 'example', $adapter->get_page_id() );
		$this->assertSame( array( 'extended' => true ), $adapter->get_schema( '' ) );
		$this->assertSame( array(), $adapter->get_script_handles( '' ) );
		$this->assertSame( 'form_post', $adapter->get_save_adapter( '' ) );
	}

	/**
	 * @testdox The schema helpers return neutral values and report their use as deprecated.
	 */
	public function test_schema_returns_neutral_values(): void {
		foreach ( array( 'from_legacy_settings', 'canonicalize_option_values', 'canonicalize_schema_values', 'assert_valid_schema' ) as $method ) {
			$this->setExpectedDeprecated( 'Automattic\WooCommerce\Internal\Admin\Settings\SettingsUISchema::' . $method );
		}
		$schema = array( 'groups' => array() );

		$this->assertSame( array(), SettingsUISchema::from_legacy_settings( 'example', '', 'Example', array() ) );
		$this->assertSame( $schema, SettingsUISchema::canonicalize_option_values( $schema ) );
		$this->assertSame( $schema, SettingsUISchema::canonicalize_schema_values( $schema ) );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox Registering a section adds nothing and reports the call as deprecated.
	 */
	public function test_registry_does_not_add_sections(): void {
		$this->setExpectedDeprecated( 'Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry::register' );
		$registry = SettingsSectionRegistry::get_instance();

		$this->assertFalse( $registry->register( $this->get_section() ) );
		$this->assertNull( $registry->get_registered( 'checkout', 'example_section' ) );
		$this->assertSame( array(), $registry->get_sections_for_page( 'checkout' ) );
	}

	/**
	 * @testdox Sections registered through the deprecated action don't appear on the settings page.
	 */
	public function test_registration_action_is_deprecated(): void {
		$section = $this->get_section();
		add_action(
			'woocommerce_settings_sections_registration',
			static function ( $registry ) use ( $section ) {
				$registry->register( $section );
			}
		);
		$this->setExpectedDeprecated( 'woocommerce_settings_sections_registration' );
		$this->setExpectedDeprecated( 'Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry::register' );

		$sections = $this->get_settings_page()->get_sections();

		$this->assertArrayNotHasKey( 'example_section', $sections );
	}

	/**
	 * @testdox Settings pages and old request context callers get the classic renderer.
	 */
	public function test_settings_page_uses_classic_renderer(): void {
		$page    = $this->get_settings_page();
		$context = SettingsUIRequestContext::for_settings_page( $page, '' );

		$this->assertNull( $page->get_settings_ui_page() );
		$this->assertSame( 'wp-admin', $page->add_settings_ui_body_class( 'wp-admin' ) );
		$this->assertNull( SettingsUIRequestContext::get_current() );
		$this->assertFalse( $context->is_rendering_enabled() );
		$this->assertSame( 'example', $context->get_page_id() );
	}

	/**
	 * Get a settings page.
	 *
	 * @return \WC_Settings_Page
	 */
	private function get_settings_page(): \WC_Settings_Page {
		return new class() extends \WC_Settings_Page {
			/**
			 * Set up the page.
			 */
			public function __construct() {
				$this->id    = 'example';
				$this->label = 'Example';
			}
		};
	}

	/**
	 * Get a settings section.
	 *
	 * @return SettingsSection
	 */
	private function get_section(): SettingsSection {
		return new class() extends SettingsSection {
			/**
			 * Get the parent page id.
			 *
			 * @return string
			 */
			public function get_parent_page_id(): string {
				return 'example';
			}

			/**
			 * Get the section id.
			 *
			 * @return string
			 */
			public function get_id(): string {
				return 'example_section';
			}

			/**
			 * Get the section label.
			 *
			 * @return string
			 */
			public function get_label(): string {
				return 'Example section';
			}

			/**
			 * Get the section settings.
			 *
			 * @param \WC_Settings_Page $parent_page Parent settings page.
			 * @return array
			 */
			public function get_settings( \WC_Settings_Page $parent_page ): array {
				return array();
			}
		};
	}
}
