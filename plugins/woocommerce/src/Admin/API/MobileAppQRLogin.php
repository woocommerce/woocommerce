<?php
/**
 * REST API Mobile App QR Login controller.
 *
 * Handles requests to generate and exchange QR login tokens for direct mobile app
 * authentication via Application Passwords. Token generation is available to any
 * user with the manage_woocommerce capability (typically administrators and shop
 * managers); a linked WordPress.com account is no longer required.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\API;

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
	 * Rate limit transient prefix.
	 */
	const RATE_LIMIT_PREFIX = '_wc_qr_login_rate_';

	/**
	 * Max tokens per user per 15-minute window.
	 */
	const MAX_TOKENS_PER_WINDOW = 5;

	/**
	 * Max exchange attempts per IP per 15-minute window.
	 */
	const MAX_EXCHANGE_ATTEMPTS = 10;

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
		$key   = self::RATE_LIMIT_PREFIX . 'gen_' . $user_id;
		$count = (int) get_transient( $key );

		if ( $count >= self::MAX_TOKENS_PER_WINDOW ) {
			return false;
		}

		set_transient( $key, $count + 1, 15 * MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Check rate limit for token exchange.
	 *
	 * @return bool True if within rate limit.
	 */
	private function check_exchange_rate_limit() {
		$ip    = $this->get_client_ip();
		$key   = self::RATE_LIMIT_PREFIX . 'exc_' . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= self::MAX_EXCHANGE_ATTEMPTS ) {
			return false;
		}

		set_transient( $key, $count + 1, 15 * MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Get the client IP address.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return $ip;
	}

	/**
	 * Validate that the configured site URL is HTTPS and return it.
	 *
	 * `is_ssl()` only tells us the current REQUEST is HTTPS — it says nothing about
	 * the canonical site URL WordPress hands out. Behind a misconfigured proxy (or
	 * after a half-completed HTTPS migration where `siteurl` was never updated),
	 * `get_site_url()` can still return an `http://…` URL even when `is_ssl()` is
	 * true. That URL is what the mobile app uses for the token-exchange POST, so
	 * handing back `http://` would let the token + resulting Application Password
	 * travel in cleartext — the exact risk the HTTPS gate is supposed to prevent.
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
		$site_url = get_site_url();
		$scheme   = wp_parse_url( $site_url, PHP_URL_SCHEME );

		if ( 'https' !== $scheme ) {
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
		// Check rate limit.
		if ( ! $this->check_exchange_rate_limit() ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				__( 'Too many exchange attempts. Please try again later.', 'woocommerce' ),
				array( 'status' => 429 )
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

		$token      = $request->get_param( 'token' );
		$token_hash = hash( 'sha256', $token );
		$key        = self::TOKEN_TRANSIENT_PREFIX . $token_hash;

		// Retrieve and immediately delete the token (one-time use).
		$token_data = get_transient( $key );
		delete_transient( $key );

		if ( false === $token_data ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or expired QR login token.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		// Validate token hasn't expired (belt and suspenders with transient TTL).
		if ( time() > $token_data['expires_at'] ) {
			return new \WP_Error(
				'token_expired',
				__( 'QR login token has expired.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		$user_id = $token_data['user_id'];
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new \WP_Error(
				'user_not_found',
				__( 'User associated with this token no longer exists.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		// Create an Application Password for the mobile app.
		$app_password_result = \WP_Application_Passwords::create_new_application_password(
			$user_id,
			array(
				'name' => __( 'WooCommerce Mobile App (QR Login)', 'woocommerce' ),
			)
		);

		if ( is_wp_error( $app_password_result ) ) {
			return new \WP_Error(
				'application_password_failed',
				$app_password_result->get_error_message(),
				array( 'status' => 500 )
			);
		}

		list( $new_password, $item ) = $app_password_result;

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
