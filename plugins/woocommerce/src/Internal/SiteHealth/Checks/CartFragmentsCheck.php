<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Checks;

defined( 'ABSPATH' ) || exit;

/**
 * Checks whether wc-cart-fragments script is loaded site-wide via a loopback request.
 *
 * @internal
 */
class CartFragmentsCheck {

	public const ID = 'cart_fragments_sitewide';

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
		return __( 'WooCommerce cart fragments load policy', 'woocommerce' );
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
		$response = wp_remote_get(
			home_url( '/' ),
			array(
				'sslverify' => false,
				'timeout'   => 10,
			)
		);
		if ( is_wp_error( $response ) ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'Cart fragments loopback failed: ' . $response->get_error_message(), array( 'source' => 'site-health' ) );
			}
			return $this->finish(
				array(
					'label'       => __( 'WooCommerce could not run the cart fragments check', 'woocommerce' ),
					'status'      => 'recommended',
					'badge'       => array(
						'label' => __( 'Performance', 'woocommerce' ),
						'color' => 'gray',
					),
					'description' => '<p>' . esc_html__( 'The loopback request to the home page failed, so the cart fragments check could not run.', 'woocommerce' ) . '</p>',
					'actions'     => '',
				)
			);
		}

		$body   = (string) wp_remote_retrieve_body( $response );
		$loaded = ( false !== strpos( $body, 'wc-cart-fragments-js' ) );

		if ( $loaded ) {
			return $this->finish(
				array(
					'label'       => __( 'WooCommerce cart fragments are loaded on the home page', 'woocommerce' ),
					'status'      => 'recommended',
					'badge'       => array(
						'label' => __( 'Performance', 'woocommerce' ),
						'color' => 'orange',
					),
					'description' => '<p>' . esc_html__( 'The cart fragments script makes an AJAX request on every front-end request that loads it. If you do not need cart counts on non-cart pages, suppress it via the woocommerce_cart_fragments_should_load filter.', 'woocommerce' ) . '</p>',
					'actions'     => '',
				)
			);
		}

		return $this->finish(
			array(
				'label'       => __( 'WooCommerce cart fragments are not loaded site-wide', 'woocommerce' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'Performance', 'woocommerce' ),
					'color' => 'green',
				),
				'description' => '<p>' . esc_html__( 'Cart fragments are not enqueued on the home page.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			)
		);
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
}
