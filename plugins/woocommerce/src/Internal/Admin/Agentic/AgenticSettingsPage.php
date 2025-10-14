<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Agentic;

/**
 * AgenticSettingsPage class
 *
 * Adds Agentic Commerce settings to WooCommerce > Settings > Integration.
 * Uses a provider-based system to allow multiple AI agent integrations.
 *
 * @since 10.4.0
 */
class AgenticSettingsPage {

	/**
	 * Registry option name.
	 */
	const REGISTRY_OPTION = 'woocommerce_agentic_agent_registry';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// No hooks needed - used by AgenticCommerceIntegration class.
	}

	/**
	 * Get the agent registry with default values.
	 *
	 * @return array Agent registry.
	 */
	private function get_registry() {
		return get_option( self::REGISTRY_OPTION, array() );
	}

	/**
	 * Get registered providers.
	 *
	 * Each provider should return an array with:
	 * - id: string (unique identifier, e.g., 'openai')
	 * - name: string (display name, e.g., 'OpenAI')
	 * - description: string (optional description)
	 * - fields: array (settings fields configuration)
	 *
	 * @return array Array of registered providers.
	 */
	private function get_providers() {
		$registry = $this->get_registry();

		// Register built-in OpenAI provider.
		$providers = array(
			array(
				'id'          => 'openai',
				'name'        => __( 'ChatGPT', 'woocommerce' ),
				'description' => sprintf(
					/* translators: %s: URL to ChatGPT merchants application page */
					__( 'To get started, <a href="%s" target="_blank">apply to ChatGPT</a>. Once approved, ChatGPT will provide the credentials below.', 'woocommerce' ),
					'https://chatgpt.com/merchants'
				),
				'fields'      => $this->get_openai_fields( $registry['openai'] ?? array() ),
			),
		);

		/**
		 * Filter to register additional AI agent providers.
		 *
		 * Allows extensions to add their own AI agent provider settings.
		 * Each provider should return an array with id, name, description, and fields.
		 *
		 * @since 10.4.0
		 *
		 * @param array $providers Array of provider configurations.
		 * @param array $registry  Current registry data.
		 */
		return apply_filters( 'woocommerce_agentic_commerce_providers', $providers, $registry );
	}

	/**
	 * Get OpenAI provider fields.
	 *
	 * @param array $config Current OpenAI configuration.
	 * @return array Fields configuration.
	 */
	private function get_openai_fields( $config ) {
		return array(
			array(
				'title'    => __( 'Authorization Token', 'woocommerce' ),
				'desc'     => __( 'The bearer token that ChatGPT uses to authenticate API requests. Provided by OpenAI.', 'woocommerce' ),
				'id'       => 'woocommerce_agentic_openai_bearer_token',
				'type'     => 'password',
				'css'      => 'min-width:400px;',
				'default'  => $config['bearer_token'] ?? '',
				'desc_tip' => true,
			),
			array(
				'title'       => __( 'Webhook URL', 'woocommerce' ),
				'desc'        => __( 'The URL where order events will be sent. Provided by OpenAI.', 'woocommerce' ),
				'id'          => 'woocommerce_agentic_openai_webhook_url',
				'type'        => 'text',
				'css'         => 'min-width:400px;',
				'placeholder' => 'https://openai.example.com/agentic_checkout/webhooks/order_events',
				'default'     => $config['webhook_url'] ?? '',
				'desc_tip'    => true,
			),
			array(
				'title'    => __( 'Webhook Secret', 'woocommerce' ),
				'desc'     => __( 'Secret key used to sign outgoing webhook requests. Provided by OpenAI.', 'woocommerce' ),
				'id'       => 'woocommerce_agentic_openai_webhook_secret',
				'type'     => 'password',
				'css'      => 'min-width:400px;',
				'default'  => $config['webhook_secret'] ?? '',
				'desc_tip' => true,
			),
		);
	}

	/**
	 * Get settings for Agentic Commerce integration.
	 *
	 * @param array  $settings Current settings.
	 * @param string $current_section Current section ID.
	 * @return array Settings array.
	 */
	public function get_settings( $settings, $current_section ) {
		if ( 'agentic_commerce' !== $current_section ) {
			return $settings;
		}

		$agentic_settings = array();
		$providers        = $this->get_providers();

		// Build settings for each provider.
		foreach ( $providers as $provider ) {
			// Provider section header.
			$agentic_settings[] = array(
				'title' => $provider['name'],
				'type'  => 'title',
				'desc'  => $provider['description'] ?? '',
				'id'    => 'agentic_commerce_' . $provider['id'] . '_settings',
			);

			// Add provider fields.
			foreach ( $provider['fields'] as $field ) {
				$agentic_settings[] = $field;
			}

			// Provider section end.
			$agentic_settings[] = array(
				'type' => 'sectionend',
				'id'   => 'agentic_commerce_' . $provider['id'] . '_settings',
			);
		}

		return $agentic_settings;
	}

	/**
	 * Save settings to registry structure.
	 */
	public function save_settings() {
		// Verify nonce for security.
		check_admin_referer( 'woocommerce-settings' );

		// Get current registry.
		$registry = $this->get_registry();

		// Update OpenAI settings.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above with check_admin_referer.
		$registry['openai'] = array(
			'bearer_token'     => isset( $_POST['woocommerce_agentic_openai_bearer_token'] )
				? sanitize_text_field( wp_unslash( $_POST['woocommerce_agentic_openai_bearer_token'] ) )
				: '',
			'webhook_url'      => isset( $_POST['woocommerce_agentic_openai_webhook_url'] )
				? esc_url_raw( wp_unslash( $_POST['woocommerce_agentic_openai_webhook_url'] ) )
				: '',
			'webhook_secret'   => isset( $_POST['woocommerce_agentic_openai_webhook_secret'] )
				? sanitize_text_field( wp_unslash( $_POST['woocommerce_agentic_openai_webhook_secret'] ) )
				: '',
			'payment_provider' => isset( $_POST['woocommerce_agentic_openai_payment_provider'] )
				? sanitize_text_field( wp_unslash( $_POST['woocommerce_agentic_openai_payment_provider'] ) )
				: '',
		);

		/**
		 * Filter registry before saving.
		 *
		 * Allows extensions to save their own agent provider settings.
		 * Extensions should inspect $_POST for their settings and add them to the registry.
		 *
		 * @since 10.4.0
		 *
		 * @param array $registry Registry data to save.
		 * @param array $_POST    Posted form data.
		 */
		$registry = apply_filters( 'woocommerce_agentic_commerce_save_settings', $registry, $_POST );

		// Save registry (don't autoload to prevent performance issues).
		update_option( self::REGISTRY_OPTION, $registry, false );
	}
}
