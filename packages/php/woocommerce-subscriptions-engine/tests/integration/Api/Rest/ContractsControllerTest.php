<?php
/**
 * Integration tests for the customer-portal REST controller: the auth + ownership
 * matrix (anonymous 401, valid owner 200, foreign owner 404, unknown id 404), the
 * lifecycle action round-trips, and the illegal-transition 409.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api\Rest;

use EngineIntegrationTestCase;
use WP_REST_Request;
use WP_REST_Response;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\ContractsController;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\ContractsController
 */
class ContractsControllerTest extends EngineIntegrationTestCase {

	private const BASE = '/wc/v3/subscriptions-engine/contracts';

	/**
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * @var int
	 */
	private $owner_id;

	/**
	 * @var int
	 */
	private $other_id;

	public function set_up(): void {
		parent::set_up();

		$this->contracts = new ContractRepository();

		// Register the controller on `rest_api_init` (where core requires routes to be
		// registered) and re-fire the action so the routes exist on the live server for
		// this test. Mirrors how Bootstrap wires it in production.
		add_action(
			'rest_api_init',
			static function (): void {
				( new ContractsController() )->register_routes();
			}
		);
		do_action( 'rest_api_init' );

		$this->owner_id = $this->create_customer();
		$this->other_id = $this->create_customer();
	}

	public function tear_down(): void {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Create a customer user and return its id.
	 */
	private function create_customer(): int {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		$this->assertIsInt( $user_id );

		return $user_id;
	}

	/**
	 * Seed a contract for a customer.
	 *
	 * @param int    $customer_id Owning customer.
	 * @param string $status      Status.
	 */
	private function seed( int $customer_id, string $status = ContractStatus::ACTIVE ): int {
		$contract = Contract::create(
			array(
				'customer_id'          => $customer_id,
				'status'               => $status,
				'currency'             => 'USD',
				'selling_plan_id'      => 1,
				'payment_method_title' => 'Visa ending in 4242',
				'start_gmt'            => '2026-01-01 00:00:00',
				'next_payment_gmt'     => '2099-02-01 00:00:00',
				'billing_total'        => '19.99',
			)
		);

		return $this->contracts->insert( $contract );
	}

	public function test_anonymous_request_is_unauthorized(): void {
		wp_set_current_user( 0 );
		$id = $this->seed( $this->owner_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::BASE . '/' . $id ) );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_owner_can_read_their_contract_detail(): void {
		wp_set_current_user( $this->owner_id );
		$id = $this->seed( $this->owner_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::BASE . '/' . $id ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $this->data_array( $response );
		$this->assertSame( $id, $data['id'] );
		$this->assertSame( ContractStatus::ACTIVE, $data['status'] );
		// The detail view-model shape the consumer portal expects.
		foreach ( array( 'status_label', 'recurring_summary', 'start_date', 'date_row_label', 'date_row_value', 'cancel_visible', 'hold_visible', 'reactivate_visible', 'needs_payment_notice', 'at_period_end', 'cancel_modal_copy', 'related_orders' ) as $key ) {
			$this->assertArrayHasKey( $key, $data, "detail view-model is missing '{$key}'" );
		}
	}

	public function test_foreign_owned_contract_is_not_found(): void {
		wp_set_current_user( $this->other_id );
		$id = $this->seed( $this->owner_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::BASE . '/' . $id ) );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_unknown_contract_is_not_found_indistinguishably(): void {
		wp_set_current_user( $this->other_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::BASE . '/4242424' ) );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_list_is_scoped_to_the_current_customer(): void {
		wp_set_current_user( $this->owner_id );
		$mine = $this->seed( $this->owner_id );
		$this->seed( $this->other_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::BASE ) );

		$this->assertSame( 200, $response->get_status() );
		$rows = $this->data_array( $response );
		$this->assertCount( 1, $rows );
		$this->assertIsArray( $rows[0] );
		$this->assertSame( $mine, $rows[0]['id'] );
	}

	public function test_list_serves_the_embed_row_representation_with_totals_headers(): void {
		wp_set_current_user( $this->owner_id );
		$this->seed( $this->owner_id );
		$this->seed( $this->owner_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::BASE ) );

		$this->assertSame( 200, $response->get_status() );

		$headers = $response->get_headers();
		$this->assertSame( '2', (string) ( $headers['X-WP-Total'] ?? '' ) );
		$this->assertSame( '1', (string) ( $headers['X-WP-TotalPages'] ?? '' ) );

		$rows = $this->data_array( $response );
		$this->assertIsArray( $rows[0] );
		// The embed (list-row) representation: row fields present, detail-only absent.
		foreach ( array( 'id', 'status', 'status_label', 'next_payment', 'payment_method_title', 'total' ) as $key ) {
			$this->assertArrayHasKey( $key, $rows[0], "list row is missing '{$key}'" );
		}
		$this->assertArrayNotHasKey( 'related_orders', $rows[0] );
	}

