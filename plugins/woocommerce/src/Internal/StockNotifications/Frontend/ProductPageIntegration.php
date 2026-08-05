<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupService;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use WC_Product;

/**
 * Class for integrating with the product page.
 */
class ProductPageIntegration {

	/**
	 * Runtime cache for preventing double rendering.
	 *
	 * @var array<int, bool>
	 */
	private array $rendered = array();

	/**
	 * The eligibility service instance.
	 *
	 * @var EligibilityService
	 */
	private EligibilityService $eligibility_service;

	/**
	 * The signup service instance.
	 *
	 * @var SignupService
	 */
	private SignupService $signup_service;

	/**
	 * Init.
	 *
	 * @internal
	 *
	 * @param EligibilityService $eligibility_service The eligibility service instance.
	 * @param SignupService      $signup_service The signup service instance.
	 */
	final public function init( EligibilityService $eligibility_service, SignupService $signup_service ): void {
		$this->eligibility_service = $eligibility_service;
		$this->signup_service      = $signup_service;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_simple_add_to_cart', array( $this, 'maybe_render_form' ), 30 );
		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'maybe_render_form' ), 30 );
	}

	/**
	 * Handle BIS form.
	 *
	 * @return void
	 */
	public function maybe_render_form() {

		if ( ! Config::allows_signups() ) {
			return;
		}

		global $product;
		if ( ! is_product() || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		if ( isset( $this->rendered[ $product->get_id() ] ) ) {
			return;
		}

		$this->rendered[ $product->get_id() ] = true;

		$is_variable = $product->is_type( 'variable' );
		// Check if the product is in stock.
		// Hint: This is negative logic. If the product is eligible for notifications, skip rendering.
		// We avoid checking for variable products here because we want to render the form for out of stock variations.
		if ( ! $is_variable && $this->eligibility_service->is_stock_status_eligible( $product->get_stock_status() ) ) {
			return;
		}

		if ( ! $this->eligibility_service->is_product_eligible( $product ) ) {
			return;
		}

		if ( ! $this->eligibility_service->product_allows_signups( $product ) ) {
			return;
		}

		// Enqueue the script.
		wp_enqueue_script( 'wc-back-in-stock-form' );

		$this->render_form( $product );
	}

	/**
	 * Render the form.
	 *
	 * @param WC_Product $product Product object.
	 * @return void
	 */
	private function render_form( WC_Product $product ): void {

		// Check if requires account.
		if ( Config::requires_account() && ! is_user_logged_in() ) {
			$this->display_account_required( $product );
			return;
		}

		// Check if already signed up.
		if ( $this->is_personalization_enabled() && is_user_logged_in() ) {
			$user         = \get_user_by( 'id', \get_current_user_id() );
			$notification = $this->signup_service->is_already_signed_up( $product->get_id(), $user->ID, $user->user_email );
			if ( $notification instanceof Notification ) {
				$this->display_already_signed_up( $product, $notification );
				return;
			}
		}

		$this->display_form( $product );
	}

	/**
	 * Display the account required message.
	 *
	 * @param WC_Product $product Product object.
	 * @return void
	 */
	public function display_account_required( WC_Product $product ): void {

		/**
		 * Filter the account required message HTML.
		 *
		 * @since 10.2.0
		 *
		 * @param string|null $pre The message.
		 * @param WC_Product  $product Product object.
		 * @return string|null The message.
		 */
		$pre = apply_filters( 'woocommerce_customer_stock_notifications_account_required_message_html', null, $product );
		if ( ! is_null( $pre ) ) {
			echo wp_kses_post( $pre );
			return;
		}

		$text = __( 'Please {login_link} to sign up for stock notifications.', 'woocommerce' );
		$text = str_replace( '{login_link}', '<a href="' . wc_get_account_endpoint_url( 'my-account' ) . '">' . _x( 'log in', 'back in stock form', 'woocommerce' ) . '</a>', $text );
		wc_print_notice( $text, 'notice' );
	}

	/**
	 * Display the already signed up message.
	 *
	 * @param WC_Product   $product Product object.
	 * @param Notification $notification Notification object.
	 * @return void
	 */
	public function display_already_signed_up( WC_Product $product, Notification $notification ): void {

		/**
		 * Filter the already signed up message HTML.
		 *
		 * @since 10.2.0
		 *
		 * @param string|null  $pre The message.
		 * @param WC_Product   $product Product object.
		 * @param Notification $notification Notification object.
		 * @return string|null The message.
		 */
		$pre = apply_filters( 'woocommerce_customer_stock_notifications_already_signed_up_message_html', null, $product, $notification );
		if ( ! is_null( $pre ) ) {
			echo wp_kses_post( $pre );
			return;
		}

		$text = __( 'You have already joined the waitlist! Click {manage_account_link} to manage your notifications.', 'woocommerce' );
		$text = str_replace( '{manage_account_link}', '<a href="' . wc_get_account_endpoint_url( 'stock-notifications' ) . '">' . _x( 'here', 'back in stock form', 'woocommerce' ) . '</a>', $text );
		wc_print_notice( $text, 'notice' );
	}

	/**
	 * Display the form.
	 *
	 * @param WC_Product $product Product object.
	 * @return void
	 */
	public function display_form( WC_Product $product ): void {

		$button_class = implode(
			' ',
			array_filter(
				array(
					'button',
					\wc_wp_theme_get_element_class_name( 'button' ),
					'wc_bis_form__button',
				)
			)
		);

		// When a variable has no purchasable variations, allow for signups on the parent product.
		$is_visible = ! $product->is_type( 'variable' ) || ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $product->has_purchasable_variations() );

		wc_get_template(
			'single-product/back-in-stock-form.php',
			array(
				'product_id'       => $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(),
				'show_checkbox'    => ! is_user_logged_in() && Config::creates_account_on_signup() && ! Config::requires_account(),
				'show_email_field' => ! is_user_logged_in() && ! Config::requires_account(),
				'button_class'     => $button_class,
				'is_visible'       => $is_visible,
			)
		);
	}

	/**
	 * Whether personalization is enabled.
	 *
	 * Personalization includes checking if the user is already signed up and displaying the 'already signed up' message.
	 *
	 * @return bool True if personalization is enabled, false otherwise.
	 */
	public static function is_personalization_enabled(): bool {

		/**
		 * Filter whether personalization is enabled while rendering the form.
		 *
		 * @since 10.2.0
		 *
		 * @param bool $enabled Whether personalization is enabled.
		 * @return bool
		 */
		return (bool) apply_filters( 'woocommerce_customer_stock_notifications_personalization_enabled', false );
	}
}
