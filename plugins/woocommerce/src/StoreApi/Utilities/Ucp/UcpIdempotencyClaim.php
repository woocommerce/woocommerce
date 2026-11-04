<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities\Ucp;

/**
 * The `Idempotency-Key` a UCP write runs under.
 *
 * `for_request()` settles a request before anything else runs — a replay, a conflict or a
 * refused key is answered without loading a session — or hands back the claim the request
 * now owns, which `settle()` stores or releases according to the response.
 */
final class UcpIdempotencyClaim {
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
	 * Reads only: a `GET` changes nothing, so a retry needs no key and is never stored.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param string           $capability UCP capability the route serves, for a refusal's envelope.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response|self|null A response settling the request on its own, the
	 *         claim the request now owns, or null when it runs under no key: a read, a
	 *         keyless write in lenient mode, or a disabled gate.
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
		 * True, the default, matches the UCP OpenAPI document, which declares the header
		 * required on every mutating cart operation: a missing or malformed key is refused
		 * at 400. False honours a key when one is sent and lets a keyless write through.
		 *
		 * @since 11.4.0
		 *
		 * @param bool $strict Whether a key is required. Default true.
		 */
		$strict = (bool) apply_filters( 'woocommerce_ucp_idempotency_strict', true );

		// RFC 4122 calls a UUID case-insensitive but `wp_is_uuid()` matches lower case only, so
		// an upper-case key would be refused. Normalising also stops two spellings claiming two rows.
		$key = strtolower( trim( (string) $request->get_header( 'Idempotency-Key' ) ) );

		if ( '' === $key || ! wp_is_uuid( $key ) ) {
			if ( ! $strict ) {
				// Not stored when it is not a UUID: the column is bounded, so a longer key
				// would be truncated and could then collide with an unrelated request's.
				return null;
			}

			return UcpUtils::error_response(
				$capability,
				'' === $key ? 'IDEMPOTENCY_KEY_REQUIRED' : 'IDEMPOTENCY_KEY_INVALID',
				'' === $key
					? __( 'The Idempotency-Key header is required.', 'woocommerce' )
					: __( 'The Idempotency-Key header must be a UUID.', 'woocommerce' ),
				'recoverable',
				400
			);
		}

		$fingerprint = UcpRequestFingerprint::compute( $request );

		$claimed = UcpIdempotencyStore::try_begin( $key, $fingerprint );

		if ( true === $claimed ) {
			return new self( $key );
		}

		// Storage is unreachable, which the store logs, so this write cannot be deduplicated. It
		// proceeds unprotected rather than refused, since a `409` here would refuse every retry too.
		if ( null === $claimed ) {
			return null;
		}

		$existing = UcpIdempotencyStore::find( $key );

		// The row went away between the two statements, which a refused write does as it releases
		// its key. It is free again, so the claim is retried rather than reported as held.
		if ( null === $existing ) {
			return self::claim_again( $key, $fingerprint, $capability );
		}

		// Past retention the record no longer answers for its key, whatever it was used for, so
		// this request takes the row over. The sweep that would have dropped it runs daily.
		if ( UcpIdempotencyStore::has_expired( $existing ) ) {
			if ( UcpIdempotencyStore::try_reclaim_expired( $key, $fingerprint ) ) {
				return new self( $key );
			}

			return self::in_flight( $capability );
		}

		if ( (string) $existing['request_fingerprint'] !== $fingerprint ) {
			return UcpUtils::error_response(
				$capability,
				'IDEMPOTENCY_KEY_REUSED',
				__( 'The Idempotency-Key was already used for a different request.', 'woocommerce' ),
				'unrecoverable',
				409
			);
		}

		if ( UcpIdempotencyStore::STATE_COMPLETED !== (string) $existing['state'] ) {
			// A claim past the orphan window belonged to a process that died, so it may be taken
			// over rather than waiting for the sweep. Only one of several callers wins.
			if ( UcpIdempotencyStore::try_take_over_stale( $key, UcpIdempotencyStore::STALE_CLAIM_SECONDS ) ) {
				return new self( $key );
			}

			return self::in_flight( $capability );
		}

		return self::replay( $existing );
	}

	/**
	 * Claim a key that was just found free, once; a second loss means another retry won it.
	 *
	 * @param string $key Idempotency key.
	 * @param string $fingerprint Request fingerprint.
	 * @param string $capability UCP capability the route serves.
	 * @return \WP_REST_Response|self
	 */
	private static function claim_again( string $key, string $fingerprint, string $capability ) {
		return true === UcpIdempotencyStore::try_begin( $key, $fingerprint ) ? new self( $key ) : self::in_flight( $capability );
	}

	/**
	 * Record the outcome of the request this claim belongs to, and hand the response back.
	 *
	 * @param \WP_REST_Response $response Response the request produced.
	 * @return \WP_REST_Response The same response.
	 */
	public function settle( \WP_REST_Response $response ): \WP_REST_Response {
		if ( self::is_replayable( $response ) ) {
			UcpIdempotencyStore::finalize( $this->key, $response->get_status(), $response->get_data() );

			return $response;
		}

		// A failed request is released.
		UcpIdempotencyStore::delete( $this->key );

		return $response;
	}

	/**
	 * Whether a response may be stored for replay.
	 *
	 * A refusal is not: UCP reports one at 200 with `ucp.status` set to `error`, and the route
	 * rolls back. Nothing happened, so a retry must run again — once the stock is back or the
	 * quantity corrected, a stored refusal would stand in the corrected request's way.
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
	 * The original status is repeated, since that is the contract the first call published;
	 * the `Idempotency-Replayed` header is what sets a replay apart from a fresh answer.
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
		return UcpUtils::error_response(
			$capability,
			'IDEMPOTENCY_CONFLICT',
			__( 'A request with this Idempotency-Key is still being processed.', 'woocommerce' ),
			'recoverable',
			409
		);
	}
}
