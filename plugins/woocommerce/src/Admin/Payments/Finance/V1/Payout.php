<?php
/**
 * Payout value object.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Payments\Finance\V1;

use Automattic\WooCommerce\Enums\PayoutStatus;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceDataValidator;

defined( 'ABSPATH' ) || exit;

/**
 * A payout from the payment provider to the merchant's bank account (finance data-model version 1).
 *
 * Amounts are decimal strings in major currency units, for example "1234.56". Dates are
 * DateTimeInterface objects in any timezone; WooCommerce serializes them as UTC.
 *
 * @since 11.2.0
 */
final class Payout {

	/**
	 * Provider-issued payout identifier.
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * Three-letter ISO 4217 currency code, uppercase.
	 *
	 * @var string
	 */
	private string $currency;

	/**
	 * The payout amount as a decimal string.
	 *
	 * @var string
	 */
	private string $amount;

	/**
	 * High-level status, one of the PayoutStatus constants.
	 *
	 * @var string
	 */
	private string $status;

	/**
	 * When the payout was initiated.
	 *
	 * @var \DateTimeInterface
	 */
	private \DateTimeInterface $date_initiated;

	/**
	 * Free-text bank account name or details, or null when not reported.
	 *
	 * @var string|null
	 */
	private ?string $bank_account = null;

	/**
	 * Expected deposit date for pending payouts, or when the status last changed, or null when unknown.
	 *
	 * @var \DateTimeInterface|null
	 */
	private ?\DateTimeInterface $date_expected = null;

	/**
	 * Provider-specific status, or null when not available.
	 *
	 * @var string|null
	 */
	private ?string $provider_status = null;

	/**
	 * Constructor.
	 *
	 * @param string                  $id              Provider-issued payout identifier, stable across requests.
	 * @param string                  $currency        Three-letter ISO 4217 currency code. Case-insensitive.
	 * @param string                  $amount          The payout amount as a decimal string in major currency units.
	 * @param string                  $status          One of the PayoutStatus constants.
	 * @param \DateTimeInterface      $date_initiated  When the payout was initiated.
	 * @param \DateTimeInterface|null $date_expected   Expected deposit date for pending payouts, or when the status last changed, or null when unknown.
	 * @param string|null             $provider_status Provider-specific status, or null when not available.
	 * @throws \InvalidArgumentException When the id, currency, amount or status is not valid.
	 *
	 * @since 11.2.0
	 */
	public function __construct( string $id, string $currency, string $amount, string $status, \DateTimeInterface $date_initiated, ?\DateTimeInterface $date_expected = null, ?string $provider_status = null ) {
		$id = trim( $id );
		if ( '' === $id ) {
			throw new \InvalidArgumentException( 'The payout id must not be empty.' );
		}

		if ( ! in_array( $status, PayoutStatus::get_all(), true ) ) {
			throw new \InvalidArgumentException( 'The payout status must be one of the PayoutStatus constants.' );
		}

		$this->id             = $id;
		$this->currency       = FinanceDataValidator::normalize_currency( $currency );
		$this->amount         = FinanceDataValidator::validate_amount( $amount );
		$this->status         = $status;
		$this->date_initiated = $date_initiated;
		$this->date_expected  = $date_expected;
		$this->provider_status = self::normalize_optional_string( $provider_status );
	}

	/**
	 * Set the bank account name or details.
	 *
	 * @param string|null $bank_account Free-text bank account name or details, or null when not reported.
	 * @return self
	 *
	 * @since 11.2.0
	 */
	public function set_bank_account( ?string $bank_account ): self {
		$this->bank_account = self::normalize_optional_string( $bank_account );
		return $this;
	}

	/**
	 * Set the expected deposit date, or the date the status last changed for complete and failed payouts.
	 *
	 * @param \DateTimeInterface|null $date The date, or null when unknown.
	 * @return self
	 *
	 * @since 11.2.0
	 */
	public function set_date_expected( ?\DateTimeInterface $date ): self {
		$this->date_expected = $date;
		return $this;
	}

	/**
	 * Set the provider-specific status.
	 *
	 * @param string|null $provider_status The provider's own status value, or null when not reported.
	 * @return self
	 *
	 * @since 11.2.0
	 */
	public function set_provider_status( ?string $provider_status ): self {
		$this->provider_status = self::normalize_optional_string( $provider_status );
		return $this;
	}

	/**
	 * Get the provider-issued payout identifier.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_id(): string {
		return $this->id;
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
	 * Get the payout amount as a decimal string.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_amount(): string {
		return $this->amount;
	}

	/**
	 * Get the high-level status, one of the PayoutStatus constants.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Get when the payout was initiated.
	 *
	 * @return \DateTimeInterface
	 *
	 * @since 11.2.0
	 */
	public function get_date_initiated(): \DateTimeInterface {
		return $this->date_initiated;
	}

	/**
	 * Get the bank account name or details, or null when not reported.
	 *
	 * @return string|null
	 *
	 * @since 11.2.0
	 */
	public function get_bank_account(): ?string {
		return $this->bank_account;
	}

	/**
	 * Get the expected deposit date, or when the status last changed, or null when unknown.
	 *
	 * @return \DateTimeInterface|null
	 *
	 * @since 11.2.0
	 */
	public function get_date_expected(): ?\DateTimeInterface {
		return $this->date_expected;
	}

	/**
	 * Get the provider-specific status, or null when not available.
	 *
	 * @return string|null
	 *
	 * @since 11.2.0
	 */
	public function get_provider_status(): ?string {
		return $this->provider_status;
	}

	/**
	 * Trim an optional string and turn an empty value into null.
	 *
	 * @param string|null $value The value.
	 * @return string|null
	 */
	private static function normalize_optional_string( ?string $value ): ?string {
		if ( null === $value ) {
			return null;
		}

		$value = trim( $value );
		return '' === $value ? null : $value;
	}
}
