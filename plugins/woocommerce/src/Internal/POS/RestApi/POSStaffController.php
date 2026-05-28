<?php
/**
 * POSStaffController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\Actors\AccessProfileRegistry;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Automattic\WooCommerce\Internal\StoreActors\ActorAccessRepository;
use Automattic\WooCommerce\Internal\StoreActors\ActorRepository;
use WP_REST_Request;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller for the POS staff list.
 *
 * Exposes GET /wc/pos/v1/staff — the canonical staff list the mobile client
 * caches and validates PIN entry against locally. Each entry includes the
 * staff member's identity, resolved access profile + permissions, and stored
 * PIN hash record (or null if no PIN set).
 *
 * The internal model uses generic "store actor" terminology (the wc_store_actors
 * table is identity-only and may be reused by future non-POS staff features),
 * but the public REST surface is POS-specific — hence `/staff`.
 *
 * Permission: `manage_woocommerce`. Only admin / shop_manager users hold this
 * cap by default. Gating on a weaker capability would expose stored PIN
 * hashes to roles that should not be able to brute-force them.
 *
 * @internal Owned by the Point of Sale staff (actors) feature.
 * @since 10.9.0
 */
class POSStaffController extends RestApiControllerBase {

	/**
	 * The route namespace used in `register_rest_route()`.
	 *
	 * @var string
	 */
	protected string $route_namespace = 'wc/pos/v1';

	/**
	 * @var ActorRepository
	 */
	private ActorRepository $actor_repo;

	/**
	 * @var ActorAccessRepository
	 */
	private ActorAccessRepository $access_repo;

	/**
	 * @var AccessProfileRegistry
	 */
	private AccessProfileRegistry $profiles;

	/**
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * DI init.
	 *
	 * @internal
	 *
	 * @param ActorRepository       $actor_repo  Actor repository.
	 * @param ActorAccessRepository $access_repo Actor access repository.
	 * @param AccessProfileRegistry $profiles    Access profile registry.
	 * @param POSPinService         $pin_service PIN service.
	 * @return void
	 */
	final public function init(
		ActorRepository $actor_repo,
		ActorAccessRepository $access_repo,
		AccessProfileRegistry $profiles,
		POSPinService $pin_service
	): void {
		$this->actor_repo  = $actor_repo;
		$this->access_repo = $access_repo;
		$this->profiles    = $profiles;
		$this->pin_service = $pin_service;
	}

