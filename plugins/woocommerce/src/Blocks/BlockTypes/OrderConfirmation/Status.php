<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Status class.
 */
class Status extends AbstractOrderConfirmationBlock {
	/**
	 * Status block selected for the current order.
	 *
	 * @var \WP_Block|null
	 */
	private $status_block;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-status';

	/**
	 * Initialize this block type.
	 *
	 * @return void
	 */
	protected function initialize() {
		add_filter( 'block_type_metadata_settings', array( $this, 'add_block_type_metadata_settings' ), 10, 2 );
		parent::initialize();
	}

	/**
	 * Skip the default rendering pass because only the matching status state is rendered.
	 *
	 * @param array $settings Block type settings.
	 * @param array $metadata Block metadata.
	 * @return array
	 */
	public function add_block_type_metadata_settings( $settings, $metadata ) {
		if ( ! empty( $metadata['name'] ) && 'woocommerce/order-confirmation-status' === $metadata['name'] ) {
			$settings['skip_inner_blocks'] = true;
		}

		return $settings;
	}

	/**
	 * This block uses a custom render method so that the email verification form can be appended to the block. This does
	 * not inherit styles from the parent block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content Block content.
	 * @param \WP_Block $block Block instance.
	 *
	 * @return string | void Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$order              = $this->get_order();
		$this->status_block = $this->get_status_block( $order, $block );
		$classname          = StyleAttributesUtils::get_classes_by_attributes( $attributes, array( 'extra_classes' ) );

		if ( isset( $attributes['align'] ) ) {
			$classname .= " align{$attributes['align']}";
		}

		$rendered_block     = parent::render( $attributes, $content, $block );
		$this->status_block = null;

		if ( ! $rendered_block ) {
			return '';
		}

		$additional_content = $this->render_confirmation_notice( $order );

		if ( $additional_content ) {
			$rendered_block .= sprintf(
				'<div class="wc-block-order-confirmation-status-description %1$s">%2$s</div>',
				esc_attr( trim( $classname ) ),
				$additional_content
			);
		}

		return $rendered_block;
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
			$order_received_text = $this->filter_order_received_text( esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), null );

			return '<p>' . $order_received_text . '</p>';
		}

		$content  = $this->get_hook_content( 'woocommerce_before_thankyou', [ $order->get_id() ] );
		$status   = $order->get_status();
		$content .= $this->render_status_content( $order, $status );

		if ( OrderStatus::FAILED === $status && $this->status_block ) {
			$content .= $this->render_failed_actions( $order );
		}

		return $content;
	}

	/**
	 * Get the inner block matching the current order status.
	 *
	 * @param \WC_Order|null $order Order object.
	 * @param \WP_Block|null $block Parent block instance.
	 * @return \WP_Block|null
	 */
	private function get_status_block( $order, $block ) {
		if ( ! $order || ! $block instanceof \WP_Block ) {
			return null;
		}

		$block_name = $this->get_status_block_name( $order->get_status() );

		foreach ( $block->inner_blocks as $inner_block ) {
			if ( $block_name === $inner_block->name ) {
				return $inner_block;
			}
		}

		return null;
	}

	/**
	 * Get the inner block name for an order status.
	 *
	 * @param string $status Order status.
	 * @return string
	 */
	private function get_status_block_name( $status ) {
		switch ( $status ) {
			case OrderStatus::CANCELLED:
				return 'woocommerce/order-confirmation-status-cancelled';
			case OrderStatus::REFUNDED:
				return 'woocommerce/order-confirmation-status-refunded';
			case OrderStatus::COMPLETED:
				return 'woocommerce/order-confirmation-status-completed';
			case OrderStatus::FAILED:
				return 'woocommerce/order-confirmation-status-failed';
			default:
				return 'woocommerce/order-confirmation-status-successful';
		}
	}

