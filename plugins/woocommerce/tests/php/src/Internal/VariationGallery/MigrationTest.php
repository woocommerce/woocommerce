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

		$this->assertSame(
			1,
			$wpdb->insert(
				$wpdb->posts,
				array(
					'post_type'   => 'product_variation',
					'post_status' => 'publish',
					'post_title'  => 'Variation gallery migration batch fixture',
				)
			)
		);
		$decoy_id      = (int) $wpdb->insert_id;
		$variation_ids = $this->create_variation_posts( 251 );
		$this->assertNotContains( $decoy_id, $variation_ids );

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
		$this->assertFalse( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( $decoy_id ) );
		$this->assertSame( '', get_post_meta( $decoy_id, '_wc_additional_variation_images', true ) );
		$this->assertSame( '', get_post_meta( $decoy_id, '_product_image_gallery', true ) );
	}

	/**
	 * Create a variation for testing.
	 */
	private function create_variation(): int {
		$product = WC_Helper_Product::create_variation_product();

		return (int) $product->get_children()[0];
	}

	/**
	 * Create bare variation posts and legacy gallery metadata for migration batching tests.
	 *
	 * The migration reads these rows directly, so inserting the fixture in bulk avoids
	 * running unrelated product hooks hundreds of times.
	 *
	 * @param int $count Number of variations to create.
	 * @return int[] Variation post IDs.
	 */
	private function create_variation_posts( int $count ): array {
		global $wpdb;

		$post_title        = 'Variation gallery migration batch fixture ' . wp_generate_uuid4();
		$post_placeholders = array_fill( 0, $count, '(%s, %s, %s)' );
		$post_values       = array();

		for ( $index = 0; $index < $count; ++$index ) {
			array_push( $post_values, 'product_variation', 'publish', $post_title );
		}

		$posts_query = $wpdb->prepare(
			"INSERT INTO {$wpdb->posts} (post_type, post_status, post_title) VALUES " . implode( ', ', $post_placeholders ), // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- Placeholders are generated above.
			$post_values
		);
		$wpdb->query( $posts_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above.

		$variation_ids = array_map(
			'intval',
			$wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_title = %s ORDER BY ID ASC",
					'product_variation',
					$post_title
				)
			)
		);
		$this->assertCount( $count, $variation_ids );

		$meta_placeholders = array_fill( 0, $count, '(%d, %s, %s)' );
		$meta_values       = array();

		foreach ( $variation_ids as $index => $variation_id ) {
			array_push( $meta_values, $variation_id, '_wc_additional_variation_images', (string) ( $index + 1 ) );
		}

		$meta_query = $wpdb->prepare(
			"INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ', ', $meta_placeholders ), // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- Placeholders are generated above.
			$meta_values
		);
		$wpdb->query( $meta_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above.

		return $variation_ids;
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
