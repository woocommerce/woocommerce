<?php
/**
 * REST API: WC_Analytics_Tracking_Proxy class
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Class to handle tracking events via the REST API
 *
 * @since 0.7.0
 */
class WC_Analytics_Tracking_Proxy extends \WC_REST_Controller {

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
	protected $rest_base = 'track';

	/**
	 * Register the routes for tracking.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'track_events' ),
					// Unauthenticated front-end event endpoint. track_events() validates consent
					// and records events without client-supplied server-owned properties.
					'permission_callback' => '__return_true',
					'schema'              => array( $this, 'get_public_item_schema' ),
				),
			)
		);
	}

	/**
	 * Track events.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function track_events( $request ) {
		// Cached pages can still post here after proxy tracking is disabled; return a
		// visible error instead of losing the event to a 404.
		if ( ! Features::is_proxy_tracking_enabled() ) {
			return new \WP_Error(
				'proxy_tracking_disabled',
				'Proxy tracking is not enabled on this site.',
				array( 'status' => 403 )
			);
		}

		// Check consent before processing any events
		if ( ! Consent_Manager::has_analytics_consent() ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Events skipped due to lack of analytics consent',
					'results' => array(),
				),
				200
			);
		}

		$response_data = WC_Analytics_Tracking::record_client_events( $request->get_json_params() );

		return new \WP_REST_Response( $response_data, $response_data['success'] ? 200 : 207 );
	}

	/**
	 * Get the schema for tracking events.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'tracking_events',
			'type'    => 'array',
			'items'   => array(
				'type'       => 'object',
				'properties' => array(
					'event_name' => array(
						'type' => 'string',
					),
					'properties' => array(
						'type' => 'object',
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
