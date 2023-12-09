<?php
/**
 * Unit tests for the GPT AI API Connection.
 *
 * @package WooCommerce\AI\Tests
 */

namespace Automattic\WooCommerce\Blocks\Tests\AI;

use Automattic\WooCommerce\Blocks\AI\Connection;
use \WP_UnitTestCase;

/**
 * Class Connection_Test.
 */
class ConnectionTest extends WP_UnitTestCase {
	/**
	 * The Connection instance.
	 *
	 * @var Connection
	 */
	private $connection;

	/**
	 * Initialize the connection instance.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->connection = new Connection();
	}

	/**
	 * Test get_jwt_token returns an error when the request fails.
	 */
	public function test_get_jwt_token_with_wp_error_in_response() {
		add_filter( 'pre_http_request', array( $this, 'mock_error' ) );

		wp_set_current_user( 0 );

		$response = $this->connection->get_jwt_token( 1 );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'Failed to retrieve the JWT token: Try again later.', $response->get_error_message() );

		remove_filter( 'pre_http_request', array( $this, 'mock_error' ) );
	}

	/**
	 * Test fetch_ai_response returns an error when the request fails.
	 */
	public function test_post_request_with_wp_error_in_response() {
		add_filter( 'pre_http_request', array( $this, 'mock_error' ) );

		$response = $this->connection->fetch_ai_response( 'token_value', 'prompt_value' );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'Failed to connect with the AI endpoint: try again later.', $response->get_error_message() );

		remove_filter( 'pre_http_request', array( $this, 'mock_error' ) );
	}

	/**
	 * Test get_site_id returns an error when the request fails.
	 */
	public function test_get_site_id_with_wp_error_in_response() {
		add_filter( 'pre_http_request', array( $this, 'mock_error' ) );

		wp_set_current_user( 0 );

		$response = $this->connection->get_site_id();

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'Failed to fetch the site ID: The site is not registered.', $response->get_error_message() );

		remove_filter( 'pre_http_request', array( $this, 'mock_error' ) );
	}

	/**
	 * Test get_site_id returns an error when the request fails.
	 */
	public function mock_error() {
		return new \WP_Error( 'failed_http_request', 'Error.' );
	}
}
