<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Tests\Helpers\ImageAttachmentTrait;

/**
 * Tests for the WC_Regenerate_Images class.
 */
class WC_Regenerate_Images_Test extends WC_Unit_Test_Case {

	use ImageAttachmentTrait;

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->remove_added_uploads();

		parent::tearDown();
	}

	/**
	 * @testdox Resizing an image on the fly should not persist a filtered view of the metadata.
	 */
	public function test_resize_does_not_persist_filtered_metadata(): void {
		// Wide on purpose: maybe_resize_image() only regenerates on an aspect ratio mismatch.
		$attachment_id = $this->create_image_attachment( 900, 300, 'wc-regen-test.jpg' );

		$this->add_custom_attachment_metadata( $attachment_id, array( 'test_api_meta' => array( 'last_modified' => 1708332626 ) ) );

		// A co-installed plugin hides its own key from readers.
		$metadata_filter = function ( $data ) {
			if ( is_array( $data ) ) {
				unset( $data['test_api_meta'] );
			}

			return $data;
		};
		add_filter( 'wp_get_attachment_metadata', $metadata_filter );

		$this->assertArrayNotHasKey( 'test_api_meta', wp_get_attachment_metadata( $attachment_id ), 'The filter should hide the key from readers' );
		$this->assertArrayHasKey( 'test_api_meta', wp_get_attachment_metadata( $attachment_id, true ), 'The stored value should keep the key' );

		$thumbnail = $this->delete_attachment_size( $attachment_id, 'woocommerce_thumbnail' );

		$this->assertFileDoesNotExist( $thumbnail, 'The size should be missing before regeneration runs' );

		// Full size dimensions, which is what image_downsize() returns when the size is missing.
		$image    = array( wp_get_attachment_url( $attachment_id ), 900, 300, false );
		$returned = WC_Regenerate_Images::maybe_resize_image( $image, $attachment_id, 'woocommerce_thumbnail', false );

		// The registered size, not wc_get_image_size(), is what regeneration produces.
		$target = wp_get_registered_image_subsizes()['woocommerce_thumbnail'];

		$this->assertSame(
			array( (int) $target['width'], (int) $target['height'] ),
			array( $returned[1], $returned[2] ),
			'The resized image should be returned, not the original'
		);

		$this->assert_size_exists( $attachment_id, 'woocommerce_thumbnail' );

		$stored = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );

		$this->assertArrayHasKey(
			'test_api_meta',
			$stored,
			'Metadata hidden by a wp_get_attachment_metadata filter should survive on-the-fly regeneration'
		);
	}

	/**
	 * @testdox Rendering an image should keep its stored sizes when a site filter skips the WooCommerce size.
	 */
	public function test_render_keeps_sizes_when_site_filter_skips_requested_size(): void {
		$generation_runs = 0;

		// A site that keeps the size registered but never generates it.
		add_filter(
			'intermediate_image_sizes_advanced',
			function ( $sizes ) use ( &$generation_runs ) {
				++$generation_runs;
				unset( $sizes['woocommerce_thumbnail'] );

				return $sizes;
			},
			20
		);

		// The image is wide because maybe_resize_image() regenerates only when the aspect ratio does not match.
		$attachment_id = $this->create_image_attachment( 900, 300, 'wc-regen-skipped-size.jpg' );
		$stored_sizes  = wp_get_attachment_metadata( $attachment_id, true )['sizes'];

		$this->assertNotEmpty( $stored_sizes, 'The upload should generate the sizes the site allows' );
		$this->assertArrayNotHasKey( 'woocommerce_thumbnail', $stored_sizes, 'The site filter should stop the WooCommerce size at upload' );

		$runs_after_upload = $generation_runs;

		wp_get_attachment_image_src( $attachment_id, 'woocommerce_thumbnail' );

		$this->assertGreaterThan( $runs_after_upload, $generation_runs, 'Rendering should try to regenerate the missing size' );
		$this->assertSame(
			$stored_sizes,
			wp_get_attachment_metadata( $attachment_id, true )['sizes'],
			'A regeneration that creates no file should leave the stored sizes as they were'
		);
	}

	/**
	 * @testdox Rendering a large unscaled image should keep its sizes and record both the scaled file and the original.
	 */
	public function test_render_of_unscaled_large_image_keeps_file_keys_in_sync(): void {
		// A site that keeps the size registered but never generates it.
		add_filter(
			'intermediate_image_sizes_advanced',
			function ( $sizes ) {
				unset( $sizes['woocommerce_thumbnail'] );

				return $sizes;
			},
			20
		);

		// Store the image unscaled, like an upload from before WordPress 5.3 added the big image threshold.
		add_filter( 'big_image_size_threshold', '__return_false' );
		$attachment_id = $this->create_image_attachment( 3000, 2000, 'wc-regen-unscaled.jpg' );
		remove_filter( 'big_image_size_threshold', '__return_false' );

		$original_file = get_attached_file( $attachment_id );
		$stored_sizes  = wp_get_attachment_metadata( $attachment_id, true )['sizes'];

		// Render twice. The second render regenerates from the scaled file, which WordPress does not scale again.
		wp_get_attachment_image_src( $attachment_id, 'woocommerce_thumbnail' );
		wp_get_attachment_image_src( $attachment_id, 'woocommerce_thumbnail' );

		$metadata      = wp_get_attachment_metadata( $attachment_id, true );
		$attached_file = get_attached_file( $attachment_id );

		$this->assertNotSame( $original_file, $attached_file, 'WordPress should have scaled the attached file down' );
		$this->assertSame( basename( $attached_file ), basename( $metadata['file'] ), 'The stored file should be the attached file' );
		$this->assertSame( array( 2560, 1707 ), array( $metadata['width'], $metadata['height'] ), 'The stored dimensions should be those of the scaled file' );
		$this->assertSame( basename( $original_file ), $metadata['original_image'] ?? null, 'The stored record should point to the uploaded original' );
		$this->assertSame( $original_file, wp_get_original_image_path( $attachment_id ), 'The original image path should be the uploaded file' );

		foreach ( array_keys( $stored_sizes ) as $size ) {
			$this->assertArrayHasKey( $size, $metadata['sizes'], "The $size size should be kept" );
		}
	}
}
