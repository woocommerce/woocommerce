<?php
/**
 * WebhookTopicNotices class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Webhooks;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Admin_Notices;
use WC_Cache_Helper;

/**
 * Disables stored custom action webhooks whose topic is no longer allowed, and tells store owners about them.
 *
 * @since 11.4.0
 */
class WebhookTopicNotices implements RegisterHooksInterface {

	/**
	 * Version of the topic rules the stored webhooks were last checked against. Bump it when wc_is_webhook_valid_topic() refuses more topics.
	 */
	public const TOPIC_CHECK_VERSION = 1;

	/**
	 * Option holding the topic rules version the stored webhooks were last checked against.
	 */
	public const TOPIC_CHECK_OPTION = 'woocommerce_webhook_topic_check_version';

	/**
	 * Admin notice listing webhooks disabled because their topic is no longer allowed.
	 */
	public const BLOCKED_TOPICS_NOTICE = 'webhook_topics_blocked';

	/**
	 * Register hooks.
	 *
	 * @since 11.4.0
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'handle_admin_init' ) );
	}

	/**
	 * Handle the admin_init hook.
	 *
	 * @internal
	 */
	public function handle_admin_init(): void {
		if ( ! class_exists( WC_Admin_Notices::class ) ) {
			return;
		}

		$this->maybe_disable_blocked_webhooks();
	}

	/**
	 * Disable webhooks with blocked topics and list them, including those already disabled.
	 */
	private function maybe_disable_blocked_webhooks(): void {
		if ( (int) get_option( self::TOPIC_CHECK_OPTION, 0 ) >= self::TOPIC_CHECK_VERSION ) {
			return;
		}

		$webhooks = $this->get_stored_action_webhooks();
		if ( null === $webhooks ) {
			return;
		}

		$blocked     = array();
		$needs_retry = false;
		foreach ( $webhooks as $webhook ) {
			if ( ! $this->is_newly_blocked_topic( $webhook['topic'] ) ) {
				continue;
			}

			if ( ! $this->disable_webhook( $webhook ) ) {
				$needs_retry = true;
				continue;
			}

			$blocked[] = $webhook;
		}

		if ( ! $needs_retry ) {
			update_option( self::TOPIC_CHECK_OPTION, self::TOPIC_CHECK_VERSION );
		}

		if ( $blocked ) {
			$this->clear_webhook_caches();
			$this->show_blocked_webhooks_notice( $blocked );

			foreach ( $blocked as $webhook ) {
				if ( 'disabled' !== $webhook['status'] ) {
					/**
					 * Fires after the topic check disables a webhook and clears the caches.
					 *
					 * @since 11.4.0
					 * @param int $webhook_id Webhook ID.
					 */
					do_action( 'woocommerce_webhook_updated', (int) $webhook['webhook_id'] );
				}
			}
		}
	}

	/**
	 * Read topics from the table. WC_Webhook clears invalid topics when it loads.
	 *
	 * @return array[]|null Stored rows, or null if the query failed.
	 */
	private function get_stored_action_webhooks(): ?array {
		global $wpdb;

		$webhooks = $wpdb->get_results( "SELECT webhook_id, name, topic, status FROM {$wpdb->prefix}wc_webhooks WHERE topic LIKE 'action.%'", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time check of raw stored topics.
		if ( '' !== $wpdb->last_error || ! is_array( $webhooks ) ) {
			return null;
		}

		return $webhooks;
	}

	/**
	 * Check for blocked topics with a prefix that older versions accepted.
	 *
	 * @param string $topic Stored topic.
	 * @return bool
	 */
	private function is_newly_blocked_topic( string $topic ): bool {
		return ( 0 === strpos( $topic, 'action.woocommerce_' ) || 0 === strpos( $topic, 'action.wc_' ) ) && ! wc_is_webhook_valid_topic( $topic );
	}

	/**
	 * Disable a webhook and keep its stored topic.
	 *
	 * @param array<string,mixed> $webhook Stored row.
	 * @return bool Whether the row is disabled.
	 */
	private function disable_webhook( array $webhook ): bool {
		if ( 'disabled' === $webhook['status'] ) {
			return true;
		}

		global $wpdb;
		$webhook_id = (int) $webhook['webhook_id'];
		$updated    = $wpdb->update(
			"{$wpdb->prefix}wc_webhooks",
			array( 'status' => 'disabled' ),
			array(
				'webhook_id' => $webhook_id,
				'topic'      => $webhook['topic'],
				'status'     => $webhook['status'],
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Status-only update keeps the stored topic; caches are cleared below.

		wp_cache_delete( $webhook_id, 'webhooks' );
		if ( ! $updated ) {
			// Retry on the next admin request if the write failed or the row changed.
			return false;
		}

		wc_get_logger()->warning(
			sprintf( 'Webhook ID %d was disabled because its topic "%s" is no longer allowed.', $webhook_id, $webhook['topic'] ),
			array( 'source' => 'webhooks' )
		);

		return true;
	}

	/**
	 * Clear caches after the status changes.
	 */
	private function clear_webhook_caches(): void {
		delete_transient( 'woocommerce_webhook_ids' );
		foreach ( array_keys( wc_get_webhook_statuses() ) as $status ) {
			delete_transient( 'woocommerce_webhook_ids_status_' . $status );
		}
		WC_Cache_Helper::invalidate_cache_group( 'webhooks' );
	}

	/**
	 * Show store owners which webhooks use unsupported topics.
	 *
	 * @param array[] $blocked Stored rows to list.
	 */
	private function show_blocked_webhooks_notice( array $blocked ): void {
		$items = array_map(
			fn( array $webhook ) => sprintf( '<li>%s (<code>%s</code>)</li>', esc_html( $this->get_webhook_label( (int) $webhook['webhook_id'], (string) $webhook['name'] ) ), esc_html( $webhook['topic'] ) ),
			$blocked
		);

		WC_Admin_Notices::add_custom_notice(
			self::BLOCKED_TOPICS_NOTICE,
			'<p><strong>' . esc_html__( 'Some webhooks use topics that are no longer supported.', 'woocommerce' ) . '</strong></p>' .
			'<p>' . esc_html__( 'These webhooks are disabled. To use one again, select a supported topic.', 'woocommerce' ) . '</p>' .
			'<ul>' . implode( '', $items ) . '</ul>' .
			'<p>' . $this->get_webhooks_settings_link() . '</p>'
		);
		// Save the notice now. The next admin request can skip the completed scan.
		WC_Admin_Notices::store_notices();
	}

	/**
	 * Get a label for a webhook in a notice.
	 *
	 * @param int    $webhook_id The webhook ID.
	 * @param string $name       The webhook name.
	 * @return string
	 */
	private function get_webhook_label( int $webhook_id, string $name ): string {
		/* translators: 1: webhook name, 2: webhook ID. */
		return sprintf( __( '%1$s (ID %2$d)', 'woocommerce' ), '' !== $name ? $name : __( 'Unnamed webhook', 'woocommerce' ), $webhook_id );
	}

	/**
	 * Get a link to the webhooks settings screen.
	 *
	 * @return string
	 */
	private function get_webhooks_settings_link(): string {
		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=webhooks' ) ),
			esc_html__( 'Review webhooks', 'woocommerce' )
		);
	}
}
