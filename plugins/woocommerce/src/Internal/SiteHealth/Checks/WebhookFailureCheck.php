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

	public function get_id(): string {
		return 'woocommerce_' . self::ID;
	}

	public function get_label(): string {
		return __( 'Woo: webhook deliveries', 'woocommerce' );
	}

	public function is_async(): bool {
		return true;
	}

	public function run(): array {
		try {
			$count = $this->count_recent_failures();
		} catch ( \Throwable $e ) {
			return $this->error_result( $e );
		}

		$threshold = (int) apply_filters(
			'woocommerce_site_health_check_' . self::ID . '_threshold',
			self::DEFAULT_THRESHOLD
		);

		if ( $count > $threshold ) {
			return $this->finish( array(
				'label'       => __( 'Woo: has failed webhook deliveries', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'orange' ),
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
			) );
		}

		return $this->finish( array(
			'label'       => __( 'Woo: webhook deliveries are healthy', 'woocommerce' ),
			'status'      => 'good',
			'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'green' ),
			'description' => '<p>' . esc_html__( 'No significant webhook delivery failures detected in the last 24 hours.', 'woocommerce' ) . '</p>',
			'actions'     => '',
		) );
	}

	public function count_recent_failures(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'actionscheduler_actions';
		if ( ! $this->table_exists( $table ) ) {
			return 0;
		}
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE hook = 'woocommerce_deliver_webhook_async' AND status = 'failed' AND last_attempt_gmt > %s",
			$cutoff
		) );
	}

	private function table_exists( string $table ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	private function finish( array $base ): array {
		$base['test'] = $this->get_id();
		if ( ! apply_filters( 'woocommerce_site_health_check_' . self::ID . '_enabled', true ) ) {
			return array();
		}
		return (array) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_result', $base );
	}

	private function error_result( \Throwable $e ): array {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error( $e->getMessage(), array( 'source' => 'site-health' ) );
		}
		return $this->finish( array(
			'label'       => __( 'Woo: could not run a Site Health check', 'woocommerce' ),
			'status'      => 'recommended',
			'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'gray' ),
			'description' => '<p>' . esc_html__( 'WooCommerce was unable to run this check. See the site-health log channel for details.', 'woocommerce' ) . '</p>',
			'actions'     => '',
		) );
	}
}
