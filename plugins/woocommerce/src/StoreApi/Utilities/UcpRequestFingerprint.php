<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\Internal\Utilities\ArrayUtil;

/**
 * Fingerprints a UCP request, binding an `Idempotency-Key` to the payload it was first used with.
 *
 * Covers the method, the resolved route — which carries the cart id — and the body. The body
 * comes from the decoded JSON with keys sorted, not the raw string, so the whitespace and key
 * order a proxy changes when it re-serialises cannot make a genuine retry look like a new request.
 */
class UcpRequestFingerprint {
	/**
	 * Fingerprint a request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return string 64 character hex digest.
	 */
	public static function compute( \WP_REST_Request $request ): string {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();

		self::sort_keys( $params );

		return hash(
			'sha256',
			strtoupper( (string) $request->get_method() ) . "\n" . (string) $request->get_route() . "\n" . (string) wp_json_encode( $params )
		);
	}

	/**
	 * Sort a decoded body by key, at every depth.
	 *
	 * Lists keep their order: there, position carries meaning.
	 *
	 * @param array<int|string, mixed> $data Data to sort in place.
	 * @return void
	 */
	private static function sort_keys( array &$data ): void {
		foreach ( $data as &$value ) {
			if ( is_array( $value ) ) {
				self::sort_keys( $value );
			}
		}
		unset( $value );

		if ( ! ArrayUtil::array_is_list( $data ) ) {
			ksort( $data );
		}
	}
}
