<?php
/**
 * WooCommerce tax calculation class.
 *
 * @class   WC_Tax_Calculator
 * @package WooCommerce/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Tax_Calculator class.
 */
class WC_Tax_Calculator {
	/**
	 * Precision.
	 *
	 * @var int
	 */
	protected $precision = 0;

	/**
	 * Tax inclusive.
	 *
	 * @var bool
	 */
	protected $inclusive = false;

	/**
	 * Tax rate.
	 *
	 * @var float
	 */
	protected $tax_rate = 0.0;

	/**
	 * Before tax amount.
	 *
	 * @var float
	 */
	protected $before_tax;

	/**
	 * Total including tax.
	 *
	 * @var float
	 */
	protected $total;

	/**
	 * Calculated before tax amount.
	 *
	 * @var float
	 */
	protected $calculated_before_tax;

	/**
	 * Calculated tax amount.
	 *
	 * @var float
	 */
	protected $calculated_tax;
	/**
	 * Calculated total including tax.
	 *
	 * @var float
	 */
	protected $calculated_total;

	/**
	 * Calculate amounts.
	 */
	public function calculate() {
		if ( $this->inclusive ) {
			$this->calculate_tax_inclusive();
		} else {
			$this->calculate_tax_exclusive();
		}
	}

	/**
	 * Calculate base amount and tax when tax is inclusive.
	 */
	private function calculate_tax_inclusive() {
		if ( is_null( $this->total ) || 0 == ( $this->tax_rate - 1.0 ) ) {
			return;
		}

		$this->calculated_total      = null;
		$this->calculated_before_tax = $this->round( $this->total / ( 1.0 + $this->tax_rate ) );
		$this->calculated_tax        = $this->total - $this->calculated_tax;
	}

	/**
	 * Calculate tax and total when tax is exclusive.
	 */
	private function calculate_tax_exclusive() {
		if ( is_null( $this->before_tax ) ) {
			return;
		}

		$this->calculated_before_tax = null;
		$this->calculated_tax        = $this->round( $this->before_tax * $this->tax_rate );
		$this->calculated_total      = $this->before_tax + $this->calculated_tax;
	}

	/**
	 * Round to precision.
	 *
	 * @param float $amount Amount to be rounded.
	 * @return float Rounded amount.
	 */
	protected function round( $amount ) {
		return round( $amount, $this->precision );
	}

	/**
	 * Getters and Setters.
	 */

	/**
	 * Before tax amount getter.
	 *
	 * @return float Calculated or set base amount.
	 */
	public function get_before_tax() {
		return ! is_null( $this->calculated_before_tax ) ? $this->calculated_before_tax : $this->before_tax;
	}

	/**
	 * Tax amount getter.
	 *
	 * @return float Calculated tax.
	 */
	public function get_tax_amount() {
		return (float) $this->calculated_tax;
	}

	/**
	 * Total including tax getter.
	 *
	 * @return float Calculated or set total.
	 */
	public function get_total() {
		return ! is_null( $this->calculated_total ) ? $this->calculated_total : $this->total;
	}

	/**
	 * Precision setter.
	 *
	 * @param int $precision Rounding precision.
	 */
	public function set_precision( $precision ) {
		$this->precision = (int) $precision;
	}

	/**
	 * Inclusive setter.
	 *
	 * @param bool $inclusive Calculate taxes inclusive.
	 */
	public function set_inclusive( $inclusive ) {
		$this->inclusive = (bool) $inclusive;
	}

	/**
	 * Tax percentage setter.
	 *
	 * @param float $tax_rate Tax percentage for calculation.
	 */
	public function set_tax_rate( $tax_rate ) {
		$this->tax_rate = (float) $tax_rate / 100.0;
	}

	/**
	 * Before tax amount setter.
	 *
	 * @param float $before_tax Before tax amount.
	 */
	public function set_before_tax( $before_tax ) {
		$this->before_tax = $this->round( (float) $before_tax );
	}

	/**
	 * Total including tax setter.
	 *
	 * @param float $total Tax inclusive total.
	 */
	public function set_total( $total ) {
		$this->total = $this->round( (float) $total );
	}
}
