<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Agentic;

/**
 * AgenticWebhookSettings class
 *
 * Manages settings for Agentic Commerce Protocol feature enablement.
 * The actual webhooks are managed through WooCommerce's native webhook system.
 *
 * @since 9.6.0
 */
class AgenticWebhookSettings {
	/**
	 * Option name for storing whether ACP webhooks are enabled.
	 */
	const ENABLED_OPTION = 'woocommerce_agentic_webhooks_enabled';

	/**
	 * Check if Agentic webhooks are enabled.
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	public static function is_enabled() {
		return (bool) get_option( self::ENABLED_OPTION, false );
	}

	/**
	 * Enable Agentic webhooks.
	 */
	public static function enable() {
		update_option( self::ENABLED_OPTION, true );
	}

	/**
	 * Disable Agentic webhooks.
	 */
	public static function disable() {
		update_option( self::ENABLED_OPTION, false );
	}

	/**
	 * Get instructions for setting up Agentic webhooks.
	 *
	 * @return array Setup instructions.
	 */
	public static function get_setup_instructions() {
		return array(
			'title' => __( 'Agentic Commerce Protocol Webhook Setup', 'woocommerce' ),
			'steps' => array(
				__( '1. Navigate to WooCommerce → Settings → Advanced → Webhooks', 'woocommerce' ),
				__( '2. Click "Add webhook" button', 'woocommerce' ),
				__( '3. Set Name to something descriptive (e.g., "ChatGPT Order Created")', 'woocommerce' ),
				__( '4. Set Status to "Active"', 'woocommerce' ),
				__( '5. Set Topic to "Action Event" and enter one of:', 'woocommerce' ),
				__( '   - woocommerce_agentic_order_created (for new orders)', 'woocommerce' ),
				__( '   - woocommerce_agentic_order_updated (for order updates)', 'woocommerce' ),
				__( '6. Set Delivery URL to your AI agent endpoint (base URL only)', 'woocommerce' ),
				__( '7. Set Secret to a secure key (min 16 characters)', 'woocommerce' ),
				__( '8. Set API Version to "WP REST API Integration v3"', 'woocommerce' ),
				__( '9. Click "Save webhook"', 'woocommerce' ),
			),
			'notes' => array(
				__( 'The webhook will automatically append /agentic_checkout/webhooks/order_events to your URL.', 'woocommerce' ),
				__( 'Only orders created through the Agentic Checkout API will trigger these webhooks.', 'woocommerce' ),
				__( 'The payload format follows the Agentic Commerce Protocol specification.', 'woocommerce' ),
				__( 'The signature will be sent in the Merchant-Signature header.', 'woocommerce' ),
			),
		);
	}

	/**
	 * Create webhooks programmatically for Agentic Commerce.
	 *
	 * @param string $base_url Base URL for the webhook endpoint.
	 * @param string $secret   Secret key for webhook signing.
	 * @return array Array of created webhook IDs or error messages.
	 */
	public static function create_webhooks( $base_url, $secret ) {
		// Validate inputs.
		if ( empty( $base_url ) || empty( $secret ) ) {
			return array( 'error' => __( 'Base URL and secret are required.', 'woocommerce' ) );
		}

		// Validate URL format.
		if ( ! filter_var( $base_url, FILTER_VALIDATE_URL ) ) {
			return array( 'error' => __( 'Invalid URL format.', 'woocommerce' ) );
		}

		// Validate secret length.
		if ( strlen( $secret ) < 16 ) {
			return array( 'error' => __( 'Secret must be at least 16 characters.', 'woocommerce' ) );
		}

		$results = array();

		// Create webhook for order created.
		$webhook_created = new \WC_Webhook();
		$webhook_created->set_name( __( 'Agentic Order Created', 'woocommerce' ) );
		$webhook_created->set_status( 'active' );
		$webhook_created->set_topic( 'action.woocommerce_agentic_order_created' );
		$webhook_created->set_delivery_url( $base_url );
		$webhook_created->set_secret( $secret );
		$webhook_created->set_api_version( 3 );
		$webhook_created->set_user_id( get_current_user_id() );
		$webhook_created->save();

		if ( $webhook_created->get_id() ) {
			$results['created'] = $webhook_created->get_id();
		}

		// Create webhook for order updated.
		$webhook_updated = new \WC_Webhook();
		$webhook_updated->set_name( __( 'Agentic Order Updated', 'woocommerce' ) );
		$webhook_updated->set_status( 'active' );
		$webhook_updated->set_topic( 'action.woocommerce_agentic_order_updated' );
		$webhook_updated->set_delivery_url( $base_url );
		$webhook_updated->set_secret( $secret );
		$webhook_updated->set_api_version( 3 );
		$webhook_updated->set_user_id( get_current_user_id() );
		$webhook_updated->save();

		if ( $webhook_updated->get_id() ) {
			$results['updated'] = $webhook_updated->get_id();
		}

		return $results;
	}

	/**
	 * Get all Agentic webhooks.
	 *
	 * @return array Array of webhook objects.
	 */
	public static function get_agentic_webhooks() {
		$data_store = \WC_Data_Store::load( 'webhook' );

		// Search for all active webhooks.
		$webhook_ids = $data_store->search_webhooks(
			array(
				'limit'  => -1,
				'status' => 'active',
			)
		);

		$agentic_webhooks = array();

		foreach ( $webhook_ids as $webhook_id ) {
			$webhook = new \WC_Webhook( $webhook_id );
			$topic   = $webhook->get_topic();

			// Check if this is an Agentic webhook.
			if ( in_array(
				$topic,
				array(
					'action.woocommerce_agentic_order_created',
					'action.woocommerce_agentic_order_updated',
				),
				true
			) ) {
				$agentic_webhooks[] = $webhook;
			}
		}

		return $agentic_webhooks;
	}
}
