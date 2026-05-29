<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_REST_Request;
use WP_REST_Server;
use WP_User_Query;

/**
 * REST controller for the POS staff list.
 *
 * Exposes GET /wc/pos/v1/staff — the canonical staff list the mobile client caches
 * and validates PIN entry against locally. Each entry includes the user's display
 * name, effective POS role, POS capability map, and stored PIN hash record (or
 * null if no PIN set).
 *
 * Permission: `manage_woocommerce` — i.e. administrator + shop_manager. POS-only
 * users (cashiers / managers, identified by the `_pos_role` user meta) never call
 * this endpoint directly; the device admin reads the staff list on their behalf
 * and PIN entry is validated client-side against the cached payload.
 *
 * @since 10.9.0
 * @internal
 */
class POSStaffController extends RestApiControllerBase {

	/**
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * @inheritDoc
	 */
	protected string $route_namespace = 'wc/pos/v1';

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @internal
	 *
	 * @param POSPinService $pin_service The PIN service.
	 */
	final public function init( POSPinService $pin_service ): void {
		$this->pin_service = $pin_service;
	}

	/**
	 * @inheritDoc
	 */
	protected function get_rest_api_namespace(): string {
		return 'pos-staff';
	}

	/**
	 * @inheritDoc
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			'/staff',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'list_staff' ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'manage_woocommerce' ),
					'args'                => array(),
				),
				'schema' => fn() => $this->get_staff_list_schema(),
			)
		);
	}

	/**
	 * List every user with an explicit POS role assignment.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return array{staff: array<int, array<string, mixed>>}
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	protected function list_staff( WP_REST_Request $request ): array {
		unset( $request );

		$user_query = new WP_User_Query(
			array(
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => Capabilities::POS_ROLE_META_KEY,
						'value'   => Capabilities::assignable_pos_roles(),
						'compare' => 'IN',
					),
				),
				'orderby'    => 'display_name',
				'order'      => 'ASC',
				'number'     => -1,
			)
		);

		$staff = array();
		foreach ( $user_query->get_results() as $user ) {
			$pos_role = Capabilities::get_pos_role( (int) $user->ID );
			if ( null === $pos_role ) {
				continue;
			}

			$staff[] = array(
				'user_id'      => (int) $user->ID,
				'user_login'   => (string) $user->user_login,
				'display_name' => (string) $user->display_name,
				'role'         => $pos_role,
				'capabilities' => Capabilities::capabilities_for_role( $pos_role ),
				'pin'          => $this->pin_service->get_public_pin_record( (int) $user->ID ),
			);
		}

		return array( 'staff' => $staff );
	}

	/**
	 * Schema describing the staff list payload.
	 *
	 * @return array<string, mixed>
	 */
	private function get_staff_list_schema(): array {
		$schema               = $this->get_base_schema();
		$schema['title']      = 'pos_staff_list';
		$schema['properties'] = array(
			'staff' => array(
				'description' => __( 'List of users with POS access.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'user_id'      => array(
							'type'    => 'integer',
							'context' => array( 'view' ),
						),
						'user_login'   => array(
							'type'    => 'string',
							'context' => array( 'view' ),
						),
						'display_name' => array(
							'type'    => 'string',
							'context' => array( 'view' ),
						),
						'role'         => array(
							'type'    => 'string',
							'context' => array( 'view' ),
							'enum'    => Capabilities::assignable_pos_roles(),
						),
						'capabilities' => array(
							'type'                 => 'object',
							'context'              => array( 'view' ),
							'additionalProperties' => array( 'type' => 'boolean' ),
						),
						'pin'          => array(
							'type'       => array( 'object', 'null' ),
							'context'    => array( 'view' ),
							'properties' => array(
								'algo'       => array( 'type' => 'string' ),
								'iterations' => array( 'type' => 'integer' ),
								'salt'       => array( 'type' => 'string' ),
								'hash'       => array( 'type' => 'string' ),
							),
						),
					),
				),
			),
		);

		return $schema;
	}
}
