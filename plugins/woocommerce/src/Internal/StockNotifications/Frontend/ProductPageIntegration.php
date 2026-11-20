<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils as AddToCartWithOptionsUtils;
use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountEndpoint;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupService;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use WC_Product;
use WC_Product_Variation;

/**
 * Class for integrating with the product page.
 */
class ProductPageIntegration {

	/**
	 * Name of the Add to Cart + Options block.
	 */
	private const ADD_TO_CART_WITH_OPTIONS_BLOCK = 'woocommerce/add-to-cart-with-options';

	/**
	 * Script module that shows the form for the variation chosen in the Add to Cart + Options block.
	 */
	private const SCRIPT_MODULE = 'woocommerce/back-in-stock-form';

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
		add_filter( 'render_block_' . self::ADD_TO_CART_WITH_OPTIONS_BLOCK, array( $this, 'render_form_after_add_to_cart_with_options' ), 10, 3 );
	}

	/**
	 * Handle BIS form.
	 *
	 * @return void
	 */
	public function maybe_render_form() {

		// Add to Cart + Options buffers this hook inside its <form> and falls back to a
		// legacy HTML form when the buffer contains form elements. The form is appended
		// after the block instead, see render_form_after_add_to_cart_with_options().
		if ( $this->is_rendering_inside_add_to_cart_with_options() ) {
			return;
		}

		global $product;
		if ( is_a( $product, 'WC_Product' ) ) {
			$this->maybe_render_product_form( $product );
		}
	}

	/**
	 * Append the BIS form to the Add to Cart + Options block, outside its <form>.
	 *
	 * @internal
	 *
	 * @param string    $block_content The block content.
	 * @param array     $parsed_block  The parsed block.
	 * @param \WP_Block $block         The block instance.
	 * @return string
	 */
	public function render_form_after_add_to_cart_with_options( $block_content, $parsed_block, $block ) {
		$product_id = (int) ( $block->context['postId'] ?? 0 );

		// Only the block for the product being viewed, not those in related products, upsells or other products' Single Product blocks.
		$product = $product_id && get_queried_object_id() === $product_id ? wc_get_product( $product_id ) : null;
		if ( '' === $block_content || ! $product ) {
			return $block_content;
		}

		ob_start();
		$this->maybe_render_product_form( $product, true );

		return $this->add_interactivity_to_form( $block_content, (string) ob_get_clean(), $product );
	}

	/**
	 * Let the Interactivity API show the form for the variation chosen in the block and post its attributes.
	 *
	 * The template is left untouched: the directives are added to its rendered markup.
	 *
	 * @param string     $block_content The Add to Cart + Options block content.
	 * @param string     $form          The rendered form markup.
	 * @param WC_Product $product       Product object.
	 * @return string The block content followed by the form.
	 */
	private function add_interactivity_to_form( string $block_content, string $form, WC_Product $product ): string {
		$html = new \WP_HTML_Tag_Processor( $form );

		// The form of a variable product starts hidden until an out of stock variation is chosen.
		if ( ! $product instanceof \WC_Product_Variable || ! $html->next_tag( array( 'class_name' => 'wc_bis_form' ) ) || null === $html->get_attribute( 'hidden' ) ) {
			return $block_content . $form;
		}

		// The block renders no variation selector when the product can't be bought, so offer sign-ups for the parent.
		if ( AddToCartWithOptionsUtils::is_not_purchasable_product( $product ) ) {
			$html->remove_attribute( 'hidden' );

			return $block_content . $html->get_updated_html();
		}

		wp_enqueue_script_module( self::SCRIPT_MODULE );

		// The store namespace matches the script module ID.
		$html->set_attribute( 'data-wp-interactive', self::SCRIPT_MODULE );
		$html->set_attribute( 'data-wp-bind--hidden', '!state.isVisible' );

		$form_id = wp_unique_id( 'wc-bis-form-' );
		if ( $html->next_tag( array( 'tag_name' => 'form' ) ) ) {
			$html->set_attribute( 'id', $form_id );
		}

		while ( $html->next_tag( array( 'tag_name' => 'input' ) ) ) {
			if ( 'wc_bis_product_id' === $html->get_attribute( 'name' ) ) {
				$html->set_attribute( 'data-wp-bind--value', 'woocommerce/products::state.productInContext.id' );
				break;
			}
		}

		$form = $html->get_updated_html();

		// Post the chosen values, so "any" attributes are kept like in the classic form. The inputs sit inside
		// the block's <form> to read its selected attributes, and the `form` attribute submits them with the BIS form.
		$attribute_inputs = '';
		foreach ( array_keys( $product->get_variation_attributes() ) as $attribute_name ) {
			$attribute_name    = (string) $attribute_name;
			$attribute_inputs .= sprintf(
				'<input type="hidden" name="%s" form="%s" data-wp-interactive="%s" %s data-wp-bind--value="state.selectedAttributeValue" />',
				esc_attr( wc_variation_attribute_name( $attribute_name ) ),
				esc_attr( $form_id ),
				esc_attr( self::SCRIPT_MODULE ),
				wp_interactivity_data_wp_context( array( 'attribute' => wc_attribute_label( $attribute_name ) ) )
			);
		}

		// The block's own </form> comes first, as forms can't be nested.
		$block_form_end = stripos( $block_content, '</form>' );
		if ( false !== $block_form_end ) {
			$block_content = substr_replace( $block_content, $attribute_inputs, $block_form_end, 0 );
		}

		return $block_content . $form;
	}

	/**
	 * Render the BIS form for a product when it is eligible for sign-ups.
	 *
	 * @param WC_Product $product  Product object.
	 * @param bool       $is_block Whether the form renders for the Add to Cart + Options block.
	 * @return void
	 */
	private function maybe_render_product_form( WC_Product $product, bool $is_block = false ): void {

		if ( ! Config::allows_signups() || ! is_product() ) {
			return;
		}

		if ( isset( $this->rendered[ $product->get_id() ] ) ) {
			return;
		}

		// The block doesn't mark the product as rendered, as its output can be discarded.
		if ( ! $is_block ) {
			$this->rendered[ $product->get_id() ] = true;
		}

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

		// The block is driven by the Interactivity API, see add_interactivity_to_form().
		if ( ! $is_block ) {
			wp_enqueue_script( 'wc-back-in-stock-form' );
		}

		$this->render_form( $product );
	}

	/**
	 * Whether the hook is firing from inside the Add to Cart + Options render callback.
	 *
	 * WP_Block::render() sets WP_Block_Supports::$block_to_render around the callback,
	 * and the block fires its buffered template hooks before rendering inner blocks.
	 *
	 * @return bool
	 */
	private function is_rendering_inside_add_to_cart_with_options(): bool {
		return isset( \WP_Block_Supports::$block_to_render['blockName'] )
			&& self::ADD_TO_CART_WITH_OPTIONS_BLOCK === \WP_Block_Supports::$block_to_render['blockName'];
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

		if ( $this->is_visible_on_load( $product ) ) {
			$notification = $this->get_current_user_signup( $product );
			if ( $notification instanceof Notification ) {
				$this->display_already_signed_up( $product, $notification );
				return;
			}

			$this->display_form( $product );
			return;
		}

		// JS points a variable product's hidden form at the selected variation, and shows the
		// "already joined" notice instead for the variations listed on the form.
		$signups = $this->get_current_user_variation_signups( $product );
		$this->display_form( $product, array_keys( $signups ) );

		if ( $signups ) {
			echo '<div class="wc_bis_already_signed_up" data-bis-product-id="' . absint( $product->get_id() ) . '" hidden>';
			$this->display_already_signed_up( $product, reset( $signups ) );
			echo '</div>';
		}
	}

	/**
	 * Get the logged-in shopper's active sign-ups for a variable product's variations, keyed by variation ID.
	 *
	 * Matches get_current_user_signup() for each variation, with one query for all of them. In stock variations
	 * and those with 'any' attributes are skipped: a sign-up for 'any' attributes also stores the chosen values,
	 * which aren't known until the shopper picks them.
	 *
	 * @param WC_Product $product Variable product.
	 * @return array<int, Notification>
	 */
	private function get_current_user_variation_signups( WC_Product $product ): array {
		$variation_ids = $product->get_children();
		if ( ! $variation_ids || ! is_user_logged_in() || ! self::is_personalization_enabled() ) {
			return array();
		}

		$notification_ids = NotificationQuery::get_notifications(
			array(
				'status'     => NotificationStatus::ACTIVE,
				'product_id' => $variation_ids,
				'user_id'    => get_current_user_id(),
			)
		);

		$signups = array();
		foreach ( $notification_ids as $notification_id ) {
			$notification = Factory::get_notification( (int) $notification_id );
			$variation    = $notification instanceof Notification ? wc_get_product( $notification->get_product_id() ) : null;
			if (
				! $variation instanceof WC_Product_Variation
				|| isset( $signups[ $variation->get_id() ] )
				|| $this->eligibility_service->is_stock_status_eligible( $variation->get_stock_status() )
				|| in_array( '', $variation->get_variation_attributes(), true )
			) {
				continue;
			}

			$signups[ $variation->get_id() ] = $notification;
		}

		return $signups;
	}

	/**
	 * Get the logged-in shopper's active sign-up for a product, when personalization is enabled.
	 *
	 * A pending sign-up, or one made as a guest with the same email, keeps the form: submitting it activates
	 * the sign-up or resends its verification e-mail, and only the shopper's own sign-ups are listed in My Account.
	 *
	 * @param WC_Product $product Product object.
	 * @return Notification|null
	 */
	private function get_current_user_signup( WC_Product $product ): ?Notification {
		if ( ! is_user_logged_in() || ! self::is_personalization_enabled() ) {
			return null;
		}

		$user         = wp_get_current_user();
		$notification = $this->signup_service->is_already_signed_up( $product->get_id(), $user->ID, $user->user_email );

		if ( ! $notification instanceof Notification || NotificationStatus::ACTIVE !== $notification->get_status() || $user->ID !== (int) $notification->get_user_id() ) {
			return null;
		}

		return $notification;
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

		// The My Account login form turns `redirect_to` into its hidden `redirect` field, so the shopper comes back here after logging in.
		$login_url = add_query_arg( 'redirect_to', rawurlencode( $product->get_permalink() ), wc_get_page_permalink( 'myaccount' ) );

		$text = __( 'Please {login_link} to sign up for stock notifications.', 'woocommerce' );
		$text = str_replace( '{login_link}', '<a href="' . esc_url( $login_url ) . '">' . _x( 'log in', 'back in stock form', 'woocommerce' ) . '</a>', $text );
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

		// The endpoint can be switched off from Settings > Advanced, leaving nowhere to link to.
		if ( '' === MyAccountEndpoint::get_endpoint_slug() ) {
			wc_print_notice( __( 'You have already joined this waitlist.', 'woocommerce' ), 'notice' );
			return;
		}

		$text = __( 'You have already joined this waitlist. Click {manage_account_link} to manage your notifications.', 'woocommerce' );
		$text = str_replace( '{manage_account_link}', '<a href="' . esc_url( wc_get_account_endpoint_url( MyAccountEndpoint::ENDPOINT ) ) . '">' . _x( 'here', 'back in stock form', 'woocommerce' ) . '</a>', $text );
		wc_print_notice( $text, 'notice' );
	}

	/**
	 * Display the form.
	 *
	 * @param WC_Product $product                 Product object.
	 * @param int[]      $signed_up_variation_ids Variations the shopper already joined, listed on the form for JS.
	 * @return void
	 */
	public function display_form( WC_Product $product, array $signed_up_variation_ids = array() ): void {

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

		$args = array(
			'product_id'       => $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(),
			// Kept so template overrides that still read it don't raise an undefined-variable warning.
			'show_checkbox'    => false,
			'show_email_field' => ! is_user_logged_in() && ! Config::requires_account(),
			'button_class'     => $button_class,
			'is_visible'       => $this->is_visible_on_load( $product ),
		);

		// Added to the rendered markup, so template overrides get it too.
		$html = new \WP_HTML_Tag_Processor( wc_get_template_html( 'single-product/back-in-stock-form.php', $args ) );
		if ( $signed_up_variation_ids && $html->next_tag( array( 'class_name' => 'wc_bis_form' ) ) ) {
			$html->set_attribute( 'data-bis-signed-up-variations', (string) wp_json_encode( $signed_up_variation_ids ) );
		}

		echo $html->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template markup, the attribute is escaped by the tag processor.
	}

	/**
	 * Whether the form is visible on page load, rather than revealed by JS for a selected variation.
	 *
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	private function is_visible_on_load( WC_Product $product ): bool {
		// When a variable has no purchasable variations, allow for signups on the parent product.
		return ! $product->is_type( 'variable' ) || ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $product->has_purchasable_variations() );
	}

	/**
	 * Whether personalization is enabled.
	 *
	 * Personalization includes checking if the user is already signed up and displaying the 'already signed up' message.
	 * It only changes the output for logged-in shoppers, whose pages full-page caches don't serve from cache. Sites
	 * that cache logged-in pages across users can turn it off with the filter.
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
		return (bool) apply_filters( 'woocommerce_customer_stock_notifications_personalization_enabled', true );
	}
}
