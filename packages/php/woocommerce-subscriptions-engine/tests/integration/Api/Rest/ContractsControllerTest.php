<?php
/**
 * Integration tests for the contracts REST controller.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api\Rest;

use EngineIntegrationTestCase;
use RuntimeException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractActions;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Rest\ContractActionRegistry;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\ContractsController
 */
class ContractsControllerTest extends EngineIntegrationTestCase {

	private const BASE = '/wc/v3/subscriptions-engine/contracts';

	private const EXTENSION_SLUG = 'test-extension';

	/**
	 * Action callback calls, as `array( action, contract id, action_args )`.
	 *
	 * @var array<int, array{0: string, 1: int, 2: array<string, mixed>}>
	 */
	private $calls = array();

	public function set_up(): void {
		parent::set_up();

		ContractActionRegistry::reset();
		$this->calls = array();

		// A fresh server fires `rest_api_init`, so the routes come from the engine's own wiring.
		$GLOBALS['wp_rest_server'] = null;
		rest_get_server();
	}

	public function tear_down(): void {
		ContractActionRegistry::reset();
		$GLOBALS['wp_rest_server'] = null;
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * A store manager reads the stored contract facts, children included.
	 */
	public function test_get_returns_the_contract_to_a_store_manager(): void {
		$contract = Contracts::create(
			array(
				'extension_slug'   => 'test-extension',
				'status'           => 'active',
				'customer_id'      => 7,
				'currency'         => 'EUR',
				'next_payment_gmt' => '2026-11-01 10:00:00',
				'billing_total'    => '20.00',
				'items'            => array(
					array(
						'item_name'  => 'Coffee',
						'product_id' => 9,
						'quantity'   => '2',
						'total'      => '20',
					),
				),
				'addresses'        => array(
					'billing' => array(
						'first_name' => 'Ada',
						'country'    => 'PT',
					),
				),
			)
		);
		wp_set_current_user( $this->create_user( 'administrator' ) );

		$response = $this->get( $contract->get_id() );

		$this->assertSame( 200, $response->get_status() );
		$data = $this->response_data( $response );
		$this->assertSame( $contract->get_id(), $data['id'] );
		$this->assertSame( 'test-extension', $data['extension_slug'] );
		$this->assertSame( 'active', $data['status'] );
		$this->assertSame( 7, $data['customer_id'] );
		$this->assertSame( 'EUR', $data['currency'] );
		$this->assertSame( '2026-11-01T10:00:00', $data['next_payment_gmt'] );
		$this->assertNull( $data['end_gmt'] );
		$stored = Contracts::get( $contract->get_id() );
		$this->assertNotNull( $stored );
		$this->assertSame( $stored->get_billing_total(), $data['billing_total'] );
		$this->assertSame( $stored->get_items(), $data['items'] );
		$this->assertSame( $stored->get_addresses(), $data['addresses'] );
		$this->assertNotEmpty( $stored->get_items() );
		$this->assertNotEmpty( $stored->get_addresses() );
	}

	/**
	 * A contract without addresses encodes them as an empty JSON object.
	 */
	public function test_get_encodes_no_addresses_as_an_object(): void {
		$contract = Contracts::create(
			array(
				'extension_slug' => 'test-extension',
				'status'         => 'active',
				'customer_id'    => 7,
				'currency'       => 'EUR',
			)
		);
		wp_set_current_user( $this->create_user( 'administrator' ) );

		$response = $this->get( $contract->get_id() );

		$this->assertStringContainsString( '"addresses":{}', (string) wp_json_encode( $response->get_data() ) );
	}

	/**
	 * An unknown id is a 404.
	 */
	public function test_get_unknown_contract_is_not_found(): void {
		wp_set_current_user( $this->create_user( 'administrator' ) );

		$response = $this->get( 999999 );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'woocommerce_subscriptions_engine_contract_not_found', $this->response_data( $response )['code'] );
	}

	/**
	 * Anonymous requests get a 401 and customers a 403, even for their own contract.
	 */
	public function test_get_requires_a_store_manager(): void {
		$customer_id = $this->create_user( 'customer' );
		$contract    = Contracts::create(
			array(
				'extension_slug' => 'test-extension',
				'customer_id'    => $customer_id,
			)
		);

		$this->assertSame( 401, $this->get( $contract->get_id() )->get_status() );

		wp_set_current_user( $customer_id );
		$this->assertSame( 403, $this->get( $contract->get_id() )->get_status() );
	}

