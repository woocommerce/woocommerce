<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities\Ucp;

/**
 * Fingerprints a UCP request, binding an `Idempotency-Key` to the payload it was first used with.
 *
 * Covers the method, the resolved route — which carries the cart id — and the body. The body
 * enters the hash as the raw bytes `Content-Digest` covers, per
 * https://ucp.dev/2026-04-08/specification/signatures/#replay-protection.
 */
class RequestFingerprint {
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
		return hash(
			'sha256',
			strtoupper( (string) $request->get_method() ) . "\n" . (string) $request->get_route() . "\n" . (string) $request->get_body()
		);
	}
}
