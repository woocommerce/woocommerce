<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Auth\POSRequestContext;
use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WP_User;

/**
 * POC-only debug endpoint: GET /wc/pos/v1/whoami.
 *
 * Reports the effective current user as the server sees it, so the POS staff current-user swap can
 * be verified end-to-end with zero side effects. A structural POS request (`/wc/pos/v1/*`) carrying
 * valid staff PIN headers is swapped to the staff member by POSAuthHandler, and this endpoint then
 * returns that staff member — proving the structural (Tier 1) detection + swap path.
 *
 * NOT FOR PRODUCTION — remove before this feature ships. Permission is "holds POS access" (not
 * manage_woocommerce) so a swapped-in cashier passes; a plain device admin without POS caps is
 * denied, which is the intended demonstration.
 *
 * @since 11.0.0
 * @internal
 */
class POSWhoamiController extends RestApiControllerBase {

	/**
	 * The REST route namespace for the POS endpoints.
	 *
	 * @var string
	 */
	protected string $route_namespace = 'wc/pos/v1';

	/**
	 * Request context detector, surfaced for debugging the detection tier.
	 *
	 * @var POSRequestContext
	 */
	private POSRequestContext $request_context;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @internal
	 *
	 * @param POSRequestContext $request_context The request-shape detector.
	 */
	final public function init( POSRequestContext $request_context ): void {
		$this->request_context = $request_context;
	}

	/**
	 * Logger source identifier for this controller.
	 *
	 * {@inheritDoc}
	 */
	protected function get_rest_api_namespace(): string {
		return 'pos-whoami';
	}

	/**
	 * Register the /whoami route.
	 *
	 * {@inheritDoc}
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			'/whoami',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_whoami' ),
					'permission_callback' => fn( $request ) => $this->check_pos_access( $request ),
					'args'                => array(),
				),
				'schema' => fn() => $this->get_whoami_schema(),
			)
		);
	}

	/**
	 * Return the effective current user as the server resolved it.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return array<string, mixed>
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	protected function get_whoami( WP_REST_Request $request ): array {
		unset( $request );

		$user_id = get_current_user_id();
		$user    = $user_id > 0 ? get_userdata( $user_id ) : null;

		return array(
			'user_id'        => $user_id,
			'user_login'     => $user instanceof WP_User ? (string) $user->user_login : '',
			'display_name'   => $user instanceof WP_User ? (string) $user->display_name : '',
			'roles'          => $user instanceof WP_User ? array_values( $user->roles ) : array(),
			'has_pos_access' => Capabilities::has_pos_access( $user_id ),
			'pos_request'    => $this->request_context->is_pos_request(),
			'pos_tier'       => $this->request_context->tier(),
		);
	}

	/**
	 * Permission check: the effective current user must hold POS access.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return bool|WP_Error
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	private function check_pos_access( WP_REST_Request $request ) {
		unset( $request );

		if ( Capabilities::has_pos_access( get_current_user_id() ) ) {
			return true;
		}

		return new WP_Error(
			'woocommerce_rest_forbidden',
			__( 'Sorry, you do not have POS access.', 'woocommerce' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Schema for the whoami payload.
	 *
	 * @return array<string, mixed>
	 */
	private function get_whoami_schema(): array {
		$schema               = $this->get_base_schema();
		$schema['title']      = 'pos_whoami';
		$schema['type']       = 'object';
		$schema['properties'] = array(
			'user_id'        => array(
				'type'    => 'integer',
				'context' => array( 'view' ),
			),
			'user_login'     => array(
				'type'    => 'string',
				'context' => array( 'view' ),
			),
			'display_name'   => array(
				'type'    => 'string',
				'context' => array( 'view' ),
			),
			'roles'          => array(
				'type'    => 'array',
				'items'   => array( 'type' => 'string' ),
				'context' => array( 'view' ),
			),
			'has_pos_access' => array(
				'type'    => 'boolean',
				'context' => array( 'view' ),
			),
			'pos_request'    => array(
				'type'    => 'boolean',
				'context' => array( 'view' ),
			),
			'pos_tier'       => array(
				'type'    => 'string',
				'context' => array( 'view' ),
			),
		);

		return $schema;
	}
}
