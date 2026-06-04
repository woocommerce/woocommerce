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
					'permission_callback' => '__return_true', // no need to check permissions
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

		$events = $request->get_json_params();

		if ( ! is_array( $events ) || ( isset( $events['event_name'] ) ) ) {
			// If $events is a single event (associative array), wrap it in an array.
			$events = array( $events );
		}

		$results    = array();
		$has_errors = false;

		foreach ( $events as $index => $event ) {
			// Validate event structure.
			if ( empty( $event ) || ! is_array( $event ) ) {
				$results[ $index ] = array(
					'success' => false,
					'error'   => 'Invalid event format',
				);
				$has_errors        = true;
				continue;
			}

			// Validate event name and properties.
			$event_name = $event['event_name'] ?? null;
			$properties = $event['properties'] ?? array();
			if ( ! $event_name || ! is_array( $properties ) ) {
				$results[ $index ] = array(
					'success' => false,
					'error'   => 'Missing event_name or invalid properties',
				);
				$has_errors        = true;
				continue;
			}

			$result = WC_Analytics_Tracking::record_event( $event_name, $properties );

			if ( is_wp_error( $result ) ) {
				$results[ $index ] = array(
					'success' => false,
					'error'   => $result->get_error_message(),
				);
				$has_errors        = true;
				continue;
			}

			$results[ $index ] = array( 'success' => true );
		}

		$response_data = array(
			'success' => ! $has_errors,
			'results' => $results,
		);

		return new \WP_REST_Response( $response_data, $has_errors ? 207 : 200 );
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
