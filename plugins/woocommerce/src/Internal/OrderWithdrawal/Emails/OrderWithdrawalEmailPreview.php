<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal\Emails;

use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFormProcessor;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Email_Customer_Order_Withdrawal_Requested;
use WC_Email_Order_Withdrawal_Requested;
use WC_Order;

/**
 * Prepares order withdrawal emails for preview.
 *
 * @internal Just for internal use.
 */
final class OrderWithdrawalEmailPreview implements RegisterHooksInterface {

	/**
	 * Register email preview hooks.
	 *
	 * @since 11.2.0
	 */
	public function register(): void {
		add_filter( 'woocommerce_prepare_email_for_preview', array( $this, 'prepare_email_for_preview' ), 10, 1 );
	}

	/**
	 * Populate order withdrawal data for email previews.
	 *
	 * @param mixed $email Email being prepared for preview.
	 * @return mixed
	 *
	 * @since 11.2.0
	 */
	public function prepare_email_for_preview( $email ) {
		if (
			(
				! $email instanceof WC_Email_Customer_Order_Withdrawal_Requested
				&& ! $email instanceof WC_Email_Order_Withdrawal_Requested
			)
			|| ! $email->object instanceof WC_Order
		) {
			return $email;
		}

		$order                                       = $email->object;
		$order_date                                  = $order->get_date_created();
		$email->withdrawal_data                      = array(
			OrderWithdrawalFormProcessor::FIELD_FIRST_NAME => $order->get_billing_first_name(),
			OrderWithdrawalFormProcessor::FIELD_LAST_NAME  => $order->get_billing_last_name(),
			OrderWithdrawalFormProcessor::FIELD_EMAIL      => $order->get_billing_email(),
			OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => $order->get_order_number(),
			OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE => OrderWithdrawalFormProcessor::WITHDRAWAL_TYPE_SPECIFIC,
			OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS => __( 'I would like to withdraw the first item from this order.', 'woocommerce' ),
		);
		$email->submitted_at                         = $order_date ? $order_date->getTimestamp() : time();
		$email->placeholders['{order_number}']       = $order->get_order_number();
		$email->placeholders['{order_billing_name}'] = $order->get_formatted_billing_full_name();

		if ( $email instanceof WC_Email_Order_Withdrawal_Requested ) {
			$email->matched_order             = $order;
			$email->outside_withdrawal_window = false;
			$email->withdrawal_window_warning = '';
		}

		return $email;
	}
}
