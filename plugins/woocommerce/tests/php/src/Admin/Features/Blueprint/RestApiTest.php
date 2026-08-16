<?php
/**
 * Unit tests for RestApi class.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin\Features\Blueprint;

use Automattic\WooCommerce\Admin\Features\Blueprint\RestApi;
use WP_REST_Request;
use WP_Test_REST_TestCase;

/**
 * Unit tests for RestApi class.
 */
class RestApiTest extends WP_Test_REST_TestCase {
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
}
