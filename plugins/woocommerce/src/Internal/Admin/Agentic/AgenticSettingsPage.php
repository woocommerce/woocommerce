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
				'fields'      => $this->get_openai_fields(),
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
		 * @internal This filter is experimental and behind a non-visible feature flag. Backwards compatibility not guaranted.
		 *
		 * @param array $providers Array of provider configurations.
		 * @param array $registry  Current registry data.
		 */
		$providers = apply_filters( 'woocommerce_agentic_commerce_providers', $providers, $registry );

		// Validate provider structure.
		$validated = array();
		foreach ( $providers as $provider ) {
			if (
				! is_array( $provider )
				|| empty( $provider['id'] )
				|| empty( $provider['name'] )
				|| ! is_array( $provider['fields'] ?? null )
			) {
				continue;
			}

			// Sanitize text fields.
			$provider['id']   = sanitize_key( $provider['id'] );
			$provider['name'] = sanitize_text_field( $provider['name'] );
			if ( ! empty( $provider['description'] ) ) {
				$provider['description'] = wp_kses_post( $provider['description'] );
			}

			$validated[] = $provider;
		}

		return $validated;
	}

	/**
	 * Get general Agentic Commerce settings.
	 *
	 * @param array $config Current general configuration.
	 * @return array Settings fields.
	 */
	private function get_general_settings( $config ) {
		return array(
			array(
				'title' => __( 'Agentic commerce', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'agentic_commerce_general_settings',
			),
			array(
				'title'   => __( 'Enable product visibility', 'woocommerce' ),
				'desc'    => __( 'Allow products to be visible by default to the AI agents you integrate with. Can be overridden per product.', 'woocommerce' ),
				'id'      => 'woocommerce_agentic_enable_products_default',
				'type'    => 'checkbox',
				'default' => ( ! empty( $config['enable_products_default'] ) && 'yes' === $config['enable_products_default'] ) ? 'yes' : 'no',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'agentic_commerce_general_settings',
			),
		);
	}

	/**
	 * Get store policies settings.
	 *
	 * @return array Settings fields.
	 */
	private function get_store_policies_settings() {
		// Get URLs from WooCommerce/WordPress settings.
		$terms_page_id   = wc_terms_and_conditions_page_id();
		$privacy_page_id = get_option( 'wp_page_for_privacy_policy' );

		$terms_url   = $terms_page_id ? get_permalink( $terms_page_id ) : '';
		$privacy_url = $privacy_page_id ? get_permalink( $privacy_page_id ) : '';

		// Build admin URLs for configuration links.
		$advanced_settings_url = admin_url( 'admin.php?page=wc-settings&tab=advanced' );
		$privacy_settings_url  = admin_url( 'options-privacy.php' );

		return array(
			array(
				'title' => __( 'Store policies', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'agentic_commerce_store_policies',
			),
			array(
				'title'             => __( 'Privacy Policy URL', 'woocommerce' ),
				'desc'              => sprintf(
					/* translators: %s: URL to WordPress privacy settings */
					__( 'Configure your Privacy Policy page in <a href="%s">Settings &gt; Privacy</a>.', 'woocommerce' ),
					esc_url( $privacy_settings_url )
				),
				'id'                => 'woocommerce_agentic_privacy_url_display',
				'type'              => 'text',
				'default'           => esc_url( $privacy_url ),
				'custom_attributes' => array(
					'disabled' => 'disabled',
					'readonly' => 'readonly',
				),
			),
			array(
				'title'             => __( 'Terms and Conditions URL', 'woocommerce' ),
				'desc'              => sprintf(
					/* translators: %s: URL to WooCommerce advanced settings */
					__( 'Configure your Terms and Conditions page in <a href="%s">WooCommerce &gt; Settings &gt; Advanced &gt; Page setup</a>.', 'woocommerce' ),
					esc_url( $advanced_settings_url )
				),
				'id'                => 'woocommerce_agentic_terms_url_display',
				'type'              => 'text',
				'default'           => esc_url( $terms_url ),
				'custom_attributes' => array(
					'disabled' => 'disabled',
					'readonly' => 'readonly',
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'agentic_commerce_store_policies',
			),
		);
	}

	/**
	 * Get OpenAI provider fields.
	 *
	 * @return array Fields configuration.
	 */
	private function get_openai_fields() {
		return array(
			array(
				'title'   => __( 'Authorization Token', 'woocommerce' ),
				'desc'    => __( 'The bearer token that ChatGPT uses to authenticate checkout requests.', 'woocommerce' ),
				'id'      => 'woocommerce_agentic_openai_bearer_token',
				'type'    => 'password',
				'default' => '',
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
		$registry         = $this->get_registry();

		// Add general Agentic Commerce settings section.
		$agentic_settings = array_merge( $agentic_settings, $this->get_general_settings( $registry['general'] ?? array() ) );

		// Build settings for each provider.
		$providers = $this->get_providers();
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

		// Add store policies section.
		$agentic_settings = array_merge( $agentic_settings, $this->get_store_policies_settings() );

		return $agentic_settings;
	}

	/**
	 * Save settings to registry structure.
	 */
	public function save_settings() {
		check_admin_referer( 'woocommerce-settings' );

		$registry = $this->get_registry();

		// Update general settings.
		$registry['general'] = array(
			'enable_products_default' => isset( $_POST['woocommerce_agentic_enable_products_default'] ) && '1' === $_POST['woocommerce_agentic_enable_products_default']
				? 'yes'
				: 'no',
		);

		// Update OpenAI settings.
		$new_token = isset( $_POST['woocommerce_agentic_openai_bearer_token'] )
			? sanitize_text_field( wp_unslash( $_POST['woocommerce_agentic_openai_bearer_token'] ) )
			: '';

		// Only update if a new token was provided; otherwise keep existing.
		if ( ! empty( $new_token ) ) {
			$registry['openai']['bearer_token'] = wp_hash_password( $new_token );
		} elseif ( ! isset( $registry['openai']['bearer_token'] ) ) {
			$registry['openai']['bearer_token'] = '';
		}

		/**
		 * Filter registry before saving.
		 *
		 * Allows extensions to save their own agent provider settings.
		 * Extensions can access $_POST directly for their settings but MUST sanitize all input
		 * using appropriate WordPress sanitization functions (sanitize_text_field, esc_url_raw, etc.)
		 * and call wp_unslash() on POST data.
		 *
		 * @since 10.4.0
		 *
		 * @internal This filter is experimental and behind a non-visible feature flag. Backwards compatibility not guaranted.
		 *
		 * @param array $registry Registry data to save. Extensions should add their provider settings to this array.
		 */
		$registry = apply_filters( 'woocommerce_agentic_commerce_save_settings', $registry );

		// Save registry (don't autoload to prevent performance issues).
		update_option( self::REGISTRY_OPTION, $registry, false );
	}
}
