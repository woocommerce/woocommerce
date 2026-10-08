<?php
/**
 * Integration tests for the contracts REST controller.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api\Rest;

use EngineIntegrationTestCase;
use WP_REST_Request;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\ContractsController
 */
class ContractsControllerTest extends EngineIntegrationTestCase {

	private const BASE = '/wc/v3/subscriptions-engine/contracts';

	public function set_up(): void {
		parent::set_up();

		// A fresh server fires `rest_api_init`, so the routes come from the engine's own wiring.
		$GLOBALS['wp_rest_server'] = null;
		rest_get_server();
	}

	public function tear_down(): void {
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
	 * Only the read route is registered: no lifecycle action routes.
	 */
	public function test_registers_only_the_read_route(): void {
		$routes = array_filter(
			array_keys( rest_get_server()->get_routes() ),
			static function ( string $route ): bool {
				return 0 === strpos( $route, self::BASE );
			}
		);

		$this->assertSame( array( self::BASE . '/(?P<id>[\d]+)' ), array_values( $routes ) );
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
	 * @param \WP_REST_Response $response Response.
	 * @return array<array-key, mixed>
	 */
	private function response_data( \WP_REST_Response $response ): array {
		$data = $response->get_data();
		$this->assertIsArray( $data );

		return $data;
	}

	/**
	 * Dispatch a GET for one contract.
	 *
	 * @param int $contract_id Contract id.
	 */
	private function get( int $contract_id ): \WP_REST_Response {
		return rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::BASE . '/' . $contract_id ) );
	}
}
