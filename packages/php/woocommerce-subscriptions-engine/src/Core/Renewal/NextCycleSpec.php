<?php
/**
 * NextCycleSpec - the computed shape of the next cycle a contract advances into:
 * the period boundaries, the expected total, and the currency.
 *
 * Immutable value object returned by {@see RenewalCalculator::compute_next_cycle()}.
 * It is the pure Core seam the Integration layer freezes onto the new cycle row
 * (the same shape a later renewal service returns). `expected_total` is held on the
 * storage money scale; timestamps are GMT strings (`Y-m-d H:i:s`). WordPress-free
 * Core zone.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\MoneyScale;

defined( 'ABSPATH' ) || exit;

/**
 * NextCycleSpec value object.
 *
 * Immutable.
 */
final class NextCycleSpec {

	use MoneyScale;

	/**
	 * Period start (and, billing-in-advance, the charge date). GMT string.
	 *
	 * @var string
	 */
	private $starts_at_gmt;

	/**
	 * Period end. GMT string.
	 *
	 * @var string
	 */
	private $ends_at_gmt;

	/**
	 * Amount expected to be billed for the next cycle (decimal-safe string).
	 *
	 * @var string
	 */
	private $expected_total;

	/**
	 * ISO-4217 currency code.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * Build a next-cycle spec.
	 *
	 * @param string $starts_at_gmt  Period start (GMT string).
	 * @param string $ends_at_gmt    Period end (GMT string).
	 * @param string $expected_total Amount expected to be billed (decimal string or number).
	 * @param string $currency       ISO-4217 currency code.
	 */
	public function __construct( string $starts_at_gmt, string $ends_at_gmt, string $expected_total, string $currency ) {
		$this->starts_at_gmt  = $starts_at_gmt;
		$this->ends_at_gmt    = $ends_at_gmt;
		$this->expected_total = self::normalize_money( $expected_total );
		$this->currency       = $currency;
	}

	/**
	 * Period start (GMT string).
	 */
	public function get_starts_at_gmt(): string {
		return $this->starts_at_gmt;
	}

	/**
	 * Period end (GMT string).
	 */
	public function get_ends_at_gmt(): string {
		return $this->ends_at_gmt;
	}

	/**
	 * Amount expected to be billed (decimal-safe string, storage scale).
	 */
	public function get_expected_total(): string {
		return $this->expected_total;
	}

	/**
	 * ISO-4217 currency code.
	 */
	public function get_currency(): string {
		return $this->currency;
	}
}
