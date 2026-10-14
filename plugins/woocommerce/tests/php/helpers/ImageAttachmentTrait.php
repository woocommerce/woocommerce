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

	/**
	 * Delete a generated size, both the file and its metadata entry.
	 *
	 * @param int    $attachment_id Attachment to strip the size from.
	 * @param string $size          Size name.
	 * @return string Path of the deleted file.
	 */
	private function delete_attachment_size( int $attachment_id, string $size ): string {
		$metadata = wp_get_attachment_metadata( $attachment_id, true );
		$path     = $this->get_attachment_size_path( $attachment_id, $metadata, $size );

		wp_delete_file( $path );

		unset( $metadata['sizes'][ $size ] );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return $path;
	}

	/**
	 * Assert that a size exists as a real image file of the dimensions its metadata records.
	 *
	 * @param int    $attachment_id Attachment to check.
	 * @param string $size          Size name.
	 */
	private function assert_size_exists( int $attachment_id, string $size ): void {
		$metadata = wp_get_attachment_metadata( $attachment_id, true );

		$this->assertArrayHasKey( $size, $metadata['sizes'], "The $size size should be recorded in the metadata" );

		$path = $this->get_attachment_size_path( $attachment_id, $metadata, $size );

		$this->assertFileExists( $path, "The $size file should be on disk" );

		$dimensions = getimagesize( $path );

		$this->assertSame( (int) $metadata['sizes'][ $size ]['width'], $dimensions[0], "The $size file should be as wide as its metadata records" );
		$this->assertSame( (int) $metadata['sizes'][ $size ]['height'], $dimensions[1], "The $size file should be as tall as its metadata records" );
	}

	/**
	 * Absolute path of a generated size.
	 *
	 * @param int    $attachment_id Attachment the size belongs to.
	 * @param array  $metadata      Stored attachment metadata.
	 * @param string $size          Size name.
	 * @return string
	 */
	private function get_attachment_size_path( int $attachment_id, array $metadata, string $size ): string {
		return trailingslashit( dirname( get_attached_file( $attachment_id ) ) ) . $metadata['sizes'][ $size ]['file'];
	}
}
