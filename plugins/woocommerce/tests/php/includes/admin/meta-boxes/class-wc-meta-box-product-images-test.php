<?php
/**
 * Tests for the product images meta box.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\ProductGallery\ProductMediaGallery;

require_once WC_ABSPATH . 'includes/admin/meta-boxes/class-wc-meta-box-product-images.php';

/**
 * Tests for WC_Meta_Box_Product_Images.
 */
class WC_Meta_Box_Product_Images_Test extends WC_Unit_Test_Case {

	/**
	 * Product IDs created by a test.
	 *
	 * @var int[]
	 */
	private $product_ids = array();

	/**
	 * Attachment IDs created by a test.
	 *
	 * @var int[]
	 */
	private $attachment_ids = array();

	/**
	 * Whether the product media gallery feature option existed before the test.
	 *
	 * @var bool
	 */
	private $had_product_media_gallery_option;

	/**
	 * Original product media gallery feature option value.
	 *
	 * @var mixed
	 */
	private $original_product_media_gallery_option;

	/**
	 * Whether the product media gallery feature option was autoloaded before the test.
	 *
	 * @var bool
	 */
	private $product_media_gallery_option_was_autoloaded;

	/**
	 * Whether the POST global existed before the test.
	 *
	 * @var bool
	 */
	private $had_post_global;

	/**
	 * Original POST global value.
	 *
	 * @var mixed
	 */
	private $original_post_global;

	/**
	 * Set up test state.
	 */
	public function setUp(): void {
		parent::setUp();

		$missing_option                                    = new stdClass();
		$this->original_product_media_gallery_option       = get_option( ProductMediaGallery::ENABLE_OPTION_NAME, $missing_option );
		$this->had_product_media_gallery_option            = $this->original_product_media_gallery_option !== $missing_option;
		$this->product_media_gallery_option_was_autoloaded = array_key_exists( ProductMediaGallery::ENABLE_OPTION_NAME, wp_load_alloptions() );
		$this->had_post_global                             = array_key_exists( '_POST', $GLOBALS );
		$this->original_post_global                        = $GLOBALS['_POST'] ?? null;

		update_option( ProductMediaGallery::ENABLE_OPTION_NAME, 'no' );
	}

