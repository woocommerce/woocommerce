<?php
/**
 * Balance value object.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Payments\Finance\V1;

use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceDataValidator;

defined( 'ABSPATH' ) || exit;

/**
 * The account balance for one currency (finance data-model version 1).
 *
 * Amounts are decimal strings in major currency units, for example "1234.56" or "-10.00". Build them
 * with number_format( $value, $decimals, '.', '' ) rather than passing floats.
 *
 * @since 11.2.0
 */
final class Balance {

	/**
	 * Three-letter ISO 4217 currency code, uppercase.
	 *
	 * @var string
	 */
	private string $currency;

	/**
	 * The balance amount as a decimal string.
	 *
	 * @var string
	 */
	private string $amount;

	/**
	 * The amount available for payout as a decimal string, or null when not reported.
	 *
	 * @var string|null
	 */
	private ?string $available_amount = null;

	/**
	 * Link for initiating a payout, or null when not available.
	 *
	 * @var Link|null
	 */
	private ?Link $payout_link = null;

	/**
	 * Constructor.
	 *
	 * @param string $currency Three-letter ISO 4217 currency code. Case-insensitive.
	 * @param string $amount   The balance as a decimal string in major currency units, for example "1234.56".
	 * @throws \InvalidArgumentException When the currency or amount is not valid.
	 *
	 * @since 11.2.0
	 */
	public function __construct( string $currency, string $amount ) {
		$this->currency = FinanceDataValidator::normalize_currency( $currency );
		$this->amount   = FinanceDataValidator::validate_amount( $amount );
	}

	/**
	 * Set the amount available for payout.
	 *
	 * @param string|null $amount The available amount as a decimal string, or null when not reported.
	 * @return self
	 * @throws \InvalidArgumentException When the amount is not valid.
	 *
	 * @since 11.2.0
	 */
	public function set_available_amount( ?string $amount ): self {
		$this->available_amount = null === $amount ? null : FinanceDataValidator::validate_amount( $amount );
		return $this;
	}

	/**
	 * Set the link for initiating a payout.
	 *
	 * @param Link|null $link The payout link, or null when not available.
	 * @return self
	 *
	 * @since 11.2.0
	 */
	public function set_payout_link( ?Link $link ): self {
		$this->payout_link = $link;
		return $this;
	}

	/**
	 * Get the currency code.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_currency(): string {
		return $this->currency;
	}

	/**
	 * Get the balance amount as a decimal string.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_amount(): string {
		return $this->amount;
	}

	/**
	 * Get the amount available for payout, or null when not reported.
	 *
	 * @return string|null
	 *
	 * @since 11.2.0
	 */
	public function get_available_amount(): ?string {
		return $this->available_amount;
	}

	/**
	 * Get the link for initiating a payout, or null when not available.
	 *
	 * @return Link|null
	 *
	 * @since 11.2.0
	 */
	public function get_payout_link(): ?Link {
		return $this->payout_link;
	}
}
