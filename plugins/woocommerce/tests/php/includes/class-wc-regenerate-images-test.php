<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Regenerate_Images class.
 */
class WC_Regenerate_Images_Test extends WC_Unit_Test_Case {

	/**
	 * Attachment created by a test, removed on teardown.
	 *
	 * @var int
	 */
	private $attachment_id = 0;

	/**
	 * Metadata filter callback added by a test, removed on teardown.
	 *
	 * @var callable|null
	 */
	private $metadata_filter = null;

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( $this->attachment_id ) {
			wp_delete_attachment( $this->attachment_id, true );
			$this->attachment_id = 0;
		}

		if ( $this->metadata_filter ) {
			remove_filter( 'wp_get_attachment_metadata', $this->metadata_filter );
			$this->metadata_filter = null;
		}

		parent::tearDown();
	}

	/**
	 * Create an attachment backed by a real image file.
	 *
	 * Wide on purpose: maybe_resize_image() only regenerates on an aspect ratio mismatch.
	 *
	 * @return int Attachment ID.
	 */
	private function create_attachment(): int {
		$uploads = wp_upload_dir();
		$file    = trailingslashit( $uploads['path'] ) . 'wc-regen-test.jpg';

		wp_mkdir_p( $uploads['path'] );

		$image = imagecreatetruecolor( 900, 300 );
		imagefilledrectangle( $image, 0, 0, 900, 300, imagecolorallocate( $image, 120, 60, 30 ) );
		imagejpeg( $image, $file, 90 );
		imagedestroy( $image );

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'WC regen test',
				'post_status'    => 'inherit',
			),
			$file
		);

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file ) );

		return $attachment_id;
	}

	/**
	 * @testdox Resizing an image on the fly should not persist a filtered view of the metadata.
	 */
	public function test_resize_does_not_persist_filtered_metadata(): void {
		$this->attachment_id = $this->create_attachment();

		$metadata                  = wp_get_attachment_metadata( $this->attachment_id, true );
		$metadata['test_api_meta'] = array( 'last_modified' => 1708332626 );
		wp_update_attachment_metadata( $this->attachment_id, $metadata );

		// A co-installed plugin hides its own key from readers.
		$this->metadata_filter = function ( $data ) {
			if ( is_array( $data ) ) {
				unset( $data['test_api_meta'] );
			}

			return $data;
		};
		add_filter( 'wp_get_attachment_metadata', $this->metadata_filter );

		// Full size dimensions, which is what image_downsize() returns when the size is missing.
		$image = array( wp_get_attachment_url( $this->attachment_id ), 900, 300, false );
		WC_Regenerate_Images::maybe_resize_image( $image, $this->attachment_id, 'woocommerce_thumbnail', false );

		$stored = get_post_meta( $this->attachment_id, '_wp_attachment_metadata', true );

		$this->assertArrayHasKey(
			'test_api_meta',
			$stored,
			'Metadata hidden by a wp_get_attachment_metadata filter should survive on-the-fly regeneration'
		);
	}
}
