<?php
/**
 * Visitor id endpoint for WooCommerce Analytics.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Jetpack\Device_Detection\User_Agent_Info;

defined( 'ABSPATH' ) || exit;

/**
 * Issues the `tk_ai` visitor cookie from the server.
 *
 * The cookie used to be written by page JavaScript. WebKit deletes cookies created through
 * `document.cookie` after seven days of Safari use without interaction with the site, and caps
 * them to 24 hours when the landing URL is decorated by a classified domain, which is what an
 * ad click looks like. Cookies that arrive in a `Set-Cookie` header from the site's own origin
 * are outside both rules, so the server has to be the writer.
 *
 * It cannot be written while rendering a page: page caches refuse to store a response that
 * carries `Set-Cookie`, and one that slipped through would hand the same id to every visitor
 * served from that cache. This endpoint is a `POST`, which no page cache stores, and the client
 * calls it only when it sees no cookie or once per browser session to refresh the expiry. Only a
 * browser that ran the client script reaches it, so the cookie-less crawlers that used to be
 * minted throwaway ids on server-fired events never get one here.
 */
class WC_Analytics_Visitor extends \WC_REST_Controller {

	/**
	 * Cookie name, shared with WooCommerce core and the stats.wp.com tracker.
	 */
	const COOKIE_NAME = 'tk_ai';

	/**
	 * Cookie lifetime in seconds: one year, renewed on each issue.
	 */
	const COOKIE_LIFETIME = 365 * DAY_IN_SECONDS;

	/**
	 * Values the browser may send back. Covers the 24-character base64 ids this package mints,
	 * the `jetpack:` and `woo:` prefixed forms other writers use, and nothing else.
	 */
	const VALUE_PATTERN = '/^[A-Za-z0-9+\/=:._-]{8,64}$/';

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'woocommerce-analytics/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'visitor';

	/**
	 * Register the route.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'issue_visitor_id' ),
					// Unauthenticated by design: the visitor is anonymous. The handler only
					// echoes or mints an opaque id and never reads user data.
					'permission_callback' => '__return_true',
					'schema'              => array( $this, 'get_public_item_schema' ),
				),
			)
		);
	}

	/**
	 * Issue or refresh the visitor cookie.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function issue_visitor_id( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$response = new \WP_REST_Response( array( 'anon_id' => null ) );
		$response->header( 'Cache-Control', 'no-store' );

		if ( ! Consent_Manager::has_analytics_consent() || $this->is_bot_request() ) {
			return $response;
		}

		$anon_id = $this->get_valid_cookie_value();
		$reused  = null !== $anon_id;

		if ( ! $reused ) {
			$anon_id = $this->generate_anon_id();
		}

		$response->set_data(
			array(
				'anon_id' => $anon_id,
				'reused'  => $reused,
			)
		);
		$response->header( 'Set-Cookie', $this->build_cookie_header( $anon_id ) );

		return $response;
	}

	/**
	 * Whether the request comes from a known bot user agent.
	 *
	 * The user agent is passed explicitly: `User_Agent_Info::is_bot()` caches the first
	 * answer it computes from `$_SERVER` for the rest of the process.
	 *
	 * @return bool
	 */
	private function is_bot_request() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		return User_Agent_Info::is_bot_user_agent( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );
	}

	/**
	 * The `tk_ai` value the browser sent, when it is well formed.
	 *
	 * @return string|null
	 */
	private function get_valid_cookie_value() {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return null;
		}

		$value = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );

		return preg_match( self::VALUE_PATTERN, $value ) ? $value : null;
	}

	/**
	 * Mint a new id: 18 random bytes, base64 encoded, 24 characters. Same shape the client
	 * script produced, so downstream consumers see no change.
	 *
	 * @return string
	 */
	private function generate_anon_id() {
		return base64_encode( random_bytes( 18 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Build the `Set-Cookie` header value.
	 *
	 * `Path=/` and no `Domain`, so the write replaces the cookie the client script used to set
	 * instead of adding a second one. `SameSite=Lax`, so the cookie travels on the top-level
	 * navigation that follows an ad click. Not `HttpOnly`: the client script and the
	 * stats.wp.com tracker read the value to stamp `_ui` on events.
	 *
	 * @param string $anon_id Cookie value.
	 * @return string
	 */
	private function build_cookie_header( $anon_id ) {
		$parts = array(
			self::COOKIE_NAME . '=' . $anon_id,
			'Path=/',
			'Expires=' . gmdate( 'D, d M Y H:i:s \G\M\T', time() + self::COOKIE_LIFETIME ),
			'Max-Age=' . self::COOKIE_LIFETIME,
		);

		if ( is_ssl() ) {
			$parts[] = 'Secure';
		}

		$parts[] = 'SameSite=Lax';

		return implode( '; ', $parts );
	}

	/**
	 * Response schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'woocommerce_analytics_visitor',
			'type'       => 'object',
			'properties' => array(
				'anon_id' => array(
					'description' => 'The anonymous visitor id carried by the tk_ai cookie, or null when none was issued.',
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'reused'  => array(
					'description' => 'Whether the id came from the cookie the browser sent.',
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);
	}
}
