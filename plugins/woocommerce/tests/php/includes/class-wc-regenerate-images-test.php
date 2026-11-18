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
	 * @testdox Rendering an image that WordPress would now scale or rotate should add the size without replacing the attached file ($_dataName).
	 * @dataProvider provide_images_wordpress_would_replace
	 *
	 * @param string $filter_at_upload The filter that is off while the image is uploaded.
	 * @param string $file             Image file to upload.
	 */
	public function test_render_adds_size_without_replacing_attached_file( string $filter_at_upload, string $file ): void {
		add_filter( $filter_at_upload, '__return_false' );
		$attachment_id = $this->create_attachment_from_file( $file );
		remove_filter( $filter_at_upload, '__return_false' );

		$this->delete_attachment_size( $attachment_id, 'woocommerce_thumbnail' );

		$attached_file = get_attached_file( $attachment_id );
		$stored        = wp_get_attachment_metadata( $attachment_id, true );
		$saved_sizes   = array();
		$contexts      = array();

		add_filter(
			'wp_update_attachment_metadata',
			function ( $data ) use ( &$saved_sizes ) {
				$saved_sizes[] = array_keys( $data['sizes'] ?? array() );

				return $data;
			}
		);
		add_filter(
			'wp_generate_attachment_metadata',
			function ( $data, $id, $context ) use ( &$contexts ) {
				$contexts[] = $context;

				return $data;
			},
			10,
			3
		);

		wp_get_attachment_image_src( $attachment_id, 'woocommerce_thumbnail' );

		$metadata = wp_get_attachment_metadata( $attachment_id, true );

		$this->assertSame( $attached_file, get_attached_file( $attachment_id ), 'The attached file should not change' );
		$this->assert_size_exists( $attachment_id, 'woocommerce_thumbnail' );

		unset( $metadata['sizes']['woocommerce_thumbnail'] );
		$this->assertSame( $stored, $metadata, 'Only the new size should be added to the stored record' );

		foreach ( $saved_sizes as $sizes ) {
			$this->assertEmpty( array_diff( array_keys( $stored['sizes'] ), $sizes ), 'No save should drop a stored size' );
		}

		$this->assertSame( array( 'update' ), $contexts, 'Plugins should see the new size as an update to an existing image' );
	}

	/**
	 * Images that wp_generate_attachment_metadata() would give a new main file.
	 *
	 * @return array
	 */
	public function provide_images_wordpress_would_replace(): array {
		return array(
			'large image stored before the big image threshold' => array( 'big_image_size_threshold', DIR_TESTDATA . '/images/test-image-large.jpg' ),
			'image stored without EXIF rotation' => array( 'wp_image_maybe_exif_rotate', DIR_TESTDATA . '/images/test-image-rotated-90ccw.jpg' ),
		);
	}
}
