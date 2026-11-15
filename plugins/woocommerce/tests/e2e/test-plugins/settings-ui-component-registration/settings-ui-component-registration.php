<?php
/**
 * Plugin Name: WooCommerce Settings UI Component Registration Test
 * Description: Provides Settings UI component registration scenarios for end-to-end tests.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-settings-ui-component-registration-test
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Admin\Settings\SettingsSection;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Settings UI test fixture.
 */
final class WC_Settings_UI_Component_Registration_Test_Plugin {

	private const REGISTERED_HANDLE = 'settings-ui-component-test-registered';
	private const MISSING_HANDLE    = 'settings-ui-component-test-missing-registration';

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'register_scripts' ) );
		add_action( 'woocommerce_settings_sections_registration', array( self::class, 'register_sections' ) );
	}

	/**
	 * Register test scripts before Settings UI dependencies are resolved.
	 *
	 * @internal
	 */
	public static function register_scripts(): void {
		wp_register_script( self::REGISTERED_HANDLE, false, array( 'wc-settings-ui', 'wp-element' ), '1.0.0', true );
		wp_add_inline_script(
			self::REGISTERED_HANDLE,
			<<<'JS'
window.wc.settingsUi.registerSettingsExtension( {
	scope: { page: 'products', section: 'settings_ui_component_registered' },
	components: {
		'woocommerce/settings-ui-component-test': function SettingsUIComponentTest( props ) {
			return window.wp.element.createElement(
				'label',
				{ 'data-testid': 'settings-ui-registered-component' },
				'Registered settings UI component',
				window.wp.element.createElement( 'input', {
					'aria-label': 'Registered component value',
					onChange: function ( event ) {
						var change = {};
						change[ props.field.id ] = event.target.value;
						props.onChange( change );
					},
					value: String( props.field.getValue( { item: props.data } ) ?? '' ),
				} )
			);
		},
	},
} );
JS
		);

		wp_register_script( self::MISSING_HANDLE, false, array( 'wc-settings-ui' ), '1.0.0', true );
		wp_add_inline_script(
			self::MISSING_HANDLE,
			'window.wcSettingsUIComponentTest = window.wcSettingsUIComponentTest || {}; window.wcSettingsUIComponentTest.missingRegistrationScriptExecuted = true;'
		);
	}

	/**
	 * Register the test settings sections.
	 *
	 * @internal
	 *
	 * @param SettingsSectionRegistry $registry Settings section registry.
	 */
	public static function register_sections( SettingsSectionRegistry $registry ): void {
		$registry->register( self::create_section( 'settings_ui_component_registered', self::REGISTERED_HANDLE ) );
		$registry->register( self::create_section( 'settings_ui_component_missing', self::MISSING_HANDLE ) );
	}

	/**
	 * Create a Settings UI test section after WooCommerce has loaded its settings classes.
	 *
	 * @param string $section_id   Section id.
	 * @param string $script_handle Declared script handle.
	 * @return SettingsSection
	 */
	private static function create_section( string $section_id, string $script_handle ): SettingsSection {
		return new class( $section_id, $script_handle ) extends SettingsSection {
			/**
			 * Section id.
			 *
			 * @var string
			 */
			private string $section_id;

			/**
			 * Declared script handle.
			 *
			 * @var string
			 */
			private string $script_handle;

			/**
			 * Create a test section.
			 *
			 * @param string $section_id   Section id.
			 * @param string $script_handle Declared script handle.
			 */
			public function __construct( string $section_id, string $script_handle ) {
				$this->section_id    = $section_id;
				$this->script_handle = $script_handle;
			}

			/**
			 * Get the parent page id.
			 *
			 * @return string
			 */
			public function get_parent_page_id(): string {
				return 'products';
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
				return 'Settings UI component test';
			}

			/**
			 * Get the section settings.
			 *
			 * @param WC_Settings_Page $parent_page Parent settings page.
			 * @return array
			 */
			public function get_settings( WC_Settings_Page $parent_page ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by the settings section contract.
				return array(
					array(
						'id'    => $this->section_id . '_group',
						'title' => 'Settings UI component test',
						'type'  => 'title',
					),
					array(
						'component' => 'woocommerce/settings-ui-component-test',
						'default'   => 'Initial value',
						'id'        => $this->section_id . '_value',
						'title'     => 'Component value',
						'type'      => 'text',
					),
					array(
						'id'   => $this->section_id . '_group',
						'type' => 'sectionend',
					),
				);
			}

			/**
			 * Get the declared script handles.
			 *
			 * @param WC_Settings_Page $parent_page Parent settings page.
			 * @return string[]
			 */
			public function get_script_handles( WC_Settings_Page $parent_page ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by the settings section contract.
				return array( $this->script_handle );
			}
		};
	}
}

WC_Settings_UI_Component_Registration_Test_Plugin::init();
