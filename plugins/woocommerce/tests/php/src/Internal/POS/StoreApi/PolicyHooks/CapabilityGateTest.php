<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CapabilityGate;
use WC_Unit_Test_Case;
use WP_Error;
use WP_REST_Response;

/**
 * Tests for CapabilityGate.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CapabilityGate
 */
class CapabilityGateTest extends WC_Unit_Test_Case {

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
	 * Admin user with manage_woocommerce.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Set up the system under test and capture the current user.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut              = new CapabilityGate();
		$this->original_user_id = get_current_user_id();
		$this->admin_id         = $this->factory()->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Remove the filter and restore the user/override mutated by the test.
	 */
	public function tearDown(): void {
		remove_filter( 'rest_dispatch_request', array( $this->sut, 'enforce_capability' ), 5 );
		Context::set_test_override( null );
		wp_set_current_user( $this->original_user_id );
		wp_delete_user( $this->admin_id );
		parent::tearDown();
	}

	/**
	 * @testdox register() attaches the dispatch hook.
	 */
	public function test_register_attaches_filter(): void {
		$this->sut->register();

		$this->assertSame( 5, has_filter( 'rest_dispatch_request', array( $this->sut, 'enforce_capability' ) ) );
	}

	/**
	 * @testdox enforce_capability rejects a POS request from a user without the operator capability (403).
	 */
	public function test_rejects_pos_request_without_capability(): void {
		Context::set_test_override( true );
		$subscriber_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$result = $this->sut->enforce_capability( null );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_rest_forbidden', $result->get_error_code() );
		$this->assertSame( 403, $result->get_error_data()['status'] );

		wp_delete_user( $subscriber_id );
	}

	/**
	 * A guest forging the marker is not authenticated, so the gate answers 401
	 * (authenticate) rather than 403 (forbidden), per rest_authorization_required_code().
	 *
	 * @testdox enforce_capability rejects a POS request from an unauthenticated guest (401).
	 */
	public function test_rejects_pos_request_from_guest(): void {
		Context::set_test_override( true );
		wp_set_current_user( 0 );

		$result = $this->sut->enforce_capability( null );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 401, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox enforce_capability lets an authorised operator through unchanged.
	 */
	public function test_allows_authorised_operator(): void {
		Context::set_test_override( true );
		wp_set_current_user( $this->admin_id );

		$this->assertNull( $this->sut->enforce_capability( null ) );
	}

	/**
	 * @testdox enforce_capability leaves non-POS requests untouched even without the capability.
	 */
	public function test_ignores_non_pos_requests(): void {
		Context::set_test_override( false );
		wp_set_current_user( 0 );

		$sentinel = new WP_REST_Response( array( 'ok' => true ), 200 );

		$this->assertSame( $sentinel, $this->sut->enforce_capability( $sentinel ) );
	}

	/**
	 * If an earlier hook already produced an error, the gate must not replace it
	 * (and must not run its own logic on top of a halted dispatch).
	 *
	 * @testdox enforce_capability passes through an error from an earlier hook unchanged.
	 */
	public function test_passes_through_prior_error(): void {
		Context::set_test_override( true );
		$subscriber_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$prior = new WP_Error( 'something_else', 'Already rejected.', array( 'status' => 400 ) );

		$result = $this->sut->enforce_capability( $prior );

		$this->assertSame( $prior, $result );

		wp_delete_user( $subscriber_id );
	}
}
