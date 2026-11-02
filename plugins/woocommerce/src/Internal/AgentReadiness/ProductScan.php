<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * One pass over the published catalog for the readiness checks.
 *
 * Walks published simple and variable products in batches, most recently
 * modified first, tests each against every rule once and tallies product
 * statuses per channel. Stops after PRODUCT_LIMIT products so the scan stays
 * inside one request. Products hidden from the shop catalog are the merchant's
 * choice and other product types are not supported by agents yet, so both are
 * counted, not checked.
 *
 * @since 11.4.0
 */
class ProductScan {

	public const BATCH_SIZE = 200;

	public const PRODUCT_LIMIT = 5000;

	public const SUPPORTED_TYPES = array( 'simple', 'variable' );

	public const NOT_LISTABLE = 'not_listable';
	public const WITH_GAPS    = 'with_gaps';
	public const COMPLETE     = 'complete';

	/**
	 * Count and product ids per rule id.
	 *
	 * @var array<string, array{count:int,product_ids:array<int,int>}>
	 */
	private array $results = array();

	/**
	 * Products tested.
	 *
	 * @var int
	 */
	private int $products = 0;

	/**
	 * Published products of a supported type hidden from the shop catalog.
	 *
	 * @var int
	 */
	private int $hidden_from_catalog = 0;

	/**
	 * Published products of a type agents don't support yet.
	 *
	 * @var int
	 */
	private int $unsupported_types = 0;

	/**
	 * Product statuses by channel id.
	 *
	 * @var array<string, array<string, int>>
	 */
	private array $statuses = array();

	/**
	 * Normalised GTIN keys carried by more than one published product.
	 *
	 * @var array<string, true>|null
	 */
	private ?array $shared_gtins = null;

	/**
	 * Whether the catalog has more products than PRODUCT_LIMIT.
	 *
	 * @var bool
	 */
	private bool $truncated = false;

	/**
	 * Whether the readiness checks test this product.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function covers( \WC_Product $product ): bool {
		return 'publish' === $product->get_status()
			&& ! $product instanceof \WC_Product_Variation
			&& in_array( self::stored_type( $product ), self::SUPPORTED_TYPES, true )
			&& ! in_array( $product->get_catalog_visibility(), array( 'search', 'hidden' ), true );
	}

	/**
	 * The product type as stored, which the scan query reads. A product whose
	 * type's plugin is inactive loads as simple, but keeps its stored type.
	 *
	 * @param \WC_Product $product Product.
	 */
	private static function stored_type( \WC_Product $product ): string {
		$type = \WC_Product_Factory::get_product_type( $product->get_id() );
		return is_string( $type ) && '' !== $type ? $type : 'simple';
	}

