<?php
/**
 * Mock Platform Mapper class for testing.
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Mocks;

use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformMapperInterface;

/**
 * A mock mapper class for testing purposes.
 */
class MockPlatformMapper implements PlatformMapperInterface {

	/**
	 * {@inheritdoc}
	 *
	 * @param object $platform_data The platform data.
	 */
	public function map_product_data( object $platform_data ): array {
		return array(
			'name'        => $platform_data->name ?? 'Default Product Name',
			'description' => 'Mapped product description',
			'price'       => '10.00',
			'sku'         => 'mapped-sku-' . ( $platform_data->id ?? '1' ),
		);
	}
}
