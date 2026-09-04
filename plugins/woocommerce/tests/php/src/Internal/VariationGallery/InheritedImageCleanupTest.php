<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\VariationGallery;

use Automattic\WooCommerce\Internal\VariationGallery\InheritedImageCleanup;
use WC_Helper_Product;

/**
 * Tests for InheritedImageCleanup.
 */
class InheritedImageCleanupTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox A variation whose stored featured image equals the parent's current featured image is reset to inherit dynamically.
	 */
	public function test_removes_thumbnail_that_duplicates_parent_featured() {
		list( $variation_id, $parent_featured ) = $this->create_variation_with_parent_featured();
		update_post_meta( $variation_id, '_thumbnail_id', (string) $parent_featured );

		$has_more = InheritedImageCleanup::run();

		$this->assertFalse( $has_more );
		$this->assertSame( '', get_post_meta( $variation_id, '_thumbnail_id', true ), 'The duplicated featured image should be removed so the variation inherits again.' );
		$this->assertNotFalse( get_option( InheritedImageCleanup::COMPLETED_OPTION ), 'The cleanup should record completion.' );
	}

	/**
	 * @testdox A variation whose stored featured image differs from the parent's is left untouched.
	 */
	public function test_keeps_thumbnail_that_diverged_from_parent_featured() {
		list( $variation_id ) = $this->create_variation_with_parent_featured();
		$own_image            = $this->create_attachment( 'Variation own image' );
		update_post_meta( $variation_id, '_thumbnail_id', (string) $own_image );

		InheritedImageCleanup::run();

		$this->assertSame(
			(string) $own_image,
			get_post_meta( $variation_id, '_thumbnail_id', true ),
			'A diverged value may be a deliberate merchant choice and must survive the cleanup.'
		);
	}

	/**
	 * @testdox The cleanup removes only metadata values that duplicate the parent image.
	 */
	public function test_keeps_other_thumbnail_metadata_values() {
		list( $variation_id, $parent_featured ) = $this->create_variation_with_parent_featured();
		$own_image                              = $this->create_attachment( 'Variation own image' );
		update_post_meta( $variation_id, '_thumbnail_id', (string) $parent_featured );
		add_post_meta( $variation_id, '_thumbnail_id', (string) $own_image );

		InheritedImageCleanup::run();

		$this->assertSame( array( (string) $own_image ), get_post_meta( $variation_id, '_thumbnail_id', false ) );
	}

	/**
	 * @testdox The cleanup never runs again once the completion option is set.
	 */
	public function test_does_not_run_after_completion() {
		list( $variation_id, $parent_featured ) = $this->create_variation_with_parent_featured();
		update_post_meta( $variation_id, '_thumbnail_id', (string) $parent_featured );
		update_option( InheritedImageCleanup::COMPLETED_OPTION, time() );

		$has_more = InheritedImageCleanup::run();

		$this->assertFalse( $has_more );
		$this->assertSame(
			(string) $parent_featured,
			get_post_meta( $variation_id, '_thumbnail_id', true ),
			'A value set after the one-time cleanup completed must never be removed.'
		);
	}

	/**
	 * Create a variable product with a featured image and return the first variation's ID plus that image ID.
	 *
	 * @return array{0: int, 1: int}
	 */
	private function create_variation_with_parent_featured(): array {
		$product         = WC_Helper_Product::create_variation_product();
		$parent_featured = $this->create_attachment( 'Parent featured image' );
		$product->set_image_id( $parent_featured );
		$product->save();

		return array( $product->get_children()[0], $parent_featured );
	}

	/**
	 * @param string $title Attachment title.
	 * @return int
	 */
	private function create_attachment( string $title ): int {
		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => $title,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);

		update_post_meta( $attachment_id, '_wp_attached_file', sanitize_title( $title ) . '.jpg' );

		return $attachment_id;
	}
}
