<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\VariationGallery;

use Automattic\WooCommerce\Internal\VariationGallery\LegacyVariationGalleryCompatibility;
use WC_Helper_Product;
use WC_Product_Variation;

/**
 * Tests for LegacyVariationGalleryCompatibility.
 */
class LegacyVariationGalleryCompatibilityTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var LegacyVariationGalleryCompatibility
	 */
	private $sut;

	/**
	 * Set up test dependencies.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new LegacyVariationGalleryCompatibility();
	}

	/**
	 * Remove registered filters after each test.
	 */
	public function tearDown(): void {
		remove_filter(
			'woocommerce_product_variation_get_gallery_image_ids',
			array( $this->sut, 'maybe_read_legacy_gallery_image_ids' ),
			10
		);
		parent::tearDown();
	}

	/**
	 * @testdox Legacy variation gallery meta is ignored when the core gallery already has values.
	 */
	public function test_legacy_gallery_meta_is_ignored_when_core_gallery_is_present() {
		$variation          = $this->create_variation();
		$core_gallery_ids   = array(
			$this->create_attachment( 'Core variation gallery 1' ),
			$this->create_attachment( 'Core variation gallery 2' ),
		);
		$legacy_gallery_ids = array(
			$this->create_attachment( 'Legacy variation gallery 1' ),
			$this->create_attachment( 'Legacy variation gallery 2' ),
		);

		update_post_meta(
			$variation->get_id(),
			'_wc_additional_variation_images',
			implode( ',', $legacy_gallery_ids )
		);

		$this->assertSame(
			$core_gallery_ids,
			$this->sut->maybe_read_legacy_gallery_image_ids(
				$core_gallery_ids,
				$variation
			)
		);
	}

	/**
	 * @testdox Legacy variation gallery meta is ignored when fallback has been disabled.
	 */
	public function test_legacy_gallery_meta_is_ignored_when_fallback_has_been_disabled() {
		$variation = $this->create_variation();
		$image_ids = array(
			$this->create_attachment( 'Legacy variation gallery 1' ),
			$this->create_attachment( 'Legacy variation gallery 2' ),
		);

		update_post_meta(
			$variation->get_id(),
			'_wc_additional_variation_images',
			implode( ',', $image_ids )
		);
		LegacyVariationGalleryCompatibility::mark_variation_id_core_managed( $variation->get_id() );

		$this->assertSame(
			array(),
			$this->sut->maybe_read_legacy_gallery_image_ids( array(), $variation )
		);
	}

	/**
	 * @testdox Register wires the legacy gallery fallback into variation prop reads.
	 */
	public function test_register_wires_legacy_gallery_fallback_into_variation_prop_reads() {
		$variation = $this->create_variation();
		$image_ids = array(
			$this->create_attachment( 'Legacy variation gallery 1' ),
			$this->create_attachment( 'Legacy variation gallery 2' ),
		);

		update_post_meta(
			$variation->get_id(),
			'_wc_additional_variation_images',
			implode( ',', $image_ids )
		);

		$this->sut->register();

		$this->assertSame( $image_ids, array_map( 'intval', $variation->get_gallery_image_ids() ) );
	}

	/**
	 * Create a variation for testing.
	 *
	 * @return WC_Product_Variation
	 */
	private function create_variation(): WC_Product_Variation {
		$product = WC_Helper_Product::create_variation_product();

		return wc_get_product( $product->get_children()[0] );
	}

	/**
	 * Create a test attachment.
	 *
	 * @param string $title Attachment title.
	 * @return int
	 */
	private function create_attachment( string $title ): int {
		return wp_insert_attachment(
			array(
				'post_title'     => $title,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
	}
}
