<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Helpers;

/**
 * Trait ImageAttachmentTrait.
 *
 * Image attachment fixtures backed by real files on disk, so tests can exercise code
 * that reads the image itself rather than just the attachment post.
 *
 * The files land in the uploads directory, so call remove_added_uploads() from tearDown().
 */
trait ImageAttachmentTrait {

	/**
	 * Create an attachment backed by a real image file.
	 *
	 * @param int    $width    Width in pixels.
	 * @param int    $height   Height in pixels.
	 * @param string $filename File name to write into the uploads directory.
	 * @return int Attachment ID.
	 */
	private function create_image_attachment( int $width, int $height, string $filename ): int {
		$uploads = wp_upload_dir();
		$file    = trailingslashit( $uploads['path'] ) . $filename;

		wp_mkdir_p( $uploads['path'] );

		$image = imagecreatetruecolor( $width, $height );
		imagefilledrectangle( $image, 0, 0, $width, $height, imagecolorallocate( $image, 120, 60, 30 ) );
		imagejpeg( $image, $file, 90 );
		imagedestroy( $image );

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'WC image attachment test',
				'post_status'    => 'inherit',
			),
			$file
		);

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file ) );

		return $attachment_id;
	}

	/**
	 * Add top level metadata keys that WordPress does not own.
	 *
	 * @param int   $attachment_id Attachment to add the keys to.
	 * @param array $custom_keys   Metadata keyed by name.
	 */
	private function add_custom_attachment_metadata( int $attachment_id, array $custom_keys ): void {
		$metadata = wp_get_attachment_metadata( $attachment_id, true );

		wp_update_attachment_metadata( $attachment_id, array_merge( $metadata, $custom_keys ) );
	}
}
