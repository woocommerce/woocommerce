<?php
/**
 * Mock Shopify Data for Testing
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Fixtures
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Fixtures;

/**
 * MockShopifyData class.
 *
 * Provides consistent mock data for Shopify migration tests.
 */
class MockShopifyData {

	/**
	 * Get mock Shopify product data.
	 *
	 * @param int $count Number of products to generate.
	 * @return array Array of mock product objects.
	 */
	public static function get_mock_products( int $count = 3 ): array {
		$products = array();

		for ( $i = 1; $i <= $count; $i++ ) {
			$products[] = (object) array(
				'node' => (object) array(
					'id'              => "gid://shopify/Product/{$i}",
					'title'           => "Test Product {$i}",
					'handle'          => "test-product-{$i}",
					'descriptionHtml' => "<p>This is test product {$i} description</p>",
					'status'          => 0 === $i % 2 ? 'DRAFT' : 'ACTIVE',
					'createdAt'       => '2024-01-01T10:00:00Z',
					'vendor'          => 'Test Vendor',
					'tags'            => array( 'test', 'sample', "tag{$i}" ),
					'onlineStoreUrl'  => "https://test-shop.myshopify.com/products/test-product-{$i}",
					'options'         => array(
						(object) array(
							'id'       => "gid://shopify/ProductOption/{$i}1",
							'name'     => 'Size',
							'position' => 1,
							'values'   => array( 'Small', 'Medium', 'Large' ),
						),
						(object) array(
							'id'       => "gid://shopify/ProductOption/{$i}2",
							'name'     => 'Color',
							'position' => 2,
							'values'   => array( 'Red', 'Blue', 'Green' ),
						),
					),
					'featuredMedia'   => (object) array(
						'id'    => "gid://shopify/MediaImage/{$i}1",
						'image' => (object) array(
							'url'     => 'https://placehold.co/800x600.jpg',
							'altText' => "Test Product {$i} Featured Image",
						),
					),
					'media'           => (object) array(
						'edges' => array(
							(object) array(
								'node' => (object) array(
									'id'    => "gid://shopify/MediaImage/{$i}1",
									'image' => (object) array(
										'url'     => 'https://placehold.co/800x600.jpg',
										'altText' => "Test Product {$i} Image 1",
									),
								),
							),
							(object) array(
								'node' => (object) array(
									'id'    => "gid://shopify/MediaImage/{$i}2",
									'image' => (object) array(
										'url'     => 'https://placehold.co/600x600.jpg',
										'altText' => "Test Product {$i} Image 2",
									),
								),
							),
						),
					),
					'variants'        => (object) array(
						'edges' => self::get_mock_variants_for_product( $i ),
					),
					'collections'     => (object) array(
						'edges' => array(
							(object) array(
								'node' => (object) array(
									'id'     => 'gid://shopify/Collection/1',
									'handle' => 'test-collection',
									'title'  => 'Test Collection',
								),
							),
						),
					),
					'metafields'      => (object) array(
						'edges' => array(
							(object) array(
								'node' => (object) array(
									'namespace' => 'global',
									'key'       => 'custom_field_1',
									'value'     => "Custom value for product {$i}",
								),
							),
							(object) array(
								'node' => (object) array(
									'namespace' => 'global',
									'key'       => 'seo_title',
									'value'     => "SEO Title for Product {$i}",
								),
							),
						),
					),
				),
			);
		}

		return $products;
	}

	/**
	 * Get mock variants for a product.
	 *
	 * @param int $product_id The product ID.
	 * @param int $variant_count Number of variants to generate.
	 * @return array Array of variant edges.
	 */
	private static function get_mock_variants_for_product( int $product_id, int $variant_count = 2 ): array {
		$variants = array();

		for ( $v = 1; $v <= $variant_count; $v++ ) {
			$variants[] = (object) array(
				'node' => (object) array(
					'id'                => "gid://shopify/ProductVariant/{$product_id}{$v}",
					'product'           => (object) array(
						'id' => "gid://shopify/Product/{$product_id}",
					),
					'price'             => (string) ( 10.00 + $v * 5.00 ), // 15.00, 20.00, etc.
					'compareAtPrice'    => (string) ( 15.00 + $v * 5.00 ), // 20.00, 25.00, etc.
					'sku'               => "TEST-SKU-{$product_id}-{$v}",
					'inventoryPolicy'   => 'DENY',
					'inventoryQuantity' => 50 + $v * 10,
					'position'          => $v,
					'inventoryItem'     => (object) array(
						'tracked'     => true,
						'measurement' => (object) array(
							'weight' => (object) array(
								'value' => (float) ( 0.5 + $v * 0.1 ), // 0.6, 0.7, etc.
								'unit'  => 'KILOGRAMS',
							),
						),
					),
					'media'             => (object) array(
						'edges' => array(
							(object) array(
								'node' => (object) array(
									'id'    => "gid://shopify/MediaImage/{$product_id}{$v}1",
									'image' => (object) array(
										'url'     => 'https://placehold.co/400x400.jpg',
										'altText' => "Test Product {$product_id} Variant {$v} Image",
									),
								),
							),
						),
					),
					'selectedOptions'   => array(
						(object) array(
							'name'  => 'Size',
							'value' => 1 === $v ? 'Small' : 'Large',
						),
						(object) array(
							'name'  => 'Color',
							'value' => 1 === $v ? 'Red' : 'Blue',
						),
					),
				),
			);
		}

		return $variants;
	}

