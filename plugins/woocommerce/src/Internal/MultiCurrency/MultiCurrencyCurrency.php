<?php
/**
 * MultiCurrencyCurrency class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;

/**
 * Currency value object for the native multi-currency runtime.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyCurrency implements \JsonSerializable {

	/**
	 * Localization service.
	 *
	 * @var MultiCurrencyLocalizationInterface
	 */
	private MultiCurrencyLocalizationInterface $localization_service;

	/**
	 * Three-letter currency code.
	 *
	 * @var string
	 */
	private string $code;

	/**
	 * Currency conversion rate.
	 *
	 * @var float
	 */
	private float $rate;

	/**
	 * Currency charm amount after conversion and rounding.
	 *
	 * @var float|null
	 */
	private ?float $charm = null;

	/**
	 * Whether the currency is the store default.
	 *
	 * @var bool
	 */
	private bool $is_default;

	/**
	 * Currency rounding amount after conversion.
	 *
	 * @var string|null
	 */
	private ?string $rounding = null;

	/**
	 * Whether the currency has zero decimal places.
	 *
	 * @var bool
	 */
	private bool $is_zero_decimal;

	/**
	 * Last successful rate update timestamp.
	 *
	 * @var int|null
	 */
	private ?int $last_updated;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrencyLocalizationInterface $localization_service Localization service.
	 * @param string                             $code                 Three-letter currency code.
	 * @param float                              $rate                 Conversion rate.
	 * @param bool                               $is_default           Whether this is the default currency.
	 * @param int|null                           $last_updated         Last successful rate update timestamp.
	 */
	public function __construct(
		MultiCurrencyLocalizationInterface $localization_service,
		string $code,
		float $rate = 1.0,
		bool $is_default = false,
		?int $last_updated = null
	) {
		$this->localization_service = $localization_service;
		$this->code                 = strtoupper( $code );
		$this->rate                 = $rate;
		$this->is_default           = $is_default;
		$this->last_updated         = $last_updated;
		$this->is_zero_decimal      = 0 === (int) $this->localization_service->get_currency_format( $this->code )['num_decimals'];
	}

	/**
	 * Get the currency code.
	 *
	 * @return string
	 */
	public function get_code(): string {
		return $this->code;
	}

	/**
	 * Get the lowercase currency ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return strtolower( $this->code );
	}

	/**
	 * Tell whether this is the default currency.
	 *
	 * @return bool
	 */
	public function get_is_default(): bool {
		return $this->is_default;
	}

	/**
	 * Get the currency conversion rate.
	 *
	 * @return float
	 */
	public function get_rate(): float {
		return $this->rate;
	}

	/**
	 * Set the currency conversion rate.
	 *
	 * @param mixed $rate Conversion rate.
	 */
	public function set_rate( $rate ): void {
		$this->rate = (float) $rate;
	}

	/**
	 * Get the charm amount.
	 *
	 * @return float
	 */
	public function get_charm(): float {
		return $this->charm ?? 0.0;
	}

	/**
	 * Set the charm amount.
	 *
	 * @param mixed $charm Charm amount.
	 */
	public function set_charm( $charm ): void {
		$this->charm = (float) $charm;
	}

	/**
	 * Get the rounding amount.
	 *
	 * @return string
	 */
	public function get_rounding(): string {
		return $this->rounding ?? '0';
	}

	/**
	 * Set the rounding amount.
	 *
	 * @param mixed $rounding Rounding amount.
	 */
	public function set_rounding( $rounding ): void {
		$this->rounding = (string) $rounding;
	}

	/**
	 * Tell whether this is a zero-decimal currency.
	 *
	 * @return bool
	 */
	public function get_is_zero_decimal(): bool {
		return $this->is_zero_decimal;
	}

	/**
	 * Get the last successful rate update timestamp.
	 *
	 * @return int|null
	 */
	public function get_last_updated(): ?int {
		return $this->last_updated;
	}

	/**
	 * Set the last successful rate update timestamp.
	 *
	 * @param int $last_updated Last successful rate update timestamp.
	 */
	public function set_last_updated( int $last_updated ): void {
		$this->last_updated = $last_updated;
	}

	/**
	 * Get the currency name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		$currencies = get_woocommerce_currencies();

		return $currencies[ $this->code ] ?? $this->code;
	}

	/**
	 * Get the currency symbol.
	 *
	 * @return string
	 */
	public function get_symbol(): string {
		return get_woocommerce_currency_symbol( $this->code );
	}

	/**
	 * Get the currency symbol position.
	 *
	 * @return string
	 */
	public function get_symbol_position(): string {
		return (string) $this->localization_service->get_currency_format( $this->code )['currency_pos'];
	}

	/**
	 * Specify data for JSON serialization.
	 *
	 * @return array<string,mixed>
	 */
	public function jsonSerialize(): array {
		return array(
			'id'              => $this->get_id(),
			'code'            => $this->get_code(),
			'name'            => $this->get_name(),
			'rate'            => $this->get_rate(),
			'symbol'          => $this->get_symbol(),
			'symbol_position' => $this->get_symbol_position(),
			'is_zero_decimal' => $this->get_is_zero_decimal(),
			'is_default'      => $this->get_is_default(),
			'charm'           => $this->get_charm(),
			'rounding'        => $this->get_rounding(),
			'last_updated'    => $this->get_last_updated(),
		);
	}
}
