<?php
/**
 * WooPaymentsHttpClientInterface class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api;

/**
 * Provider-scoped WPCOM transport seam for WooPayments native requests.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
interface WooPaymentsHttpClientInterface {

	/**
	 * Tell whether the site has a usable WPCOM/Jetpack connection.
	 *
	 * @return bool
	 */
	public function is_connected(): bool;

	/**
	 * Get the connected WPCOM blog ID.
	 *
	 * @return int|null
	 */
	public function get_blog_id(): ?int;

	/**
	 * Send a WPCOM request.
	 *
	 * @param string      $method  HTTP method.
	 * @param string      $path    Site-scoped WPCOM path.
	 * @param string[]    $headers Request headers.
	 * @param string|null $body    Encoded request body.
	 * @param int         $timeout Request timeout.
	 * @return mixed
	 */
	public function request( string $method, string $path, array $headers = array(), ?string $body = null, int $timeout = 70 );
}
