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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queries product reviews and rating aggregates.
 */
class ProductReviewsQuery extends AbstractDomainAbility implements AbilityDefinition {

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
		return array(
			'label'               => __( 'Query product reviews', 'woocommerce' ),
			'description'         => __( 'Read product reviews, ratings and moderation status. Search matches review text only, never reviewer identity. Summary and per-product ranking cover all reviews in the requested product/status scope, ignoring search, rating range and include. Unrated reviews have rating zero and do not affect averages. Ranking is worst first; above 200 products it keeps the 100 worst and 100 best rated. Text searches omit the ranking. Requested products without reviews and unknown product IDs are reported separately. Reviewer identity is withheld.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_output_schema(),
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
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array( 'type' => 'integer' ),
							'product_id'   => array( 'type' => 'integer' ),
							'product_name' => array( 'type' => 'string' ),
							'date_created' => array( 'type' => array( 'string', 'null' ) ),
							'rating'       => array( 'type' => 'integer' ),
							'verified'     => array( 'type' => 'boolean' ),
							'status'       => array( 'type' => 'string' ),
							'review'       => array( 'type' => 'string' ),
						),
					),
				),
			),
		);
	}

	/**
	 * Moderation statuses mapped to the comment_approved values they cover.
	 * An empty list means every status.
	 */
	private const STATUSES = array(
		'approved' => array( '1' ),
		'hold'     => array( '0' ),
		'spam'     => array( 'spam' ),
		'trash'    => array( 'trash' ),
		'all'      => array(),
	);

	private const STATUS_LABELS = array(
		'1'     => 'approved',
		'0'     => 'hold',
		'spam'  => 'spam',
		'trash' => 'trash',
	);

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
					'enum'        => array_keys( self::STATUSES ),
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
	 * @param mixed $input The input parameters.
	 * @return array|\WP_Error
	 */
	public static function execute( $input = null ) {
		$input       = is_array( $input ) ? $input : array();
		$status      = $input['status'] ?? 'approved';
		$product_ids = array_values( array_unique( $input['product_ids'] ?? array() ) );
		if ( ( $input['min_rating'] ?? 1 ) > ( $input['max_rating'] ?? 5 ) ) {
			return new \WP_Error( 'woocommerce_ability_product_review_invalid_rating_range', __( 'min_rating must not exceed max_rating.', 'woocommerce' ) );
		}

		$scope = array(
			'status'      => $status,
			'product_ids' => $product_ids,
		);

		$filters           = $scope;
		$filters['search'] = trim( (string) ( $input['search'] ?? '' ) );

		if ( isset( $input['min_rating'] ) || isset( $input['max_rating'] ) ) {
			$filters['min_rating'] = (int) ( $input['min_rating'] ?? 1 );
			$filters['max_rating'] = (int) ( $input['max_rating'] ?? 5 );
		}

		$filters['include'] = $input['include'] ?? array();
		$page               = (int) ( $input['page'] ?? 1 );
		$per_page           = (int) ( $input['per_page'] ?? self::DEFAULT_LIMIT );
		$offset             = ( $page - 1 ) * $per_page;
		$rows               = self::fetch( 'c.comment_ID, p.post_title', $filters, "ORDER BY c.comment_date_gmt DESC, c.comment_ID DESC LIMIT {$per_page} OFFSET {$offset}" );
		_prime_comment_caches( array_column( $rows, 'comment_ID' ), true );

		$reviews = array();
		foreach ( $rows as $row ) {
			$comment = get_comment( (int) $row->comment_ID );
			if ( ! $comment ) {
				continue;
			}
			$review = array(
				'id'           => (int) $comment->comment_ID,
				'product_id'   => (int) $comment->comment_post_ID,
				'product_name' => (string) $row->post_title,
				'date_created' => self::format_date( $comment->comment_date_gmt ),
				'rating'       => (int) get_comment_meta( (int) $comment->comment_ID, 'rating', true ),
				'verified'     => (bool) get_comment_meta( (int) $comment->comment_ID, 'verified', true ),
				'status'       => self::STATUS_LABELS[ $comment->comment_approved ] ?? (string) $comment->comment_approved,
				'review'       => wp_strip_all_tags( (string) $comment->comment_content ),
			);
			if ( 1 === count( $product_ids ) ) {
				unset( $review['product_id'], $review['product_name'] );
			}
			if ( 'all' !== $status ) {
				unset( $review['status'] );
			}
			$reviews[] = $review;
		}

		$grouped = self::fetch(
			'c.comment_post_ID, p.post_title, COUNT(*) AS total, SUM( NULLIF( cm.meta_value + 0, 0 ) ) AS rating_sum, COUNT( NULLIF( cm.meta_value + 0, 0 ) ) AS rated',
			$scope,
			'GROUP BY c.comment_post_ID, p.post_title'
		);

		$total_reviews = 0;
		$rating_sum    = 0.0;
		$rated_reviews = 0;
		$by_product    = array();

		foreach ( $grouped as $row ) {
			$total_reviews += (int) $row->total;
			$rating_sum    += (float) $row->rating_sum;
			$rated_reviews += (int) $row->rated;

			$by_product[] = array(
				'product_id'     => (int) $row->comment_post_ID,
				'product_name'   => (string) $row->post_title,
				'review_count'   => (int) $row->total,
				'average_rating' => $row->rated ? round( (float) $row->rating_sum / (int) $row->rated, 2 ) : 0.0,
			);
		}

		$total  = self::fetch( 'COUNT(*)', $filters );
		$result = array(
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => (int) ceil( $total / $per_page ),
			'status'      => $status,
			'count'       => count( $reviews ),
			'summary'     => array(
				'average_rating'   => $rated_reviews ? round( $rating_sum / $rated_reviews, 2 ) : 0.0,
				'total_reviews'    => $total_reviews,
				'matching_reviews' => $total,
				'total_products'   => count( $by_product ),
			),
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
	 * Build the shared FROM/WHERE for every review query.
	 *
	 * Legacy plain comments require rating metadata; modern reviews can be unrated.
	 * Replies are excluded even when WooCommerce stamps them as reviews.
	 *
	 * @param array<string, mixed> $filters status, product_ids, and optionally
	 *                                      min_rating/max_rating/search.
	 * @return array{0: string, 1: array<int, mixed>} SQL fragment and its params.
	 */
	private static function build_query( array $filters ): array {
		global $wpdb;

		$sql = "FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
			LEFT JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID AND cm.meta_key = 'rating'
			WHERE p.post_type = 'product'
			AND c.comment_parent = 0
			AND c.comment_type IN ( 'review', 'comment', '' )
			AND ( c.comment_type = 'review' OR cm.meta_value > 0 )";

		$params = array();

		$approved = self::STATUSES[ $filters['status'] ];
		if ( $approved ) {
			$sql   .= ' AND c.comment_approved IN ( ' . implode( ', ', array_fill( 0, count( $approved ), '%s' ) ) . ' )';
			$params = array_merge( $params, $approved );
		}

		if ( $filters['product_ids'] ) {
			$sql   .= ' AND c.comment_post_ID IN ( ' . implode( ', ', array_fill( 0, count( $filters['product_ids'] ), '%d' ) ) . ' )';
			$params = array_merge( $params, $filters['product_ids'] );
		}

		if ( ! empty( $filters['include'] ) ) {
			$sql   .= ' AND c.comment_ID IN ( ' . implode( ', ', array_fill( 0, count( $filters['include'] ), '%d' ) ) . ' )';
			$params = array_merge( $params, $filters['include'] );
		}

		if ( isset( $filters['min_rating'] ) ) {
			$sql     .= ' AND cm.meta_value + 0 BETWEEN %d AND %d';
			$params[] = $filters['min_rating'];
			$params[] = $filters['max_rating'];
		}

		if ( isset( $filters['search'] ) && '' !== $filters['search'] ) {
			$sql     .= ' AND c.comment_content LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
		}

		return array( $sql, $params );
	}

	/**
	 * Narrow a list of IDs to the ones that are products, whatever their status.
	 *
	 * @param array<int, int> $ids Candidate product IDs.
	 * @return array<int, int>
	 */
	private static function existing_product_ids( array $ids ): array {
		global $wpdb;

		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		$sql          = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND ID IN ( {$placeholders} )";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The ID placeholders are built above.
		$found = $wpdb->get_col( $wpdb->prepare( $sql, $ids ) );

		return array_map( 'intval', $found );
	}

	/**
	 * Run a review query, optionally as a count.
	 *
	 * @param string               $select  Columns to select, or 'COUNT(*)'.
	 * @param array<string, mixed> $filters Filters for build_query().
	 * @param string               $tail    ORDER BY / LIMIT / GROUP BY clause.
	 * @return ($select is 'COUNT(*)' ? int : array<int, \stdClass>)
	 */
	private static function fetch( string $select, array $filters, string $tail = '' ) {
		global $wpdb;

		list( $from, $params ) = self::build_query( $filters );

		$sql = "SELECT {$select} {$from} {$tail}";
		if ( $params ) {
			$sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders are built above.
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Only internal SQL fragments and prepared filter values are used.
		return 'COUNT(*)' === $select ? (int) $wpdb->get_var( $sql ) : $wpdb->get_results( $sql );
	}

	/**
	 * Render a GMT comment date as UTC, or null when there is no usable date.
	 *
	 * Imported and legacy comments can carry '0000-00-00 00:00:00', which
	 * strtotime() rolls into year -1 rather than rejecting.
	 *
	 * @param string|null $date_gmt comment_date_gmt as stored.
	 * @return string|null
	 */
	private static function format_date( ?string $date_gmt ): ?string {
		$date_gmt  = trim( (string) $date_gmt );
		$timestamp = '' === $date_gmt ? false : strtotime( $date_gmt . ' UTC' );

		return $timestamp > 0 ? gmdate( 'Y-m-d\TH:i:s\Z', $timestamp ) : null;
	}

	/**
	 * Order per-product entries worst-rated first and cut them to the cap.
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
					return $b['review_count'] - $a['review_count'];
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
