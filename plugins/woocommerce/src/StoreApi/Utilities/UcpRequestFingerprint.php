<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

/**
 * Fingerprints a UCP request, so that an `Idempotency-Key` can be bound to the payload it
 * was first used with.
 *
 * The fingerprint covers the method, the resolved route — which carries the cart id — and
 * the request body. The body is taken from the decoded JSON with keys sorted rather than
 * from the raw string, so whitespace and key order, as a proxy re-serializing a payload
 * changes them, do not make a genuine retry look like a different request.
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

		if ( ! self::is_list( $data ) ) {
			ksort( $data );
		}
	}

	/**
	 * Whether an array is a list, with keys 0 to n-1 in order.
	 *
	 * Stands in for `array_is_list()`, which needs PHP 8.1.
	 *
	 * @param array<int|string, mixed> $data Array to inspect.
	 * @return bool
	 */
	private static function is_list( array $data ): bool {
		$expected = 0;

		foreach ( $data as $key => $ignored ) {
			if ( $key !== $expected ) {
				return false;
			}

			++$expected;
		}

		return true;
	}
}
