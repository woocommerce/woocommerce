<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Agentic;

use Automattic\WooCommerce\Enums\OrderStatus;
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
class AgenticWebhookManager {
	/**
	 * Custom webhook topic for Agentic order creation.
	 */
	const TOPIC_ORDER_CREATED = 'action.woocommerce_agentic_order_created';

	/**
	 * Custom webhook topic for Agentic order updates.
	 */
	const TOPIC_ORDER_UPDATED = 'action.woocommerce_agentic_order_updated';

	/**
	 * Payload builder instance.
	 *
	 * @var AgenticWebhookPayloadBuilder
	 */
	private $payload_builder;

	/**
	 * Track processed events to prevent duplicate firing.
	 *
	 * @var array
	 */
	private static $processed_events = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->payload_builder = new AgenticWebhookPayloadBuilder();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks for webhook integration.
	 */
	private function init_hooks() {
		add_filter( 'woocommerce_valid_webhook_events', array( $this, 'register_webhook_events' ) );
		add_filter( 'woocommerce_webhook_topics', array( $this, 'register_webhook_topic_names' ) );

		// Hook into order lifecycle events to fire our custom actions.
		add_action( 'woocommerce_new_order', array( $this, 'handle_order_created' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_changed' ), 10, 4 );
		add_action( 'woocommerce_order_refunded', array( $this, 'handle_order_refunded' ), 10, 2 );

		// Customize webhook payload for our topics.
		add_filter( 'woocommerce_webhook_payload', array( $this, 'customize_webhook_payload' ), 10, 4 );

		// Customize webhook HTTP arguments for our topics.
		add_filter( 'woocommerce_webhook_http_args', array( $this, 'customize_webhook_http_args' ), 10, 3 );
		add_filter( 'woocommerce_webhook_delivery_url', array( $this, 'customize_webhook_delivery_url' ), 10, 2 );
	}

	/**
	 * Register valid webhook events for the 'order' resource.
	 *
	 * @param array $events Valid events.
	 * @return array Modified events.
	 */
	public function register_webhook_events( $events ) {
		// Add our custom events to the valid events list.
		$events[] = 'agentic_created';
		$events[] = 'agentic_updated';
		return $events;
	}

	/**
	 * Register webhook topic names for display in the UI.
	 *
	 * @param array $topics Existing topics.
	 * @return array Modified topics.
	 */
	public function register_webhook_topic_names( $topics ) {
		$topics['action.woocommerce_agentic_order_created'] = __( 'Agentic Order Created', 'woocommerce' );
		$topics['action.woocommerce_agentic_order_updated'] = __( 'Agentic Order Updated', 'woocommerce' );
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
		 * Fires when an Agentic order is created.
		 *
		 * @since 10.3.0
		 *
		 * @param int      $order_id Order ID.
		 * @param WC_Order $order    Order object.
		 */
		do_action( 'woocommerce_agentic_order_created', $order_id, $order );
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

		// Prevent duplicate firing for the same status change.
		$event_key = 'status_' . $order_id . '_' . $old_status . '_' . $new_status;
		if ( isset( self::$processed_events[ $event_key ] ) ) {
			return;
		}
		self::$processed_events[ $event_key ] = true;

		/**
		 * Fires when an Agentic order status changes.
		 *
		 * @since 10.3.0
		 *
		 * @param int      $order_id Order ID.
		 * @param WC_Order $order    Order object.
		 */
		do_action( 'woocommerce_agentic_order_updated', $order_id, $order );
	}

	/**
	 * Handle order refunds.
	 *
	 * @param int $order_id  Order ID.
	 * @param int $refund_id Refund ID.
	 */
	public function handle_order_refunded( $order_id, $refund_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $this->should_trigger_webhook( $order ) ) {
			return;
		}

		// Prevent duplicate firing for the same refund.
		$event_key = 'refund_' . $order_id . '_' . $refund_id;
		if ( isset( self::$processed_events[ $event_key ] ) ) {
			return;
		}
		self::$processed_events[ $event_key ] = true;

		/**
		 * Fires when an Agentic order is refunded.
		 *
		 * @since 10.3.0
		 *
		 * @param int      $order_id Order ID.
		 * @param WC_Order $order    Order object.
		 */
		do_action( 'woocommerce_agentic_order_updated', $order_id, $order );
	}

	/**
	 * Check if webhook should be triggered for this order.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool True if webhook should be triggered.
	 */
	private function should_trigger_webhook( $order ) {
		// Only trigger for orders with an Agentic checkout session ID.
		$checkout_session_id = $order->get_meta( '_agentic_checkout_session_id' );
		if ( empty( $checkout_session_id ) ) {
			return false;
		}

		// Don't trigger for draft orders.
		if ( in_array( $order->get_status(), array( OrderStatus::DRAFT, OrderStatus::AUTO_DRAFT ), true ) ) {
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
		if ( ! in_array( $topic, array( self::TOPIC_ORDER_CREATED, self::TOPIC_ORDER_UPDATED ), true ) ) {
			return $payload;
		}

		// Get the order.
		$order = wc_get_order( $resource_id );
		if ( ! $order ) {
			return $payload;
		}

		// Determine event type based on topic.
		$event = ( self::TOPIC_ORDER_CREATED === $topic ) ? 'order_create' : 'order_update';

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
		if ( ! in_array( $topic, array( self::TOPIC_ORDER_CREATED, self::TOPIC_ORDER_UPDATED ), true ) ) {
			return $http_args;
		}

		// Replace X-WC-Webhook-Signature with Merchant-Signature for ACP compliance.
		if ( isset( $http_args['headers']['X-WC-Webhook-Signature'] ) ) {
			$http_args['headers']['Merchant-Signature'] = $http_args['headers']['X-WC-Webhook-Signature'];
			unset( $http_args['headers']['X-WC-Webhook-Signature'] );
		}

		// Add ACP-specific headers.
		$http_args['headers']['Request-Id'] = wp_generate_uuid4();
		$http_args['headers']['Timestamp']  = gmdate( 'c' );

		return $http_args;
	}

	/**
	 * Customize webhook delivery URL for Agentic topics.
	 *
	 * @param string $url        Delivery URL.
	 * @param int    $webhook_id Webhook ID.
	 * @return string Modified URL.
	 */
	public function customize_webhook_delivery_url( $url, $webhook_id ) {
		// Only modify URL during actual delivery, not during retrieval.
		// Check if we're in a delivery context by looking at the call stack.
		if ( ! $this->is_delivering_webhook() ) {
			return $url;
		}

		$webhook = wc_get_webhook( $webhook_id );
		if ( ! $webhook ) {
			return $url;
		}

		$topic = $webhook->get_topic();

		// Check if this is one of our Agentic topics.
		if ( ! in_array( $topic, array( self::TOPIC_ORDER_CREATED, self::TOPIC_ORDER_UPDATED ), true ) ) {
			return $url;
		}

		// Append the ACP endpoint path if not already present.
		$acp_path = '/agentic_checkout/webhooks/order_events';
		if ( strpos( $url, $acp_path ) === false ) {
			$url = trailingslashit( $url ) . ltrim( $acp_path, '/' );
		}

		return $url;
	}

	/**
	 * Check if we're currently delivering a webhook.
	 *
	 * @return bool True if in delivery context.
	 */
	private function is_delivering_webhook() {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Needed to detect delivery context.
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );
		foreach ( $backtrace as $call ) {
			if ( isset( $call['class'] ) && 'WC_Webhook' === $call['class']
				&& isset( $call['function'] ) && 'deliver' === $call['function'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Reset processed events tracking.
	 * Useful for testing or when starting a new request context.
	 */
	public static function reset_processed_events() {
		self::$processed_events = array();
	}
}
