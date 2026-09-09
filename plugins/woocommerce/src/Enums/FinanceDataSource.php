<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the types of finance data a payment gateway can expose to the WooCommerce admin.
 *
 * @since 11.2.0
 */
final class FinanceDataSource {
	/**
	 * Balances for payment gateway accounts, one per currency.
	 *
	 * @var string
	 */
	public const BALANCE = 'balance';

	/**
	 * Recent and upcoming payouts to the merchant's bank account(s).
	 *
	 * @var string
	 */
	public const PAYOUTS = 'payouts';
}
