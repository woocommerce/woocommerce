<?php
/**
 * Finance data provider contract.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Payments\Finance;

defined( 'ABSPATH' ) || exit;

/**
 * Contract for payment gateways that expose finance data (balances, payouts) to the WooCommerce admin.
 *
 * Register an implementation from the 'woocommerce_payments_finance_providers_registration' action.
 * Implement one optional interface per data type (BalanceProviderInterface, PayoutsProviderInterface)
 * and list that type in get_supported_data_types(); WooCommerce requires both.
 *
 * @since 11.2.0
 */
interface FinanceDataProviderInterface {

	/**
	 * Get the ID of the payment gateway this provider returns data for.
	 *
	 * Must match the `id` of a registered WC_Payment_Gateway.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_payment_gateway_id(): string;

	/**
	 * Get the URL of the icon for the payment provider.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_icon_url(): string;


	/**
	 * Get the title of the payment provider.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_title(): string;

	/**
	 * Get the finance data types this provider supports and the data-model version it implements for each.
	 *
	 * Keys are {@see \Automattic\WooCommerce\Enums\FinanceDataSource} constants, values are integer versions. The version names the
	 * sub-namespace of the value objects you return: declare 1 when returning V1\Balance and
	 * V1\Payout objects. Hardcode the integer rather than deriving it from a WooCommerce constant.
	 *
	 * @return array<string, int> Data type => data-model version.
	 *
	 * @since 11.2.0
	 */
	public function get_supported_data_types(): array;
}
