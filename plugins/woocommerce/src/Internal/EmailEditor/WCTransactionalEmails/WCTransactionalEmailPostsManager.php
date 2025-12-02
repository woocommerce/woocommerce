<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

/**
 * Class responsible for managing WooCommerce email editor post templates.
 */
class WCTransactionalEmailPostsManager {
	const WC_OPTION_NAME = 'woocommerce_email_templates_%_post_id';

	/**
	 * Cache group for email template lookups.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'wc_block_email_templates';

	/**
	 * Cache expiration time in seconds (1 week).
	 *
	 * @var int
	 */
	const CACHE_EXPIRATION = WEEK_IN_SECONDS;

	/**
	 * Singleton instance of the class.
	 *
	 * @var WCTransactionalEmailPostsManager|null
	 */
	private static $instance = null;

	/**
	 * In-memory cache for post_id to email_type lookups within the same request.
	 *
	 * @var array<int|string, string|null>
	 */
	private $post_id_to_email_type_cache = array();

	/**
	 * In-memory cache for email class name (e.g. 'WC_Email_Customer_New_Account') lookups within the same request.
	 *
	 * @var array<string, string|null>
	 */
	private $email_class_name_cache = array();

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
	 * Uses multi-level caching:
	 * 1. In-memory cache for the same request
	 * 2. WordPress object cache for cross-request caching
	 * 3. Database query if cache is not available.
	 *
	 * @param int|string $post_id The post ID.
	 * @param bool       $skip_cache Whether to skip the cache. Defaults to false.
	 * @return string|null The WooCommerce email type if found, null otherwise.
	 */
	public function get_email_type_from_post_id( $post_id, $skip_cache = false ) {
		// Early return if post_id is invalid.
		if ( empty( $post_id ) ) {
			return null;
		}

		$post_id   = (int) $post_id;
		$cache_key = $this->get_cache_key_for_post_id( $post_id );

		if ( ! $skip_cache ) {
			// Check in-memory cache first (fastest).
			if ( array_key_exists( $post_id, $this->post_id_to_email_type_cache ) ) {
				return $this->post_id_to_email_type_cache[ $post_id ];
			}

			// Check WordPress object cache.
			$email_type = wp_cache_get( $cache_key, self::CACHE_GROUP );

			if ( ! empty( $email_type ) ) {
				$this->post_id_to_email_type_cache[ $post_id ] = $email_type;
				return $email_type;
			}
		}

		// Cache miss - perform database query.
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

		$email_type = $this->get_email_type_from_option_name( $option_name );

		// Store in both caches.
		$this->post_id_to_email_type_cache[ $post_id ] = $email_type;
		wp_cache_set( $cache_key, $email_type, self::CACHE_GROUP, self::CACHE_EXPIRATION );

		return $email_type;
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

		$previous_id = get_option( $option_name );

		update_option( $option_name, $post_id );

		// Invalidate caches for the previous mapping (if any).
		if ( ! empty( $previous_id ) ) {
			$this->invalidate_cache_for_template( (int) $previous_id, 'post_id' );
		}

		// Invalidate cache for the new post_id.
		$this->invalidate_cache_for_template( $email_type, 'email_type' );

		// Update in-memory caches with the new values.
		$this->post_id_to_email_type_cache[ $post_id ] = $email_type;
		wp_cache_set( $this->get_cache_key_for_post_id( $post_id ), $email_type, self::CACHE_GROUP, self::CACHE_EXPIRATION );
	}

	/**
	 * Gets the post ID for a specific email template type.
	 *
	 * Uses multi-level caching for improved performance.
	 *
	 * @param string $email_type The type of email template e.g. 'customer_new_account' from the WC_Email->id property.
	 * @return int|false The post ID if found, false otherwise.
	 */
	public function get_email_template_post_id( $email_type ) {
		// Check in-memory cache first.
		$post_id_from_cache = array_search( $email_type, $this->post_id_to_email_type_cache, true );
		if ( false !== $post_id_from_cache ) {
			return $post_id_from_cache;
		}

		$option_name = $this->get_option_name( $email_type );
		$post_id     = get_option( $option_name );

		if ( ! empty( $post_id ) ) {
			$post_id = (int) $post_id;

			// Store in in-memory cache.
			$this->post_id_to_email_type_cache[ $post_id ] = $email_type;
		}

		return $post_id;
	}

