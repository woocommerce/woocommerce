<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Settings;

use Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter;
use Automattic\WooCommerce\Admin\Settings\SettingsSection;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;
use Automattic\WooCommerce\Admin\Settings\SettingsUISchema;
use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIDeprecation;
use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIRequestContext;
use WC_Unit_Test_Case;

/**
 * Tests that the deprecated Settings UI classes still load, do nothing and log their use.
 */
class DeprecatedSettingsUITest extends WC_Unit_Test_Case {

	/**
	 * Warnings logged during the test.
	 *
	 * @var string[]
	 */
	private array $warnings = array();

	/**
	 * Capture logged warnings.
	 */
	public function setUp(): void {
		parent::setUp();
		include_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';
		SettingsUIDeprecation::reset();
		SettingsSectionRegistry::get_instance()->unregister_all();

		$logger = $this->getMockBuilder( \WC_Logger_Interface::class )->getMock();
		$logger->method( 'warning' )->willReturnCallback(
			function ( $message ) {
				$this->warnings[] = $message;
			}
		);
		add_filter( 'woocommerce_logging_class', fn() => $logger );
	}

	/**
	 * Reset the registry and recorded usage.
	 */
	public function tearDown(): void {
		SettingsUIDeprecation::reset();
		SettingsSectionRegistry::get_instance()->unregister_all();
		parent::tearDown();
	}

	/**
	 * @testdox The public adapter keeps working for subclasses, returns neutral values and logs its use.
	 */
	public function test_adapter_returns_neutral_values(): void {
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
		$this->assertCount( 3, $this->warnings, 'Each deprecated method should log once' );
		$this->assertStringContainsString( 'LegacySettingsPageAdapter::get_schema is deprecated', $this->warnings[0] );
	}

	/**
	 * @testdox The schema helpers return neutral values and log each use once per request.
	 */
	public function test_schema_returns_neutral_values(): void {
		$schema = array( 'groups' => array() );

		$this->assertSame( array(), SettingsUISchema::from_legacy_settings( 'example', '', 'Example', array() ) );
		$this->assertSame( $schema, SettingsUISchema::canonicalize_option_values( $schema ) );
		$this->assertSame( $schema, SettingsUISchema::canonicalize_schema_values( $schema ) );
		SettingsUISchema::assert_valid_schema( $schema );
		SettingsUISchema::assert_valid_schema( $schema );

		$this->assertCount( 4, $this->warnings, 'Repeated calls should only log once' );
	}

	/**
	 * @testdox Registering a section adds nothing and logs the attempt.
	 */
	public function test_registry_does_not_add_sections(): void {
		$registry = SettingsSectionRegistry::get_instance();

		$this->assertFalse( $registry->register( $this->get_section() ) );
		$this->assertNull( $registry->get_registered( 'checkout', 'example_section' ) );
		$this->assertSame( array(), $registry->get_sections_for_page( 'checkout' ) );
		$this->assertCount( 1, $this->warnings );
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
