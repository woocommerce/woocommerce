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
		$email       = $this->get_email_by_type( $email_type ?? '' );

		// When the email type is not found, it means that the email type is not supported.
		if ( ! $email ) {
			return array(
				'subject'         => null,
				'subject_full'    => null,
				'subject_partial' => null,
				'preheader'       => null,
				'default_subject' => null,
				'email_type'      => null,
				'recipient'       => null,
				'cc'              => null,
				'bcc'             => null,
			);
		}

		$form_fields = $email->get_form_fields();
		return array(
			'enabled'         => $email->is_enabled(),
			'is_manual'       => $email->is_manual(),
			'subject'         => $email->get_option( 'subject' ),
			'subject_full'    => $email->get_option( 'subject_full' ), // For customer_refunded_order email type because it has two different subjects.
			'subject_partial' => $email->get_option( 'subject_partial' ),
			'preheader'       => $email->get_option( 'preheader' ),
			'default_subject' => $email->get_default_subject(),
			'email_type'      => $email_type,
			// Recipient is possible to set only for the specific type of emails. When the field `recipient` is set in the form fields, it means that the email type has a recipient field.
			'recipient'       => array_key_exists( 'recipient', $form_fields ) ? $email->get_option( 'recipient', get_option( 'admin_email' ) ) : null,
			'cc'              => $email->get_option( 'cc' ),
			'bcc'             => $email->get_option( 'bcc' ),
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

		if ( array_key_exists( 'enabled', $data ) ) {
			$post_option['enabled'] = $data['enabled'] ? 'yes' : 'no';
		}
		if ( array_key_exists( 'recipient', $data ) ) {
			$post_option['recipient'] = $data['recipient'];
		}
		if ( array_key_exists( 'cc', $data ) ) {
			$post_option['cc'] = $data['cc'];
		}
		if ( array_key_exists( 'bcc', $data ) ) {
			$post_option['bcc'] = $data['bcc'];
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
				'default_subject' => Builder::string()->nullable(),
				'email_type'      => Builder::string()->nullable(),
				'recipient'       => Builder::string()->nullable(),
				'cc'              => Builder::string()->nullable(),
				'bcc'             => Builder::string()->nullable(),
			)
		)->to_array();
	}

	/**
	 * Get the email object by ID.
	 *
	 * @param string $id - The email ID.
	 * @return \WC_Email|null - The email object or null if not found.
	 */
	private function get_email_by_type( ?string $id ): ?WC_Email {
		foreach ( $this->emails as $email ) {
			if ( $email->id === $id ) {
				return $email;
			}
		}
		return null;
	}
}
