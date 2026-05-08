<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Checks;

defined( 'ABSPATH' ) || exit;

/**
 * Checks the WooCommerce sessions table row count for excessive growth.
 *
 * @internal
 */
class SessionsTableCheck {

	public const ID = 'sessions_table';

	private const DEFAULT_THRESHOLD = 100_000;

	public function get_id(): string {
		return 'woocommerce_' . self::ID;
	}

	public function get_label(): string {
		return __( 'Woo: sessions table size', 'woocommerce' );
	}

	public function is_async(): bool {
		return true;
	}

	public function run(): array {
		try {
			$count = $this->count_sessions();
		} catch ( \Throwable $e ) {
			return $this->error_result( $e );
		}

		$threshold = (int) apply_filters(
			'woocommerce_site_health_check_' . self::ID . '_threshold',
			self::DEFAULT_THRESHOLD
		);

		if ( $count > $threshold ) {
			return $this->finish( array(
				'label'       => __( 'Woo: sessions table is large', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
				'description' => '<p>' . esc_html(
					sprintf(
						__( 'WooCommerce has %s session rows. The session cleanup cron only removes a portion at a time, so accumulation is possible if traffic grows. Consider truncating expired sessions.', 'woocommerce' ),
						number_format_i18n( $count )
					)
				) . '</p>',
				'actions'     => sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( admin_url( 'admin.php?page=wc-status&tab=tools' ) ),
					esc_html__( 'Clear customer sessions', 'woocommerce' )
				),
			) );
		}

		return $this->finish( array(
			'label'       => __( 'Woo: sessions table size is healthy', 'woocommerce' ),
			'status'      => 'good',
			'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
			'description' => '<p>' . esc_html__( 'The WooCommerce sessions table is within healthy bounds.', 'woocommerce' ) . '</p>',
			'actions'     => '',
		) );
	}

	public function count_sessions(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'woocommerce_sessions';
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
