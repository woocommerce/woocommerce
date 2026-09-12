<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Checks;

defined( 'ABSPATH' ) || exit;

/**
 * Checks for recent WooCommerce webhook delivery failures.
 *
 * @internal
 */
class WebhookFailureCheck {

	public const ID = 'webhook_failures';

	private const DEFAULT_THRESHOLD = 10;

	/**
	 * Return the prefixed test slug used by WordPress Site Health.
	 *
	 * @return string The Site Health test id.
	 */
	public function get_id(): string {
		return 'woocommerce_' . self::ID;
	}

	/**
	 * Return the human-readable label for this check.
	 *
	 * @return string The human-readable check title.
	 */
	public function get_label(): string {
		return __( 'WooCommerce webhook deliveries', 'woocommerce' );
	}

	/**
	 * Whether this check runs asynchronously.
	 *
	 * @return bool Whether this check runs asynchronously.
	 */
	public function is_async(): bool {
		return true;
	}

	/**
	 * Run the check.
	 *
	 * @return array WP Site Health result array.
	 */
	public function run(): array {
		try {
			$count = $this->count_recent_failures();
		} catch ( \Throwable $e ) {
			return $this->error_result( $e );
		}

		/**
		 * Filters the failed-delivery count threshold above which webhooks are flagged as unhealthy.
		 *
		 * @since 11.0.0
		 *
		 * @param int $threshold The failed-delivery count threshold.
		 */
		$threshold = (int) apply_filters(
			'woocommerce_site_health_check_' . self::ID . '_threshold',
			self::DEFAULT_THRESHOLD
		);

		if ( $count > $threshold ) {
			return $this->finish(
				array(
					'label'       => __( 'WooCommerce has failed webhook deliveries', 'woocommerce' ),
					'status'      => 'recommended',
					'badge'       => array(
						'label' => __( 'WooCommerce', 'woocommerce' ),
						'color' => 'orange',
					),
					'description' => '<p>' . esc_html(
						sprintf(
							/* translators: %d: number of failed webhook deliveries in the last 24 hours */
							__( 'WooCommerce has had %d failed webhook delivery attempts in the last 24 hours. Check your webhook endpoints are reachable and returning a successful HTTP status.', 'woocommerce' ),
							$count
						)
					) . '</p>',
					'actions'     => sprintf(
						'<p><a href="%s">%s</a></p>',
						esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=webhooks' ) ),
						esc_html__( 'Manage webhooks', 'woocommerce' )
					),
				)
			);
		}

		return $this->finish(
			array(
				'label'       => __( 'WooCommerce webhook deliveries are healthy', 'woocommerce' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'WooCommerce', 'woocommerce' ),
					'color' => 'green',
				),
				'description' => '<p>' . esc_html__( 'No significant webhook delivery failures detected in the last 24 hours.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			)
		);
	}

	/**
	 * Count failed webhook delivery actions in the last 24 hours.
	 *
	 * @return int The number of recent failures, or 0 when the table is absent.
	 */
	public function count_recent_failures(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'actionscheduler_actions';
		if ( ! $this->table_exists( $table ) ) {
			return 0;
		}
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is derived from $wpdb->prefix and a hardcoded table name, not user input; the user value $cutoff is passed via %s.
				"SELECT COUNT(*) FROM {$table} WHERE hook = 'woocommerce_deliver_webhook_async' AND status = 'failed' AND last_attempt_gmt > %s",
				$cutoff
			)
		);
	}

	/**
	 * Determine whether the given database table exists.
	 *
	 * @param string $table The fully qualified table name.
	 * @return bool Whether the table exists.
	 */
	private function table_exists( string $table ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	/**
	 * Apply enable/result filters and finalize.
	 *
	 * @param array $base Base result array (without the 'test' key).
	 * @return array Filtered result, or empty array when the check is disabled.
	 */
	private function finish( array $base ): array {
		$base['test'] = $this->get_id();
		/**
		 * Filters whether this Site Health check is enabled.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $enabled Whether the check is enabled.
		 */
		if ( ! apply_filters( 'woocommerce_site_health_check_' . self::ID . '_enabled', true ) ) {
			return array();
		}
		/**
		 * Filters the result array returned by this Site Health check.
		 *
		 * @since 11.0.0
		 *
		 * @param array $base The Site Health result array.
		 */
		return (array) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_result', $base );
	}

	/**
	 * Build an error result when the check itself throws.
	 *
	 * @param \Throwable $e The caught exception.
	 * @return array WP Site Health result array.
	 */
	private function error_result( \Throwable $e ): array {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error( $e->getMessage(), array( 'source' => 'site-health' ) );
		}
		return $this->finish(
			array(
				'label'       => __( 'WooCommerce could not run a Site Health check', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array(
					'label' => __( 'Performance', 'woocommerce' ),
					'color' => 'gray',
				),
				'description' => '<p>' . esc_html__( 'WooCommerce was unable to run this check. See the site-health log channel for details.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			)
		);
	}
}