	/**
	 * Get mock fetch batch response.
	 *
	 * @param array       $items Array of product items.
	 * @param string|null $cursor The pagination cursor.
	 * @param bool        $has_next_page Whether there are more pages.
	 * @return array Mock batch response.
	 */
	public static function get_mock_batch_response( array $items, ?string $cursor = null, bool $has_next_page = false ): array {
		$last_cursor = null;
		if ( ! empty( $items ) ) {
			$last_item   = end( $items );
			$last_cursor = $cursor ?? 'cursor_' . ( $last_item->node->id ?? 'unknown' );
		}

		return array(
			'items'         => $items,
			'cursor'        => $last_cursor,
			'has_next_page' => $has_next_page,
		);
	}

	/**
	 * Get mock import session data.
	 *
	 * @param string $platform The platform name.
	 * @param array  $additional_data Additional session data.
	 * @return array Session creation data.
	 */
	public static function get_mock_session_data( string $platform = 'shopify', array $additional_data = array() ): array {
		$default_data = array(
			'data_source' => $platform,
			'file_name'   => ucfirst( $platform ) . ' Migration - Test Session',
		);

		return array_merge( $default_data, $additional_data );
	}

	/**
	 * Get mock credentials data.
	 *
	 * @param string $platform The platform name.
	 * @return array Credentials data.
	 */
	public static function get_mock_credentials( string $platform = 'shopify' ): array {
		switch ( $platform ) {
			case 'shopify':
				return array(
					'shop_url'     => 'https://test-shop.myshopify.com',
					'access_token' => 'shpat_test_access_token_12345',
				);

			default:
				return array(
					'api_key'    => 'test_api_key_12345',
					'api_secret' => 'test_api_secret_67890',
				);
		}
	}

	/**
	 * Get mock WooCommerce product data (mapped from Shopify).
	 *
	 * @param int $product_id The source product ID.
	 * @return array Mapped WooCommerce product data.
	 */
	public static function get_mock_wc_product_data( int $product_id = 1 ): array {
		return array(
			'original_product_id' => (string) $product_id,
			'name'                => "Test Product {$product_id}",
			'slug'                => "test-product-{$product_id}",
			'description'         => "<p>This is test product {$product_id} description</p>",
			'status'              => 0 === $product_id % 2 ? 'draft' : 'publish',
			'date_created_gmt'    => '2024-01-01T10:00:00Z',
			'catalog_visibility'  => 'visible',
			'regular_price'       => '15.00',
			'sku'                 => "TEST-SKU-{$product_id}-1",
			'stock_quantity'      => 60,
			'manage_stock'        => true,
			'stock_status'        => 'instock',
			'weight'              => '0.6',
			'categories'          => array(
				array(
					'name' => 'Test Collection',
					'slug' => 'test-collection',
				),
			),
			'tags'                => array(
				array( 'name' => 'test' ),
				array( 'name' => 'sample' ),
				array( 'name' => "tag{$product_id}" ),
			),
			'images'              => array(
				array(
					'src' => 'https://placehold.co/800x600.jpg',
					'alt' => "Test Product {$product_id} Featured Image",
				),
				array(
					'src' => 'https://placehold.co/600x600.jpg',
					'alt' => "Test Product {$product_id} Image 1",
				),
			),
			'attributes'          => array(
				array(
					'name'      => 'Size',
					'options'   => array( 'Small', 'Medium', 'Large' ),
					'variation' => false,
					'visible'   => true,
				),
				array(
					'name'      => 'Color',
					'options'   => array( 'Red', 'Blue', 'Green' ),
					'variation' => false,
					'visible'   => true,
				),
			),
			'meta_data'           => array(
				array(
					'key'   => '_shopify_product_id',
					'value' => (string) $product_id,
				),
				array(
					'key'   => 'custom_field_1',
					'value' => "Custom value for product {$product_id}",
				),
				array(
					'key'   => '_yoast_wpseo_title',
					'value' => "SEO Title for Product {$product_id}",
				),
			),
		);
	}

	/**
	 * Get mock command arguments for testing.
	 *
	 * @param array $overrides Arguments to override defaults.
	 * @return array Command arguments.
	 */
	public static function get_mock_command_args( array $overrides = array() ): array {
		$default_args = array(
			'platform'   => 'shopify',
			'limit'      => '100',
			'batch-size' => '20',
			'fields'     => 'name,price,sku,description',
			'status'     => 'active',
		);

		return array_merge( $default_args, $overrides );
	}

	/**
	 * Get mock error responses for testing error scenarios.
	 *
	 * @param string $error_type The type of error to simulate.
	 * @return array Error response data.
	 */
	public static function get_mock_error_response( string $error_type = 'network' ): array {
		switch ( $error_type ) {
			case 'network':
				return array(
					'items'         => array(),
					'cursor'        => null,
					'has_next_page' => false,
					'error'         => 'Network connection failed',
				);

			case 'auth':
				return array(
					'items'         => array(),
					'cursor'        => null,
					'has_next_page' => false,
					'error'         => 'Authentication failed',
				);

			case 'rate_limit':
				return array(
					'items'         => array(),
					'cursor'        => null,
					'has_next_page' => false,
					'error'         => 'Rate limit exceeded',
				);

			default:
				return array(
					'items'         => array(),
					'cursor'        => null,
					'has_next_page' => false,
					'error'         => 'Unknown error occurred',
				);
		}
	}
}
