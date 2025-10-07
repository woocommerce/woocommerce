<?php
/**
 * StockNotification class file.
 */

declare( strict_types = 1);

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\HasherHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Notification data class.
 */
class Notification extends \WC_Data {
	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'stock_notification';

	/**
	 * Product. Runtime property.
	 *
	 * @var \WC_Product
	 */
	public $product;

	/**
	 * Default data.
	 *
	 * @var array
	 */
	protected $data = array(
		'status'              => NotificationStatus::PENDING,
		'product_id'          => 0,
		'user_id'             => 0,
		'user_email'          => '',
		'date_created'        => null,
		'date_confirmed'      => null,
		'date_modified'       => null,
		'date_notified'       => null,
		'date_last_attempt'   => null,
		'date_cancelled'      => null,
		'cancellation_source' => null,
	);

	/**
	 * Constructor.
	 *
	 * @param int|object|array $read ID to load from the DB (optional) or already queried data.
	 */
	public function __construct( $read = 0 ) {
		parent::__construct( $read );
		if ( is_numeric( $read ) && $read > 0 ) {
			$this->set_id( $read );
		} elseif ( $read instanceof self ) {
			$this->set_id( $read->get_id() );
		} elseif ( ! empty( $read->ID ) ) {
			$this->set_id( absint( $read->ID ) );
		} elseif ( is_array( $read ) && ! empty( $read['id'] ) ) {
			$this->set_props( $read );
			$this->set_object_read( true );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = \WC_Data_Store::load( 'stock_notification' );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Get the product ID.
	 *
	 * @param string $context Context.
	 * @return int
	 */
	public function get_product_id( $context = 'view' ) {
		return $this->get_prop( 'product_id', $context );
	}

	/**
	 * Get the user ID.
	 *
	 * @param string $context Context.
	 * @return int
	 */
	public function get_user_id( $context = 'view' ) {
		return $this->get_prop( 'user_id', $context );
	}

	/**
	 * Get the user email.
	 *
	 * @param string $context Context.
	 * @return string
	 */
	public function get_user_email( $context = 'view' ) {
		return $this->get_prop( 'user_email', $context );
	}

	/**
	 * Get the status.
	 *
	 * @param string $context Context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Get the date created.
	 *
	 * @param string $context Context.
	 * @return \WC_DateTime|null Datetime object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get the date modified.
	 *
	 * @param string $context Context.
	 * @return \WC_DateTime|null Datetime object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Get the date confirmed.
	 *
	 * @param string $context Context.
	 * @return \WC_DateTime|null Datetime object if the date is set or null if there is no date.
	 */
	public function get_date_confirmed( $context = 'view' ) {
		return $this->get_prop( 'date_confirmed', $context );
	}

	/**
	 * Get the date last attempt.
	 *
	 * @param string $context Context.
	 * @return \WC_DateTime|null Datetime object if the date is set or null if there is no date.
	 */
	public function get_date_last_attempt( $context = 'view' ) {
		return $this->get_prop( 'date_last_attempt', $context );
	}

	/**
	 * Get the date notified.
	 *
	 * @param string $context Context.
	 * @return \WC_DateTime|null Datetime object if the date is set or null if there is no date.
	 */
	public function get_date_notified( $context = 'view' ) {
		return $this->get_prop( 'date_notified', $context );
	}

	/**
	 * Get the date cancelled.
	 *
	 * @param string $context Context.
	 * @return \WC_DateTime|null Datetime object if the date is set or null if there is no date.
	 */
	public function get_date_cancelled( $context = 'view' ) {
		return $this->get_prop( 'date_cancelled', $context );
	}

	/**
	 * Get the cancellation source.
	 *
	 * @param string $context Context.
	 * @return string|null The cancellation source or null if there is no source.
	 */
	public function get_cancellation_source( $context = 'view' ) {
		return $this->get_prop( 'cancellation_source', $context );
	}

	/**
	 * Get the product.
	 *
	 * @return \WC_Product|false
	 */
	public function get_product() {
		if ( ! empty( $this->product ) ) {
			return $this->product;
		}

		$product = wc_get_product( $this->get_prop( 'product_id' ) );
		if ( ! $product ) {
			return false;
		}

		$this->product = $product;
		return $product;
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set the product ID.
	 *
	 * @param int $product_id Product ID.
	 */
	public function set_product_id( int $product_id ) {

		// Reset runtime cache if the product ID has changed.
		if ( is_a( $this->product, 'WC_Product' ) && $product_id !== $this->product->get_id() ) {
			$this->product = null;
		}
		$this->set_prop( 'product_id', $product_id );
	}

	/**
	 * Set the user ID.
	 *
	 * @param int $user_id User ID.
	 */
	public function set_user_id( int $user_id ) {
		$this->set_prop( 'user_id', $user_id );
	}

	/**
	 * Set the user email.
	 *
	 * @param string $user_email User email.
	 */
	public function set_user_email( string $user_email ) {
		$this->set_prop( 'user_email', $user_email );
	}

	/**
	 * Set the status.
	 *
	 * @param string $status Status.
	 */
	public function set_status( string $status ) {

		if ( ! in_array( $status, NotificationStatus::get_valid_statuses(), true ) ) {
			// Default to pending.
			$status = NotificationStatus::PENDING;
		}

		$this->set_prop( 'status', $status );
	}

	/**
	 * Set the date created.
	 *
	 * @param string|int $date_created Date created.
	 */
	public function set_date_created( $date_created ) {
		$this->set_date_prop( 'date_created', $date_created );
	}

	/**
	 * Set the date modified.
	 *
	 * @param string|int $date_modified Date modified.
	 */
	public function set_date_modified( $date_modified ) {
		$this->set_date_prop( 'date_modified', $date_modified );
	}

	/**
	 * Set the date confirmed.
	 *
	 * @param string|int $date_confirmed Date confirmed.
	 */
	public function set_date_confirmed( $date_confirmed ) {
		$this->set_date_prop( 'date_confirmed', $date_confirmed );
	}

	/**
	 * Set the date last attempt.
	 *
	 * @param string|int $date_last_attempt Date last attempt.
	 */
	public function set_date_last_attempt( $date_last_attempt ) {
		$this->set_date_prop( 'date_last_attempt', $date_last_attempt );
	}

	/**
	 * Set the date notified.
	 *
	 * @param string|int $date_notified Date notified.
	 */
	public function set_date_notified( $date_notified ) {
		$this->set_date_prop( 'date_notified', $date_notified );
	}

	/**
	 * Set the date cancelled.
	 *
	 * @param string|int $date_cancelled Date cancelled.
	 */
	public function set_date_cancelled( $date_cancelled ) {
		$this->set_date_prop( 'date_cancelled', $date_cancelled );
	}

	/**
	 * Set the cancellation source.
	 *
	 * @param string|null $cancellation_source Cancellation source. Can be null.
	 */
	public function set_cancellation_source( ?string $cancellation_source ) {
		if ( $cancellation_source && ! in_array( $cancellation_source, NotificationCancellationSource::get_valid_cancellation_sources(), true ) ) {
			// Default to user.
			$cancellation_source = NotificationCancellationSource::USER;
		}

		$this->set_prop( 'cancellation_source', $cancellation_source );
	}

	/*
	|--------------------------------------------------------------------------
	| Other Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Validate the data.
	 *
	 * @throws \WC_Data_Exception If the data is invalid.
	 */
	protected function validate_props() {
		if ( empty( $this->get_prop( 'product_id' ) ) ) {
			$this->error( 'stock_notification_product_id_required', __( 'Product ID is required.', 'woocommerce' ) );
		}

		if ( empty( $this->get_prop( 'user_id' ) ) && empty( $this->get_prop( 'user_email' ) ) ) {
			$this->error( 'stock_notification_user_id_or_user_email_required', __( 'User ID or User Email is required.', 'woocommerce' ) );
		}

		if ( ! empty( $this->get_prop( 'user_email' ) ) && ! filter_var( $this->get_prop( 'user_email' ), FILTER_VALIDATE_EMAIL ) ) {
			$this->error( 'stock_notification_user_email_invalid', __( 'User Email is invalid.', 'woocommerce' ) );
		}
	}

	/**
	 * Save the notification.
	 *
	 * @return int|\WP_Error The notification ID or a WP_Error if the save failed.
	 */
	public function save() {
		if ( ! $this->data_store ) {
			return $this->get_id();
		}

		try {
			$this->validate_props();
		} catch ( \WC_Data_Exception $e ) {
			return new \WP_Error( 'stock_notification_validation_error', $e->getMessage() );
		}

		if ( $this->get_id() ) {
			$this->data_store->update( $this );
		} else {
			$this->data_store->create( $this );
		}

		return $this->get_id();
	}

	/**
	 * Retrieves the formatted attributes of the product based on the notification's posted attributes.
	 *
	 * Wrapper of the `wc_get_formatted_variation` function.
	 *
	 * @param bool $flat Flatten the list.
	 * @return string
	 */
	public function get_product_formatted_variation_list( bool $flat = false ) {

		$product = $this->get_product();
		if ( ! $product || ! $product->is_type( array( 'variation' ) ) ) {
			return '';
		}

		// Replace list with custom data.
		$attributes = $this->get_meta( 'posted_attributes' );
		if ( ! $attributes ) {
			$attributes = $product->get_attributes();
		}

		if ( empty( $attributes ) ) {
			return '';
		}

		$attrs = array();
		foreach ( $attributes as $key => $value ) {

			if ( 0 === strpos( $key, 'attribute_pa_' ) ) {
				$attrs[ str_replace( 'attribute_', '', $key ) ] = $value;
			} else {
				// By pass converting global product attributes.
				$attrs[ wc_attribute_label( str_replace( 'attribute_', '', $key ), $product ) ] = $value;
			}
		}

		$formatted_variation_list = wc_get_formatted_variation( $attrs, $flat, true, true );

		return $formatted_variation_list;
	}

	/**
	 * Get product link.
	 *
	 * @return string
	 */
	public function get_product_permalink() {

		$product = $this->get_product();
		if ( ! $product ) {
			return '';
		}

		if ( $product->is_type( 'variation' ) && ! empty( $this->get_meta( 'posted_attributes' ) ) ) {
			return $product->get_permalink( array( 'item_meta_array' => $this->get_meta( 'posted_attributes' ) ) );
		} else {
			return $product->get_permalink();
		}
	}

	/**
	 * Get product name.
	 *
	 * @return string
	 */
	public function get_product_name() {
		$product = $this->get_product();
		if ( ! $product ) {
			return '';
		}

		return $product->get_parent_id() ? $product->get_name() : $product->get_title();
	}

	/**
	 * Check if the given key is a valid verification key.
	 *
	 * This method checks if the key is valid by verifying the hash and checking the expiration time.
	 *
	 * @param string $key The key to check.
	 * @return bool True if the key is valid, false otherwise.
	 */
	public function check_verification_key( string $key ): bool {
		$action_key = $this->get_meta( 'email_link_action_key' );

		if ( ! str_contains( $action_key, ':' ) ) {
			return false;
		}

		list( $timestamp, $hash ) = explode( ':', $action_key, 2 );

		$threshold = Config::get_verification_expiration_time_threshold();
		if ( time() - (int) $timestamp > $threshold ) {
			return false;
		}

		return HasherHelper::wp_verify_fast_hash( $key, $hash );
	}

	/**
	 * Maybe setup verification data for the notification.
	 *
	 * This is used to ensure that the notification has valid verification data.
	 *
	 * @param bool $persist If true, save the changes to the database.
	 * @return string The generated verification key.
	 */
	public function get_verification_key( bool $persist ): string {
		$key = wp_generate_password( 20, false );
		$this->update_meta_data( 'email_link_action_key', time() . ':' . HasherHelper::wp_fast_hash( $key ) );

		if ( $persist ) {
			$this->save();
		}

		return $key;
	}

	/**
	 * Check if the given key is a valid unsubscribe key.
	 *
	 * @param string $key The key to check.
	 * @return bool True if the key is valid, false otherwise.
	 */
	public function check_unsubscribe_key( string $key ): bool {
		return HasherHelper::wp_verify_fast_hash( $key, $this->get_meta( 'email_link_action_key' ) );
	}

	/**
	 * Maybe setup verification data for the notification.
	 *
	 * This is used to ensure that the notification has valid verification data.
	 *
	 * @param bool $persist If true, save the changes to the database.
	 * @return string The generated unsubscribe key.
	 */
	public function get_unsubscribe_key( bool $persist ): string {
		$key  = wp_generate_password( 20, false );
		$hash = HasherHelper::wp_fast_hash( $key );
		$this->update_meta_data( 'email_link_action_key', $hash );

		if ( $persist ) {
			$this->save();
		}

		return $key;
	}
}
