<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\StoreApi\Utilities\OrderController;

/**
 * CreateAccount class.
 */
class CreateAccount extends AbstractOrderConfirmationBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-create-account';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_script( $key = null ) {
		$script = [
			'handle'       => 'wc-order-confirmation-create-account-block-frontend',
			'path'         => $this->asset_api->get_block_asset_build_path( 'order-confirmation-create-account-frontend' ),
			'dependencies' => [],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Process posted account form.
	 *
	 * @param \WC_Order $order Order object.
	 * @return \WP_Error|int
	 */
	protected function process_form_post( $order ) {
		if ( ! isset( $_POST['create-account'], $_POST['email'], $_POST['password'], $_POST['_wpnonce'] ) ) {
			return 0;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'wc_create_account' ) ) {
			return new \WP_Error( 'invalid_nonce', __( 'Unable to create account. Please try again.', 'woocommerce' ) );
		}

		$user_email = sanitize_email( wp_unslash( $_POST['email'] ) );
		$password   = wp_unslash( $_POST['password'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Does order already have user?
		if ( $order->get_customer_id() ) {
			return new \WP_Error( 'order_already_has_user', __( 'This order is already linked to a user account.', 'woocommerce' ) );
		}

		// Check given details match the current viewed order.
		if ( $order->get_billing_email() !== $user_email ) {
			return new \WP_Error( 'email_mismatch', __( 'The email address provided does not match the email address on this order.', 'woocommerce' ) );
		}

		if ( empty( $password ) || strlen( $password ) < 8 ) {
			return new \WP_Error( 'password_too_short', __( 'Password must be at least 8 characters.', 'woocommerce' ) );
		}

		$customer_id = wc_create_new_customer(
			$user_email,
			'',
			$password,
			[
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
				'source'     => 'delayed-account-creation',
			]
		);

		if ( is_wp_error( $customer_id ) ) {
			return $customer_id;
		}

		// Associate customer with the order.
		$order->set_customer_id( $customer_id );
		$order->save();

		// Associate addresses from the order with the customer.
		$order_controller = new OrderController();
		$order_controller->sync_customer_data_with_order( $order );

		// Set the customer auth cookie.
		wc_set_customer_auth_cookie( $customer_id );

		return $customer_id;
	}

	/**
	 * This renders the content of the block within the wrapper.
	 *
	 * @param \WC_Order    $order Order object.
	 * @param string|false $permission If the current user can view the order details or not.
	 * @param array        $attributes Block attributes.
	 * @param string       $content Original block content.
	 * @return string
	 */
	protected function render_content( $order, $permission = false, $attributes = [], $content = '' ) {
		if ( ! $permission ) {
			return '';
		}

		// Check registration is possible for this order/customer, and if not, return early.
		if ( is_user_logged_in() || email_exists( $order->get_billing_email() ) ) {
			return '';
		}

		$result = $this->process_form_post( $order );

		if ( is_wp_error( $result ) ) {
			$notice = wc_print_notice( $result->get_error_message(), 'error', [], true );
		} elseif ( $result ) {
			return $this->render_confirmation();
		}

		$processor = new \WP_HTML_Tag_Processor(
			$content .
			'<div class="woocommerce-order-confirmation-create-account-form-wrapper">' .
				$notice .
				'<div class="woocommerce-order-confirmation-create-account-form"></div>' .
			'</div>'
		);

		if ( ! $processor->next_tag( array( 'class_name' => 'wp-block-woocommerce-order-confirmation-create-account' ) ) ) {
			return $content;
		}

		$processor->set_attribute( 'class', '' );
		$processor->set_attribute( 'style', '' );
		$processor->add_class( 'woocommerce-order-confirmation-create-account-content' );

		if ( ! $processor->next_tag( array( 'class_name' => 'woocommerce-order-confirmation-create-account-form' ) ) ) {
			return $content;
		}

		$processor->set_attribute( 'data-customer-email', $order->get_billing_email() );
		$processor->set_attribute( 'data-nonce-token', wp_create_nonce( 'wc_create_account' ) );

		if ( ! empty( $attributes['hasDarkControls'] ) ) {
			$processor->add_class( 'has-dark-controls' );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Render the block when an account has been registered.
	 *
	 * @return string
	 */
	protected function render_confirmation() {
		$content  = '<div class="woocommerce-order-confirmation-create-account-success" id="create-account">';
		$content .= '<h3>' . esc_html__( 'Your account has been successfully created', 'woocommerce' ) . '</h3>';
		$content .= '<p>' . sprintf(
			/* translators: 1: link to my account page, 2: link to shipping and billing addresses, 3: link to account details, 4: closing tag */
			esc_html__( 'You can now %1$sview your recent orders%4$s, manage your %2$sshipping and billing addresses%4$s, and edit your %3$spassword and account details%4$s.', 'woocommerce' ),
			'<a href="' . esc_url( wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ) ) . '">',
			'<a href="' . esc_url( wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ) ) . '">',
			'<a href="' . esc_url( wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) ) ) . '">',
			'</a>'
		) . '</p>';
		$content .= '</div>';

		return $content;
	}
}
