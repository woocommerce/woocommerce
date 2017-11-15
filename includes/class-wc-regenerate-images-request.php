<?php
/**
 * All functionality to regenerate images in the background when settings change.
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
 * Class that extends WP_Background_Process to process image regeneration in the background
 */
class WC_Regenerate_Images_Request extends WP_Background_Process {

	/**
	 * Action to hook onto
	 *
	 * @var string
	 */
	protected $action = 'woocommerce_regenerate_images';

	/**
	 * Fires when the job should start
	 *
	 * @return void
	 */
	public function dispatch() {
		$log = wc_get_logger();
		$log->info( __( 'Starting product image regeneration job.', 'woocommerce' ) );
		parent::dispatch();
	}

	/**
	 * Code to execute for each item in the queue
	 *
	 * @param mixed $item Queue item to iterate over.
	 * @return bool
	 */
	protected function task( $item ) {
		if ( ! is_array( $item ) && ! isset( $item['attachment_id'] ) ) {
			return false;
		}

		if ( ! function_exists( 'wp_crop_image' ) ) {
			include( ABSPATH . 'wp-admin/includes/image.php' );
		}

		$attachment_id = absint( $item['attachment_id'] );

		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type || 'image/' !== substr( $attachment->post_mime_type, 0, 6 ) ) {
			return false;
		}
		$log = wc_get_logger();
		// translators: %s: ID of the attachment.
		$log->info( sprintf( __( 'Regenerating images for attachment ID: %s', 'woocommerce' ), absint( $attachment->ID ) ) );

		$fullsizepath = get_attached_file( $attachment->ID );

		// Check if the file exists, if not just remove item from queue.
		if ( false === $fullsizepath || ! file_exists( $fullsizepath ) ) {
			return false;
		}

		// This function will generate the new image sizes.
		$metadata = wp_generate_attachment_metadata( $attachment->ID, $fullsizepath );

		// If something went wrong lets just remove the item from the queue.
		if ( is_wp_error( $metadata ) || empty( $metadata ) ) {
			return false;
		}

		// Update the meta data with the new size values.
		wp_update_attachment_metadata( $attachment->ID, $metadata );

		// We made it till the end, now lets remove the item from the queue.
		return false;
	}

	/**
	 * This runs once the job has completed all items on the queue.
	 *
	 * @return void
	 */
	protected function complete() {
		parent::complete();
		$log = wc_get_logger();
		$log->info( __( 'Completed product image regeneration job.', 'woocommerce' ) );
	}


}
