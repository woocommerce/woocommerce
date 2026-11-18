<?php
/**
 * WebhookScheduledActionArgs class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Webhooks;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Shows the stored arguments of webhook deliveries redacted on Status > Scheduled Actions.
 *
 * Deliveries queued before redaction existed, or by a replacement queue, can still hold raw custom action arguments.
 *
 * @since 11.4.0
 */
class WebhookScheduledActionArgs implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 *
	 * @since 11.4.0
	 */
	public function register() {
		add_filter( 'action_scheduler_list_table_column_args', array( $this, 'handle_action_scheduler_list_table_column_args' ), 10, 2 );
	}

	/**
	 * Get the arguments column of a webhook delivery with its hook argument redacted.
	 *
	 * @internal
	 *
	 * @param mixed $html The column HTML.
	 * @param mixed $row  The scheduled action row.
	 * @return mixed
	 */
	public function handle_action_scheduler_list_table_column_args( $html, $row ) {
		if ( ! is_array( $row ) || 'woocommerce_deliver_webhook_async' !== ( $row['hook'] ?? '' ) || ! is_array( $row['args'] ?? null ) || ! array_key_exists( 'arg', $row['args'] ) ) {
			return $html;
		}

		$args        = $row['args'];
		$args['arg'] = $this->redact_queued_arg( (int) ( $args['webhook_id'] ?? 0 ), $args['arg'] );
		if ( $row['args']['arg'] === $args['arg'] ) {
			return $html;
		}

		$html = '<ul>';
		foreach ( $args as $key => $value ) {
			$html .= sprintf( '<li><code>%s => %s</code></li>', esc_html( var_export( $key, true ) ), esc_html( var_export( $value, true ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Same format as the Action Scheduler list table.
		}

		return $html . '</ul>';
	}

	/**
	 * Redact the hook argument of a queued webhook delivery for display.
	 *
	 * The webhook's topic may have changed since the delivery was queued, so the current topic can't say what a bare value means:
	 * IDs are kept unless the webhook is gone (for action topics only integers), other bare values are hidden, and arrays and objects are redacted by name.
	 *
	 * @param int   $webhook_id The webhook ID.
	 * @param mixed $arg        The queued hook argument.
	 * @return mixed
	 */
	private function redact_queued_arg( int $webhook_id, $arg ) {
		if ( null === $arg || '' === $arg || is_bool( $arg ) || WebhookPayloadRedactor::REDACTED === $arg ) {
			return $arg;
		}

		$webhook  = wc_get_webhook( $webhook_id );
		$resource = $webhook ? $webhook->get_resource() : '';

		if ( '' !== $resource && is_numeric( $arg ) && ( 'action' !== $resource || is_int( $arg ) ) ) {
			return $arg;
		}

		if ( 'action' === $resource && ! is_scalar( $arg ) ) {
			return WebhookPayloadRedactor::redact( $webhook->get_topic(), $arg );
		}

		return WebhookPayloadRedactor::REDACTED;
	}
}
