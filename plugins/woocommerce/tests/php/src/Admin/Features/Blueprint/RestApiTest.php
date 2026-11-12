<?php
/**
 * Unit tests for RestApi class.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin\Features\Blueprint;

use Automattic\WooCommerce\Admin\Features\Blueprint\RestApi;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Unit tests for RestApi class.
 */
class RestApiTest extends WC_Unit_Test_Case {
	/**
	 * @var RestApi
	 */
	private $rest_api;

	/**
	 * @var string
	 */
	private $temp_file;

	/**
	 * @var int User ID with administrator role.
	 */
	private $user;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->rest_api = new RestApi();
		$this->useAdmin();

		// Create a temporary test file with valid Blueprint schema.
		$this->temp_file   = wp_tempnam( 'blueprint_test_' );
		$blueprint_content = wp_json_encode(
			array(
				'steps' => array(
					array(
						'step'     => 'setWCSettings',
						'settings' => array(
							'woocommerce_store_address' => '123 Test St',
							'woocommerce_store_city'    => 'Test City',
						),
					),
				),
			)
		);
		global $wp_filesystem;
		WP_Filesystem();
		$wp_filesystem->put_contents( $this->temp_file, $blueprint_content );
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
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		$this->clear_rest_server();
		parent::tearDown();
		// Clean up global state.
		unset( $_FILES['file'] );

		// Clean up temporary file.
		if ( file_exists( $this->temp_file ) ) {
			wp_delete_file( $this->temp_file );
		}

