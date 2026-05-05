<?php
/**
 * MissingCreateServerMcpAdapterForTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\MCP\Fixtures;

/**
 * MCP adapter test fixture missing create_server().
 */
class MissingCreateServerMcpAdapterForTest {

	/**
	 * Get the adapter instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		return new self();
	}
}
