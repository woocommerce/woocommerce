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
}
