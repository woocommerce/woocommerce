<?php
/**
 * Renders the email preview.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\EmailPreview;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Email;
use WC_Order;
use WC_Product;
use WC_Product_Variation;
use WP_User;

defined( 'ABSPATH' ) || exit;


/**
 * EmailPreview Class.
 */
class EmailPreview {
	const DEFAULT_EMAIL_TYPE = 'WC_Email_Customer_Processing_Order';
	const DEFAULT_EMAIL_ID   = 'customer_processing_order';
	const USER_OBJECT_EMAILS = array(
		'WC_Email_Customer_New_Account',
		'WC_Email_Customer_Reset_Password',
	);

	/**
	 * All fields IDs that can customize email styles in Settings.
	 *
	 * @var array
	 */
	private static array $email_style_setting_ids = array(
		'woocommerce_email_background_color',
		'woocommerce_email_base_color',
		'woocommerce_email_body_background_color',
		'woocommerce_email_font_family',
		'woocommerce_email_footer_text',
		'woocommerce_email_footer_text_color',
		'woocommerce_email_header_alignment',
		'woocommerce_email_header_image',
		'woocommerce_email_header_image_width',
		'woocommerce_email_text_color',
	);

	/**
	 * All fields IDs that can customize specific email content in Settings.
	 *
	 * @var array
	 */
	private static array $email_content_setting_ids = array();

	/**
	 * Whether the email setting IDs are initialized.
	 *
	 * @var bool
	 */
	private static bool $email_setting_ids_initialized = false;

	/**
	 * The email type to preview.
	 *
	 * @var string|null
	 */
	private ?string $email_type = null;

	/**
	 * The email object.
	 *
	 * @var WC_Email|null
	 */
	private ?WC_Email $email = null;

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Get all email setting IDs.
	 */
	public static function get_all_email_setting_ids() {
		if ( ! self::$email_setting_ids_initialized ) {
			self::$email_setting_ids_initialized = true;

			$emails = WC()->mailer()->get_emails();
			foreach ( $emails as $email ) {
				self::$email_content_setting_ids = array_merge(
					self::$email_content_setting_ids,
					self::get_email_content_setting_ids( $email->id )
				);
			}
			self::$email_content_setting_ids = array_unique( self::$email_content_setting_ids );
		}
		return array_merge(
			self::$email_style_setting_ids,
			self::$email_content_setting_ids,
		);
	}

	/**
	 * Get email style setting IDs.
	 */
	public static function get_email_style_setting_ids() {
		/**
		 * Filter the email style setting IDs. Email preview automatically refreshes when these settings are changed.
		 *
		 * @param array $setting_ids The email style setting IDs.
		 *
		 * @since 9.8.0
		 */
		return apply_filters( 'woocommerce_email_preview_email_style_setting_ids', self::$email_style_setting_ids );
	}

	/**
	 * Get email content setting IDs for specific email.
	 *
	 * @param string|null $email_id Email ID.
	 */
	public static function get_email_content_setting_ids( ?string $email_id ) {
		if ( ! $email_id ) {
			return array();
		}
		$setting_ids = array(
			"woocommerce_{$email_id}_subject",
			"woocommerce_{$email_id}_heading",
			"woocommerce_{$email_id}_additional_content",
			"woocommerce_{$email_id}_email_type",
		);

		/**
		 * Filter the email content setting IDs for specific email. Email preview automatically refreshes when these settings are changed.
		 *
		 * @param array  $setting_ids The email content setting IDs.
		 * @param string $email_id The email ID.
		 *
		 * @since 9.8.0
		 */
		return apply_filters( 'woocommerce_email_preview_email_content_setting_ids', $setting_ids, $email_id );
	}

