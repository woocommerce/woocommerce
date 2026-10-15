<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Privacy;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountEndpoint;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EmailNormalizer;

/**
 * Privacy exporter and eraser for WooCommerce Customer Stock Notifications.
 *
 * This class handles the export and erasure of customer stock notification data
 * for users who request their personal data.
 */
class PrivacyEraser extends \WC_Abstract_Privacy {

	/**
	 * Number of sign-ups exported per page.
	 */
	private const EXPORT_PAGE_SIZE = 10;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'init', array( $this, 'register_erasers_exporters' ) );
	}

	/**
	 * Register the exporter and eraser for stock notifications.
	 */
	public function register_erasers_exporters() {
		$this->add_exporter(
			'woocommerce-customer-stock-notifications',
			__( 'WooCommerce Customer Stock Notifications', 'woocommerce' ),
			array( $this, 'export_notification_data' )
		);
		$this->add_eraser(
			'woocommerce-customer-stock-notifications',
			__( 'WooCommerce Customer Stock Notifications', 'woocommerce' ),
			array( $this, 'erase_notification_data' )
		);
	}

	/**
	 * Export customer stock notification data for a given email address and the account it belongs to.
	 *
	 * @since 11.3.0
	 *
	 * @param string $email_address The email address to export data for.
	 * @param int    $page          Page number, starting at 1.
	 *
	 * @return array Response containing the exported data and whether the export is done.
	 */
	public static function export_notification_data( $email_address, $page = 1 ): array {
		$customer       = self::get_customer( is_string( $email_address ) ? $email_address : '' );
		$page           = max( 1, (int) $page );
		$data_to_export = array();
		$done           = true;

		if ( ! empty( $customer ) ) {
			$notification_ids = NotificationQuery::get_notifications(
				array(
					'customer' => $customer,
					'limit'    => self::EXPORT_PAGE_SIZE,
					'offset'   => ( $page - 1 ) * self::EXPORT_PAGE_SIZE,
				)
			);

			foreach ( $notification_ids as $notification_id ) {
				$notification = Factory::get_notification( (int) $notification_id );
				if ( ! $notification instanceof Notification || ! self::belongs_to_customer( $notification, $customer ) ) {
					continue;
				}

				$data_to_export[] = array(
					'group_id'          => 'woocommerce_stock_notifications',
					'group_label'       => __( 'Stock Notifications', 'woocommerce' ),
					'group_description' => __( 'User&#8217;s WooCommerce back-in-stock notification sign-ups.', 'woocommerce' ),
					'item_id'           => 'stock-notification-' . $notification->get_id(),
					'data'              => self::get_notification_personal_data( $notification ),
				);
			}

			$done = count( $notification_ids ) < self::EXPORT_PAGE_SIZE
				|| NotificationQuery::count_notifications( array( 'customer' => $customer ) ) <= $page * self::EXPORT_PAGE_SIZE;
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}

	/**
	 * Get the personal data to export for a single sign-up.
	 *
	 * @since 11.3.0
	 *
	 * @param Notification $notification The sign-up.
	 *
	 * @return array List of name/value pairs.
	 */
	private static function get_notification_personal_data( Notification $notification ): array {
		$product_name = MyAccountEndpoint::get_display_product_name( $notification );
		if ( '' === $product_name ) {
			/* translators: %d: product ID */
			$product_name = sprintf( __( 'Product #%d', 'woocommerce' ), $notification->get_product_id() );
		}
		$variation = $notification->get_product_formatted_variation_list( true );
		if ( '' !== $variation ) {
			$product_name .= ' ' . $variation;
		}

		$status_labels = array(
			NotificationStatus::PENDING   => _x( 'Pending', 'stock notification status', 'woocommerce' ),
			NotificationStatus::ACTIVE    => _x( 'Active', 'stock notification status', 'woocommerce' ),
			NotificationStatus::SENT      => _x( 'Sent', 'stock notification status', 'woocommerce' ),
			NotificationStatus::CANCELLED => _x( 'Cancelled', 'stock notification status', 'woocommerce' ),
		);
		$status        = (string) $notification->get_status();
		$date_format   = get_option( 'date_format' ) . ', ' . get_option( 'time_format' );
		$date_created  = $notification->get_date_created();
		$date_notified = $notification->get_date_notified();

		return array(
			array(
				'name'  => __( 'Product', 'woocommerce' ),
				'value' => $product_name,
			),
			array(
				'name'  => __( 'Product URL', 'woocommerce' ),
				'value' => $notification->get_product_permalink(),
			),
			array(
				'name'  => __( 'Email', 'woocommerce' ),
				'value' => $notification->get_user_email(),
			),
			array(
				'name'  => __( 'Status', 'woocommerce' ),
				'value' => $status_labels[ $status ] ?? $status,
			),
			array(
				'name'  => __( 'Date signed up', 'woocommerce' ),
				'value' => $date_created ? wc_format_datetime( $date_created, $date_format ) : '',
			),
			array(
				'name'  => __( 'Date notified', 'woocommerce' ),
				'value' => $date_notified ? wc_format_datetime( $date_notified, $date_format ) : '',
			),
		);
	}

	/**
	 * Get the `customer` query values for an email address and the account it belongs to.
	 *
	 * @since 11.3.0
	 *
	 * @param string $email_address The email address, in the letter case the request was made with.
	 *
	 * @return array<int|string> Normalized email plus user ID when there is an account. Empty for an invalid email.
	 */
	private static function get_customer( string $email_address ): array {
		$normalized_email = EmailNormalizer::normalize( $email_address );
		if ( '' === $normalized_email || ! is_email( $normalized_email ) ) {
			return array();
		}

		$customer = array( $normalized_email );

		// Look up the account with the letter case as entered: `wp_users.user_email` is never
		// normalized, so on a case-sensitive collation the lowercased form would miss it.
		$user = get_user_by( 'email', $email_address );
		if ( $user instanceof \WP_User ) {
			$customer[] = (int) $user->ID;
		}

		return $customer;
	}

	/**
	 * Check that a sign-up belongs to the customer being exported or erased.
	 *
	 * The data store can be replaced through the `woocommerce_stock_notification_data_store`
	 * filter, and a replacement that ignores the `customer` query argument would return every
	 * sign-up. Checking each row keeps both paths from touching other customers' data.
	 *
	 * @since 11.3.0
	 *
	 * @param Notification      $notification The sign-up.
	 * @param array<int|string> $customer     The `customer` query values from `get_customer()`.
	 *
	 * @return bool
	 */
	private static function belongs_to_customer( Notification $notification, array $customer ): bool {
		return in_array( (int) $notification->get_user_id(), $customer, true )
			|| in_array( EmailNormalizer::normalize( (string) $notification->get_user_email() ), $customer, true );
	}

	/**
	 * Erase customer stock notification data for a given email address and the account it belongs to.
	 *
	 * This method anonymizes the user email and sets the status of the notifications to 'cancelled'.
	 *
	 * @param string $email_address The email address to erase data for.
	 *
	 * @return array Response containing the status of the operation and messages.
	 */
	public static function erase_notification_data( string $email_address ): array {
		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		$customer      = self::get_customer( $email_address );
		$email_address = EmailNormalizer::normalize( $email_address );
		$notifications = empty( $customer ) ? array() : NotificationQuery::get_notifications( array( 'customer' => $customer ) );

		foreach ( $notifications as $notification_id ) {
			$notification = Factory::get_notification( (int) $notification_id );
			if ( ! $notification instanceof Notification || ! self::belongs_to_customer( $notification, $customer ) ) {
				continue;
			}

			$anonymous_email = wp_privacy_anonymize_data( 'email', $email_address );
			$notification->set_user_email( $anonymous_email );
			$notification->set_user_id( 0 );
			$notification->set_status( NotificationStatus::CANCELLED );
			$notification->set_cancellation_source( NotificationCancellationSource::USER );
			$notification->set_date_cancelled( time() );
			$notification->update_meta_data( '_anonymized', 'yes' );
			$notification->update_meta_data( 'verification_action_key', '' );
			$notification->update_meta_data( 'unsubscribe_action_key', '' );
			$notification->save();
			$response['messages'][] = sprintf(
			/* translators: %d the numeric product ID */
				__( 'Removed back-in-stock notification for product id: %d', 'woocommerce' ),
				$notification->get_product_id()
			);
			$response['items_removed'] = true;
		}//end foreach

		return $response;
	}
}
