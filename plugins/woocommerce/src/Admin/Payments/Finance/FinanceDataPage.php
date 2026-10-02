<?php
/**
 * Finance data page.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Payments\Finance;

defined( 'ABSPATH' ) || exit;

/**
 * One page of finance data items plus the cursor pagination state, returned by providers.
 *
 * @since 11.2.0
 */
final class FinanceDataPage {

	/**
	 * The items on this page.
	 *
	 * @var object[]
	 */
	private array $items;

	/**
	 * Whether more items exist beyond this page.
	 *
	 * @var bool
	 */
	private bool $has_more;

	/**
	 * Cursor for the next page, or null when there is none.
	 *
	 * @var string|null
	 */
	private ?string $next_cursor;

	/**
	 * Cursor for the previous page, or null when there is none.
	 *
	 * @var string|null
	 */
	private ?string $prev_cursor;

	/**
	 * Constructor.
	 *
	 * @param object[]    $items       The items on this page, for example V1\Balance or V1\Payout objects.
	 * @param bool        $has_more    Whether more items exist beyond this page.
	 * @param string|null $next_cursor Opaque cursor for the next page, or null when there is none.
	 * @param string|null $prev_cursor Opaque cursor for the previous page, or null when there is none.
	 * @throws \InvalidArgumentException When an item is not an object.
	 *
	 * @since 11.2.0
	 */
	public function __construct( array $items, bool $has_more = false, ?string $next_cursor = null, ?string $prev_cursor = null ) {
		foreach ( $items as $item ) {
			if ( ! is_object( $item ) ) {
				throw new \InvalidArgumentException( 'Finance data page items must be objects.' );
			}
		}

		$this->items       = array_values( $items );
		$this->has_more    = $has_more;
		$this->next_cursor = '' === $next_cursor ? null : $next_cursor;
		$this->prev_cursor = '' === $prev_cursor ? null : $prev_cursor;
	}

	/**
	 * Get the items on this page.
	 *
	 * @return object[]
	 *
	 * @since 11.2.0
	 */
	public function get_items(): array {
		return $this->items;
	}

	/**
	 * Whether more items exist beyond this page.
	 *
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	public function has_more(): bool {
		return $this->has_more;
	}

	/**
	 * Get the cursor for the next page, or null when there is none.
	 *
	 * @return string|null
	 *
	 * @since 11.2.0
	 */
	public function get_next_cursor(): ?string {
		return $this->next_cursor;
	}

	/**
	 * Get the cursor for the previous page, or null when there is none.
	 *
	 * @return string|null
	 *
	 * @since 11.2.0
	 */
	public function get_prev_cursor(): ?string {
		return $this->prev_cursor;
	}
}