		remove_all_filters( 'pre_option_woocommerce_coming_soon' );
	}

	/**
	 * @testdox Should report import eligibility only for users who may import.
	 * @testWith ["administrator", true]
	 *           ["shop_manager", false]
	 * @param string $role User role.
	 * @param bool   $expected Whether the user may import.
	 */
	public function test_import_allowed_respects_permissions( string $role, bool $expected ): void {
		$user_id = $this->factory->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );
		update_option( 'woocommerce_coming_soon', 'yes' );
		if ( is_multisite() && $expected ) {
			grant_super_admin( $user_id );
		}

		try {
			$server   = $this->create_rest_server_with_routes( array( array( $this->rest_api, 'register_routes' ) ) );
			$response = $server->dispatch( new WP_REST_Request( 'GET', '/wc-admin/blueprint/import-allowed' ) );

			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( $expected, $response->get_data()['import_allowed'] );
			update_option( 'woocommerce_coming_soon', 'no' );
			$this->assertFalse( $this->rest_api->get_import_allowed()->get_data()['import_allowed'] );
		} finally {
			if ( is_multisite() && $expected ) {
				revoke_super_admin( $user_id );
			}
		}
	}

	/**
	 * @testdox Should deny a multisite subsite administrator and allow a network super administrator.
	 * @group ms-required
	 */
	public function test_import_allowed_requires_multisite_super_admin(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}
		update_option( 'woocommerce_coming_soon', 'yes' );

		$this->assertFalse( is_super_admin( $this->user ) );
		$this->assertWPError( $this->rest_api->check_import_permission() );
		$this->assertFalse( $this->rest_api->get_import_allowed()->get_data()['import_allowed'] );

		grant_super_admin( $this->user );
		try {
			$this->assertTrue( $this->rest_api->check_import_permission() );
			$this->assertTrue( $this->rest_api->get_import_allowed()->get_data()['import_allowed'] );
		} finally {
			revoke_super_admin( $this->user );
		}
	}

	/**
	 * Test that blueprint imports are disabled in live mode.
	 */
	public function test_cannot_import_blueprint_in_live_mode() {
		add_filter(
			'pre_option_woocommerce_coming_soon',
			function () {
				return 'no';
			}
		);

		$request = new \WP_REST_Request( 'POST', '/wc-admin/blueprint/import-step' );
		$request->set_body(
			wp_json_encode(
				array(
					'step_definition' => array(
						'step'    => 'setSiteOptions',
						'options' => array(
							'woocommerce_store_address' => '123 Test St',
						),
					),
				)
			)
		);

		$response = $this->rest_api->import_step( $request );

		$this->assertFalse( $response['success'] );
		$this->assertCount( 1, $response['messages'] );
		$this->assertEquals( 'error', $response['messages'][0]['type'] );
		$this->assertStringContainsString( 'Blueprint imports are disabled', $response['messages'][0]['message'] );
	}

	/**
	 * Test file size validation in import_step endpoint.
	 */
	public function test_import_step_file_size_validation() {
		add_filter(
			'pre_option_woocommerce_coming_soon',
			function () {
				return 'yes';
			}
		);

		// Create a large request body.
		$large_value = str_repeat( 'X', RestApi::MAX_FILE_SIZE + 1024 ); // Slightly over limit.
		$request     = new \WP_REST_Request( 'POST', '/wc-admin/blueprint/import-step' );
		$request->set_body(
			wp_json_encode(
				array(
					'step_definition' => array(
						'step'    => 'setSiteOptions',
						'options' => array(
							'large_setting' => $large_value,
						),
					),
				)
			)
		);

		$response = $this->rest_api->import_step( $request );

		$this->assertFalse( $response['success'] );
		$this->assertCount( 1, $response['messages'] );
		$this->assertEquals( 'error', $response['messages'][0]['type'] );
		$this->assertStringContainsString( '50 MB', $response['messages'][0]['message'] );
	}

	/**
	 * Test that import blueprint endpoint is working.
	 */
	public function test_import_blueprint() {
		add_filter(
			'pre_option_woocommerce_coming_soon',
			function () {
				return 'yes';
			}
		);

		$request = new \WP_REST_Request( 'POST', '/wc-admin/blueprint/import-step' );
		$request->set_body(
			wp_json_encode(
				array(
					'step_definition' => array(
						'step'    => 'setSiteOptions',
						'options' => array(
							'woocommerce_store_address' => '123 Test St',
						),
					),
				)
			)
		);
		$request->set_header( 'Content-Type', 'application/json' );

		$response      = $this->rest_api->import_step( $request );
		$response_data = $response->get_data();
		$this->assertTrue( $response_data['success'], $response_data['messages'][0]['message'] );
		$this->assertCount( 1, $response_data['messages'] );
		$this->assertStringContainsString( 'woocommerce_store_address has been updated', $response_data['messages'][0]['message'] );
	}

	/**
	 * @testdox Should continue an import after its steps change site visibility and flush the cache.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $external_object_cache Whether transients are stored in the object cache.
	 */
	public function test_import_continues_after_site_visibility_changes( bool $external_object_cache ): void {
		$previous_external_object_cache = wp_using_ext_object_cache();
		wp_using_ext_object_cache( $external_object_cache );

		try {
			update_option( 'woocommerce_coming_soon', 'yes' );
			update_option( 'woocommerce_store_pages_only', 'no' );

			$response = $this->rest_api->import_step(
				$this->create_import_request( array( 'woocommerce_coming_soon' => 'no' ) )
			);
			$this->assertInstanceOf( \WP_REST_Response::class, $response );
			$this->assertTrue( $response->get_data()['success'], 'The step that makes the site live should succeed.' );
			$this->assertSame( 'no', get_option( 'woocommerce_coming_soon' ) );

			$session_token = $response->get_headers()['X-Blueprint-Import-Session'];
			$this->assertNotEmpty( $session_token, 'The first response should supply a session token.' );

			$response = $this->rest_api->import_step(
				$this->create_import_request( array( 'woocommerce_store_pages_only' => 'yes' ), $session_token )
			);
			$this->assertInstanceOf( \WP_REST_Response::class, $response, 'The existing session should continue on a live site.' );
			$this->assertTrue( $response->get_data()['success'] );

			$response = $this->rest_api->import_step(
				$this->create_import_request( array( 'woocommerce_store_address' => 'Continued import' ), $session_token )
			);
			$this->assertInstanceOf( \WP_REST_Response::class, $response, 'The session should survive a cache flush in a later step.' );
			$this->assertTrue( $response->get_data()['success'] );
			$this->assertSame( 'Continued import', get_option( 'woocommerce_store_address' ) );

			foreach ( array( '', wp_generate_uuid4() ) as $invalid_session_token ) {
				$response = $this->rest_api->import_step(
					$this->create_import_request( array( 'woocommerce_store_address' => 'Blocked import' ), $invalid_session_token )
				);
				$this->assertFalse( $response['success'], 'A missing or unknown session must not allow an import on a live site.' );
				$this->assertSame( 'Continued import', get_option( 'woocommerce_store_address' ) );
			}
		} finally {
			wp_using_ext_object_cache( $previous_external_object_cache );
		}
	}

	/**
	 * Create an import step request for site options.
	 *
	 * @param array  $options       Site options to import.
	 * @param string $session_token Import session token.
	 * @return WP_REST_Request
	 */
	private function create_import_request( array $options, string $session_token = '' ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/wc-admin/blueprint/import-step' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_header( 'X-Blueprint-Import-Session', $session_token );
		$request->set_body(
			wp_json_encode(
				array(
					'step_definition' => array(
						'step'    => 'setSiteOptions',
						'options' => $options,
					),
				)
			)
		);
		return $request;
	}
}
