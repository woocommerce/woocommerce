<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Pagination;

/**
 * Represents a Relay-style paginated connection.
 */
class Connection {
	/**
	 * Connection edges wrapping each node with its cursor.
	 *
	 * @var Edge[]
	 */
	public array $edges;

	/**
	 * The raw nodes without cursor wrappers.
	 *
	 * @var object[]
	 */
	public array $nodes;

	public PageInfo $page_info;

	public int $total_count;

	/**
	 * Whether this connection has already been sliced.
	 *
	 * When true, subsequent calls to slice() return $this immediately,
	 * preventing double-slicing when both the command class and the
	 * auto-generated resolver call slice().
	 *
	 * @var bool
	 */
	private bool $sliced = false;

	/**
	 * Create a pre-sliced connection for the performance path.
	 *
	 * Use this when the DB query already applied pagination limits,
	 * so no further slicing is needed.
	 *
	 * @param Edge[]   $edges       The already-paginated edges.
	 * @param PageInfo $page_info   The pagination info.
	 * @param int      $total_count The total count before pagination.
	 * @return self A Connection marked as already sliced.
	 */
	public static function pre_sliced( array $edges, PageInfo $page_info, int $total_count ): self {
		$connection              = new self();
		$connection->edges       = $edges;
		$connection->nodes       = array_map( fn( Edge $e ) => $e->node, $edges );
		$connection->page_info   = $page_info;
		$connection->total_count = $total_count;
		$connection->sliced      = true;

		return $connection;
	}

	/**
	 * Return a new Connection sliced according to the given pagination args.
	 *
	 * Applies the Relay cursor-based pagination algorithm: first narrow by
	 * after/before cursors, then take first N or last N from the remainder.
	 *
	 * @param array $args Pagination arguments with keys: first, last, after, before.
	 * @return self A new Connection with sliced edges/nodes and updated page_info.
	 */
	public function slice( array $args ): self {
		if ( $this->sliced ) {
			return $this;
		}

		// Enforce the same 0..MAX_PAGE_SIZE bounds that PaginationParams
		// applies to root queries. Without this, nested connection fields
		// (e.g. `variations(first: 1000)`) would slip past the cap because
		// the generated resolver passes raw GraphQL args straight in.
		PaginationParams::validate_args( $args );

		$first  = $args['first'] ?? null;
		$last   = $args['last'] ?? null;
		$after  = $args['after'] ?? null;
		$before = $args['before'] ?? null;

		// No pagination requested — return as-is.
		if ( null === $first && null === $last && null === $after && null === $before ) {
			return $this;
		}

		$edges = $this->edges;

		// Narrow by "after" cursor.
		if ( null !== $after ) {
			$found = false;
			foreach ( $edges as $i => $edge ) {
				if ( $edge->cursor === $after ) {
					$edges = array_slice( $edges, $i + 1 );
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$edges = array();
			}
		}

		// Narrow by "before" cursor.
		if ( null !== $before ) {
			$filtered = array();
			foreach ( $edges as $edge ) {
				if ( $edge->cursor === $before ) {
					break;
				}
				$filtered[] = $edge;
			}
			$edges = $filtered;
		}

		$total_after_cursors = count( $edges );

		// Apply first/last.
		if ( null !== $first && $first >= 0 ) {
			$edges = array_slice( $edges, 0, $first );
		}
		if ( null !== $last && $last >= 0 ) {
			$edges = array_slice( $edges, max( 0, count( $edges ) - $last ) );
		}

		// Build the sliced connection.
		$connection              = new self();
		$connection->edges       = array_values( $edges );
		$connection->nodes       = array_map( fn( Edge $e ) => $e->node, $edges );
		$connection->total_count = $this->total_count;
		$connection->sliced      = true;

		$page_info                    = new PageInfo();
		$page_info->start_cursor      = ! empty( $edges ) ? $edges[0]->cursor : null;
		$page_info->end_cursor        = ! empty( $edges ) ? $edges[ count( $edges ) - 1 ]->cursor : null;
		$page_info->has_next_page     = null !== $first ? count( $edges ) < $total_after_cursors : $this->page_info->has_next_page;
		$page_info->has_previous_page = null !== $last ? count( $edges ) < $total_after_cursors : ( null !== $after );
		$connection->page_info        = $page_info;

		return $connection;
	}
}
