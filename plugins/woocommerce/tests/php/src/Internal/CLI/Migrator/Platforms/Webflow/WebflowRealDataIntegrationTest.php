<?php
/**
 * Webflow End-to-End Pipeline Test
 *
 * Drives WebflowFetcher and WebflowMapper together against a Webflow-shaped
 * HTTP response, with the network layer stubbed via `pre_http_request`. This
 * is the closest thing to a `wp wc migrate products --dry-run` we can run
 * without booting wp-env, and it's the test that guards the contract
 * between the two classes — specifically that the fetcher resolves the
 * categories collection once and decorates each item with the resolved
 * `_resolved_categories` that the mapper then surfaces as `categories[]`.
 *
 * Individual mapper edge cases (null main-image, compare-at-price, infinite
 * inventory, etc.) are covered by WebflowMapperTest. This test focuses on
 * what only an end-to-end run exercises.
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow;

use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow\WebflowFetcher;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow\WebflowMapper;
use WC_Unit_Test_Case;

/**
 * End-to-end fetcher → mapper test against a Webflow-shaped response.
 */
class WebflowRealDataIntegrationTest extends WC_Unit_Test_Case {

	private const SITE_ID = 'site-test';

	/**
	 * Set up the HTTP stub for each test.
	 */
	public function setUp(): void {
		parent::setUp();
		add_filter( 'pre_http_request', array( $this, 'handle_request' ), 10, 3 );
	}

	/**
	 * Tear down the HTTP stub.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'handle_request' ), 10 );
		parent::tearDown();
	}

	/**
	 * Route Webflow API requests to canned responses. The fetcher hits three endpoints:
	 *   - /sites/{id}/products            → product list
	 *   - /sites/{id}/collections         → discovers the Categories collection
	 *   - /collections/{id}/items         → loads category items for resolution
	 *
	 * @param mixed  $preempt Always ignored (we always preempt).
	 * @param array  $args    Request args.
	 * @param string $url     Request URL.
	 * @return array
	 */
	public function handle_request( $preempt, $args, $url ): array {
		unset( $preempt, $args );

		if ( false !== strpos( $url, '/sites/' . self::SITE_ID . '/products' ) ) {
			return $this->ok( $this->products_response() );
		}
		if ( false !== strpos( $url, '/sites/' . self::SITE_ID . '/collections' ) ) {
			return $this->ok( $this->collections_response() );
		}
		if ( false !== strpos( $url, '/collections/coll-cats/items' ) ) {
			return $this->ok( $this->categories_response() );
		}

		return array(
			'response' => array( 'code' => 404 ),
			'body'     => wp_json_encode( array( 'message' => 'Not stubbed: ' . $url ) ),
		);
	}

