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
use WP_User;

/**
 * REST controller for the POS staff list.
 *
 * Exposes GET /wc/pos/v1/staff — the canonical staff list the mobile client caches
 * and validates PIN entry against locally. Each entry includes the user's display
 * name, assigned POS preset, POS capability map, and stored PIN hash record.
 *
 * PIN is required at the wp-admin staff form layer, so every listed user is
 * guaranteed to have one — the client can rely on `pin` being present and never
 * has to render a phantom staff member it can't authenticate.
 *
 * Permission: `manage_woocommerce` — i.e. administrator + shop_manager. POS-only
 * users (cashiers / managers, labelled with the `pos_staff` WP role) never call
 * this endpoint directly; the device admin reads the staff list on their behalf
 * and PIN entry is validated client-side against the cached payload.
 *
 * @since 11.0.0
 * @internal
 */
class POSStaffController extends RestApiControllerBase {

	/**
	 * PIN service used to render each row's PIN record.
	 *
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * REST namespace for the POS routes.
	 *
	 * @var string
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
	 * Logger source identifier for this controller.
	 *
	 * {@inheritDoc}
	 */
	protected function get_rest_api_namespace(): string {
		return 'pos-staff';
	}

	/**
	 * Register the /staff route on this controller's namespace.
	 *
	 * {@inheritDoc}
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
	 * List every user with POS access that has a valid preset and PIN.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return list<array<string, mixed>>
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	protected function list_staff( WP_REST_Request $request ): array {
		unset( $request );

		$user_query = new WP_User_Query(
			array_merge(
				Capabilities::pos_staff_user_query_args(),
				array(
					'orderby' => 'display_name',
					'order'   => 'ASC',
					'number'  => -1,
				)
			)
		);

		$staff = array();
		foreach ( $user_query->get_results() as $user ) {
			if ( ! $user instanceof WP_User ) {
				continue;
			}

			// Defensive: a preset meta value can be stale or non-assignable
			// (e.g. set manually via wp-admin Users meta edit). Skip rather
			// than ship a half-formed row the client can't render.
			$preset = Capabilities::get_pos_preset( (int) $user->ID );
			if ( null === $preset ) {
				continue;
			}

			// PIN is the sole operator credential at the till; a row without one
			// would be unauthenticatable on the device. The admin form enforces
			// PIN at add/save time, but a manual edit could leave a POS-access
			// user without one — skip them so `pin` is guaranteed non-null in
			// the wire payload.
			$pin_record = $this->pin_service->get_public_pin_record( (int) $user->ID );
			if ( null === $pin_record ) {
				continue;
			}

			// Caps reported to the client are derived from the assigned preset.
			//
			// DEBUG ONLY — remove before release. When WP_DEBUG is on, report the
			// user's ACTUAL held POS caps instead, so the per-cap debug toggles on
			// the wp-admin Staff form (which edit real caps without touching the
			// preset meta) are observable on the client. For normally-managed staff
			// the two are identical, so production behavior is unchanged.
			$capabilities = Capabilities::capabilities_for_preset( $preset );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$capabilities = array();
				foreach ( Capabilities::all_pos_capabilities() as $cap ) {
					if ( ! empty( $user->allcaps[ $cap ] ) ) {
						$capabilities[ $cap ] = true;
					}
				}
			}

			$staff[] = array(
				'user_id'      => (int) $user->ID,
				'user_login'   => (string) $user->user_login,
				'display_name' => (string) $user->display_name,
				'preset'       => $preset,
				'capabilities' => $capabilities,
				'pin'          => $pin_record,
			);
		}

		return $staff;
	}

	/**
	 * Schema describing the staff list payload.
	 *
	 * @return array<string, mixed>
	 */
	private function get_staff_list_schema(): array {
		$schema          = $this->get_base_schema();
		$schema['title'] = 'pos_staff_list';
		$schema['type']  = 'array';
		$schema['items'] = array(
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
				'preset'       => array(
					'type'    => 'string',
					'context' => array( 'view' ),
					'enum'    => Capabilities::assignable_pos_presets(),
				),
				'capabilities' => array(
					'type'                 => 'object',
					'context'              => array( 'view' ),
					'additionalProperties' => array( 'type' => 'boolean' ),
				),
				'pin'          => array(
					'type'        => 'object',
					'context'     => array( 'view' ),
					'description' => 'PIN record. Always present — staff without a PIN are excluded from the list.',
					'required'    => true,
					'properties'  => array(
						'algo'       => array( 'type' => 'string' ),
						'iterations' => array( 'type' => 'integer' ),
						'salt'       => array( 'type' => 'string' ),
						'hash'       => array( 'type' => 'string' ),
					),
				),
			),
		);

		return $schema;
	}
}
