<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\VariationGallery;

use Automattic\WooCommerce\Internal\VariationGallery\LegacyVariationGalleryCompatibility;
use Automattic\WooCommerce\Internal\VariationGallery\Migration;
use WC_Helper_Product;

/**
 * Tests for the legacy variation gallery migration runner.
 */
class MigrationTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox Migration copies legacy variation gallery meta into the core gallery prop and disables fallback.
	 */
	public function test_migration_copies_legacy_gallery_and_disables_fallback(): void {
		$variation_id = $this->create_variation();
		$image_ids    = array(
			$this->create_attachment( 'Legacy gallery image 1' ),
			$this->create_attachment( 'Legacy gallery image 2' ),
		);

		update_post_meta( $variation_id, '_wc_additional_variation_images', implode( ',', $image_ids ) );

		$this->assertFalse( Migration::run() );

		$this->assertTrue( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( $variation_id ) );
		$this->assertSame( implode( ',', $image_ids ), get_post_meta( $variation_id, '_product_image_gallery', true ) );
	}

	/**
	 * @testdox Migration preserves existing core variation gallery values while disabling fallback.
	 */
	public function test_migration_preserves_existing_core_gallery(): void {
		$variation_id       = $this->create_variation();
		$core_gallery_ids   = array(
			$this->create_attachment( 'Core gallery image 1' ),
			$this->create_attachment( 'Core gallery image 2' ),
		);
		$legacy_gallery_ids = array(
			$this->create_attachment( 'Legacy gallery image 1' ),
			$this->create_attachment( 'Legacy gallery image 2' ),
		);

		update_post_meta( $variation_id, '_product_image_gallery', implode( ',', $core_gallery_ids ) );
		update_post_meta( $variation_id, '_wc_additional_variation_images', implode( ',', $legacy_gallery_ids ) );

		$this->assertFalse( Migration::run() );

		$this->assertTrue( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( $variation_id ) );
		$this->assertSame( implode( ',', $core_gallery_ids ), get_post_meta( $variation_id, '_product_image_gallery', true ) );
	}

	/**
	 * @testdox Migration disables fallback for malformed legacy variation gallery meta without writing invalid core values.
	 */
	public function test_migration_disables_fallback_for_malformed_legacy_meta(): void {
		$variation_id = $this->create_variation();

		update_post_meta( $variation_id, '_wc_additional_variation_images', 'not-an-id' );

		$this->assertFalse( Migration::run() );

		$this->assertTrue( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( $variation_id ) );
		$this->assertSame( '', get_post_meta( $variation_id, '_product_image_gallery', true ) );
	}

	/**
	 * @testdox Migration batches legacy variation gallery rows and requeues until complete.
	 */
	public function test_migration_batches_updates(): void {
		global $wpdb;

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Test setup needs to scope deletes by meta_key.
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_wc_additional_variation_images' ) );
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_product_image_gallery' ) );
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => LegacyVariationGalleryCompatibility::get_core_managed_meta_key() ) );
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key

		$variation_ids = array();

		for ( $index = 0; $index < 251; ++$index ) {
			$variation_id    = $this->create_variation_post();
			$variation_ids[] = $variation_id;
			update_post_meta( $variation_id, '_wc_additional_variation_images', (string) ( $index + 1 ) );
		}

		$this->assertTrue( Migration::run() );

		$processed_after_first_batch = 0;

		foreach ( $variation_ids as $variation_id ) {
			if ( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( $variation_id ) ) {
				++$processed_after_first_batch;
			}
		}

		$this->assertSame( 250, $processed_after_first_batch );
		$this->assertFalse( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( end( $variation_ids ) ) );

		$this->assertFalse( Migration::run() );

		foreach ( $variation_ids as $variation_id ) {
			$this->assertTrue( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( $variation_id ) );
		}
	}

	/**
	 * Create a variation for testing.
	 */
	private function create_variation(): int {
		$product = WC_Helper_Product::create_variation_product();

		return (int) $product->get_children()[0];
	}

	/**
	 * Create a bare variation post for migration batching tests.
	 */
	private function create_variation_post(): int {
		return self::factory()->post->create(
			array(
				'post_type'   => 'product_variation',
				'post_status' => 'publish',
			)
		);
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
