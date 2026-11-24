<?php
declare( strict_types = 1 );

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- legacy conventions.

/**
 * Tests for session metadata protection in the v2 and v3 customer endpoints.
 */
class WC_REST_Customers_Session_Meta_Test extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var WP_REST_Server
	 */
	private $sut;

	/**
	 * @var int Customer user ID.
	 */
	private $customer_id;

	/**
	 * Set up the REST server and authorized users.
	 */
	public function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_rest_server'] = null;
		$this->sut                 = rest_get_server();
		$this->customer_id         = $this->create_user_with_role( 'customer' );
		$admin_id                  = $this->create_user_with_role( 'administrator' );
		if ( is_multisite() ) {
			grant_super_admin( $admin_id );
		}
		wp_set_current_user( $admin_id );
	}

	/**
	 * @testdox Creating a customer ignores session metadata and saves unrelated metadata.
	 * @testWith ["v2"]
	 *           ["v3"]
	 *
	 * @param string $version REST API version.
	 */
	public function test_customer_create_ignores_session_meta( string $version ): void {
		wp_set_current_user( $this->create_user_with_role( 'shop_manager' ) );
		$request = new WP_REST_Request( 'POST', "/wc/$version/customers" );
		$request->set_body_params(
			array(
				'email'     => 'session_meta@example.com',
				'meta_data' => $this->get_session_meta_data(),
			)
		);

		$response = $this->sut->dispatch( $request );

		$this->assertSame( 201, $response->get_status() );
		$this->assert_session_meta_unchanged( $response->get_data()['id'] );
	}

	/**
	 * @testdox Updating a customer cannot insert session metadata or change existing sessions.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $existing_sessions Whether the customer already has a session row.
	 */
	public function test_customer_update_ignores_session_meta( bool $existing_sessions ): void {
		if ( $existing_sessions ) {
			WP_Session_Tokens::get_instance( $this->customer_id )->create( time() + HOUR_IN_SECONDS );
		}
		$sessions = get_user_meta( $this->customer_id, 'session_tokens', false );
		$request  = new WP_REST_Request( 'PUT', "/wc/v2/customers/{$this->customer_id}" );
		$request->set_body_params( array( 'meta_data' => $this->get_session_meta_data() ) );

		$response = $this->sut->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assert_session_meta_unchanged( $this->customer_id, $sessions );
	}

	/**
	 * @testdox Updating a customer cannot rename an ordinary metadata row to session_tokens by ID.
	 */
	public function test_customer_update_cannot_rename_meta_to_session_tokens(): void {
		$meta_id = (int) add_user_meta( $this->customer_id, 'ordinary_meta', 'original' );
		$request = new WP_REST_Request( 'PUT', "/wc/v2/customers/{$this->customer_id}" );
		$request->set_body_params( array( 'meta_data' => $this->get_session_meta_data( $meta_id ) ) );

		$response = $this->sut->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$row = get_metadata_by_mid( 'user', $meta_id );
		$this->assertNotFalse( $row );
		$this->assertSame( 'ordinary_meta', $row->meta_key, 'The existing row must not be renamed.' );
		$this->assertSame( 'original', $row->meta_value, 'The existing row must not be overwritten.' );
		$this->assert_session_meta_unchanged( $this->customer_id );
	}

	/**
	 * Create a user and fail if the fixture cannot be saved.
	 *
	 * @param string $role User role.
	 * @return int
	 */
	private function create_user_with_role( string $role ): int {
		$user_id = self::factory()->user->create( array( 'role' => $role ) );
		$this->assertIsInt( $user_id );
		return $user_id;
	}

	/**
	 * Build session key variants and an unrelated extension metadata entry.
	 *
	 * @param int|null $meta_id Existing row ID for the exact session_tokens key.
	 * @return array[]
	 */
	private function get_session_meta_data( ?int $meta_id = null ): array {
		$meta_data = array_map(
			fn( $key ) => array(
				'key'   => $key,
				'value' => array( hash( 'sha256', 'injected_session_token' ) => array( 'expiration' => time() + HOUR_IN_SECONDS ) ),
			),
			array( 'session_tokens', 'SESSION_TOKENS', ' session_tokens ' )
		);
		if ( null !== $meta_id ) {
			$meta_data[0]['id'] = $meta_id;
		}
		$meta_data[] = array(
			'key'   => 'my_session_tokens',
			'value' => 'extension_value',
		);
		return $meta_data;
	}

	/**
	 * Assert that session rows are unchanged and unrelated metadata was saved.
	 *
	 * @param int   $customer_id Customer user ID.
	 * @param array $sessions    Original session rows, including any duplicates.
	 */
	private function assert_session_meta_unchanged( int $customer_id, array $sessions = array() ): void {
		$this->assertSame( $sessions, get_user_meta( $customer_id, 'session_tokens', false ), 'Session rows must not be added, replaced, or duplicated.' );
		$this->assertFalse( metadata_exists( 'user', $customer_id, 'SESSION_TOKENS' ) );
		$this->assertFalse( metadata_exists( 'user', $customer_id, ' session_tokens ' ) );
		$this->assertFalse( WP_Session_Tokens::get_instance( $customer_id )->verify( 'injected_session_token' ) );
		$this->assertSame( 'extension_value', get_user_meta( $customer_id, 'my_session_tokens', true ), 'Unrelated metadata must still be writable.' );
	}
}