	/**
	 * The engine serves the read route and the action route, nothing else.
	 */
	public function test_registers_the_read_and_action_routes(): void {
		$routes = array_filter(
			array_keys( rest_get_server()->get_routes() ),
			static function ( string $route ): bool {
				return 0 === strpos( $route, self::BASE );
			}
		);

		$this->assertSame( array( self::BASE . '/(?P<id>[\d]+)', self::BASE . '/(?P<id>[\d]+)/action' ), array_values( $routes ) );
	}

	/**
	 * Anonymous callers get a 401 on both action routes.
	 */
	public function test_actions_require_a_logged_in_user(): void {
		$this->register_action( 'pause', 'customer' );
		$contract = $this->create_contract( $this->create_user( 'customer' ) );

		$this->assertSame( 401, $this->list_actions( $contract->get_id() )->get_status() );
		$this->assertSame( 401, $this->run_action( $contract->get_id(), 'pause' )->get_status() );
		$this->assertSame( array(), $this->calls );
	}

	/**
	 * The customer preset lets the contract's customer run the action: the callback gets the
	 * contract and the validated args, and the response is the resulting id and status.
	 */
	public function test_customer_runs_an_action_on_their_contract(): void {
		$customer_id = $this->create_user( 'customer' );
		$contract    = $this->create_contract( $customer_id );
		$this->register_action( 'pause', 'customer', array( 'args' => array( 'note' => array( 'type' => 'string' ) ) ) );
		wp_set_current_user( $customer_id );

		$response = $this->run_action( $contract->get_id(), 'pause', array( 'note' => 'Away' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'id'     => $contract->get_id(),
				'status' => 'on-hold',
			),
			$response->get_data()
		);
		$this->assertSame( array( array( 'pause', $contract->get_id(), array( 'note' => 'Away' ) ) ), $this->calls );
	}

	/**
	 * Unknown contract, a contract of another customer, an unregistered action and a wrong
	 * `extension_slug` all get the same 404, and nothing runs.
	 */
	public function test_post_hides_contracts_the_caller_cannot_act_on(): void {
		$customer_id = $this->create_user( 'customer' );
		$contract    = $this->create_contract( $customer_id );
		$foreign     = $this->create_contract( $this->create_user( 'customer' ) );
		$this->register_action( 'pause', 'customer' );
		wp_set_current_user( $customer_id );

		$responses = array(
			$this->run_action( 999999, 'pause' ),
			$this->run_action( $foreign->get_id(), 'pause' ),
			$this->run_action( $contract->get_id(), 'resume' ),
			$this->run_action( $contract->get_id(), 'pause', array(), 'other-extension' ),
		);

		$not_found = $this->response_data( $responses[0] );
		$this->assertSame( 'woocommerce_subscriptions_engine_contract_not_found', $not_found['code'] );
		foreach ( $responses as $response ) {
			$this->assertSame( 404, $response->get_status() );
			$this->assertSame( $not_found, $response->get_data() );
		}
		$this->assertSame( array(), $this->calls );
	}

	/**
	 * Only the contract owner's action runs, even when another extension registered the same name.
	 */
	public function test_dispatches_only_to_the_contract_owner(): void {
		$this->register_action( 'pause', 'manager' );
		ContractActions::register(
			'other-extension',
			'pause',
			array(
				'callback'   => function ( ContractView $contract ): ContractView {
					$this->calls[] = array( 'other-extension', $contract->get_id(), array() );
					return $contract;
				},
				'permission' => 'manager',
			)
		);
		$contract = $this->create_contract( null, 'other-extension' );
		wp_set_current_user( $this->create_user( 'administrator' ) );

		$this->assertSame( 404, $this->run_action( $contract->get_id(), 'pause' )->get_status() );
		$this->assertSame( 200, $this->run_action( $contract->get_id(), 'pause', array(), 'other-extension' )->get_status() );
		$this->assertSame( array( array( 'other-extension', $contract->get_id(), array() ) ), $this->calls );
	}

	/**
	 * The manager preset admits store managers and hides the contract from its own customer.
	 */
	public function test_manager_preset(): void {
		$customer_id = $this->create_user( 'customer' );
		$contract    = $this->create_contract( $customer_id );
		$this->register_action( 'pause', 'manager' );

		wp_set_current_user( $customer_id );
		$this->assertSame( 404, $this->run_action( $contract->get_id(), 'pause' )->get_status() );

		wp_set_current_user( $this->create_user( 'shop_manager' ) );
		$this->assertSame( 200, $this->run_action( $contract->get_id(), 'pause' )->get_status() );
	}

