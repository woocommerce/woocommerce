<?php

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\RestApiUtil;

/**
 * The Legacy REST API was removed in WooCommerce 9.0 and is now available as a dedicated extension.
 * A stub is kept in WooCommerce core that acts when the extension is not installed and has two purposes:
 *
 * 1. Return a "The WooCommerce API is disabled on this site" error for any request to the Legacy REST API endpoints.
 *
 * 2. Provide the not-endpoint related utility methods that were previously supplied by the WC_API class,
 *    this is achieved by setting the value of WooCommerce::api (typically accessed via 'WC()->api') to an instance of this class.
 *
 * DO NOT add any additional public method to this class unless the method existed with the same signature in the old WC_API class.
 *
 * See: https://developer.woocommerce.com/2023/10/03/the-legacy-rest-api-will-move-to-a-dedicated-extension-in-woocommerce-9-0/
 */
class LegacyRestApiStub implements RegisterHooksInterface {

	/**
	 * The instance of RestApiUtil to use.
	 *
	 * @var RestApiUtil
	 */
	private RestApiUtil $rest_api_util;

	/**
	 * Set up the Legacy REST API endpoints stub.
	 */
	public function register() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules_for_legacy_rest_api_stub' ), 0 );
		add_action( 'query_vars', array( __CLASS__, 'add_query_vars_for_legacy_rest_api_stub' ), 0 );
		add_action( 'parse_request', array( __CLASS__, 'parse_legacy_rest_api_request' ), 0 );
	}

	/**
	 * Initialize the class dependencies.
	 *
	 * @internal
	 * @param RestApiUtil $rest_api_util The instance of RestApiUtil to use.
	 */
	final public function init( RestApiUtil $rest_api_util ) {
		$this->rest_api_util = $rest_api_util;
	}

	/**
	 * Add the necessary rewrite rules for the Legacy REST API
	 * (either the dedicated extension if it's installed, or the stub otherwise).
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public static function add_rewrite_rules_for_legacy_rest_api_stub() {
		add_rewrite_rule( '^wc-api/v([1-3]{1})/?$', 'index.php?wc-api-version=$matches[1]&wc-api-route=/', 'top' );
		add_rewrite_rule( '^wc-api/v([1-3]{1})(.*)?', 'index.php?wc-api-version=$matches[1]&wc-api-route=$matches[2]', 'top' );
		add_rewrite_endpoint( 'wc-api', EP_ALL );
	}

	/**
	 * Add the necessary request query variables for the Legacy REST API
	 * (either the dedicated extension if it's installed, or the stub otherwise).
	 *
	 * @param array $vars The query variables array to extend.
	 * @return array The extended query variables array.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public static function add_query_vars_for_legacy_rest_api_stub( $vars ) {
		$vars[] = 'wc-api-version';
		$vars[] = 'wc-api-route';
		$vars[] = 'wc-api';
		return $vars;
	}

	/**
	 * Process an incoming request for the Legacy REST API.
	 *
	 * If the dedicated Legacy REST API extension is installed and active, this method does nothing.
	 * Otherwise it returns a "The WooCommerce API is disabled on this site" error,
	 * unless the request contains a "wc-api" variable and the appropriate
	 * "woocommerce_api_*" hook is set.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public static function parse_legacy_rest_api_request() {
		global $wp;

		// The WC_Legacy_REST_API_Plugin class existence means that the Legacy REST API extension is installed and active.
		if ( class_exists( 'WC_Legacy_REST_API_Plugin' ) ) {
			return;
		}

		self::maybe_process_wc_api_query_var();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput

		if ( ! empty( $_GET['wc-api-version'] ) ) {
			$wp->query_vars['wc-api-version'] = $_GET['wc-api-version'];
		}

		if ( ! empty( $_GET['wc-api-route'] ) ) {
			$wp->query_vars['wc-api-route'] = $_GET['wc-api-route'];
		}

		if ( ! empty( $wp->query_vars['wc-api-version'] ) && ! empty( $wp->query_vars['wc-api-route'] ) ) {
			header(
				sprintf(
					'Content-Type: %s; charset=%s',
					isset( $_GET['_jsonp'] ) ? 'application/javascript' : 'application/json',
					get_option( 'blog_charset' )
				)
			);
			status_header( 404 );
			echo wp_json_encode(
				array(
					'errors' => array(
						'code'    => 'woocommerce_api_disabled',
						'message' => 'The WooCommerce API is disabled on this site',
					),
				)
			);
			exit;
		}

		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
	}

	/**
	 * Process a "wc-api" variable if present in the query, by triggering the appropriate hooks.
	 */
	private static function maybe_process_wc_api_query_var() {
		global $wp;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['wc-api'] ) ) {
			$wp->query_vars['wc-api'] = sanitize_key( wp_unslash( $_GET['wc-api'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// wc-api endpoint requests.
		if ( ! empty( $wp->query_vars['wc-api'] ) ) {

			// Buffer, we won't want any output here.
			ob_start();

			// No cache headers.
			wc_nocache_headers();

			// Clean the API request.
			$api_request = strtolower( wc_clean( $wp->query_vars['wc-api'] ) );

			// Make sure gateways are available for request.
			WC()->payment_gateways();

			// phpcs:disable WooCommerce.Commenting.CommentHooks.HookCommentWrongStyle

			// Trigger generic action before request hook.
			do_action( 'woocommerce_api_request', $api_request );

			// Is there actually something hooked into this API request? If not trigger 400 - Bad request.
			status_header( has_action( 'woocommerce_api_' . $api_request ) ? 200 : 400 );

			// Trigger an action which plugins can hook into to fulfill the request.
			do_action( 'woocommerce_api_' . $api_request );

			// phpcs:enable WooCommerce.Commenting.CommentHooks.HookCommentWrongStyle

			// Done, clear buffer and exit.
			ob_end_clean();
			die( '-1' );
		}
	}

	/**
	 * Get data from a WooCommerce API endpoint.
	 * This method used to be part of the WooCommerce Legacy REST API.
	 *
	 * @since 9.1.0
	 *
	 * @param string $endpoint Endpoint.
	 * @param array  $params Params to pass with request.
	 * @return array|\WP_Error
	 */
	public function get_endpoint_data( $endpoint, $params = array() ) {
		wc_doing_it_wrong(
			'get_endpoint_data',
			"'WC()->api->get_endpoint_data' is deprecated, please use the following instead: wc_get_container()->get(Automattic\WooCommerce\Utilities\RestApiUtil::class)->get_endpoint_data",
			'9.1.0'
		);

		return $this->rest_api_util->get_endpoint_data( $endpoint, $params );
	}
}
