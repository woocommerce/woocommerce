<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries;

use Automattic\WooCommerce\Api\Attributes\ConnectionOf;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\Attributes\Unroll;
use Automattic\WooCommerce\Api\Pagination\Connection;
use Automattic\WooCommerce\Api\Pagination\Edge;
use Automattic\WooCommerce\Api\Pagination\PageInfo;
use Automattic\WooCommerce\Api\Pagination\PaginationParams;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Enums\Priority;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\InputTypes\WidgetFilterInput;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Store;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Types\Widget;

/**
 * Lists widgets, exercising:
 * - class-level #[Unroll] (via PaginationParams) on a parameter.
 * - parameter-level #[Unroll] on the filters argument.
 * - multiple #[RequiredCapability] attributes.
 * - the infrastructure `_query_info` parameter.
 * - #[ConnectionOf] on the execute method.
 */
#[Name( 'widgets' )]
#[Description( 'List widgets with cursor-based pagination' )]
#[RequiredCapability( 'manage_options' )]
#[RequiredCapability( 'edit_posts' )]
class ListWidgets {
	#[ConnectionOf( Widget::class )]
	public function execute(
		PaginationParams $pagination,
		#[Unroll]
		WidgetFilterInput $filters,
		#[Description( 'A second filter applied after the unrolled ones' )]
		?Priority $min_priority = null,
		?array $_query_info = null,
	): Connection {
		unset( $_query_info );

		$widgets = array_values( Store::all_widgets() );

		if ( null !== $filters->color ) {
			$widgets = array_values(
				array_filter(
					$widgets,
					static fn( Widget $w ): bool => $w->color === $filters->color
				)
			);
		}
		if ( null !== $filters->search ) {
			$needle  = $filters->search;
			$widgets = array_values(
				array_filter(
					$widgets,
					static fn( Widget $w ): bool => str_contains( strtolower( $w->label ), strtolower( $needle ) )
				)
			);
		}
		if ( null !== $min_priority ) {
			$widgets = array_values(
				array_filter(
					$widgets,
					static fn( Widget $w ): bool => $w->priority === $min_priority
				)
			);
		}

		$total = count( $widgets );

		$limit = $pagination->first ?? $pagination->last ?? PaginationParams::get_default_page_size();
		$page  = array_slice( $widgets, 0, $limit );

		$edges = array();
		$nodes = array();
		foreach ( $page as $widget ) {
			$edge         = new Edge();
			$edge->cursor = base64_encode( (string) $widget->id );
			$edge->node   = $widget;
			$edges[]      = $edge;
			$nodes[]      = $widget;
		}

		$page_info                    = new PageInfo();
		$page_info->has_next_page     = count( $page ) < $total;
		$page_info->has_previous_page = false;
		$page_info->start_cursor      = $edges[0]->cursor ?? null;
		$page_info->end_cursor        = $edges[ count( $edges ) - 1 ]->cursor ?? null;

		$connection              = new Connection();
		$connection->edges       = $edges;
		$connection->nodes       = $nodes;
		$connection->page_info   = $page_info;
		$connection->total_count = $total;

		return $connection;
	}
}
