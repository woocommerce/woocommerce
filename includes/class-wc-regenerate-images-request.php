<?php
/**
 * All functionality to regenerate images in the background when settings change.
 *
 * @package WooCommerce/Classes
 * @version 3.3.0
 * @since   3.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once dirname( __FILE__ ) . '/libraries/wp-async-request.php';
}

if ( ! class_exists( 'WP_Background_Process', false ) ) {
	include_once dirname( __FILE__ ) . '/libraries/wp-background-process.php';
}

/**
 * Class that extends WP_Background_Process to process image regeneration in the background
 */
class WC_Regenerate_Images_Request extends WP_Background_Process {

	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		// Uses unique prefix per blog so each blog has separate queue.
		$this->prefix = 'wp_' . get_current_blog_id();
		$this->action = 'wc_regenerate_images';

		parent::__construct();
	}

	/**
	 * Fires when the job should start
	 *
	 * @return void
	 */
	public function dispatch() {
		$log = wc_get_logger();
		$log->info( __( 'Starting product image regeneration job.', 'woocommerce' ),
			array(
				'source' => 'wc-image-regeneration',
			)
		);
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

		$attachment_id      = absint( $item['attachment_id'] );
		$last_attachment_id = absint( get_option( 'woocommerce_last_image_regen_id', 0 ) );

		// If the current task matches the last task there may have been an error which prevented the task from completing; skip.
		if ( $last_attachment_id === $attachment_id ) {
			return false;
		}

		update_option( 'woocommerce_last_image_regen_id', $item['attachment_id'] );

		if ( ! function_exists( 'wp_crop_image' ) ) {
			include ABSPATH . 'wp-admin/includes/image.php';
		}

		$attachment = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type || 'image/' !== substr( $attachment->post_mime_type, 0, 6 ) ) {
			return false;
		}

		$log = wc_get_logger();

		// translators: %s: ID of the attachment.
		$log->info( sprintf( __( 'Regenerating images for attachment ID: %s', 'woocommerce' ), absint( $attachment->ID ) ),
			array(
				'source' => 'wc-image-regeneration',
			)
		);

		$fullsizepath = get_attached_file( $attachment->ID );

		// Check if the file exists, if not just remove item from queue.
		if ( false === $fullsizepath || ! file_exists( $fullsizepath ) ) {
			return false;
		}

		// We only want to regen WC images.
		add_filter( 'intermediate_image_sizes', array( $this, 'adjust_intermediate_image_sizes' ) );

		// This function will generate the new image sizes.
		$metadata = wp_generate_attachment_metadata( $attachment->ID, $fullsizepath );

		// If something went wrong lets just remove the item from the queue.
		if ( is_wp_error( $metadata ) || empty( $metadata ) ) {
			return false;
		}

		// Update the meta data with the new size values.
		wp_update_attachment_metadata( $attachment->ID, $metadata );

		// Remove custom filter.
		remove_filter( 'intermediate_image_sizes', array( $this, 'adjust_intermediate_image_sizes' ) );

		// We made it till the end, now lets remove the item from the queue.
		return false;
	}

	/**
	 * Returns the sizes we want to regenerate.
	 *
	 * @param array $sizes Sizes to generate.
	 * @return array
	 */
	public function adjust_intermediate_image_sizes( $sizes ) {
		return apply_filters( 'woocommerce_regenerate_images_intermediate_image_sizes', array( 'woocommerce_thumbnail', 'woocommerce_thumbnail_2x', 'woocommerce_single' ) );
	}

	/**
	 * This runs once the job has completed all items on the queue.
	 *
	 * @return void
	 */
	protected function complete() {
		parent::complete();
		$log = wc_get_logger();
		$log->info( __( 'Completed product image regeneration job.', 'woocommerce' ),
			array(
				'source' => 'wc-image-regeneration',
			)
		);
	}
}
