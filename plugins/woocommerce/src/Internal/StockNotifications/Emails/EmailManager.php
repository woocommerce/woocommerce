<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\CustomerStockNotificationEmail;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\CustomerStockNotificationVerifyEmail;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\CustomerStockNotificationVerifiedEmail;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailTemplatesController;
/**
 * Emails manager.
 */
class EmailManager {

	/**
	 * List of all core email IDs.
	 *
	 * @var array
	 */
	public static $email_ids = array(
		'customer_stock_notification',
		'customer_stock_notification_verify',
		'customer_stock_notification_verified',
	);

	/**
	 * Initialize the emails.
	 *
	 * @internal
	 *
	 * @return void
	 */
	final public function init() {

		// Setup email hooks & handlers.
		add_filter( 'woocommerce_email_classes', array( $this, 'email_classes' ) );

		// Add "transactional" emails.
		add_action( 'woocommerce_email_actions', array( $this, 'add_transactional_emails' ) );

		// Setup styles.
		add_filter( 'woocommerce_email_styles', array( $this, 'add_stylesheets' ), 10, 2 );

		// Preview.
		add_filter( 'woocommerce_prepare_email_for_preview', array( $this, 'prepare_email_for_preview' ) );
		add_filter( 'woocommerce_email_preview_email_content_setting_ids', array( $this, 'add_intro_content_to_preview_settings' ), 10, 2 );

		// Restore customer's context while rendering the emails.
		add_action( 'woocommerce_email_stock_notification_product', array( $this, 'maybe_restore_customer_tax_location_data' ), 9 );

		// Register email templates.
		$container = wc_get_container();
		$container->get( EmailTemplatesController::class );
	}

	/**
	 * Registers custom emails classes.
	 *
	 * @param array $emails Array of email classes.
	 * @return array
	 */
	public function email_classes( $emails ) {
		$emails['WC_Email_Customer_Stock_Notification']          = new CustomerStockNotificationEmail();
		$emails['WC_Email_Customer_Stock_Notification_Verify']   = new CustomerStockNotificationVerifyEmail();
		$emails['WC_Email_Customer_Stock_Notification_Verified'] = new CustomerStockNotificationVerifiedEmail();

		return $emails;
	}

	/**
	 * Adds transactional emails.
	 *
	 * Stock notifications are sent via a custom AS job.
	 * Additionally, two transactional emails are dispatched during the signup and verification processes,
	 * which need to be included in the actions array to support deferred email functionality.
	 *
	 * @hook woocommerce_defer_transactional_emails
	 *
	 * @param array $actions The list of actions.
	 * @return array
	 */
	public function add_transactional_emails( $actions ) {
		if ( ! is_array( $actions ) ) {
			return $actions;
		}

		$actions[] = 'woocommerce_customer_stock_notification_verify';
		$actions[] = 'woocommerce_customer_stock_notification_verified';

		return $actions;
	}

	/**
	 * Restore customer tax location data from notification's metadata
	 * to display product prices in emails using the customer's tax location, if applicable.
	 *
	 * @param  Notification $notification The notification object.
	 * @return void
	 */
	public function maybe_restore_customer_tax_location_data( $notification ) {

		// No need if stores displaying price excluding tax.
		if ( 'incl' !== get_option( 'woocommerce_tax_display_shop' ) ) {
			return;
		}

		// Check if for some reason (e.g., 3PD), a WC_Customer is already assigned into the BG process's context.
		if ( ! empty( WC()->customer ) ) {
			return;
		}

		// Get the recorded customer data, if any.
		$location = $notification->get_meta( '_customer_location_data' );
		if ( empty( $location ) || ! is_array( $location ) || 4 !== count( $location ) ) {
			return;
		}

		// Restore the tax location.
		add_filter(
			'woocommerce_get_tax_location',
			function () use ( $location ) {
				return $location;
			}
		);
	}

