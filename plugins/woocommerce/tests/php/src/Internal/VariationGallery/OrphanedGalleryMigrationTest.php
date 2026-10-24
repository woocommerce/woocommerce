<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\VariationGallery;

use Automattic\WooCommerce\Internal\VariationGallery\LegacyVariationGalleryCompatibility;
use Automattic\WooCommerce\Internal\VariationGallery\Migration;
use Automattic\WooCommerce\Internal\VariationGallery\OrphanedGalleryMigration;

/**
 * Tests for the one-time gallery-only variation normalization.
 */
class OrphanedGalleryMigrationTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox Should leave a failed batch unfinished so it can be retried safely.
	 */
	public function test_failed_selection_can_be_retried(): void {
		global $wpdb;

		$image     = $this->create_image();
		$variation = $this->create_variation( array( $image ) );
		update_option( Migration::COMPLETED_OPTION, time() );
		$fail_query = static function ( $query ) use ( $wpdb ) {
			return false !== strpos( $query, 'SELECT DISTINCT gallery.post_id' ) ? "SELECT missing_gallery_column FROM {$wpdb->postmeta}" : $query;
		};
		$suppressed = $wpdb->suppress_errors();
		$last_error = $wpdb->last_error;
		$failed     = false;
		add_filter( 'query', $fail_query );
		try {
			OrphanedGalleryMigration::run();
		} catch ( \RuntimeException $error ) {
			$failed = true;
		} finally {
			remove_filter( 'query', $fail_query );
			$wpdb->suppress_errors( $suppressed );
			$wpdb->last_error = $last_error;
		}

		$this->assertTrue( $failed, 'The database updater must be told that the batch failed.' );
		$this->assertFalse( get_option( OrphanedGalleryMigration::COMPLETED_OPTION ), 'A failed selection must not mark the migration complete.' );
		$this->assertFalse( get_option( OrphanedGalleryMigration::LAST_ID_OPTION ), 'A failed selection must not advance the cursor.' );
		$this->assertFalse( OrphanedGalleryMigration::run(), 'The same batch should succeed once the database error is gone.' );
		$this->assertSame( $image, (int) wc_get_product( $variation->get_id() )->get_image_id( 'edit' ), 'Retrying should promote the photo.' );
	}

	/**
	 * @testdox Should copy pending legacy gallery data before normalizing its featured image.
	 */
	public function test_waits_for_legacy_gallery_migration(): void {
		$first     = $this->create_image();
		$second    = $this->create_image();
		$variation = $this->create_variation( array() );
		update_post_meta( $variation->get_id(), '_wc_additional_variation_images', "$first,$second" );
		delete_option( Migration::COMPLETED_OPTION );

		$this->assertFalse( OrphanedGalleryMigration::run(), 'The legacy data and normalization should finish in one small batch.' );

		$updated = new \WC_Product_Variation( $variation->get_id() );
		$this->assertSame( $first, (int) $updated->get_image_id( 'edit' ), 'The first legacy photo must be promoted before completion.' );
		$this->assertSame( array( $second ), array_map( 'intval', $updated->get_gallery_image_ids( 'edit' ) ), 'Other legacy photos should remain in the core gallery.' );
	}

	/**
	 * @testdox Should promote the first valid gallery image and preserve other gallery references.
	 */
	public function test_promotes_first_valid_image(): void {
		$first     = $this->create_image();
		$second    = $this->create_image();
		$variation = $this->create_variation( array( 999999999, $first, $second ) );

		$callback = OrphanedGalleryMigration::class . '::run';
		$this->assertContains( $callback, \WC_Install::get_db_update_callbacks()['11.4.0'], 'The release updater must register the migration.' );
		\WC_Install::run_update_callback( $callback );
		$this->assertNotEmpty( get_option( OrphanedGalleryMigration::COMPLETED_OPTION ), 'The database updater should finish the migration.' );

		$updated = wc_get_product( $variation->get_id() );
		$this->assertSame( $first, (int) $updated->get_image_id( 'edit' ), 'The first valid photo should become featured.' );
		$this->assertSame( array( 999999999, $second ), $updated->get_gallery_image_ids( 'edit' ), 'Unrelated gallery references should be preserved.' );
	}

	/**
	 * @testdox Should promote a PDF with a preview before a later gallery photo.
	 */
	public function test_promotes_pdf_preview(): void {
		$pdf = wp_insert_attachment( array( 'post_mime_type' => 'application/pdf' ) );
		update_post_meta( $pdf, '_wp_attached_file', 'orphaned-document.pdf' );
		wp_update_attachment_metadata(
			$pdf,
			array(
				'sizes' => array(
					'full' => array(
						'file'      => 'orphaned-document.jpg',
						'width'     => 600,
						'height'    => 800,
						'mime-type' => 'image/jpeg',
					),
				),
			)
		);
		$photo     = $this->create_image();
		$variation = $this->create_variation( array( $pdf, $photo ) );

		OrphanedGalleryMigration::run();

		$updated = new \WC_Product_Variation( $variation->get_id() );
		$this->assertSame( $pdf, (int) $updated->get_image_id( 'edit' ) );
		$this->assertSame( array( $photo ), $updated->get_gallery_image_ids( 'edit' ) );
	}

	/**
	 * @testdox Should leave a PDF without a preview assigned to its original gallery field.
	 */
	public function test_leaves_pdf_without_preview_unchanged(): void {
		$pdf = wp_insert_attachment( array( 'post_mime_type' => 'application/pdf' ) );
		update_post_meta( $pdf, '_wp_attached_file', 'orphaned-document.pdf' );
		$variation = $this->create_variation( array( $pdf ) );

		OrphanedGalleryMigration::run();

		$updated = new \WC_Product_Variation( $variation->get_id() );
		$this->assertSame( 0, (int) $updated->get_image_id( 'edit' ) );
		$this->assertSame( array( $pdf ), $updated->get_gallery_image_ids( 'edit' ) );
	}

	/**
	 * @testdox Should normalize gallery-only data left by the inherited-image cleanup.
	 */
	public function test_normalizes_gallery_left_by_inherited_image_cleanup(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';
		update_option( 'woocommerce_db_version', '11.1.0' );
		delete_option( 'woocommerce_update_1120_completed_at' );
		delete_option( 'woocommerce_update_1120_cleanup_state' );
		$parent_image = $this->create_image();
		$extra        = $this->create_image();
		$variation    = $this->create_variation( array( $extra ), $parent_image );
		$parent       = wc_get_product( $variation->get_parent_id() );
		$parent->set_image_id( $parent_image );
		$parent->save();

		$this->assertFalse( wc_update_1120_cleanup_inherited_variation_images() );
		$this->assertSame( '', get_post_meta( $variation->get_id(), '_thumbnail_id', true ) );

		OrphanedGalleryMigration::run();

		$updated = new \WC_Product_Variation( $variation->get_id() );
		$this->assertSame( $extra, (int) $updated->get_image_id( 'edit' ) );
		$this->assertSame( array(), $updated->get_gallery_image_ids( 'edit' ) );
	}

	/**
	 * @testdox Should keep an explicit featured image even when it matches the parent.
	 */
	public function test_preserves_explicit_featured_image(): void {
		$parent_image = $this->create_image();
		$extra        = $this->create_image();
		$variation    = $this->create_variation( array( $extra ), $parent_image );
		$parent       = wc_get_product( $variation->get_parent_id() );
		$parent->set_image_id( $parent_image );
		$parent->save();

		OrphanedGalleryMigration::run();

		$updated = wc_get_product( $variation->get_id() );
		$this->assertSame( $parent_image, (int) $updated->get_image_id( 'edit' ), 'An intentional match must remain assigned.' );
		$this->assertSame( array( $extra ), $updated->get_gallery_image_ids( 'edit' ), 'Its gallery must remain unchanged.' );
	}

	/**
	 * @testdox Should not revive legacy images after promoting a single core gallery image.
	 */
	public function test_disables_legacy_fallback_without_deleting_legacy_meta(): void {
		$image     = $this->create_image();
		$variation = $this->create_variation( array( $image ) );
		update_post_meta( $variation->get_id(), '_wc_additional_variation_images', (string) $image );
		( new LegacyVariationGalleryCompatibility() )->register();

		OrphanedGalleryMigration::run();

		$updated = wc_get_product( $variation->get_id() );
		$this->assertSame( array(), $updated->get_gallery_image_ids(), 'A single promoted image must not reappear as an extra.' );
		$this->assertSame( (string) $image, get_post_meta( $variation->get_id(), '_wc_additional_variation_images', true ), 'Legacy meta remains available to third-party readers.' );
	}

	/**
	 * @testdox Should skip galleries with no valid images and leave new records alone after completion.
	 */
	public function test_completion_is_one_time(): void {
		$invalid = $this->create_variation( array( 999999999 ) );
		$this->assertFalse( OrphanedGalleryMigration::run(), 'Invalid-only galleries must not cause endless batches.' );
		$this->assertNotEmpty( get_option( OrphanedGalleryMigration::COMPLETED_OPTION ), 'Completion must be recorded.' );
		$this->assertSame( 0, (int) wc_get_product( $invalid->get_id() )->get_image_id( 'edit' ), 'Invalid references cannot become featured.' );

		$later = $this->create_variation( array( $this->create_image() ) );
		$this->assertFalse( OrphanedGalleryMigration::run(), 'A completed migration must not run again.' );
		$this->assertSame( 0, (int) wc_get_product( $later->get_id() )->get_image_id( 'edit' ), 'Future API/import records retain their stored fields.' );
	}

	/**
	 * @testdox Should resume batches past invalid-only rows and clear the cursor on completion.
	 */
	public function test_batches_invalid_rows_without_looping(): void {
		global $wpdb;

		$ids = self::factory()->post->create_many( 251, array( 'post_type' => 'product_variation' ) );
		foreach ( $ids as $id ) {
			add_post_meta( $id, '_product_image_gallery', '999999999' );
		}
		$this->assertTrue( OrphanedGalleryMigration::run(), 'The first 250 records should require another batch.' );
		$this->assertSame( $ids[249], (int) get_option( OrphanedGalleryMigration::LAST_ID_OPTION ), 'The cursor should advance even when no valid photo is found.' );
		$this->assertFalse( OrphanedGalleryMigration::run(), 'The remaining row should finish the scan.' );
		$this->assertFalse( get_option( OrphanedGalleryMigration::LAST_ID_OPTION ), 'The completed scan should remove its cursor.' );
		$this->assertSame( '', $wpdb->last_error, 'Batch selection must not produce database errors.' );
	}

	/**
	 * Create one variation with canonical stored image fields.
	 *
	 * @param int[] $gallery Gallery attachment IDs.
	 * @param int   $featured Featured attachment ID.
	 * @return \WC_Product_Variation
	 */
	private function create_variation( array $gallery, int $featured = 0 ): \WC_Product_Variation {
		$parent = new \WC_Product_Variable();
		$parent->save();
		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_image_id( $featured );
		$variation->set_gallery_image_ids( $gallery );
		$variation->save();
		return $variation;
	}

	/**
	 * Create an image attachment without writing a media file.
	 *
	 * @return int
	 */
	private function create_image(): int {
		$id = wp_insert_attachment( array( 'post_mime_type' => 'image/jpeg' ) );
		update_post_meta( $id, '_wp_attached_file', 'orphaned-photo.jpg' );
		return $id;
	}
}
