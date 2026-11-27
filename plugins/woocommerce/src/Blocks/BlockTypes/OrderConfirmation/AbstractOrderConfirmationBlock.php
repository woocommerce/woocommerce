<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Internal\Orders\OrderReceivedAccess;

/**
 * AbstractOrderConfirmationBlock class.
 */
abstract class AbstractOrderConfirmationBlock extends AbstractBlock {
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
		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );

		return $block_content ? sprintf(
			'<div class="wp-block-%5$s-%4$s wc-block-%4$s %1$s" style="%2$s">%3$s</div>',
			esc_attr( $classes_and_styles['classes'] ),
			esc_attr( $classes_and_styles['styles'] ),
			$block_content,
			esc_attr( $this->block_name ),
			esc_attr( $this->namespace )
		) : '';
	}

	/**
	 * This renders the content of the block within the wrapper. The permission determines what data can be shown under
	 * the given context.
	 *
	 * @param \WC_Order    $order Order object.
	 * @param string|false $permission If the current user can view the order details or not.
	 * @param array        $attributes Block attributes.
	 * @param string       $content Original block content.
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
	 * @param \WC_Order|null $order Order object.
	 * @return string|false Returns "full" if the user can view all order details. False if they can view no details.
	 */
	protected function get_view_order_permissions( $order ) {
		if ( ! $order || ! $this->has_valid_order_key( $order ) ) {
			return false; // Always disallow access to invalid orders and those without a valid key.
		}

		if ( $this->get_order_received_access()->current_user_can_view_order( $order, $this->get_request_order_key() ) ) {
			return 'full';
		}

		// Only the shared rule can unlock a customer order by email, even when a subclass overrides email_verification_permitted().
		if ( $order->get_customer_id() && ! $this->get_order_received_access()->guest_can_unlock_order( $order, $this->get_request_order_key() ) ) {
			return false;
		}

		return $this->email_verification_permitted( $order ) && $this->is_email_verified( $order ) ? 'full' : false;
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
	 * See if a guest can unlock the order details by entering the billing email address.
	 *
	 * @param \WC_Order $order Order object.
	 * @return boolean
	 */
	protected function email_verification_permitted( $order ) {
		return $this->get_order_received_access()->guest_can_unlock_order( $order, $this->get_request_order_key() );
	}

	/**
	 * See if the order was created within the grace period for viewing details.
	 *
	 * @param \WC_Order $order Order object.
	 * @return boolean
	 */
	protected function is_within_grace_period( $order ) {
		return $this->get_order_received_access()->is_within_grace_period( $order );
	}

	/**
	 * Returns true if the email has been verified (posted email matches given order email).
	 *
	 * @param \WC_Order $order Order object.
	 * @return boolean
	 */
	protected function is_email_verified( $order ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( empty( $_POST ) || ! isset( $_POST['email'], $_POST['_wpnonce'] ) || ! is_string( $_POST['email'] ) ) {
			return false;
		}

		$nonce_value = sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) );

		if ( ! wp_verify_nonce( $nonce_value, 'wc_verify_email' ) && ! wp_verify_nonce( $nonce_value, 'wc_create_account' ) ) {
			return false;
		}

		return $this->get_order_received_access()->email_unlocks_order( $order, sanitize_email( wp_unslash( $_POST['email'] ) ) );
	}

	/**
	 * See if we need to verify the email address before showing the order details.
	 *
	 * @param \WC_Order $order Order object.
	 * @return boolean
	 */
	protected function email_verification_required( $order ) {
		return ! $this->get_order_received_access()->current_user_can_view_order( $order, $this->get_request_order_key() ) && ! $this->is_email_verified( $order );
	}

	/**
	 * See if the order key is valid.
	 *
	 * @param \WC_Order $order Order object.
	 * @return boolean
	 */
	protected function has_valid_order_key( $order ) {
		return $order->key_is_valid( $this->get_request_order_key() );
	}

	/**
	 * Get the order key from the request.
	 *
	 * @return string
	 */
	private function get_request_order_key(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
	}

	/**
	 * Get the rules that decide who can see the order.
	 *
	 * @return OrderReceivedAccess
	 */
	private function get_order_received_access(): OrderReceivedAccess {
		return wc_get_container()->get( OrderReceivedAccess::class );
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
	 * Render custom fields for the order.
	 *
	 * @param array $fields List of additional fields with values.
	 * @return string
	 */
	protected function render_additional_fields( $fields ) {
		if ( empty( $fields ) ) {
			return '';
		}
		return '<dl class="wc-block-components-additional-fields-list">' . implode( '', array_map( array( $this, 'render_additional_field' ), $fields ) ) . '</dl>';
	}

	/**
	 * Render custom field row.
	 *
	 * @param array $field An additional field and value.
	 * @return string
	 */
	protected function render_additional_field( $field ) {
		return sprintf(
			'<dt>%1$s</dt><dd>%2$s</dd>',
			esc_html( $field['label'] ),
			esc_html( $field['value'] )
		);
	}
}
