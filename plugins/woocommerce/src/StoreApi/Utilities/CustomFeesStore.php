<?php
/**
 * CustomFeesStore class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use WC_Cart;
use WC_Session;

/**
 * Session-backed store for ad-hoc custom fees added through the Store API.
 *
 * WooCommerce fees are never persisted: every cart calculation clears them and
 * re-fires `woocommerce_cart_calculate_fees`, so a fee added in one request is
 * gone by the next unless something re-applies it. This store keeps the fee
 * *specs* in the WC session — the same mechanism by which applied coupons
 * persist as codes — so a consumer can re-apply them on every calculation via
 * {@see self::apply_to_cart()} (hooked on `woocommerce_cart_calculate_fees`).
 * Once the order is created the cart fees are copied to order fee line items by
 * the normal checkout flow, so persistence only has to span the cart-building
 * phase, which the session covers.
 *
 * Fee identity is content-derived (name + amount + tax), so adding the same fee
 * twice is an idempotent upsert — a retried request can't create a duplicate.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
final class CustomFeesStore {

	/**
	 * Session key under which the fee specs are stored.
	 */
	private const SESSION_KEY = 'store_api_custom_fees';

	/**
	 * Session the fee specs are read from and written to.
	 *
	 * @var WC_Session
	 */
	private $session;

	/**
	 * Constructor.
	 *
	 * @param WC_Session $session Session to persist fee specs in.
	 */
	public function __construct( WC_Session $session ) {
		$this->session = $session;
	}

	/**
	 * All stored fee specs, keyed by fee id.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_all(): array {
		$fees = $this->session->get( self::SESSION_KEY, array() );
		return is_array( $fees ) ? $fees : array();
	}

	/**
	 * Whether a fee with the given id is stored.
	 *
	 * @param string $id Fee id.
	 * @return bool
	 */
	public function has( string $id ): bool {
		return array_key_exists( $id, $this->get_all() );
	}

	/**
	 * Store a fee spec, keyed by a content-derived id. Adding an identical fee
	 * again replaces it in place, so the operation is idempotent.
	 *
	 * @param string $name      Display name for the fee.
	 * @param float  $amount    Fee amount (callers must reject non-positive values).
	 * @param bool   $taxable   Whether the fee is taxable.
	 * @param string $tax_class Tax class slug when taxable; empty string is the standard class.
	 * @return array<string, mixed> The stored spec, including its generated `id`.
	 */
	public function add( string $name, float $amount, bool $taxable = false, string $tax_class = '' ): array {
		$spec = array(
			'id'        => '',
			'name'      => $name,
			'amount'    => $amount,
			'taxable'   => $taxable,
			'tax_class' => $tax_class,
		);

		$spec['id'] = $this->generate_id( $spec );

		$fees                = $this->get_all();
		$fees[ $spec['id'] ] = $spec;
		$this->save( $fees );

		return $spec;
	}

	/**
	 * Remove a stored fee by id.
	 *
	 * @param string $id Fee id.
	 * @return bool True if a fee was removed, false if no fee had that id.
	 */
	public function remove( string $id ): bool {
		$fees = $this->get_all();

		if ( ! array_key_exists( $id, $fees ) ) {
			return false;
		}

		unset( $fees[ $id ] );
		$this->save( $fees );

		return true;
	}

	/**
	 * Re-apply every stored fee to a cart. Intended as the
	 * `woocommerce_cart_calculate_fees` callback so the fees survive the
	 * per-request fee reset.
	 *
	 * @param WC_Cart $cart Cart to apply the fees to.
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

	/**
	 * Persist the fee specs, clearing the session key when none remain.
	 *
	 * @param array<string, array<string, mixed>> $fees Fee specs keyed by id.
	 */
	private function save( array $fees ): void {
		$this->session->set( self::SESSION_KEY, empty( $fees ) ? null : $fees );
	}

	/**
	 * Derive a stable fee id from the fee's content so identical fees collapse
	 * to one entry (idempotent) while distinct fees stay separate.
	 *
	 * @param array<string, mixed> $spec Fee spec without its id.
	 * @return string
	 */
	private function generate_id( array $spec ): string {
		return 'custom-fee-' . md5(
			(string) wp_json_encode(
				array( $spec['name'], $spec['amount'], $spec['taxable'], $spec['tax_class'] )
			)
		);
	}
}
