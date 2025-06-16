<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Blocks\Utils\HtmlTextReplacementProcessor;

/**
 * Status class.
 */
class Status extends AbstractOrderConfirmationBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-status';

	/**
	 * This block uses a custom render method so that the email verification form can be appended to the block. This does
	 * not inherit styles from the parent block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string | void Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$order     = $this->get_order();
		$classname = StyleAttributesUtils::get_classes_by_attributes( $attributes, array( 'extra_classes' ) );

		if ( isset( $attributes['align'] ) ) {
			$classname .= " align{$attributes['align']}";
		}

		$block = parent::render( $attributes, $content, $block );

		if ( ! $block ) {
			return '';
		}

		$additional_content = $this->render_confirmation_notice( $order );

		if ( $additional_content ) {
			$block = $block . sprintf(
				'<div class="wc-block-order-confirmation-status-description %1$s">%2$s</div>',
				esc_attr( trim( $classname ) ),
				$additional_content
			);
		}

		return $block;
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
			$default_texts = $this->get_default_status_texts( null );
			return $this->update_inner_blocks_content( $content, $default_texts['title'], $default_texts['description'] );
		}

		$hook_content = $this->get_hook_content( 'woocommerce_before_thankyou', [ $order->get_id() ] );
		$status       = $order->get_status();

		// Initialize with default texts, specific statuses will override these.
		$default_texts    = $this->get_default_status_texts( $order );
		$title_text       = $default_texts['title'];
		$description_text = $default_texts['description'];

		// Override with specific texts for certain order statuses.
		switch ( $status ) {
			case 'cancelled':
				$title_text = wp_kses_post(
					/**
					 * Filter the title shown after a checkout is complete.
					 *
					 * @since 9.6.0
					 *
					 * @param string         $title The title.
					 * @param WC_Order|false $order The order created during checkout, or false if order data is not available.
					 */
					apply_filters(
						'woocommerce_thankyou_order_received_title',
						esc_html__( 'Order cancelled', 'woocommerce' ),
						$order
					)
				);
				$description_text = wp_kses_post(
					// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
					apply_filters(
						'woocommerce_thankyou_order_received_text',
						esc_html__( 'Your order has been cancelled.', 'woocommerce' ),
						$order
					)
				);
				break;
			case 'refunded':
				$title_text = wp_kses_post(
					// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
					apply_filters(
						'woocommerce_thankyou_order_received_title',
						esc_html__( 'Order refunded', 'woocommerce' ),
						$order
					)
				);
				$description_text = wp_kses_post(
					sprintf(
						// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
						apply_filters(
							'woocommerce_thankyou_order_received_text',
							// translators: %s: date and time of the order refund.
							esc_html__( 'Your order was refunded %s.', 'woocommerce' ),
							$order
						),
						wc_format_datetime( $order->get_date_modified() )
					)
				);
				break;
			case 'completed':
				$title_text = wp_kses_post(
					// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
					apply_filters(
						'woocommerce_thankyou_order_received_title',
						esc_html__( 'Order completed', 'woocommerce' ),
						$order
					)
				);
				$description_text = wp_kses_post(
					// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
					apply_filters(
						'woocommerce_thankyou_order_received_text',
						esc_html__( 'Thank you. Your order has been fulfilled.', 'woocommerce' ),
						$order
					)
				);
				break;
			case 'failed':
				$title_text = wp_kses_post(
					// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
					apply_filters(
						'woocommerce_thankyou_order_received_title',
						esc_html__( 'Order failed', 'woocommerce' ),
						$order
					)
				);
				// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
				$order_received_text = apply_filters(
					'woocommerce_thankyou_order_received_text',
					esc_html__( 'Your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ),
					null
				);
				$actions             = $this->build_failed_order_actions( $order );
				$description_text    = $order_received_text . '<br><span class="wc-block-order-confirmation-status__actions">' . $actions . '</span>';
				break;
		}

		$updated_content = $this->update_inner_blocks_content( $content, $title_text, $description_text );

		return $hook_content . $updated_content;
	}

	/**
	 * Get fallback HTML for title and description.
	 *
	 * @param string $title_text Dynamic title text.
	 * @param string $description_text Dynamic description text.
	 * @return string Fallback HTML.
	 */
	private function get_fallback_html( $title_text, $description_text ) {
		return '<h1>' . esc_html( $title_text ) . '</h1><p>' . wp_kses_post( $description_text ) . '</p>';
	}

	/**
	 * Update inner blocks content with dynamic title and description using WP_HTML_Processor.
	 *
	 * @param string $content Block content.
	 * @param string $title_text Dynamic title text.
	 * @param string $description_text Dynamic description text.
	 * @return string Updated content.
	 */
	private function update_inner_blocks_content( $content, $title_text, $description_text ) {
		// Fallback if no content or WP_HTML_Processor is unavailable.
		if ( empty( $content ) || ! class_exists( 'WP_HTML_Processor' ) ) {
			return $this->get_fallback_html( $title_text, $description_text );
		}

		$processor = HtmlTextReplacementProcessor::create_fragment( $content );

		$in_heading   = false;
		$in_paragraph = false;

		while ( $processor->next_token() ) {
			switch ( $processor->get_token_type() ) {
				case '#tag':
					$tag_name      = $processor->get_tag();
					$is_closer_tag = $processor->is_tag_closer();
					if ( in_array( $tag_name, array( 'H1', 'H2', 'H3', 'H4', 'H5', 'H6' ), true ) ) {
						$in_heading = ! $is_closer_tag;
					} elseif ( 'P' === $tag_name ) {
						$in_paragraph = ! $is_closer_tag;
					}
					break;
				case '#text':
					if ( $in_heading ) {
						$processor->unsafe_replace_text_with_raw_html( esc_html( $title_text ) );
					} elseif ( $in_paragraph ) {
						$processor->unsafe_replace_text_with_raw_html( wp_kses_post( $description_text ) );
					}
					break;
			}
		}

		return $processor->get_updated_html();
	}

	/**
	 * Build action buttons for failed orders.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string HTML for action buttons.
	 */
	private function build_failed_order_actions( $order ) {
		$actions = '<a href="' . esc_url( $order->get_checkout_payment_url() ) . '" class="button">' . esc_html__( 'Try again', 'woocommerce' ) . '</a> ';

		if ( wc_get_page_permalink( 'myaccount' ) ) {
			$actions .= '<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="button">' . esc_html__( 'My account', 'woocommerce' ) . '</a> ';
		}

		return $actions;
	}

	/**
	 * Get default status texts for 'order received' scenario.
	 *
	 * @param \WC_Order|null $order Order object or null for no permission case.
	 * @return array Array with 'title' and 'description' keys.
	 */
	private function get_default_status_texts( $order ) {
		return array(
			'title'       => wp_kses_post(
				// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
				apply_filters(
					'woocommerce_thankyou_order_received_title',
					esc_html__( 'Order received', 'woocommerce' ),
					$order
				)
			),
			'description' => wp_kses_post(
				// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
				apply_filters(
					'woocommerce_thankyou_order_received_text',
					esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ),
					$order
				)
			),
		);
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
