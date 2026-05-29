<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST controller for the POS staff list.
 *
 * Exposes GET /wc/pos/v1/staff — the canonical staff list the mobile client caches
 * and validates PIN entry against locally. Each entry includes the user's display
 * name, role, capability map, and stored PIN hash record (or null if no PIN set).
 *
 * Permission: `manage_pos_staff`. Only admin / shop_manager users hold this cap by
 * default, which matches the design assumption that the device-level API caller is
 * always an admin / shop_manager; cashiers and POS managers never authenticate
 * against the server directly in this iteration. Gating on a weaker POS cap would
 * expose stored PIN hashes to roles that should not be able to brute-force them.
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
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'manage_pos_staff' ),
					'args'                => array(),
				),
				'schema' => fn() => $this->get_staff_list_schema(),
			)
		);
	}

	/**
	 * List every user with the `view_pos` capability along with their POS-relevant
	 * details and (if set) PIN hash record.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return array{staff: array<int, array<string, mixed>>}
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	protected function list_staff( WP_REST_Request $request ): array {
		unset( $request );

		$users = get_users(
			array(
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);

		$staff = array();
		foreach ( $users as $user ) {
			if ( ! user_can( $user->ID, 'view_pos' ) ) {
				continue;
			}

			$roles = (array) $user->roles;
			$role  = ! empty( $roles ) ? (string) reset( $roles ) : '';

			$capabilities = array();
			foreach ( (array) $user->allcaps as $cap => $granted ) {
				if ( $granted ) {
					$capabilities[ (string) $cap ] = true;
				}
			}

			$staff[] = array(
				'user_id'      => (int) $user->ID,
				'user_login'   => (string) $user->user_login,
				'display_name' => (string) $user->display_name,
				'role'         => $role,
				'capabilities' => $capabilities,
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
