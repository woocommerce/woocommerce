<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi;

use Automattic\WooCommerce\StoreApi\SessionHandler;
use WC_Session;
use WC_Unit_Test_Case;

/**
 * Tests for the StoreApi SessionHandler class.
 */
class SessionHandlerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var SessionHandler
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$_SERVER['HTTP_CART_TOKEN'] = '';

		$this->sut = new SessionHandler();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		unset( $_SERVER['HTTP_CART_TOKEN'] );
		parent::tearDown();
	}

	/**
	 * @testdox SessionHandler extends WC_Session.
	 */
	public function test_extends_wc_session(): void {
		$this->assertInstanceOf( WC_Session::class, $this->sut, 'SessionHandler should extend WC_Session' );
	}

	/**
	 * @testdox has_session returns false when no customer ID is set.
	 */
	public function test_has_session_returns_false_without_customer_id(): void {
		$this->assertFalse( $this->sut->has_session(), 'Should return false when no customer ID is set' );
	}

	/**
	 * @testdox has_session returns true when a customer ID is set.
	 */
	public function test_has_session_returns_true_with_customer_id(): void {
		$reflection = new \ReflectionProperty( $this->sut, '_customer_id' );
		$reflection->setAccessible( true );
		$reflection->setValue( $this->sut, 'test_customer_123' );

		$this->assertTrue( $this->sut->has_session(), 'Should return true when customer ID is set' );
	}

	/**
	 * @testdox generate_customer_id returns a non-empty string.
	 */
	public function test_generate_customer_id_returns_string(): void {
		$result = $this->sut->generate_customer_id();
		$this->assertNotEmpty( $result, 'generate_customer_id should return a non-empty string' );
		$this->assertIsString( $result, 'generate_customer_id should return a string' );
	}

	/**
	 * @testdox get_customer_unique_id returns empty string when no session.
	 */
	public function test_get_customer_unique_id_returns_empty_without_session(): void {
		wp_set_current_user( 0 );
		$this->assertSame( '', $this->sut->get_customer_unique_id(), 'Should return empty string when no session and not logged in' );
	}

	/**
	 * @testdox forget_session clears data and customer ID.
	 */
	public function test_forget_session_clears_state(): void {
		$reflection = new \ReflectionProperty( $this->sut, '_customer_id' );
		$reflection->setAccessible( true );
		$reflection->setValue( $this->sut, 'test_customer_123' );

		$this->sut->set( 'test_key', 'test_value' );

		$this->sut->forget_session();

		$this->assertSame( '', $this->sut->get_customer_id(), 'Customer ID should be cleared after forget_session' );
		$this->assertNull( $this->sut->get( 'test_key' ), 'Session data should be cleared after forget_session' );
	}

	/**
	 * @testdox destroy_session clears data and session state.
	 */
	public function test_destroy_session_clears_state(): void {
		$reflection = new \ReflectionProperty( $this->sut, '_customer_id' );
		$reflection->setAccessible( true );
		$reflection->setValue( $this->sut, 'test_customer_123' );

		$this->sut->set( 'test_key', 'test_value' );

		$this->sut->destroy_session();

		$this->assertNull( $this->sut->get( 'test_key' ), 'Session data should be cleared after destroy_session' );
		$this->assertFalse( $this->sut->has_session(), 'has_session should return false after destroy_session' );
		$this->assertSame( '', $this->sut->get_customer_id(), 'Customer ID should be cleared after destroy_session' );
	}

	/**
	 * @testdox set and get work for session data.
	 */
	public function test_set_and_get_session_data(): void {
		$this->sut->set( 'test_key', 'test_value' );
		$this->assertSame( 'test_value', $this->sut->get( 'test_key' ), 'Should return the value that was set' );
	}
}
