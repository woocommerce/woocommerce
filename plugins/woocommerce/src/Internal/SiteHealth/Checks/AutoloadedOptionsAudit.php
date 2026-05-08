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

	public function get_id(): string {
		return 'woocommerce_' . self::ID;
	}

	public function get_label(): string {
		return __( 'Woo: autoloaded options size', 'woocommerce' );
	}

	public function is_async(): bool {
		return true;
	}

	public function run(): array {
		try {
			$total   = $this->query_total_size();
			$largest = $this->query_largest_wc_options();
		} catch ( \Throwable $e ) {
			return $this->error_result( $e );
		}

		$total_threshold      = (int) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_threshold', self::DEFAULT_TOTAL_THRESHOLD );
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
			return $this->finish( array(
				'label'       => __( 'Woo: autoloaded options are large', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
				'description' => '<p>' . esc_html( $description ) . '</p>',
				'actions'     => sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( 'https://woocommerce.com/document/optimizing-woocommerce/' ),
					esc_html__( 'Learn how to clean up autoloaded options', 'woocommerce' )
				),
			) );
		}

		return $this->finish( array(
			'label'       => __( 'Woo: autoloaded options size is healthy', 'woocommerce' ),
			'status'      => 'good',
			'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
			'description' => '<p>' . esc_html__( 'Autoloaded options are within healthy bounds.', 'woocommerce' ) . '</p>',
			'actions'     => '',
		) );
	}

	public function query_total_size(): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ('yes','on')" );
	}

	public function query_largest_wc_options(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results(
			"SELECT option_name, LENGTH(option_value) AS size FROM {$wpdb->options} WHERE autoload IN ('yes','on') AND option_name LIKE 'woocommerce\\_%' ORDER BY size DESC LIMIT 5",
			ARRAY_A
		);
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
