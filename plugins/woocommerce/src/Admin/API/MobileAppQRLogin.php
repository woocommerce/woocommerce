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
	 * Broad anonymous exchange abuse guard per IP per 15-minute window.
	 */
	const MAX_EXCHANGE_IP_ATTEMPTS = 1000;

	/**
	 * Option prefix for database-backed atomic token claims.
	 */
	const CLAIM_OPTION_PREFIX = '_wc_qr_login_claim_';

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
	 * Whitelisted keys for the optional `device` payload sent by the mobile app
	 * on the exchange call. Anything outside this set is dropped before storage.
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
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/qr-login-exchange',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'exchange_token' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'token'  => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'device' => array(
							'required'   => false,
							'type'       => 'object',
							// Sanitization happens inside the callback via
							// `sanitize_device_payload()`; we accept any object
							// shape here and whitelist server-side.
							'properties' => array(
								'os'          => array( 'type' => 'string' ),
								'os_version'  => array( 'type' => 'string' ),
								'model'       => array( 'type' => 'string' ),
								'brand'       => array( 'type' => 'string' ),
								'app_version' => array( 'type' => 'string' ),
							),
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
	 * cache. Stale claims are cleaned once and retried.
	 *
	 * @param string $token_hash SHA-256 hash of the plaintext token.
	 * @param int    $expires_at Unix timestamp when the token expires.
	 * @return bool True if the claim was acquired.
	 */
	private function claim_token_for_exchange( $token_hash, $expires_at ) {
		$claim_key        = $this->get_token_claim_key( $token_hash );
		$claim_expires_at = max( time() + 30, (int) $expires_at );

		if ( add_option( $claim_key, (string) $claim_expires_at, '', false ) ) {
			return true;
		}

		$existing_expires_at = (int) get_option( $claim_key, 0 );
		if ( $existing_expires_at > 0 && $existing_expires_at <= time() ) {
			delete_option( $claim_key );
			return add_option( $claim_key, (string) $claim_expires_at, '', false );
		}

		return false;
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

		// Use get_site_url() for the returned value so any scheme normalization or
		// filtering that WordPress applies downstream is preserved.
		return get_site_url();
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
		$expires_at = time() + self::TOKEN_TTL;

		// Store token data as a transient.
		$token_data = array(
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
		if ( time() > $token_data['expires_at'] ) {
			delete_transient( $key );
			$this->release_token_exchange_claim( $token_hash );
			return new \WP_Error(
				'token_expired',
				__( 'QR login token has expired.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

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

		// Whitelist + sanitize the optional `device` payload. Older app versions
		// don't send this; missing/empty is fine — falls back to the default AP
		// name and an empty `device` array on the consumed record.
		$device = $this->sanitize_device_payload( $request->get_param( 'device' ) );

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

		delete_transient( $key );
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
			return rest_ensure_response( array( 'status' => 'expired' ) );
		}

		$token_hash = hash( 'sha256', $token );

		// Consumed lookup first — once a token has been exchanged we want the
		// poll to immediately reflect that state, even though the original
		// token transient was deleted by `exchange_token()`.
		$consumed = get_transient( self::CONSUMED_TRANSIENT_PREFIX . $token_hash );
		if ( is_array( $consumed ) ) {
			// Defense in depth: only the user who minted the token can see its
			// consumed details. We hide the record from cross-user reads to
			// avoid leaking that a given token has been used.
			if ( ! isset( $consumed['user_id'] ) || (int) $consumed['user_id'] !== (int) $user_id ) {
				return rest_ensure_response( array( 'status' => 'expired' ) );
			}

			return rest_ensure_response(
				array(
					'status'      => 'consumed',
					'consumed_at' => isset( $consumed['consumed_at'] ) ? (int) $consumed['consumed_at'] : null,
					'ap_uuid'     => isset( $consumed['ap_uuid'] ) ? (string) $consumed['ap_uuid'] : null,
					'ap_name'     => isset( $consumed['ap_name'] ) ? (string) $consumed['ap_name'] : null,
					'device'      => isset( $consumed['device'] ) && is_array( $consumed['device'] ) ? $consumed['device'] : array(),
				)
			);
		}

		$pending = get_transient( self::TOKEN_TRANSIENT_PREFIX . $token_hash );
		if ( is_array( $pending ) ) {
			// Same defense-in-depth ownership check.
			if ( ! isset( $pending['user_id'] ) || (int) $pending['user_id'] !== (int) $user_id ) {
				return rest_ensure_response( array( 'status' => 'expired' ) );
			}

			return rest_ensure_response(
				array(
					'status'     => 'pending',
					'expires_at' => isset( $pending['expires_at'] ) ? (int) $pending['expires_at'] : null,
				)
			);
		}

		return rest_ensure_response( array( 'status' => 'expired' ) );
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
	 * Whitelist + sanitize the optional `device` payload sent by the mobile app
	 * on the exchange call.
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
	 * Falls back to the legacy literal `WooCommerce Mobile App (QR Login)` if
	 * neither model nor OS is available — that keeps older mobile clients (which
	 * don't send the `device` payload) working without changing their visible
	 * AP name.
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

		$descriptor = '';
		if ( '' !== $model ) {
			$descriptor = $model;
		} elseif ( '' !== $os ) {
			$descriptor = $os;
		}

		if ( '' === $descriptor ) {
			// Legacy fallback — preserves the existing AP name format for
			// older app versions that don't send a device payload.
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
	 * Send the merchant a transactional email summarizing a successful QR
	 * sign-in, unless they (or a site owner) opt out via the
	 * `woocommerce_qr_login_should_send_signin_email` filter.
	 *
	 * Wrapped so a misconfigured mailer cannot break the exchange path —
	 * exceptions are caught silently. We deliberately do not block the API
	 * response on email delivery; the merchant already saw the confirmation
	 * UI in wc-admin (Task 5).
	 *
	 * @param \WP_User                                                                   $user            The user who minted the token (recipient).
	 * @param array{consumed_at: int, user_id: int, ap_uuid: string, ap_name: string, device: array<string, string>} $consumed_record The record we just persisted to the consumed transient.
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
		 * @param bool                                                                       $should_send     Whether to send the email.
		 * @param \WP_User                                                                   $user            The user who minted the QR token.
		 * @param array{consumed_at: int, user_id: int, ap_uuid: string, ap_name: string, device: array<string, string>} $consumed_record The consumed record about to be emailed.
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
			$this->send_sign_in_notification_email( $user, $consumed_record );
		} catch ( \Exception $e ) {
			// Swallow — the API response is the source of truth for the
			// merchant; the email is best-effort.
			unset( $e );
		}
	}

	/**
	 * Render and dispatch the sign-in notification email.
	 *
	 * Uses `wp_mail()` directly (no `WC_Email` subclass) because this is a
	 * one-shot transactional notification, not part of the configurable WC
	 * email surface. We wrap the body in `WC()->mailer()->wrap_message()`
	 * when available so the visual shell matches other WC mails; if the
	 * mailer isn't initialized (e.g. very early bootstrap), we fall back to
	 * the bare HTML.
	 *
	 * @param \WP_User                                                                   $user            Recipient.
	 * @param array{consumed_at: int, user_id: int, ap_uuid: string, ap_name: string, device: array<string, string>} $consumed_record The consumed record.
	 * @return void
	 */
	private function send_sign_in_notification_email( \WP_User $user, array $consumed_record ): void {
		$site_name = wp_specialchars_decode(
			(string) get_bloginfo( 'name' ),
			ENT_QUOTES
		);

		/* translators: %s: site name. */
		$subject = sprintf( __( 'A new device signed in to %s', 'woocommerce' ), $site_name );

		$body_html = $this->render_sign_in_notification_email_body( $user, $consumed_record, $site_name );

		// Prefer the WC mailer's HTML wrapper for visual consistency. Falls
		// back to bare HTML when the mailer isn't ready (rare in REST flow).
		if ( function_exists( 'WC' ) && WC() && method_exists( WC(), 'mailer' ) ) {
			$mailer = WC()->mailer();
			if ( $mailer && method_exists( $mailer, 'wrap_message' ) ) {
				$body_html = $mailer->wrap_message( $subject, $body_html );
			}
		}

		wp_mail(
			$user->user_email,
			$subject,
			$body_html,
			array( 'Content-Type: text/html; charset=UTF-8' )
		);
	}

	/**
	 * Render the HTML body of the sign-in notification email.
	 *
	 * Pulled out so the partial can be rendered in isolation in tests, and so
	 * future template tweaks live in one place. Uses the site's configured
	 * timezone for the timestamp so it matches what the merchant sees in
	 * wp-admin elsewhere.
	 *
	 * @param \WP_User                                                                   $user            Recipient.
	 * @param array{consumed_at: int, user_id: int, ap_uuid: string, ap_name: string, device: array<string, string>} $consumed_record The consumed record.
	 * @param string                                                                     $site_name       Decoded site name (passed in to avoid double-decoding).
	 * @return string Rendered HTML.
	 */
	private function render_sign_in_notification_email_body( \WP_User $user, array $consumed_record, string $site_name ): string {
		$device       = $consumed_record['device'] ?? array();
		$consumed_at  = isset( $consumed_record['consumed_at'] ) ? (int) $consumed_record['consumed_at'] : time();
		$ap_name      = $consumed_record['ap_name'] ?? '';
		$applications_url = admin_url( 'profile.php#application-passwords-section' );

		ob_start();
		include __DIR__ . '/views/mobile-app-qr-login-signin-email.php';
		$html = ob_get_clean();

		return is_string( $html ) ? $html : '';
	}
}