	/**
	 * @inheritDoc
	 */
	protected function get_rest_api_namespace(): string {
		return 'wc/pos';
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
	 * List active POS staff members. Returns a flat array (no top-level
	 * wrapper) to match `/wc/v3/orders`, `/wc/v3/products`, etc.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return array<int, array<string, mixed>>
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	protected function list_staff( WP_REST_Request $request ): array {
		unset( $request );

		$actors  = $this->actor_repo->list_active();
		$payload = array();

		foreach ( $actors as $actor ) {
			$access = $this->access_repo->get_for_actor( (int) $actor['actor_id'] );

			// Skip staff members without an active POS access row.
			if ( null === $access || ActorAccessRepository::STATUS_ACTIVE !== ( $access['status'] ?? '' ) ) {
				continue;
			}

			$payload[] = $this->serialize_staff( $actor, $access );
		}

		return $payload;
	}

	/**
	 * Build the response shape for one staff member + access row pair.
	 *
	 * @param array<string, mixed> $actor  Actor row.
	 * @param array<string, mixed> $access Access row.
	 * @return array<string, mixed>
	 */
	private function serialize_staff( array $actor, array $access ): array {
		$profile_key = (string) ( $access['access_profile_key'] ?? '' );
		$profile     = $this->profiles->get( $profile_key );

		$permissions = array();
		if ( null !== $profile ) {
			foreach ( $profile['permissions'] as $tag => $value ) {
				$permissions[ (string) $tag ] = (string) $value;
			}
		}

		return array(
			'id'               => (int) $actor['actor_id'],
			'uuid'             => (string) $actor['actor_uuid'],
			'wp_user_id'       => isset( $actor['wp_user_id'] ) ? (int) $actor['wp_user_id'] : null,
			'status'           => (string) $actor['status'],
			'display_name'     => (string) $actor['display_name'],
			'first_name'       => self::nullable_string( $actor['first_name'] ?? null ),
			'last_name'        => self::nullable_string( $actor['last_name'] ?? null ),
			'email'            => self::nullable_string( $actor['email'] ?? null ),
			'pos_access'       => array(
				'profile_key'  => $profile_key,
				'profile_name' => null !== $profile ? (string) $profile['name'] : '',
				'permissions'  => $permissions,
				'credential'   => $this->pin_service->get_public_pin_record( (int) $actor['actor_id'] ),
			),
			'date_updated_gmt' => (string) $actor['date_updated_gmt'],
		);
	}

	/**
	 * Normalize a nullable string field — empty strings collapse to null so
	 * the response cleanly distinguishes "not set" from a real value.
	 *
	 * @param mixed $value Raw value from the row.
	 * @return string|null
	 */
	private static function nullable_string( $value ): ?string {
		if ( null === $value ) {
			return null;
		}
		$value = (string) $value;
		return '' === $value ? null : $value;
	}

	/**
	 * Schema describing the staff list payload (top-level array of staff
	 * objects, following the `/wc/v3` convention for collection endpoints).
	 *
	 * Permission values use a tri-state enum: `allow`, `deny`, or
	 * `approval_required`. The map shape (tag => state) matches WordPress'
	 * `WP_User::$allcaps` convention.
	 *
	 * @return array<string, mixed>
	 */
	private function get_staff_list_schema(): array {
		$schema             = $this->get_base_schema();
		$schema['title']    = 'pos_staff_list';
		$schema['type']     = 'array';
		$schema['readonly'] = true;
		$schema['items']    = array(
			'type'       => 'object',
			'properties' => array(
				'id'               => array( 'type' => 'integer' ),
				'uuid'             => array( 'type' => 'string' ),
				'wp_user_id'       => array( 'type' => array( 'integer', 'null' ) ),
				'status'           => array( 'type' => 'string' ),
				'display_name'     => array( 'type' => 'string' ),
				'first_name'       => array( 'type' => array( 'string', 'null' ) ),
				'last_name'        => array( 'type' => array( 'string', 'null' ) ),
				'email'            => array( 'type' => array( 'string', 'null' ) ),
				'pos_access'       => array(
					'type'       => 'object',
					'properties' => array(
						'profile_key'  => array( 'type' => 'string' ),
						'profile_name' => array( 'type' => 'string' ),
						'permissions'  => array(
							'type'                 => 'object',
							'description'          => __( 'Tri-state permission map keyed by permission tag. Each value is allow, deny, or approval_required.', 'woocommerce' ),
							'additionalProperties' => array(
								'type' => 'string',
								'enum' => array(
									AccessProfileRegistry::ACCESS_ALLOW,
									AccessProfileRegistry::ACCESS_DENY,
									AccessProfileRegistry::ACCESS_APPROVAL_REQUIRED,
								),
							),
						),
						'credential'   => array(
							'type'       => array( 'object', 'null' ),
							'properties' => array(
								'algo'       => array( 'type' => 'string' ),
								'iterations' => array( 'type' => 'integer' ),
								'salt'       => array( 'type' => 'string' ),
								'hash'       => array( 'type' => 'string' ),
								'updated_at' => array( 'type' => array( 'string', 'null' ) ),
							),
						),
					),
				),
				'date_updated_gmt' => array( 'type' => 'string' ),
			),
		);
		// Collection endpoints don't have a meaningful single-object schema; drop the inherited "properties".
		unset( $schema['properties'] );

		return $schema;
	}
}
