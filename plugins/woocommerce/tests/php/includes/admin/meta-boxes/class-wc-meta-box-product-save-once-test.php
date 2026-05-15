<?php
declare( strict_types = 1 );

/**
 * Tests that ensure the product Update action only triggers a single
 * WC_Product::save() during the `woocommerce_process_product_meta` cycle.
 *
 * Regression test for woocommerce/woocommerce#55882: prior to the fix, both
 * WC_Meta_Box_Product_Data::save() and WC_Meta_Box_Product_Images::save()
 * independently called $product->save(), causing two saves per update.
 *
 * @package WooCommerce\Tests\Admin\MetaBoxes
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName

/**
 * Class WC_Meta_Box_Product_Save_Once_Test
 */
class WC_Meta_Box_Product_Save_Once_Test extends WC_Unit_Test_Case {

	/**
	 * Number of times $product->save() has been observed during the test.
	 *
	 * @var int
	 */
	private $save_count = 0;

	/**
	 * Callback used to count product saves.
	 *
	 * @var callable|null
	 */
	private $save_counter_callback;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		require_once WC_ABSPATH . 'includes/admin/meta-boxes/class-wc-meta-box-product-data.php';
		require_once WC_ABSPATH . 'includes/admin/meta-boxes/class-wc-meta-box-product-images.php';

		$this->save_count            = 0;
		$this->save_counter_callback = function ( $product ) {
			if ( $product instanceof WC_Product ) {
				++$this->save_count;
			}
		};

		add_action( 'woocommerce_before_product_object_save', $this->save_counter_callback );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( null !== $this->save_counter_callback ) {
			remove_action( 'woocommerce_before_product_object_save', $this->save_counter_callback );
		}
		WC_Meta_Box_Product_Images::$gallery_handled_by_product_data = false;
		$_POST = array();

		parent::tearDown();
	}

	/**
	 * @testdox Should call product save() only once when both meta box save handlers run for a product update.
	 */
	public function test_product_save_is_called_once_for_update(): void {
		$product = WC_Helper_Product::create_simple_product();
		$post_id = $product->get_id();
		$post    = get_post( $post_id );

		$_POST = array(
			'product-type'          => 'simple',
			'post_ID'               => $post_id,
			'_regular_price'        => '12.34',
			'product_image_gallery' => '',
		);

		WC_Meta_Box_Product_Data::save( $post_id, $post );
		WC_Meta_Box_Product_Images::save( $post_id, $post );

		$this->assertSame(
			1,
			$this->save_count,
			'Product update should trigger WC_Product::save() exactly once across both meta box handlers.'
		);
	}

	/**
	 * @testdox Should persist gallery image ids set via the product data meta box save handler.
	 */
	public function test_gallery_image_ids_are_persisted_by_product_data_save(): void {
		$product = WC_Helper_Product::create_simple_product();
		$post_id = $product->get_id();
		$post    = get_post( $post_id );

		$_POST = array(
			'product-type'          => 'simple',
			'post_ID'               => $post_id,
			'_regular_price'        => '12.34',
			'product_image_gallery' => '10,20,30',
		);

		WC_Meta_Box_Product_Data::save( $post_id, $post );
		WC_Meta_Box_Product_Images::save( $post_id, $post );

		$reloaded = wc_get_product( $post_id );

		$this->assertSame(
			array( 10, 20, 30 ),
			array_values( $reloaded->get_gallery_image_ids( 'edit' ) ),
			'Gallery image ids submitted via the form should be persisted.'
		);
		$this->assertSame(
			1,
			$this->save_count,
			'Saving gallery ids alongside other product data should still only trigger a single save().'
		);
	}

	/**
	 * @testdox Should fall back to saving via the images meta box handler when the product data handler did not run.
	 */
	public function test_images_save_runs_when_product_data_save_is_unhooked(): void {
		$product = WC_Helper_Product::create_simple_product();
		$post_id = $product->get_id();
		$post    = get_post( $post_id );

		$_POST = array(
			'product-type'          => 'simple',
			'post_ID'               => $post_id,
			'product_image_gallery' => '4,5,6',
		);

		// Simulate a third party having unhooked WC_Meta_Box_Product_Data::save.
		WC_Meta_Box_Product_Images::$gallery_handled_by_product_data = false;

		WC_Meta_Box_Product_Images::save( $post_id, $post );

		$reloaded = wc_get_product( $post_id );

		$this->assertSame(
			array( 4, 5, 6 ),
			array_values( $reloaded->get_gallery_image_ids( 'edit' ) ),
			'Images handler should still save the gallery when the product data handler did not run.'
		);
		$this->assertSame(
			1,
			$this->save_count,
			'Fallback path in the images handler should still result in a single save().'
		);
	}
}
