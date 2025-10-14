<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Agentic;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\OrderMetaKey;
use WC_Order;
use WC_Webhook;

/**
 * AgenticWebhookManager class
 *
 * Integrates Agentic Commerce Protocol webhooks with WooCommerce's native webhook system.
 * Defines custom action topics and handles filtering/transformation for ACP compliance.
 *
 * @since 10.3.0
 */
class AgenticWebhookManager implements RegisterHooksInterface {
	/**
	 * Action that will be triggered for webhooks.
	 *
	 * @var string
	 */
	const WEBHOOK_ACTION = 'woocommerce_agentic_order_changed';

	/**
	 * Topic that will be used for webhooks.
	 *
	 * @var string
	 */
	const WEBHOOK_TOPIC = 'action.' . self::WEBHOOK_ACTION;

	/**
	 * Payload builder instance.
	 *
	 * @var AgenticWebhookPayloadBuilder
	 */
	private $payload_builder;

	/**
	 * Initializes dependencies and hooks.
	 *
	 * @internal
	 *
	 * @param AgenticWebhookPayloadBuilder $payload_builder Payload builder instance.
	 */
	final public function init( AgenticWebhookPayloadBuilder $payload_builder ) {
		$this->payload_builder = $payload_builder;
	}

	/**
	 * Initialize hooks for webhook integration.
	 *
	 *  @internal
	 */
	public function register() {
		add_action( 'woocommerce_init', array( $this, 'create_webhook' ) );

		add_filter( 'woocommerce_webhook_topics', array( $this, 'register_webhook_topic_names' ) );

		// Hook into order lifecycle events to fire our custom actions.
		add_action( 'woocommerce_new_order', array( $this, 'handle_order_created' ), 999, 2 ); // Hook late to give a chance for other plugins to modify.
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_changed' ), 10, 4 );
		add_action( 'woocommerce_order_refunded', array( $this, 'handle_order_refunded' ), 10, 1 );

		// Customize webhook payload for our topics.
		add_filter( 'woocommerce_webhook_payload', array( $this, 'customize_webhook_payload' ), 10, 4 );

		// Customize webhook HTTP arguments for our topics.
		add_filter( 'woocommerce_webhook_http_args', array( $this, 'customize_webhook_http_args' ), 10, 3 );
	}

	/**
	 * Create the webhook for Agentic Commerce Protocol.
	 *
	 * @return void
	 */
	public function create_webhook(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$name_prefix  = 'ACP'; // Not translated on purpose, to make search more specific.
		$data_store   = \WC_Data_Store::load( 'webhook' );
		$webhooks_ids = $data_store->search_webhooks(
			array(
				'search' => $name_prefix,
				'status' => 'active',
				'limit'  => -1,
			)
		);

		foreach ( $webhooks_ids as $webhook_id ) {
			$webhook = wc_get_webhook( $webhook_id );
			if ( self::WEBHOOK_TOPIC === $webhook->get_topic() ) {
				// There is a correct webhook already.
				return;
			}
		}

		/**
		 * Filter the delivery URL for Agentic webhooks.
		 *
		 * @since 10.3.0
		 *
		 * @param string $delivery_url Delivery URL.
		 */
		$delivery_url = apply_filters( 'woocommerce_agentic_webhook_delivery_url', 'https://acp.invalid' );

		// Include the non-translated prefix (ACP) to allow searching for the webhook by name.
		$name = sprintf(
			// translators: %s: webhook name prefix (ACP).
			__( '%s: Order Created or Updated', 'woocommerce' ),
			$name_prefix
		);

		$webhook = new \WC_Webhook();
		$webhook->set_name( $name );
		$webhook->set_user_id( get_current_user_id() );
		$webhook->set_topic( self::WEBHOOK_TOPIC );
		$webhook->set_secret( wp_generate_password( 50, false ) ); // This will be ignored, but is required.
		$webhook->set_delivery_url( $delivery_url );
		$webhook->set_status( 'active' );
		$webhook->save();
	}

	/**
	 * Register webhook topic names for display in the UI.
	 *
	 * @param array $topics Existing topics.
	 * @return array Modified topics.
	 */
	public function register_webhook_topic_names( $topics ): array {
		$topics[ self::WEBHOOK_TOPIC ] = __( 'Agentic Commerce Protocol: Order created or updated', 'woocommerce' );
		return $topics;
	}

	/**
	 * Handle order creation.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order    Order object.
	 */
	public function handle_order_created( $order_id, $order ) {
		if ( ! $this->should_trigger_webhook( $order ) ) {
			return;
		}

		/**
		 * Fires when an Agentic order is updated or created.
		 *
		 * @since 10.3.0
		 *
		 * @param int      $order_id Order ID.
		 * @param WC_Order $order    Order object.
		 */
		do_action( self::WEBHOOK_ACTION, $order_id, $order );
	}

