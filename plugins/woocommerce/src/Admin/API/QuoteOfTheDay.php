<?php
/**
 * REST API QuoteOfTheDay Controller
 *
 * Handles requests to /quote-of-the-day
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Data controller.
 *
 * @extends WC_REST_Data_Controller
 */
class QuoteOfTheDay extends \WC_REST_Data_Controller {

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
	protected $rest_base = 'quote-of-the-day';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_quote' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Gets quote from remote website zenquotes.
	 *
	 * @return array
	 */
	private function get_remote_quote() {
		if ( ! empty( get_transient( 'wc_quote_of_the_day' ) ) ) {
			return get_transient( 'wc_quote_of_the_day' );
		}
		$response = wp_remote_get( 'https://zenquotes.io/api/random' );
		if ( is_wp_error( $response ) ) {
			return array();
		}
		$json = json_decode( $response['body'] );
		$quote = count( $json ) > 0 ? $json[0] : array();
		set_transient( 'wc_quote_of_the_day', $quote, 5 * 60 );

		return $quote;
	}

	/**
	 * Forward the experiment request to WP.com and return the WP.com response.
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_quote( $request ) {
		$anon_id        = isset( $_COOKIE['tk_ai'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) ) : '';
		$allow_tracking = 'yes' === get_option( 'woocommerce_allow_tracking' );
		$abtest         = new \WooCommerce\Admin\Experimental_Abtest(
			$anon_id,
			'woocommerce',
			$allow_tracking
		);
		$variation = $abtest->get_variation( 'explat_test_mothra_quote_of_the_day' );
		error_log( $variation );
		if ( 'control' === $variation ) {
			return rest_ensure_response( array() );
		}

		return rest_ensure_response( $this->get_remote_quote() );
	}
}
