<?php
/**
 * Webflow Platform Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow\WebflowFetcher;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow\WebflowMapper;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow\WebflowPlatform;

/**
 * Test cases for Webflow platform registration.
 */
class WebflowPlatformTest extends \WC_Unit_Test_Case {

	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		parent::setUp();
		remove_all_filters( 'woocommerce_migrator_platforms' );
		WebflowPlatform::init();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_migrator_platforms' );
		parent::tearDown();
	}

	/**
	 * Test that the Webflow platform is registered with the expected configuration.
	 */
	public function test_webflow_platform_is_registered() {
		$registry  = new PlatformRegistry();
		$platforms = $registry->get_platforms();

		$this->assertArrayHasKey( 'webflow', $platforms );
		$this->assertSame( 'Webflow', $platforms['webflow']['name'] );
		$this->assertSame( WebflowFetcher::class, $platforms['webflow']['fetcher'] );
		$this->assertSame( WebflowMapper::class, $platforms['webflow']['mapper'] );
	}

	/**
	 * Test that the credentials prompt set includes site_id and access_token.
	 */
	public function test_credentials_include_site_id_and_access_token() {
		$registry = new PlatformRegistry();
		$fields   = $registry->get_platform_credential_fields( 'webflow' );

		$this->assertArrayHasKey( 'site_id', $fields );
		$this->assertArrayHasKey( 'access_token', $fields );
	}

	/**
	 * Test that multiple init() calls don't cause duplicate registrations.
	 */
	public function test_multiple_init_calls_safe() {
		WebflowPlatform::init();
		WebflowPlatform::init();

		$registry  = new PlatformRegistry();
		$platforms = $registry->get_platforms();

		$this->assertCount( 1, $platforms );
		$this->assertArrayHasKey( 'webflow', $platforms );
	}
}
