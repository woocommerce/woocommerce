<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance\Mocks;

use Automattic\WooCommerce\Admin\Payments\Finance\BalanceProviderInterface;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataPage;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataProviderInterface;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataQuery;
use Automattic\WooCommerce\Admin\Payments\Finance\PayoutsProviderInterface;
use Automattic\WooCommerce\Enums\FinanceDataSource;

/**
 * Fake finance data provider implementing every finance data interface.
 */
class FakeFinanceDataProvider implements FinanceDataProviderInterface, BalanceProviderInterface, PayoutsProviderInterface {

	/**
	 * The payment gateway id.
	 *
	 * @var string
	 */
	public string $gateway_id;

	/**
	 * The declared data types and versions.
	 *
	 * @var array<string, int>
	 */
	public array $supported_data_types;

	/**
	 * The page returned by get_balances().
	 *
	 * @var FinanceDataPage|null
	 */
	public ?FinanceDataPage $balances_page = null;

	/**
	 * The page returned by get_payouts().
	 *
	 * @var FinanceDataPage|null
	 */
	public ?FinanceDataPage $payouts_page = null;

	/**
	 * Thrown by get_balances() and get_payouts() when set.
	 *
	 * @var \Throwable|null
	 */
	public ?\Throwable $fetch_throwable = null;

	/**
	 * Thrown by get_supported_data_types() when set.
	 *
	 * @var \Throwable|null
	 */
	public ?\Throwable $declaration_throwable = null;

	/**
	 * The last query received by get_balances() or get_payouts().
	 *
	 * @var FinanceDataQuery|null
	 */
	public ?FinanceDataQuery $last_query = null;

	/**
	 * Constructor.
	 *
	 * @param string     $gateway_id           The payment gateway id.
	 * @param array|null $supported_data_types The declared data types, or null for balance and payouts at version 1.
	 */
	public function __construct( string $gateway_id = 'mock', ?array $supported_data_types = null ) {
		$this->gateway_id           = $gateway_id;
		$this->supported_data_types = $supported_data_types ?? array(
			FinanceDataSource::BALANCE => 1,
			FinanceDataSource::PAYOUTS => 1,
		);
	}

	/**
	 * Get the payment gateway id.
	 *
	 * @return string
	 */
	public function get_payment_gateway_id(): string {
		return $this->gateway_id;
	}

	/**
	 * Get the declared data types.
	 *
	 * @return array<string, int>
	 * @throws \Throwable When declaration_throwable is set.
	 */
	public function get_supported_data_types(): array {
		if ( null !== $this->declaration_throwable ) {
			throw $this->declaration_throwable;
		}

		return $this->supported_data_types;
	}

	/**
	 * Get the balances page.
	 *
	 * @param FinanceDataQuery $query The query.
	 * @return FinanceDataPage
	 * @throws \Throwable When fetch_throwable is set.
	 */
	public function get_balances( FinanceDataQuery $query ): FinanceDataPage {
		$this->last_query = $query;
		if ( null !== $this->fetch_throwable ) {
			throw $this->fetch_throwable;
		}

		return $this->balances_page ?? new FinanceDataPage( array() );
	}

	/**
	 * Get the payouts page.
	 *
	 * @param FinanceDataQuery $query The query.
	 * @return FinanceDataPage
	 * @throws \Throwable When fetch_throwable is set.
	 */
	public function get_payouts( FinanceDataQuery $query ): FinanceDataPage {
		$this->last_query = $query;
		if ( null !== $this->fetch_throwable ) {
			throw $this->fetch_throwable;
		}

		return $this->payouts_page ?? new FinanceDataPage( array() );
	}
}
