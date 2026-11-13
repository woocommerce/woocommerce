<?php
/**
 * Product reviews query ability.
 *
 * Reads product reviews and rating aggregates from the site's comments table.
 * Reviewer identity is not included in the output.
 *
 * @package woocommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductReviewAbilityTrait;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queries product reviews and rating aggregates.
 */
class ProductReviewsQuery extends AbstractDomainAbility implements AbilityDefinition {

	use ProductReviewAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/product-reviews-query';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		$output_schema                                   = self::get_output_schema();
		$output_schema['properties']['reviews']['items'] = ExtensibleAbilities::add_fields_schema( $output_schema['properties']['reviews']['items'], 'product_review' );

		return array(
			'label'               => __( 'Query product reviews', 'woocommerce' ),
			'description'         => __( 'Read product reviews, ratings and moderation status. Search matches review text only, never reviewer identity. Summary and per-product ranking cover all reviews in the requested product/status scope, ignoring search, rating range and include. Unrated reviews have rating zero and do not affect averages. Ranking is worst first; above 200 products it keeps the 100 worst and 100 best rated. Text searches omit the ranking. Requested products without reviews and unknown product IDs are reported separately. store_reply_count counts replies from store staff only, so a review with only customer replies has 0. Reviewer identity is withheld.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => $output_schema,
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_query_reviews' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		);
	}

	/**
	 * Check review read access.
	 *
	 * @since 11.4.0
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public static function can_query_reviews( $input = null ): bool {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'moderate_comments' );
	}

	/**
	 * Get the output schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'status'                   => array( 'type' => 'string' ),
				'count'                    => array( 'type' => 'integer' ),
				'total'                    => array( 'type' => 'integer' ),
				'page'                     => array( 'type' => 'integer' ),
				'per_page'                 => array( 'type' => 'integer' ),
				'total_pages'              => array( 'type' => 'integer' ),
				'summary'                  => array(
					'type'       => 'object',
					'properties' => array(
						'average_rating'   => array( 'type' => 'number' ),
						'total_reviews'    => array( 'type' => 'integer' ),
						'matching_reviews' => array( 'type' => 'integer' ),
						'total_products'   => array( 'type' => 'integer' ),
					),
				),
				'search_scope'             => array( 'type' => 'string' ),
				'products_without_reviews' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
				'unknown_product_ids'      => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
				'by_product'               => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'product_id'     => array( 'type' => 'integer' ),
							'product_name'   => array( 'type' => 'string' ),
							'review_count'   => array( 'type' => 'integer' ),
							'average_rating' => array( 'type' => 'number' ),
						),
					),
				),
				'reviews'                  => array(
					'type'  => 'array',
					'items' => self::get_review_output_schema(),
				),
			),
		);
	}

	private const DEFAULT_LIMIT = 20;

	/**
	 * Most per-product entries one call returns. Past this the caller gets both
	 * ends of the ranking and summary.total_products says how many there are.
	 */
	private const MAX_PRODUCTS = 200;

	/**
	 * Get the input schema for this ability.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'product_ids' => array(
					'type'        => 'array',
					'description' => __( 'Only return reviews for these products, and scope the summary and the ranking to them. Omit it to cover every product.', 'woocommerce' ),
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
					'minItems'    => 1,
					'maxItems'    => self::MAX_PRODUCTS,
				),
				'search'      => array(
					'type'        => 'string',
					'description' => __( 'Free-text search across review content.', 'woocommerce' ),
				),
				'status'      => array(
					'type'        => 'string',
					'description' => __( 'Review moderation status. "hold" is reviews awaiting moderation.', 'woocommerce' ),
					'enum'        => array_merge( array_keys( self::get_review_statuses() ), array( 'all' ) ),
					'default'     => 'approved',
				),
				'min_rating'  => array(
					'type'        => 'integer',
					'description' => __( 'Only return reviews with at least this star rating. Use with max_rating to find unhappy customers.', 'woocommerce' ),
					'minimum'     => 1,
					'maximum'     => 5,
				),
				'max_rating'  => array(
					'type'        => 'integer',
					'description' => __( 'Only return reviews with at most this star rating.', 'woocommerce' ),
					'minimum'     => 1,
					'maximum'     => 5,
				),
				'include'     => array(
					'type'        => 'array',
					'description' => __( 'Limit matching reviews to these review IDs. Summary and ranking still cover the product scope.', 'woocommerce' ),
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
					'minItems'    => 1,
					'maxItems'    => 200,
				),
				'page'        => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page'    => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 200,
					'default' => 20,
				),
			),
			'default'              => array(),
			'additionalProperties' => false,
		);
	}

	/**
	 * Execute the review search.
	 *
	 * @since 11.4.0
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error
	 */
	public static function execute( array $input = array() ) {
		$status      = $input['status'] ?? 'approved';
		$product_ids = array_values( array_unique( $input['product_ids'] ?? array() ) );
		if ( ( $input['min_rating'] ?? 1 ) > ( $input['max_rating'] ?? 5 ) ) {
			return new \WP_Error( 'woocommerce_ability_product_review_invalid_rating_range', __( 'min_rating must not exceed max_rating.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$scope = array(
			'status'      => $status,
			'product_ids' => $product_ids,
		);

		$filters           = $scope;
		$filters['search'] = trim( $input['search'] ?? '' );

		if ( isset( $input['min_rating'] ) || isset( $input['max_rating'] ) ) {
			$filters['min_rating'] = $input['min_rating'] ?? 1;
			$filters['max_rating'] = $input['max_rating'] ?? 5;
		}

		$filters['include'] = $input['include'] ?? array();
		$page               = $input['page'] ?? 1;
		$per_page           = $input['per_page'] ?? self::DEFAULT_LIMIT;
		$matches            = self::query_reviews( $filters, $page, $per_page );
		$total              = (int) $matches->found_comments;
		$reviews            = array();
		$with_product       = count( $product_ids ) !== 1;
		$with_status        = 'all' === $status;
		$store_reply_counts = self::get_store_reply_counts( wp_list_pluck( $matches->comments, 'comment_ID' ) );
		foreach ( $matches->comments as $comment ) {
			$comment = get_comment( $comment );
			if ( $comment ) {
				$review = self::format_review( $comment, $with_product, $with_status, $store_reply_counts[ (int) $comment->comment_ID ] ?? 0 );
				if ( is_wp_error( $review ) ) {
					return $review;
				}
				$reviews[] = $review;
			}
		}
		$ratings    = self::get_rating_summary( $scope, $total );
		$by_product = $ratings['by_product'];

		$result = array(
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => (int) ceil( $total / $per_page ),
			'status'      => $status,
			'count'       => count( $reviews ),
			'summary'     => $ratings['summary'],
		);

		if ( $product_ids ) {
			$missing = array_values( array_diff( $product_ids, array_column( $by_product, 'product_id' ) ) );
			$known   = $missing ? self::existing_product_ids( $missing ) : array();

			$result['products_without_reviews'] = array_values( array_intersect( $missing, $known ) );

			$unknown = array_values( array_diff( $missing, $known ) );
			if ( $unknown ) {
				$result['unknown_product_ids'] = $unknown;
			}
		}

		if ( '' !== $filters['search'] ) {
			$result['search_scope'] = 'review_text';
		} else {
			$result['by_product'] = self::rank( $by_product );
		}

		$result['reviews'] = $reviews;

		return $result;
	}

	/**
	 * Query review objects and the matching count using WordPress pagination and caches.
	 *
	 * @param array $filters Review filters.
	 * @param int   $page Current page.
	 * @param int   $per_page Page size.
	 * @return \WP_Comment_Query
	 */
	private static function query_reviews( array $filters, int $page, int $per_page ): \WP_Comment_Query {
		$args = array(
			'post_type'                 => 'product',
			'post__in'                  => $filters['product_ids'],
			'comment__in'               => $filters['include'],
			'type__in'                  => array( 'review', 'comment' ),
			'parent'                    => 0,
			'status'                    => 'all' === $filters['status'] ? 'any' : ( 'approved' === $filters['status'] ? 'approve' : $filters['status'] ),
			'number'                    => $per_page,
			'offset'                    => ( $page - 1 ) * $per_page,
			'orderby'                   => array(
				'comment_date_gmt' => 'DESC',
				'comment_ID'       => 'DESC',
			),
			'no_found_rows'             => false,
			'update_comment_post_cache' => true,
			// The text-only search is added through SQL, so include it in the cache key.
			'cache_domain'              => 'woocommerce-product-reviews-' . md5( $filters['search'] ),
		);
		if ( isset( $filters['min_rating'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- WordPress stores review ratings in comment metadata.
			$args['meta_query'] = array(
				array(
					'key'     => 'rating',
					'value'   => array( $filters['min_rating'], $filters['max_rating'] ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
			);
		}

		$query  = new \WP_Comment_Query();
		$filter = static function ( array $clauses, \WP_Comment_Query $current ) use ( $query, $filters ): array {
			if ( $current !== $query ) {
				return $clauses;
			}
			global $wpdb;
			// Modern reviews may be unrated; legacy plain comments require a rating.
			$clauses['where'] .= " AND ( {$wpdb->comments}.comment_type = 'review' OR EXISTS (
				SELECT 1 FROM {$wpdb->commentmeta} legacy_rating
				WHERE legacy_rating.comment_id = {$wpdb->comments}.comment_ID
				AND legacy_rating.meta_key = 'rating' AND legacy_rating.meta_value + 0 > 0
			) )";
			// WordPress's normal comment search also matches reviewer identity.
			if ( '' !== $filters['search'] ) {
				$clauses['where'] .= $wpdb->prepare( " AND {$wpdb->comments}.comment_content LIKE %s", '%' . $wpdb->esc_like( $filters['search'] ) . '%' );
			}
			return $clauses;
		};
		add_filter( 'comments_clauses', $filter, 10, 2 );
		try {
			$query->query( $args );
			if ( ! $query->comments ) {
				unset( $args['number'], $args['offset'] );
				$args['count']         = true;
				$query->found_comments = (int) $query->query( $args );
			}
			return $query;
		} finally {
			remove_filter( 'comments_clauses', $filter, 10 );
		}
	}

	/**
	 * Aggregate all reviews in the product/status scope, independently of the page filters.
	 *
	 * @param array $scope Product and status scope.
	 * @param int   $matching_reviews Total matching the page filters.
	 * @return array
	 */
	private static function get_rating_summary( array $scope, int $matching_reviews ): array {
		global $wpdb;
		$where    = "p.post_type = 'product' AND c.comment_parent = 0
			AND c.comment_type IN ( 'review', 'comment', '' )
			AND ( c.comment_type = 'review' OR cm.meta_value > 0 )";
		$params   = array();
		$approved = self::get_review_statuses()[ $scope['status'] ] ?? null;
		if ( null !== $approved ) {
			$where   .= ' AND c.comment_approved = %s';
			$params[] = $approved;
		}
		if ( $scope['product_ids'] ) {
			$where .= ' AND c.comment_post_ID IN ( ' . implode( ', ', array_fill( 0, count( $scope['product_ids'] ), '%d' ) ) . ' )';
			$params = array_merge( $params, $scope['product_ids'] );
		}
		$sql = "SELECT c.comment_post_ID, p.post_title, COUNT(*) AS total,
			SUM( NULLIF( cm.meta_value + 0, 0 ) ) AS rating_sum, COUNT( NULLIF( cm.meta_value + 0, 0 ) ) AS rated
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
			LEFT JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID AND cm.meta_key = 'rating'
			WHERE {$where} GROUP BY c.comment_post_ID, p.post_title";
		if ( $params ) {
			$sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Only internal clauses and placeholders are combined above.
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Filter values are prepared above.
		$grouped       = $wpdb->get_results( $sql );
		$total_reviews = 0;
		$rating_sum    = 0.0;
		$rated_reviews = 0;
		$by_product    = array();
		foreach ( $grouped as $row ) {
			$total_reviews += (int) $row->total;
			$rating_sum    += (float) $row->rating_sum;
			$rated_reviews += (int) $row->rated;
			$by_product[]   = array(
				'product_id'     => (int) $row->comment_post_ID,
				'product_name'   => (string) $row->post_title,
				'review_count'   => (int) $row->total,
				'average_rating' => $row->rated ? round( (float) $row->rating_sum / (int) $row->rated, 2 ) : 0.0,
			);
		}
		return array(
			'summary'    => array(
				'average_rating'   => $rated_reviews ? round( $rating_sum / $rated_reviews, 2 ) : 0.0,
				'total_reviews'    => $total_reviews,
				'matching_reviews' => $matching_reviews,
				'total_products'   => count( $by_product ),
			),
			'by_product' => $by_product,
		);
	}

	/**
	 * Find requested products regardless of their publication status.
	 *
	 * @param int[] $ids Candidate product IDs.
	 * @return int[]
	 */
	private static function existing_product_ids( array $ids ): array {
		return get_posts(
			array(
				'post_type'   => 'product',
				'post_status' => array_keys( get_post_stati() ),
				'post__in'    => $ids,
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
	}

	/**
	 * Order products by rating, review count, then product ID, and apply the cap.
	 *
	 * Unrated products sort last. Above the cap, retain both ends of the rated ranking.
	 *
	 * @param array<int, array<string, mixed>> $by_product One entry per product.
	 * @return array<int, array<string, mixed>>
	 */
	private static function rank( array $by_product ): array {
		usort(
			$by_product,
			static function ( $a, $b ) {
				if ( ( 0.0 === $a['average_rating'] ) !== ( 0.0 === $b['average_rating'] ) ) {
					return 0.0 === $a['average_rating'] ? 1 : -1;
				}
				if ( $a['average_rating'] === $b['average_rating'] ) {
					if ( $a['review_count'] !== $b['review_count'] ) {
						return $b['review_count'] - $a['review_count'];
					}
					return $a['product_id'] <=> $b['product_id'];
				}
				return $a['average_rating'] < $b['average_rating'] ? -1 : 1;
			}
		);

		if ( count( $by_product ) <= self::MAX_PRODUCTS ) {
			return $by_product;
		}

		$rated = array_values(
			array_filter(
				$by_product,
				static function ( $entry ) {
					return $entry['average_rating'] > 0.0;
				}
			)
		);

		if ( count( $rated ) <= self::MAX_PRODUCTS ) {
			return $rated;
		}

		$half = intdiv( self::MAX_PRODUCTS, 2 );

		return array_merge( array_slice( $rated, 0, $half ), array_slice( $rated, -$half ) );
	}
}
