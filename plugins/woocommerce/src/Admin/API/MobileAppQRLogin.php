<?php
/**
 * REST API Mobile App QR Login controller.
 *
 * Handles requests to generate and exchange QR login tokens for direct mobile
 * app authentication via Application Passwords. Token generation is gated on
 * the `manage_woocommerce` capability (administrators and shop managers by
 * default).
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\API\RateLimits\QRLoginRateLimits;

defined( 'ABSPATH' ) || exit;

/**
 * Mobile App QR Login controller.
 *
 * @internal
 */
class MobileAppQRLogin extends \WC_REST_Data_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'mobile-app';

	/**
	 * Token TTL in seconds (5 minutes).
	 */
	const TOKEN_TTL = 300;

	/**
	 * Transient prefix for QR login tokens.
	 */
	const TOKEN_TRANSIENT_PREFIX = '_wc_qr_login_token_';

	/**
	 * Max tokens per user per 15-minute window.
	 */
	const MAX_TOKENS_PER_WINDOW = 5;

	/**
	 * Max exchange attempts per valid token per 15-minute window.
	 */
	const MAX_EXCHANGE_ATTEMPTS = 10;

	/**
	 * Max invalid-token exchange attempts per IP per 15-minute window.
	 */
	const MAX_INVALID_EXCHANGE_ATTEMPTS = 100;

	/**
	 * Max invalid-token scan attempts per IP per 15-minute window.
	 */
	const MAX_INVALID_SCAN_ATTEMPTS = 100;

	/**
	 * Broad anonymous exchange abuse guard per IP per 15-minute window.
	 */
	const MAX_EXCHANGE_IP_ATTEMPTS = 1000;

	/**
	 * Option prefix for database-backed atomic token claims.
	 */
	const CLAIM_OPTION_PREFIX = '_wc_qr_login_claim_';

	/**
	 * Scan-claim option prefix. Independent from `CLAIM_OPTION_PREFIX` so the
	 * scan and exchange mutexes can't deadlock each other; they protect
	 * different write windows on the same token record.
	 */
	const SCAN_CLAIM_OPTION_PREFIX = '_wc_qr_login_scan_claim_';

	/**
	 * Approval-claim option prefix. Prevents concurrent number choices from
	 * racing the one-strike scanned -> approved/rejected transition.
	 */
	const APPROVE_CLAIM_OPTION_PREFIX = '_wc_qr_login_approve_claim_';

	/**
	 * Stable Application Passwords `app_id` for credentials issued by this
	 * flow. Lets administrators identify QR-issued credentials in the
	 * Application Passwords screen and revoke them in bulk.
	 */
	const APP_ID = '0b540e2f-86b7-4b8a-8e0c-f61e9bfbde59';

	/**
	 * Transient prefix for the "token consumed" record written after a successful
	 * exchange. The wc-admin UI polls a status endpoint that reads this so it can
	 * transition to a confirmation panel and surface the device that signed in.
	 */
	const CONSUMED_TRANSIENT_PREFIX = '_wc_qr_login_consumed_';

	/**
	 * Max status checks per user per 15-minute window. The polling client hits
	 * this every ~2.5s while a QR is on screen; 600/15min ≈ 40/min, comfortably
	 * above the polling rate but tight enough to short-circuit a misbehaving
	 * client or a credential-stuffing scan.
	 */
	const MAX_STATUS_CHECKS_PER_WINDOW = 600;

	/**
	 * Max revoke attempts per user per 15-minute window.
	 */
	const MAX_REVOKE_ATTEMPTS = 10;

	/**
	 * Whitelisted keys for the `device` payload sent by the mobile app on the
	 * scan call. Anything outside this set is dropped before storage.
	 *
	 * `brand` is Android-only (`Build.BRAND`, e.g. "google", "samsung"); iOS
	 * doesn't have a direct analogue and clients that don't have the field
	 * just leave it absent.
	 *
	 * @var string[]
	 */
	const DEVICE_PAYLOAD_KEYS = array( 'os', 'os_version', 'model', 'brand', 'app_version' );

	/**
	 * Maximum length (chars) for any individual sanitized device-payload field.
	 * Defends against accidental or hostile bloat ending up in transients and
	 * the Application Password name.
	 */
	const DEVICE_FIELD_MAX_LENGTH = 64;

	/**
	 * State machine values for the per-token record. Transitions are gated
	 * by an explicit `current_state` check at the top of each handler
	 * (scan/approve/exchange) so the only writers are the handlers themselves.
	 */
	const STATE_PENDING  = 'pending';
	const STATE_SCANNED  = 'scanned';
	const STATE_APPROVED = 'approved';
	const STATE_REJECTED = 'rejected';
	const STATE_EXPIRED  = 'expired';
	const STATE_CONSUMED = 'consumed';

	/**
	 * Pick window after the app scans a QR (seconds). The merchant has this
	 * long to tap the matching number on wc-admin before the session
	 * auto-rejects. Short enough to limit replay; long enough for a confused
	 * user to read the phone, find their browser, and click.
	 */
	const CHALLENGE_TTL_SECONDS = 90;

	/**
	 * Length (bytes pre-bin2hex) of the exchange-grant nonce minted on
	 * approval. The grant gates the final `/qr-login-exchange` call so an
	 * attacker who learned the token can't race the legit app to exchange
	 * after approval. 32 bytes = 64 hex chars = 256 bits of entropy.
	 */
	const EXCHANGE_GRANT_BYTES = 32;

	/**
	 * Invalid exchange grants allowed before the token is terminally rejected.
	 */
	const MAX_INVALID_GRANT_ATTEMPTS = 3;

	/**
	 * Transient prefix mapping `session_id` → `token_hash` so the mobile-side
	 * `/qr-login-session-status` poll can resolve a session id back to the
	 * underlying token record without exposing the original token to the
	 * polling channel.
	 */
	const SESSION_TRANSIENT_PREFIX = '_wc_qr_login_session_';

	/**
	 * Rate limit for /qr-login-scan (per IP per 15 min).
	 */
	const MAX_SCAN_PER_WINDOW = 10;

	/**
	 * Rate limit for /qr-login-approve (per user per 15 min).
	 */
	const MAX_APPROVE_PER_WINDOW = 20;

	/**
	 * Rate limit for /qr-login-session-status (per session id per 15 min).
	 *
	 * Accounts for ~2-s polling over a 90-s challenge window plus headroom.
	 */
	const MAX_SESSION_STATUS_PER_WINDOW = 60;

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		// Generate a QR login token (requires authentication and `manage_woocommerce` capability).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/qr-login-token',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'generate_token' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// Exchange a QR login token for Application Password (no authentication required).
		// The device payload is captured at /qr-login-scan time and sourced from the
		// approved record — the exchange call only needs the token + grant nonce.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/qr-login-exchange',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'exchange_token' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'token'          => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						// Soft-required: the handler enforces presence + validity
						// via constant-time comparison and returns a clear
						// `invalid_exchange_grant` 412 if missing. We don't make
						// it `required: true` at the schema layer because that
						// would short-circuit earlier checks (HTTPS, invalid
						// token, rate limit) with a generic WP validation 400
						// before our diagnostic responses can fire.
						'exchange_grant' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// Poll for token status (consumed yet?). Used by wc-admin to transition
		// the modal from "QR shown" to "Signed in successfully on {device}".
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/qr-login-status',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'token' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// Revoke (delete) the Application Password issued by an exchange. The
		// user must own the AP — verified inside the callback via the WP API.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/qr-login-revoke',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'revoke_password' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'uuid' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// Mobile app reports the QR was scanned. Server generates the
		// number-match challenge and returns the *real* number to the app
		// only. Public — token + capability flag are the auth.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/qr-login-scan',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'scan_token' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'token'                    => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						// The device payload is required: it shows up in the
						// merchant's "match this number" device card, in the
						// Application Password name, and in the sign-in
						// notification email. Mobile clients always have these
						// fields available from the platform SDK (Build.MODEL,
						// UIDevice.current.model, etc.), so requiring the
						// payload at the protocol level keeps every downstream
						// surface honest.
						'device'                   => array(
							'required'   => true,
							'type'       => 'object',
							'properties' => array(
								'os'          => array( 'type' => 'string' ),
								'os_version'  => array( 'type' => 'string' ),
								'model'       => array( 'type' => 'string' ),
								'brand'       => array( 'type' => 'string' ),
								'app_version' => array( 'type' => 'string' ),
							),
						),
						// Capability flag the mobile app sets to advertise that
						// it implements the number-matching protocol. Reserved
						// for future protocol bumps that might gate behavior on
						// further capability bits.
						'supports_number_matching' => array(
							'required' => true,
							'type'     => 'boolean',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// Merchant taps a number on wc-admin. Server validates against the
		// stored real number with hash_equals; correct → approved, wrong →
		// rejected (terminal, no retry).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/qr-login-approve',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'approve_token' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'token'  => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'choice' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// Mobile app polls this with the session id returned from /scan.
		// While in `scanned` we say so; on `approved` we hand over the
		// short-lived `exchange_grant` nonce required by the final
		// /qr-login-exchange call.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/qr-login-session-status',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_session_status' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'session_id' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'token_hash' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// Cheap up-front capability check so wc-admin can render a permanently
		// disabled QR card (rather than spin up a token request that will fail)
		// when application passwords are unavailable on this site. Same gate as
		// the token endpoint so a subscriber cannot probe site configuration.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/qr-login-availability',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_availability' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		parent::register_routes();
	}

	/**
	 * Check whether the current user can generate a QR login token.
	 *
	 * Requires the `manage_woocommerce` capability, which covers administrators and
	 * shop managers out of the box. The check is deliberately explicit (not routed
	 * through `wc_rest_check_manager_permissions()`) so it cannot be loosened by the
	 * `woocommerce_rest_check_permissions` filter that other Admin API endpoints share.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request The REST request (unused).
	 * @return \WP_Error|bool True if the user has the required capability, WP_Error otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		unset( $request );
		// Parameter required by WP REST contract but unused here.

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'woocommerce_rest_cannot_view',
				__( 'Sorry, you are not allowed to generate a mobile app QR login token.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Check if Application Passwords are available.
	 *
	 * @return bool
	 */
	private function are_application_passwords_available() {
		return function_exists( 'wp_is_application_passwords_available' )
			&& wp_is_application_passwords_available();
	}

	/**
	 * Return a REST response carrying WordPress' no-cache headers.
	 *
	 * @param array<string, mixed> $data Response payload.
	 * @return \WP_REST_Response
	 */
	private function rest_ensure_nocache_response( array $data ): \WP_REST_Response {
		$response = rest_ensure_response( $data );

		foreach ( wp_get_nocache_headers() as $header_name => $header_value ) {
			if ( false === $header_value ) {
				continue;
			}

			$response->header( $header_name, (string) $header_value );
		}

		return $response;
	}

	/**
	 * Reason codes returned by `/qr-login-availability` so wc-admin can
	 * tailor the disabled card to the specific cause.
	 */
	const AVAILABILITY_REASON_HTTPS_REQUIRED        = 'https_required';
	const AVAILABILITY_REASON_AP_UNSUPPORTED        = 'application_passwords_unsupported';
	const AVAILABILITY_REASON_AP_DISABLED_BY_FILTER = 'application_passwords_disabled_by_filter';

	/**
	 * Report whether QR login is currently available on this site.
	 *
	 * Lets wc-admin render a permanently-disabled QR card with the right
	 * explanation up-front, instead of mounting `<QRDirectLoginCode />`,
	 * spinning, calling `/qr-login-token`, and only then showing a generic
	 * error. The reason code is the heuristic best we can do without each
	 * security plugin self-identifying:
	 *
	 *  - `https_required` — `is_ssl()` is false or the raw/final `siteurl` is
	 *    `http://`. The most common cause is a local dev environment;
	 *    production sites without HTTPS can't use QR login at all.
	 *  - `application_passwords_unsupported` — WordPress core's own support
	 *    gate (`wp_is_application_passwords_supported()`) returns false.
	 *    Ships true on every modern WP host that has SSL or is local; false
	 *    here typically means the site is non-local + non-SSL.
	 *  - `application_passwords_disabled_by_filter` — the WP support gate
	 *    passes, but the `wp_is_application_passwords_available` filter
	 *    returns false. This is the case where a security plugin (Wordfence,
	 *    Solid Security, etc.) or a custom code snippet has explicitly
	 *    disabled application passwords. We can't name the exact source from
	 *    the filter alone; the docs link in the merchant-facing UI covers it.
	 *
	 * `nocache_headers()` so an upstream cache cannot pin a stale
	 * "unavailable" response for a site that just installed an HTTPS cert.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request The REST request (unused).
	 * @return \WP_REST_Response
	 */
	public function get_availability( $request ): \WP_REST_Response {
		unset( $request );

		nocache_headers();

		$site_url     = $this->get_secure_site_url();
		$https_ok     = ! is_wp_error( $site_url );
		$ap_supported = function_exists( 'wp_is_application_passwords_supported' )
			&& wp_is_application_passwords_supported();
		$ap_available = $this->are_application_passwords_available();

		$https_ok  = is_ssl() && $https_ok;
		$available = $https_ok && $ap_available;
		$reason    = null;

		if ( ! $available ) {
			if ( ! $https_ok ) {
				$reason = self::AVAILABILITY_REASON_HTTPS_REQUIRED;
			} elseif ( ! $ap_supported ) {
				$reason = self::AVAILABILITY_REASON_AP_UNSUPPORTED;
			} else {
				$reason = self::AVAILABILITY_REASON_AP_DISABLED_BY_FILTER;
			}
		}

		return $this->rest_ensure_nocache_response(
			array(
				'available' => $available,
				'reason'    => $reason,
			)
		);
	}

	/**
	 * Check rate limit for token generation.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if within rate limit.
	 */
	private function check_generation_rate_limit( $user_id ) {
		return QRLoginRateLimits::consume( QRLoginRateLimits::BUCKET_GENERATION, (string) $user_id );
	}

	/**
	 * Broad anonymous abuse guard for token exchange.
	 *
	 * This intentionally has a high ceiling. It is only meant to slow obvious
	 * unauthenticated floods; valid-token and invalid-token traffic use separate
	 * lower buckets so a few random requests from a shared proxy IP cannot block
	 * legitimate QR login exchanges.
	 *
	 * @return bool True if within rate limit.
	 */
	private function check_exchange_ip_rate_limit() {
		return QRLoginRateLimits::consume( QRLoginRateLimits::BUCKET_EXCHANGE_IP, $this->get_client_ip() );
	}

	/**
	 * Check rate limit for random/nonexistent exchange tokens.
	 *
	 * @return bool True if within rate limit.
	 */
	private function check_invalid_exchange_rate_limit() {
		return QRLoginRateLimits::consume( QRLoginRateLimits::BUCKET_INVALID_EXCHANGE, $this->get_client_ip() );
	}

	/**
	 * Check rate limit for random/nonexistent scan tokens.
	 *
	 * @return bool True if within rate limit.
	 */
	private function check_invalid_scan_rate_limit() {
		return QRLoginRateLimits::consume( QRLoginRateLimits::BUCKET_INVALID_SCAN, $this->get_client_ip() );
	}

	/**
	 * Check rate limit for exchange attempts against a valid token.
	 *
	 * @param string $token_hash SHA-256 hash of the plaintext token.
	 * @return bool True if within rate limit.
	 */
	private function check_valid_exchange_rate_limit( $token_hash ) {
		return QRLoginRateLimits::consume( QRLoginRateLimits::BUCKET_VALID_EXCHANGE, $token_hash );
	}

	/**
	 * Get the client IP address used as the per-IP rate-limit key.
	 *
	 * Uses `REMOTE_ADDR` exclusively. We intentionally do not honor
	 * `HTTP_X_FORWARDED_FOR` here: the exchange endpoint is unauthenticated, and
	 * without a project-wide trusted-proxy list we cannot tell a legitimate
	 * proxy header from an attacker-supplied one. Trusting the first XFF value
	 * would let any client choose a fresh rate-limit bucket per request and
	 * bypass per-IP caps. On sites behind a CDN/load balancer that all clients
	 * share, REMOTE_ADDR is the proxy IP, so exchange uses broad IP throttling
	 * only as an abuse guard and relies on token-scoped buckets for security.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return '';
	}

	/**
	 * Build the option name used for a token exchange claim.
	 *
	 * @param string $token_hash SHA-256 hash of the plaintext token.
	 * @return string
	 */
	private function get_token_claim_key( $token_hash ) {
		return self::CLAIM_OPTION_PREFIX . $token_hash;
	}

	/**
	 * Atomically claim a token for exchange using the options table.
	 *
	 * `add_option()` is backed by a unique option_name constraint, so it works
	 * across PHP workers even on default installs without a persistent object
	 * cache. Stale claims are cleaned only if their stored value still matches
	 * the value this request observed, avoiding deletion of another worker's
	 * fresh claim.
	 *
	 * @param string $token_hash SHA-256 hash of the plaintext token.
	 * @param int    $expires_at Unix timestamp when the token expires.
	 * @return bool True if the claim was acquired.
	 */
	private function claim_token_for_exchange( $token_hash, $expires_at ) {
		return $this->claim_token_with_option_key(
			$this->get_token_claim_key( $token_hash ),
			$expires_at
		);
	}

	/**
	 * Atomically claim a token using an option key.
	 *
	 * @param string $claim_key Option key used as the claim mutex.
	 * @param int    $expires_at Unix timestamp when the token expires.
	 * @return bool True if the claim was acquired.
	 */
	private function claim_token_with_option_key( $claim_key, $expires_at ) {
		$claim_expires_at = max( time() + 30, (int) $expires_at );

		if ( add_option( $claim_key, (string) $claim_expires_at, '', false ) ) {
			return true;
		}

		$existing_expires_at = (int) get_option( $claim_key, 0 );
		if ( $existing_expires_at > 0 && $existing_expires_at <= time() ) {
			$this->delete_claim_if_value_matches( $claim_key, (string) $existing_expires_at );
			return add_option( $claim_key, (string) $claim_expires_at, '', false );
		}

		return false;
	}

	/**
	 * Delete a claim option only if it still has the value this request observed.
	 *
	 * @param string $claim_key            Option key used as the claim mutex.
	 * @param string $observed_claim_value Claim expiry value previously read from the option.
	 * @return bool True if the observed stale claim was deleted.
	 */
	private function delete_claim_if_value_matches( $claim_key, $observed_claim_value ) {
		global $wpdb;

		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
				$claim_key,
				$observed_claim_value
			)
		);

		if ( false === $result ) {
			wc_get_logger()->warning(
				sprintf(
					'QR login stale-claim cleanup query failed for %s: %s',
					$claim_key,
					$wpdb->last_error
				),
				array( 'source' => 'mobile-app-qr-login' )
			);
		}

		wp_cache_delete( $claim_key, 'options' );

		return (int) $result > 0;
	}

	/**
	 * Release a token exchange claim.
	 *
	 * @param string $token_hash SHA-256 hash of the plaintext token.
	 * @return void
	 */
	private function release_token_exchange_claim( $token_hash ) {
		delete_option( $this->get_token_claim_key( $token_hash ) );
	}

	/**
	 * Atomically claim a token for scan. Mirrors `claim_token_for_exchange()`
	 * (same `add_option()` unique-constraint mutex, same staleness recovery)
	 * but uses its own option key so the scan and exchange windows are
	 * independent.
	 *
	 * Without this, two concurrent `/qr-login-scan` requests both pass the
	 * `state === pending` gate, both write a fresh challenge, and the last
	 * writer wins — leaving the loser's session_id silently orphaned and
	 * pointing at the wrong challenge.
	 *
	 * @param string $token_hash SHA-256 hash of the plaintext token.
	 * @param int    $expires_at Unix timestamp when the token expires.
	 * @return bool True if the claim was acquired.
	 */
	private function claim_token_for_scan( $token_hash, $expires_at ) {
		return $this->claim_token_with_option_key(
			self::SCAN_CLAIM_OPTION_PREFIX . $token_hash,
			$expires_at
		);
	}

	/**
	 * Release a token scan claim owned by this request.
	 *
	 * @param string $token_hash SHA-256 hash of the plaintext token.
	 * @return void
	 */
	private function release_token_scan_claim( $token_hash ) {
		delete_option( self::SCAN_CLAIM_OPTION_PREFIX . $token_hash );
	}

	/**
	 * Atomically claim a token for approval.
	 *
	 * @param string $token_hash SHA-256 hash of the plaintext token.
	 * @param int    $expires_at Unix timestamp when the claim should expire.
	 * @return bool True if the claim was acquired.
	 */
	private function claim_token_for_approval( $token_hash, $expires_at ) {
		return $this->claim_token_with_option_key(
			self::APPROVE_CLAIM_OPTION_PREFIX . $token_hash,
			$expires_at
		);
	}

	/**
	 * Release a token approval claim owned by this request.
	 *
	 * @param string $token_hash SHA-256 hash of the plaintext token.
	 * @return void
	 */
	private function release_token_approval_claim( $token_hash ) {
		delete_option( self::APPROVE_CLAIM_OPTION_PREFIX . $token_hash );
	}

	/**
	 * Get the remaining storage TTL for a token record.
	 *
	 * @param array<string, mixed> $token_data Token record.
	 * @return int Remaining TTL in seconds.
	 */
	private function get_token_record_ttl( array $token_data ) {
		return max(
			1,
			isset( $token_data['expires_at'] ) ? (int) $token_data['expires_at'] - time() : self::TOKEN_TTL
		);
	}

	/**
	 * Delete the session-id to token-hash mapping for a token record.
	 *
	 * @param array<string, mixed> $token_data Token record.
	 * @return void
	 */
	private function delete_session_mapping_for_record( array $token_data ) {
		if ( empty( $token_data['challenge']['session_id'] ) ) {
			return;
		}

		delete_transient(
			self::SESSION_TRANSIENT_PREFIX . hash( 'sha256', (string) $token_data['challenge']['session_id'] )
		);
	}

	/**
	 * Validate that the configured site URL is HTTPS and return it.
	 *
	 * `is_ssl()` only tells us the current REQUEST is HTTPS — it says nothing about
	 * the canonical site URL WordPress is configured to advertise. `get_site_url()`
	 * itself is also insufficient because it passes its result through
	 * `set_url_scheme()`, which rewrites the scheme to match `is_ssl()` — so
	 * `get_site_url()` will return `https://…` whenever the request happens to be
	 * HTTPS, masking a stale `http://` `siteurl` option underneath. We therefore
	 * check the RAW stored option, which is what reflects admin configuration
	 * and what shows up in reset-password emails, webhooks, canonical redirects,
	 * etc. If that is `http://`, a misconfigured proxy that terminated TLS before
	 * reaching PHP could still cause this endpoint to hand the mobile app a cleartext
	 * site URL for the token-exchange POST.
	 *
	 * We deliberately reject (rather than silently normalizing to `https://`)
	 * because:
	 *   1. The misconfig usually affects other things (reset-password emails,
	 *      webhooks, canonical redirects). Failing loudly surfaces it.
	 *   2. Normalizing assumes the site actually serves HTTPS on the same host,
	 *      which we cannot verify from within a single request.
	 *   3. A 500 is strictly safer than a leaky success.
	 *
	 * @return string|\WP_Error The HTTPS site URL, or a WP_Error if it is not HTTPS.
	 */
	private function get_secure_site_url() {
		// Raw option: what the admin actually configured, before `set_url_scheme()`
		// inside `get_site_url()` normalizes it based on the current request's scheme.
		$raw_site_url = get_option( 'siteurl' );
		$raw_scheme   = is_string( $raw_site_url ) ? wp_parse_url( $raw_site_url, PHP_URL_SCHEME ) : null;

		if ( 'https' !== $raw_scheme ) {
			return new \WP_Error(
				'insecure_site_url',
				__( 'QR login cannot be used because the site URL is not configured for HTTPS. Please update the WordPress Address (URL) in Settings → General to use https://.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		// Use get_site_url() for the returned value so any scheme normalization
		// or filtering that WordPress applies downstream is preserved, then
		// validate the final value too. A plugin can still filter `site_url`
		// after the raw option check above; never hand the mobile app an
		// HTTP exchange target.
		$site_url     = get_site_url();
		$final_scheme = wp_parse_url( $site_url, PHP_URL_SCHEME );

		if ( 'https' !== $final_scheme ) {
			return new \WP_Error(
				'insecure_site_url',
				__( 'QR login cannot be used because the site URL is not configured for HTTPS. Please update the WordPress Address (URL) in Settings → General to use https://.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return $site_url;
	}

	/**
	 * Generate a QR login token.
	 *
	 * Creates a short-lived one-time token that can be exchanged for an Application
	 * Password by the mobile app. The caller is assumed to have already passed the
	 * `manage_woocommerce` capability check in `get_items_permissions_check()`.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function generate_token( $request ) {
		unset( $request );
		// Parameter required by WP REST contract but unused here.

		// Check HTTPS.
		if ( ! is_ssl() ) {
			return new \WP_Error(
				'ssl_required',
				__( 'QR login requires an HTTPS connection.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		// Verify the canonical site URL is HTTPS — is_ssl() alone is not enough
		// when WordPress is behind a misconfigured proxy.
		$site_url = $this->get_secure_site_url();
		if ( is_wp_error( $site_url ) ) {
			return $site_url;
		}

		// Check Application Passwords are available.
		if ( ! $this->are_application_passwords_available() ) {
			return new \WP_Error(
				'application_passwords_unavailable',
				__( 'Application Passwords are not available on this site.', 'woocommerce' ),
				array( 'status' => 501 )
			);
		}

		// Check rate limit.
		if ( ! $this->check_generation_rate_limit( get_current_user_id() ) ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				__( 'Too many QR login requests. Please try again later.', 'woocommerce' ),
				array( 'status' => 429 )
			);
		}

		// Generate a cryptographically secure token.
		$token      = wp_generate_password( 64, false );
		$token_hash = hash( 'sha256', $token );
		$now        = time();
		$expires_at = $now + self::TOKEN_TTL;

		// Structured state-machine record. Subsequent transitions
		// (scan/approve/exchange) gate themselves on the current state at the
		// top of each handler.
		$token_data = array(
			'state'      => self::STATE_PENDING,
			'created_at' => $now,
			'state_at'   => $now,
			'user_id'    => get_current_user_id(),
			'site_url'   => $site_url,
			'expires_at' => $expires_at,
		);

		set_transient( self::TOKEN_TRANSIENT_PREFIX . $token_hash, $token_data, self::TOKEN_TTL );

		// Build the QR URL (deep link for the mobile app).
		$qr_url = sprintf(
			'woocommerce://qr-login?token=%s&siteUrl=%s',
			rawurlencode( $token ),
			rawurlencode( $site_url )
		);

		return rest_ensure_response(
			array(
				'qr_url'     => $qr_url,
				'expires_at' => $expires_at,
				'ttl'        => self::TOKEN_TTL,
			)
		);
	}

	/**
	 * Exchange a QR login token for an Application Password.
	 *
	 * This endpoint does not require authentication — the token serves
	 * as the authentication mechanism.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function exchange_token( $request ) {
		// Refuse to return credentials over a non-HTTPS request.
		if ( ! is_ssl() ) {
			return new \WP_Error(
				'ssl_required',
				__( 'QR login requires an HTTPS connection.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		// Refuse to return credentials bound to a non-HTTPS site URL — see
		// get_secure_site_url() for rationale. A token that was minted while the
		// siteurl was still https:// but has since been changed to http:// should
		// also be refused here.
		$site_url = $this->get_secure_site_url();
		if ( is_wp_error( $site_url ) ) {
			return $site_url;
		}

		// Defensive sanitize even though the REST `sanitize_callback` already
		// did so — guards against future refactors that bypass the callback.
		$token      = sanitize_text_field( (string) $request->get_param( 'token' ) );
		$token_hash = hash( 'sha256', $token );
		$key        = self::TOKEN_TRANSIENT_PREFIX . $token_hash;

		$token_data = get_transient( $key );
		if ( ! is_array( $token_data ) ) {
			if ( ! $this->check_invalid_exchange_rate_limit() ) {
				return new \WP_Error(
					'rate_limit_exceeded',
					__( 'Too many exchange attempts. Please try again later.', 'woocommerce' ),
					array( 'status' => 429 )
				);
			}

			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or expired QR login token.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		// Broad anonymous abuse guard applies only after token lookup. Random
		// invalid requests use the invalid-token bucket above so they cannot
		// exhaust this shared-IP guard for later valid exchanges behind the same
		// proxy/CDN IP.
		if ( ! $this->check_exchange_ip_rate_limit() ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				__( 'Too many exchange attempts. Please try again later.', 'woocommerce' ),
				array( 'status' => 429 )
			);
		}

		if ( ! $this->check_valid_exchange_rate_limit( $token_hash ) ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				__( 'Too many exchange attempts. Please try again later.', 'woocommerce' ),
				array( 'status' => 429 )
			);
		}

		if ( ! $this->claim_token_for_exchange( $token_hash, isset( $token_data['expires_at'] ) ? (int) $token_data['expires_at'] : time() + self::TOKEN_TTL ) ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or expired QR login token.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		// Re-read after acquiring the database claim in case another process
		// consumed or expired the token while this request was waiting.
		$token_data = get_transient( $key );
		if ( ! is_array( $token_data ) ) {
			$this->release_token_exchange_claim( $token_hash );
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or expired QR login token.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		// Validate token hasn't expired (belt and suspenders with transient TTL).
		if ( ! empty( $token_data['expires_at'] ) && time() >= (int) $token_data['expires_at'] ) {
			delete_transient( $key );
			$this->delete_session_mapping_for_record( $token_data );
			$this->release_token_exchange_claim( $token_hash );
			return new \WP_Error(
				'token_expired',
				__( 'QR login token has expired.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		// Number-matching enforcement: exchange must be preceded by /scan +
		// /approve. Anything other than `approved` (including `pending` —
		// scan was skipped — and `scanned` — scan completed but merchant
		// didn't tap a number yet) is a hard 412.
		$current_state = isset( $token_data['state'] ) ? (string) $token_data['state'] : self::STATE_PENDING;

		if ( self::STATE_APPROVED !== $current_state ) {
			$this->release_token_exchange_claim( $token_hash );
			return new \WP_Error(
				'qr_login_not_approved',
				__( 'This QR login session has not been approved.', 'woocommerce' ),
				array( 'status' => 412 )
			);
		}

		// Constant-time grant comparison. The grant is bound to this token
		// at /approve time and only handed back to the polling app via
		// /session-status, so an attacker who somehow learned the token
		// can't race the legit app to exchange after approval.
		$submitted_grant = (string) $request->get_param( 'exchange_grant' );
		$stored_grant    = isset( $token_data['exchange_grant'] ) ? (string) $token_data['exchange_grant'] : '';

		if ( '' === $stored_grant || ! hash_equals( $stored_grant, $submitted_grant ) ) {
			$invalid_grant_attempts = isset( $token_data['invalid_grant_attempts'] ) ? (int) $token_data['invalid_grant_attempts'] : 0;
			++$invalid_grant_attempts;

			$token_data['invalid_grant_attempts']          = $invalid_grant_attempts;
			$token_data['invalid_grant_last_attempted_at'] = time();

			if ( $invalid_grant_attempts >= self::MAX_INVALID_GRANT_ATTEMPTS ) {
				$token_data['state']    = self::STATE_REJECTED;
				$token_data['state_at'] = time();

				wc_get_logger()->warning(
					'QR login rejected after repeated invalid exchange grants',
					array(
						'source'  => 'qr-login-security',
						'user_id' => isset( $token_data['user_id'] ) ? (int) $token_data['user_id'] : 0,
						'ip'      => $this->get_client_ip(),
					)
				);
			}

			set_transient( $key, $token_data, $this->get_token_record_ttl( $token_data ) );
			$this->release_token_exchange_claim( $token_hash );
			return new \WP_Error(
				'invalid_exchange_grant',
				__( 'Invalid exchange grant for this QR login session.', 'woocommerce' ),
				array( 'status' => 412 )
			);
		}//end if

		$user_id = $token_data['user_id'];
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			$this->release_token_exchange_claim( $token_hash );
			return new \WP_Error(
				'user_not_found',
				__( 'User associated with this token no longer exists.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		// Application Passwords may have been disabled after the token was minted.
		if ( ! $this->are_application_passwords_available() ) {
			$this->release_token_exchange_claim( $token_hash );
			return new \WP_Error(
				'application_passwords_unavailable',
				__( 'Application Passwords are not available on this site.', 'woocommerce' ),
				array( 'status' => 501 )
			);
		}

		// Mirror the permission check WP core performs in
		// WP_REST_Application_Passwords_Controller::create_item_permissions_check().
		// Capability or per-user availability filters could have changed in the
		// window between token generation and exchange.
		if ( ! user_can( $user, 'create_app_password', $user_id ) ) {
			$this->release_token_exchange_claim( $token_hash );
			return new \WP_Error(
				'rest_cannot_create_application_passwords',
				__( 'Application passwords are not available for your account. Please contact the site administrator for assistance.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// Source the device payload from the scan record. /qr-login-scan
		// requires a device object, so by the time we reach `approved` it's
		// guaranteed present. Re-sanitize defensively in case the transient
		// was tampered with at the storage layer.
		$device_source = isset( $token_data['challenge']['device'] ) && is_array( $token_data['challenge']['device'] )
			? $token_data['challenge']['device']
			: array();
		$device        = $this->sanitize_device_payload( $device_source );

		// Create an Application Password for the mobile app. The name is
		// descriptive (e.g. "Woo Mobile · iPhone 15 · 2026-04-28") so the user
		// can identify it later in Users → Profile → Application Passwords.
		$app_password_result = \WP_Application_Passwords::create_new_application_password(
			$user_id,
			array(
				'name'   => $this->format_application_password_name( $device ),
				'app_id' => self::APP_ID,
			)
		);

		if ( is_wp_error( $app_password_result ) ) {
			wc_get_logger()->error(
				sprintf(
					'QR login: failed to create Application Password for user %d: %s',
					$user_id,
					$app_password_result->get_error_message()
				),
				array( 'source' => 'mobile-app-qr-login' )
			);
			$this->release_token_exchange_claim( $token_hash );
			return new \WP_Error(
				'application_password_failed',
				__( 'Could not create a mobile-app credential. Please try again, or contact your site administrator.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		list( $new_password, $item ) = $app_password_result;

		// Write a "consumed" record so wc-admin's polling client can transition
		// from "QR shown" to "Signed in successfully on {device}" and surface
		// a revoke button. Same TTL as the original token transient — there's
		// no value in keeping this record longer than the modal that polls it.
		$consumed_record = array(
			'consumed_at' => time(),
			'user_id'     => $user_id,
			'ap_uuid'     => $item['uuid'],
			'ap_name'     => $item['name'],
			'device'      => $device,
		);
		set_transient(
			self::CONSUMED_TRANSIENT_PREFIX . $token_hash,
			$consumed_record,
			self::TOKEN_TTL
		);

		// One-shot: consume only after the Application Password has been
		// successfully created and the consumed record is visible to wc-admin's
		// polling client.
		delete_transient( $key );
		$this->delete_session_mapping_for_record( $token_data );
		$this->release_token_exchange_claim( $token_hash );

		// Notify the merchant out-of-band so they're aware of a fresh sign-in
		// even when they aren't currently looking at wc-admin. Wrapped in a
		// try/catch + filter to keep the exchange path uninterrupted if the
		// site's mailer is misconfigured.
		$this->maybe_send_sign_in_notification_email( $user, $consumed_record );

		return rest_ensure_response(
			array(
				'success'              => true,
				'user_login'           => $user->user_login,
				'user_email'           => $user->user_email,
				'user_id'              => $user_id,
				'site_url'             => $site_url,
				'application_password' => $new_password,
				'uuid'                 => $item['uuid'],
			)
		);
	}

	/**
	 * Get the status of a previously generated QR login token.
	 *
	 * Used by the wc-admin UI to poll while the QR is on screen. Returns one of:
	 *   - `pending`  — token transient exists, has not been exchanged yet.
	 *   - `consumed` — token has been exchanged; payload includes the device that
	 *                  signed in and the AP UUID so the UI can render the
	 *                  confirmation panel and (optionally) revoke the AP.
	 *   - `expired`  — neither transient exists, so the token has expired or
	 *                  was never valid for this user.
	 *
	 * The user calling this endpoint must be the same user who minted the token.
	 * That's defense in depth — tokens are 64 random chars and not realistically
	 * guessable, but cross-user status reads should be impossible regardless.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_status( $request ) {
		// Defeat any intermediary cache (Cloudflare, NGINX micro-cache, browser, edge proxy)
		// that might pin this GET to its first response. Polling endpoints are by definition
		// state-bearing — every tick must see the live transient. Returning a stale `scanned`
		// response forever is exactly the symptom we'd see if the cache pins the first hit.
		nocache_headers();

		$user_id = get_current_user_id();

		if ( ! $this->check_status_rate_limit( $user_id ) ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				__( 'Too many QR login status checks. Please try again later.', 'woocommerce' ),
				array( 'status' => 429 )
			);
		}

		$token = (string) $request->get_param( 'token' );
		if ( '' === $token ) {
			return $this->rest_ensure_nocache_response( array( 'status' => 'expired' ) );
		}

		$token_hash = hash( 'sha256', $token );

		// Consumed lookup first — once a token has been exchanged the main
		// transient is deleted, but we keep a one-way breadcrumb at the
		// `_wc_qr_login_consumed_` key so the polling client (which still
		// has the plaintext token) can render the success panel.
		$consumed = get_transient( self::CONSUMED_TRANSIENT_PREFIX . $token_hash );
		if ( is_array( $consumed ) ) {
			if ( ! isset( $consumed['user_id'] ) || (int) $consumed['user_id'] !== (int) $user_id ) {
				return $this->rest_ensure_nocache_response( array( 'status' => 'expired' ) );
			}

			return $this->rest_ensure_nocache_response(
				array(
					'status'      => self::STATE_CONSUMED,
					'consumed_at' => isset( $consumed['consumed_at'] ) ? (int) $consumed['consumed_at'] : null,
					'ap_uuid'     => isset( $consumed['ap_uuid'] ) ? (string) $consumed['ap_uuid'] : null,
					'ap_name'     => isset( $consumed['ap_name'] ) ? (string) $consumed['ap_name'] : null,
					'device'      => isset( $consumed['device'] ) && is_array( $consumed['device'] ) ? $consumed['device'] : array(),
				)
			);
		}

		$record = get_transient( self::TOKEN_TRANSIENT_PREFIX . $token_hash );
		if ( ! is_array( $record ) ) {
			return $this->rest_ensure_nocache_response( array( 'status' => self::STATE_EXPIRED ) );
		}

		// Cross-user defense in depth — same as before.
		if ( ! isset( $record['user_id'] ) || (int) $record['user_id'] !== (int) $user_id ) {
			return $this->rest_ensure_nocache_response( array( 'status' => self::STATE_EXPIRED ) );
		}

		$state = isset( $record['state'] ) ? (string) $record['state'] : self::STATE_PENDING;

		// Rejected / expired states are terminal — surface them directly so
		// wc-admin can render the "Login denied" terminal screen.
		if ( in_array( $state, array( self::STATE_REJECTED, self::STATE_EXPIRED ), true ) ) {
			return $this->rest_ensure_nocache_response( array( 'status' => $state ) );
		}

		if ( ! empty( $record['expires_at'] ) && time() >= (int) $record['expires_at'] ) {
			return $this->rest_ensure_nocache_response( array( 'status' => self::STATE_EXPIRED ) );
		}

		// While in `scanned`, surface the shuffled candidate triple and the
		// device that scanned so wc-admin can render the matching UI. The
		// REAL number is never returned via this endpoint — only the
		// shuffled triple of (real + 2 distractors) is, so an XSS / hostile
		// extension can't read which one is correct from JS state.
		if ( self::STATE_SCANNED === $state ) {
			$challenge = isset( $record['challenge'] ) && is_array( $record['challenge'] ) ? $record['challenge'] : array();
			$numbers   = $this->shuffled_candidate_numbers( $challenge );

			return $this->rest_ensure_nocache_response(
				array(
					'status'     => self::STATE_SCANNED,
					'numbers'    => $numbers,
					'device'     => isset( $challenge['device'] ) && is_array( $challenge['device'] ) ? $challenge['device'] : array(),
					'expires_at' => isset( $challenge['expires_at'] ) ? (int) $challenge['expires_at'] : null,
				)
			);
		}

		// Approved (post-tap, pre-exchange) — surface so a wc-admin tab that
		// reloaded between approve and exchange shows the "Signing in…"
		// transitional state rather than going back to the QR.
		if ( self::STATE_APPROVED === $state ) {
			return $this->rest_ensure_nocache_response( array( 'status' => self::STATE_APPROVED ) );
		}

		// Pending: same shape as before, plus the new `state` field for
		// clients that want to switch on it directly.
		return $this->rest_ensure_nocache_response(
			array(
				'status'     => self::STATE_PENDING,
				'expires_at' => isset( $record['expires_at'] ) ? (int) $record['expires_at'] : null,
			)
		);
	}

	/**
	 * Revoke (delete) the Application Password issued by a QR login exchange.
	 *
	 * The current user must own the AP being revoked — verified via
	 * `WP_Application_Passwords::get_user_application_password()`. We
	 * deliberately do NOT use `current_user_can( 'edit_user', $user_id )`
	 * because that would let a higher-privilege admin revoke another user's AP
	 * here; the QR flow's revoke surface is for "I just authorized this — undo,"
	 * not for site-wide AP management (which lives at Users → Profile).
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function revoke_password( $request ) {
		$user_id = get_current_user_id();

		if ( ! $this->check_revoke_rate_limit( $user_id ) ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				__( 'Too many QR login revoke attempts. Please try again later.', 'woocommerce' ),
				array( 'status' => 429 )
			);
		}

		if ( ! $this->are_application_passwords_available() ) {
			return new \WP_Error(
				'application_passwords_unavailable',
				__( 'Application Passwords are not available on this site.', 'woocommerce' ),
				array( 'status' => 501 )
			);
		}

		$uuid = (string) $request->get_param( 'uuid' );

		// Ownership check: the AP must exist AND belong to the current user.
		$ap = \WP_Application_Passwords::get_user_application_password( $user_id, $uuid );
		if ( ! is_array( $ap ) ) {
			return new \WP_Error(
				'application_password_not_found',
				__( 'No matching Application Password to revoke.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$deleted = \WP_Application_Passwords::delete_application_password( $user_id, $uuid );
		if ( true !== $deleted ) {
			return new \WP_Error(
				'application_password_revoke_failed',
				__( 'Could not revoke the Application Password. Please try again.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'uuid'    => $uuid,
			)
		);
	}

	/**
	 * Whitelist + sanitize the `device` payload sent by the mobile app.
	 *
	 * Returns an array of strings keyed by the whitelisted keys defined in
	 * `DEVICE_PAYLOAD_KEYS`. Anything outside that whitelist is dropped. Each
	 * value is run through `sanitize_text_field()` and capped at
	 * `DEVICE_FIELD_MAX_LENGTH` characters. The function is total — pass `null`
	 * or anything non-array and you get back `array()`.
	 *
	 * @param mixed $device Raw payload from the request.
	 * @return array<string, string>
	 */
	private function sanitize_device_payload( $device ) {
		if ( ! is_array( $device ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( self::DEVICE_PAYLOAD_KEYS as $key ) {
			if ( ! isset( $device[ $key ] ) || ! is_scalar( $device[ $key ] ) ) {
				continue;
			}
			$value = sanitize_text_field( (string) $device[ $key ] );
			if ( '' === $value ) {
				continue;
			}
			if ( strlen( $value ) > self::DEVICE_FIELD_MAX_LENGTH ) {
				$value = substr( $value, 0, self::DEVICE_FIELD_MAX_LENGTH );
			}
			$sanitized[ $key ] = $value;
		}

		return $sanitized;
	}

	/**
	 * Build a descriptive name for the Application Password issued by the QR
	 * login exchange.
	 *
	 * Preferred: `Woo Mobile · iPhone 15 · 2026-04-28` (model + ISO date).
	 * Falls back to `Woo Mobile · iOS · 2026-04-28` when only the OS is known.
	 * The scan endpoint requires at least model or OS. The legacy fallback is
	 * retained as a defensive guard in case stored token data is corrupted.
	 *
	 * The name is what the merchant sees in WP admin → Users → Profile →
	 * Application Passwords, so it should be human-readable, single-line, and
	 * not contain anything that would only make sense to an engineer.
	 *
	 * @param array<string, string> $device Sanitized device payload.
	 * @return string
	 */
	private function format_application_password_name( array $device ): string {
		$model = isset( $device['model'] ) ? trim( $device['model'] ) : '';
		$os    = isset( $device['os'] ) ? trim( $device['os'] ) : '';

		// Prefer model (e.g. "iPhone 15", "Pixel 10"); fall back to the OS
		// label if a particular device build returns an empty MODEL string.
		// Both fields come from the platform SDK on the mobile side and are
		// effectively always populated, but defending against an empty model
		// is cheaper than chasing the edge case at runtime.
		$descriptor = '' !== $model ? $model : $os;
		if ( '' === $descriptor ) {
			return __( 'WooCommerce Mobile App (QR Login)', 'woocommerce' );
		}

		// Use the site's configured timezone so the date the merchant sees in
		// the AP list matches what they'd see in the rest of wp-admin.
		$date = wp_date( 'Y-m-d' );

		/* translators: 1: device descriptor (model or OS, e.g. "iPhone 15"). 2: ISO date the AP was created. */
		return sprintf( __( 'Woo Mobile · %1$s · %2$s', 'woocommerce' ), $descriptor, $date );
	}

	/**
	 * Per-user rate limit for the polling status endpoint.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if within rate limit.
	 */
	private function check_status_rate_limit( $user_id ) {
		return QRLoginRateLimits::consume( QRLoginRateLimits::BUCKET_STATUS, (string) $user_id );
	}

	/**
	 * Per-user rate limit for the revoke endpoint.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if within rate limit.
	 */
	private function check_revoke_rate_limit( $user_id ) {
		return QRLoginRateLimits::consume( QRLoginRateLimits::BUCKET_REVOKE, (string) $user_id );
	}

	/**
	 * Mobile app reports the QR was scanned. Generates the number-match
	 * challenge, marks the state as `scanned`, and returns the *real* number
	 * + a session id back to the app. Public — the token is the auth.
	 *
	 * Hard-break compat: clients that don't send `supports_number_matching`
	 * get 426 Upgrade Required. The Android Task 7 PR adds the flag; older
	 * apps in the wild see a clear "update required" error.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function scan_token( $request ) {
		if ( ! is_ssl() ) {
			return new \WP_Error(
				'ssl_required',
				__( 'QR login requires an HTTPS connection.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		if ( true !== (bool) $request->get_param( 'supports_number_matching' ) ) {
			return new \WP_Error(
				'mobile_app_update_required',
				__( 'This Woo mobile app version is no longer supported for QR sign-in. Please update the app and try again.', 'woocommerce' ),
				array( 'status' => 426 )
			);
		}

		$token      = (string) $request->get_param( 'token' );
		$token_hash = hash( 'sha256', $token );
		$key        = self::TOKEN_TRANSIENT_PREFIX . $token_hash;

		$record = get_transient( $key );
		if ( ! is_array( $record ) ) {
			if ( ! $this->check_invalid_scan_rate_limit() ) {
				return new \WP_Error(
					'rate_limit_exceeded',
					__( 'Too many QR login scans. Please try again later.', 'woocommerce' ),
					array( 'status' => 429 )
				);
			}

			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or expired QR login token.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		if ( ! $this->check_scan_rate_limit() ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				__( 'Too many QR login scans. Please try again later.', 'woocommerce' ),
				array( 'status' => 429 )
			);
		}

		$device = $this->sanitize_device_payload( $request->get_param( 'device' ) );
		if ( empty( $device['model'] ) && empty( $device['os'] ) ) {
			return new \WP_Error(
				'invalid_device',
				__( 'QR login requires device information from the mobile app.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Atomic mutex on the read-mutate-write window. Without this, two
		// concurrent scans both pass the state==pending check below and both
		// write a new challenge — last writer wins, the loser's session_id
		// is silently orphaned. The state check is kept as defense-in-depth
		// for any path that bypasses the claim (e.g. the staleness branch).
		if ( ! $this->claim_token_for_scan(
			$token_hash,
			isset( $record['expires_at'] ) ? (int) $record['expires_at'] : time() + self::TOKEN_TTL
		) ) {
			return new \WP_Error(
				'qr_login_already_scanned',
				__( 'This QR login session is no longer accepting scans.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		$current_state = isset( $record['state'] ) ? (string) $record['state'] : self::STATE_PENDING;
		if ( self::STATE_PENDING !== $current_state ) {
			$this->release_token_scan_claim( $token_hash );
			return new \WP_Error(
				'qr_login_already_scanned',
				__( 'This QR login session is no longer accepting scans.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		$challenge_numbers = $this->generate_challenge_numbers();
		$session_id        = wp_generate_uuid4();
		$now               = time();
		$token_expires_at  = isset( $record['expires_at'] ) ? (int) $record['expires_at'] : $now + self::TOKEN_TTL;

		if ( $token_expires_at <= $now ) {
			$this->release_token_scan_claim( $token_hash );
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or expired QR login token.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		$challenge_expires_at = min( $now + self::CHALLENGE_TTL_SECONDS, $token_expires_at );
		$challenge_ttl        = max( 1, $challenge_expires_at - $now );

		// Shuffle the candidate triple ONCE at scan time and persist the chosen ordering so
		// every subsequent /qr-login-status poll returns the same array. Re-shuffling per-poll
		// would make the wc-admin tile order flicker every 2.5 s — terrible UX, and makes the
		// merchant doubt they're reading the right number.
		$candidates = array_merge( array( $challenge_numbers['real'] ), $challenge_numbers['distractors'] );
		shuffle( $candidates );

		$record['state']     = self::STATE_SCANNED;
		$record['state_at']  = $now;
		$record['challenge'] = array(
			'real'        => $challenge_numbers['real'],
			'distractors' => $challenge_numbers['distractors'],
			'shuffled'    => $candidates,
			'session_id'  => $session_id,
			'expires_at'  => $challenge_expires_at,
			'device'      => $device,
		);

		// Re-use whatever TTL the original transient had left. The challenge
		// window itself is capped to the remaining token lifetime, while the
		// storage TTL keeps the full challenge visible for normal fresh scans.
		$ttl_left    = max( 1, $token_expires_at - $now );
		$storage_ttl = min( $ttl_left, self::CHALLENGE_TTL_SECONDS + 30 );
		set_transient( $key, $record, $storage_ttl );

		// Sibling transient that resolves session_id → token_hash for the
		// app's session-status poll. Stored hashed so the session id isn't
		// directly indexable in wp_options.
		set_transient(
			self::SESSION_TRANSIENT_PREFIX . hash( 'sha256', $session_id ),
			$token_hash,
			$storage_ttl
		);

		$this->release_token_scan_claim( $token_hash );

		return rest_ensure_response(
			array(
				'session_id'  => $session_id,
				'real_number' => $challenge_numbers['real'],
				'expires_in'  => $challenge_ttl,
			)
		);
	}

	/**
	 * Merchant taps a number on wc-admin. Server validates against the
	 * stored real number with `hash_equals()` (constant-time). Correct →
	 * `approved` + mints `exchange_grant`. Wrong → `rejected` (terminal,
	 * security event logged). One-strike: no retry.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function approve_token( $request ) {
		$user_id = get_current_user_id();

		if ( ! $this->check_approve_rate_limit( $user_id ) ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				__( 'Too many QR login approval attempts. Please try again later.', 'woocommerce' ),
				array( 'status' => 429 )
			);
		}

		$token      = (string) $request->get_param( 'token' );
		$token_hash = hash( 'sha256', $token );
		$key        = self::TOKEN_TRANSIENT_PREFIX . $token_hash;

		$record = get_transient( $key );
		if ( ! is_array( $record ) ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or expired QR login token.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		$approval_claim_expires_at = ! empty( $record['challenge']['expires_at'] )
			? (int) $record['challenge']['expires_at']
			: ( isset( $record['expires_at'] ) ? (int) $record['expires_at'] : time() + self::TOKEN_TTL );
		if ( ! $this->claim_token_for_approval( $token_hash, $approval_claim_expires_at ) ) {
			return new \WP_Error(
				'qr_login_approval_in_progress',
				__( 'This QR login session is already being approved.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		// Re-read after acquiring the database claim in case another request
		// approved, rejected, or expired the challenge while this one was waiting.
		$record = get_transient( $key );
		if ( ! is_array( $record ) ) {
			$this->release_token_approval_claim( $token_hash );
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or expired QR login token.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		// Same cross-user defense as get_status — only the user that minted
		// the token can approve it.
		if ( ! isset( $record['user_id'] ) || (int) $record['user_id'] !== (int) $user_id ) {
			$this->release_token_approval_claim( $token_hash );
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or expired QR login token.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		if ( ! empty( $record['expires_at'] ) && time() >= (int) $record['expires_at'] ) {
			$record['state']    = self::STATE_EXPIRED;
			$record['state_at'] = time();
			set_transient( $key, $record, 60 );
			$this->release_token_approval_claim( $token_hash );
			return new \WP_Error(
				'qr_login_expired',
				__( 'The QR login challenge has expired. Please generate a new code.', 'woocommerce' ),
				array( 'status' => 410 )
			);
		}

		$current_state = isset( $record['state'] ) ? (string) $record['state'] : self::STATE_PENDING;
		if ( self::STATE_SCANNED !== $current_state ) {
			$this->release_token_approval_claim( $token_hash );
			return new \WP_Error(
				'qr_login_not_scanned',
				__( 'This QR login session is not waiting for approval.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		// Challenge expiry — normally 90 s after scan, capped by token expiry.
		if ( ! empty( $record['challenge']['expires_at'] ) && time() > (int) $record['challenge']['expires_at'] ) {
			$record['state']    = self::STATE_EXPIRED;
			$record['state_at'] = time();
			set_transient( $key, $record, 60 );
			$this->release_token_approval_claim( $token_hash );
			return new \WP_Error(
				'qr_login_expired',
				__( 'The QR login challenge has expired. Please generate a new code.', 'woocommerce' ),
				array( 'status' => 410 )
			);
		}

		$choice = (string) $request->get_param( 'choice' );
		$real   = isset( $record['challenge']['real'] ) ? (string) $record['challenge']['real'] : '';

		// Constant-time compare. Defends against PHP string-comparison fast
		// paths that can leak prefix-matching info via timing.
		if ( '' === $real || ! hash_equals( $real, $choice ) ) {
			$record['state']    = self::STATE_REJECTED;
			$record['state_at'] = time();
			set_transient( $key, $record, 60 );

			wc_get_logger()->warning(
				'QR login number-match rejected — wrong choice submitted',
				array(
					'source'  => 'qr-login-security',
					'user_id' => (int) $user_id,
					'ip'      => $this->get_client_ip(),
					'device'  => isset( $record['challenge']['device'] ) ? $record['challenge']['device'] : array(),
				)
			);

			$this->release_token_approval_claim( $token_hash );
			return rest_ensure_response( array( 'state' => self::STATE_REJECTED ) );
		}

		$record['state']          = self::STATE_APPROVED;
		$record['state_at']       = time();
		$record['exchange_grant'] = bin2hex( random_bytes( self::EXCHANGE_GRANT_BYTES ) );
		$ttl                      = max(
			1,
			isset( $record['expires_at'] ) ? (int) $record['expires_at'] - time() : self::CHALLENGE_TTL_SECONDS
		);

		set_transient( $key, $record, $ttl );
		$this->release_token_approval_claim( $token_hash );

		return rest_ensure_response( array( 'state' => self::STATE_APPROVED ) );
	}

	/**
	 * Mobile app polls this with the session id from /scan. Returns the
	 * current state of the underlying token, plus — when state is
	 * `approved` — the `exchange_grant` nonce required by /qr-login-exchange.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_session_status( $request ) {
		// Defeat any intermediary cache (Cloudflare, NGINX micro-cache, OkHttp's shared
		// cache, edge proxy) that might pin this GET to its first response. Polling
		// endpoints are by definition state-bearing — every tick must see the live
		// transient. Returning a stale `scanned` response forever is exactly the
		// symptom we see if the cache pins the first hit.
		nocache_headers();

		if ( ! is_ssl() ) {
			return new \WP_Error(
				'ssl_required',
				__( 'QR login requires an HTTPS connection.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		$session_id     = (string) $request->get_param( 'session_id' );
		$submitted_hash = (string) $request->get_param( 'token_hash' );

		$token_hash = get_transient( self::SESSION_TRANSIENT_PREFIX . hash( 'sha256', $session_id ) );
		if ( ! is_string( $token_hash ) || '' === $token_hash ) {
			// Either the session never existed or it has expired. Either way,
			// surface as expired to the polling app.
			return $this->rest_ensure_nocache_response( array( 'state' => self::STATE_EXPIRED ) );
		}

		// Bind grant delivery to proof of token knowledge: an attacker who
		// learns the session_id alone (mobile logs, network capture, debug
		// output) cannot poll for state transitions and walk away with the
		// `exchange_grant` the moment the merchant approves. The mobile app
		// already holds the plaintext token from the QR scan — passing
		// SHA-256(token) on every poll is essentially free for it.
		// `hash_equals` for constant-time comparison; `expired` opacity so
		// we never leak whether the session_id is real or not.
		if ( ! hash_equals( $token_hash, $submitted_hash ) ) {
			return $this->rest_ensure_nocache_response( array( 'state' => self::STATE_EXPIRED ) );
		}

		if ( ! $this->check_session_status_rate_limit( $session_id ) ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				__( 'Too many QR login session-status checks. Please try again later.', 'woocommerce' ),
				array( 'status' => 429 )
			);
		}

		$record = get_transient( self::TOKEN_TRANSIENT_PREFIX . $token_hash );
		if ( ! is_array( $record ) ) {
			return $this->rest_ensure_nocache_response( array( 'state' => self::STATE_EXPIRED ) );
		}

		$state    = isset( $record['state'] ) ? (string) $record['state'] : self::STATE_PENDING;
		$response = array( 'state' => $state );

		if ( in_array( $state, array( self::STATE_REJECTED, self::STATE_EXPIRED ), true ) ) {
			return $this->rest_ensure_nocache_response( $response );
		}

		if ( ! empty( $record['expires_at'] ) && time() >= (int) $record['expires_at'] ) {
			return $this->rest_ensure_nocache_response( array( 'state' => self::STATE_EXPIRED ) );
		}

		if ( self::STATE_APPROVED === $state && ! empty( $record['exchange_grant'] ) ) {
			$response['exchange_grant'] = (string) $record['exchange_grant'];
		}

		return $this->rest_ensure_nocache_response( $response );
	}

	/**
	 * Generate a 1-real + 2-distractor number triple for the match
	 * challenge. Distractors must differ from the real number and from each
	 * other by ≥ 100 — defends against a partial-read leak fingerprinting
	 * the real one (no `042` vs `043` near-misses).
	 *
	 * Uses `random_int()` (CSPRNG-backed) rather than `wp_rand()`, which can
	 * fall back to mt_rand() and is predictable.
	 *
	 * @return array{real: string, distractors: array<int, string>}
	 * @throws \RuntimeException If a valid distractor set cannot be generated.
	 */
	private function generate_challenge_numbers(): array {
		$real             = random_int( 0, 999 );
		$valid_candidates = array();

		for ( $candidate = 0; $candidate <= 999; $candidate++ ) {
			if ( $candidate !== $real && abs( $candidate - $real ) >= 100 ) {
				$valid_candidates[] = $candidate;
			}
		}

		if ( empty( $valid_candidates ) ) {
			throw new \RuntimeException( 'QR login challenge generator could not find a valid first distractor.' );
		}

		$first_index = random_int( 0, count( $valid_candidates ) - 1 );
		$first       = $valid_candidates[ $first_index ];

		$second_candidates = array_values(
			array_filter(
				$valid_candidates,
				static function ( $candidate ) use ( $first ) {
					return abs( $candidate - $first ) >= 100;
				}
			)
		);

		if ( empty( $second_candidates ) ) {
			throw new \RuntimeException( 'QR login challenge generator could not find a valid second distractor.' );
		}

		$second      = $second_candidates[ random_int( 0, count( $second_candidates ) - 1 ) ];
		$distractors = array( $first, $second );

		return array(
			'real'        => str_pad( (string) $real, 3, '0', STR_PAD_LEFT ),
			'distractors' => array_map(
				static function ( $n ) {
					return str_pad( (string) $n, 3, '0', STR_PAD_LEFT );
				},
				$distractors
			),
		);
	}

	/**
	 * Build the shuffled candidate triple returned by `/qr-login-status`
	 * while in the `scanned` state. The order is fixed at scan time (in
	 * `scan_token`) and stored in `challenge.shuffled` so every poll returns
	 * the same array — re-shuffling per-poll caused visible tile flicker on
	 * wc-admin. Falls back to building + shuffling on the fly for any token
	 * record that predates the persisted-shuffle change.
	 *
	 * @param array<string, mixed> $challenge The challenge payload from the token record.
	 * @return array<int, string>
	 */
	private function shuffled_candidate_numbers( array $challenge ): array {
		if ( isset( $challenge['shuffled'] ) && is_array( $challenge['shuffled'] ) ) {
			return array_map( 'strval', $challenge['shuffled'] );
		}

		$real        = isset( $challenge['real'] ) ? (string) $challenge['real'] : '';
		$distractors = isset( $challenge['distractors'] ) && is_array( $challenge['distractors'] )
			? array_map( 'strval', $challenge['distractors'] )
			: array();

		$candidates = array_merge( array( $real ), $distractors );
		shuffle( $candidates );
		return $candidates;
	}

	/**
	 * Per-IP rate limit on /qr-login-scan.
	 *
	 * @return bool True if within rate limit.
	 */
	private function check_scan_rate_limit() {
		return QRLoginRateLimits::consume( QRLoginRateLimits::BUCKET_SCAN, $this->get_client_ip() );
	}

	/**
	 * Per-user rate limit on /qr-login-approve.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if within rate limit.
	 */
	private function check_approve_rate_limit( $user_id ) {
		return QRLoginRateLimits::consume( QRLoginRateLimits::BUCKET_APPROVE, (string) $user_id );
	}

	/**
	 * Per-session rate limit on /qr-login-session-status.
	 *
	 * @param string $session_id The session ID.
	 * @return bool True if within rate limit.
	 */
	private function check_session_status_rate_limit( $session_id ) {
		return QRLoginRateLimits::consume( QRLoginRateLimits::BUCKET_SESSION_STATUS, $session_id );
	}

	/**
	 * Send the merchant a transactional email summarizing a successful QR
	 * sign-in, unless they (or a site owner) opt out via the
	 * `woocommerce_qr_login_should_send_signin_email` filter.
	 *
	 * Wrapped so a misconfigured mailer cannot break the exchange path. Mailer
	 * false returns and exceptions are logged, but delivery never blocks the API
	 * response.
	 *
	 * @param \WP_User             $user            The user who minted the token (recipient).
	 * @param array<string, mixed> $consumed_record The record persisted to the consumed transient (keys: consumed_at, user_id, ap_uuid, ap_name, device).
	 * @return void
	 */
	private function maybe_send_sign_in_notification_email( \WP_User $user, array $consumed_record ): void {
		/**
		 * Filter whether to send the QR sign-in notification email.
		 *
		 * Default: true. Return false to suppress the send for a specific
		 * user, environment (e.g. staging), or test run.
		 *
		 * @since 10.9.0
		 *
		 * @param bool                 $should_send     Whether to send the email.
		 * @param \WP_User             $user            The user who minted the QR token.
		 * @param array<string, mixed> $consumed_record The consumed record about to be emailed (keys: consumed_at, user_id, ap_uuid, ap_name, device).
		 */
		$should_send = (bool) apply_filters(
			'woocommerce_qr_login_should_send_signin_email',
			true,
			$user,
			$consumed_record
		);

		if ( ! $should_send ) {
			return;
		}

		try {
			if ( ! $this->send_sign_in_notification_email( $user, $consumed_record ) ) {
				wc_get_logger()->warning(
					sprintf(
						'QR sign-in notification email failed for user %d: wp_mail returned false',
						$user->ID
					),
					array( 'source' => 'mobile-app-qr-login' )
				);
			}
		} catch ( \Throwable $e ) {
			// Don't surface mailer failures to the exchange response — the
			// merchant already has the API result, and the email is best-effort.
			// Log instead so a misconfigured mailer is observable rather than
			// invisible. Catch \Throwable so an \Error from the mailer also
			// stays out of the exchange path.
			wc_get_logger()->warning(
				sprintf(
					'QR sign-in notification email failed for user %d: %s',
					$user->ID,
					$e->getMessage()
				),
				array( 'source' => 'mobile-app-qr-login' )
			);
		}
	}

	/**
	 * Render and dispatch the sign-in notification email.
	 *
	 * Uses `wp_mail()` directly with our own minimal HTML shell rather than
	 * `WC()->mailer()->wrap_message()` — the WC wrapper auto-prepends a small
	 * site-name header that duplicates the subject line shown by most clients
	 * and constrains the body width. Owning the wrapper lets us deliver one
	 * coherent layout.
	 *
	 * @param \WP_User             $user            Recipient.
	 * @param array<string, mixed> $consumed_record The consumed record (same shape as `maybe_send_sign_in_notification_email`).
	 * @return bool True if WordPress accepted the message for delivery.
	 */
	private function send_sign_in_notification_email( \WP_User $user, array $consumed_record ): bool {
		$site_name = wp_specialchars_decode(
			(string) get_bloginfo( 'name' ),
			ENT_QUOTES
		);

		/* translators: %s: site name. */
		$subject = sprintf( __( 'A new device signed in to %s', 'woocommerce' ), $site_name );

		$body_html = $this->render_sign_in_notification_email_body( $user, $consumed_record, $site_name, $subject );

		return wp_mail(
			$user->user_email,
			$subject,
			$body_html,
			array( 'Content-Type: text/html; charset=UTF-8' )
		);
	}

	/**
	 * Render the full HTML email document for the sign-in notification.
	 *
	 * @param \WP_User             $user            Recipient.
	 * @param array<string, mixed> $consumed_record The consumed record (same shape as `maybe_send_sign_in_notification_email`).
	 * @param string               $site_name       Decoded site name (passed in to avoid double-decoding).
	 * @param string               $subject         Email subject; rendered as the in-body heading.
	 * @return string Rendered HTML document.
	 */
	private function render_sign_in_notification_email_body( \WP_User $user, array $consumed_record, string $site_name, string $subject ): string {
		$device           = $consumed_record['device'] ?? array();
		$consumed_at      = isset( $consumed_record['consumed_at'] ) ? (int) $consumed_record['consumed_at'] : time();
		$ap_name          = $consumed_record['ap_name'] ?? '';
		$applications_url = admin_url( 'profile.php#application-passwords-section' );

		ob_start();
		include __DIR__ . '/views/mobile-app-qr-login-signin-email.php';
		$html = ob_get_clean();

		return is_string( $html ) ? $html : '';
	}
}
