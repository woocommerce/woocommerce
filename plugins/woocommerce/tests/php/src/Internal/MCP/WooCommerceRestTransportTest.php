<?php
/**
 * WooCommerceRestTransportTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\MCP;

use Automattic\WooCommerce\Internal\MCP\Transport\WooCommerceRestTransport;

/**
 * Tests for the WooCommerceRestTransport class.
 */
class WooCommerceRestTransportTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var WooCommerceRestTransport
	 */
	private $sut;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Bootstrap the MCP Adapter for tests (required for transport context).
		if ( ! class_exists( 'WP\\MCP\\Transport\\Infrastructure\\McpTransportContext' ) ) {
			$mcp_bootstrap = WP_PLUGIN_DIR . '/woocommerce/vendor/wordpress/mcp-adapter/includes/Autoloader.php';
			if ( file_exists( $mcp_bootstrap ) ) {
				require_once $mcp_bootstrap;
				// Initialize the autoloader.
				if ( class_exists( 'WP\\MCP\\Autoloader' ) ) {
					\WP\MCP\Autoloader::autoload();
				}
			}
		}

		// Create a mock transport context since we're not testing the full transport integration.
		$mock_context = $this->createMock( 'WP\MCP\Transport\Infrastructure\McpTransportContext' );
		$this->sut    = new WooCommerceRestTransport( $mock_context );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// Clean up any global state changes.
		wp_set_current_user( 0 );

		// Remove any test filters.
		remove_all_filters( 'woocommerce_mcp_allow_insecure_transport' );

		parent::tearDown();
	}

	/**
	 * Test HTTPS requirement enforcement.
	 */
	public function test_validate_request_requires_https_by_default() {
		$request = new \WP_REST_Request();

		// Mock non-SSL environment.
		add_filter( 'is_ssl', '__return_false' );

		$result = $this->sut->validate_request( $request );

		$this->assertInstanceOf( \WP_Error::class, $result, 'Should return WP_Error for non-HTTPS requests' );
		$this->assertEquals( 'insecure_transport', $result->get_error_code(), 'Should have insecure transport error code' );

		remove_filter( 'is_ssl', '__return_false' );
	}

	/**
	 * Test HTTPS requirement can be bypassed with filter.
	 */
	public function test_validate_request_allows_insecure_with_filter() {
		$request = new \WP_REST_Request();
		$request->set_header( 'X-MCP-API-Key', 'valid_key:valid_secret' );

		// Mock non-SSL environment but allow insecure transport.
		add_filter( 'is_ssl', '__return_false' );
		add_filter( 'woocommerce_mcp_allow_insecure_transport', '__return_true' );

		// Mock valid API key authentication.
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => 1,
				'description'     => 'Test Key',
				'permissions'     => 'read_write',
				'consumer_key'    => wc_api_hash( 'valid_key' ),
				'consumer_secret' => 'valid_secret',
				'truncated_key'   => 'ck_test',
			)
		);

		$result = $this->sut->validate_request( $request );

		$this->assertTrue( $result, 'Should allow insecure transport when filter is enabled' );

		// Cleanup.
		$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'consumer_key' => wc_api_hash( 'valid_key' ) ) );
		remove_filter( 'is_ssl', '__return_false' );
	}

	/**
	 * Test missing API key header.
	 */
	public function test_validate_request_requires_api_key_header() {
		$request = new \WP_REST_Request();

		// Allow insecure transport for testing - we're testing API key validation logic,
		// not HTTPS enforcement. We trust WordPress's is_ssl() function works correctly.
		add_filter( 'woocommerce_mcp_allow_insecure_transport', '__return_true' );

		$result = $this->sut->validate_request( $request );

		$this->assertInstanceOf( \WP_Error::class, $result, 'Should return WP_Error for missing API key' );
		$this->assertEquals( 'missing_api_key', $result->get_error_code(), 'Should have missing API key error code' );
	}

	/**
	 * Test invalid API key format.
	 */
	public function test_validate_request_validates_api_key_format() {
		$request = new \WP_REST_Request();
		$request->set_header( 'X-MCP-API-Key', 'invalid_format_without_colon' );

		// Allow insecure transport for testing - we're testing API key format validation,
		// not HTTPS enforcement. We trust WordPress's is_ssl() function works correctly.
		add_filter( 'woocommerce_mcp_allow_insecure_transport', '__return_true' );

		$result = $this->sut->validate_request( $request );

		$this->assertInstanceOf( \WP_Error::class, $result, 'Should return WP_Error for invalid API key format' );
		$this->assertEquals( 'invalid_api_key', $result->get_error_code(), 'Should have invalid API key error code' );
	}

	/**
	 * Test invalid consumer key.
	 */
	public function test_validate_request_rejects_invalid_consumer_key() {
		$request = new \WP_REST_Request();
		$request->set_header( 'X-MCP-API-Key', 'invalid_key:some_secret' );

		// Allow insecure transport for testing - we're testing database authentication logic,
		// not HTTPS enforcement. We trust WordPress's is_ssl() function works correctly.
		add_filter( 'woocommerce_mcp_allow_insecure_transport', '__return_true' );

		$result = $this->sut->validate_request( $request );

		$this->assertInstanceOf( \WP_Error::class, $result, 'Should return WP_Error for invalid consumer key' );
		$this->assertEquals( 'authentication_failed', $result->get_error_code(), 'Should have authentication failed error code' );
	}

	/**
	 * Test invalid consumer secret.
	 */
	public function test_validate_request_rejects_invalid_consumer_secret() {
		$request = new \WP_REST_Request();
		$request->set_header( 'X-MCP-API-Key', 'valid_key:invalid_secret' );

		// Allow insecure transport for testing - we're testing hash_equals secret validation,
		// not HTTPS enforcement. We trust WordPress's is_ssl() function works correctly.
		add_filter( 'woocommerce_mcp_allow_insecure_transport', '__return_true' );

		// Insert valid API key with different secret.
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => 1,
				'description'     => 'Test Key',
				'permissions'     => 'read_write',
				'consumer_key'    => wc_api_hash( 'valid_key' ),
				'consumer_secret' => 'correct_secret',
				'truncated_key'   => 'ck_test',
			)
		);

		$result = $this->sut->validate_request( $request );

		$this->assertInstanceOf( \WP_Error::class, $result, 'Should return WP_Error for invalid consumer secret' );
		$this->assertEquals( 'authentication_failed', $result->get_error_code(), 'Should have authentication failed error code' );

		// Cleanup.
		$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'consumer_key' => wc_api_hash( 'valid_key' ) ) );
	}

	/**
	 * Test valid authentication.
	 */
	public function test_validate_request_accepts_valid_credentials() {
		$request = new \WP_REST_Request();
		$request->set_header( 'X-MCP-API-Key', 'valid_key:valid_secret' );

		// Allow insecure transport for testing - we're testing successful authentication flow,
		// not HTTPS enforcement. We trust WordPress's is_ssl() function works correctly.
		add_filter( 'woocommerce_mcp_allow_insecure_transport', '__return_true' );

		// Create a test user.
		$user_id = $this->factory->user->create();

		// Insert valid API key.
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => $user_id,
				'description'     => 'Test Key',
				'permissions'     => 'read_write',
				'consumer_key'    => wc_api_hash( 'valid_key' ),
				'consumer_secret' => 'valid_secret',
				'truncated_key'   => 'ck_test',
			)
		);

		$result = $this->sut->validate_request( $request );

		$this->assertTrue( $result, 'Should accept valid credentials' );

		// Verify user context was set.
		$this->assertEquals( $user_id, get_current_user_id(), 'Should set current user context' );

		// Cleanup.
		$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'consumer_key' => wc_api_hash( 'valid_key' ) ) );
	}

	/**
	 * Test user existence validation.
	 */
	public function test_validate_request_validates_user_exists() {
		$request = new \WP_REST_Request();
		$request->set_header( 'X-MCP-API-Key', 'valid_key:valid_secret' );

		// Allow insecure transport for testing - we're testing user existence validation,
		// not HTTPS enforcement. We trust WordPress's is_ssl() function works correctly.
		add_filter( 'woocommerce_mcp_allow_insecure_transport', '__return_true' );

		// Insert API key with non-existent user.
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => 99999, // Non-existent user ID.
				'description'     => 'Test Key',
				'permissions'     => 'read_write',
				'consumer_key'    => wc_api_hash( 'valid_key' ),
				'consumer_secret' => 'valid_secret',
				'truncated_key'   => 'ck_test',
			)
		);

		$result = $this->sut->validate_request( $request );

		$this->assertInstanceOf( \WP_Error::class, $result, 'Should return WP_Error for non-existent user' );
		$this->assertEquals( 'mcp_user_not_found', $result->get_error_code(), 'Should have user not found error code' );

		// Cleanup.
		$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'consumer_key' => wc_api_hash( 'valid_key' ) ) );
	}

	/**
	 * Test permission checking for GET requests.
	 */
	public function test_check_ability_permission_allows_get_with_read_permission() {
		// Set up MCP context with read permissions.
		$reflection = new \ReflectionClass( WooCommerceRestTransport::class );
		$property   = $reflection->getProperty( 'current_mcp_permissions' );
		$property->setAccessible( true );
		$property->setValue( 'read' );

		$result = $this->sut->check_ability_permission( false, 'GET', new \stdClass() );

		$this->assertTrue( $result, 'Should allow GET requests with read permissions' );

		// Cleanup.
		$property->setValue( null );
	}

	/**
	 * Test permission checking for POST requests.
	 */
	public function test_check_ability_permission_denies_post_with_read_permission() {
		// Set up MCP context with read permissions.
		$reflection = new \ReflectionClass( WooCommerceRestTransport::class );
		$property   = $reflection->getProperty( 'current_mcp_permissions' );
		$property->setAccessible( true );
		$property->setValue( 'read' );

		$result = $this->sut->check_ability_permission( false, 'POST', new \stdClass() );

		$this->assertFalse( $result, 'Should deny POST requests with read-only permissions' );

		// Cleanup.
		$property->setValue( null );
	}

	/**
	 * Test permission checking for POST requests with write permission.
	 */
	public function test_check_ability_permission_allows_post_with_write_permission() {
		// Set up MCP context with write permissions.
		$reflection = new \ReflectionClass( WooCommerceRestTransport::class );
		$property   = $reflection->getProperty( 'current_mcp_permissions' );
		$property->setAccessible( true );
		$property->setValue( 'write' );

		$result = $this->sut->check_ability_permission( false, 'POST', new \stdClass() );

		$this->assertTrue( $result, 'Should allow POST requests with write permissions' );

		// Cleanup.
		$property->setValue( null );
	}

	/**
	 * Test permission checking for read_write permission.
	 */
	public function test_check_ability_permission_allows_all_with_read_write_permission() {
		// Set up MCP context with read_write permissions.
		$reflection = new \ReflectionClass( WooCommerceRestTransport::class );
		$property   = $reflection->getProperty( 'current_mcp_permissions' );
		$property->setAccessible( true );
		$property->setValue( 'read_write' );

		$get_result    = $this->sut->check_ability_permission( false, 'GET', new \stdClass() );
		$post_result   = $this->sut->check_ability_permission( false, 'POST', new \stdClass() );
		$delete_result = $this->sut->check_ability_permission( false, 'DELETE', new \stdClass() );

		$this->assertTrue( $get_result, 'Should allow GET requests with read_write permissions' );
		$this->assertTrue( $post_result, 'Should allow POST requests with read_write permissions' );
		$this->assertTrue( $delete_result, 'Should allow DELETE requests with read_write permissions' );

		// Cleanup.
		$property->setValue( null );
	}

	/**
	 * Test permission checking without MCP context.
	 */
	public function test_check_ability_permission_preserves_original_without_mcp_context() {
		// No MCP context set (permissions = null).
		$result = $this->sut->check_ability_permission( true, 'GET', new \stdClass() );

		$this->assertTrue( $result, 'Should preserve original permission when no MCP context' );

		$result = $this->sut->check_ability_permission( false, 'GET', new \stdClass() );

		$this->assertFalse( $result, 'Should preserve original permission when no MCP context' );
	}

	/**
	 * Test get current user permissions.
	 */
	public function test_get_current_user_permissions_returns_null_without_context() {
		$permissions = WooCommerceRestTransport::get_current_user_permissions();

		$this->assertNull( $permissions, 'Should return null when no MCP context' );
	}

	/**
	 * Test get current user permissions returns set value.
	 */
	public function test_get_current_user_permissions_returns_set_value() {
		// Set permissions via reflection (simulating authentication).
		$reflection = new \ReflectionClass( WooCommerceRestTransport::class );
		$property   = $reflection->getProperty( 'current_mcp_permissions' );
		$property->setAccessible( true );
		$property->setValue( 'read_write' );

		$permissions = WooCommerceRestTransport::get_current_user_permissions();

		$this->assertEquals( 'read_write', $permissions, 'Should return set permissions' );

		// Cleanup.
		$property->setValue( null );
	}
}
