<?php
/**
 * REST API Onboarding Free Extensions Controller
 *
 * Handles requests to /onboarding/free-extensions
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\Init as RemoteFreeExtensions;
use WC_REST_Data_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Onboarding Payments Controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class OnboardingFreeExtensions extends WC_REST_Data_Controller {

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
	protected $rest_base = 'onboarding/free-extensions';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_available_extensions' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check whether a given request has permission to read onboarding profile data.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Return available payment methods.
	 *
	 * @param WP_REST_Request $request Request data.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_available_extensions( $request ) {
		$extensions = RemoteFreeExtensions::get_extensions();
		/**
		* Allows removing Jetpack suggestions from WooCommerce Admin when false.
		 *
		 * In this instance it is removed from the list of extensions suggested in the Onboarding Profiler. This list is first retrieved from the WooCommerce.com API, then if a plugin with the 'jetpack' slug is found, it is removed.
		 *
		 * @since 7.8
		*/
		if ( false === apply_filters( 'woocommerce_suggest_jetpack', true ) ) {
			foreach ( $extensions as &$extension ) {
				$extension['plugins'] = array_filter(
					$extension['plugins'],
					function( $plugin ) {
						return 'jetpack' !== $plugin->key;
					}
				);
			}
		}

		$extensions = $this->replace_jetpack_with_jetpack_boost_for_treatment( $extensions );

		return new WP_REST_Response( $extensions );
	}

	private function replace_jetpack_with_jetpack_boost_for_treatment( array $extensions ) {
		$is_treatment = \WooCommerce\Admin\Experimental_Abtest::in_treatment( 'woocommerce_jetpack_copy' );

		if ( ! $is_treatment ) {
			return $extensions;
		}

		$has_core_profiler = array_search( 'obw/core-profiler', array_column( $extensions, 'key' ) );

		if ( $has_core_profiler === false ) {
			return $extensions;
		}

		$has_jetpack = array_search( 'jetpack', array_column( $extensions[ $has_core_profiler ]['plugins'], 'key' ) );

		if ( $has_jetpack === false ) {
			return $extensions;
		}

		$jetpack                  = &$extensions[ $has_core_profiler ]['plugins'][ $has_jetpack ];
		$jetpack->key             = 'jetpack-boost';
		$jetpack->name            = 'Jetpack Boost';
		$jetpack->label           = __( 'Optimize store performance with Jetpack Boost', 'woocommerce' );
		$jetpack->description     = __( 'Speed up your store and improve your SEO with performance-boosting tools from Jetpack. Learn more', 'woocommerce' );
		$jetpack->learn_more_link = 'https://jetpack.com/boost/';

		return $extensions;
	}
}
