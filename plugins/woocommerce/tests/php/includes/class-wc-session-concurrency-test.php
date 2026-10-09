<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\StoreApi\SessionHandler as StoreApiSessionHandler;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;

/**
 * Tests for overlapping requests saving the same session.
 *
 * Each test loads the session in two "requests" before either saves, then saves request A and then request B, as
 * happens when a slow request overlaps another one from the same shopper. Requests that change the same key
 * still end with the value from the last save, so those cases are not covered here.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/46483
 */
class WC_Session_Concurrency_Test extends WC_Unit_Test_Case {

	private const GUEST_ID = 't_0123456789abcdef0123456789abcd';

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		unset( $_SERVER['HTTP_CART_TOKEN'] );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Cases: initial session data, the keys each request sets (null unsets), and the expected end state.
	 *
	 * @return array<string, array{array<string, mixed>, array<string, mixed>, array<string, mixed>, array<string, mixed>}>
	 */
	public function provide_overlapping_requests(): array {
		$item = array(
			'product_id' => 10,
			'quantity'   => 1,
		);

		return array(
			'add to cart vs notices write'      => array(
				array(),
				array( 'cart' => array( 'x' => $item ) ),
				array( 'wc_notices' => array( 'notice' => array( 'Hello' ) ) ),
				array(
					'cart'       => array( 'x' => $item ),
					'wc_notices' => array( 'notice' => array( 'Hello' ) ),
				),
			),
			'value update vs unrelated write'   => array(
				array( 'ext_consent' => 'granted' ),
				array( 'ext_consent' => 'withdrawn' ),
				array( 'chosen_shipping_methods' => array( 'free_shipping:1' ) ),
				array(
					'ext_consent'             => 'withdrawn',
					'chosen_shipping_methods' => array( 'free_shipping:1' ),
				),
			),
			'coupon removal vs unrelated write' => array(
				array( 'applied_coupons' => array( 'dev10' ) ),
				array( 'applied_coupons' => null ),
				array( 'wc_notices' => array( 'notice' => array( 'Hello' ) ) ),
				array( 'wc_notices' => array( 'notice' => array( 'Hello' ) ) ),
			),
		);
	}

	/**
	 * @testdox WC_Session_Handler keeps both requests' changes when saves overlap: $_dataName.
	 * @dataProvider provide_overlapping_requests
	 *
	 * @param array $initial   Session data before either request starts.
	 * @param array $changes_a Keys set by request A, which saves first.
	 * @param array $changes_b Keys set by request B, which saves last from older data.
	 * @param array $expected  Expected stored session data.
	 */
	public function test_cookie_session_handler_overlapping_saves( array $initial, array $changes_a, array $changes_b, array $expected ): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'customer' ) ) );

		$this->run_overlapping_requests( WC_Session_Handler::class, $initial, $changes_a, $changes_b );

		$this->assertEquals( $expected, $this->get_stored_session( (string) get_current_user_id() ), 'Request B should not overwrite request A\'s changes' );
	}

	/**
	 * @testdox Store API SessionHandler keeps both requests' changes when saves overlap: $_dataName.
	 * @dataProvider provide_overlapping_requests
	 *
	 * @param array $initial   Session data before either request starts.
	 * @param array $changes_a Keys set by request A, which saves first.
	 * @param array $changes_b Keys set by request B, which saves last from older data.
	 * @param array $expected  Expected stored session data.
	 */
	public function test_store_api_session_handler_overlapping_saves( array $initial, array $changes_a, array $changes_b, array $expected ): void {
		$_SERVER['HTTP_CART_TOKEN'] = CartTokenUtils::get_cart_token( self::GUEST_ID );

		$this->run_overlapping_requests( StoreApiSessionHandler::class, $initial, $changes_a, $changes_b );

		$this->assertEquals( $expected, $this->get_stored_session( self::GUEST_ID ), 'Request B should not overwrite request A\'s changes' );
	}

	/**
	 * @testdox Keys removed by the woocommerce_restored_session_data filter are removed from storage on the next save.
	 */
	public function test_filtered_restored_data_is_saved(): void {
		$user_id = (string) $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( (int) $user_id );
		$this->apply_and_save( $this->start_request( WC_Session_Handler::class ), array( 'ext_tracking' => 'abc' ) );

		add_filter(
			'woocommerce_restored_session_data',
			function ( $data ) {
				unset( $data['ext_tracking'] );
				return $data;
			}
		);

		// The filter only runs when the session is restored from a cookie.
		$request = $this->getMockBuilder( WC_Session_Handler::class )->onlyMethods( array( 'get_session_cookie' ) )->getMock();
		$request->method( 'get_session_cookie' )->willReturn( array( $user_id, time() + DAY_IN_SECONDS, time() + HOUR_IN_SECONDS, 'hash' ) );
		$request->init();
		$this->apply_and_save( $request, array( 'wc_notices' => array( 'notice' => array( 'Hello' ) ) ) );

		$this->assertEquals( array( 'wc_notices' => array( 'notice' => array( 'Hello' ) ) ), $this->get_stored_session( $user_id ) );
	}

	/**
	 * @testdox A request that saves twice merges each save, so its second save does not undo another request's changes.
	 */
	public function test_second_save_in_same_request_still_merges(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'customer' ) ) );

		$request_a = $this->start_request( WC_Session_Handler::class );
		$request_b = $this->start_request( WC_Session_Handler::class );

		$this->apply_and_save( $request_b, array( 'first' => 'b' ) );
		$this->apply_and_save( $request_a, array( 'other' => 'a' ) );
		$this->apply_and_save( $request_b, array( 'second' => 'b' ) );

		$this->assertEquals(
			array(
				'first'  => 'b',
				'other'  => 'a',
				'second' => 'b',
			),
			$this->get_stored_session( (string) get_current_user_id() )
		);
	}

	/**
	 * Seeds the session, then interleaves two requests: load A, load B, save A, save B.
	 *
	 * @param string $handler_class Session handler class.
	 * @param array  $initial       Session data before either request starts.
	 * @param array  $changes_a     Keys set by request A.
	 * @param array  $changes_b     Keys set by request B.
	 */
	private function run_overlapping_requests( string $handler_class, array $initial, array $changes_a, array $changes_b ): void {
		$this->apply_and_save( $this->start_request( $handler_class ), $initial );

		$request_a = $this->start_request( $handler_class );
		$request_b = $this->start_request( $handler_class );

		$this->apply_and_save( $request_a, $changes_a );
		$this->apply_and_save( $request_b, $changes_b );
	}

	/**
	 * Starts a request: a new session handler that has loaded the session.
	 *
	 * @param string $handler_class Session handler class.
	 * @return WC_Session
	 */
	private function start_request( string $handler_class ): WC_Session {
		$handler = new $handler_class();
		$handler->init();
		return $handler;
	}

	/**
	 * Sets session keys (null unsets) and saves.
	 *
	 * @param WC_Session $handler Session handler.
	 * @param array      $changes Keys to set.
	 */
	private function apply_and_save( WC_Session $handler, array $changes ): void {
		foreach ( $changes as $key => $value ) {
			$handler->set( $key, $value );
		}
		$handler->save_data();
	}

	/**
	 * Reads the stored session straight from the database, bypassing the object cache.
	 *
	 * @param string $customer_id Session key.
	 * @return array
	 */
	private function get_stored_session( string $customer_id ): array {
		global $wpdb;

		$value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", $customer_id ) );

		return array_map( 'maybe_unserialize', (array) maybe_unserialize( $value ) );
	}
}
