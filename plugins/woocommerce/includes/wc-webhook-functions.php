<?php
/**
 * WooCommerce Webhook functions
 *
 * @package WooCommerce\Functions
 * @version 3.3.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Utilities\ActionSchedulerUtil;
use Automattic\WooCommerce\Internal\Webhooks\WebhookCaptureRules;
use Automattic\WooCommerce\Internal\Webhooks\WebhookObservers;
use Automattic\WooCommerce\Internal\Webhooks\WebhookPayloadRedactor;

/**
 * Process the web hooks at the end of the request.
 *
 * @since 4.4.0
 */
function wc_webhook_execute_queue() {
	global $wc_queued_webhooks;
	if ( empty( $wc_queued_webhooks ) ) {
		return;
	}

	$now = time();

	foreach ( $wc_queued_webhooks as $data ) {
		$original_arg = $data['original_arg'] ?? $data['arg'];

		/**
		 * Filters whether a webhook is delivered in the background.
		 *
		 * Background delivery is the default so a slow or failed delivery does not delay the request that triggered it.
		 *
		 * @since 3.5.0
		 * @param bool       $deliver_async Whether to deliver in the background. Default true.
		 * @param WC_Webhook $webhook       The webhook being delivered.
		 * @param mixed      $arg           The argument the webhook fired with, usually a resource ID.
		 */
		if ( apply_filters( 'woocommerce_webhook_deliver_async', true, $data['webhook'], $original_arg ) ) {

			$queue_args = array(
				'webhook_id' => $data['webhook']->get_id(),
				'arg'        => $data['arg'],
			);

			$next_scheduled = as_next_scheduled_action( 'woocommerce_deliver_webhook_async', $queue_args, 'woocommerce-webhooks' );

			// Make webhooks unique - only schedule one webhook every 10 minutes to maintain backward compatibility with WP Cron behaviour seen in WC < 3.5.0.
			// A pending delivery has a timestamp; an in-progress delivery (true) or none (false) gets a new one.
			if ( ! is_numeric( $next_scheduled ) || $next_scheduled >= ( 10 * MINUTE_IN_SECONDS + $now ) ) {
				/**
				 * Filters the Action Scheduler priority of a queued webhook delivery.
				 *
				 * @since 11.4.0
				 *
				 * @param int        $priority The delivery priority, expected to be in the range 0-255 (lower numbers take precedence). Default 10.
				 * @param WC_Webhook $webhook  The webhook being delivered.
				 * @param mixed      $arg      The argument the webhook fired with, usually the resource ID.
				 */
				$priority = apply_filters( 'woocommerce_webhook_delivery_priority', 10, $data['webhook'], $original_arg );
				$priority = is_numeric( $priority ) && is_finite( $priority ) ? (int) max( 0, min( $priority, 255 ) ) : 10;

				ActionSchedulerUtil::schedule_single( $now, 'woocommerce_deliver_webhook_async', $queue_args, 'woocommerce-webhooks', false, $priority );
			}
		} else {
			// Deliver immediately.
			$data['webhook']->deliver( $original_arg );
		}
	}
}
add_action( 'shutdown', 'wc_webhook_execute_queue' );

/**
 * Process webhook delivery.
 *
 * @since 3.3.0
 * @param WC_Webhook $webhook Webhook instance.
 * @param mixed      $arg     Delivery argument.
 */
function wc_webhook_process_delivery( $webhook, $arg ) {
	// We need to queue the webhook so that it can be ran after the request has finished processing.
	global $wc_queued_webhooks;
	if ( ! isset( $wc_queued_webhooks ) ) {
		$wc_queued_webhooks = array();
	}
	$wc_queued_webhooks[] = array(
		'webhook'      => $webhook,
		'arg'          => $webhook instanceof WC_Webhook ? WebhookPayloadRedactor::redact( $webhook->get_topic(), $arg ) : $arg,
		'original_arg' => $arg,
	);
}
add_action( 'woocommerce_webhook_process_delivery', 'wc_webhook_process_delivery', 10, 2 );

/**
 * Wrapper function to execute the `woocommerce_deliver_webhook_async` cron.
 * hook, see WC_Webhook::process().
 *
 * @since 2.2.0
 * @param int   $webhook_id Webhook ID to deliver.
 * @throws Exception        If webhook cannot be read/found and $data parameter of WC_Webhook class constructor is set.
 * @param mixed $arg        Hook argument.
 */
function wc_deliver_webhook_async( $webhook_id, $arg ) {
	$webhook = WebhookObservers::without_observers(
		static function () use ( $webhook_id ) {
			$webhook = new WC_Webhook( $webhook_id );

			return 0 === $webhook->get_id() || '' === $webhook->get_topic() ? null : $webhook;
		}
	);

	if ( $webhook ) {
		$webhook->deliver( $arg );
	}
}
add_action( 'woocommerce_deliver_webhook_async', 'wc_deliver_webhook_async', 10, 2 );

/**
 * Check if the given topic is a valid webhook topic, a topic is valid if:
 *
 * + it is a custom action topic with a supported hook name that is not blocked below.
 * + it has a valid resource & event AND (when both are part of the default sets) the
 *   `<resource>.<event>` pair has a registered hook. Default/default pairs with no
 *   registered hook (e.g. `order.published`, `customer.restored`) are rejected to
 *   prevent ghost webhooks that save as Active but never deliver.
 *
 * @since  2.2.0
 * @param  string $topic Webhook topic.
 * @return bool
 */
