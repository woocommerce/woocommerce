<?php
/**
 * CustomFeesStore class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi;

use WC_Cart;
use WC_Session;

/**
 * Session-backed store for POS custom fees ("custom amounts").
 *
 * WC_Cart fees are computed state, not stored state: the fees API is emptied
 * and rebuilt on every totals calculation, so a fee added once would vanish
 * on the next cart operation. This store keeps the fee *specs* in the
 * transaction session and {@see PolicyHooks\CustomFeesPolicy} re-applies them
 * on every calculation.
 *
 * Fee identity derives from the fee's content, so re-sending the same fee
 * (client retry, replayed request) is an idempotent upsert rather than a
 * duplicate charge.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class CustomFeesStore {

	/**
	 * Session key holding the fee specs.
	 */
	private const SESSION_KEY = 'pos_custom_fees';

	/**
	 * The transaction session.
	 *
	 * @var WC_Session
	 */
	private $session;

	/**
	 * Constructor.
	 *
	 * @param WC_Session $session The transaction session.
	 */
	public function __construct( WC_Session $session ) {
		$this->session = $session;
	}

	/**
	 * All stored fee specs, keyed by fee id.
	 *
	 * @return array<string, array{id: string, name: string, amount: float, taxable: bool, tax_class: string}>
	 */
	public function get_all(): array {
		$fees = $this->session->get( self::SESSION_KEY, array() );

		return is_array( $fees ) ? $fees : array();
	}

	/**
	 * Add a fee spec (idempotent by content).
	 *
	 * @param string $name      Fee label shown on the order.
	 * @param float  $amount    Fee amount, excluding tax.
	 * @param bool   $taxable   Whether tax applies to the fee.
	 * @param string $tax_class Tax class when taxable.
	 * @return array{id: string, name: string, amount: float, taxable: bool, tax_class: string} The stored spec.
	 */
	public function add( string $name, float $amount, bool $taxable = false, string $tax_class = '' ): array {
		$spec = array(
			'id'        => '',
			'name'      => $name,
			'amount'    => $amount,
			'taxable'   => $taxable,
			'tax_class' => $tax_class,
		);

		$spec['id'] = 'pos-fee-' . md5( (string) wp_json_encode( array( $name, $amount, $taxable, $tax_class ) ) );

		$fees                = $this->get_all();
		$fees[ $spec['id'] ] = $spec;
		$this->session->set( self::SESSION_KEY, $fees );

		return $spec;
	}

	/**
	 * Apply all stored fee specs to the cart's fees API.
	 *
	 * Called from the `woocommerce_cart_calculate_fees` hook, i.e. inside
	 * every totals calculation.
	 *
	 * @param WC_Cart $cart The cart being calculated.
	 */
	public function apply_to_cart( WC_Cart $cart ): void {
		foreach ( $this->get_all() as $spec ) {
			$cart->fees_api()->add_fee(
				array(
					'id'        => (string) $spec['id'],
					'name'      => (string) $spec['name'],
					'amount'    => (float) $spec['amount'],
					'taxable'   => (bool) $spec['taxable'],
					'tax_class' => (string) $spec['tax_class'],
				)
			);
		}
	}
}
