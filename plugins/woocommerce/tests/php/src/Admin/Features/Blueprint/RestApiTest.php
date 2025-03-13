<?php
/**
 * Unit tests for RestApi class.
 */

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
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->rest_api = new RestApi();

		// Create a temporary test file with valid Blueprint schema
		$this->temp_file = wp_tempnam('blueprint_test_');
		$blueprint_content = json_encode([
			'steps' => [
				[
					'step' => 'setWCSettings',
					'settings' => [
						'woocommerce_store_address' => '123 Test St',
						'woocommerce_store_city' => 'Test City'
					]
				]
			]
		]);
		file_put_contents($this->temp_file, $blueprint_content);
	}

	/**
	 * Test file size validation in import_step endpoint.
	 */
	public function test_import_step_file_size_validation() {
		// Create a large request body
		$large_value = str_repeat('X', RestApi::MAX_FILE_SIZE + 1024); // Slightly over limit
		$request = new \WP_REST_Request('POST', '/wc-admin/blueprint/import-step');
		$request->set_body(json_encode(array(
			'step_definition' => array(
				'step' => 'setWCSettings',
				'settings' => array(
					'large_setting' => $large_value
				)
			)
		)));

		$response = $this->rest_api->import_step($request);

		$this->assertFalse($response['success']);
		$this->assertCount(1, $response['messages']);
		$this->assertEquals('error', $response['messages'][0]['type']);
		$this->assertStringContainsString('50 MB', $response['messages'][0]['message']);
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		// Clean up global state
		unset($_FILES['file']);

		// Clean up temporary file
		if (file_exists($this->temp_file)) {
			unlink($this->temp_file);
		}
	}
} 