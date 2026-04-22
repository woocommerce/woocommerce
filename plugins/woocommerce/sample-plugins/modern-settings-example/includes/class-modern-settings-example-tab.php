<?php
/**
 * Modern Settings Example tab.
 *
 * @package ModernSettingsExample
 */

declare( strict_types=1 );

namespace Modern_Settings_Example;

use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPageInterface;
use WC_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Modern Example" tab under WooCommerce → Settings.
 *
 * The tab uses only field types that the React renderer supports natively, so no
 * accompanying JavaScript bundle is required.
 *
 * With the `modern-settings` feature flag on, this tab renders via the React
 * `DataForm` path. With the flag off, it renders via the legacy
 * `WC_Admin_Settings::output_fields()` path with no behavioural change.
 */
class Modern_Settings_Example_Tab extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'modern_example';
		$this->label = __( 'Modern Example', 'modern-settings-example' );

		parent::__construct();
	}

	/**
	 * Get settings for the default section.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section(): array {
		return array(
			array(
				'title' => __( 'Modern settings example', 'modern-settings-example' ),
				'type'  => 'title',
				'desc'  => __( 'A minimal demonstration of the modernised settings SDK. All fields here use natively-supported field types.', 'modern-settings-example' ),
				'id'    => 'mse_general_options',
			),
			array(
				'title'    => __( 'Display name', 'modern-settings-example' ),
				'desc'     => __( 'A short label shown next to your example feature.', 'modern-settings-example' ),
				'id'       => 'mse_display_name',
				'type'     => 'text',
				'default'  => __( 'Modern Example', 'modern-settings-example' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Theme', 'modern-settings-example' ),
				'desc'     => __( 'Choose how the example feature is presented.', 'modern-settings-example' ),
				'id'       => 'mse_theme',
				'type'     => 'select',
				'default'  => 'auto',
				'options'  => array(
					'auto'  => __( 'Match site theme', 'modern-settings-example' ),
					'light' => __( 'Light', 'modern-settings-example' ),
					'dark'  => __( 'Dark', 'modern-settings-example' ),
				),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Enable example', 'modern-settings-example' ),
				'desc'     => __( 'Turn the example feature on or off.', 'modern-settings-example' ),
				'id'       => 'mse_enabled',
				'type'     => 'toggle',
				'default'  => 'no',
				'desc_tip' => false,
			),
			array(
				'title'         => __( 'Show in admin bar', 'modern-settings-example' ),
				'desc'          => __( 'Add a quick-access entry for the example feature to the WordPress admin bar.', 'modern-settings-example' ),
				'id'            => 'mse_show_in_admin_bar',
				'type'          => 'checkbox',
				'default'       => 'no',
				'checkboxgroup' => 'start',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'mse_general_options',
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_react_settings_page(): ?ReactSettingsPageInterface {
		require_once __DIR__ . '/class-modern-settings-example-react-page.php';
		return new \Modern_Settings_Example\Modern_Settings_Example_React_Page();
	}
}
