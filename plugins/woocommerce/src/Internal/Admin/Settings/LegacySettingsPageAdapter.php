<?php
/**
 * Legacy WC_Settings_Page adapter for modern settings.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Settings\ModernSettingsPageInterface as PublicModernSettingsPageInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Adapts a WC_Settings_Page instance into the modern settings page contract.
 *
 * Internal implementation of the legacy settings adapter. Extensions should use
 * Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter.
 *
 * @since 10.8.0
 */
class LegacySettingsPageAdapter implements PublicModernSettingsPageInterface {

	/**
	 * Legacy settings page.
	 *
	 * @var \WC_Settings_Page
	 */
	protected \WC_Settings_Page $settings_page;

	/**
	 * Constructor.
	 *
	 * @since 10.8.0
	 *
	 * @param \WC_Settings_Page $settings_page Legacy settings page.
	 */
	public function __construct( \WC_Settings_Page $settings_page ) {
		$this->settings_page = $settings_page;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_page_id(): string {
		return $this->settings_page->get_id();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_schema( string $section ): array {
		return ModernSettingsSchema::from_legacy_settings(
			$this->settings_page->get_id(),
			$section,
			$this->settings_page->get_label(),
			$this->settings_page->get_settings( $section ),
			$this->get_save_adapter( $section )
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_script_handles( string $section ): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_save_adapter( string $section ): string {
		return 'form_post';
	}
}
