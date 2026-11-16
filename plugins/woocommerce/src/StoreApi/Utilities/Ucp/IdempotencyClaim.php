<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities\Ucp;

/**
 * The `Idempotency-Key` a UCP write runs under.
 *
 * `for_request()` settles a request before anything else runs, or hands back the claim the request
 * now owns, which `settle()` stores or releases according to the response.
 */
final class IdempotencyClaim {
	/**
	 * Seconds a client is told to wait before retrying when the key store is unavailable.
	 */
	const RETRY_AFTER_SECONDS = 30;

	/**
	 * Shape of an acceptable key: 22 characters of this alphabet carry the 128 bits the spec
	 * asks for, and 191 is the column width. https://ucp.dev/2026-04-08/specification/signatures/#replay-protection
	 */
	const KEY_PATTERN = '/^[A-Za-z0-9_-]{22,191}\z/';

	/**
	 * Key this request owns.
	 *
	 * @var string
	 */
	private $key;

	/**
	 * Constructor.
	 *
	 * @param string $key Key this request owns.
	 */
	private function __construct( string $key ) {
		$this->key = $key;
	}

	/**
	 * Settle the `Idempotency-Key` a request carries, before the request runs.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param string           $capability UCP capability the route serves, for a refusal's envelope.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response|self|null A response settling the request on its own, the
	 *         claim the request now owns, or null when it runs under no key.
	 */
	public static function for_request( \WP_REST_Request $request, string $capability ) {
		if ( in_array( strtoupper( (string) $request->get_method() ), array( 'GET', 'HEAD' ), true ) ) {
			return null;
		}

		/**
		 * Filters whether UCP idempotency handling runs at all.
		 *
		 * @since 11.4.0
		 *
		 * @param bool $enabled Whether the `Idempotency-Key` header is honoured. Default true.
		 */
		if ( ! apply_filters( 'woocommerce_ucp_idempotency_enabled', true ) ) {
			return null;
		}

		/**
		 * Filters whether a UCP write must carry an `Idempotency-Key`.
		 *
		 * True, the default, matches the UCP specs, and returns 400 for a missing or malformed key.
		 * False honors a key when one is sent and lets a keyless write through.
		 *
		 * @since 11.4.0
		 *
		 * @param bool $strict Whether a key is required. Default true.
		 */
		$strict = (bool) apply_filters( 'woocommerce_ucp_idempotency_strict', true );

		// Case counts: a 22-character key only carries 128 bits when it is not folded.
		$key = trim( (string) $request->get_header( 'Idempotency-Key' ) );

		if ( 1 !== preg_match( self::KEY_PATTERN, $key ) ) {
			if ( ! $strict ) {
				// Not stored when malformed: a key longer than the column would be truncated and
				// could then collide with an unrelated request's.
				return null;
			}

			return Utils::error_response(
				$capability,
				'' === $key ? 'IDEMPOTENCY_KEY_REQUIRED' : 'IDEMPOTENCY_KEY_INVALID',
				'' === $key
					? __( 'The Idempotency-Key header is required.', 'woocommerce' )
					: __( 'The Idempotency-Key header must be 22 to 191 letters, digits, hyphens or underscores.', 'woocommerce' ),
				'recoverable',
				400
			);
		}

		$fingerprint = RequestFingerprint::compute( $request );

		$claimed = IdempotencyStore::try_begin( $key, $fingerprint );

		if ( true === $claimed ) {
			return new self( $key );
		}

		// A key that cannot be stored fails closed, per https://ucp.dev/2026-04-08/specification/signatures/#replay-protection.
		if ( null === $claimed ) {
			return self::store_unavailable( $capability );
		}

		$existing = IdempotencyStore::find( $key );

		// The row went away between the two statements, which a refused write does as it releases
		// its key. It is free again, so the claim is retried rather than reported as held.
		if ( null === $existing ) {
			return self::claim_again( $key, $fingerprint, $capability );
		}

		// Past retention the record no longer answers for its key, whatever it was used for, so
		// this request takes the row over. The sweep that would have dropped it runs daily.
		if ( IdempotencyStore::has_expired( $existing ) ) {
			if ( IdempotencyStore::try_reclaim_expired( $key, $fingerprint ) ) {
				return new self( $key );
			}

			// Zero rows affected is either a competing retry that won, or the sweep deleting
			// the row first. Claiming once more tells them apart.
			return self::claim_again( $key, $fingerprint, $capability );
		}

		if ( (string) $existing['request_fingerprint'] !== $fingerprint ) {
			return Utils::error_response(
				$capability,
				'IDEMPOTENCY_KEY_REUSED',
				__( 'The Idempotency-Key was already used for a different request.', 'woocommerce' ),
				'unrecoverable',
				409
			);
		}

		if ( IdempotencyStore::STATE_COMPLETED !== (string) $existing['state'] ) {
			// A claim past the orphan window belonged to a process that died, so it may be taken
			// over rather than waiting for the sweep. Only one of several callers wins.
			if ( IdempotencyStore::try_take_over_stale( $key, IdempotencyStore::STALE_CLAIM_SECONDS ) ) {
				return new self( $key );
			}

			// Same race as above: the sweep may have removed the orphan between the read and the update.
			return self::claim_again( $key, $fingerprint, $capability );
		}

		return self::replay( $existing );
	}

