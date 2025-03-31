<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Validator\Builder;

defined( 'ABSPATH' ) || exit;

/**
 * API Controller for managing WooCommerce email templates via extending the post type API.
 *
 * @internal
 */
class EmailApiController {
	/**
	 * Returns the data from wp_options table for the given post.
	 *
	 * @param array $post_data - Post data.
	 * @return array - The email data.
	 */
	public function get_email_data( $post_data ): array {
		$email_type  = get_post_meta( $post_data['id'], Integration::WC_EMAIL_TYPE_ID_POST_META_KEY, true );
		$post_option = get_option( "woocommerce_{$email_type}_settings" );

		return array(
			'subject' => $post_option['subject'] ?? null,
			'heading' => $post_option['heading'] ?? null,
		);
	}

	/**
	 * Update WooCommerce specific option data by post name.
	 *
	 * @param array    $data - Data that are stored in the wp_options table.
	 * @param \WP_Post $post - WP_Post object.
	 */
	public function save_email_data( array $data, \WP_Post $post ): void {
		if ( ! array_key_exists( 'subject', $data ) && ! array_key_exists( 'heading', $data ) ) {
			return;
		}
		$email_type  = get_post_meta( $post->ID, Integration::WC_EMAIL_TYPE_ID_POST_META_KEY, true );
		$option_name = "woocommerce_{$email_type}_settings";
		$post_option = get_option( $option_name );
		if ( array_key_exists( 'subject', $data ) ) {
			$post_option['subject'] = $data['subject'];
		}
		if ( array_key_exists( 'heading', $data ) ) {
			$post_option['heading'] = $data['heading'];
		}
		update_option( $option_name, $post_option );
	}

	/**
	 * Get the schema for the WooCommerce email post data.
	 *
	 * @return array
	 */
	public function get_email_data_schema(): array {
		return Builder::object(
			array(
				'subject'         => Builder::string()->nullable(),
				'preheader'       => Builder::string()->nullable(),
			)
		)->to_array();
	}
}
