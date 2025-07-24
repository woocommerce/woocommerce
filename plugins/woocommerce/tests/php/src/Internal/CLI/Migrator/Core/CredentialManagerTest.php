<?php
/**
 * Credential Manager Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Core
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Core;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;

/**
 * CredentialManagerTest class.
 */
class CredentialManagerTest extends \WC_Unit_Test_Case {

	/**
	 * The CredentialManager instance under test.
	 *
	 * @var CredentialManager
	 */
	private CredentialManager $credential_manager;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->credential_manager = new CredentialManager();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// Clean up any stored credentials.
		delete_option( 'wc_migrator_credentials_shopify' );
		delete_option( 'wc_migrator_credentials_test_platform' );
		parent::tearDown();
	}

	/**
	 * Test storing and retrieving credentials.
	 */
	public function test_store_and_get_credentials() {
		$platform    = 'shopify';
		$credentials = array(
			'shop_url'     => 'https://test-shop.myshopify.com',
			'access_token' => 'shpat_test123456789',
		);

		$this->credential_manager->save_credentials( $platform, $credentials );
		$retrieved = $this->credential_manager->get_credentials( $platform );

		$this->assertEquals( $credentials, $retrieved );
	}

	/**
	 * Test getting credentials for a platform with no stored credentials.
	 */
	public function test_get_credentials_for_platform_with_no_credentials() {
		$retrieved = $this->credential_manager->get_credentials( 'nonexistent_platform' );
		$this->assertNull( $retrieved );
	}

	/**
	 * Test checking if credentials exist.
	 */
	public function test_has_credentials() {
		$platform = 'test_platform';

		// Initially no credentials.
		$this->assertFalse( $this->credential_manager->has_credentials( $platform ) );

		// Store credentials.
		$credentials = array( 'api_key' => 'test_key' );
		$this->credential_manager->save_credentials( $platform, $credentials );

		// Now should have credentials.
		$this->assertTrue( $this->credential_manager->has_credentials( $platform ) );
	}

	/**
	 * Test clearing credentials for a platform.
	 */
	public function test_clear_credentials() {
		$platform    = 'test_platform';
		$credentials = array( 'api_key' => 'test_key' );

		// Store credentials.
		$this->credential_manager->save_credentials( $platform, $credentials );
		$this->assertTrue( $this->credential_manager->has_credentials( $platform ) );

		// Clear credentials.
		$this->credential_manager->delete_credentials( $platform );
		$this->assertFalse( $this->credential_manager->has_credentials( $platform ) );
		$this->assertNull( $this->credential_manager->get_credentials( $platform ) );
	}

	/**
	 * Test deleting credentials for a platform.
	 */
	public function test_delete_credentials() {
		$platform    = 'test_platform';
		$credentials = array( 'token' => 'test_token' );

		// Store credentials first.
		$this->credential_manager->save_credentials( $platform, $credentials );
		$this->assertTrue( $this->credential_manager->has_credentials( $platform ) );

		// Delete credentials.
		$this->credential_manager->delete_credentials( $platform );
		$this->assertFalse( $this->credential_manager->has_credentials( $platform ) );
		$this->assertNull( $this->credential_manager->get_credentials( $platform ) );
	}

	/**
	 * Test prompting for credentials (mock behavior).
	 */
	public function test_prompt_for_credentials() {
		$fields = array(
			'shop_url'     => 'Enter shop URL:',
			'access_token' => 'Enter access token:',
		);

		// Since prompt_for_credentials uses STDIN, we can't really test it in unit tests.
		// But we can verify the method exists and is callable.
		$this->assertTrue( method_exists( $this->credential_manager, 'prompt_for_credentials' ) );
		$this->assertTrue( is_callable( array( $this->credential_manager, 'prompt_for_credentials' ) ) );
	}

	/**
	 * Test that credentials are stored securely in WordPress options.
	 */
	public function test_credentials_storage_location() {
		$platform    = 'test_platform';
		$credentials = array( 'secret' => 'very_secret_value' );

		$this->credential_manager->save_credentials( $platform, $credentials );

		// Verify the credentials are stored in the expected option as JSON.
		$stored_option = get_option( 'wc_migrator_credentials_' . $platform );
		$this->assertEquals( wp_json_encode( $credentials ), $stored_option );
	}

	/**
	 * Test storing empty credentials clears the platform.
	 */
	public function test_storing_empty_credentials_clears_platform() {
		$platform = 'test_platform';

		// Store some credentials first.
		$this->credential_manager->save_credentials( $platform, array( 'token' => 'test' ) );
		$this->assertTrue( $this->credential_manager->has_credentials( $platform ) );

		// Store empty credentials.
		$this->credential_manager->save_credentials( $platform, array() );
		$this->assertFalse( $this->credential_manager->has_credentials( $platform ) );
	}

	/**
	 * Test the setup_credentials method exists and is callable.
	 */
	public function test_setup_credentials_method_exists() {
		$this->assertTrue( method_exists( $this->credential_manager, 'setup_credentials' ) );
		$this->assertTrue( is_callable( array( $this->credential_manager, 'setup_credentials' ) ) );
	}
}