function wc_is_webhook_valid_topic( $topic ) {
	$data = explode( '.', $topic );

	if ( ! isset( $data[1] ) ) {
		return false;
	}

	if ( 'action' === $data[0] ) {
		// WC_Webhook subscribes to the second segment, even when the topic has a suffix.
		$hook = $data[1];
		if ( 0 !== strpos( $hook, 'woocommerce_' ) && 0 !== strpos( $hook, 'wc_' ) ) {
			return false;
		}

		return ! WebhookCaptureRules::is_blocked_hook( $hook );
	}

	$default_resources = array( 'coupon', 'customer', 'order', 'product' );
	$default_events    = array( 'created', 'updated', 'deleted', 'restored', 'published' );

	/**
	 * Filters the list of valid webhook resources.
	 *
	 * @since 2.2.0
	 * @param array $valid_resources Array of valid webhook resources.
	 */
	$valid_resources = apply_filters( 'woocommerce_valid_webhook_resources', $default_resources );

	/**
	 * Filters the list of valid webhook events.
	 *
	 * @since 2.2.0
	 * @param array $valid_events Array of valid webhook events.
	 */
	$valid_events = apply_filters( 'woocommerce_valid_webhook_events', $default_events );

	if ( ! in_array( $data[0], $valid_resources, true ) || ! in_array( $data[1], $valid_events, true ) ) {
		return false;
	}

	// Topics that include a filter-added resource or event are accepted; plugins
	// extending the resource/event lists are expected to wire delivery via the
	// `woocommerce_webhook_topic_hooks` or `woocommerce_webhook_hooks` filters.
	if ( ! in_array( $data[0], $default_resources, true ) || ! in_array( $data[1], $default_events, true ) ) {
		return true;
	}

	// Default-resource + default-event topics must correspond to a registered
	// hook. Pairs without one (e.g. `order.published`, `customer.restored`) save
	// as Active webhooks but never deliver, so reject them.
	//
	// The allowlist is derived from `WC_Webhook::get_default_topic_hooks()`,
	// which runs through the `woocommerce_webhook_topic_hooks` filter.
	// Extensions that register hooks for an otherwise-empty default pair (via
	// that filter) are therefore honored automatically, with no separate
	// allowlist to keep in sync.
	$default_topics = array_keys( array_filter( WC_Webhook::get_default_topic_hooks() ) );

	return in_array( $topic, $default_topics, true );
}

/**
 * Check if given status is a valid webhook status.
 *
 * @since 3.5.3
 * @param string $status Status to check.
 * @return bool
 */
function wc_is_webhook_valid_status( $status ) {
	return in_array( $status, array_keys( wc_get_webhook_statuses() ), true );
}

/**
 * Get Webhook statuses.
 *
 * @since  2.3.0
 * @return array
 */
function wc_get_webhook_statuses() {
	return apply_filters(
		'woocommerce_webhook_statuses',
		array(
			'active'   => __( 'Active', 'woocommerce' ),
			'paused'   => __( 'Paused', 'woocommerce' ),
			'disabled' => __( 'Disabled', 'woocommerce' ),
		)
	);
}

/**
 * Load webhooks.
 *
 * @since  3.3.0
 * @throws Exception If webhook cannot be read/found and $data parameter of WC_Webhook class constructor is set.
 * @param  string   $status Optional - status to filter results by. Must be a key in return value of @see wc_get_webhook_statuses(). @since 3.5.0.
 * @param  null|int $limit Limit number of webhooks loaded. @since 3.6.0.
 * @return bool
 */
function wc_load_webhooks( $status = '', $limit = null ) {
	// short-circuit if webhooks should not be loaded at all.
	if ( ! is_null( $limit ) && $limit <= 0 ) {
		return false;
	}

	$data_store = WC_Data_Store::load( 'webhook' );
	$webhooks   = $data_store->get_webhooks_ids( $status );
	$loaded     = 0;

	foreach ( $webhooks as $webhook_id ) {
		if ( ! is_null( $limit ) && $loaded >= $limit ) {
			break;
		}

		WebhookObservers::without_observers(
			static function () use ( $webhook_id ) {
				$webhook = new WC_Webhook( $webhook_id );
				$webhook->enqueue();
			}
		);
		$loaded ++;
	}

	return 0 < $loaded;
}

/**
 * Get webhook.
 *
 * @param  int|WC_Webhook $id Webhook ID or object.
 * @throws Exception          If webhook cannot be read/found and $data parameter of WC_Webhook class constructor is set.
 * @return WC_Webhook|null
 */
function wc_get_webhook( $id ) {
	$webhook = new WC_Webhook( $id );

	return 0 !== $webhook->get_id() ? $webhook : null;
}

/**
 * Get webhoook REST API versions.
 *
 * @since 3.5.1
 * @return array
 */
function wc_get_webhook_rest_api_versions() {
	return array(
		'wp_api_v1',
		'wp_api_v2',
		'wp_api_v3',
	);
}
