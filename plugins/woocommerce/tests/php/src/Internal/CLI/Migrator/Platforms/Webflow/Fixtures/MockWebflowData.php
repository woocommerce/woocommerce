<?php
/**
 * Mock Webflow API response data for use in unit tests.
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow\Fixtures
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow\Fixtures;

/**
 * Canned Webflow API payloads used across mapper/fetcher tests.
 *
 * Each method returns a freshly-decoded object so tests can mutate without
 * affecting one another. Shapes mirror what `/v2/sites/{id}/products` returns.
 */
class MockWebflowData {

	/**
	 * Decode a JSON string to objects (mirrors what WebflowClient returns).
	 *
	 * @param string $json JSON string.
	 * @return object
	 */
	private static function decode( string $json ): object {
		return json_decode( $json );
	}

	/**
	 * Single non-variant product (one SKU, no sku-properties).
	 *
	 * @return object
	 */
	public static function simple_product_item(): object {
		return self::decode(
			<<<'JSON'
			{
				"product": {
					"id": "prod-simple-1",
					"isArchived": false,
					"isDraft": false,
					"createdOn": "2024-01-01T00:00:00Z",
					"lastUpdated": "2024-01-02T00:00:00Z",
					"fieldData": {
						"name": "Plain Tee",
						"slug": "plain-tee",
						"description": "<p>Just a plain tee.</p>",
						"short-description": "A tee.",
						"seo-title": "Plain Tee — Buy Now",
						"seo-description": "The plainest tee.",
						"category": ["cat-shirts"],
						"more-images": [
							{ "fileId": "img-prod-1", "url": "https://cdn.webflow.test/prod-1.jpg", "alt": "front" }
						]
					}
				},
				"skus": [
					{
						"id": "sku-simple-1",
						"fieldData": {
							"sku": "PLAIN-001",
							"price": { "value": 1999, "unit": "USD" },
							"compare-at-price": { "value": 2499, "unit": "USD" },
							"inventory": { "type": "finite", "quantity": 7 },
							"weight": 0.5,
							"weight-unit": "lb",
							"length": 10,
							"width": 5,
							"height": 2,
							"main-image": { "fileId": "img-prod-1", "url": "https://cdn.webflow.test/prod-1.jpg", "alt": "front" }
						}
					}
				],
				"_resolved_categories": [
					{ "name": "Shirts", "slug": "shirts" }
				]
			}
			JSON
		);
	}

	/**
	 * Variable product with two sku-properties (Color, Size) and four SKUs,
	 * each with its own main-image. Includes infinite inventory on one SKU.
	 *
	 * @return object
	 */
	public static function variable_product_item(): object {
		return self::decode(
			<<<'JSON'
			{
				"product": {
					"id": "prod-var-1",
					"isArchived": false,
					"isDraft": false,
					"createdOn": "2024-03-01T00:00:00Z",
					"fieldData": {
						"name": "Fancy Hoodie",
						"slug": "fancy-hoodie",
						"description": "<p>Hoodie.</p>",
						"category": ["cat-outerwear"],
						"sku-properties": [
							{
								"id": "prop-color",
								"name": "Color",
								"enum": [
									{ "id": "enum-red",  "name": "Red",  "slug": "red"  },
									{ "id": "enum-blue", "name": "Blue", "slug": "blue" }
								]
							},
							{
								"id": "prop-size",
								"name": "Size",
								"enum": [
									{ "id": "enum-s", "name": "S", "slug": "s" },
									{ "id": "enum-m", "name": "M", "slug": "m" }
								]
							}
						],
						"more-images": [
							{ "fileId": "img-gallery", "url": "https://cdn.webflow.test/gallery.jpg", "alt": "gallery" }
						]
					}
				},
				"skus": [
					{
						"id": "sku-red-s",
						"fieldData": {
							"sku": "HOOD-RED-S",
							"price": { "value": 4999, "unit": "USD" },
							"inventory": { "type": "finite", "quantity": 3 },
							"length": 20,
							"width": 15,
							"height": 8,
							"sku-values": { "prop-color": "enum-red", "prop-size": "enum-s" },
							"main-image": { "fileId": "img-red", "url": "https://cdn.webflow.test/red.jpg", "alt": "red" }
						}
					},
					{
						"id": "sku-red-m",
						"fieldData": {
							"sku": "HOOD-RED-M",
							"price": { "value": 4999, "unit": "USD" },
							"inventory": { "type": "infinite" },
							"sku-values": { "prop-color": "enum-red", "prop-size": "enum-m" },
							"main-image": { "fileId": "img-red", "url": "https://cdn.webflow.test/red.jpg", "alt": "red" }
						}
					},
					{
						"id": "sku-blue-s",
						"fieldData": {
							"sku": "HOOD-BLUE-S",
							"price": { "value": 5499, "unit": "USD" },
							"compare-at-price": { "value": 5999, "unit": "USD" },
							"inventory": { "type": "finite", "quantity": 0 },
							"sku-values": { "prop-color": "enum-blue", "prop-size": "enum-s" },
							"main-image": { "fileId": "img-blue", "url": "https://cdn.webflow.test/blue.jpg", "alt": "blue" }
						}
					},
					{
						"id": "sku-blue-m",
						"fieldData": {
							"sku": "HOOD-BLUE-M",
							"price": { "value": 5499, "unit": "USD" },
							"inventory": { "type": "finite", "quantity": 10 },
							"sku-values": { "prop-color": "enum-blue", "prop-size": "enum-m" },
							"main-image": { "fileId": "img-blue", "url": "https://cdn.webflow.test/blue.jpg", "alt": "blue" }
						}
					}
				],
				"_resolved_categories": [
					{ "name": "Outerwear", "slug": "outerwear" }
				]
			}
			JSON
		);
	}

