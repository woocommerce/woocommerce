<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Validator\Builder;
use WC_Email;

defined( 'ABSPATH' ) || exit;

/**
 * API Controller for managing WooCommerce email templates via extending the post type API.
 *
 * @internal
 */
class EmailApiController {
	/** @var \WC_Email[] */
	private array $emails;

	/**
	 * Initialize the controller.
	 *
	 * @internal
	 */
	final public function init(): void {
		$this->emails = WC()->mailer()->get_emails();
	}

	/**
	 * Returns the data from wp_options table for the given post.
	 *
	 * @param array $post_data - Post data.
	 * @return array - The email data.
	 */
	public function get_email_data( $post_data ): array {
		$email_type  = get_post_meta( $post_data['id'], Integration::WC_EMAIL_TYPE_ID_POST_META_KEY, true );
		$post_option = get_option( "woocommerce_{$email_type}_settings" );

		$email = $this->get_email_by_type( $email_type );

		return array(
			'subject' => $post_option['subject'] ?? null,
			'heading' => $post_option['heading'] ?? null,
			'default_subject' => $email->get_default_subject(),
			'default_heading' => $email->get_default_heading(),
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
				'default_subject' => Builder::string(),
				'default_heading' => Builder::string(),
			)
		)->to_array();
	}

	/**
	 * Get the email object by ID.
	 *
	 * @param string $id - The email ID.
	 * @return \WC_Email|null - The email object or null if not found.
	 */
	private function get_email_by_type(string $id ): ?WC_Email {
		foreach ( $this->emails as $email ) {
			if ( $email->id === $id ) {
				return $email;
			}
		}
		return null;
	}
}
