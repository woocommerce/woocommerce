<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

/**
 * Class responsible for managing WooCommerce email editor post templates.
 */
class WCTransactionalEmailPostsManager {
	/**
	 * Singleton instance of the class.
	 *
	 * @var WCTransactionalEmailPostsManager|null
	 */
	private static $instance = null;

	/**
	 * Gets the singleton instance of the class.
	 *
	 * @return WCTransactionalEmailPostsManager Instance of the class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Retrieves the email post by its type.
	 *
	 * Type here refers to the email type, e.g. 'customer_new_account' from the WC_Email->id property.
	 *
	 * @param string $email_type The type of email to retrieve.
	 * @return \WP_Post|null The email post if found, null otherwise.
	 */
	public function get_email_post( $email_type ) {
		$post_id = $this->get_email_template_post_id( $email_type );

		if ( ! $post_id ) {
			return null;
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		return $post;
	}

	/**
	 * Checks if an email template exists for the given type.
	 *
	 * Type here refers to the email type, e.g. 'customer_new_account' from the WC_Email->id property.
	 *
	 * @param string $email_type The type of email to check.
	 * @return bool True if the template exists, false otherwise.
	 */
	public function template_exists( $email_type ) {
		return null !== $this->get_email_post( $email_type );
	}

	/**
	 * Saves the post ID for a specific email template type.
	 *
	 * @param string $email_type The type of email template e.g. 'customer_new_account' from the WC_Email->id property.
	 * @param int    $post_id    The post ID to save.
	 */
	public function save_email_template_post_id( $email_type, $post_id ) {
		$option_name = 'woocommerce_email_templates_' . $email_type . '_post_id';
		update_option( $option_name, $post_id );
	}

	/**
	 * Gets the post ID for a specific email template type.
	 *
	 * @param string $email_type The type of email template e.g. 'customer_new_account' from the WC_Email->id property.
	 * @return int|false The post ID if found, false otherwise.
	 */
	public function get_email_template_post_id( $email_type ) {
		$option_name = 'woocommerce_email_templates_' . $email_type . '_post_id';
		return get_option( $option_name );
	}

	/**
	 * Deletes the post ID for a specific email template type.
	 *
	 * @param string $email_type The type of email template e.g. 'customer_new_account' from the WC_Email->id property.
	 */
	public function delete_email_template( $email_type ) {
		$option_name = 'woocommerce_email_templates_' . $email_type . '_post_id';
		if ( ! get_option( $option_name ) ) {
			return;
		}
		delete_option( $option_name );
	}
}
