<?php
/**
 * Order_Ref - an immutable reference to a WooCommerce order by id.
 *
 * The Core zone never loads a live order object; it holds a reference and
 * commands effects through the Orders host binding in the integration layer.
 *
 * @package WooCommerce\Subscriptions\Engine\Core\ValueObject
 */

declare( strict_types=1 );

namespace WooCommerce\Subscriptions\Engine\Core\ValueObject;

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
	 */
	public function __construct( int $id ) {
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
