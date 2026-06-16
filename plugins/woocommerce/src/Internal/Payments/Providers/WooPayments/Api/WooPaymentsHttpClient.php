<?php
/**
 * WooPaymentsHttpClient class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api;

use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;
use Automattic\WooCommerce\Internal\Jetpack\JetpackConnection;
use WP_Error;

/**
 * Site-scoped Jetpack-signed WPCOM transport for WooPayments native requests.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsHttpClient implements WooPaymentsHttpClientInterface {

	/**
	 * Tell whether the site has a usable WPCOM/Jetpack connection.
	 *
	 * @return bool
	 */
	public function is_connected(): bool {
		try {
			$manager = JetpackConnection::get_manager();

			return $manager->is_connected() && $manager->has_connected_owner();
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get the connected WPCOM blog ID.
	 *
	 * @return int|null
	 */
	public function get_blog_id(): ?int {
		if ( ! class_exists( 'Jetpack_Options' ) ) {
			return null;
		}

		$blog_id = \Jetpack_Options::get_option( 'id' );

		return is_numeric( $blog_id ) && (int) $blog_id > 0 ? (int) $blog_id : null;
	}

	/**
	 * Send a WPCOM request.
	 *
	 * @param string      $method  HTTP method.
	 * @param string      $path    Site-scoped WPCOM path.
	 * @param string[]    $headers Request headers.
	 * @param string|null $body    Encoded request body.
	 * @param int         $timeout Request timeout.
	 * @return array|WP_Error
	 */
	public function request( string $method, string $path, array $headers = array(), ?string $body = null, int $timeout = 70 ) {
		$site_id = $this->get_blog_id();
		if ( ! $this->is_connected() ) {
			return new WP_Error( 'wcpay_wpcom_not_connected', __( 'Site is not connected to WordPress.com.', 'woocommerce' ) );
		}

		if ( null === $site_id ) {
			return new WP_Error( 'wcpay_blog_id_unavailable', __( 'The WooPayments site ID is unavailable.', 'woocommerce' ) );
		}

		$response = Jetpack_Connection_Client::wpcom_json_api_request_as_blog(
			$path,
			'2',
			array(
				'headers' => $headers,
				'method'  => $method,
				'timeout' => $timeout,
			),
			$body,
			'wpcom'
		);

		return is_array( $response ) || $response instanceof WP_Error
			? $response
			: new WP_Error( 'wcpay_transport_invalid_response', __( 'WooPayments returned an invalid transport response.', 'woocommerce' ) );
	}
}
