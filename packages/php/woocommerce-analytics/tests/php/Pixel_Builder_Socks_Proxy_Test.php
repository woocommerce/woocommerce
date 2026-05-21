<?php
/**
 * Tests for Pixel_Builder SOCKS proxy detection.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use PHPUnit\Framework\Attributes\DataProvider;
use WorDBless\BaseTestCase;

/**
 * Tests for Pixel_Builder SOCKS proxy detection.
 */
class Pixel_Builder_Socks_Proxy_Test extends BaseTestCase {

	/**
	 * Test is_socks_proxy_host returns true for SOCKS proxy hosts.
	 *
	 * @dataProvider socks_proxy_hosts_provider
	 * @param string $host The proxy host value.
	 */
	#[DataProvider( 'socks_proxy_hosts_provider' )]
	public function test_is_socks_proxy_host_returns_true( string $host ): void {
		$this->assertTrue( Pixel_Builder::is_socks_proxy_host( $host ) );
	}

	/**
	 * Data provider for SOCKS proxy hosts.
	 *
	 * @return array
	 */
	public static function socks_proxy_hosts_provider(): array {
		return array(
			'socks5'           => array( 'socks5://127.0.0.1' ),
			'socks4'           => array( 'socks4://127.0.0.1' ),
			'socks generic'    => array( 'socks://127.0.0.1' ),
			'socks5 uppercase' => array( 'SOCKS5://127.0.0.1' ),
			'socks5 with port' => array( 'socks5://127.0.0.1:1080' ),
		);
	}

	/**
	 * Test is_socks_proxy_host returns false for non-SOCKS proxy hosts.
	 *
	 * @dataProvider non_socks_proxy_hosts_provider
	 * @param string $host The proxy host value.
	 */
	#[DataProvider( 'non_socks_proxy_hosts_provider' )]
	public function test_is_socks_proxy_host_returns_false( string $host ): void {
		$this->assertFalse( Pixel_Builder::is_socks_proxy_host( $host ) );
	}

	/**
	 * Data provider for non-SOCKS proxy hosts.
	 *
	 * @return array
	 */
	public static function non_socks_proxy_hosts_provider(): array {
		return array(
			'plain hostname' => array( 'proxy.example.com' ),
			'http proxy'     => array( 'http://proxy.example.com' ),
			'ip address'     => array( '192.168.1.1' ),
		);
	}

	/**
	 * Test is_socks_proxy_configured returns false when WP_PROXY_HOST is not defined.
	 */
	public function test_is_socks_proxy_configured_returns_false_when_not_defined(): void {
		if ( defined( 'WP_PROXY_HOST' ) ) {
			$this->markTestSkipped( 'WP_PROXY_HOST is already defined in the environment.' );
		}

		$reflection = new \ReflectionClass( Pixel_Builder::class );
		$method     = $reflection->getMethod( 'is_socks_proxy_configured' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		$this->assertFalse( $method->invoke( null ) );
	}
}
