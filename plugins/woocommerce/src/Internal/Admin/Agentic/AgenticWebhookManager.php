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
	 * Meta key to store if the first event has been delivered.
	 *
	 * @var string
	 */
	const FIRST_EVENT_DELIVERED_META_KEY = '_acp_order_created_sent';

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

		add_filter( 'woocommerce_webhook_topics', array( $this, 'register_webhook_topic_names' ) );

		// Hook into order lifecycle events to fire our custom actions.
		add_action( 'woocommerce_new_order', array( $this, 'handle_order_created' ), 999, 2 ); // Hook late to give a chance for other plugins to modify.
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_changed' ), 10, 4 );
		add_action( 'woocommerce_order_refunded', array( $this, 'handle_order_refunded' ), 10, 1 );

		// Customize webhook payload for our topics.
		add_filter( 'woocommerce_webhook_payload', array( $this, 'customize_webhook_payload' ), 10, 4 );

		// Customize webhook HTTP arguments for our topics.
		add_filter( 'woocommerce_webhook_http_args', array( $this, 'customize_webhook_http_args' ), 10, 3 );

		// When the webhook is delivered (or not), mark the first event as delivered.
		add_action( 'woocommerce_webhook_delivery', array( $this, 'mark_first_event_delivered' ), 10, 5 );
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
		do_action( self::WEBHOOK_ACTION, $order_id, $order );
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

		$is_first_event = 'sent' !== $order->get_meta( self::FIRST_EVENT_DELIVERED_META_KEY );
		$event          = $is_first_event ? 'order_create' : 'order_update';

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

	/**
	 * Mark first event as delivered on successful webhook delivery.
	 *
	 * @param array $http_args   HTTP request args.
	 * @param mixed $response    HTTP response.
	 * @param float $duration    Request duration.
	 * @param int   $arg         First argument to the action (order_id).
	 * @param int   $webhook_id  Webhook ID.
	 */
	public function mark_first_event_delivered( $http_args, $response, $duration, $arg, $webhook_id ) {
		// Only proceed for successful responses.
		if ( is_wp_error( $response ) ) {
			return;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return;
		}

		// Verify this is our webhook topic.
		$webhook = wc_get_webhook( $webhook_id );
		if ( ! $webhook || self::WEBHOOK_TOPIC !== $webhook->get_topic() ) {
			return;
		}

		// $arg contains the order_id from do_action( self::WEBHOOK_ACTION, $order_id, $order ).
		$order = wc_get_order( $arg );
		if ( ! $order ) {
			return;
		}

		if ( 'sent' !== $order->get_meta( self::FIRST_EVENT_DELIVERED_META_KEY ) ) {
			$order->update_meta_data( self::FIRST_EVENT_DELIVERED_META_KEY, 'sent' );
			$order->save();
		}
	}
}
