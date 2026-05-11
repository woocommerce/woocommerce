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

		// Create an Application Password for the mobile app.
		$app_password_result = \WP_Application_Passwords::create_new_application_password(
			$user_id,
			array(
				'name'   => __( 'WooCommerce Mobile App (QR Login)', 'woocommerce' ),
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

		delete_transient( $key );
		$this->release_token_exchange_claim( $token_hash );

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
}