	/**
	 * Render the selected status block with filtered heading and paragraph content.
	 *
	 * @param \WC_Order $order Order object.
	 * @param string    $status Order status.
	 * @return string
	 */
	private function render_status_content( $order, $status ) {
		if ( ! $this->status_block ) {
			if ( OrderStatus::FAILED === $status ) {
				return $this->render_legacy_failed_status_content( $order );
			}

			return $this->render_legacy_status_content( $order, $status );
		}

		list( $default_title, $default_text ) = $this->get_default_status_content( $status );

		$title              = $this->get_status_inner_block_content( 'core/heading', 'h[1-6]' );
		$text               = $this->get_status_inner_block_content( 'core/paragraph', 'p' );
		$has_custom_content = null !== $title && null !== $text;

		$title = $has_custom_content ? $title : $default_title;
		$text  = $has_custom_content ? $text : $default_text;

		// Preserve the historical filter order and arguments for failed orders.
		if ( OrderStatus::FAILED === $status ) {
			$text  = $this->filter_order_received_text( $text, null );
			$title = $this->filter_order_received_title( $title, $order );
		} else {
			$title = $this->filter_order_received_title( $title, $order );
			$text  = $this->filter_order_received_text( $text, $order );
		}

		if ( $has_custom_content ) {
			return $this->render_selected_status_block( $title, $text );
		}

		return '<h1>' . $title . '</h1><p>' . $text . '</p>';
	}

	/**
	 * Render legacy status content for blocks saved without editable state blocks.
	 *
	 * @param \WC_Order $order Order object.
	 * @param string    $status Order status.
	 * @return string
	 */
	private function render_legacy_status_content( $order, $status ) {
		list( $title, $text ) = $this->get_default_status_content( $status );

		switch ( $status ) {
			case OrderStatus::REFUNDED:
				$title = $this->filter_order_received_title( $title, $order );
				$text  = wp_kses_post(
					sprintf(
						$this->filter_order_received_text(
							// translators: %s: date and time of the order refund.
							esc_html__( 'Your order was refunded %s.', 'woocommerce' ),
							$order
						),
						wc_format_datetime( $order->get_date_modified() )
					)
				);
				break;
			default:
				$title = $this->filter_order_received_title( $title, $order );
				$text  = $this->filter_order_received_text( $text, $order );
				break;
		}

		return '<h1>' . $title . '</h1><p>' . $text . '</p>';
	}

	/**
	 * Render legacy failed-order content while preserving the historical hook sequence.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	private function render_legacy_failed_status_content( $order ) {
		list( $title, $text ) = $this->get_default_status_content( OrderStatus::FAILED );

		$text    = $this->filter_legacy_failed_order_received_text(
			esc_html__( 'Your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' )
		);
		$actions = $this->render_failed_actions( $order );
		$title   = $this->filter_order_received_title( $title, $order );

		return '<h1>' . $title . '</h1><p>' . $text . '</p>' . $actions;
	}

	/**
	 * Render failed-order actions.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	private function render_failed_actions( $order ) {
		$actions = '<a href="' . esc_url( $order->get_checkout_payment_url() ) . '" class="button">' . esc_html__( 'Try again', 'woocommerce' ) . '</a> ';

		if ( wc_get_page_permalink( 'myaccount' ) ) {
			$actions .= '<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="button">' . esc_html__( 'My account', 'woocommerce' ) . '</a> ';
		}

		return '
			<p class="wc-block-order-confirmation-status__actions">' . $actions . '</p>
		';
	}

	/**
	 * Get content from a direct inner block of the selected status state.
	 *
	 * @param string $block_name Inner block name.
	 * @param string $tag_pattern Regular expression matching the content tag name.
	 * @return string|null
	 */
	private function get_status_inner_block_content( $block_name, $tag_pattern ) {
		$status_block = $this->status_block;
		if ( ! $status_block ) {
			return null;
		}

		foreach ( $status_block->parsed_block['innerBlocks'] as $inner_block ) {
			if ( $block_name === $inner_block['blockName'] ) {
				return $this->get_element_content( $inner_block['innerHTML'], $tag_pattern );
			}
		}

		return null;
	}

