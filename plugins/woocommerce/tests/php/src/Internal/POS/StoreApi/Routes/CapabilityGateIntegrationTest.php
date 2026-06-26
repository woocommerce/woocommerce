<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CapabilityGate;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * End-to-end coverage for CapabilityGate over the shared Store API routes.
 *
 * Confirms the gate's WP_Error short-circuits the REST dispatch into a real
 * forbidden response, and that an authorised operator is let through — the
 * detection logic itself is unit-tested in ContextTest.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CapabilityGate
 */
class CapabilityGateIntegrationTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CapabilityGate
	 */
	private $sut;

	/**
	 * Original current_user_id captured in setUp so it can be restored.
	 *
	 * @var int
	 */
	private $original_user_id;

	/**
	 * Register the gate and capture the current user.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CapabilityGate();
		$this->sut->register();
		$this->original_user_id = get_current_user_id();
	}

	/**
	 * Remove the filter and restore the user/override mutated by the test.
	 */
	public function tearDown(): void {
		remove_filter( 'rest_dispatch_request', array( $this->sut, 'enforce_capability' ), 5 );
		Context::set_test_override( null );
		wp_set_current_user( $this->original_user_id );
		parent::tearDown();
	}

	/**
	 * @testdox a POS request from a user without the operator capability is rejected with 403.
	 */
	public function test_pos_request_without_capability_is_forbidden(): void {
		Context::set_test_override( true );
		$subscriber_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'woocommerce_pos_rest_forbidden', $response->get_data()['code'] );

		wp_delete_user( $subscriber_id );
	}

	/**
	 * @testdox a POS request from an authorised operator is not blocked by the gate.
	 */
	public function test_pos_request_with_capability_passes_gate(): void {
		Context::set_test_override( true );
		$admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );

		$this->assertNotSame( 'woocommerce_pos_rest_forbidden', $response->get_data()['code'] ?? null );
		$this->assertSame( 200, $response->get_status() );

		wp_delete_user( $admin_id );
	}
}
