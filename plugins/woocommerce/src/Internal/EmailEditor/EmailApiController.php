<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Validator\Builder;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;
use WC_Email;

defined( 'ABSPATH' ) || exit;

/**
 * API Controller for managing WooCommerce email templates via extending the post type API.
 *
 * @internal
 */
class EmailApiController {
	/**
	 * A list of WooCommerce emails.
	 *
	 * @var \WC_Email[]
	 */
	private array $emails;

	/**
	 * The WooCommerce transactional email post manager.
	 *
	 * @var WCTransactionalEmailPostsManager|null
	 */
	private ?WCTransactionalEmailPostsManager $post_manager;

	/**
	 * Initialize the controller.
	 *
	 * @internal
	 */
	final public function init(): void {
		$this->emails       = WC()->mailer()->get_emails();
		$this->post_manager = WCTransactionalEmailPostsManager::get_instance();
	}

	/**
	 * Returns the data from wp_options table for the given post.
	 *
	 * @param array $post_data - Post data.
	 * @return array - The email data.
	 */
	public function get_email_data( $post_data ): array {
		$email_type  = $this->post_manager->get_email_type_from_post_id( $post_data['id'] );
		$post_option = get_option( "woocommerce_{$email_type}_settings" );
		$email       = $this->get_email_by_type( $email_type );

		return array(
			'subject'         => $post_option['subject'] ?? null,
			'subject_full'    => $post_option['subject_full'] ?? null, // For customer_refunded_order email type because it has two different subjects.
			'subject_partial' => $post_option['subject_partial'] ?? null,
			'preheader'       => $post_option['preheader'] ?? null,
			'default_subject' => $email->get_default_subject(),
			'email_type'      => $email_type,
		);
	}

	/**
	 * Update WooCommerce specific option data by post name.
	 *
	 * @param array    $data - Data that are stored in the wp_options table.
	 * @param \WP_Post $post - WP_Post object.
	 */
	public function save_email_data( array $data, \WP_Post $post ): void {
		if ( ! array_key_exists( 'subject', $data ) && ! array_key_exists( 'preheader', $data ) ) {
			return;
		}
		$email_type  = $this->post_manager->get_email_type_from_post_id( $post->ID );
		$option_name = "woocommerce_{$email_type}_settings";
		$post_option = get_option( $option_name );

		// Handle customer_refunded_order email type because it has two different subjects.
		if ( 'customer_refunded_order' === $email_type ) {
			if ( array_key_exists( 'subject_full', $data ) ) {
				$post_option['subject_full'] = $data['subject_full'];
			}
			if ( array_key_exists( 'subject_partial', $data ) ) {
				$post_option['subject_partial'] = $data['subject_partial'];
			}
		} elseif ( array_key_exists( 'subject', $data ) ) {
			$post_option['subject'] = $data['subject'];
		}

		if ( array_key_exists( 'preheader', $data ) ) {
			$post_option['preheader'] = $data['preheader'];
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
				'subject_full'    => Builder::string()->nullable(), // For customer_refunded_order email type because it has two different subjects.
				'subject_partial' => Builder::string()->nullable(),
				'preheader'       => Builder::string()->nullable(),
				'default_subject' => Builder::string(),
				'email_type'      => Builder::string(),
			)
		)->to_array();
	}

	/**
	 * Get the email object by ID.
	 *
	 * @param string $id - The email ID.
	 * @return \WC_Email|null - The email object or null if not found.
	 */
	private function get_email_by_type( string $id ): ?WC_Email {
		foreach ( $this->emails as $email ) {
			if ( $email->id === $id ) {
				return $email;
			}
		}
		return null;
	}
}
