<?php
/**
 * Unit tests for WC_Woo_Update_Manager_Plugin class
 *
 * @package WooCommerce\Tests\Admin\Helper
 */

declare(strict_types=1);

/**
 * Class WC_Woo_Update_Manager_Plugin_Test
 */
class WC_Woo_Update_Manager_Plugin_Test extends WC_Unit_Test_Case {
	/**
	 * @testdox Hosts WooCommerce.com can't reach count as local, public ones don't.
	 *
	 * @dataProvider urls
	 *
	 * @param string $url      URL to check.
	 * @param bool   $is_local Expected result.
	 */
	public function test_is_local_url( string $url, bool $is_local ): void {
		$this->assertSame( $is_local, WC_Woo_Update_Manager_Plugin::is_local_url( $url ), $url );
	}

	/**
	 * URLs on both sides of the local-host check.
	 *
	 * @return array<string, array{string, bool}>
	 */
	public function urls(): array {
		return array(
			'localhost with port'      => array( 'http://localhost:8888', true ),
			'loopback IPv4'            => array( 'http://127.0.0.1/shop', true ),
			'private IPv4'             => array( 'https://192.168.1.20', true ),
			'loopback IPv6'            => array( 'http://[::1]:8080', true ),
			'unique local IPv6'        => array( 'http://[fd12:3456::1]', true ),
			'single-label host'        => array( 'http://mystore', true ),
			'.local, any case'         => array( 'https://Shop.LOCAL', true ),
			'.test'                    => array( 'https://store.test', true ),
			'DDEV subdomain'           => array( 'https://store.ddev.site', true ),
			'.example'                 => array( 'https://store.example', true ),
			'public domain'            => array( 'https://example.org', false ),
			'local in a public domain' => array( 'https://local.example.com', false ),
			'look-alike TLD'           => array( 'https://store.testing', false ),
			'public IPv4'              => array( 'http://8.8.8.8', false ),
			'no host'                  => array( '', false ),
		);
	}
}
