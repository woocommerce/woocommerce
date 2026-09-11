<?php
/**
 * Finance data query.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Payments\Finance;

defined( 'ABSPATH' ) || exit;

/**
 * The parameters of a finance data request, built by WooCommerce and read by providers.
 *
 * @since 11.2.0
 */
final class FinanceDataQuery {

	/**
	 * Default number of items per page.
	 *
	 * @var int
	 */
	public const DEFAULT_PER_PAGE = 10;

	/**
	 * Maximum number of items per page.
	 *
	 * @var int
	 */
	public const MAX_PER_PAGE = 100;

	/**
	 * Opaque pagination cursor issued by the provider, or null for the first page.
	 *
	 * @var string|null
	 */
	private ?string $next_cursor;

	/**
	 * Opaque pagination cursor issued by the provider, or null for the first page.
	 *
	 * @var string|null
	 */
	private ?string $prev_cursor;

	/**
	 * Requested number of items per page.
	 *
	 * @var int
	 */
	private int $per_page;

	/**
	 * Constructor.
	 *
	 * @param string|null $next_cursor Pagination cursor from a previous page's next_cursor or prev_cursor, or null for the first page.
	 * @param string|null $prev_cursor Pagination cursor from a previous page's next_cursor or prev_cursor, or null for the first page.
	 * @param int         $per_page    Requested items per page. Limited to 1..MAX_PER_PAGE.
	 *
	 * @since 11.2.0
	 */
	public function __construct( ?string $next_cursor = null, ?string $prev_cursor = null, int $per_page = self::DEFAULT_PER_PAGE ) {
		$this->next_cursor = ( null === $next_cursor || '' === $next_cursor ) ? null : $next_cursor;
		$this->prev_cursor = ( null === $prev_cursor || '' === $prev_cursor ) ? null : $prev_cursor;
		$this->per_page    = max( 1, min( self::MAX_PER_PAGE, $per_page ) );
	}

	/**
	 * Get the pagination cursor for the next page, or null for the first page.
	 *
	 * @return string|null
	 *
	 * @since 11.2.0
	 */
	public function get_next_cursor(): ?string {
		return $this->next_cursor;
	}

	/**
	 * Get the pagination cursor for the previous page, or null for the first page.
	 *
	 * @return string|null
	 *
	 * @since 11.2.0
	 */
	public function get_prev_cursor(): ?string {
		return $this->prev_cursor;
	}

	/**
	 * Get the requested number of items per page.
	 *
	 * @return int
	 *
	 * @since 11.2.0
	 */
	public function get_per_page(): int {
		return $this->per_page;
	}
}
