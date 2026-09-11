<?php
/**
 * Balance provider contract.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Payments\Finance;

defined( 'ABSPATH' ) || exit;

/**
 * Optional contract for finance data providers that can return account balances.
 *
 * Also list FinanceDataSource::BALANCE in FinanceDataProviderInterface::get_supported_data_types().
 *
 * @since 11.2.0
 */
interface BalanceProviderInterface {

	/**
	 * Get the account balances, one V1\Balance item per currency.
	 *
	 * Balances are not paginated: return a FinanceDataPage with has_more false and no cursors.
	 * Throw a FinanceDataException to surface a user-presentable error.
	 *
	 * @param FinanceDataQuery $query The query parameters.
	 * @return FinanceDataPage A page whose items are V1\Balance objects.
	 * @throws FinanceDataException When the balances cannot be returned.
	 *
	 * @since 11.2.0
	 */
	public function get_balances( FinanceDataQuery $query ): FinanceDataPage;
}
