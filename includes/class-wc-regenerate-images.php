<?php
/**
 * Regenerate Images Functionality
 *
 * All functionality pertaining to regenerating product images in realtime.
 *
 * @category Images
 * @package WooCommerce/Classes
 * @author Automattic
 * @version 3.3.0
 * @since 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Regenerate Images Class
 */
class WC_Regenerate_Images {

	/**
	 * Background process to regenerate all images
	 *
	 * @var WC_Regenerate_Images_Request
	 */
	protected static $background_process;

	/**
	 * Init function
	 */
	public static function init() {
		include_once( WC_ABSPATH . 'includes/class-wc-regenerate-images-request.php' );
		self::$background_process = new WC_Regenerate_Images_Request();

		// Action to handle on-the-fly image resizing.
		add_action( 'wp_get_attachment_image_src', array( __CLASS__, 'maybe_resize_image' ), 10, 4 );
		// Action to handle image generation when settings change.
		add_action( 'update_option', array( __CLASS__, 'maybe_regenerate_images_background' ), 10, 3 );
	}

	/**
	 * Check if we should maybe generate a new image size if not already there.
	 *
	 * @param array        $image Properties of the image.
	 * @param int          $attachment_id Attachment ID.
	 * @param string|array $size Image size.
	 * @param bool         $icon If icon or not.
	 * @return array
	 */
	public static function maybe_resize_image( $image, $attachment_id, $size, $icon ) {
		if ( ! apply_filters( 'woocommerce_resize_images', true ) ) {
			return $image;
		}

		if ( is_admin() ) {
			return $image;
		}

		$imagemeta = wp_get_attachment_metadata( $attachment_id );

		if ( false === $imagemeta || empty( $imagemeta ) ) {
			return $image;
		}

		$size_settings = wc_get_image_size( $size );

		if ( isset( $imagemeta['sizes'][ $size ] ) ) {
			if ( $imagemeta['sizes'][ $size ]['width'] !== $size_settings['width'] || $imagemeta['sizes'][ $size ]['height'] !== $size_settings['height'] ) {
				$image = self::resize_and_return_image( $attachment_id, $image, $size, $icon );
			}
		} else {
			$image = self::resize_and_return_image( $attachment_id, $image, $size, $icon );
		}
		return $image;
	}

	/**
	 * Regenerate the image according to the required size
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param array  $image Original Image.
	 * @param string $size Size to return for new URL.
	 * @param bool   $icon If icon or not.
	 * @return string
	 */
	private static function resize_and_return_image( $attachment_id, $image, $size, $icon ) {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type || 'image/' !== substr( $attachment->post_mime_type, 0, 6 ) ) {
			return $image;
		}

		if ( ! function_exists( 'wp_crop_image' ) ) {
			include( ABSPATH . 'wp-admin/includes/image.php' );
		}

		$wp_uploads     = wp_upload_dir( null, false );
		$wp_uploads_dir = $wp_uploads['basedir'];
		$wp_uploads_url = $wp_uploads['baseurl'];

		$original_image_file_path   = get_attached_file( $attachment->ID );

		$rel_path = str_replace( $wp_uploads_dir, '', $original_image_file_path );

		if ( ! file_exists( $original_image_file_path ) || ! getimagesize( $original_image_file_path ) ) {
			return $image;
		}

		$info = pathinfo( $original_image_file_path );
		$ext = $info['extension'];
		list( $orig_w, $orig_h ) = getimagesize( $original_image_file_path );
		// Get image size after cropping.
		$image_size = wc_get_image_size( $size );
		$dimensions = image_resize_dimensions( $orig_w, $orig_h, $image_size['width'], $image_size['height'], $image_size['crop'] );
		if ( ! $dimensions || ! is_array( $dimensions ) ) {
			return $image;
		}

		$dst_w        = $dimensions[4];
		$dst_h        = $dimensions[5];
		$suffix       = "{$dst_w}x{$dst_h}";
		$dst_rel_path = str_replace( '.' . $ext, '', $original_image_file_path );
		$destfilename = "{$dst_rel_path}-{$suffix}.{$ext}";

		// If the file is already there perhaps just load it.
		if ( file_exists( $destfilename ) ) {
			return array(
				0 => str_replace( $wp_uploads_dir, $wp_uploads_url, $destfilename ),
				1 => $image_size['width'],
				2 => $image_size['height'],
			);
		}

		// Lets resize the image if it does not exist yet.
		$editor = wp_get_image_editor( $original_image_file_path );
		if ( is_wp_error( $editor ) || is_wp_error( $editor->resize( $image_size['width'], $image_size['height'], $image_size['crop'] ) ) ) {
			return $image;
		}
		$resized_file = $editor->save();
		if ( ! is_wp_error( $resized_file ) ) {
			$img_url = str_replace( $wp_uploads_dir, $wp_uploads_url, $resized_file['path'] );
			return array(
				0 => $img_url,
				1 => $image_size['width'],
				2 => $image_size['height'],
			);
		}

		// Lets just add this here as a fallback.
		return $image;
	}

	/**
	 * Check if we should regenerate the product images using a job queue in the background.
	 *
	 * @param string $option Option name.
	 * @param mixed  $old_value Old option value.
	 * @param mixed  $new_value New option value.
	 */
	public static function maybe_regenerate_images_background( $option, $old_value, $new_value ) {
		global $wpdb;

		if ( ! apply_filters( 'woocommerce_background_image_regeneration', true ) ) {
			return;
		}
		if ( ! in_array( $option, array( 'woocommerce_thumbnail_cropping', 'woocommerce_thumbnail_image_width', 'woocommerce_single_image_width' ), true ) ) {
			return;
		}

		if ( $new_value === $old_value ) {
			return;
		}

		// If we made it till here, it means that image settings have changed.
		// First lets cancel existing running queue to avoid running it more than once.
		self::$background_process->cancel_process();

		// Now lets find all product image attachments IDs and pop them onto the queue.
		$images = $wpdb->get_results( // @codingStandardsIgnoreLine
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE 'image/%'
			ORDER BY ID DESC"
		);
		foreach ( $images as $image ) {
			self::$background_process->push_to_queue( array(
				'attachment_id' => $image->ID,
			) );
		}

		// Lets dispatch the queue to start processing.
		self::$background_process->save()->dispatch();
	}
}
WC_Regenerate_Images::init();
