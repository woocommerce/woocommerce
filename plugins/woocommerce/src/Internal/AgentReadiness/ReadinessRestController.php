<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * GET wc/v3/agent-readiness returns the stored report; POST
 * wc/v3/agent-readiness/scan queues a new scan. Both answer with the report
 * (null before the first scan) and whether a scan is pending, so a settings
 * page can show the last result and poll while a scan runs.
 *
 * Registered only while the agent_readiness feature is enabled.
 *
 * @since 11.4.0
 */
class ReadinessRestController extends RestApiControllerBase {

	/**
	 * Features controller.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Readiness service.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $agent_readiness;

	/**
	 * Background scan.
	 *
	 * @var ScheduledScan
	 */
	private ScheduledScan $scheduled_scan;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller Features controller.
	 * @param AgentReadiness     $agent_readiness     Readiness service.
	 * @param ScheduledScan      $scheduled_scan      Background scan.
	 */
	final public function init( FeaturesController $features_controller, AgentReadiness $agent_readiness, ScheduledScan $scheduled_scan ): void {
		$this->features_controller = $features_controller;
		$this->agent_readiness     = $agent_readiness;
		$this->scheduled_scan      = $scheduled_scan;
	}

	/**
	 * Key in the WooCommerce REST namespaces list.
	 */
	protected function get_rest_api_namespace(): string {
		return 'agent-readiness';
	}

	/**
	 * Register the routes.
	 */
	public function register_routes(): void {
		if ( ! $this->features_controller->feature_is_enabled( StatusTab::FEATURE_ID ) ) {
			return;
		}
		register_rest_route(
			$this->route_namespace,
			'/agent-readiness',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_status' ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'manage_woocommerce' ),
				),
				'schema' => array( $this, 'get_status_schema' ),
			)
		);
		register_rest_route(
			$this->route_namespace,
			'/agent-readiness/scan',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'queue_scan' ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'manage_woocommerce' ),
				),
				'schema' => array( $this, 'get_status_schema' ),
			)
		);
	}

	/**
	 * The stored report and whether a scan is pending.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array{report:array<string,mixed>|null,scan_pending:bool}
	 */
	protected function get_status( WP_REST_Request $request ): array {
		return array(
			'report'       => $this->agent_readiness->get_cached_report(),
			'scan_pending' => $this->scheduled_scan->is_pending(),
		);
	}

	/**
	 * Queue a scan, then answer like GET.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response
	 */
	protected function queue_scan( WP_REST_Request $request ): \WP_REST_Response {
		$this->scheduled_scan->queue();
		return new \WP_REST_Response( $this->get_status( $request ), 202 );
	}

	/**
	 * Response schema. The report's shape is documented in this directory's README.
	 *
	 * @internal
	 */
	public function get_status_schema(): array {
		$schema               = $this->get_base_schema();
		$schema['title']      = 'agent_readiness';
		$schema['properties'] = array(
			'report'       => array(
				'description' => __( 'The last stored readiness report, or null before the first scan.', 'woocommerce' ),
				'type'        => array( 'object', 'null' ),
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
			'scan_pending' => array(
				'description' => __( 'Whether a requested scan has not finished yet.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
		);
		return $schema;
	}
}
