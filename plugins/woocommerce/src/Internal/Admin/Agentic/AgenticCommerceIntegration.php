<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Agentic;

/**
 * Agentic Commerce Integration class
 *
 * Registers the Agentic Commerce Protocol as a WooCommerce integration.
 * Manages settings for various AI agent providers (OpenAI, Anthropic, etc.)
 *
 * @since 10.4.0
 */
class AgenticCommerceIntegration extends \WC_Integration {

	/**
	 * Settings page instance.
	 *
	 * @var AgenticSettingsPage
	 */
	private $settings_page;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'agentic_commerce';
		$this->method_title       = __( 'Agentic Commerce', 'woocommerce' );
		$this->method_description = __( 'Configure settings to allow AI agents to purchase from your store.', 'woocommerce' );

		// Initialize settings page helper.
		$this->settings_page = new AgenticSettingsPage();

		// Bind to the save action for the settings.
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Admin options output.
	 */
	public function admin_options() {
		$settings = $this->settings_page->get_settings( array(), $this->id );
		\WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Process and save options.
	 */
	public function process_admin_options() {
		// Let AgenticSettingsPage handle saving.
		$this->settings_page->save_settings();
	}
}
