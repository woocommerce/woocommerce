<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\Admin\Features\Features;

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
	 * Initialize this block type.
	 */
	protected function initialize() {
		parent::initialize();

		if ( $this->is_feature_enabled() ) {
			$this->initialize_hooks();
		}
	}

	/**
	 * Initialize hooks.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/hooked_block/
	 */
	protected function initialize_hooks() {
		// This does not use the Block Hooks Trait used in mini cart. The implementation is simpler because we support
		// versions higher than WP 6.5 when hooks were introduced. They should be consolidated in the future.
		add_filter(
			'hooked_block_types',
			function ( $hooked_block_types, $relative_position, $anchor_block_type, $context ) {
				if ( 'after' !== $relative_position || 'woocommerce/order-confirmation-summary' !== $anchor_block_type || ! $context instanceof \WP_Block_Template ) {
					return $hooked_block_types;
				}

				if ( ! str_contains( $context->content, '<!-- wp:' . $this->get_full_block_name() ) ) {
					$hooked_block_types[] = $this->get_full_block_name();
				}

				return $hooked_block_types;
			},
			10,
			4
		);
		add_filter(
			'hooked_block_woocommerce/order-confirmation-create-account',
			function ( $parsed_hooked_block, $hooked_block_type, $relative_position ) {
				if ( 'after' !== $relative_position || is_null( $parsed_hooked_block ) ) {
					return $parsed_hooked_block;
				}

				/* translators: %s: Site title */
				$site_title_heading                  = sprintf( __( 'Create an account with %s', 'woocommerce' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );
				$parsed_hooked_block['innerContent'] = array(
					'<div class="wp-block-woocommerce-order-confirmation-create-account alignwide">
					<!-- wp:heading {"level":3} -->
                    <h3 class="wp-block-heading">' . esc_html( $site_title_heading ) . '</h3>
					<!-- /wp:heading -->
					<!-- wp:list {"className":"is-style-checkmark-list"} -->
					<ul class="wp-block-list is-style-checkmark-list"><!-- wp:list-item -->
                    <li>' . esc_html__( 'Faster future purchases', 'woocommerce' ) . '</li>
                    <!-- /wp:list-item -->
                    <!-- wp:list-item -->
                    <li>' . esc_html__( 'Securely save payment info', 'woocommerce' ) . '</li>
                    <!-- /wp:list-item -->
                    <!-- wp:list-item -->
                    <li>' . esc_html__( 'Track orders &amp; view shopping history', 'woocommerce' ) . '</li>
                    <!-- /wp:list-item --></ul>
                    <!-- /wp:list -->
                    </div>',
				);

					return $parsed_hooked_block;
			},
			10,
			4
		);
	}

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
	 * Returns if delayed account creation is enabled.
	 *
	 * @return bool
	 */
	protected function is_feature_enabled() {
		return get_option( 'woocommerce_enable_delayed_account_creation', 'yes' ) === 'yes';
	}

	/**
	 * Process posted account form.
	 *
	 * @param \WC_Order $order Order object.
	 * @return \WP_Error|int
	 */
	protected function process_form_post( $order ) {
		if ( ! isset( $_POST['create-account'], $_POST['email'], $_POST['_wpnonce'] ) ) {
			return 0;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'wc_create_account' ) ) {
			return new \WP_Error( 'invalid_nonce', __( 'Unable to create account. Please try again.', 'woocommerce' ) );
		}

		$user_email = sanitize_email( wp_unslash( $_POST['email'] ) );

		// Does order already have user?
		if ( $order->get_customer_id() ) {
			return new \WP_Error( 'order_already_has_user', __( 'This order is already linked to a user account.', 'woocommerce' ) );
		}

		// Check given details match the current viewed order.
		if ( $order->get_billing_email() !== $user_email ) {
			return new \WP_Error( 'email_mismatch', __( 'The email address provided does not match the email address on this order.', 'woocommerce' ) );
		}

		$generate_password = filter_var( get_option( 'woocommerce_registration_generate_password', 'no' ), FILTER_VALIDATE_BOOLEAN );

		if ( $generate_password ) {
			$password = ''; // Will be generated by wc_create_new_customer.
		} else {
			$password = wp_unslash( $_POST['password'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( empty( $password ) || strlen( $password ) < 8 ) {
				return new \WP_Error( 'password_too_short', __( 'Password must be at least 8 characters.', 'woocommerce' ) );
			}
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
		if ( ! $permission || ! $this->is_feature_enabled() ) {
			return '';
		}

		// Check registration is possible for this order/customer, and if not, return early.
		if ( is_user_logged_in() || email_exists( $order->get_billing_email() ) ) {
			return '';
		}

		$result = $this->process_form_post( $order );
		$notice = '';

		if ( is_wp_error( $result ) ) {
			$notice = wc_print_notice( $result->get_error_message(), 'error', [], true );
		} elseif ( $result ) {
			return $this->render_confirmation();
		}

		$processor = new \WP_HTML_Tag_Processor(
			$content .
			'<div class="wc-block-order-confirmation-create-account-form-wrapper">' .
				$notice .
				'<div class="wc-block-order-confirmation-create-account-form"></div>' .
			'</div>'
		);

		if ( ! $processor->next_tag( array( 'class_name' => 'wp-block-woocommerce-order-confirmation-create-account' ) ) ) {
			return $content;
		}

		$processor->set_attribute( 'class', '' );
		$processor->set_attribute( 'style', '' );
		$processor->add_class( 'wc-block-order-confirmation-create-account-content' );

		if ( ! $processor->next_tag( array( 'class_name' => 'wc-block-order-confirmation-create-account-form' ) ) ) {
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
		$content  = '<div class="wc-block-order-confirmation-create-account-success" id="create-account">';
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

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );

		$this->asset_data_registry->add( 'delayedAccountCreationEnabled', $this->is_feature_enabled() );
		$this->asset_data_registry->add( 'registrationGeneratePassword', filter_var( get_option( 'woocommerce_registration_generate_password' ), FILTER_VALIDATE_BOOLEAN ) );
	}
}
