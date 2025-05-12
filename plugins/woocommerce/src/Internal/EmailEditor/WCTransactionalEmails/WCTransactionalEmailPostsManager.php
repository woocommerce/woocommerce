<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

/**
 * Class responsible for managing WooCommerce email editor post templates.
 */
class WCTransactionalEmailPostsManager {
	const WC_OPTION_NAME = 'woocommerce_email_templates_%_post_id';

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
	 * Retrieves the WooCommerce email type from the options table when post ID is provided.
	 *
	 * @param int|string $post_id The post ID.
	 * @return string|null The WooCommerce email type if found, null otherwise.
	 */
	public function get_email_type_from_post_id( $post_id ) {
		// Early return if post_id is invalid.
		if ( empty( $post_id ) ) {
			return null;
		}

		global $wpdb;

		$option_name = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value = %s LIMIT 1",
				self::WC_OPTION_NAME,
				$post_id
			)
		);

		if ( empty( $option_name ) ) {
			return null;
		}

		return $this->get_email_type_from_option_name( $option_name );
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
		$option_name = $this->get_option_name( $email_type );
		update_option( $option_name, $post_id );
	}

	/**
	 * Gets the post ID for a specific email template type.
	 *
	 * @param string $email_type The type of email template e.g. 'customer_new_account' from the WC_Email->id property.
	 * @return int|false The post ID if found, false otherwise.
	 */
	public function get_email_template_post_id( $email_type ) {
		$option_name = $this->get_option_name( $email_type );
		return get_option( $option_name );
	}

	/**
	 * Deletes the post ID for a specific email template type.
	 *
	 * @param string $email_type The type of email template e.g. 'customer_new_account' from the WC_Email->id property.
	 */
	public function delete_email_template( $email_type ) {
		$option_name = $this->get_option_name( $email_type );
		if ( ! get_option( $option_name ) ) {
			return;
		}
		delete_option( $option_name );
	}

	/**
	 * Gets the option name for a specific email type.
	 *
	 * @param string $email_type The type of email template e.g. 'customer_new_account' from the WC_Email->id property.
	 * @return string The option name e.g. 'woocommerce_email_templates_customer_new_account_post_id'
	 */
	private function get_option_name( $email_type ) {
		return str_replace( '%', $email_type, self::WC_OPTION_NAME );
	}

	/**
	 * Gets the email type from the option name.
	 *
	 * @param string $option_name The option name e.g. 'woocommerce_email_templates_customer_new_account_post_id'.
	 * @return string The email type e.g. 'customer_new_account'
	 */
	private function get_email_type_from_option_name( $option_name ) {
		return str_replace(
			array(
				'woocommerce_email_templates_',
				'_post_id',
			),
			'',
			$option_name
		);
	}

	/**
	 * Gets the email type class name from the template name.
	 *
	 * @param string $email_template_name The template name of the email type.
	 * @return string The email type class name.
	 */
	public function get_email_type_class_name_from_template_name( $email_template_name ) {
		return 'WC_Email_' . implode( '_', array_map( 'ucfirst', explode( '_', $email_template_name ) ) );
	}
}