	/**
	 * Handle order status changes.
	 *
	 * @param int      $order_id   Order ID.
	 * @param string   $old_status Old status.
	 * @param string   $new_status New status.
	 * @param WC_Order $order      Order object.
	 */
	public function handle_order_status_changed( $order_id, $old_status, $new_status, $order ) {
		if ( ! $this->should_trigger_webhook( $order ) ) {
			return;
		}

		/**
		 * Fires when an Agentic order status changes.
		 *
		 * @since 10.3.0
		 *
		 * @param int      $order_id Order ID.
		 * @param WC_Order $order    Order object.
		 */
		do_action( self::WEBHOOK_ACTION, $order_id, $order );
	}

	/**
	 * Handle order refunds.
	 *
	 * @param int $order_id  Order ID.
	 */
	public function handle_order_refunded( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $this->should_trigger_webhook( $order ) ) {
			return;
		}

		/**
		 * Fires when an Agentic order is refunded.
		 *
		 * @since 10.3.0
		 *
		 * @param int      $order_id Order ID.
		 * @param WC_Order $order    Order object.
		 */
		do_action( self::WEBHOOK_ACTION, $order_id, $order, 'order_update' );
	}

	/**
	 * Check if webhook should be triggered for this order.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool True if webhook should be triggered.
	 */
	private function should_trigger_webhook( $order ) {
		// Only trigger for orders with an Agentic checkout session ID.
		$checkout_session_id = $order->get_meta( OrderMetaKey::AGENTIC_CHECKOUT_SESSION_ID );
		if ( empty( $checkout_session_id ) ) {
			return false;
		}

		// Don't trigger for draft orders.
		if (
			in_array(
				$order->get_status(),
				array(
					OrderStatus::CHECKOUT_DRAFT,
					OrderStatus::DRAFT,
					OrderStatus::AUTO_DRAFT,
				),
				true
			)
		) {
			return false;
		}

		return true;
	}

	/**
	 * Customize webhook payload for Agentic topics.
	 *
	 * @param array  $payload        Original payload.
	 * @param string $resource_type  Resource type.
	 * @param int    $resource_id    Resource ID.
	 * @param int    $webhook_id     Webhook ID.
	 * @return array Modified payload.
	 */
	public function customize_webhook_payload( $payload, $resource_type, $resource_id, $webhook_id ) {
		$webhook = wc_get_webhook( $webhook_id );
		if ( ! $webhook ) {
			return $payload;
		}

		$topic = $webhook->get_topic();

		// Check if this is one of our Agentic topics.
		if ( self::WEBHOOK_TOPIC !== $topic ) {
			return $payload;
		}

		// Get the order.
		$order = wc_get_order( $resource_id );
		if ( ! $order ) {
			return $payload;
		}

		// The meta key is not used elsewhere, so it is not stored in a constant.
		$meta_key       = '_acp_order_created_sent';
		$is_first_event = 'sent' !== $order->get_meta( $meta_key );
		if ( $is_first_event ) {
			$event = 'order_create';
			$order->update_meta_data( $meta_key, 'sent' );
			$order->save();
		} else {
			$event = 'order_update';
		}

		// Build ACP-compliant payload.
		return $this->payload_builder->build_payload( $event, $order );
	}

	/**
	 * Customize webhook HTTP arguments for Agentic topics.
	 *
	 * @param array $http_args  HTTP arguments.
	 * @param mixed $arg        First hook argument.
	 * @param int   $webhook_id Webhook ID.
	 * @return array Modified HTTP arguments.
	 */
	public function customize_webhook_http_args( $http_args, $arg, $webhook_id ) {
		$webhook = wc_get_webhook( $webhook_id );
		if ( ! $webhook ) {
			return $http_args;
		}

		$topic = $webhook->get_topic();

		// Check if this is one of our Agentic topics.
		if ( self::WEBHOOK_TOPIC !== $topic ) {
			return $http_args;
		}

		// Compute HMAC signature per ACP webhook spec using WooCommerce's built-in method.
		// The signature must be computed over the raw request body.
		if ( isset( $http_args['body'] ) && ! empty( $webhook->get_secret() ) ) {
			// Use WooCommerce's signature generation to ensure consistency.
			$signature = $webhook->generate_signature( $http_args['body'] );

			// Add Merchant-Signature header per ACP webhook specification.
			$http_args['headers']['Merchant-Signature'] = $signature;
		}

		return $http_args;
	}
}