	/**
	 * Claim a key that was just found free, once; a loss here means another retry holds it.
	 *
	 * @param string $key Idempotency key.
	 * @param string $fingerprint Request fingerprint.
	 * @param string $capability UCP capability the route serves.
	 * @return \WP_REST_Response|self
	 */
	private static function claim_again( string $key, string $fingerprint, string $capability ) {
		$claimed = IdempotencyStore::try_begin( $key, $fingerprint );

		if ( true === $claimed ) {
			return new self( $key );
		}

		return null === $claimed ? self::store_unavailable( $capability ) : self::in_flight( $capability );
	}

	/**
	 * Record the outcome of the request this claim belongs to, and hand the response back.
	 *
	 * @param \WP_REST_Response $response Response the request produced.
	 * @return \WP_REST_Response The same response.
	 */
	public function settle( \WP_REST_Response $response ): \WP_REST_Response {
		if ( self::is_replayable( $response ) ) {
			IdempotencyStore::finalize( $this->key, $response->get_status(), $response->get_data() );

			return $response;
		}

		// A failed request is released.
		IdempotencyStore::delete( $this->key );

		return $response;
	}

	/**
	 * Whether a response may be stored for replay.
	 *
	 * @param \WP_REST_Response $response Response to inspect.
	 * @return bool
	 */
	private static function is_replayable( \WP_REST_Response $response ): bool {
		$status = $response->get_status();

		if ( $status < 200 || $status >= 300 ) {
			return false;
		}

		$data = $response->get_data();

		return ! is_array( $data ) || ! isset( $data['ucp']['status'] ) || 'error' !== $data['ucp']['status'];
	}

	/**
	 * Replay a stored response.
	 *
	 * @param array<string, mixed> $existing Stored record.
	 * @return \WP_REST_Response
	 */
	private static function replay( array $existing ): \WP_REST_Response {
		$response = new \WP_REST_Response(
			json_decode( (string) $existing['response_body'], true ),
			isset( $existing['response_status'] ) ? (int) $existing['response_status'] : 200
		);

		$response->header( 'Idempotency-Replayed', 'true' );

		return $response;
	}

	/**
	 * Refuse a key another request is still working on.
	 *
	 * @param string $capability UCP capability the route serves.
	 * @return \WP_REST_Response
	 */
	private static function in_flight( string $capability ): \WP_REST_Response {
		return Utils::error_response(
			$capability,
			'IDEMPOTENCY_CONFLICT',
			__( 'A request with this Idempotency-Key is still being processed.', 'woocommerce' ),
			'recoverable',
			409
		);
	}

	/**
	 * Refuse a write whose key could not be recorded, rather than run it undeduplicated.
	 *
	 * @param string $capability UCP capability the route serves.
	 * @return \WP_REST_Response
	 */
	private static function store_unavailable( string $capability ): \WP_REST_Response {
		$response = Utils::error_response(
			$capability,
			'IDEMPOTENCY_STORE_UNAVAILABLE',
			__( 'The Idempotency-Key could not be recorded. Retry the request later.', 'woocommerce' ),
			'recoverable',
			503
		);

		$response->header( 'Retry-After', (string) self::RETRY_AFTER_SECONDS );

		return $response;
	}
}
