<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Tests\Helpers\ImageAttachmentTrait;

/**
 * Tests for the WC_Regenerate_Images class.
 */
class WC_Regenerate_Images_Test extends WC_Unit_Test_Case {

	use ImageAttachmentTrait;

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
		$this->remove_added_uploads();

		if ( $this->metadata_filter ) {
			remove_filter( 'wp_get_attachment_metadata', $this->metadata_filter );
			$this->metadata_filter = null;
		}

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
		$this->metadata_filter = function ( $data ) {
			if ( is_array( $data ) ) {
				unset( $data['test_api_meta'] );
			}

			return $data;
		};
		add_filter( 'wp_get_attachment_metadata', $this->metadata_filter );

		// Full size dimensions, which is what image_downsize() returns when the size is missing.
		$image = array( wp_get_attachment_url( $attachment_id ), 900, 300, false );
		WC_Regenerate_Images::maybe_resize_image( $image, $attachment_id, 'woocommerce_thumbnail', false );

		$stored = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );

		$this->assertArrayHasKey(
			'test_api_meta',
			$stored,
			'Metadata hidden by a wp_get_attachment_metadata filter should survive on-the-fly regeneration'
		);
	}
}
