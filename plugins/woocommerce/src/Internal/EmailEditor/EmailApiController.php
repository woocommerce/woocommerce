<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Validator\Builder;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;
use WC_Email;
use WP_Error;

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
		$email_type = $this->post_manager->get_email_type_from_post_id( $post_data['id'] );
		$email      = $this->get_email_by_type( $email_type ?? '' );

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
		$enabled     = $email->get_option( 'enabled' );
		return array(
			'enabled'         => is_null( $enabled ) ? $email->is_enabled() : 'yes' === $enabled,
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
	 * @return \WP_Error|null Returns WP_Error if email validation fails, null otherwise.
	 */
	public function save_email_data( array $data, \WP_Post $post ): ?\WP_Error {
		$error = $this->validate_email_data( $data );
		if ( is_wp_error( $error ) ) {
			return new \WP_Error( 'invalid_email_data', implode( ' ', $error->get_error_messages() ), array( 'status' => 400 ) );
		}

		if ( ! array_key_exists( 'subject', $data ) && ! array_key_exists( 'preheader', $data ) ) {
			return null;
		}
		$email_type = $this->post_manager->get_email_type_from_post_id( $post->ID );
		$email      = $this->get_email_by_type( $email_type ?? '' );

		if ( ! $email ) {
			return null; // not saving of type wc_email. Allow process to continue.
		}

		// Handle customer_refunded_order email type because it has two different subjects.
		if ( 'customer_refunded_order' === $email_type ) {
			if ( array_key_exists( 'subject_full', $data ) ) {
				$email->update_option( 'subject_full', $data['subject_full'] );
			}
			if ( array_key_exists( 'subject_partial', $data ) ) {
				$email->update_option( 'subject_partial', $data['subject_partial'] );
			}
		} elseif ( array_key_exists( 'subject', $data ) ) {
			$email->update_option( 'subject', $data['subject'] );
		}

		if ( array_key_exists( 'preheader', $data ) ) {
			$email->update_option( 'preheader', $data['preheader'] );
		}

		if ( array_key_exists( 'enabled', $data ) ) {
			$email->update_option( 'enabled', $data['enabled'] ? 'yes' : 'no' );
		}
		if ( array_key_exists( 'recipient', $data ) ) {
			$email->update_option( 'recipient', $data['recipient'] );
		}
		if ( array_key_exists( 'cc', $data ) ) {
			$email->update_option( 'cc', $data['cc'] );
		}
		if ( array_key_exists( 'bcc', $data ) ) {
			$email->update_option( 'bcc', $data['bcc'] );
		}

		return null;
	}

	/**
	 * Validate the email data.
	 *
	 * @param array $data - The email data.
	 * @return \WP_Error|null Returns WP_Error if email validation fails, null otherwise.
	 */
	private function validate_email_data( array $data ) {
		$error = new \WP_Error();

		// Validate 'recipient' email(s) field.
		$invalid_recipients = $this->filter_invalid_email_addresses( $data['recipient'] ?? '' );
		if ( ! empty( $invalid_recipients ) ) {
			$error_message = sprintf(
				// translators: %s will be replaced by comma-separated email addresses. For example, "invalidemail1@example.com,invalidemail2@example.com".
				__( 'One or more Recipient email addresses are invalid: “%s”. Please enter valid email addresses separated by commas.', 'woocommerce' ),
				implode( ',', $invalid_recipients )
			);
			$error->add( 'invalid_recipient_email_address', $error_message );
		}

		// Validate 'cc' email(s) field.
		$invalid_cc = $this->filter_invalid_email_addresses( $data['cc'] ?? '' );
		if ( ! empty( $invalid_cc ) ) {
			$error_message = sprintf(
				// translators: %s will be replaced by comma-separated email addresses. For example, "invalidemail1@example.com,invalidemail2@example.com".
				__( 'One or more CC email addresses are invalid: “%s”. Please enter valid email addresses separated by commas.', 'woocommerce' ),
				implode( ',', $invalid_cc )
			);
			$error->add( 'invalid_cc_email_address', $error_message );
		}

		// Validate 'bcc' email(s) field.
		$invalid_bcc = $this->filter_invalid_email_addresses( $data['bcc'] ?? '' );
		if ( ! empty( $invalid_bcc ) ) {
			$error_message = sprintf(
				// translators: %s will be replaced by comma-separated email addresses. For example, "invalidemail1@example.com,invalidemail2@example.com".
				__( 'One or more BCC email addresses are invalid: “%s”. Please enter valid email addresses separated by commas.', 'woocommerce' ),
				implode( ',', $invalid_bcc )
			);
			$error->add( 'invalid_bcc_email_address', $error_message );
		}

		if ( $error->has_errors() ) {
			return $error;
		}

		return null;
	}

	/**
	 * Filter in invalid email addresses from a comma-separated string.
	 *
	 * @param string $comma_separated_email_addresses - A comma-separated string of email addresses.
	 * @return array - An array of invalid email addresses.
	 */
	private function filter_invalid_email_addresses( $comma_separated_email_addresses ) {
		$invalid_email_addresses = array();

		if ( empty( trim( $comma_separated_email_addresses ) ) ) {
			return $invalid_email_addresses;
		}

		foreach ( explode( ',', $comma_separated_email_addresses ) as $email_address ) {
			if ( ! filter_var( trim( $email_address ), FILTER_VALIDATE_EMAIL ) ) {
				$invalid_email_addresses[] = trim( $email_address );
			}
		}

		return $invalid_email_addresses;
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
