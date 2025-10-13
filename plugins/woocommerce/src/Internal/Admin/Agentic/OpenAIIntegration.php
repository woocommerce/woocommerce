<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Agentic;

/**
 * OpenAI Integration class
 *
 * Registers OpenAI as a WooCommerce integration for Agentic Commerce.
 *
 * @since 10.4.0
 */
class OpenAIIntegration extends \WC_Integration {

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
		$this->id                 = 'openai';
		$this->method_title       = __( 'OpenAI', 'woocommerce' );
		$this->method_description = __( 'Configure settings to allow ChatGPT to purchase from your store.', 'woocommerce' );

		// Initialize settings page helper.
		$this->settings_page = new AgenticSettingsPage();
	}

	/**
	 * Admin options output.
	 */
	public function admin_options() {
		echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
		echo wp_kses_post( wpautop( $this->get_method_description() ) );
		echo '<div><input type="hidden" name="section" value="' . esc_attr( $this->id ) . '" /></div>';

		// Get settings from AgenticSettingsPage.
		$settings = $this->settings_page->get_settings( array(), 'agentic_commerce' );
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
