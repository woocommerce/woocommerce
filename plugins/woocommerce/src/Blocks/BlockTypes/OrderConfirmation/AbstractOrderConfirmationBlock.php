<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * AbstractOrderConfirmationBlock class.
 */
abstract class AbstractOrderConfirmationBlock extends AbstractBlock {
	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		parent::initialize();
		add_action( 'wp_loaded', array( $this, 'register_patterns' ) );
	}

	/**
	 * Get the content from a hook and return it.
	 *
	 * @param string $hook Hook name.
	 * @param array  $args Array of args to pass to the hook.
	 * @return string
	 */
	protected function get_hook_content( $hook, $args ) {
		ob_start();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action_ref_array( $hook, $args );
		return ob_get_clean();
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string | void Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$order              = $this->get_order();
		$permission         = $this->get_view_order_permissions( $order );
		$block_content      = $order ? $this->render_content( $order, $permission, $attributes, $content ) : $this->render_content_fallback();
		$classname          = $attributes['className'] ?? '';
		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );

		if ( ! empty( $classes_and_styles['classes'] ) ) {
			$classname .= ' ' . $classes_and_styles['classes'];
		}

		return $block_content ? sprintf(
			'<div class="wc-block-%4$s %1$s" style="%2$s">%3$s</div>',
			esc_attr( trim( $classname ) ),
			esc_attr( $classes_and_styles['styles'] ),
			$block_content,
			esc_attr( $this->block_name )
		) : '';
	}

	/**
	 * This renders the content of the block within the wrapper. The permission determines what data can be shown under
	 * the given context.
	 *
	 * @param \WC_Order $order Order object.
	 * @param string    $permission Permission level for viewing order details.
	 * @param array     $attributes Block attributes.
	 * @param string    $content Original block content.
	 * @return string
	 */
	abstract protected function render_content( $order, $permission = false, $attributes = [], $content = '' );

	/**
	 * This is what gets rendered when the order does not exist. Renders nothing by default, but can be overridden by
	 * child classes.
	 *
	 * @return string
	 */
	protected function render_content_fallback() {
		return '';
	}

	/**
	 * Get current order.
	 *
	 * @return \WC_Order|null
	 */
	protected function get_order() {
		$order_id = absint( get_query_var( 'order-received' ) );

		if ( $order_id ) {
			return wc_get_order( $order_id );
		}

		return null;
	}

	/**
	 * View mode for order details based on the order, current user, and settings.
	 *
	 * Possible values are:
	 * - "full" user can view all order details.
	 * - "limited" user can view some order details, but no PII. This may happen for example, if the user checked out as a guest.
	 * - false user cannot view order details.
	 *
	 * @param \WC_Order|null $order Order object.
	 * @return "full"|"limited"|false
	 */
	protected function get_view_order_permissions( $order ) {
		if ( ! $order || ! $this->has_valid_order_key( $order ) ) {
			return false; // Always disallow access to invalid orders and those without a valid key.
		}

		// For customers with accounts, verify the order belongs to the current user or disallow access.
		if ( $this->is_customer_order( $order ) ) {
			return $this->is_current_customer_order( $order ) ? 'full' : false;
		}

		// Guest orders are displayed with limited information.
		return $this->email_verification_required( $order ) ? false : 'limited';
	}

	/**
	 * See if guest checkout is enabled.
	 *
	 * @return boolean
	 */
	protected function allow_guest_checkout() {
		return 'yes' === get_option( 'woocommerce_enable_guest_checkout' );
	}

	/**
	 * Guest users without an active session can provide their email address to view order details. This however can only
	 * be permitted if the user also provided the correct order key, and guest checkout is actually enabled.
	 *
	 * @param \WC_Order $order Order object.
	 * @return boolean
	 */
	protected function email_verification_permitted( $order ) {
		return $this->allow_guest_checkout() && $this->has_valid_order_key( $order ) && ! $this->is_customer_order( $order );
	}

	/**
	 * See if we need to verify the email address before showing the order details.
	 *
	 * @param \WC_Order $order Order object.
	 * @return boolean
	 */
	protected function email_verification_required( $order ) {
		// Skip verification if the current user still has the order in their session.
		if ( $order->get_id() === wc()->session->get( 'store_api_draft_order' ) ) {
			return false;
		}

		/**
		 * Controls the grace period within which we do not require any sort of email verification step before rendering
		 * the 'order received' or 'order pay' pages.
		 *
		 * @see \WC_Shortcode_Checkout::order_received()
		 * @since 11.4.0
		 * @param int      $grace_period Time in seconds after an order is placed before email verification may be required.
		 * @param \WC_Order $order        The order for which this grace period is being assessed.
		 * @param string   $context      Indicates the context in which we might verify the email address. Typically 'order-pay' or 'order-received'.
		 */
		$verification_grace_period = (int) apply_filters( 'woocommerce_order_email_verification_grace_period', 10 * MINUTE_IN_SECONDS, $order, 'order-received' );
		$date_created              = $order->get_date_created();

		// We do not need to verify the email address if we are within the grace period immediately following order creation.
		if ( is_a( $date_created, \WC_DateTime::class ) && time() - $date_created->getTimestamp() <= $verification_grace_period ) {
			return false;
		}

		$session       = wc()->session;
		$session_email = '';
		$session_order = 0;

		if ( is_a( $session, \WC_Session::class ) ) {
			$customer      = $session->get( 'customer' );
			$session_email = is_array( $customer ) && isset( $customer['email'] ) ? sanitize_email( $customer['email'] ) : '';
			$session_order = (int) $session->get( 'store_api_draft_order' );
		}

		// We do not need to verify the email address if the user still has the order in session.
		if ( $order->get_id() === $session_order ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( ! empty( $_POST ) && ! wp_verify_nonce( $_POST['check_submission'] ?? '', 'wc_verify_email' ) ) {
			return true;
		}

		$session_email_match  = $session_email === $order->get_billing_email();
		$supplied_email_match = isset( $_POST['email'] ) && sanitize_email( wp_unslash( $_POST['email'] ) ?? '' ) === $order->get_billing_email();

		// If we cannot match the order with the current user, the user should verify their email address.
		$email_verification_required = ! $session_email_match && ! $supplied_email_match;

		/**
		 * Provides an opportunity to override the (potential) requirement for shoppers to verify their email address
		 * before we show information such as the order summary, or order payment page.
		 *
		 * @see \WC_Shortcode_Checkout::order_received()
		 * @since 11.4.0
		 * @param bool     $email_verification_required If email verification is required.
		 * @param WC_Order $order                       The relevant order.
		 * @param string   $context                     The context under which we are performing this check.
		 */
		return (bool) apply_filters( 'woocommerce_order_email_verification_required', $email_verification_required, $order, 'order-received' );
	}

	/**
	 * See if the order key is valid.
	 *
	 * @param \WC_Order $order Order object.
	 * @return boolean
	 */
	protected function has_valid_order_key( $order ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return ! empty( $_GET['key'] ) && $order->key_is_valid( wc_clean( wp_unslash( $_GET['key'] ) ) );
	}

	/**
	 * See if the current order came from a guest or a logged in customer.
	 *
	 * @param \WC_Order $order Order object.
	 * @return boolean
	 */
	protected function is_customer_order( $order ) {
		return 0 < $order->get_user_id();
	}

	/**
	 * See if the current logged in user ID matches the given order customer ID.
	 *
	 * Returns false for logged-out customers.
	 *
	 * @param \WC_Order $order Order object.
	 * @return boolean
	 */
	protected function is_current_customer_order( $order ) {
		return $this->is_customer_order( $order ) && $order->get_user_id() === get_current_user_id();
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Register block pattern for Order Confirmation to make it translatable.
	 */
	public function register_patterns() {

		register_block_pattern(
			'woocommerce/order-confirmation-totals-heading',
			array(
				'title'    => '',
				'inserter' => false,
				'content'  => '<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"24px"}}} --><h3 class="wp-block-heading" style="font-size:24px">' . esc_html__( 'Order details', 'woocommerce' ) . '</h3><!-- /wp:heading -->',
			)
		);

		register_block_pattern(
			'woocommerce/order-confirmation-downloads-heading',
			array(
				'title'    => '',
				'inserter' => false,
				'content'  => '<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"24px"}}} --><h3 class="wp-block-heading" style="font-size:24px">' . esc_html__( 'Downloads', 'woocommerce' ) . '</h3><!-- /wp:heading -->',
			)
		);

		register_block_pattern(
			'woocommerce/order-confirmation-shipping-heading',
			array(
				'title'    => '',
				'inserter' => false,
				'content'  => '<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"24px"}}} --><h3 class="wp-block-heading" style="font-size:24px">' . esc_html__( 'Shipping address', 'woocommerce' ) . '</h3><!-- /wp:heading -->',
			)
		);

		register_block_pattern(
			'woocommerce/order-confirmation-billing-heading',
			array(
				'title'    => '',
				'inserter' => false,
				'content'  => '<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"24px"}}} --><h3 class="wp-block-heading" style="font-size:24px">' . esc_html__( 'Billing address', 'woocommerce' ) . '</h3><!-- /wp:heading -->',
			)
		);

	}
}
