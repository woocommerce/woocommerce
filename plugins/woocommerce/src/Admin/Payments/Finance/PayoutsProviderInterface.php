<?php
/**
 * Payouts provider contract.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Payments\Finance;

defined( 'ABSPATH' ) || exit;

/**
 * Optional contract for finance data providers that can return payouts.
 *
 * Also list FinanceDataSource::PAYOUTS in FinanceDataProviderInterface::get_supported_data_types().
 *
 * @since 11.2.0
 */
interface PayoutsProviderInterface {

	/**
	 * Get a page of recent and upcoming payouts.
	 *
	 * Honor the query's cursor and page size. Cursors are opaque strings issued by the provider:
	 * whatever is returned as next_cursor or prev_cursor comes back unchanged as the query cursor,
	 * so encode the direction in the cursor itself. Throw a FinanceDataException to surface a
	 * user-presentable error.
	 *
	 * @param FinanceDataQuery $query The query parameters.
	 * @return FinanceDataPage A page whose items are V1\Payout objects.
	 * @throws FinanceDataException When the payouts cannot be returned.
	 *
	 * @since 11.2.0
	 */
	public function get_payouts( FinanceDataQuery $query ): FinanceDataPage;
}
