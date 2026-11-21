<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\SharedStores;

/**
 * Read-only, by-ID view of one member (`products` or `productVariations`) of the
 * `woocommerce/products` server state, placed in the `woocommerce` server state.
 *
 * Each read returns what `woocommerce/products` holds under that ID at that moment,
 * and the view serializes as an empty object, so the page carries each record once.
 *
 * Temporary: once every block reads product data from the `woocommerce` store, that
 * store holds the data itself, these views go away, and code that reads it by ID
 * doesn't change.
 *
 * @internal
 *
 * @implements \ArrayAccess<int|string, array|null>
 */
final class ProductDataView implements \ArrayAccess, \JsonSerializable {

	/**
	 * The `woocommerce/products` state member this view reads.
	 *
	 * @var string
	 */
	private string $member;

	/**
	 * Constructor.
	 *
	 * @param string $member The `woocommerce/products` state member to read: `products` or `productVariations`.
	 */
	public function __construct( string $member ) {
		$this->member = $member;
	}

	/**
	 * Whether `woocommerce/products` holds a record under this ID.
	 *
	 * @param mixed $offset The product or variation ID.
	 * @return bool
	 */
	public function offsetExists( $offset ): bool {
		return null !== $this->offsetGet( $offset );
	}

	/**
	 * The record `woocommerce/products` holds under this ID.
	 *
	 * @param mixed $offset The product or variation ID.
	 * @return array|null The record in Store API shape, or null when there is none.
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		$state = wp_interactivity_state( 'woocommerce/products' );

		return $state[ $this->member ][ $offset ] ?? null;
	}

	/**
	 * Writing through the view is not supported: product data is put in `woocommerce/products`.
	 *
	 * @param mixed $offset The product or variation ID.
	 * @param mixed $value  The record.
	 * @return void
	 * @throws \LogicException Always.
	 */
	public function offsetSet( $offset, $value ): void {
		throw new \LogicException( 'Product data is read-only in the woocommerce store. Load it into woocommerce/products instead.' );
	}

	/**
	 * Removing through the view is not supported: product data is put in `woocommerce/products`.
	 *
	 * @param mixed $offset The product or variation ID.
	 * @return void
	 * @throws \LogicException Always.
	 */
	public function offsetUnset( $offset ): void {
		throw new \LogicException( 'Product data is read-only in the woocommerce store. Load it into woocommerce/products instead.' );
	}

	/**
	 * Serialize as an empty object, so the records are sent to the page only under `woocommerce/products`.
	 *
	 * @return object
	 */
	public function jsonSerialize(): object {
		return new \stdClass();
	}
}
