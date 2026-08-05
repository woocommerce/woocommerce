<?php
/**
 * Integration tests for the lifecycle-actions REST controller: the auth + ownership
 * matrix (anonymous 401, valid owner 200, foreign owner 404, unknown id 404), the
 * action round-trips with their domain-summary responses, and the
 * illegal-transition 409.
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

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'POST', self::BASE . '/' . $id . '/hold' ) );

		$this->assertSame( 401, $response->get_status() );
		// The contract is untouched.
		$this->assertSame( ContractStatus::ACTIVE, $this->reload( $id )->get_status() );
	}

	public function test_unknown_contract_is_not_found_indistinguishably_from_foreign(): void {
		wp_set_current_user( $this->other_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'POST', self::BASE . '/4242424/hold' ) );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_options_exposes_the_action_schema(): void {
		wp_set_current_user( $this->owner_id );
		$id = $this->seed( $this->owner_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'OPTIONS', self::BASE . '/' . $id . '/hold' ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $this->data_array( $response );
		$this->assertIsArray( $data['schema'] );
		$this->assertSame( 'subscription_engine_contract_action', $data['schema']['title'] );
	}

	public function test_hold_action_on_a_foreign_contract_is_not_found(): void {
		wp_set_current_user( $this->other_id );
		$id = $this->seed( $this->owner_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'POST', self::BASE . '/' . $id . '/hold' ) );

		$this->assertSame( 404, $response->get_status() );
		// The contract is untouched.
		$this->assertSame( ContractStatus::ACTIVE, $this->reload( $id )->get_status() );
	}

	public function test_owner_hold_transitions_and_returns_the_domain_summary(): void {
		wp_set_current_user( $this->owner_id );
		$id = $this->seed( $this->owner_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'POST', self::BASE . '/' . $id . '/hold' ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $this->data_array( $response );
		// The action response is a domain summary: id + resulting status slug,
		// no view-model fields (labels, formatted values, visibility flags).
		$this->assertSame( $id, $data['id'] );
		$this->assertSame( ContractStatus::ON_HOLD, $data['status'] );
		$this->assertArrayNotHasKey( 'status_label', $data );
		$this->assertArrayNotHasKey( 'related_orders', $data );
		$this->assertSame( ContractStatus::ON_HOLD, $this->reload( $id )->get_status() );
	}

	public function test_owner_reactivate_transitions_and_returns_the_domain_summary(): void {
		wp_set_current_user( $this->owner_id );
		$id = $this->seed( $this->owner_id, ContractStatus::ON_HOLD );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'POST', self::BASE . '/' . $id . '/reactivate' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( ContractStatus::ACTIVE, $this->data_array( $response )['status'] );
		$this->assertSame( ContractStatus::ACTIVE, $this->reload( $id )->get_status() );
	}

	public function test_reactivate_on_an_already_active_contract_is_a_conflict(): void {
		// An active contract must never reach the date recompute (a past-due date
		// rolled forward would skip a charge); the guard maps to a 409.
		wp_set_current_user( $this->owner_id );
		$id     = $this->seed( $this->owner_id, ContractStatus::ACTIVE );
		$before = $this->reload( $id )->get_next_payment_gmt();

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'POST', self::BASE . '/' . $id . '/reactivate' ) );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( $before, $this->reload( $id )->get_next_payment_gmt(), 'The schedule is untouched.' );
	}

	public function test_cancel_at_period_end_winds_down_the_contract(): void {
		wp_set_current_user( $this->owner_id );
		$id = $this->seed( $this->owner_id );

		$request = new WP_REST_Request( 'POST', self::BASE . '/' . $id . '/cancel' );
		$request->set_body_params( array( 'at_period_end' => true ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		// The summary status tells the caller which cancel mode landed.
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $this->data_array( $response )['status'] );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $this->reload( $id )->get_status() );
	}

	public function test_cancel_now_terminates_the_contract(): void {
		wp_set_current_user( $this->owner_id );
		$id = $this->seed( $this->owner_id, ContractStatus::ON_HOLD );

		$request = new WP_REST_Request( 'POST', self::BASE . '/' . $id . '/cancel' );
		$request->set_body_params( array( 'at_period_end' => false ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( ContractStatus::CANCELLED, $this->data_array( $response )['status'] );
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
