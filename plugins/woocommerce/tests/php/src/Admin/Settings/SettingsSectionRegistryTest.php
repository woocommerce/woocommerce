<?php
/**
 * Settings section registry tests.
 *
 * @package WooCommerce\Tests\Admin\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Settings;

use Automattic\WooCommerce\Admin\Settings\SettingsSection;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionInterface;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;
use Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface;
use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIRequestContext;
use WC_Unit_Test_Case;

/**
 * Tests for settings section registration.
 */
class SettingsSectionRegistryTest extends WC_Unit_Test_Case {

	/**
	 * Original current settings section.
	 *
	 * @var mixed
	 */
	private $original_current_section = null;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		include_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';

		global $current_section;
		$this->original_current_section = $current_section ?? null;

		SettingsSectionRegistry::get_instance()->unregister_all();
		SettingsUIRequestContext::reset();
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		global $current_section;
		$current_section = $this->original_current_section;

		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		SettingsSectionRegistry::get_instance()->unregister_all();
		SettingsUIRequestContext::reset();

		parent::tearDown();
	}

	/**
	 * @testdox Should register sections through the registration action.
	 */
	public function test_registers_sections_through_registration_action(): void {
		$page    = $this->get_parent_page();
		$section = $this->get_registered_section();
		$action  = static function ( SettingsSectionRegistry $registry ) use ( $section ): void {
			$registry->register( $section );
		};

		add_action( 'woocommerce_settings_sections_registration', $action );

		try {
			$sections = $page->get_sections();
		} finally {
			remove_action( 'woocommerce_settings_sections_registration', $action );
		}

		$this->assertArrayHasKey( 'acme_payments', $sections, 'Registered section should be exposed by its parent page.' );
		$this->assertSame( 'Acme Payments', $sections['acme_payments'] );
	}

	/**
	 * @testdox Should provide registered section legacy settings to the parent page.
	 */
	public function test_provides_registered_section_legacy_settings(): void {
		$page = $this->get_parent_page();
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section() );

		$settings = $page->get_settings_for_section( 'acme_payments' );

		$this->assertSame( 'registered_acme_payments_setting', $settings[0]['id'] );
	}

	/**
	 * @testdox Should resolve a registered section settings UI adapter before the parent page adapter.
	 */
	public function test_resolves_registered_section_settings_ui_adapter(): void {
		$page = $this->get_parent_page();
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section() );

		$settings_ui_page = SettingsUIRequestContext::for_settings_page( $page, 'acme_payments' )->get_settings_ui_page();

		$this->assertInstanceOf( SettingsUIPageInterface::class, $settings_ui_page );
		$this->assertSame( 'checkout', $settings_ui_page->get_page_id() );
		$this->assertSame( array( 'acme-payments-settings-ui' ), $settings_ui_page->get_script_handles( 'acme_payments' ) );
		$this->assertSame( 'form_post', $settings_ui_page->get_save_adapter( 'acme_payments' ) );
	}

	/**
	 * @testdox Should render a registered section through the settings UI when the feature is enabled.
	 */
	public function test_renders_registered_section_with_settings_ui(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_settings_ui_feature' ) );
		SettingsSectionRegistry::get_instance()->register( $this->get_registered_section() );

		global $current_section;
		$current_section = 'acme_payments';
		$page            = $this->get_parent_page();

		ob_start();
		$page->output();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-wc-settings-ui="1"', $output );
		$this->assertStringContainsString( 'data-wc-settings-page="checkout"', $output );
		$this->assertStringNotContainsString( 'name="registered_acme_payments_setting"', $output );
	}

	/**
	 * @testdox Should contain registration action failures.
	 */
	public function test_registration_action_failures_are_contained(): void {
		$calls  = 0;
		$action = static function () use ( &$calls ): void {
			++$calls;
			throw new \Error( 'Broken settings section registration.' );
		};
		add_action( 'woocommerce_settings_sections_registration', $action );

		try {
			$sections      = SettingsSectionRegistry::get_instance()->get_sections_for_page( 'checkout' );
			$second_lookup = SettingsSectionRegistry::get_instance()->get_sections_for_page( 'checkout' );
		} finally {
			remove_action( 'woocommerce_settings_sections_registration', $action );
		}

		$this->assertSame( array(), $sections );
		$this->assertSame( array(), $second_lookup );
		$this->assertSame( 1, $calls, 'The registration action should not be retried after a failure.' );
	}

	/**
	 * @testdox Should reject checkout sections that collide with payment gateway ids.
	 */
	public function test_rejects_checkout_sections_that_collide_with_payment_gateway_ids(): void {
		$this->setExpectedIncorrectUsage( SettingsSectionRegistry::class . '::register' );

		$result = SettingsSectionRegistry::get_instance()->register( $this->get_registered_section( 'bacs' ) );

		$this->assertFalse( $result );
		$this->assertNull( SettingsSectionRegistry::get_instance()->get_registered( 'checkout', 'bacs' ) );
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
	 * Build a parent settings page.
	 *
	 * @return \WC_Settings_Page
	 */
	private function get_parent_page(): \WC_Settings_Page {
		return new class() extends \WC_Settings_Page {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id    = 'checkout';
				$this->label = 'Payments';
			}
		};
	}

	/**
	 * Build a registered test section.
	 *
	 * @param string $section_id Section id.
	 * @return SettingsSectionInterface
	 */
	private function get_registered_section( string $section_id = 'acme_payments' ): SettingsSectionInterface {
		return new class( $section_id ) extends SettingsSection {
			/**
			 * Section id.
			 *
			 * @var string
			 */
			private string $section_id;

			/**
			 * Constructor.
			 *
			 * @param string $section_id Section id.
			 */
			public function __construct( string $section_id ) {
				$this->section_id = $section_id;
			}

			/**
			 * Get the parent page id.
			 *
			 * @return string
			 */
			public function get_parent_page_id(): string {
				return 'checkout';
			}

			/**
			 * Get the section id.
			 *
			 * @return string
			 */
			public function get_id(): string {
				return $this->section_id;
			}

			/**
			 * Get the section label.
			 *
			 * @return string
			 */
			public function get_label(): string {
				return 'Acme Payments';
			}

			/**
			 * Get legacy settings.
			 *
			 * @param \WC_Settings_Page $parent_page Parent settings page.
			 * @return array
			 */
			public function get_settings( \WC_Settings_Page $parent_page ): array {
				return array(
					array(
						'id'    => 'registered_' . $this->section_id . '_setting',
						'type'  => 'text',
						'title' => 'Registered Acme Payments setting',
					),
				);
			}

			/**
			 * Get script handles.
			 *
			 * @param \WC_Settings_Page $parent_page Parent settings page.
			 * @return string[]
			 */
			public function get_script_handles( \WC_Settings_Page $parent_page ): array {
				return array( 'acme-payments-settings-ui' );
			}

		};
	}
}
