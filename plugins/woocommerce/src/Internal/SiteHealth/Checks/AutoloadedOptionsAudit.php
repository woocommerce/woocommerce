<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Checks;

defined( 'ABSPATH' ) || exit;

/**
 * Checks autoloaded options size for WooCommerce-prefixed entries.
 *
 * @internal
 */
class AutoloadedOptionsAudit {

	public const ID = 'autoloaded_options';

	private const DEFAULT_TOTAL_THRESHOLD      = 800 * 1024;
	private const DEFAULT_PER_OPTION_THRESHOLD = 100 * 1024;

	/**
	 * Get the unique identifier for this check.
	 *
	 * @return string The check ID, prefixed with `woocommerce_`.
	 */
	public function get_id(): string {
		return 'woocommerce_' . self::ID;
	}

	/**
	 * Get the human-readable label for this check.
	 *
	 * @return string The translated check label.
	 */
	public function get_label(): string {
		return __( 'WooCommerce autoloaded options size', 'woocommerce' );
	}

	/**
	 * Whether this check runs asynchronously.
	 *
	 * @return bool True when the check is asynchronous.
	 */
	public function is_async(): bool {
		return true;
	}

	/**
	 * Run the autoloaded options size check.
	 *
	 * @return array WP Site Health result array.
	 */
	public function run(): array {
		try {
			$total   = $this->query_total_size();
			$largest = $this->query_largest_wc_options();
		} catch ( \Throwable $e ) {
			return $this->error_result( $e );
		}

		/**
		 * Filter the total autoloaded options size threshold (in bytes) for the Site Health check.
		 *
		 * @since 11.0.0
		 *
		 * @param int $total_threshold The total size threshold in bytes.
		 */
		$total_threshold = (int) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_threshold', self::DEFAULT_TOTAL_THRESHOLD );
		/**
		 * Filter the per-option autoloaded size threshold (in bytes) for the Site Health check.
		 *
		 * @since 11.0.0
		 *
		 * @param int $per_option_threshold The per-option size threshold in bytes.
		 */
		$per_option_threshold = (int) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_per_option_threshold', self::DEFAULT_PER_OPTION_THRESHOLD );

		$oversize_options = array_filter(
			$largest,
			static fn( array $row ) => (int) $row['size'] > $per_option_threshold
		);

		if ( $total > $total_threshold || ! empty( $oversize_options ) ) {
			$description = sprintf(
				/* translators: %s: human-readable byte total */
				__( 'WooCommerce autoloaded options total %s.', 'woocommerce' ),
				size_format( $total )
			);
			if ( ! empty( $oversize_options ) ) {
				$names        = wp_list_pluck( $oversize_options, 'option_name' );
				$description .= ' ' . sprintf(
					/* translators: %s: comma-separated list of option names */
					__( 'These WooCommerce-prefixed options are unusually large: %s.', 'woocommerce' ),
					implode( ', ', $names )
				);
			}
			return $this->finish(
				array(
					'label'       => __( 'WooCommerce autoloaded options are large', 'woocommerce' ),
					'status'      => 'recommended',
					'badge'       => array(
						'label' => __( 'Performance', 'woocommerce' ),
						'color' => 'orange',
					),
					'description' => '<p>' . esc_html( $description ) . '</p>',
					'actions'     => sprintf(
						'<p><a href="%s">%s</a></p>',
						esc_url( 'https://woocommerce.com/document/optimizing-woocommerce/' ),
						esc_html__( 'Learn how to clean up autoloaded options', 'woocommerce' )
					),
				)
			);
		}

		return $this->finish(
			array(
				'label'       => __( 'WooCommerce autoloaded options size is healthy', 'woocommerce' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'Performance', 'woocommerce' ),
					'color' => 'green',
				),
				'description' => '<p>' . esc_html__( 'Autoloaded options are within healthy bounds.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			)
		);
	}

	/**
	 * Query the total size of autoloaded options.
	 *
	 * @return int The combined size, in bytes, of all autoloaded option values.
	 */
	public function query_total_size(): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ('yes','on')" );
	}

	/**
	 * Query the largest autoloaded WooCommerce-prefixed options.
	 *
	 * @return array List of rows with `option_name` and `size` keys, largest first.
	 */
	public function query_largest_wc_options(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results(
			"SELECT option_name, LENGTH(option_value) AS size FROM {$wpdb->options} WHERE autoload IN ('yes','on') AND option_name LIKE 'woocommerce\\_%' ORDER BY size DESC LIMIT 5",
			ARRAY_A
		);
	}

	/**
	 * Finalize a result array, applying the enable and result filters.
	 *
	 * @param array $base The base result array.
	 * @return array The filtered result array, or an empty array when the check is disabled.
	 */
	private function finish( array $base ): array {
		$base['test'] = $this->get_id();
		/**
		 * Filter whether the autoloaded options Site Health check is enabled.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $enabled Whether the check is enabled.
		 */
		if ( ! apply_filters( 'woocommerce_site_health_check_' . self::ID . '_enabled', true ) ) {
			return array();
		}
		/**
		 * Filter the autoloaded options Site Health check result before WP renders it.
		 *
		 * @since 11.0.0
		 *
		 * @param array $base The Site Health result array.
		 */
		return (array) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_result', $base );
	}

	/**
	 * Build a result array for when the check could not run.
	 *
	 * @param \Throwable $e The throwable raised while running the check.
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
