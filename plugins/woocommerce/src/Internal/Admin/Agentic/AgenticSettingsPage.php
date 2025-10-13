<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Agentic;

/**
 * AgenticSettingsPage class
 *
 * Adds Agentic Commerce settings to WooCommerce > Settings > Integration.
 * Currently displays settings for OpenAI agent only (using registry structure).
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
		// No hooks needed - used by OpenAIIntegration class.
	}

	/**
	 * Get the agent registry with default values.
	 *
	 * @return array Agent registry.
	 */
	private function get_registry() {
		return get_option( self::REGISTRY_OPTION, array( 'openai' => array() ) );
	}

	/**
	 * Get settings for OpenAI integration.
	 *
	 * @param array  $settings Current settings.
	 * @param string $current_section Current section ID.
	 * @return array Settings array.
	 */
	public function get_settings( $settings, $current_section ) {
		if ( 'agentic_commerce' !== $current_section ) {
			return $settings;
		}

		// Get current registry.
		$registry      = $this->get_registry();
		$openai_config = $registry['openai'] ?? array();

		$agentic_settings = array(
			array(
				'title' => __( 'OpenAI Integration', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Configure settings to allow ChatGPT to purchase from your store.', 'woocommerce' ),
				'id'    => 'agentic_commerce_openai_settings',
			),

			array(
				'title'    => __( 'Authorization Token', 'woocommerce' ),
				'desc'     => __( 'The bearer token that ChatGPT uses to authenticate API requests. Provided by OpenAI.', 'woocommerce' ),
				'id'       => 'woocommerce_agentic_openai_bearer_token',
				'type'     => 'password',
				'css'      => 'min-width:400px;',
				'default'  => $openai_config['bearer_token'] ?? '',
				'desc_tip' => true,
			),

			array(
				'title'       => __( 'Webhook URL', 'woocommerce' ),
				'desc'        => __( 'The URL where order events will be sent. Provided by OpenAI.', 'woocommerce' ),
				'id'          => 'woocommerce_agentic_openai_webhook_url',
				'type'        => 'text',
				'css'         => 'min-width:400px;',
				'placeholder' => 'https://api.openai.com/webhooks/merchants/...',
				'default'     => $openai_config['webhook_url'] ?? '',
				'desc_tip'    => true,
			),

			array(
				'title'    => __( 'Webhook Secret', 'woocommerce' ),
				'desc'     => __( 'Secret key used to sign outgoing webhook requests. Provided by OpenAI.', 'woocommerce' ),
				'id'       => 'woocommerce_agentic_openai_webhook_secret',
				'type'     => 'password',
				'css'      => 'min-width:400px;',
				'default'  => $openai_config['webhook_secret'] ?? '',
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Payment Provider', 'woocommerce' ),
				'desc'     => $this->get_payment_provider_description(),
				'id'       => 'woocommerce_agentic_openai_payment_provider',
				'type'     => 'select',
				'options'  => $this->get_payment_provider_options(),
				'default'  => $openai_config['payment_provider'] ?? '',
				'desc_tip' => false,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'agentic_commerce_openai_settings',
			),

			// Information section.
			array(
				'title' => __( 'Integration Instructions', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => $this->get_integration_instructions(),
				'id'    => 'agentic_commerce_instructions',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'agentic_commerce_instructions',
			),
		);

		/**
		 * Filter agentic commerce settings.
		 *
		 * Allows extensions to add their own agent settings.
		 *
		 * @since 10.4.0
		 *
		 * @param array $agentic_settings Settings array.
		 * @param array $registry         Full registry data.
		 */
		return apply_filters( 'woocommerce_agentic_commerce_settings', $agentic_settings, $registry );
	}

	/**
	 * Save settings to registry structure.
	 */
	public function save_settings() {
		// Get current registry.
		$registry = $this->get_registry();

		// Update OpenAI settings.
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
		 * Allows extensions to save their own agent settings.
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

	/**
	 * Get payment provider options.
	 *
	 * @return array Payment provider options.
	 */
	private function get_payment_provider_options() {
		$options = array( '' => __( '-- Select a payment provider --', 'woocommerce' ) );

		$gateways = WC()->payment_gateways()->get_available_payment_gateways();

		foreach ( $gateways as $gateway ) {
			// Check if gateway supports agentic commerce.
			if ( $gateway->supports( \Automattic\WooCommerce\Enums\PaymentGatewayFeature::AGENTIC_COMMERCE ) ) {
				$options[ $gateway->id ] = $gateway->get_title();
			}
		}

		// If no gateways support it, show helpful message.
		if ( count( $options ) === 1 ) {
			$options = array(
				'' => __( 'No compatible payment providers found', 'woocommerce' ),
			);
		}

		return $options;
	}

	/**
	 * Get payment provider field description.
	 *
	 * @return string Description HTML.
	 */
	private function get_payment_provider_description() {
		$gateways       = WC()->payment_gateways()->get_available_payment_gateways();
		$has_compatible = false;

		foreach ( $gateways as $gateway ) {
			if ( $gateway->supports( \Automattic\WooCommerce\Enums\PaymentGatewayFeature::AGENTIC_COMMERCE ) ) {
				$has_compatible = true;
				break;
			}
		}

		if ( ! $has_compatible ) {
			return sprintf(
				'<span class="description" style="color: #d63638;">%s <a href="%s" target="_blank">%s</a> | <a href="%s" target="_blank">%s</a></span>',
				esc_html__( 'No compatible payment providers found. Please install:', 'woocommerce' ),
				'https://woocommerce.com/products/woocommerce-gateway-stripe/',
				esc_html__( 'Stripe for WooCommerce', 'woocommerce' ),
				'https://woocommerce.com/products/woocommerce-payments/',
				esc_html__( 'WooPayments', 'woocommerce' )
			);
		}

		return __( 'The payment gateway used to process agentic commerce transactions.', 'woocommerce' );
	}

	/**
	 * Get integration instructions HTML.
	 *
	 * @return string HTML instructions.
	 */
	private function get_integration_instructions() {
		$store_url = rest_url( 'wc/store/v1' );

		ob_start();
		?>
		<div class="wc-agentic-instructions">
			<h4><?php esc_html_e( 'How to integrate with ChatGPT:', 'woocommerce' ); ?></h4>
			<ol>
				<li>
					<?php esc_html_e( 'Sign up for OpenAI\'s Agentic Commerce program:', 'woocommerce' ); ?>
					<br><a href="https://platform.openai.com/docs/commerce" target="_blank">https://platform.openai.com/docs/commerce</a>
				</li>
				<li>
					<?php esc_html_e( 'OpenAI will provide you with:', 'woocommerce' ); ?>
					<ul style="margin-top: 5px;">
						<li><?php esc_html_e( 'Authorization token', 'woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Webhook URL', 'woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Webhook secret', 'woocommerce' ); ?></li>
					</ul>
				</li>
				<li>
					<?php esc_html_e( 'Enter those credentials in the fields above and save.', 'woocommerce' ); ?>
				</li>
				<li>
					<?php esc_html_e( 'Provide OpenAI with your Store API URL:', 'woocommerce' ); ?>
					<br><code style="background: #fff; padding: 4px 8px; border: 1px solid #ddd; border-radius: 3px;"><?php echo esc_html( $store_url ); ?></code>
					<button type="button" class="button button-secondary" style="margin-left: 8px;" onclick="navigator.clipboard.writeText('<?php echo esc_js( $store_url ); ?>'); this.textContent='<?php esc_attr_e( 'Copied!', 'woocommerce' ); ?>';">
						<?php esc_html_e( 'Copy', 'woocommerce' ); ?>
					</button>
				</li>
				<li>
					<?php esc_html_e( 'Ensure your selected payment provider is properly configured and enabled.', 'woocommerce' ); ?>
				</li>
			</ol>

			<p style="margin-top: 15px;">
				<strong><?php esc_html_e( 'Note:', 'woocommerce' ); ?></strong>
				<?php esc_html_e( 'This feature is currently in beta and requires approval from OpenAI to participate.', 'woocommerce' ); ?>
			</p>
		</div>

		<style>
			.wc-agentic-instructions {
				background: #f0f6fc;
				border: 1px solid #c3d9ed;
				border-left: 4px solid #2271b1;
				padding: 15px;
				margin-top: 10px;
			}
			.wc-agentic-instructions h4 {
				margin-top: 0;
				color: #1d2327;
			}
			.wc-agentic-instructions ol {
				margin: 10px 0 10px 20px;
			}
			.wc-agentic-instructions li {
				margin-bottom: 12px;
			}
			.wc-agentic-instructions ul {
				list-style: disc;
				margin-left: 20px;
			}
			.wc-agentic-instructions a {
				color: #2271b1;
			}
		</style>
		<?php
		return ob_get_clean();
	}
}