	/**
	 * Restore test state.
	 */
	public function tearDown(): void {
		foreach ( $this->product_ids as $product_id ) {
			wp_delete_post( $product_id, true );
		}

		foreach ( $this->attachment_ids as $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}

		if ( $this->had_product_media_gallery_option ) {
			update_option( ProductMediaGallery::ENABLE_OPTION_NAME, $this->original_product_media_gallery_option );
		} else {
			delete_option( ProductMediaGallery::ENABLE_OPTION_NAME );
		}
		$this->assertSame(
			$this->product_media_gallery_option_was_autoloaded,
			array_key_exists( ProductMediaGallery::ENABLE_OPTION_NAME, wp_load_alloptions() ),
			'The product media gallery feature option should retain its original autoload behavior.'
		);

		if ( $this->had_post_global ) {
			$GLOBALS['_POST'] = $this->original_post_global;
		} else {
			unset( $GLOBALS['_POST'] );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Saves ordered gallery images through the classic product images meta box lifecycle.
	 */
	public function test_save_persists_ordered_gallery_lifecycle(): void {
		$product         = $this->create_simple_product();
		$first_image_id  = $this->create_image_attachment();
		$second_image_id = $this->create_image_attachment();
		$third_image_id  = $this->create_image_attachment();

		$this->save_gallery( $product->get_id(), array( $first_image_id, $second_image_id, $third_image_id ) );
		$this->assertSame(
			array( $first_image_id, $second_image_id, $third_image_id ),
			$this->get_fresh_product( $product->get_id() )->get_gallery_image_ids(),
			'The classic meta box should persist gallery image IDs in their posted order.'
		);

		$this->save_gallery( $product->get_id(), array( $second_image_id, $third_image_id ) );
		$this->assertSame(
			array( $second_image_id, $third_image_id ),
			$this->get_fresh_product( $product->get_id() )->get_gallery_image_ids(),
			'The classic meta box should persist removed gallery images and their remaining order.'
		);

		$this->save_gallery( $product->get_id(), array() );
		$this->assertSame(
			array(),
			$this->get_fresh_product( $product->get_id() )->get_gallery_image_ids(),
			'The classic meta box should clear the gallery when its posted field is empty.'
		);
	}

	/**
	 * @testdox Renders saved featured and gallery images in order before rendering the placeholder.
	 */
	public function test_saved_gallery_renders_featured_and_gallery_images_then_placeholder(): void {
		$product         = $this->create_simple_product();
		$first_image_id  = $this->create_image_attachment();
		$second_image_id = $this->create_image_attachment();
		$third_image_id  = $this->create_image_attachment();
		$product_id      = $product->get_id();

		$product->set_image_id( $first_image_id );
		$product->save();
		$this->save_gallery( $product_id, array( $second_image_id, $third_image_id ) );

		$rendered_attachment_ids = array();
		$record_attachment_id    = static function ( $html, $attachment_id ) use ( &$rendered_attachment_ids ) {
			$rendered_attachment_ids[] = (int) $attachment_id;

			return $html;
		};
		add_filter( 'woocommerce_single_product_image_thumbnail_html', $record_attachment_id, 10, 2 );

		try {
			$gallery_html = wc_get_product_gallery_html( $this->get_fresh_product( $product_id ) );
		} finally {
			remove_filter( 'woocommerce_single_product_image_thumbnail_html', $record_attachment_id );
		}

		$this->assertSame(
			array( $first_image_id, $second_image_id, $third_image_id ),
			$rendered_attachment_ids,
			'The classic gallery template should render the featured image before saved gallery images.'
		);
		$this->assertStringContainsString( 'woocommerce-product-gallery__image', $gallery_html, 'The classic gallery template should render product image markup.' );
		$this->assertStringContainsString( 'wp-post-image', $gallery_html, 'The classic gallery template should render the featured attachment image.' );

		$product = $this->get_fresh_product( $product_id );
		$product->set_image_id( $second_image_id );
		$product->save();
		$this->save_gallery( $product_id, array( $third_image_id ) );

		$rendered_attachment_ids = array();
		add_filter( 'woocommerce_single_product_image_thumbnail_html', $record_attachment_id, 10, 2 );
		try {
			$gallery_html = wc_get_product_gallery_html( $this->get_fresh_product( $product_id ) );
		} finally {
			remove_filter( 'woocommerce_single_product_image_thumbnail_html', $record_attachment_id );
		}

		$this->assertSame(
			array( $second_image_id, $third_image_id ),
			$rendered_attachment_ids,
			'The classic gallery template should render the updated featured and gallery image order.'
		);
		$this->assertStringContainsString( 'wp-post-image', $gallery_html, 'The updated featured attachment should be rendered.' );

		$this->save_gallery( $product_id, array() );
		$product = $this->get_fresh_product( $product_id );
		$product->set_image_id( 0 );
		$product->save();

		$rendered_attachment_ids = array();
		add_filter( 'woocommerce_single_product_image_thumbnail_html', $record_attachment_id, 10, 2 );
		try {
			$gallery_html = wc_get_product_gallery_html( $this->get_fresh_product( $product_id ) );
		} finally {
			remove_filter( 'woocommerce_single_product_image_thumbnail_html', $record_attachment_id );
		}

		$this->assertStringContainsString( 'Awaiting product image', $gallery_html, 'The classic gallery template should render the product image placeholder after both image sources are cleared.' );
		$this->assertSame(
			array(),
			array_values(
				array_filter(
					$rendered_attachment_ids,
					static function ( int $attachment_id ): bool {
						return $attachment_id > 0;
					}
				)
			),
			'The placeholder should not render a positive attachment ID.'
		);
	}

	/**
	 * Create and track a simple product.
	 *
	 * @return WC_Product
	 */
	private function create_simple_product(): WC_Product {
		$product             = WC_Helper_Product::create_simple_product();
		$this->product_ids[] = $product->get_id();

		return $product;
	}

	/**
	 * Create and track a real image attachment.
	 *
	 * @return int
	 */
	private function create_image_attachment(): int {
		$attachment_id          = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$this->attachment_ids[] = $attachment_id;

		return $attachment_id;
	}

	/**
	 * Persist gallery image IDs through the real classic meta-box handler.
	 *
	 * @param int   $product_id Product ID.
	 * @param int[] $image_ids  Gallery image IDs.
	 */
	private function save_gallery( int $product_id, array $image_ids ): void {
		$_POST = array(
			'product-type'          => 'simple',
			'product_image_gallery' => implode( ',', $image_ids ),
		);

		$post = get_post( $product_id );

		if ( ! $post instanceof WP_Post ) {
			throw new RuntimeException( 'Expected the product post to exist before saving its gallery.' );
		}

		WC_Meta_Box_Product_Images::save( $product_id, $post );
	}

	/**
	 * Load a product from persistent storage.
	 *
	 * @param int $product_id Product ID.
	 * @return WC_Product
	 */
	private function get_fresh_product( int $product_id ): WC_Product {
		$product = wc_get_product( $product_id );

		if ( ! $product instanceof WC_Product ) {
			throw new RuntimeException( 'Expected the saved product to reload from persistent storage.' );
		}

		return $product;
	}
}