	/**
	 * Render the selected state without its internal wrapper.
	 *
	 * @param string $title Filtered heading content.
	 * @param string $text Filtered paragraph content.
	 * @return string
	 */
	private function render_selected_status_block( $title, $text ) {
		$status_block = $this->status_block;
		if ( ! $status_block ) {
			return '';
		}

		$parsed_block = $status_block->parsed_block;
		$replacements = array(
			'core/heading'   => array( 'h[1-6]', $title ),
			'core/paragraph' => array( 'p', $text ),
		);

		foreach ( $parsed_block['innerBlocks'] as $index => $inner_block ) {
			if ( isset( $replacements[ $inner_block['blockName'] ] ) ) {
				list( $tag_pattern, $replacement ) = $replacements[ $inner_block['blockName'] ];

				$inner_block['innerHTML'] = $this->replace_element_content( $inner_block['innerHTML'], $tag_pattern, $replacement );

				foreach ( $inner_block['innerContent'] as $content_index => $inner_content ) {
					if ( is_string( $inner_content ) ) {
						$inner_block['innerContent'][ $content_index ] = $this->replace_element_content( $inner_content, $tag_pattern, $replacement );
					}
				}

				$parsed_block['innerBlocks'][ $index ] = $inner_block;
			}
		}

		$parsed_block['innerHTML'] = '';
		foreach ( $parsed_block['innerContent'] as $index => $inner_content ) {
			if ( is_string( $inner_content ) ) {
				$parsed_block['innerContent'][ $index ] = '';
			}
		}

		return ( new \WP_Block( $parsed_block, $status_block->context ) )->render();
	}

