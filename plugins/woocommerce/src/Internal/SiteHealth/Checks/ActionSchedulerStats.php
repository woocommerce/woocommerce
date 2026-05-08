<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Checks;

defined( 'ABSPATH' ) || exit;

/**
 * Action Scheduler health checks: overdue and total table size.
 *
 * @internal
 */
class ActionSchedulerStats {

	public const ID_OVERDUE = 'action_scheduler_overdue';
	public const ID_TOTAL   = 'action_scheduler_total';

	private const DEFAULT_OVERDUE_THRESHOLD = 50;
	private const DEFAULT_TOTAL_THRESHOLD   = 500_000;

	public function run_overdue(): array {
		try {
			$count = $this->count_overdue_actions();
		} catch ( \Throwable $e ) {
			return $this->error_result( self::ID_OVERDUE, $e );
		}
		$threshold = (int) apply_filters( 'woocommerce_site_health_check_' . self::ID_OVERDUE . '_threshold', self::DEFAULT_OVERDUE_THRESHOLD );
		if ( $count > $threshold ) {
			return $this->finish( self::ID_OVERDUE, array(
				'label'       => __( 'Action Scheduler has overdue actions', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
				'description' => '<p>' . esc_html(
					sprintf( __( 'Action Scheduler has %d actions overdue by more than one hour. This often indicates a stuck cron or background job.', 'woocommerce' ), $count )
				) . '</p>',
				'actions'     => sprintf( '<p><a href="%s">%s</a></p>', esc_url( admin_url( 'admin.php?page=wc-status&tab=action-scheduler' ) ), esc_html__( 'View scheduled actions', 'woocommerce' ) ),
			) );
		}
		return $this->finish( self::ID_OVERDUE, array(
			'label'       => __( 'Action Scheduler is up to date', 'woocommerce' ),
			'status'      => 'good',
			'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
			'description' => '<p>' . esc_html__( 'Action Scheduler has no significant backlog of overdue actions.', 'woocommerce' ) . '</p>',
			'actions'     => '',
		) );
	}

	public function run_total(): array {
		try {
			$count = $this->count_total_actions();
		} catch ( \Throwable $e ) {
			return $this->error_result( self::ID_TOTAL, $e );
		}
		$threshold = (int) apply_filters( 'woocommerce_site_health_check_' . self::ID_TOTAL . '_threshold', self::DEFAULT_TOTAL_THRESHOLD );
		if ( $count > $threshold ) {
			return $this->finish( self::ID_TOTAL, array(
				'label'       => __( 'Action Scheduler table is large', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
				'description' => '<p>' . esc_html(
					sprintf( __( 'The Action Scheduler actions table contains %s rows. Consider lowering the retention period for completed actions.', 'woocommerce' ), number_format_i18n( $count ) )
				) . '</p>',
				'actions'     => '',
			) );
		}
		return $this->finish( self::ID_TOTAL, array(
			'label'       => __( 'Action Scheduler table size is healthy', 'woocommerce' ),
			'status'      => 'good',
			'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
			'description' => '<p>' . esc_html__( 'Action Scheduler retention is within healthy bounds.', 'woocommerce' ) . '</p>',
			'actions'     => '',
		) );
	}

	public function count_overdue_actions(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'actionscheduler_actions';
		if ( ! $this->table_exists( $table ) ) {
			return 0;
		}
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE status = 'pending' AND scheduled_date_gmt < %s",
			$cutoff
		) );
	}

	public function count_total_actions(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'actionscheduler_actions';
		if ( ! $this->table_exists( $table ) ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	private function table_exists( string $table ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	private function finish( string $id, array $base ): array {
		$base['test'] = 'woocommerce_' . $id;
		if ( ! apply_filters( "woocommerce_site_health_check_{$id}_enabled", true ) ) {
			return array();
		}
		return (array) apply_filters( "woocommerce_site_health_check_{$id}_result", $base );
	}

	private function error_result( string $id, \Throwable $e ): array {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error( $e->getMessage(), array( 'source' => 'site-health' ) );
		}
		return $this->finish( $id, array(
			'label'       => __( 'WooCommerce could not run an Action Scheduler check', 'woocommerce' ),
			'status'      => 'recommended',
			'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'gray' ),
			'description' => '<p>' . esc_html__( 'WooCommerce was unable to query the Action Scheduler tables. Check the site error logs.', 'woocommerce' ) . '</p>',
			'actions'     => '',
		) );
	}
}