	/**
	 * Set the email type to preview.
	 *
	 * @param string $email_type Email type.
	 *
	 * @throws \InvalidArgumentException When the email type is invalid.
	 */
	public function set_email_type( string $email_type ) {
		$wc_emails = WC()->mailer()->get_emails();
		$emails    = array_combine(
			array_map( 'get_class', $wc_emails ),
			$wc_emails
		);
		if ( ! in_array( $email_type, array_keys( $emails ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid email type' );
		}
		$this->email_type = $email_type;
		$this->email      = $emails[ $email_type ];
		$object           = null;

		if ( in_array( $email_type, self::USER_OBJECT_EMAILS, true ) ) {
			$object                  = new WP_User( 0 );
			$this->email->user_email = 'user_preview@example.com';
			$this->email->user_login = 'user_preview';
			$this->email->set_object( $object );
		} else {
			$object = $this->get_dummy_order();
			if ( 'WC_Email_Customer_Note' === $email_type ) {
				$this->email->customer_note = $object->get_customer_note();
			}
			if ( 'WC_Email_Customer_Refunded_Order' === $email_type ) {
				$this->email->partial_refund = false;
			}
			$this->email->set_object( $object );
		}
		$this->email->placeholders = array_merge(
			$this->email->placeholders,
			$this->get_placeholders( $object )
		);

		/**
		 * Allow to modify the email object before rendering the preview to add additional data.
		 *
		 * @param WC_Email $email The email object.
		 *
		 * @since 9.6.0
		 */
		$this->email = apply_filters( 'woocommerce_prepare_email_for_preview', $this->email );
	}

	/**
	 * Get the preview email content.
	 *
	 * @return string
	 */
	public function render() {
		return $this->render_preview_email();
	}

	/**
	 * Ensure links open in new tab. User in WooCommerce Settings,
	 * so the links don't open inside the iframe.
	 *
	 * @param string $content Email content HTML.
	 * @return string
	 */
	public function ensure_links_open_in_new_tab( string $content ) {
		return (string) preg_replace_callback(
			'/<a\s+([^>]*?)(target=["\']?[^"\']*["\']?)?([^>]*)>/i',
			function ( $matches ) {
				$before = $matches[1];
				$target = 'target="_blank"';
				$after  = $matches[3];
				return "<a $before $target $after>";
			},
			$content
		);
	}

	/**
	 * Get the preview email content.
	 *
	 * @return string
	 */
	public function get_subject() {
		if ( ! $this->email ) {
			return '';
		}
		$this->set_up_filters();
		$subject = $this->email->get_subject();
		$this->clean_up_filters();
		return $subject;
	}

	/**
	 * Return a dummy product when the product is not set in email classes.
	 *
	 * @param WC_Product|null $product Order item product.
	 * @return WC_Product
	 */
	public function get_dummy_product_when_not_set( $product ) {
		if ( $product ) {
			return $product;
		}
		return $this->get_dummy_product();
	}

	/**
	 * Render HTML content of the preview email.
	 *
	 * @return string
	 */
	private function render_preview_email() {
		$this->set_up_filters();

		if ( ! $this->email_type ) {
			$this->set_email_type( self::DEFAULT_EMAIL_TYPE );
		}

		if ( 'plain' === $this->email->get_email_type() ) {
			$content  = '<pre style="word-wrap: break-word; white-space: pre-wrap;">';
			$content .= $this->email->get_content_plain();
			$content .= '</pre>';
		} else {
			$content = $this->email->get_content_html();
		}
		$inlined = $this->email->style_inline( $content );

		$this->clean_up_filters();

		/** This filter is documented in src/Internal/Admin/EmailPreview/EmailPreview.php */
		return apply_filters( 'woocommerce_mail_content', $inlined ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
	}

	/**
	 * Get a dummy order object without the need to create in the database.
	 *
	 * @return WC_Order
	 */
	private function get_dummy_order() {
		$product   = $this->get_dummy_product();
		$variation = $this->get_dummy_product_variation();

		$order = new WC_Order();
		if ( $product ) {
			$order->add_product( $product, 2 );
		}
		if ( $variation ) {
			$order->add_product( $variation );
		}
		$order->set_id( 12345 );
		$order->set_date_created( time() );
		$order->set_currency( 'USD' );
		$order->set_discount_total( 10 );
		$order->set_shipping_total( 5 );
		$order->set_total( 65 );
		$order->set_payment_method_title( __( 'Direct bank transfer', 'woocommerce' ) );
		$order->set_customer_note( __( "This is a customer note. Customers can add a note to their order on checkout.\n\nIt can be multiple lines. If there’s no note, this section is hidden.", 'woocommerce' ) );

		$address = $this->get_dummy_address();
		$order->set_billing_address( $address );
		$order->set_shipping_address( $address );

		/**
		 * A dummy WC_Order used in email preview.
		 *
		 * @param WC_Order $order The dummy order object.
		 * @param string   $email_type The email type to preview.
		 *
		 * @since 9.6.0
		 */
		return apply_filters( 'woocommerce_email_preview_dummy_order', $order, $this->email_type );
	}

	/**
	 * Get a dummy product. Also used with `woocommerce_order_item_product` filter
	 * when email templates tries to get the product from the database.
	 *
	 * @return WC_Product
	 */
	private function get_dummy_product() {
		$product = new WC_Product();
		$product->set_name( __( 'Dummy Product', 'woocommerce' ) );
		$product->set_price( 25 );

		/**
		 * A dummy WC_Product used in email preview.
		 *
		 * @param WC_Product $product The dummy product object.
		 * @param string     $email_type The email type to preview.
		 *
		 * @since 9.6.0
		 */
		return apply_filters( 'woocommerce_email_preview_dummy_product', $product, $this->email_type );
	}

	/**
	 * Get a dummy product variation.
	 *
	 * @return WC_Product_Variation
	 */
	private function get_dummy_product_variation() {
		$variation = new WC_Product_Variation();
		$variation->set_name( __( 'Dummy Product Variation', 'woocommerce' ) );
		$variation->set_price( 20 );
		$variation->set_attributes(
			array(
				__( 'Color', 'woocommerce' ) => __( 'Red', 'woocommerce' ),
				__( 'Size', 'woocommerce' )  => __( 'Small', 'woocommerce' ),
			)
		);

		/**
		 * A dummy WC_Product_Variation used in email preview.
		 *
		 * @param WC_Product_Variation $variation The dummy product variation object.
		 * @param string               $email_type The email type to preview.
		 *
		 * @since 9.7.0
		 */
		return apply_filters( 'woocommerce_email_preview_dummy_product_variation', $variation, $this->email_type );
	}

	/**
	 * Get a dummy address.
	 *
	 * @return array
	 */
	private function get_dummy_address() {
		$address = array(
			'first_name' => 'John',
			'last_name'  => 'Doe',
			'company'    => 'Company',
			'email'      => 'john@company.com',
			'phone'      => '555-555-5555',
			'address_1'  => '123 Fake Street',
			'city'       => 'Faketown',
			'postcode'   => '12345',
			'country'    => 'US',
			'state'      => 'CA',
		);

		/**
		 * A dummy address used in email preview as billing and shipping one.
		 *
		 * @param array  $address The dummy address.
		 * @param string $email_type The email type to preview.
		 *
		 * @since 9.6.0
		 */
		return apply_filters( 'woocommerce_email_preview_dummy_address', $address, $this->email_type );
	}

	/**
	 * Get the placeholders for the email preview.
	 *
	 * @param WC_Order|WP_User $email_object The object to render email with.
	 * @return array
	 */
	private function get_placeholders( $email_object ) {
		$placeholders = array();

		if ( is_a( $email_object, 'WC_Order' ) ) {
			$placeholders['{order_date}']              = wc_format_datetime( $email_object->get_date_created() );
			$placeholders['{order_number}']            = $email_object->get_order_number();
			$placeholders['{order_billing_full_name}'] = $email_object->get_formatted_billing_full_name();
		}

		/**
		 * Placeholders for email preview.
		 *
		 * @param WC_Order $placeholders Placeholders for email subject.
		 * @param string   $email_type The email type to preview.
		 *
		 * @since 9.6.0
		 */
		return apply_filters( 'woocommerce_email_preview_placeholders', $placeholders, $this->email_type );
	}

	/**
	 * Set up filters for email preview.
	 */
	private function set_up_filters() {
		// Always show shipping address in the preview email.
		add_filter( 'woocommerce_order_needs_shipping_address', array( $this, 'enable_shipping_address' ) );
		// Email templates fetch product from the database to show additional information, which are not
		// saved in WC_Order_Item_Product. This filter enables fetching that data also in email preview.
		add_filter( 'woocommerce_order_item_product', array( $this, 'get_dummy_product_when_not_set' ), 10, 1 );
		// Enable email preview mode - this way transient values are fetched for live preview.
		add_filter( 'woocommerce_is_email_preview', array( $this, 'enable_preview_mode' ) );
		// Get shipping method without needing to save it in the order.
		add_filter( 'woocommerce_order_shipping_method', array( $this, 'get_shipping_method' ) );
		// Use placeholder image included in WooCommerce files.
		add_filter( 'woocommerce_order_item_thumbnail', array( $this, 'get_placeholder_image' ) );
	}

	/**
	 * Clean up filters after email preview.
	 */
	private function clean_up_filters() {
		remove_filter( 'woocommerce_order_needs_shipping_address', array( $this, 'enable_shipping_address' ) );
		remove_filter( 'woocommerce_order_item_product', array( $this, 'get_dummy_product_when_not_set' ), 10 );
		remove_filter( 'woocommerce_is_email_preview', array( $this, 'enable_preview_mode' ) );
		remove_filter( 'woocommerce_order_shipping_method', array( $this, 'get_shipping_method' ) );
		remove_filter( 'woocommerce_order_item_thumbnail', array( $this, 'get_placeholder_image' ) );
	}

	/**
	 * Get the shipping method for the preview email.
	 *
	 * @return string
	 */
	public function get_shipping_method() {
		return __( 'Flat rate', 'woocommerce' );
	}

	/**
	 * Enable shipping address in the preview email. Not using __return_true so
	 * we don't accidentally remove the same filter used by other plugin or theme.
	 *
	 * @return true
	 */
	public function enable_shipping_address() {
		return true;
	}

	/**
	 * Enable preview mode to use transient values in email-styles.php. Not using __return_true
	 * so we don't accidentally remove the same filter used by other plugin or theme.
	 *
	 * @return true
	 */
	public function enable_preview_mode() {
		return true;
	}

	/**
	 * Get the placeholder image for the preview email.
	 *
	 * @return string
	 */
	public function get_placeholder_image() {
		return '<img src="' . WC()->plugin_url() . '/assets/images/placeholder.png" width="48" height="48" alt="" />';
	}
}