	/**
	 * 200 OK with body helper.
	 *
	 * @param string $body Response body.
	 * @return array
	 */
	private function ok( string $body ): array {
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => $body,
		);
	}

	/**
	 * A two-product list: one simple, one variable. Mirrors Webflow v2 shape — products
	 * wrap a `product` object and a `skus` array, category is an array of CMS item IDs,
	 * prices use minor units, sku-properties is null on simple products.
	 *
	 * @return string
	 */
	private function products_response(): string {
		return wp_json_encode(
			array(
				'items'      => array(
					array(
						'product' => array(
							'id'         => 'prod-simple',
							'isArchived' => false,
							'isDraft'    => false,
							'fieldData'  => array(
								'name'           => 'Tee',
								'slug'           => 'tee',
								'description'    => 'A tee.',
								'sku-properties' => null,
								'category'       => array( 'cat-shirts' ),
							),
						),
						'skus'    => array(
							array(
								'id'        => 'sku-tee',
								'fieldData' => array(
									'sku'        => 'TEE-001',
									'price'      => array(
										'unit'  => 'USD',
										'value' => 2000,
									),
									'main-image' => null,
									'sku-values' => new \stdClass(),
								),
							),
						),
					),
					array(
						'product' => array(
							'id'         => 'prod-variable',
							'isArchived' => false,
							'isDraft'    => false,
							'fieldData'  => array(
								'name'           => 'Hoodie',
								'slug'           => 'hoodie',
								'description'    => 'A hoodie.',
								'sku-properties' => array(
									array(
										'id'   => 'prop-color',
										'name' => 'Color',
										'enum' => array(
											array(
												'id'   => 'enum-red',
												'name' => 'Red',
												'slug' => 'red',
											),
											array(
												'id'   => 'enum-blue',
												'name' => 'Blue',
												'slug' => 'blue',
											),
										),
									),
								),
								'category'       => array( 'cat-outerwear', 'cat-missing' ),
							),
						),
						'skus'    => array(
							array(
								'id'        => 'sku-red',
								'fieldData' => array(
									'sku'        => 'HOOD-R',
									'price'      => array(
										'unit'  => 'USD',
										'value' => 5000,
									),
									'sku-values' => array( 'prop-color' => 'enum-red' ),
									'main-image' => array(
										'fileId' => 'img-shared',
										'url'    => 'https://cdn.example.test/hood.jpg',
										'alt'    => null,
									),
								),
							),
							array(
								'id'        => 'sku-blue',
								'fieldData' => array(
									'sku'        => 'HOOD-B',
									'price'      => array(
										'unit'  => 'USD',
										'value' => 5000,
									),
									'sku-values' => array( 'prop-color' => 'enum-blue' ),
									'main-image' => array(
										'fileId' => 'img-shared',
										'url'    => 'https://cdn.example.test/hood.jpg',
										'alt'    => null,
									),
								),
							),
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
	 * Collections response: the Categories collection plus an unrelated one, so the
	 * fetcher must discriminate by slug.
	 *
	 * @return string
	 */
	private function collections_response(): string {
		return wp_json_encode(
			array(
				'collections' => array(
					array(
						'id'          => 'coll-blog',
						'slug'        => 'post',
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
	 * Categories collection items. Note: `cat-missing` is intentionally absent here
	 * so we can assert the fetcher silently drops unresolved IDs (matches real
	 * archived-category behavior).
	 *
	 * @return string
	 */
	private function categories_response(): string {
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

	/**
	 * Drive the full pipeline and return mapped products keyed by name.
	 *
	 * @return array<string,array>
	 */
	private function run_pipeline(): array {
		$fetcher = new WebflowFetcher(
			array(
				'site_id'      => self::SITE_ID,
				'access_token' => 'fake-token',
			)
		);

		$batch  = $fetcher->fetch_batch( array( 'limit' => 100 ) );
		$mapper = new WebflowMapper();

		$by_name = array();
		foreach ( $batch['items'] as $item ) {
			$mapped                     = $mapper->map_product_data( $item );
			$by_name[ $mapped['name'] ] = $mapped;
		}
		return $by_name;
	}

	/**
	 * Test that the fetcher resolves the Categories collection and the mapper surfaces it.
	 *
	 * This is the load-bearing assertion: without the fetcher's `_resolved_categories`
	 * decoration, the mapper would have nothing to read and `categories[]` would be empty.
	 */
	public function test_categories_flow_from_fetcher_through_mapper(): void {
		$mapped = $this->run_pipeline();

		$this->assertSame(
			array( 'Shirts' ),
			array_column( $mapped['Tee']['categories'], 'name' )
		);

		// "Hoodie" references both an existing (cat-outerwear) and a missing (cat-missing)
		// category — only the resolved one should appear.
		$this->assertSame(
			array( 'Outerwear' ),
			array_column( $mapped['Hoodie']['categories'], 'name' )
		);
	}

	/**
	 * Test that variation attributes resolve through sku-properties end-to-end.
	 *
	 * Webflow's `sku-values` map property IDs → enum IDs; the mapper has to traverse the
	 * property catalog (which lives on `product.fieldData['sku-properties']`) to produce
	 * `attribute_name => option_name`. This wires together the fetcher payload shape and
	 * the mapper's resolution logic.
	 */
	public function test_variation_attributes_resolve_end_to_end(): void {
		$mapped = $this->run_pipeline();

		$this->assertTrue( $mapped['Hoodie']['is_variable'] );
		$this->assertCount( 2, $mapped['Hoodie']['variations'] );

		$colours = array();
		foreach ( $mapped['Hoodie']['variations'] as $variation ) {
			$colours[] = $variation['attributes']['Color'] ?? null;
		}
		$this->assertEqualsCanonicalizing( array( 'Red', 'Blue' ), $colours );
	}

	/**
	 * Test that variations sharing one image URL produce one images[] entry that every
	 * variation keys into via image_original_id.
	 *
	 * This is the importer contract the assessment doc calls out: WooCommerceProductImporter
	 * builds an `original_id => attachment_id` map from `images[]`, and variations resolve
	 * their image via that map. If a variation references an `image_original_id` not in
	 * `images[]`, the importer can't set the variation's image.
	 */
	public function test_shared_variant_image_is_deduped_and_referenced(): void {
		$mapped = $this->run_pipeline();

		$this->assertCount( 1, $mapped['Hoodie']['images'] );
		$image_id = $mapped['Hoodie']['images'][0]['original_id'];

		foreach ( $mapped['Hoodie']['variations'] as $variation ) {
			$this->assertSame( $image_id, $variation['image_original_id'] );
		}
	}
}
