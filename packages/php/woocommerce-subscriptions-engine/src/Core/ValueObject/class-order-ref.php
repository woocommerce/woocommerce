<?php
/**
 * Order_Ref - an immutable reference to a WooCommerce order by id.
 *
 * The Core zone never loads a live order object; it holds a reference and
 * commands effects through the Orders host binding in the integration layer.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject;

defined( 'ABSPATH' ) || exit;

/**
 * Order_Ref value object.
 *
 * Immutable identity wrapper.
 */
final class Order_Ref {

	/**
	 * Order id.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Build an order reference.
	 *
	 * @param int $id Order id.
	 * @throws \InvalidArgumentException If the order id is not greater than 0.
	 */
	public function __construct( int $id ) {
		if ( $id <= 0 ) {
			throw new \InvalidArgumentException( 'Order id must be greater than 0.' );
		}
		$this->id = $id;
	}

	/**
	 * The referenced order id.
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Value equality by id.
	 *
	 * @param Order_Ref $other Reference to compare against.
	 */
	public function equals( Order_Ref $other ): bool {
		return $this->id === $other->id;
	}
}
