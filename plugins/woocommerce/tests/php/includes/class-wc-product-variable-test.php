<?php

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Tests for WC_Product_Variable.
 */
class WC_Product_Variable_Test extends \WC_Unit_Test_Case {
	/**
	 * @testdox 'get_available_variations' returns the variations as arrays if no parameters is passed.
	 */
	public function test_get_available_variations_returns_array_when_no_parameter_is_passed() {
		$product = WC_Helper_Product::create_variation_product();

		$variations = $product->get_available_variations();

		$this->assertTrue( is_array( $variations[0] ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]['sku'] );
	}

	/**
	 * @testdox 'get_available_variations' returns the variations as arrays if the parameter passed is 'array'.
	 */
	public function test_get_available_variations_returns_array_when_array_parameter_is_passed() {
		$product = WC_Helper_Product::create_variation_product();

		$variations = $product->get_available_variations( 'array' );

		$this->assertTrue( is_array( $variations[0] ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]['sku'] );
	}

	/**
	 * @testdox 'get_available_variations' returns the variations as objects if the parameter passed is 'objects'.
	 */
	public function test_get_available_variations_returns_object_when_objects_parameter_is_passed() {
		$product = WC_Helper_Product::create_variation_product();

		$variations = $product->get_available_variations( 'objects' );

		$this->assertInstanceOf( WC_Product_Variation::class, $variations[0] );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]->get_sku() );
	}

	/**
	 * @testdox 'has_purchasable_variations' should return true when all variations are purchasable.
	 */
	public function test_has_purchasable_variations_returns_true_when_all_variations_are_purchasable() {

		$product = WC_Helper_Product::create_variation_product();

		$variations = $product->get_available_variations( 'array' );
		$this->assertTrue( is_array( $variations[0] ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]['sku'] );

		$has_purchasable_variations = $product->has_purchasable_variations();
		$this->assertIsBool( $has_purchasable_variations );
		$this->assertTrue( $has_purchasable_variations );
	}

	/**
	 * @testdox 'has_purchasable_variations' returns true when some variations are purchasable.
	 */
	public function test_has_purchasable_variations_returns_true_when_some_variations_are_purchasable() {

		$product = new WC_Product_Variable();

		$product->set_props(
			array(
				'name' => 'Dummy Variable Product',
				'sku'  => 'DUMMY VARIABLE SKU' . microtime(),
			)
		);

		$attributes = array();

		$attributes[] = WC_Helper_Product::create_product_attribute_object( 'size', array( 'small', 'large', 'huge' ) );

		$product->set_attributes( $attributes );
		$product->save();

		$variations = array();

		$variations[] = WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE SMALL',
			10,
			array( 'pa_size' => 'small' )
		);

		$variations[] = WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE LARGE',
			'', // Variation is not available.
			array( 'pa_size' => 'large' )
		);

		$variation_ids = array_map(
			function ( $variation ) {
				return $variation->get_id();
			},
			$variations
		);
		$product->set_children( $variation_ids );

		$variations = $product->get_available_variations( 'array' );
		$this->assertTrue( is_array( $variations[0] ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]['sku'] );

		$has_purchasable_variations = $product->has_purchasable_variations();
		$this->assertIsBool( $has_purchasable_variations );
		$this->assertTrue( $has_purchasable_variations );
	}

	/**
	 * @testdox 'has_purchasable_variations' returns false when all variations are not purchasable.
	 */
	public function test_has_purchasable_variations_returns_false_when_all_variations_are_not_purchasable() {

		$product = new WC_Product_Variable();

		$product->set_props(
			array(
				'name' => 'Dummy Variable Product',
				'sku'  => 'DUMMY VARIABLE SKU' . microtime(),
			)
		);

		$attributes = array();

		$attributes[] = WC_Helper_Product::create_product_attribute_object( 'size', array( 'small', 'large', 'huge' ) );

		$product->set_attributes( $attributes );
		$product->save();

		$variations = array();

		$variations[] = WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE SMALL',
			'', // Variation is not available.
			array( 'pa_size' => 'small' )
		);

		$variations[] = WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE LARGE',
			'', // Variation is not available.
			array( 'pa_size' => 'large' )
		);

		$variation_ids = array_map(
			function ( $variation ) {
				return $variation->get_id();
			},
			$variations
		);
		$product->set_children( $variation_ids );

		$variations = $product->get_available_variations( 'array' );
		$this->assertTrue( empty( $variations ) );

		$has_purchasable_variations = $product->has_purchasable_variations();
		$this->assertIsBool( $has_purchasable_variations );
		$this->assertFalse( $has_purchasable_variations );
	}

	/**
	 * @testdox 'get_available_variations' with 'array' return includes image and gallery data for variations that have images set.
	 */
	public function test_get_available_variations_array_includes_image_data_when_variation_has_images(): void {
		$image_id   = $this->create_image_attachment( 'Variation Image', 'variation-image.jpg' );
		$gallery_id = $this->create_image_attachment( 'Variation Gallery Image', 'variation-gallery.jpg' );

		$product   = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $product->get_children()[0] );
		$variation->set_image_id( $image_id );
		$variation->set_gallery_image_ids( array( $gallery_id ) );
		$variation->save();

		$variations = $product->get_available_variations( 'array' );

		$this->assertSame( $image_id, $variations[0]['image_id'] );
		$this->assertSame( array( $gallery_id ), $variations[0]['gallery_image_ids'] );

		$product->delete( true );
	}

	/**
	 * @testdox 'get_available_variation' exposes typed variation gallery image IDs.
	 */
	public function test_get_available_variation_includes_gallery_image_ids() {
		$product   = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $product->get_children()[0] );
		$image_id  = wp_insert_attachment(
			array(
				'post_title'     => 'Variation Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$image_ids = array(
			wp_insert_attachment(
				array(
					'post_title'     => 'Variation Gallery Image 1',
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			),
			wp_insert_attachment(
				array(
					'post_title'     => 'Variation Gallery Image 2',
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			),
		);

		update_post_meta( $image_id, '_wp_attached_file', 'variation-featured.jpg' );
		foreach ( $image_ids as $i => $gallery_image_id ) {
			update_post_meta( $gallery_image_id, '_wp_attached_file', 'variation-gallery-' . ( $i + 1 ) . '.jpg' );
		}

		$variation->set_image_id( $image_id );
		$variation->set_gallery_image_ids( $image_ids );
		$variation->save();

		$available_variation = $product->get_available_variation( $variation );

		$this->assertSame( $image_ids, $available_variation['gallery_image_ids'] );
		$this->assertNotEmpty( $available_variation['gallery_images_html'] );
	}

	/**
	 * @testdox 'get_available_variation' falls back to the variation's own gallery when the variation featured image is stale.
	 */
	public function test_get_available_variation_falls_back_to_variation_gallery_when_featured_is_stale() {
		$product              = WC_Helper_Product::create_variation_product();
		$variation            = wc_get_product( $product->get_children()[0] );
		$parent_featured_id   = $this->create_image_attachment( 'Parent Featured Image', 'parent-featured.jpg' );
		$stale_featured_id    = $this->create_image_attachment( 'Stale Variation Image', 'stale-featured.jpg' );
		$variation_gallery_id = $this->create_image_attachment( 'Variation Gallery Image', 'variation-gallery.jpg' );

		// Delete-then-assign: set_image_id() doesn't validate the attachment,
		// but wp_delete_attachment() would clear _thumbnail_id on any post
		// pointing at it. Doing it in this order leaves the variation
		// referencing a deleted attachment, which is the bug we're testing.

		$product->set_image_id( $parent_featured_id );
		$product->save();

		wp_delete_attachment( $stale_featured_id, true );

		$variation->set_image_id( $stale_featured_id );
		$variation->set_gallery_image_ids( array( $variation_gallery_id ) );
		$variation->save();

		$available_variation = $product->get_available_variation( $variation );

		$this->assertSame( $variation_gallery_id, $available_variation['image_id'] );
		$this->assertStringContainsString( 'variation-gallery.jpg', $available_variation['gallery_images_html'] );
		$this->assertStringNotContainsString( 'parent-featured.jpg', $available_variation['gallery_images_html'] );
	}

	/**
	 * @testdox 'get_available_variation' falls back to the parent featured image when both the variation featured image and gallery are absent.
	 */
	public function test_get_available_variation_falls_back_to_parent_featured_when_variation_has_no_images() {
		$product            = WC_Helper_Product::create_variation_product();
		$variation          = wc_get_product( $product->get_children()[0] );
		$parent_featured_id = $this->create_image_attachment( 'Parent Featured Image', 'parent-featured.jpg' );
		$stale_featured_id  = $this->create_image_attachment( 'Stale Variation Image', 'stale-featured.jpg' );

		$product->set_image_id( $parent_featured_id );
		$product->save();

		wp_delete_attachment( $stale_featured_id, true );

		$variation->set_image_id( $stale_featured_id );
		$variation->set_gallery_image_ids( array() );
		$variation->save();

		$available_variation = $product->get_available_variation( $variation );

		$this->assertSame( $parent_featured_id, $available_variation['image_id'] );
		$this->assertSame( '', $available_variation['gallery_images_html'] );
	}

	/**
	 * @testdox get_variation_prices sorts on first call, skips re-sorting on repeat calls, and treats float and string prices as equal via loose comparison.
	 */
	public function test_get_variation_prices_skips_sort_on_repeated_call_and_with_equivalent_types(): void {
		$product = WC_Helper_Product::create_variation_product();
		$sut     = new class( $product->get_id() ) extends WC_Product_Variable {
			public int $sort_count = 0; // phpcs:ignore Squiz.Commenting.VariableComment.Missing

			protected function sort_variation_prices( $prices ) { // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
				++$this->sort_count;
				return parent::sort_variation_prices( $prices );
			}
		};

		// Ensure the store-level cache is not interfering the test.
		$invalidate_cache = static fn ( array $hash ) => array( ...$hash, wp_rand() );
		add_filter( 'woocommerce_get_variation_prices_hash', $invalidate_cache );

		try {
			// First call: price data will be initially populated, including sorting. 3 is a number of sort calls on initial cache population.
			$sut->get_variation_prices();
			$this->assertSame( 3, $sut->sort_count );

			// Second call: price data is unchanged and cache update being skipped. 3 is a number of sort calls, not changed since initial cache population.
			$sut->get_variation_prices();
			$this->assertSame( 3, $sut->sort_count );

			// Modify price data type, while keeping the price same.
			foreach ( $product->get_children() as $child_id ) {
				foreach ( array( '_price', '_regular_price' ) as $meta_key ) {
					$value = get_post_meta( $child_id, $meta_key, true );
					if ( '' !== $value ) {
						update_post_meta( $child_id, $meta_key, number_format( (float) $value, 2, '.', '' ) );
					}
				}
			}

			// Third call: price data is unchanged (data type is) and cache update being skipped. 3 is a number of sort calls, not changed since initial cache population.
			$sut->get_variation_prices();
			$this->assertSame( 3, $sut->sort_count );

			// Modify price.
			foreach ( $product->get_children() as $child_id ) {
				foreach ( array( '_price', '_regular_price' ) as $meta_key ) {
					$value = get_post_meta( $child_id, $meta_key, true );
					if ( '' !== $value ) {
						update_post_meta( $child_id, $meta_key, (float) $value + 0.01 );
					}
				}
			}

			// Fourth call: price data change detected — cache being updated. 6 is a number of sort calls: 3 on initial cache population + 3 on cache refresh.
			$sut->get_variation_prices();
			$this->assertSame( 6, $sut->sort_count );
		} finally {
			remove_filter( 'woocommerce_get_variation_prices_hash', $invalidate_cache );
		}

		$product->delete( true );
	}

	/**
	 * @testdox get_variation_prices returns a valid array structure when the woocommerce_variation_prices filter returns malformed data (null or false), restoring the pre-refactor foreach behaviour that tolerated non-array filter output.
	 * @dataProvider provider_malformed_variation_prices_filter_values
	 *
	 * @param mixed $malformed_value The malformed value for returning via woocommerce_get_variation_prices_hash filter.
	 */
	public function test_get_variation_prices_tolerates_malformed_filter_output( $malformed_value ): void {
		$product = WC_Helper_Product::create_variation_product();

		// Bust the transient so read_price_data() always reaches the woocommerce_variation_prices filter.
		$invalidate_cache = static fn( array $hash ) => array( ...$hash, wp_rand() );
		add_filter( 'woocommerce_get_variation_prices_hash', $invalidate_cache );

		$bad_filter = static fn() => $malformed_value;
		add_filter( 'woocommerce_variation_prices', $bad_filter );

		$this->setExpectedIncorrectUsage( 'WC_Product_Variable_Data_Store_CPT::read_price_data' );

		try {
			$prices = $product->get_variation_prices();
			$this->assertSame( $malformed_value, $prices );
		} finally {
			remove_filter( 'woocommerce_variation_prices', $bad_filter );
			remove_filter( 'woocommerce_get_variation_prices_hash', $invalidate_cache );
		}

		$product->delete( true );
	}

	/**
	 * @return array<string,array>
	 */
	public function provider_malformed_variation_prices_filter_values(): array {
		return array(
			'null'   => array( null ),
			'false'  => array( false ),
			'string' => array( 'bad_return' ),
			'object' => array( new stdClass() ),
		);
	}

	/**
	 * Create a real image attachment that passes `wp_attachment_is_image()`.
	 *
	 * @param string $title         Post title.
	 * @param string $attached_file Synthetic file path.
	 * @return int
	 */
	private function create_image_attachment( string $title, string $attached_file ): int {
		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => $title,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);

		update_post_meta( $attachment_id, '_wp_attached_file', $attached_file );

		return $attachment_id;
	}

	/**
	 * Builds a saved variable product whose variations have the given stored state, in order.
	 *
	 * Each spec is [ post status, stock status, regular price ].
	 *
	 * @param array $specs Variation specs.
	 * @return WC_Product_Variable Freshly loaded parent product.
	 */
	private function create_variable_product_with_variations( array $specs ): WC_Product_Variable {
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Scan order fixture' );
		$parent->set_attributes( array( WC_Helper_Product::create_product_attribute_object( 'size', array_map( 'strval', range( 1, count( $specs ) ) ) ) ) );
		$parent->save();

		foreach ( $specs as $index => $spec ) {
			list( $status, $stock_status, $regular_price ) = $spec;
			$variation                                     = new WC_Product_Variation();
			$variation->set_parent_id( $parent->get_id() );
			$variation->set_attributes( array( 'pa_size' => (string) ( $index + 1 ) ) );
			$variation->set_status( $status );
			$variation->set_stock_status( $stock_status );
			$variation->set_regular_price( $regular_price );
			$variation->save();
		}

		WC_Product_Variable::sync( $parent->get_id() );
		return wc_get_product( $parent->get_id() );
	}

	/**
	 * Counts how many distinct variations get their purchasability evaluated during a callback.
	 *
	 * @param callable $callback Code to run.
	 * @return int Number of distinct variation IDs passed to woocommerce_variation_is_purchasable.
	 */
	private function count_variations_checked( callable $callback ): int {
		$seen    = array();
		$counter = function ( $purchasable, $variation ) use ( &$seen ) {
			$seen[ $variation->get_id() ] = true;
			return $purchasable;
		};
		add_filter( 'woocommerce_variation_is_purchasable', $counter, 1, 2 );
		$callback();
		remove_filter( 'woocommerce_variation_is_purchasable', $counter, 1 );
		return count( $seen );
	}

	/**
	 * @testdox has_purchasable_variations checks likely-purchasable variations before the rest.
	 */
	public function test_has_purchasable_variations_checks_candidates_first(): void {
		$product = $this->create_variable_product_with_variations(
			array(
				array( ProductStatus::PUBLISH, ProductStockStatus::OUT_OF_STOCK, '10' ),
				array( ProductStatus::PUBLISH, ProductStockStatus::IN_STOCK, '' ),
				array( ProductStatus::PRIVATE, ProductStockStatus::IN_STOCK, '10' ),
				array( ProductStatus::PUBLISH, ProductStockStatus::IN_STOCK, '10' ),
			)
		);

		$result  = null;
		$checked = $this->count_variations_checked(
			function () use ( $product, &$result ) {
				$result = $product->has_purchasable_variations();
			}
		);

		$this->assertTrue( $result );
		$this->assertSame( 1, $checked, 'Only the purchasable candidate should have been evaluated.' );
	}

	/**
	 * @testdox has_purchasable_variations still evaluates non-candidates when every candidate is rejected by a filter.
	 */
	public function test_has_purchasable_variations_falls_back_to_non_candidates(): void {
		$product  = $this->create_variable_product_with_variations(
			array(
				array( ProductStatus::PUBLISH, ProductStockStatus::IN_STOCK, '10' ),
				array( ProductStatus::PUBLISH, ProductStockStatus::OUT_OF_STOCK, '10' ),
			)
		);
		$children = $product->get_children();

		// Reject the stored-state candidate, and let a filter make the out-of-stock one purchasable.
		add_filter(
			'woocommerce_is_purchasable',
			function ( $purchasable, $candidate ) use ( $children ) {
				return $purchasable && (int) $candidate->get_id() !== (int) $children[0];
			},
			10,
			2
		);
		add_filter( 'woocommerce_product_is_in_stock', '__return_true' );

		$result  = null;
		$checked = $this->count_variations_checked(
			function () use ( $product, &$result ) {
				$result = $product->has_purchasable_variations();
			}
		);

		$this->assertTrue( $result, 'A filter that makes a non-candidate purchasable must still be honoured.' );
		$this->assertSame( 2, $checked, 'Both variations should have been evaluated, candidate first.' );
	}

	/**
	 * @testdox has_purchasable_variations returns false when no variation is purchasable, evaluating every variation.
	 */
	public function test_has_purchasable_variations_evaluates_all_when_none_purchasable(): void {
		$product = $this->create_variable_product_with_variations(
			array(
				array( ProductStatus::PUBLISH, ProductStockStatus::OUT_OF_STOCK, '10' ),
				array( ProductStatus::PUBLISH, ProductStockStatus::IN_STOCK, '' ),
				array( ProductStatus::PRIVATE, ProductStockStatus::IN_STOCK, '10' ),
			)
		);

		$result  = null;
		$checked = $this->count_variations_checked(
			function () use ( $product, &$result ) {
				$result = $product->has_purchasable_variations();
			}
		);

		$this->assertFalse( $result );
		$this->assertSame( 3, $checked, 'Every variation is evaluated before returning false.' );
	}

	/**
	 * @testdox has_purchasable_variations evaluates is_purchasable before is_in_stock for each variation.
	 */
	public function test_has_purchasable_variations_keeps_check_order_per_variation(): void {
		$product = $this->create_variable_product_with_variations(
			array(
				array( ProductStatus::PUBLISH, ProductStockStatus::IN_STOCK, '10' ),
			)
		);

		$calls = array();
		add_filter(
			'woocommerce_variation_is_purchasable',
			function ( $value ) use ( &$calls ) {
				$calls[] = 'purchasable';
				return $value;
			}
		);
		add_filter(
			'woocommerce_product_is_in_stock',
			function ( $value ) use ( &$calls ) {
				$calls[] = 'in_stock';
				return $value;
			}
		);

		$this->assertTrue( $product->has_purchasable_variations() );
		$this->assertSame( array( 'purchasable', 'in_stock' ), $calls );
	}

	/**
	 * @testdox has_purchasable_variations finds a purchasable variation beyond the first batch without evaluating the ones before it.
	 */
	public function test_has_purchasable_variations_handles_products_above_the_batch_size(): void {
		$specs   = array_fill( 0, 60, array( ProductStatus::PUBLISH, ProductStockStatus::OUT_OF_STOCK, '10' ) );
		$specs[] = array( ProductStatus::PUBLISH, ProductStockStatus::IN_STOCK, '10' );
		$product = $this->create_variable_product_with_variations( $specs );

		$result  = null;
		$checked = $this->count_variations_checked(
			function () use ( $product, &$result ) {
				$result = $product->has_purchasable_variations();
			}
		);

		$this->assertTrue( $result );
		$this->assertSame( 1, $checked );
	}

	/**
	 * @testdox has_purchasable_variations ignores candidate IDs from the data store that are not children of the product.
	 */
	public function test_has_purchasable_variations_ignores_foreign_candidate_ids_from_data_store(): void {
		$foreign = WC_Helper_Product::create_simple_product();

		$data_store = new class() extends WC_Product_Variable_Data_Store_CPT {
			/**
			 * ID of a purchasable product that is not a child of the product under test.
			 *
			 * @var int
			 */
			public $foreign_id = 0;

			/**
			 * Returns the foreign product plus the first child twice.
			 *
			 * @param WC_Product_Variable $product       Parent product.
			 * @param int[]               $variation_ids Children being scanned.
			 * @return int[]
			 */
			public function get_purchasable_variation_candidates( $product, array $variation_ids ): array {
				return array( $this->foreign_id, $variation_ids[0], $variation_ids[0] );
			}
		};

		$data_store->foreign_id = $foreign->get_id();
		// Registered before the fixture exists: the product factory caches instances, so a product
		// loaded earlier would keep the stock data store.
		add_filter(
			'woocommerce_data_stores',
			static function ( $stores ) use ( $data_store ) {
				$stores['product-variable'] = $data_store;
				return $stores;
			}
		);

		$specs   = array_fill( 0, 61, array( ProductStatus::PUBLISH, ProductStockStatus::OUT_OF_STOCK, '10' ) );
		$product = $this->create_variable_product_with_variations( $specs );
		$child   = $product->get_children()[0];
		$this->assertSame( get_class( $data_store ), $product->get_data_store()->get_current_class_name() );

		$checked_ids    = array();
		$variation_hits = array();
		add_filter(
			'woocommerce_is_purchasable',
			function ( $purchasable, $checked ) use ( &$checked_ids ) {
				$checked_ids[] = $checked->get_id();
				return $purchasable;
			},
			1,
			2
		);
		add_filter(
			'woocommerce_variation_is_purchasable',
			function ( $purchasable, $variation ) use ( &$variation_hits ) {
				$variation_hits[] = $variation->get_id();
				return $purchasable;
			},
			1,
			2
		);

		$this->assertFalse( $product->has_purchasable_variations() );
		$this->assertNotContains( $foreign->get_id(), $checked_ids );
		$this->assertSame( 1, count( array_keys( $variation_hits, $child, true ) ) );
	}

	/**
	 * @testdox has_purchasable_variations respects the woocommerce_get_children filter.
	 */
	public function test_has_purchasable_variations_respects_children_filter(): void {
		$product  = $this->create_variable_product_with_variations(
			array(
				array( ProductStatus::PUBLISH, ProductStockStatus::IN_STOCK, '10' ),
				array( ProductStatus::PUBLISH, ProductStockStatus::OUT_OF_STOCK, '10' ),
			)
		);
		$children = $product->get_children();

		add_filter(
			'woocommerce_get_children',
			function ( $ids ) use ( $children ) {
				return array_values( array_intersect( $ids, array( $children[1] ) ) );
			}
		);

		$this->assertFalse( $product->has_purchasable_variations(), 'Only the filtered child list may be considered.' );
	}

	/**
	 * @testdox has_purchasable_variations asks the data store for candidates only above the batch size, and once per request.
	 */
	public function test_has_purchasable_variations_uses_the_candidate_query_only_when_it_pays(): void {
		$data_store = new class() extends WC_Product_Variable_Data_Store_CPT {
			/**
			 * How many times the candidate query was asked for.
			 *
			 * @var int
			 */
			public $calls = 0;

			/**
			 * Counts the call and defers to the real query.
			 *
			 * @param WC_Product_Variable $product       Parent product.
			 * @param int[]               $variation_ids Children being scanned.
			 * @return int[]
			 */
			public function get_purchasable_variation_candidates( $product, array $variation_ids ): array {
				++$this->calls;
				return parent::get_purchasable_variation_candidates( $product, $variation_ids );
			}
		};

		// Registered before the fixtures exist: the product factory caches instances, so a product
		// loaded earlier would keep the stock data store.
		add_filter(
			'woocommerce_data_stores',
			static function ( $stores ) use ( $data_store ) {
				$stores['product-variable'] = $data_store;
				return $stores;
			}
		);

		$spec        = array( ProductStatus::PUBLISH, ProductStockStatus::OUT_OF_STOCK, '10' );
		$at_batch    = $this->create_variable_product_with_variations( array_fill( 0, 50, $spec ) );
		$above_batch = $this->create_variable_product_with_variations( array_fill( 0, 51, $spec ) );

		$at_batch->has_purchasable_variations();
		$this->assertSame( 0, $data_store->calls, 'A product within one batch is scanned from the primed caches alone.' );

		$above_batch->has_purchasable_variations();
		$this->assertSame( 1, $data_store->calls, 'A product above the batch size asks the data store for candidates.' );

		$above_batch->has_purchasable_variations();
		$this->assertSame( 1, $data_store->calls, 'Repeat calls in the same request reuse the memoised candidate list.' );

		// Drop the memo so the next two calls really re-enter the branch.
		wp_cache_flush();
		$previous = wp_using_ext_object_cache( true );
		try {
			$above_batch->has_purchasable_variations();
		} finally {
			// Always restore. Cast to bool because wp_using_ext_object_cache( null ) is a
			// no-op, which would otherwise leak the simulated true state into later tests.
			wp_using_ext_object_cache( (bool) $previous );
		}
		$this->assertSame( 1, $data_store->calls, 'A persistent object cache makes priming cheap, so the candidate query is skipped.' );

		wp_cache_flush();
		$above_batch->has_purchasable_variations();
		$this->assertSame( 2, $data_store->calls, 'Without the memo or a persistent object cache, the candidate query runs again.' );
	}
}
