<?php
/**
 * Class WC_Email_Customer_Cart_Recovery file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email_Customer_Cart_Recovery', false ) ) :

	/**
	 * Customer cart recovery email.
	 *
	 * Sent once to a shopper who typed an email at checkout and left without ordering.
	 * Capture, scheduling and eligibility live in `Automattic\WooCommerce\Internal\CartRecovery\CartRecovery`.
	 *
	 * @since 11.3.0
	 */
	class WC_Email_Customer_Cart_Recovery extends WC_Email {

		/**
		 * Shortest allowed send delay, in minutes.
		 */
		public const MIN_DELAY_MINUTES = 15;

		/**
		 * Longest allowed send delay, in minutes. Stays under the 48-hour guest session lifetime.
		 */
		public const MAX_DELAY_MINUTES = 1380;

		/**
		 * Default send delay, in minutes.
		 */
		public const DEFAULT_DELAY_MINUTES = 60;

		/**
		 * Cart items to list, each with a `product` and a `quantity`.
		 *
		 * @var array<int, array{product: WC_Product, quantity: int}>
		 */
		public $items = array();

		/**
		 * Link that rebuilds the cart.
		 *
		 * @var string
		 */
		public $recovery_url = '';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_cart_recovery';
			$this->customer_email = true;
			$this->title          = __( 'Cart recovery', 'woocommerce' );
			$this->email_group    = 'orders';
			$this->template_html  = 'emails/customer-cart-recovery.php';
			$this->template_plain = 'emails/plain/customer-cart-recovery.php';
			$this->placeholders   = array();

			parent::__construct();

			// Must be after the parent constructor, which sets `block_email_editor_enabled`.
			$this->description = __( 'Sent once to shoppers who enter their email at checkout and leave without placing an order. Links back to their cart.', 'woocommerce' );
		}

		/**
		 * Get default email subject.
		 *
		 * @since 11.3.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'You left something in your cart at {site_title}', 'woocommerce' );
		}

		/**
		 * Get default email heading.
		 *
		 * @since 11.3.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Your cart is waiting', 'woocommerce' );
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 11.3.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'If you have any questions, reply to this email and we\'ll help out.', 'woocommerce' );
		}

		/**
		 * Keep only product category IDs that exist.
		 *
		 * @since 11.3.0
		 *
		 * @param string $key   Field key.
		 * @param mixed  $value Posted value.
		 * @return int[]
		 */
		public function validate_excluded_categories_field( $key, $value ) {
			if ( ! is_array( $value ) ) {
				return array();
			}

			$term_ids = array_filter( array_map( 'absint', $value ) );

			return array_values(
				array_filter(
					$term_ids,
					static function ( $term_id ) {
						return (bool) term_exists( $term_id, 'product_cat' );
					}
				)
			);
		}

		/**
		 * Keep only registered role slugs.
		 *
		 * @since 11.3.0
		 *
		 * @param string $key   Field key.
		 * @param mixed  $value Posted value.
		 * @return string[]
		 */
		public function validate_excluded_roles_field( $key, $value ) {
			if ( ! is_array( $value ) ) {
				return array();
			}

			return array_values( array_intersect( array_map( 'strval', $value ), array_keys( wp_roles()->get_names() ) ) );
		}

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields(): void {
			/* translators: %s: list of placeholders */
			$placeholder_text = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>{site_title}</code>' );

			// Runs on every mailer load, so only build the option lists where the settings UI or REST API needs them.
			$load_options = is_admin() || WC()->is_rest_api_request();

			$this->form_fields = array(
				'enabled'             => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'woocommerce' ),
					'default' => 'no',
				),
				'delay_minutes'       => array(
					'title'             => __( 'Send after (minutes)', 'woocommerce' ),
					'type'              => 'number',
					'description'       => __( 'How long after the shopper\'s last activity to send the email.', 'woocommerce' ),
					'default'           => (string) self::DEFAULT_DELAY_MINUTES,
					'desc_tip'          => true,
					'custom_attributes' => array(
						'min'  => (string) self::MIN_DELAY_MINUTES,
						'max'  => (string) self::MAX_DELAY_MINUTES,
						'step' => '1',
					),
				),
				'min_cart_total'      => array(
					'title'       => __( 'Minimum cart total', 'woocommerce' ),
					'type'        => 'price',
					'description' => __( 'Only email carts worth at least this amount. Leave at 0 to email every cart.', 'woocommerce' ),
					'default'     => '0',
					'desc_tip'    => true,
				),
				'excluded_categories' => array(
					'title'       => __( 'Skip carts with products from', 'woocommerce' ),
					'type'        => 'multiselect',
					'class'       => 'wc-enhanced-select',
					'description' => __( 'No email is sent when the cart has a product from one of these categories or their subcategories.', 'woocommerce' ),
					'default'     => array(),
					'options'     => $load_options ? $this->get_category_options() : array(),
					'desc_tip'    => true,
				),
				'excluded_roles'      => array(
					'title'       => __( 'Skip customers with role', 'woocommerce' ),
					'type'        => 'multiselect',
					'class'       => 'wc-enhanced-select',
					'description' => __( 'No email is sent to logged-in customers with one of these roles.', 'woocommerce' ),
					'default'     => array(),
					'options'     => $load_options ? wp_roles()->get_names() : array(),
					'desc_tip'    => true,
				),
				'subject'             => array(
					'title'       => __( 'Subject', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'             => array(
					'title'       => __( 'Email heading', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'additional_content'  => array(
					'title'       => __( 'Additional content', 'woocommerce' ),
					'description' => __( 'Text to appear below the main email content.', 'woocommerce' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => __( 'N/A', 'woocommerce' ),
					'type'        => 'textarea',
					'default'     => $this->get_default_additional_content(),
					'desc_tip'    => true,
				),
				'email_type'          => array(
					'title'       => __( 'Email type', 'woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);

			if ( FeaturesUtil::feature_is_enabled( 'email_improvements' ) ) {
				$this->form_fields['cc']  = $this->get_cc_field();
				$this->form_fields['bcc'] = $this->get_bcc_field();
			}
			if ( $this->block_email_editor_enabled ) {
				$this->form_fields['preheader'] = $this->get_preheader_field();
			}
		}

		/**
		 * Product categories as term ID => name.
		 *
		 * @return array<int, string>
		 */
		private function get_category_options(): array {
			$terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'fields'     => 'id=>name',
				)
			);

			return is_array( $terms ) ? $terms : array();
		}
	}

endif;

return new WC_Email_Customer_Cart_Recovery();