	/**
	 * A callable permission gets the contract and the request; anything but true is a 404.
	 */
	public function test_callable_permission(): void {
		$contract = $this->create_contract( null );
		$seen     = array();
		$this->register_action(
			'pause',
			static function ( ContractView $view, WP_REST_Request $request ) use ( &$seen ): bool {
				$seen[] = array( $view->get_id(), $request->get_param( 'action' ) );
				return 'yes' === $request->get_header( 'x-test-permission' );
			}
		);
		wp_set_current_user( $this->create_user( 'customer' ) );

		$this->assertSame( 404, $this->run_action( $contract->get_id(), 'pause' )->get_status() );

		$request = $this->action_request( $contract->get_id(), 'pause' );
		$request->set_header( 'x-test-permission', 'yes' );
		$this->assertSame( 200, rest_get_server()->dispatch( $request )->get_status() );
		$this->assertSame( array( array( $contract->get_id(), 'pause' ), array( $contract->get_id(), 'pause' ) ), $seen );
	}

	/**
	 * An action that is not available for the contract is a 409, and nothing runs.
	 */
	public function test_unavailable_action_is_a_conflict(): void {
		$contract = $this->create_contract( null );
		$this->register_action(
			'pause',
			'manager',
			array(
				'is_available' => static function ( ContractView $view ): bool {
					return 'on-hold' !== $view->get_status();
				},
			)
		);
		wp_set_current_user( $this->create_user( 'administrator' ) );
		$this->assertSame( 200, $this->run_action( $contract->get_id(), 'pause' )->get_status() );

		$response = $this->run_action( $contract->get_id(), 'pause' );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'woocommerce_subscriptions_engine_action_not_available', $this->response_data( $response )['code'] );
		$this->assertCount( 1, $this->calls );
	}

	/**
	 * `action_args` are checked against the schema: required and typed properties reject with a
	 * 400, defaults fill in, and unknown keys are dropped.
	 */
	public function test_action_args_are_validated_against_the_schema(): void {
		$contract = $this->create_contract( null );
		$this->register_action(
			'cancel',
			'manager',
			array(
				'args' => array(
					'at_period_end' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'reason'        => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
		wp_set_current_user( $this->create_user( 'administrator' ) );

		$missing = $this->run_action( $contract->get_id(), 'cancel' );
		$this->assertSame( 400, $missing->get_status() );
		$this->assertSame( 'woocommerce_subscriptions_engine_invalid_action_args', $this->response_data( $missing )['code'] );

		$mistyped = $this->run_action(
			$contract->get_id(),
			'cancel',
			array(
				'reason'        => 'Moving',
				'at_period_end' => 'sometimes',
			)
		);
		$this->assertSame( 400, $mistyped->get_status() );
		$this->assertCount( 0, $this->calls );

		$response = $this->run_action(
			$contract->get_id(),
			'cancel',
			array(
				'reason' => 'Moving',
				'extra'  => 'dropped',
			)
		);
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'reason'        => 'Moving',
				'at_period_end' => true,
			),
			$this->get_recorded_action_args( 0 )
		);

		$this->assertSame(
			200,
			$this->run_action(
				$contract->get_id(),
				'cancel',
				array(
					'reason'        => 'Moving',
					'at_period_end' => 'false',
				)
			)->get_status()
		);
		$this->assertFalse( $this->get_recorded_action_args( 1 )['at_period_end'] );
	}

	/**
	 * A callable `args` resolves per contract, the same way for discovery and for running.
	 */
	public function test_callable_args_resolve_per_contract(): void {
		$contract = $this->create_contract( null );
		$this->register_action(
			'cancel',
			'manager',
			array(
				'args' => static function ( ContractView $view ): array {
					return array(
						'at_period_end' => array(
							'type'    => 'boolean',
							'default' => 'active' === $view->get_status(),
						),
					);
				},
			)
		);
		wp_set_current_user( $this->create_user( 'administrator' ) );

		$actions = $this->response_data( $this->list_actions( $contract->get_id() ) )['actions'];
		$this->assertEquals(
			array(
				array(
					'action'         => 'cancel',
					'extension_slug' => self::EXTENSION_SLUG,
					'description'    => '',
					'args'           => (object) array(
						'at_period_end' => array(
							'type'     => 'boolean',
							'default'  => true,
							'required' => false,
						),
					),
				),
			),
			$actions
		);

		$this->assertSame( 200, $this->run_action( $contract->get_id(), 'cancel' )->get_status() );
		$this->assertSame( array( 'at_period_end' => true ), $this->get_recorded_action_args( 0 ) );
	}

	/**
	 * A callback `WP_Error` passes through with its status, or 400 without one.
	 */
	public function test_callback_errors_pass_through(): void {
		$contract = $this->create_contract( null );
		ContractActions::register(
			self::EXTENSION_SLUG,
			'teapot',
			array(
				'callback'   => static function (): WP_Error {
					return new WP_Error( 'teapot', 'Short and stout.', array( 'status' => 418 ) );
				},
				'permission' => 'manager',
			)
		);
		ContractActions::register(
			self::EXTENSION_SLUG,
			'refuse',
			array(
				'callback'   => static function (): WP_Error {
					return new WP_Error( 'refused', 'No.' );
				},
				'permission' => 'manager',
			)
		);
		wp_set_current_user( $this->create_user( 'administrator' ) );

		$teapot = $this->run_action( $contract->get_id(), 'teapot' );
		$this->assertSame( 418, $teapot->get_status() );
		$this->assertSame( 'teapot', $this->response_data( $teapot )['code'] );

		$refused = $this->run_action( $contract->get_id(), 'refuse' );
		$this->assertSame( 400, $refused->get_status() );
		$this->assertSame( 'refused', $this->response_data( $refused )['code'] );
	}

	/**
	 * A throwing callback, availability check or permission callable is a generic 500.
	 */
	public function test_throwing_extension_callables_are_a_server_error(): void {
		$contract = $this->create_contract( null );
		$throw    = static function (): bool {
			throw new RuntimeException( 'Boom.' );
		};
		ContractActions::register(
			self::EXTENSION_SLUG,
			'callback',
			array(
				'callback'   => $throw,
				'permission' => 'manager',
			)
		);
		$this->register_action( 'availability', 'manager', array( 'is_available' => $throw ) );
		$this->register_action( 'permission', $throw );
		wp_set_current_user( $this->create_user( 'administrator' ) );

		foreach ( array( 'callback', 'availability', 'permission' ) as $action ) {
			$response = $this->run_action( $contract->get_id(), $action );
			$this->assertSame( 500, $response->get_status(), $action );
			$this->assertSame( 'woocommerce_subscriptions_engine_action_failed', $this->response_data( $response )['code'] );
		}
	}

	/**
	 * Discovery lists the permitted actions available now; `?action=` narrows to one.
	 */
	public function test_discovery_lists_permitted_available_actions(): void {
		$customer_id = $this->create_user( 'customer' );
		$contract    = $this->create_contract( $customer_id );
		$this->register_action( 'pause', 'customer', array( 'description' => 'Pause deliveries.' ) );
		$this->register_action( 'resume', 'customer', array( 'is_available' => '__return_false' ) );
		$this->register_action( 'refund', 'manager' );
		wp_set_current_user( $customer_id );

		$response = $this->list_actions( $contract->get_id() );

		$this->assertSame( 200, $response->get_status() );
		$this->assertEquals(
			array(
				'actions' => array(
					array(
						'action'         => 'pause',
						'extension_slug' => self::EXTENSION_SLUG,
						'description'    => 'Pause deliveries.',
						'args'           => new \stdClass(),
					),
				),
			),
			$response->get_data()
		);
		$this->assertStringContainsString( '"args":{}', (string) wp_json_encode( $response->get_data() ), 'No args encode as an empty JSON object.' );
		$this->assertSame( array( 'actions' => array() ), $this->list_actions( $contract->get_id(), 'resume' )->get_data() );
		$this->assertSame( 404, $this->list_actions( $contract->get_id(), 'refund' )->get_status() );
		$this->assertSame( 404, $this->list_actions( $contract->get_id(), 'unknown' )->get_status() );
	}

	/**
	 * Discovery is a 404 for an unknown contract and for one the caller has no permitted action on.
	 */
	public function test_discovery_hides_contracts_the_caller_cannot_act_on(): void {
		$this->register_action( 'pause', 'customer' );
		$foreign = $this->create_contract( $this->create_user( 'customer' ) );
		wp_set_current_user( $this->create_user( 'customer' ) );

		$this->assertSame( 404, $this->list_actions( 999999 )->get_status() );
		$this->assertSame( 404, $this->list_actions( $foreign->get_id() )->get_status() );
	}

	/**
	 * An action registered after the routes still dispatches.
	 */
	public function test_actions_registered_after_rest_api_init_dispatch(): void {
		$contract = $this->create_contract( null );
		wp_set_current_user( $this->create_user( 'administrator' ) );
		$this->assertSame( 404, $this->run_action( $contract->get_id(), 'pause' )->get_status() );

		$this->register_action( 'pause', 'manager' );

		$this->assertSame( 200, $this->run_action( $contract->get_id(), 'pause' )->get_status() );
	}

	/**
	 * Register a test-extension action whose callback records the call and puts the contract on hold.
	 *
	 * @param string               $action     Action slug.
	 * @param string|callable      $permission Permission.
	 * @param array<string, mixed> $extra      Further registration args.
	 */
	private function register_action( string $action, $permission, array $extra = array() ): void {
		ContractActions::register(
			self::EXTENSION_SLUG,
			$action,
			array(
				'callback'   => function ( ContractView $contract, array $action_args ) use ( $action ): ?ContractView {
					$this->calls[] = array( $action, $contract->get_id(), $action_args );
					return Contracts::update( $contract->get_id(), array( 'status' => 'on-hold' ) );
				},
				'permission' => $permission,
			) + $extra
		);
	}

	/**
	 * The `action_args` an action callback received, by call order.
	 *
	 * @param int $index Call index.
	 * @return array<string, mixed>
	 */
	private function get_recorded_action_args( int $index ): array {
		$this->assertArrayHasKey( $index, $this->calls );

		return $this->calls[ $index ][2];
	}

	/**
	 * An active contract.
	 *
	 * @param int|null $customer_id    Customer user id.
	 * @param string   $extension_slug Owning extension.
	 */
	private function create_contract( ?int $customer_id, string $extension_slug = self::EXTENSION_SLUG ): ContractView {
		return Contracts::create(
			array(
				'extension_slug' => $extension_slug,
				'status'         => 'active',
				'customer_id'    => $customer_id,
			)
		);
	}

	/**
	 * Build a POST to the action route.
	 *
	 * @param int                  $contract_id    Contract id.
	 * @param string               $action         Action slug.
	 * @param array<string, mixed> $action_args    Action args.
	 * @param string               $extension_slug Extension slug sent in the body.
	 */
	private function action_request( int $contract_id, string $action, array $action_args = array(), string $extension_slug = self::EXTENSION_SLUG ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', self::BASE . '/' . $contract_id . '/action' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			(string) wp_json_encode(
				array(
					'action'         => $action,
					'extension_slug' => $extension_slug,
					'action_args'    => (object) $action_args,
				)
			)
		);

		return $request;
	}

	/**
	 * POST an action.
	 *
	 * @param int                  $contract_id    Contract id.
	 * @param string               $action         Action slug.
	 * @param array<string, mixed> $action_args    Action args.
	 * @param string               $extension_slug Extension slug sent in the body.
	 */
	private function run_action( int $contract_id, string $action, array $action_args = array(), string $extension_slug = self::EXTENSION_SLUG ): WP_REST_Response {
		return rest_get_server()->dispatch( $this->action_request( $contract_id, $action, $action_args, $extension_slug ) );
	}

	/**
	 * GET the action list.
	 *
	 * @param int         $contract_id Contract id.
	 * @param string|null $action      Optional action filter.
	 */
	private function list_actions( int $contract_id, ?string $action = null ): WP_REST_Response {
		$request = new WP_REST_Request( 'GET', self::BASE . '/' . $contract_id . '/action' );
		if ( null !== $action ) {
			$request->set_query_params( array( 'action' => $action ) );
		}

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Create a user with a role.
	 *
	 * @param string $role Role slug.
	 */
	private function create_user( string $role ): int {
		$user_id = self::factory()->user->create( array( 'role' => $role ) );
		$this->assertIsInt( $user_id );

		return $user_id;
	}

	/**
	 * Get response data as an array.
	 *
	 * @param WP_REST_Response $response Response.
	 * @return array<array-key, mixed>
	 */
	private function response_data( WP_REST_Response $response ): array {
		$data = $response->get_data();
		$this->assertIsArray( $data );

		return $data;
	}

	/**
	 * Dispatch a GET for one contract.
	 *
	 * @param int $contract_id Contract id.
	 */
	private function get( int $contract_id ): WP_REST_Response {
		return rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::BASE . '/' . $contract_id ) );
	}
}
