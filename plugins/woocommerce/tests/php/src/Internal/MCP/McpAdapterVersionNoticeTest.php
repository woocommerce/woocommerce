<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MCP;

use Automattic\WooCommerce\Internal\MCP\McpAdapterVersionNotice;
use WC_Unit_Test_Case;

/**
 * Tests for the McpAdapterVersionNotice class.
 */
class McpAdapterVersionNoticeTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should treat only a loaded MCP Adapter older than the minimum version as outdated.
	 *
	 * @testWith [null, false]
	 *           ["0.7.1", true]
	 *           ["0.7.2", false]
	 *           ["0.8.0", false]
	 *
	 * @param string|null $version  The active MCP Adapter version.
	 * @param bool        $expected Whether the version is outdated.
	 */
	public function test_is_outdated( ?string $version, bool $expected ): void {
		$this->assertSame( $expected, McpAdapterVersionNotice::is_outdated( $version ) );
	}

	/**
	 * @testdox Should name only the plugin whose main file sits at the root of the plugin folder.
	 */
	public function test_get_root_plugin_name_skips_subfolder_plugins(): void {
		$plugins = array(
			'examples/example.php' => array( 'Name' => 'A Example' ),
			'main.php'             => array( 'Name' => 'Z Host' ),
		);

		$this->assertSame( 'Z Host', McpAdapterVersionNotice::get_root_plugin_name( $plugins ) );
	}

	/**
	 * @testdox Should return no plugin name when the plugin folder has no single root plugin file.
	 */
	public function test_get_root_plugin_name_returns_null_when_ambiguous(): void {
		$plugins = array(
			'one.php'              => array( 'Name' => 'One' ),
			'two.php'              => array( 'Name' => 'Two' ),
			'examples/example.php' => array( 'Name' => 'Example' ),
		);

		$this->assertNull( McpAdapterVersionNotice::get_root_plugin_name( $plugins ) );
		$this->assertNull( McpAdapterVersionNotice::get_root_plugin_name( array( 'examples/example.php' => array( 'Name' => 'Example' ) ) ) );
	}
}
