<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi;

use Automattic\WooCommerce\Api\Attributes\Ignore;
use Automattic\WooCommerce\Api\Pagination\Connection;
use Automattic\WooCommerce\Api\Pagination\Edge;
use Automattic\WooCommerce\Api\Pagination\PageInfo;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Enums\Color;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Enums\Priority;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Types\Gadget;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Types\Widget;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Types\WidgetReview;

/**
 * In-memory fixture data backing the dummy queries / mutations.
 *
 * Carries #[Ignore] so the ApiBuilder skips it during discovery — it lives
 * inside the scanned namespace as a convenience helper, not as a code-API
 * type.
 */
#[Ignore]
final class Store {
	/**
	 * @var array<int, Widget>
	 */
	private static array $widgets = array();

	private static int $next_id = 1;

	public static function reset(): void {
		self::$widgets = array();
		self::$next_id = 1;
		self::seed();
	}

	public static function seed(): void {
		if ( empty( self::$widgets ) ) {
			self::create_widget( 'Alpha', Color::Red, 'alpha' );
			self::create_widget( 'Beta', Color::Green, 'beta' );
		}
	}

	public static function create_widget( string $label, Color $color, string $slug = '' ): Widget {
		$widget                   = new Widget();
		$widget->id               = self::$next_id++;
		$widget->label            = $label;
		$widget->slug             = '' === $slug ? strtolower( $label ) : $slug;
		$widget->caption          = null;
		$widget->color            = $color;
		$widget->priority         = Priority::Normal;
		$widget->tag_ids          = array( 1, 2, 3 );
		$widget->featured_reviews = self::build_reviews( $widget->id, 1 );
		$widget->reviews          = self::build_review_connection( $widget->id, 2 );
		$widget->date_created     = '2024-01-01T00:00:00+00:00';
		$widget->price            = '9.99';
		$widget->legacy_price     = '8.50';
		$widget->internal_notes   = 'do not expose';

		self::$widgets[ $widget->id ] = $widget;
		return $widget;
	}

	public static function get_widget( int $id ): ?Widget {
		return self::$widgets[ $id ] ?? null;
	}

	public static function delete_widget( int $id ): bool {
		if ( ! isset( self::$widgets[ $id ] ) ) {
			return false;
		}
		unset( self::$widgets[ $id ] );
		return true;
	}

	/**
	 * @return array<int, Widget>
	 */
	public static function all_widgets(): array {
		return self::$widgets;
	}

	public static function build_gadget( int $id, string $label, int $parts ): Gadget {
		$gadget              = new Gadget();
		$gadget->id          = $id;
		$gadget->label       = $label;
		$gadget->parts_count = $parts;
		return $gadget;
	}

	/**
	 * @return WidgetReview[]
	 */
	private static function build_reviews( int $widget_id, int $count ): array {
		$reviews = array();
		for ( $i = 1; $i <= $count; $i++ ) {
			$review        = new WidgetReview();
			$review->id    = $widget_id * 100 + $i;
			$review->body  = sprintf( 'Featured review %d for widget %d', $i, $widget_id );
			$review->score = 5;
			$reviews[]     = $review;
		}
		return $reviews;
	}

	private static function build_review_connection( int $widget_id, int $count ): Connection {
		$edges = array();
		$nodes = array();
		for ( $i = 1; $i <= $count; $i++ ) {
			$review        = new WidgetReview();
			$review->id    = $widget_id * 1000 + $i;
			$review->body  = sprintf( 'Review %d for widget %d', $i, $widget_id );
			$review->score = 4;

			$edge         = new Edge();
			$edge->cursor = base64_encode( (string) $review->id );
			$edge->node   = $review;

			$edges[] = $edge;
			$nodes[] = $review;
		}

		$page_info                    = new PageInfo();
		$page_info->has_next_page     = false;
		$page_info->has_previous_page = false;
		$page_info->start_cursor      = $edges[0]->cursor ?? null;
		$page_info->end_cursor        = $edges[ count( $edges ) - 1 ]->cursor ?? null;

		$connection              = new Connection();
		$connection->edges       = $edges;
		$connection->nodes       = $nodes;
		$connection->page_info   = $page_info;
		$connection->total_count = $count;
		return $connection;
	}
}