	/**
	 * Get default title and text for an order status.
	 *
	 * @param string $status Order status.
	 * @return string[]
	 */
	private function get_default_status_content( $status ) {
		switch ( $status ) {
			case OrderStatus::CANCELLED:
				return array(
					esc_html__( 'Order cancelled', 'woocommerce' ),
					esc_html__( 'Your order has been cancelled.', 'woocommerce' ),
				);
			case OrderStatus::REFUNDED:
				return array(
					esc_html__( 'Order refunded', 'woocommerce' ),
					esc_html__( 'Your order was refunded', 'woocommerce' ),
				);
			case OrderStatus::COMPLETED:
				return array(
					esc_html__( 'Order completed', 'woocommerce' ),
					esc_html__( 'Thank you. Your order has been fulfilled.', 'woocommerce' ),
				);
			case OrderStatus::FAILED:
				return array(
					esc_html__( 'Order failed', 'woocommerce' ),
					esc_html__( 'Your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again', 'woocommerce' ),
				);
			default:
				return array(
					esc_html__( 'Order received', 'woocommerce' ),
					esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ),
				);
		}
	}

	/**
	 * Filter the title shown after checkout.
	 *
	 * @param string    $title Title to filter.
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	private function filter_order_received_title( $title, $order ) {
		/**
		 * Filter the title shown after a checkout is complete.
		 *
		 * @since 9.6.0
		 *
		 * @param string         $title The title.
		 * @param WC_Order|false $order The order created during checkout, or false if order data is not available.
		 */
		$filtered_title = apply_filters( 'woocommerce_thankyou_order_received_title', $title, $order );

		return $this->sanitize_filtered_content( $filtered_title, $title );
	}

	/**
	 * Filter the text shown after checkout.
	 *
	 * @param string         $text Text to filter.
	 * @param \WC_Order|null $order Order object, or null for restricted and failed orders.
	 * @return string
	 */
	private function filter_order_received_text( $text, $order ) {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$filtered_text = apply_filters( 'woocommerce_thankyou_order_received_text', $text, $order );

		return $this->sanitize_filtered_content( $filtered_text, $text );
	}

	/**
	 * Apply the failed-order text filter without narrowing its legacy HTML support.
	 *
	 * @param string $text Text to filter.
	 * @return string
	 */
	private function filter_legacy_failed_order_received_text( $text ) {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$filtered_text = apply_filters( 'woocommerce_thankyou_order_received_text', $text, null );

		return is_string( $filtered_text ) || is_numeric( $filtered_text ) ? (string) $filtered_text : $text;
	}

	/**
	 * Sanitize a filtered heading or paragraph value.
	 *
	 * @param mixed  $content Filtered content.
	 * @param string $fallback Fallback content.
	 * @return string
	 */
	private function sanitize_filtered_content( $content, $fallback ) {
		if ( ! is_string( $content ) && ! is_numeric( $content ) ) {
			$content = $fallback;
		}

		return wp_kses_post( (string) $content );
	}

	/**
	 * Get the content of the first matching HTML element.
	 *
	 * @param string $content HTML content.
	 * @param string $tag_pattern Regular expression matching the tag name.
	 * @return string|null
	 */
	private function get_element_content( $content, $tag_pattern ) {
		$pattern = '/(<' . $tag_pattern . '\\b[^>]*>)(.*?)(<\\/' . $tag_pattern . '>)/is';

		return preg_match( $pattern, $content, $matches ) ? $matches[2] : null;
	}

	/**
	 * Replace the content of the first matching HTML element.
	 *
	 * @param string $content HTML content.
	 * @param string $tag_pattern Regular expression matching the tag name.
	 * @param string $replacement Replacement content.
	 * @return string
	 */
	private function replace_element_content( $content, $tag_pattern, $replacement ) {
		$pattern         = '/(<' . $tag_pattern . '\\b[^>]*>)(.*?)(<\\/' . $tag_pattern . '>)/is';
		$updated_content = preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $replacement ) {
				return $matches[1] . $replacement . $matches[3];
			},
			$content,
			1
		);

		return is_string( $updated_content ) ? $updated_content : $content;
	}

	/**
	 * This is what gets rendered when the order does not exist.
	 *
	 * @return string
	 */
	protected function render_content_fallback() {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		return '<p>' . esc_html__( 'Please check your email for the order confirmation.', 'woocommerce' ) . '</p>';
	}

	/**
	 * If the order is invalid or there is no permission to view the details, tell the user to check email or log-in.
	 *
	 * @param \WC_Order|null $order Order object.
	 * @return string
	 */
	protected function render_confirmation_notice( $order = null ) {
		if ( ! $order ) {
			$content = '<p>' . esc_html__( 'If you\'ve just placed an order, give your email a quick check for the confirmation.', 'woocommerce' );

			if ( wc_get_page_permalink( 'myaccount' ) ) {
				$content .= ' ' . sprintf(
					/* translators: 1: opening a link tag 2: closing a link tag */
					esc_html__( 'Have an account with us? %1$sLog in here to view your order details%2$s.', 'woocommerce' ),
					'<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="button">',
					'</a>'
				);
			}

			$content .= '</p>';

			return $content;
		}

		$permission = $this->get_view_order_permissions( $order );

		if ( $permission ) {
			return '';
		}

		$verification_required  = $this->email_verification_required( $order );
		$verification_permitted = $this->email_verification_permitted( $order );
		$my_account_page        = wc_get_page_permalink( 'myaccount' );

		$content  = '<p>';
		$content .= esc_html__( 'Great news! Your order has been received, and a confirmation will be sent to your email address.', 'woocommerce' );
		$content .= $my_account_page ? ' ' . sprintf(
			/* translators: 1: opening a link tag 2: closing a link tag */
			esc_html__( 'Have an account with us? %1$sLog in here%2$s to view your order.', 'woocommerce' ),
			'<a href="' . esc_url( $my_account_page ) . '" class="button">',
			'</a>'
		) : '';

		if ( $verification_required && $verification_permitted ) {
			$content .= ' ' . esc_html__( 'Alternatively, confirm the email address linked to the order below.', 'woocommerce' );
		}

		$content .= '</p>';

		if ( $verification_required && $verification_permitted ) {
			$content .= $this->render_verification_form();
		}

		return $content;
	}

	/**
	 * Email verification for guest users.
	 *
	 * @return string
	 */
	protected function render_verification_form() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$check_submission_notice = ! empty( $_POST ) ? wc_print_notice( esc_html__( 'We were unable to verify the email address you provided. Please try again.', 'woocommerce' ), 'error', [], true ) : '';

		return '<form method="post" class="woocommerce-form woocommerce-verify-email">' .
			$check_submission_notice .
			sprintf(
				'<p class="form-row verify-email">
					<label for="%1$s">%2$s</label>
					<input type="email" name="email" id="%1$s" autocomplete="email" class="input-text" required />
				</p>',
				esc_attr( 'verify-email' ),
				esc_html__( 'Email address', 'woocommerce' ) . '&nbsp;<span class="required">*</span>'
			) .
			sprintf(
				'<p class="form-row login-submit">
					<input type="submit" name="wp-submit" id="%1$s" class="button button-primary %4$s" value="%2$s" />
					%3$s
				</p>',
				esc_attr( 'verify-email-submit' ),
				esc_html__( 'Confirm email and view order', 'woocommerce' ),
				wp_nonce_field( 'wc_verify_email', '_wpnonce', true, false ),
				esc_attr( wc_wp_theme_get_element_class_name( 'button' ) )
			) .
			'</form>';
	}
}