	/**
	 * Deletes the post ID for a specific email template type.
	 *
	 * @param string $email_type The type of email template e.g. 'customer_new_account' from the WC_Email->id property.
	 */
	public function delete_email_template( $email_type ) {
		$option_name = $this->get_option_name( $email_type );
		$post_id     = get_option( $option_name );

		if ( ! $post_id ) {
			return;
		}

		delete_option( $option_name );

		// Invalidate cache.
		$this->invalidate_cache_for_template( $post_id, 'post_id' );
	}

	/**
	 * Invalidates cache entries for a specific post ID or email type.
	 *
	 * @param int|string $value The value to invalidate cache for.
	 * @param string     $type The type of value to invalidate cache for. Can be 'post_id' or 'email_type'.
	 * @return void
	 */
	private function invalidate_cache_for_template( $value, $type = 'post_id' ) {
		$post_id_array = array();
		if ( 'post_id' === $type ) {
			$post_id_array[] = (int) $value;
		} elseif ( 'email_type' === $type ) {
			// Get all the post IDs that map to the email type.
			$post_id_array = array_merge( $post_id_array, array_unique( array_keys( $this->post_id_to_email_type_cache, $value, true ) ) );
		}

		foreach ( $post_id_array as $post_id ) {
			unset( $this->post_id_to_email_type_cache[ $post_id ] );

			// Delete from WordPress object cache.
			$cache_key = $this->get_cache_key_for_post_id( $post_id );
			wp_cache_delete( $cache_key, self::CACHE_GROUP );
		}
	}

	/**
	 * Clears all in-memory caches.
	 *
	 * Useful for testing and debugging. Note that this only clears in-memory caches,
	 * not the WordPress object cache entries (which will expire naturally).
	 */
	public function clear_caches() {
		$this->post_id_to_email_type_cache = array();
		$this->email_class_name_cache      = array();
	}

	/**
	 * Gets the cache key for a specific post ID.
	 *
	 * @param int $post_id The post ID.
	 * @return string The cache key e.g. 'post_id_to_email_type_123'.
	 */
	public function get_cache_key_for_post_id( $post_id ) {
		return 'post_id_to_email_type_' . $post_id;
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
	 * Gets the email type class name, e.g. 'WC_Email_Customer_New_Account' from the email ID (e.g. 'customer_new_account' from the WC_Email->id property).
	 *
	 * Uses in-memory caching to avoid repeated iterations through all registered emails.
	 *
	 * @param string $email_id The email ID.
	 * @return string|null The email type class name.
	 */
	public function get_email_type_class_name_from_email_id( $email_id ) {
		// Early return if email_id is invalid.
		if ( empty( $email_id ) ) {
			return null;
		}

		// Check in-memory cache first.
		if ( isset( $this->email_class_name_cache[ $email_id ] ) ) {
			return $this->email_class_name_cache[ $email_id ];
		}

		/**
		 * Get all the emails registered in WooCommerce.
		 *
		 * @var \WC_Email[]
		 */
		$emails = WC()->mailer()->get_emails();

		// Build the cache for all emails at once to avoid repeated iterations.
		foreach ( $emails as $email ) {
			$this->email_class_name_cache[ $email->id ] = get_class( $email );
		}

		// Return the requested email class name if it exists.
		return $this->email_class_name_cache[ $email_id ] ?? null;
	}

	/**
	 * Gets the email type class name, e.g. 'WC_Email_Customer_New_Account' from the post ID.
	 *
	 * @param int $post_id The post ID.
	 * @return string|null The email type class name.
	 */
	public function get_email_type_class_name_from_post_id( $post_id ) {
		// Early return if post_id is invalid.
		if ( empty( $post_id ) ) {
			return null;
		}

		return $this->get_email_type_class_name_from_email_id( $this->get_email_type_from_post_id( $post_id ) );
	}
}