	/**
	 * Test every covered product against the rules.
	 *
	 * @param array<string, ReadinessRule>                                    $rules         Rules by id.
	 * @param array<string, array<string, array{field:string,effect:string}>> $channel_rules Channel map by rule id.
	 * @param int                                                             $ids_limit     Product ids kept per rule.
	 */
	public function evaluate( array $rules, array $channel_rules, int $ids_limit ): void {
		$this->results             = array_fill_keys(
			array_keys( $rules ),
			array(
				'count'       => 0,
				'product_ids' => array(),
			)
		);
		$this->products            = 0;
		$this->statuses            = array();
		$this->truncated           = false;
		$this->hidden_from_catalog = self::count_published( array( self::supported_type_query(), self::catalog_query( 'IN' ) ) );
		$this->unsupported_types   = self::count_published(
			array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => self::SUPPORTED_TYPES,
					'operator' => 'NOT IN',
				),
				array(
					'taxonomy' => 'product_type',
					'operator' => 'EXISTS',
				),
			)
		);

		$flush_runtime_cache = wp_cache_supports( 'flush_runtime' );
		$page                = 1;
		do {
			$query = new \WP_Query(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => self::BATCH_SIZE,
					'paged'          => $page,
					'fields'         => 'ids',
					'orderby'        => array(
						'modified' => 'DESC',
						'ID'       => 'DESC',
					),
					'no_found_rows'  => true,
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						self::supported_type_query(),
						self::catalog_query( 'NOT IN' ),
					),
				)
			);

			$product_ids = array_map(
				static function ( $post ): int {
					return $post instanceof \WP_Post ? $post->ID : (int) $post;
				},
				$query->posts
			);
			$batch_count = count( $product_ids );
			self::prime_related( $product_ids );
			foreach ( $product_ids as $product_id ) {
				if ( self::PRODUCT_LIMIT === $this->products ) {
					$this->truncated = true;
					break 2;
				}
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$this->record( $product, $rules, $channel_rules, $ids_limit );
				}
			}

			// A full-catalog read would otherwise keep every product's post, meta and terms in the request's object cache.
			if ( $flush_runtime_cache ) {
				wp_cache_flush_runtime();
			}
			++$page;
		} while ( self::BATCH_SIZE === $batch_count );
	}

	/**
	 * Tax query for a supported type. A product with no type term loads as simple.
	 *
	 * @return array<string|int, mixed>
	 */
	private static function supported_type_query(): array {
		return array(
			'relation' => 'OR',
			array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => self::SUPPORTED_TYPES,
			),
			array(
				'taxonomy' => 'product_type',
				'operator' => 'NOT EXISTS',
			),
		);
	}

	/**
	 * Tax query on the shop catalog: "Search results only" and "Hidden" both
	 * carry the exclude-from-catalog term.
	 *
	 * @param string $operator IN for hidden products, NOT IN for the rest.
	 * @return array<string, mixed>
	 */
	private static function catalog_query( string $operator ): array {
		return array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => $operator,
		);
	}

	/**
	 * Count published products matching a tax query.
	 *
	 * @param array<int, array<string|int, mixed>> $tax_query Tax query clauses.
	 */
	private static function count_published( array $tax_query ): int {
		$query = new \WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			)
		);
		return (int) $query->found_posts;
	}

	/**
	 * Load the products with their meta and terms, then the featured images
	 * and variations the checks read, one query each instead of one per product.
	 *
	 * @param array<int, int|string> $product_ids Product ids.
	 */
	public static function prime_related( array $product_ids ): void {
		global $wpdb;
		if ( empty( $product_ids ) ) {
			return;
		}

		_prime_post_caches( array_map( 'intval', $product_ids ), true, true );

		_prime_post_caches(
			array_filter(
				array_map(
					static function ( $product_id ): int {
						return (int) get_post_meta( (int) $product_id, '_thumbnail_id', true );
					},
					$product_ids
				)
			),
			false,
			true
		);
		$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );
		_prime_post_caches(
			$wpdb->get_col(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- One %d placeholder per product.
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product_variation' AND post_parent IN ({$placeholders})",
					array_map( 'intval', $product_ids )
				)
			),
			true,
			true
		);
	}

	/**
	 * Count and product ids for one rule.
	 *
	 * @param string $rule_id Rule id.
	 * @return array{count:int,product_ids:array<int,int>}
	 */
	public function result( string $rule_id ): array {
		return $this->results[ $rule_id ] ?? array(
			'count'       => 0,
			'product_ids' => array(),
		);
	}

	/**
	 * Products tested.
	 */
	public function products(): int {
		return $this->products;
	}

	/**
	 * Published products of a supported type hidden from the shop catalog, not checked.
	 */
	public function hidden_from_catalog(): int {
		return $this->hidden_from_catalog;
	}

	/**
	 * Published products of a type agents don't support yet, not checked.
	 */
	public function unsupported_types(): int {
		return $this->unsupported_types;
	}

	/**
	 * Per channel, how many products can't be listed there, list with gaps, or list complete.
	 *
	 * @return array<string, array{name:string,spec:string,url:string,listable:int,not_listable:int,with_gaps:int,complete:int}>
	 */
	public function channel_totals(): array {
		$channels = array();
		foreach ( FeedChannels::all() as $id => $channel ) {
			$counts          = array_merge( array_fill_keys( array( self::NOT_LISTABLE, self::WITH_GAPS, self::COMPLETE ), 0 ), $this->statuses[ $id ] ?? array() );
			$channels[ $id ] = array_merge(
				$channel,
				array(
					'listable'     => $this->products - $counts[ self::NOT_LISTABLE ],
					'not_listable' => $counts[ self::NOT_LISTABLE ],
					'with_gaps'    => $counts[ self::WITH_GAPS ],
					'complete'     => $counts[ self::COMPLETE ],
				)
			);
		}
		return $channels;
	}

	/**
	 * A product's status on each channel from the channel maps of the rules it
	 * fails: not_listable when a failed rule's field is required there,
	 * with_gaps when only gaps apply there, complete otherwise.
	 *
	 * @param array<int, array<string, array{field:string,effect:string}>> $failed_channels Channel map of each failed rule, for this product.
	 * @return array<string, string> Status by channel id.
	 */
	public static function statuses( array $failed_channels ): array {
		$statuses = array_fill_keys( array_keys( FeedChannels::all() ), self::COMPLETE );
		foreach ( $failed_channels as $channels ) {
			foreach ( $channels as $channel => $spec ) {
				if ( ! isset( $statuses[ $channel ] ) ) {
					continue;
				}
				if ( ReadinessRule::EFFECT_NOT_LISTABLE === $spec['effect'] ) {
					$statuses[ $channel ] = self::NOT_LISTABLE;
				} elseif ( self::COMPLETE === $statuses[ $channel ] ) {
					$statuses[ $channel ] = self::WITH_GAPS;
				}
			}
		}
		return $statuses;
	}

	/**
	 * Whether the scan stopped at PRODUCT_LIMIT with products left.
	 */
	public function truncated(): bool {
		return $this->truncated;
	}

	/**
	 * Whether more than one published product carries this GTIN, counting a
	 * variable product and its variations once. GTINs compare without leading zeros.
	 *
	 * @param string $gtin Normalised GTIN.
	 */
	public function is_shared_gtin( string $gtin ): bool {
		if ( null === $this->shared_gtins ) {
			global $wpdb;

			// Read from postmeta, not wc_product_meta_lookup: imports and direct
			// meta writes skip both the lookup table and core's uniqueness check.
			// Only shared GTINs come back, so memory follows the duplicates.
			// The separators go in as hex literals: a non-ASCII query sends
			// wpdb into its per-table charset checks.
			$normalised = 'pm.meta_value';
			foreach ( ProductFields::GTIN_SEPARATORS as $separator ) {
				$normalised = "REPLACE( {$normalised}, _utf8mb4 X'" . bin2hex( $separator ) . "', '' )";
			}
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $normalised is built from constant hex literals.
			$gtins = (array) $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT owned.gtin
					FROM (
						SELECT DISTINCT IF( p.post_type = %s, p.post_parent, p.ID ) AS owner_id,
							TRIM( LEADING '0' FROM {$normalised} ) AS gtin
						FROM {$wpdb->postmeta} pm
						INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
						INNER JOIN {$wpdb->posts} owner ON owner.ID = IF( p.post_type = %s, p.post_parent, p.ID )
						WHERE pm.meta_key = %s
							AND pm.meta_value != ''
							AND p.post_type IN ( %s, %s )
							AND p.post_status = %s
							AND owner.post_status = %s
					) owned
					GROUP BY owned.gtin
					HAVING COUNT(*) > 1
					",
					'product_variation',
					'product_variation',
					'_global_unique_id',
					'product',
					'product_variation',
					'publish',
					'publish'
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			$this->shared_gtins = array_fill_keys( array_map( 'strval', $gtins ), true );
		}

		return isset( $this->shared_gtins[ ProductFields::gtin_key( $gtin ) ] );
	}

	/**
	 * The rules a product fails, with the channels each failure applies to for this product.
	 * A rule that applies to none of the product's channels is left out.
	 *
	 * @param \WC_Product                                                     $product       Product.
	 * @param array<string, ReadinessRule>                                    $rules         Rules by id.
	 * @param array<string, array<string, array{field:string,effect:string}>> $channel_rules Channel map by rule id.
	 * @return array<string, array<string, array{field:string,effect:string}>>
	 */
	public function failures( \WC_Product $product, array $rules, array $channel_rules ): array {
		$failed = array();
		foreach ( $rules as $id => $rule ) {
			if ( ! $rule->fails( $product, $this ) ) {
				continue;
			}
			$channels = $rule->channels_for( $product, $channel_rules[ $id ] ?? array() );
			// A check with no channel (about the product page, not a feed) still counts the product.
			if ( ! empty( $channels ) || empty( $channel_rules[ $id ] ?? array() ) ) {
				$failed[ (string) $id ] = $channels;
			}
		}
		return $failed;
	}

	/**
	 * Test one product and add it to the results and totals.
	 *
	 * @param \WC_Product                                                     $product       Product.
	 * @param array<string, ReadinessRule>                                    $rules         Rules by id.
	 * @param array<string, array<string, array{field:string,effect:string}>> $channel_rules Channel map by rule id.
	 * @param int                                                             $ids_limit     Product ids kept per rule.
	 */
	private function record( \WC_Product $product, array $rules, array $channel_rules, int $ids_limit ): void {
		$failures = $this->failures( $product, $rules, $channel_rules );
		foreach ( array_keys( $failures ) as $id ) {
			$result = $this->result( $id );
			++$result['count'];
			if ( count( $result['product_ids'] ) < $ids_limit ) {
				$result['product_ids'][] = $product->get_id();
			}
			$this->results[ $id ] = $result;
		}

		++$this->products;
		foreach ( self::statuses( array_values( $failures ) ) as $channel => $status ) {
			$this->statuses[ $channel ][ $status ] = ( $this->statuses[ $channel ][ $status ] ?? 0 ) + 1;
		}
	}
}
