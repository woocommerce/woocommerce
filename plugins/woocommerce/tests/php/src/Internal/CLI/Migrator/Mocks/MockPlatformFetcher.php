<?php
/**
 * Mock Platform Fetcher class for testing.
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks;

use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformFetcherInterface;

/**
 * A mock fetcher class for testing purposes.
 */
class MockPlatformFetcher implements PlatformFetcherInterface {

	/**
	 * {@inheritdoc}
	 *
	 * @param array $args Arguments for fetching.
	 */
	public function fetch_batch( array $args ): array {
		return array(
			'items'         => array(
				(object) array(
					'id'   => 1,
					'name' => 'Test Product 1',
				),
				(object) array(
					'id'   => 2,
					'name' => 'Test Product 2',
				),
			),
			'cursor'        => 'next-cursor',
			'has_next_page' => false, // Set to false to avoid infinite loops in tests.
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array $args Arguments for fetching.
	 */
	public function fetch_total_count( array $args ): int {
		return 42;
	}
}