	/**
	 * Archived product — should map to draft status.
	 *
	 * @return object
	 */
	public static function archived_product_item(): object {
		$item                      = self::simple_product_item();
		$item->product->isArchived = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
		$item->product->id         = 'prod-archived-1';
		return $item;
	}

	/**
	 * Raw products-list response body (two products, total 7).
	 *
	 * @return string
	 */
	public static function products_list_response_body(): string {
		return wp_json_encode(
			array(
				'items'      => array(
					json_decode( wp_json_encode( self::simple_product_item() ) ),
					json_decode( wp_json_encode( self::variable_product_item() ) ),
				),
				'pagination' => array(
					'limit'  => 2,
					'offset' => 0,
					'total'  => 7,
				),
			)
		);
	}

	/**
	 * Empty products-list response (total 0).
	 *
	 * @return string
	 */
	public static function empty_products_list_response_body(): string {
		return wp_json_encode(
			array(
				'items'      => array(),
				'pagination' => array(
					'limit'  => 1,
					'offset' => 0,
					'total'  => 0,
				),
			)
		);
	}

	/**
	 * First page of the categories collection (cat-outerwear), total spanning two pages.
	 *
	 * @return string
	 */
	public static function categories_collection_items_page_one_body(): string {
		return wp_json_encode(
			array(
				'items'      => array(
					array(
						'id'        => 'cat-outerwear',
						'fieldData' => array(
							'name' => 'Outerwear',
							'slug' => 'outerwear',
						),
					),
				),
				'pagination' => array(
					'limit'  => 100,
					'offset' => 0,
					'total'  => 2,
				),
			)
		);
	}

	/**
	 * Second page of the categories collection (cat-shirts), completing the two-page set.
	 *
	 * @return string
	 */
	public static function categories_collection_items_page_two_body(): string {
		return wp_json_encode(
			array(
				'items'      => array(
					array(
						'id'        => 'cat-shirts',
						'fieldData' => array(
							'name' => 'Shirts',
							'slug' => 'shirts',
						),
					),
				),
				'pagination' => array(
					'limit'  => 100,
					'offset' => 1,
					'total'  => 2,
				),
			)
		);
	}

	/**
	 * Collections list response (one categories collection, one unrelated).
	 *
	 * @return string
	 */
	public static function collections_list_response_body(): string {
		return wp_json_encode(
			array(
				'collections' => array(
					array(
						'id'          => 'coll-blog',
						'slug'        => 'posts',
						'displayName' => 'Blog Posts',
					),
					array(
						'id'          => 'coll-cats',
						'slug'        => 'category',
						'displayName' => 'Categories',
					),
				),
			)
		);
	}

	/**
	 * Collection items response for the categories collection.
	 *
	 * @return string
	 */
	public static function categories_collection_items_response_body(): string {
		return wp_json_encode(
			array(
				'items'      => array(
					array(
						'id'        => 'cat-shirts',
						'fieldData' => array(
							'name' => 'Shirts',
							'slug' => 'shirts',
						),
					),
					array(
						'id'        => 'cat-outerwear',
						'fieldData' => array(
							'name' => 'Outerwear',
							'slug' => 'outerwear',
						),
					),
				),
				'pagination' => array(
					'limit'  => 100,
					'offset' => 0,
					'total'  => 2,
				),
			)
		);
	}
}
