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
		return __( 'WooCommerce sessions table size', 'woocommerce' );
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
			$count = $this->count_sessions();
		} catch ( \Throwable $e ) {
			return $this->error_result( $e );
		}

		/**
		 * Filters the session row count threshold above which the table is considered large.
		 *
		 * @since 11.0.0
		 *
		 * @param int $threshold The session row count threshold.
		 */
		$threshold = (int) apply_filters(
			'woocommerce_site_health_check_' . self::ID . '_threshold',
			self::DEFAULT_THRESHOLD
		);

		if ( $count > $threshold ) {
			return $this->finish(
				array(
					'label'       => __( 'WooCommerce sessions table is large', 'woocommerce' ),
					'status'      => 'recommended',
					'badge'       => array(
						'label' => __( 'Performance', 'woocommerce' ),
						'color' => 'orange',
					),
					'description' => '<p>' . esc_html(
						sprintf(
							/* translators: %s: number of session rows. */
							__( 'WooCommerce has %s session rows. The session cleanup cron only removes a portion at a time, so accumulation is possible if traffic grows. Consider truncating expired sessions.', 'woocommerce' ),
							number_format_i18n( $count )
						)
					) . '</p>',
					'actions'     => sprintf(
						'<p><a href="%s">%s</a></p>',
						esc_url( admin_url( 'admin.php?page=wc-status&tab=tools' ) ),
						esc_html__( 'Clear customer sessions', 'woocommerce' )
					),
				)
			);
		}

		return $this->finish(
			array(
				'label'       => __( 'WooCommerce sessions table size is healthy', 'woocommerce' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'Performance', 'woocommerce' ),
					'color' => 'green',
				),
				'description' => '<p>' . esc_html__( 'The WooCommerce sessions table is within healthy bounds.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			)
		);
	}

	/**
	 * Count the rows in the WooCommerce sessions table.
	 *
	 * @return int The number of session rows, or 0 when the table is absent.
	 */
	public function count_sessions(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'woocommerce_sessions';
		if ( ! $this->table_exists( $table ) ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
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
