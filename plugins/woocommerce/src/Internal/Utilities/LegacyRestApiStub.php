<?php

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

/**
 * The Legacy REST API was removed in WooCommerce 9.0 and is now available as a dedicated extension.
 * A stub is kept in WooCommerce core that just returns a "The WooCommerce API is disabled on this site"
 * error if the extension is not installed or is inactive. See:
 * https://developer.woocommerce.com/2023/10/03/the-legacy-rest-api-will-move-to-a-dedicated-extension-in-woocommerce-9-0/
 */
class LegacyRestApiStub {
	use AccessiblePrivateMethods;

	/**
	 * Set up the Legacy REST API stub.
	 */
	public static function setup() {
		self::add_action( 'init', array( __CLASS__, 'add_rewrite_rules_for_legacy_rest_api_stub' ), 0 );
		self::add_action( 'query_vars', array( __CLASS__, 'add_query_vars_for_legacy_rest_api_stub' ), 0 );
		self::add_action( 'parse_request', array( __CLASS__, 'parse_legacy_rest_api_request' ), 0 );
	}

	/**
	 * Add the necessary rewrite rules for the Legacy REST API
	 * (either the dedicated extension if it's installed, or the stub otherwise).
	 */
	private static function add_rewrite_rules_for_legacy_rest_api_stub() {
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
	 */
	private static function add_query_vars_for_legacy_rest_api_stub( $vars ) {
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
	 */
	private static function parse_legacy_rest_api_request() {
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
}
