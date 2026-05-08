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

	public function get_id(): string {
		return 'woocommerce_' . self::ID;
	}

	public function get_label(): string {
		return __( 'Woo: cart fragments load policy', 'woocommerce' );
	}

	public function is_async(): bool {
		return true;
	}

	public function run(): array {
		$response = wp_remote_get( home_url( '/' ), array( 'sslverify' => false, 'timeout' => 10 ) );
		if ( is_wp_error( $response ) ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'Cart fragments loopback failed: ' . $response->get_error_message(), array( 'source' => 'site-health' ) );
			}
			return $this->finish( array(
				'label'       => __( 'Woo: could not run the cart fragments check', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'gray' ),
				'description' => '<p>' . esc_html__( 'The loopback request to the home page failed, so the cart fragments check could not run.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			) );
		}

		$body   = (string) wp_remote_retrieve_body( $response );
		$loaded = ( false !== strpos( $body, 'wc-cart-fragments-js' ) );

		if ( $loaded ) {
			return $this->finish( array(
				'label'       => __( 'Woo: cart fragments are loaded on the home page', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
				'description' => '<p>' . esc_html__( 'The cart fragments script makes an AJAX request on every front-end request that loads it. If you do not need cart counts on non-cart pages, suppress it via the woocommerce_cart_fragments_should_load filter.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			) );
		}

		return $this->finish( array(
			'label'       => __( 'Woo: cart fragments are not loaded site-wide', 'woocommerce' ),
			'status'      => 'good',
			'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
			'description' => '<p>' . esc_html__( 'Cart fragments are not enqueued on the home page.', 'woocommerce' ) . '</p>',
			'actions'     => '',
		) );
	}

	private function finish( array $base ): array {
		$base['test'] = $this->get_id();
		if ( ! apply_filters( 'woocommerce_site_health_check_' . self::ID . '_enabled', true ) ) {
			return array();
		}
		return (array) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_result', $base );
	}
}
