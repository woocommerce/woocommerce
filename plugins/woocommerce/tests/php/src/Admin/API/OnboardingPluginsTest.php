<?php
/**
 * Test the API controller class that handles the onboarding plugins REST endpoints.
 *
 * @package WooCommerce\Admin\Tests\Admin\API
 */

namespace Automattic\WooCommerce\Tests\Admin\API;

use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * OnboardingPlugins API controller test.
 *
 * @class OnboardingPluginsTest.
 */
class OnboardingPluginsTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/onboarding/plugins';

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->useAdmin();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		unset( $GLOBALS['wp_rest_auth_cookie'] );

		parent::tearDown();
	}

	/**
	 * Use a user with administrator role.
	 *
	 * @return void
	 */
	public function useAdmin() {
		// Register an administrator user and log in.
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Use a user without any permissions.
	 *
	 * @return void
	 */
	public function useUserWithoutPluginsPermission() {
		$this->user = $this->factory->user->create();
		wp_set_current_user( $this->user );
	}

	/**
	 * Request to install-async endpoint.
	 *
	 * @param string $endpoint Request endpoint.
	 * @param string $body Request body.
	 *
	 * @return mixed
	 */
	private function request( $endpoint, $body ) {
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . $endpoint );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( $body );
		$response = $this->server->dispatch( $request );

		return $response;
	}

	/**
	 * Request to scheduled-installs endpoint.
	 *
	 * @param string $job_id job id.
	 *
	 * @return mixed
	 */
	private function get( $job_id ) {
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/scheduled-installs/' . $job_id );

		return $this->server->dispatch( $request )->get_data();
	}

	/**
	 * Test to confirm install-async response format.
	 *
	 * @return void
	 */
	public function test_response_format() {
		$data = $this->request(
			'/install-and-activate-async',
			wp_json_encode(
				array(
					'plugins' => array( 'test' ),
				)
			)
		)->get_data();
		$this->assertArrayHasKey( 'job_id', $data );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'plugins', $data );
		$this->assertTrue( isset( $data['plugins']['test'] ) );
	}

	/**
	 * Test to confirm it queues an action scheduler job.
	 *
	 * @return void
	 */
	public function test_it_queues_action() {
		$this->markTestSkipped( 'Skipping it for now until we find a better way of testing it.' );
		$data      = $this->request(
			'/install-async',
			wp_json_encode(
				array(
					'plugins' => array( 'test' ),
				)
			)
		)->get_data();
		$action_id = $data['job_id'];
		$data      = $this->get( $action_id );
		$this->assertIsArray( $data );
		$this->assertEquals( $action_id, $data['job_id'] );
	}

	/**
	 * Test it returns 404 when an unknown job id is given.
	 *
	 * @return void
	 */
	public function test_it_returns_404_with_unknown_job_id() {
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/scheduled-installs/646e6a35121601' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test permissions.
	 *
	 * @return void
	 */
	public function test_permissions() {
		$this->useUserWithoutPluginsPermission();
		foreach ( array( '/install-and-activate', '/install-and-activate-async' ) as $endpoint ) {
			$response = $this->request(
				$endpoint,
				wp_json_encode(
					array(
						'plugins' => array( 'test' ),
					)
				)
			);
			$this->assertEquals( 403, $response->get_status() );
		}
	}

	/**
	 * @testdox The Jetpack authorization URL endpoint rejects a request with no wp_rest nonce.
	 *
	 * @return void
	 */
	public function test_jetpack_authorization_url_rejects_request_without_nonce() {
		unset( $GLOBALS['wp_rest_auth_cookie'] );

		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/jetpack-authorization-url' );
		$request->set_param( 'redirect_url', 'https://example.com' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status(), 'A request without an interactive session nonce must be rejected.' );
		$this->assertSame( 'woocommerce_rest_missing_session', $response->get_data()['code'] );
	}

	/**
	 * @testdox The Jetpack authorization URL endpoint rejects a cookie session whose wp_rest nonce is missing or invalid.
	 *
	 * @testWith [null]
	 *           ["not-a-valid-nonce"]
	 *
	 * @param string|null $nonce Nonce header to send, or null to send none.
	 * @return void
	 */
	public function test_jetpack_authorization_url_rejects_cookie_with_bad_nonce( $nonce ) {
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/jetpack-authorization-url' );
		$request->set_param( 'redirect_url', 'https://example.com' );
		$this->add_rest_nonce( $request );
		if ( null === $nonce ) {
			$request->remove_header( 'X-WP-Nonce' );
		} else {
			$request->set_header( 'X-WP-Nonce', $nonce );
		}
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status(), 'A cookie session without a valid wp_rest nonce must be rejected.' );
		$this->assertSame( 'woocommerce_rest_missing_session', $response->get_data()['code'] );
	}

	/**
	 * @testdox The Jetpack authorization URL endpoint rejects a wp_rest nonce sent without a cookie session.
	 *
	 * @return void
	 */
	public function test_jetpack_authorization_url_rejects_nonce_without_cookie() {
		unset( $GLOBALS['wp_rest_auth_cookie'] );

		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/jetpack-authorization-url' );
		$request->set_param( 'redirect_url', 'https://example.com' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status(), 'A wp_rest nonce without a cookie session must be rejected.' );
		$this->assertSame( 'woocommerce_rest_missing_session', $response->get_data()['code'] );
	}

	/**
	 * @testdox The Jetpack authorization URL endpoint allows an admin with a valid wp_rest nonce.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $header_nonce Send the nonce as the X-WP-Nonce header, or as the _wpnonce parameter.
	 * @return void
	 */
	public function test_jetpack_authorization_url_allows_interactive_admin( $header_nonce ) {
		// The route callback registers an unconnected site with WordPress.com; keep that offline and fast.
		add_filter(
			'pre_http_request',
			function () {
				return new \WP_Error( 'http_request_blocked', 'Outbound HTTP is disabled in this test.' );
			}
		);
		// The registration step destructures this option with list(), which PHP 8.5 warns about when the option is unset.
		update_option( 'jetpack_activation_source', array( 'woocommerce', 'onboarding' ) );

		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/jetpack-authorization-url' );
		$request->set_param( 'redirect_url', 'https://example.com' );
		$this->add_rest_nonce( $request );
		if ( ! $header_nonce ) {
			$request->set_param( '_wpnonce', $request->get_header( 'X-WP-Nonce' ) );
			$request->remove_header( 'X-WP-Nonce' );
		}
		$response = $this->server->dispatch( $request );

		$this->assertNotSame( 403, $response->get_status(), 'An admin browser session must pass the interactive-session check.' );
		$this->assertArrayHasKey( 'url', $response->get_data() );
	}

	/**
	 * Authenticate the current user through a `logged_in` cookie, as core does
	 * for a browser request, and attach the matching `wp_rest` nonce header.
	 *
	 * @param WP_REST_Request $request Request to authenticate.
	 * @return void
	 */
	private function add_rest_nonce( WP_REST_Request $request ) {
		$user_id    = get_current_user_id();
		$expiration = time() + DAY_IN_SECONDS;
		$token      = \WP_Session_Tokens::get_instance( $user_id )->create( $expiration );

		// Validating the cookie fires auth_cookie_valid, which core records in $wp_rest_auth_cookie.
		wp_validate_auth_cookie( wp_generate_auth_cookie( $user_id, $expiration, 'logged_in', $token ), 'logged_in' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
	}
}
