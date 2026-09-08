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
 * WebKit caps cookies written by JavaScript (deleted after seven days without interaction,
 * 24 hours after a link-decorated landing); cookies set in an HTTP response are not capped.
 * Setting it while rendering a page would break page caches, so it is set from this POST
 * endpoint, which caches never store. Only the client script calls it, so cookie-less
 * crawlers never get an id.
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
	 * Accepted cookie values: the ids this package mints and the prefixed forms other writers use.
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
					// Unauthenticated: the handler only echoes or mints an opaque id.
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
	 * Whether the request comes from a known bot. The user agent is passed explicitly
	 * because `User_Agent_Info::is_bot()` caches its first answer for the whole process.
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
	 * Mint a new id: 18 random bytes, base64 encoded, the 24-character shape the client used.
	 *
	 * @return string
	 */
	private function generate_anon_id() {
		return base64_encode( random_bytes( 18 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Build the `Set-Cookie` header. `Path=/` and no `Domain` replace the cookie the client
	 * used to set; `SameSite=Lax` lets it travel on an ad-click navigation; not `HttpOnly`
	 * because the client and the stats.wp.com tracker read it.
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