	/**
	 * Prints CSS in the emails.
	 *
	 * @param  string   $css The CSS to print.
	 * @param  WC_Email $email (Optional) The email object.
	 * @return string
	 */
	public function add_stylesheets( $css, $email = null ) {

		/**
		 * `woocommerce_email_stock_notification_emails_to_style` filter.
		 *
		 * @since  10.2.0
		 *
		 * @return array
		 */
		if ( ( is_null( $email ) || ! in_array( $email->id, (array) apply_filters( 'woocommerce_email_stock_notification_emails_to_style', self::$email_ids ), true ) ) ) {
			return $css;
		}

		// General text.
		$text = get_option( 'woocommerce_email_text_color' );

		// Primary color.
		$base = get_option( 'woocommerce_email_base_color' );

		/**
		 * `woocommerce_email_stock_notification_base_text_color` filter.
		 *
		 * @since  10.2.0
		 *
		 * @return string
		 */
		$base_text = (string) apply_filters( 'woocommerce_email_stock_notification_base_text_color', wc_light_or_dark( $base, '#202020', '#ffffff' ), $email );

		ob_start();
		?>
		#header_wrapper h1 {
			line-height: 1em !important;
		}
		#notification__container {
			color: <?php echo esc_attr( $text ); ?> !important;
			padding: 20px 20px;
			text-align: center;
			font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
			width: 100%;
		}
		#notification__into_content {
			margin-bottom: 48px;
			color: <?php echo esc_attr( $text ); ?> !important;
		}
		#notification__product__image {
			text-align: center;
			margin-bottom: 20px;
			width: 100%;
		}
		#notification__product__image img {
			margin-right: 0;
			width: 220px;
		}
		#notification__product__title {
			font-size: 16px;
			font-weight: bold;
			line-height: 130%;
			margin-bottom: 5px;
			color: <?php echo esc_attr( $text ); ?> !important;
		}
		#notification__product__attributes table {
			width: 100%;
			padding: 0;
			margin: 0;
			color: <?php echo esc_attr( $text ); ?> !important;
		}
		#notification__product__attributes th,
		#notification__product__attributes td {
			color: <?php echo esc_attr( $text ); ?> !important;
			padding: 4px !important;
			text-align: center;
		}
		#notification__product__price {
			margin-bottom: 20px;
			color: <?php echo esc_attr( $text ); ?> !important;
		}
		#notification__action_button {
			text-decoration: none;
			display: inline-block;
			background: <?php echo esc_attr( $base ); ?>;
			color: <?php echo esc_attr( $base_text ); ?> !important;
			border: 10px solid <?php echo esc_attr( $base ); ?>;
		}
		#notification__verification_expiration {
			font-size: 0.8em;
			margin-top: 20px;
			color: <?php echo esc_attr( $text ); ?>;
		}
		#notification__footer {
			text-align: center;
			margin-top: 20px;
			color: <?php echo esc_attr( $text ); ?>;
		}
		#notification__unsubscribe_link {
			color: <?php echo esc_attr( $text ); ?>;
		}
		#notification__product__price .screen-reader-text {
			display: none;
		}
		<?php
		$css .= ob_get_clean();

		return $css;
	}

	/**
	 * Register intro_content email fields to be watched by WooCommerce's live email preview.
	 *
	 * @param array  $setting_ids The email content setting IDs.
	 * @param string $email_id The email ID.
	 * @return array
	 */
	public function add_intro_content_to_preview_settings( $setting_ids, $email_id ) {

		if ( in_array( $email_id, self::$email_ids, true ) ) {
			$setting_ids[] = "woocommerce_{$email_id}_intro_content";
		}

		return $setting_ids;
	}

	/**
	 * Prepares the email for preview.
	 *
	 * @param \WC_Email $email The email object being previewed.
	 * @return \WC_Email
	 */
	public function prepare_email_for_preview( $email ) {
		if ( ! in_array( $email->id, self::$email_ids, true ) ) {
			return $email;
		}

		$notification = Factory::create_dummy_notification();
		$email->prepare_email( $notification );

		return $email;
	}

	/**
	 * Send a stock notification email.
	 *
	 * @param Notification $notification The notification object.
	 * @return void
	 */
	public function send_stock_notification_email( Notification $notification ) {
		$emails = WC()->mailer()->get_emails();
		if ( isset( $emails['WC_Email_Customer_Stock_Notification'] ) ) {
			$emails['WC_Email_Customer_Stock_Notification']->trigger( $notification );
		}
	}
}