	public function test_options_exposes_the_item_schema(): void {
		wp_set_current_user( $this->owner_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'OPTIONS', self::BASE ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $this->data_array( $response );
		$this->assertIsArray( $data['schema'] );
		$this->assertSame( 'subscription_engine_contract', $data['schema']['title'] );
	}

	public function test_hold_action_on_a_foreign_contract_is_not_found(): void {
		wp_set_current_user( $this->other_id );
		$id = $this->seed( $this->owner_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'POST', self::BASE . '/' . $id . '/hold' ) );

		$this->assertSame( 404, $response->get_status() );
		// The contract is untouched.
		$this->assertSame( ContractStatus::ACTIVE, $this->reload( $id )->get_status() );
	}

	public function test_owner_hold_transitions_and_returns_the_refreshed_detail(): void {
		wp_set_current_user( $this->owner_id );
		$id = $this->seed( $this->owner_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'POST', self::BASE . '/' . $id . '/hold' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( ContractStatus::ON_HOLD, $this->data_array( $response )['status'] );
		$this->assertSame( ContractStatus::ON_HOLD, $this->reload( $id )->get_status() );
	}

	public function test_cancel_at_period_end_winds_down_the_contract(): void {
		wp_set_current_user( $this->owner_id );
		$id = $this->seed( $this->owner_id );

		$request = new WP_REST_Request( 'POST', self::BASE . '/' . $id . '/cancel' );
		$request->set_body_params( array( 'at_period_end' => true ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $this->reload( $id )->get_status() );
	}

	public function test_cancel_now_terminates_the_contract(): void {
		wp_set_current_user( $this->owner_id );
		$id = $this->seed( $this->owner_id, ContractStatus::ON_HOLD );

		$request = new WP_REST_Request( 'POST', self::BASE . '/' . $id . '/cancel' );
		$request->set_body_params( array( 'at_period_end' => false ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( ContractStatus::CANCELLED, $this->reload( $id )->get_status() );
	}

	public function test_illegal_transition_is_a_conflict(): void {
		// Reactivating an active contract is a no-op (idempotent) - so to force the
		// illegal path, try to hold a cancelled contract.
		wp_set_current_user( $this->owner_id );
		$id = $this->seed( $this->owner_id, ContractStatus::CANCELLED );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'POST', self::BASE . '/' . $id . '/hold' ) );

		$this->assertSame( 409, $response->get_status() );
	}

	/**
	 * The response body as an array (asserts it is one, narrowing offset access).
	 *
	 * @param WP_REST_Response $response The dispatched response.
	 * @return array<int|string, mixed>
	 */
	private function data_array( WP_REST_Response $response ): array {
		$data = $response->get_data();
		$this->assertIsArray( $data );

		return $data;
	}

	/**
	 * Reload a contract, asserting it still exists (narrows the nullable read).
	 *
	 * @param int $id Contract id.
	 */
	private function reload( int $id ): Contract {
		$contract = $this->contracts->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );

		return $contract;
	}
}
