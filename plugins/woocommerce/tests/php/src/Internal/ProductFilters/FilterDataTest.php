<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;
use Automattic\WooCommerce\Internal\ProductFilters\CacheController;
use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;
use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;

/**
 * Tests related to Counts service.
 */
class FilterDataTest extends AbstractProductFiltersTest {
	/**
	 * The system under test.
	 *
	 * @var DataRegenerator
	 */
	private $sut;

	/**
	 * TaxonomyHierarchyData instance for clearing the cache.
	 *
	 * @var TaxonomyHierarchyData
	 */
	private $taxonomy_hierarchy_data;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$container = wc_get_container();

		$this->sut                     = $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) );
		$this->taxonomy_hierarchy_data = $container->get( TaxonomyHierarchyData::class );

		$this->fixture_data->add_product_review( $this->products[0]->get_id(), 5 );
		$this->fixture_data->add_product_review( $this->products[1]->get_id(), 3 );
		$this->fixture_data->add_product_review( $this->products[3]->get_id(), 5 );
	}

	/**
	 * @testdox Test price range without filter.
	 */
	public function test_get_filtered_price_with_default_query() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );

		$this->test_get_filtered_price_with( $wp_query );
	}

	/**
	 * @testdox Test price range with stock filter set to instock.
	 */
	public function test_get_filtered_price_with_stock_filter() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );

		$wp_query->set( 'filter_stock_status', 'instock' );
		$this->test_get_filtered_price_with(
			$wp_query,
			function ( $product_data ) {
				return 'instock' === $product_data['stock_status'];
			}
		);
	}

	/**
	 * @testdox Test price range with stock filter set to multiple options.
	 */
	public function test_get_filtered_price_with_stock_filter_multiple() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'filter_stock_status', 'outofstock,onbackorder' );
		$this->test_get_filtered_price_with(
			$wp_query,
			function ( $product_data ) {
				return 'outofstock' === $product_data['stock_status'] ||
				'onbackorder' === $product_data['stock_status'];
			}
		);
	}

	/**
	 * @testdox Test stock counts without filter: via wc_product_meta_lookup table.
	 */
	public function test_get_stock_status_counts_with_default_query_using_lookup_table() {
		$this->test_get_stock_status_counts_with( new \WP_Query( array( 'post_type' => 'product' ) ) );
	}

	/**
	 * @testdox Test stock counts without filter: via postmeta table.
	 */
	public function test_get_stock_status_counts_with_default_query_using_postmeta_table() {
		global $wpdb;
		// Truncate the lookup table to confirm that the underlying query is targeting the correct postmeta table.
		$wpdb->query( "TRUNCATE TABLE {$wpdb->wc_product_meta_lookup}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		update_option( 'woocommerce_product_lookup_table_is_generating', '1' );
		$this->test_get_stock_status_counts_with( new \WP_Query( array( 'post_type' => 'product' ) ) );
		delete_option( 'woocommerce_product_lookup_table_is_generating' );
	}

	/**
	 * @testdox Test stock counts with min price.
	 */
	public function test_get_stock_status_counts_with_min_price() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'min_price', 20 );
		$this->test_get_stock_status_counts_with(
			$wp_query,
			function ( $product_data ) {
				if ( ! isset( $product_data['variations'] ) ) {
					return $product_data['regular_price'] >= 20;
				}

				foreach ( $product_data['variations'] as $variation_data ) {
					if ( $variation_data['props']['regular_price'] < 20 ) {
						return false;
					}
				}
				return true;
			}
		);
	}

	/**
	 * @testdox Test rating counts without filter.
	 */
	public function test_get_rating_counts_with_default_query() {
		$wp_query   = new \WP_Query( array( 'post_type' => 'product' ) );
		$query_vars = array_filter( $wp_query->query_vars );

		$actual_rating_counts   = $this->sut->get_rating_counts( $query_vars );
		$expected_rating_counts = array(
			3 => 1,
			5 => 2,
		);

		$this->assertEqualsCanonicalizing(
			$expected_rating_counts,
			$actual_rating_counts
		);
	}

	/**
	 * @testdox Test rating counts with min price.
	 */
	public function test_get_rating_counts_with_min_price() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'min_price', 20 );
		$query_vars = array_filter( $wp_query->query_vars );

		$actual_rating_counts   = $this->sut->get_rating_counts( $query_vars );
		$expected_rating_counts = array(
			3 => 1,
			5 => 1,
		);

		$this->assertEqualsCanonicalizing(
			$expected_rating_counts,
			$actual_rating_counts
		);
	}

	/**
	 * @testdox Test attribute count without filter.
	 */
	public function test_get_attribute_counts_with_default_query() {
		$wp_query                  = new \WP_Query( array( 'post_type' => 'product' ) );
		$query_vars                = array_filter( $wp_query->query_vars );
		$actual_attribute_counts   = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
		$expected_attribute_counts = $this->get_expected_attribute_counts( 'pa_color' );

		$this->assertEqualsCanonicalizing( $expected_attribute_counts, $actual_attribute_counts );
	}

	/**
	 * @testdox Attribute counts reflect a changed hide-out-of-stock setting for the same query.
	 */
	public function test_get_attribute_counts_reflect_changed_hide_out_of_stock_setting_for_same_query(): void {
		$green_variation                = $this->get_variation_by_attribute( $this->products[4], 'pa_color', 'green-slug' );
		$green_term                     = get_term_by( 'slug', 'green-slug', 'pa_color' );
		$original_stock_status          = $green_variation->get_stock_status();
		$original_hide_out_of_stock     = get_option( 'woocommerce_hide_out_of_stock_items' );
		$wp_query                       = new \WP_Query( array( 'post_type' => 'product' ) );
		$query_vars                     = array_filter( $wp_query->query_vars );
		$query_vars['counts-cache-key'] = __METHOD__;
		$lookup_data_store              = wc_get_container()->get( LookupDataStore::class );
		$filter_execution_count         = 0;
		$count_filter_executions        = function ( $results, $filter_type, $filter_query_vars, $extra ) use ( &$filter_execution_count ) {
			if ( 'attribute' === $filter_type && 'pa_color' === ( $extra['taxonomy'] ?? null ) ) {
				++$filter_execution_count;
			}

			return $results;
		};

		$this->assertInstanceOf( \WP_Term::class, $green_term );

		try {
			$green_variation->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
			$green_variation->save();
			$lookup_data_store->run_update_callback( $green_variation->get_id(), LookupDataStore::ACTION_UPDATE_STOCK );

			global $wpdb;
			$lookup_in_stock = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT in_stock FROM {$wpdb->prefix}wc_product_attributes_lookup WHERE product_id = %d AND product_or_parent_id = %d AND taxonomy = %s AND term_id = %d AND is_variation_attribute = 1",
					$green_variation->get_id(),
					$this->products[4]->get_id(),
					'pa_color',
					$green_term->term_id
				)
			);
			$this->assertNotNull( $lookup_in_stock, 'Product 5\'s green variation lookup row should exist.' );
			$this->assertSame( 0, (int) $lookup_in_stock, 'Product 5\'s green variation lookup row should be out of stock.' );

			update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
			add_filter( 'woocommerce_product_filter_data', $count_filter_executions, 10, 4 );
			$counts_with_out_of_stock = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
			$this->assertSame(
				2,
				$counts_with_out_of_stock[ $green_term->term_id ] ?? 0,
				'Both Product 5 and Product 6 should contribute to the green count when out-of-stock items are shown.'
			);

			$cached_counts_with_out_of_stock = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
			$this->assertSame( $counts_with_out_of_stock, $cached_counts_with_out_of_stock, 'The repeated identical query should return the same attribute counts.' );
			$this->assertSame( 1, $filter_execution_count, 'The repeated identical query should be served from the filter data cache.' );

			update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
			$counts_without_out_of_stock = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
			$this->assertSame(
				1,
				$counts_without_out_of_stock[ $green_term->term_id ] ?? 0,
				'Only Product 6 should contribute to the green count when out-of-stock items are hidden.'
			);
			$this->assertSame( 2, $filter_execution_count, 'Changing the option should recompute attribute counts under a distinct cache key.' );
		} finally {
			$green_variation->set_stock_status( $original_stock_status );
			$green_variation->save();
			$lookup_data_store->run_update_callback( $green_variation->get_id(), LookupDataStore::ACTION_UPDATE_STOCK );
			remove_filter( 'woocommerce_product_filter_data', $count_filter_executions, 10 );

			if ( false === $original_hide_out_of_stock ) {
				delete_option( 'woocommerce_hide_out_of_stock_items' );
			} else {
				update_option( 'woocommerce_hide_out_of_stock_items', $original_hide_out_of_stock );
			}
		}
	}

	/**
	 * @testdox A private variation does not contribute to its attribute term count.
	 */
	public function test_get_attribute_counts_exclude_private_variations(): void {
		$green_variation = $this->get_variation_by_attribute( $this->products[4], 'pa_color', 'green-slug' );
		$green_term      = get_term_by( 'slug', 'green-slug', 'pa_color' );
		$red_term        = get_term_by( 'slug', 'red-slug', 'pa_color' );

		$this->assertInstanceOf( \WP_Term::class, $green_term );
		$this->assertInstanceOf( \WP_Term::class, $red_term );

		$green_variation->set_status( 'private' );
		$green_variation->save();
		$this->assert_private_variation_lookup_row_is_retained( $green_variation, $this->products[4], 'pa_color', $green_term->term_id );

		try {
			$wp_query                          = new \WP_Query( array( 'post_type' => 'product' ) );
			$query_vars                        = array_filter( $wp_query->query_vars );
			$query_vars['counts-cache-bypass'] = __METHOD__;

			$actual_attribute_counts = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
		} finally {
			$green_variation->set_status( 'publish' );
			$green_variation->save();
		}

		$this->assertSame(
			1,
			$actual_attribute_counts[ $red_term->term_id ] ?? 0,
			'Product 5\'s published red variation should still contribute to the red count.'
		);
		$this->assertSame(
			1,
			$actual_attribute_counts[ $green_term->term_id ] ?? 0,
			'Only Product 6 should contribute to the green count while Product 5\'s green variation is private.'
		);
	}

	/**
	 * @testdox A variation visibility change invalidates cached attribute counts.
	 */
	public function test_get_attribute_counts_recompute_after_variation_visibility_change(): void {
		$green_variation                             = $this->get_variation_by_attribute( $this->products[4], 'pa_color', 'green-slug' );
		$green_term                                  = get_term_by( 'slug', 'green-slug', 'pa_color' );
		$wp_query                                    = new \WP_Query( array( 'post_type' => 'product' ) );
		$query_vars                                  = array_filter( $wp_query->query_vars );
		$query_vars['visibility-cache-invalidation'] = __METHOD__;
		$cache_controller                            = wc_get_container()->get( CacheController::class );
		$filter_execution_count                      = 0;
		$count_filter_executions                     = function ( $results, $filter_type, $filter_query_vars, $extra ) use ( &$filter_execution_count ) {
			if ( 'attribute' === $filter_type && 'pa_color' === ( $extra['taxonomy'] ?? null ) ) {
				++$filter_execution_count;
			}

			return $results;
		};
		$get_transient_key                           = new \ReflectionMethod( $this->sut, 'get_transient_key' );
		$get_transient_key->setAccessible( true );
		$transient_key              = $get_transient_key->invoke(
			$this->sut,
			$query_vars,
			'attribute',
			array(
				'taxonomy'                => 'pa_color',
				'hide_out_of_stock_items' => 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ),
			)
		);
		$version_transient_key      = CacheController::CACHE_GROUP . '-transient-version';
		$original_version           = get_transient( $version_transient_key );
		$original_cache_entry_count = get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT );
		$transition_hook_priority   = has_action( 'transition_post_status', array( $cache_controller, 'handle_transition_post_status' ) );

		$this->assertInstanceOf( \WP_Term::class, $green_term );
		$this->assertIsString( $transient_key );

		if ( false !== $transition_hook_priority ) {
			remove_action( 'transition_post_status', array( $cache_controller, 'handle_transition_post_status' ), $transition_hook_priority );
		}

		add_filter( 'woocommerce_product_filter_data', $count_filter_executions, 10, 4 );

		try {
			$published_counts = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
			$this->assertSame( 2, $published_counts[ $green_term->term_id ] ?? 0, 'Both published green variations should contribute before the transition.' );

			$cached_published_counts = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
			$this->assertSame( $published_counts, $cached_published_counts, 'The repeated query should return the primed attribute-count cache entry.' );
			$this->assertSame( 1, $filter_execution_count, 'The repeated query should not execute the attribute-count SQL.' );

			$cached_entry = get_transient( $transient_key );
			$this->assertIsArray( $cached_entry, 'The provider should store a real filter-data transient.' );
			$this->assertSame( $published_counts, $cached_entry['value'] ?? null, 'The transient should contain the primed published counts.' );

			wp_update_post(
				array(
					'ID'          => $green_variation->get_id(),
					'post_status' => 'private',
				)
			);
			$this->assert_private_variation_lookup_row_is_retained( $green_variation, $this->products[4], 'pa_color', $green_term->term_id );

			$private_post = get_post( $green_variation->get_id() );
			$this->assertInstanceOf( \WP_Post::class, $private_post );
			$cached_version = $cached_entry['version'] ?? null;
			$this->assertSame(
				$cached_version,
				get_transient( $version_transient_key ),
				'The primed cache entry should remain current before the manual visibility-transition invalidation.'
			);

			$cache_controller->handle_transition_post_status( 'private', 'publish', $private_post );

			$this->assertNotSame(
				$cached_version,
				get_transient( $version_transient_key ),
				'The visibility transition should assign a new filter-data cache version.'
			);

			$private_counts = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
			$this->assertSame( 1, $private_counts[ $green_term->term_id ] ?? 0, 'Only Product 6 should contribute after Product 5\'s green variation becomes private.' );
			$this->assertSame( 2, $filter_execution_count, 'The visibility transition should reject the stale transient and recompute attribute counts.' );
		} finally {
			wp_update_post(
				array(
					'ID'          => $green_variation->get_id(),
					'post_status' => 'publish',
				)
			);
			delete_transient( $transient_key );
			remove_filter( 'woocommerce_product_filter_data', $count_filter_executions, 10 );

			if ( false === $original_version ) {
				delete_transient( $version_transient_key );
			} else {
				set_transient( $version_transient_key, $original_version );
			}

			if ( false === $original_cache_entry_count ) {
				delete_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT );
			} else {
				set_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT, $original_cache_entry_count );
			}

			if ( false !== $transition_hook_priority ) {
				add_action( 'transition_post_status', array( $cache_controller, 'handle_transition_post_status' ), $transition_hook_priority, 3 );
			}
		}
	}

	/**
	 * @testdox Attribute count results computed during invalidation are not published to the new cache generation.
	 */
	public function test_get_attribute_counts_do_not_cache_results_when_invalidated_during_computation(): void {
		$wp_query                       = new \WP_Query( array( 'post_type' => 'product' ) );
		$query_vars                     = array_filter( $wp_query->query_vars );
		$query_vars['cache-generation'] = __METHOD__;
		$cache_controller               = wc_get_container()->get( CacheController::class );
		$filter_execution_count         = 0;
		$invalidated_during_first_fill  = false;
		$invalidate_during_first_fill   = function ( $results, $filter_type, $filter_query_vars, $extra ) use ( $cache_controller, &$filter_execution_count, &$invalidated_during_first_fill ) {
			if ( 'attribute' !== $filter_type || 'pa_color' !== ( $extra['taxonomy'] ?? null ) ) {
				return $results;
			}

			++$filter_execution_count;
			if ( ! $invalidated_during_first_fill ) {
				$invalidated_during_first_fill = true;
				$cache_controller->invalidate_filter_data_cache();
			}

			return $results;
		};
		$get_transient_key              = new \ReflectionMethod( $this->sut, 'get_transient_key' );
		$get_transient_key->setAccessible( true );
		$transient_key              = $get_transient_key->invoke(
			$this->sut,
			$query_vars,
			'attribute',
			array(
				'taxonomy'                => 'pa_color',
				'hide_out_of_stock_items' => 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ),
			)
		);
		$version_transient_key      = CacheController::CACHE_GROUP . '-transient-version';
		$original_version           = get_transient( $version_transient_key );
		$original_cache_entry_count = get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT );

		$this->assertIsString( $transient_key );

		delete_transient( $transient_key );
		set_transient( $version_transient_key, 'filter-data-race-generation-v1' );
		add_filter( 'woocommerce_product_filter_data', $invalidate_during_first_fill, 10, 4 );

		try {
			$first_counts           = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
			$cache_after_first_fill = get_transient( $transient_key );
			$second_counts          = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
		} finally {
			remove_filter( 'woocommerce_product_filter_data', $invalidate_during_first_fill, 10 );
			delete_transient( $transient_key );

			if ( false === $original_version ) {
				delete_transient( $version_transient_key );
			} else {
				set_transient( $version_transient_key, $original_version );
			}

			if ( false === $original_cache_entry_count ) {
				delete_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT );
			} else {
				set_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT, $original_cache_entry_count );
			}
		}

		$this->assertSame( $first_counts, $second_counts, 'Both calls should return the same attribute counts.' );
		$this->assertSame( 2, $filter_execution_count, 'The identical second call should recompute after the first in-flight result is discarded.' );
		$this->assertFalse( $cache_after_first_fill, 'The first result must not be published after its cache generation is invalidated.' );
	}

	/**
	 * @testdox A variation attribute term configured on the parent is absent when no matching variation exists.
	 */
	public function test_get_attribute_counts_exclude_parent_only_variation_terms(): void {
		$yellow_term = wp_insert_term(
			'Yellow',
			'pa_color',
			array( 'slug' => 'yellow-slug' )
		);

		$this->assertNotWPError( $yellow_term );

		$product         = $this->products[4];
		$attributes      = $product->get_attributes();
		$color_attribute = clone $attributes['pa_color'];
		$color_attribute->set_options(
			array_merge( $color_attribute->get_options(), array( $yellow_term['term_id'] ) )
		);
		$attributes['pa_color'] = $color_attribute;
		$product->set_attributes( $attributes );
		$product->save();

		$this->assertTrue(
			has_term( $yellow_term['term_id'], 'pa_color', $product->get_id() ),
			'Yellow should be configured as a variation term on Product 5.'
		);

		$wp_query                          = new \WP_Query( array( 'post_type' => 'product' ) );
		$query_vars                        = array_filter( $wp_query->query_vars );
		$query_vars['counts-cache-bypass'] = __METHOD__;

		$actual_attribute_counts   = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
		$expected_attribute_counts = $this->get_expected_attribute_counts( 'pa_color' );

		$this->assertEqualsCanonicalizing(
			$expected_attribute_counts,
			array_intersect_key( $actual_attribute_counts, $expected_attribute_counts ),
			'Red, green, and blue should retain their existing fixture counts.'
		);

		$this->assertArrayNotHasKey(
			$yellow_term['term_id'],
			$actual_attribute_counts,
			'Parent-only variation terms should not be offered as attribute filters.'
		);
	}

	/**
	 * @testdox Test attribute count with max price.
	 */
	public function test_get_attribute_counts_with_max_price() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'max_price', 55 );

		$query_vars                = array_filter( $wp_query->query_vars );
		$actual_attribute_counts   = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
		$expected_attribute_counts = $this->get_expected_attribute_counts(
			'pa_color',
			function ( $product_data ) {
				if ( isset( $product_data['regular_price'] ) && $product_data['regular_price'] <= 55 ) {
					return true;
				}

				if ( isset( $product_data['variations'] ) ) {
					foreach ( $product_data['variations'] as $variation_data ) {
						if ( isset( $variation_data['props']['regular_price'] ) && $variation_data['props']['regular_price'] <= 55 ) {
							return true;
						}
					}
				}

				return false;
			}
		);

		$this->assertEqualsCanonicalizing( $expected_attribute_counts, $actual_attribute_counts );
	}

	/**
	 * @testdox Test attribute count with query_type set to `and`.
	 * @todo Remove this test once the issue with `and` query type is fixed in https://github.com/woocommerce/woocommerce/pull/44825.
	 */
	public function test_get_attribute_counts_with_query_type_and() {
		$this->markTestSkipped( 'Skipping tests with query_type `and` because there is an issue with Filterer::filter_by_attribute_post_clauses that generate wrong clauses for `and`. We can fix the same issue in FilterClausesGenerator::add_attribute_clauses but doing so will make the attribute counts data doesnt match with current query. A fix for both methods is pending. See https://github.com/woocommerce/woocommerce/pull/44825.' );
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'filter_color', 'blue-slug,green-slug' );
		$wp_query->set( 'query_type_color', 'and' );

		$query_vars                = array_filter( $wp_query->query_vars );
		$actual_attribute_counts   = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
		$expected_attribute_counts = $this->get_expected_attribute_counts(
			'pa_color',
			function ( $product_data ) {
				$has_green = false;
				$has_blue  = false;

				if ( isset( $product_data['variations'] ) ) {
					foreach ( $product_data['variations'] as $variation_data ) {
						if ( empty( $variation_data['attributes']['pa_color'] ) ) {
							return false;
						}

						if ( 'blue' === $variation_data['attributes']['pa_color'] ) {
							$has_blue = true;
						}

						if ( 'green' === $variation_data['attributes']['pa_color'] ) {
							$has_green = true;
						}
					}
				}

				return $has_blue && $has_green;
			}
		);

		$this->assertEqualsCanonicalizing( $expected_attribute_counts, $actual_attribute_counts );
	}

	/**
	 * @testdox Test attribute count with query_type set to `or`.
	 */
	public function test_get_attribute_counts_with_query_type_or() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'query_type_color', 'or' );

		/**
		 * For query type `or`, the selected attributes are unset from
		 * $query_vars before passed to get_attribute_counts, so we don't set
		 * them here.
		 *
		 * $wp_query->set( 'filter_color', 'blue-slug,green-slug' );
		 */

		$query_vars                = array_filter( $wp_query->query_vars );
		$actual_attribute_counts   = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
		$expected_attribute_counts = $this->get_expected_attribute_counts(
			'pa_color',
			function ( $product_data ) {
				if ( isset( $product_data['variations'] ) ) {
					foreach ( $product_data['variations'] as $variation_data ) {
						if ( empty( $variation_data['attributes']['pa_color'] ) ) {
							return false;
						}

						if ( 'blue' === $variation_data['attributes']['pa_color'] ||
							'green' === $variation_data['attributes']['pa_color']
						) {
							return true;
						}
					}
				}

				return false;
			}
		);

		$this->assertEqualsCanonicalizing( $expected_attribute_counts, $actual_attribute_counts );
	}

	/**
	 * @testdox Test taxonomy count without filter.
	 */
	public function test_get_taxonomy_counts_with_default_query() {
		$wp_query                 = new \WP_Query( array( 'post_type' => 'product' ) );
		$query_vars               = array_filter( $wp_query->query_vars );
		$actual_taxonomy_counts   = $this->sut->get_taxonomy_counts( $query_vars, 'product_cat' );
		$expected_taxonomy_counts = $this->get_expected_category_counts();

		$this->assertEqualsCanonicalizing( $expected_taxonomy_counts, $actual_taxonomy_counts );
	}

	/**
	 * @testdox Test taxonomy count with max price.
	 */
	public function test_get_taxonomy_counts_with_max_price() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'max_price', 35 );

		$query_vars               = array_filter( $wp_query->query_vars );
		$actual_taxonomy_counts   = $this->sut->get_taxonomy_counts( $query_vars, 'product_cat' );
		$expected_taxonomy_counts = $this->get_expected_category_counts(
			function ( $product_data ) {
				if ( ! isset( $product_data['regular_price'] ) ) {
					return false;
				}

				return $product_data['regular_price'] <= 35;
			}
		);

		$this->assertEqualsCanonicalizing( $expected_taxonomy_counts, $actual_taxonomy_counts );
	}

	/**
	 * @testdox Test taxonomy count with hierarchical categories.
	 *
	 * Note: We create test categories here rather than using the existing ones from setUp()
	 * because calculating expected counts for hierarchical categories would require complex
	 * filtering logic in the callback passed to get_expected_category_counts(). The callback
	 * would need to analyze the $product_data property to handle parent-child relationships.
	 * For these hierarchy-specific tests, we directly create a simple parent-child
	 * structure and assert the expected counts based on our test data.
	 */
	public function test_get_taxonomy_counts_with_hierarchical_categories() {
		// Create parent category.
		$parent_term = wp_insert_term( 'Electronics', 'product_cat' );
		$parent_id   = $parent_term['term_id'];

		// Create child category.
		$child_term = wp_insert_term( 'Phones', 'product_cat', array( 'parent' => $parent_id ) );
		$child_id   = $child_term['term_id'];

		wp_set_object_terms( $this->products[0]->get_id(), array( $parent_id ), 'product_cat' );
		wp_set_object_terms( $this->products[1]->get_id(), array( $child_id ), 'product_cat' );

		$this->taxonomy_hierarchy_data->clear_cache( 'product_cat' );

		$wp_query   = new \WP_Query( array( 'post_type' => 'product' ) );
		$query_vars = array_filter( $wp_query->query_vars );

		$actual_taxonomy_counts = $this->sut->get_taxonomy_counts( $query_vars, 'product_cat' );

		// Parent category should have count of 2, child category should have count of 1.
		$this->assertSame( 2, $actual_taxonomy_counts[ $parent_id ] );
		$this->assertSame( 1, $actual_taxonomy_counts[ $child_id ] );

		wp_delete_term( $child_id, 'product_cat' );
		wp_delete_term( $parent_id, 'product_cat' );
	}

	/**
	 * @testdox Test taxonomy count with hierarchical categories and max price.
	 *
	 * Note: We create test categories here rather than using the existing ones from setUp()
	 * because calculating expected counts for hierarchical categories would require complex
	 * filtering logic in the callback passed to get_expected_category_counts(). The callback
	 * would need to analyze the $product_data property to handle parent-child relationships.
	 * For these hierarchy-specific tests, we directly create a simple parent-child
	 * structure and assert the expected counts based on our test data.
	 */
	public function test_get_taxonomy_counts_with_hierarchical_categories_with_max_price() {
		// Create parent category.
		$parent_term = wp_insert_term( 'Electronics', 'product_cat' );
		$parent_id   = $parent_term['term_id'];

		// Create child category.
		$child_term = wp_insert_term( 'Phones', 'product_cat', array( 'parent' => $parent_id ) );
		$child_id   = $child_term['term_id'];

		wp_set_object_terms( $this->products[0]->get_id(), array( $parent_id ), 'product_cat' );
		wp_set_object_terms( $this->products[1]->get_id(), array( $child_id ), 'product_cat' );

		$this->taxonomy_hierarchy_data->clear_cache( 'product_cat' );

		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'max_price', 15 );

		$query_vars = array_filter( $wp_query->query_vars );

		$actual_taxonomy_counts = $this->sut->get_taxonomy_counts( $query_vars, 'product_cat' );

		// Parent category should have count of 1, child category should not be in results.
		$this->assertSame( 1, $actual_taxonomy_counts[ $parent_id ] );
		$this->assertArrayNotHasKey( $child_id, $actual_taxonomy_counts );

		wp_delete_term( $child_id, 'product_cat' );
		wp_delete_term( $parent_id, 'product_cat' );
	}

	/**
	 * Get expected attribute count from product data and map them with actual term IDs.
	 *
	 * @param string   $attribute_name  WP_Query instance.
	 * @param callable $filter_callback Callback passed to filter test products.
	 */
	private function get_expected_attribute_counts( $attribute_name, $filter_callback = null ) {
		$attribute_counts_by_term_id   = array();
		$attribute_counts_by_term_name = array();

		if ( $filter_callback ) {
			$filtered_products_data = array_filter(
				$this->products_data,
				$filter_callback
			);
		} else {
			$filtered_products_data = $this->products_data;
		}

		foreach ( $filtered_products_data as $product_data ) {
			if ( empty( $product_data['variations'] ) ) {
				continue;
			}

			foreach ( $product_data['variations'] as $variation_data ) {
				if ( ! isset( $attribute_counts_by_term_name[ $variation_data['attributes'][ $attribute_name ] ] ) ) {
					$attribute_counts_by_term_name[ $variation_data['attributes'][ $attribute_name ] ] = 0;
				}
				$attribute_counts_by_term_name[ $variation_data['attributes'][ $attribute_name ] ] += 1;
			}
		}

		foreach ( get_terms( array( 'taxonomy' => 'pa_color' ) ) as $term ) {
			if ( isset( $attribute_counts_by_term_name[ $term->name ] ) ) {
				$attribute_counts_by_term_id[ $term->term_id ] = $attribute_counts_by_term_name[ $term->name ];
			}
		}

		return $attribute_counts_by_term_id;
	}

	/**
	 * Get expected category count from product data and map them with actual term IDs.
	 *
	 * @param callable $filter_callback Callback passed to filter test products.
	 */
	private function get_expected_category_counts( $filter_callback = null ) {
		$category_counts_by_term_id = array();

		if ( $filter_callback ) {
			$filtered_products_data = array_filter(
				$this->products_data,
				$filter_callback
			);
		} else {
			$filtered_products_data = $this->products_data;
		}

		foreach ( $filtered_products_data as $product_data ) {
			if ( empty( $product_data['category_ids'] ) ) {
				continue;
			}

			foreach ( $product_data['category_ids'] as $product_category_id ) {
				if ( ! isset( $category_counts_by_term_id[ $product_category_id ] ) ) {
					$category_counts_by_term_id[ $product_category_id ] = 0;
				}
				$category_counts_by_term_id[ $product_category_id ] += 1;
			}
		}

		return $category_counts_by_term_id;
	}

	/**
	 * Test stock count.
	 *
	 * @param \WP_Query $wp_query        WP_Query instance.
	 * @param callable  $filter_callback Callback passed to filter test products.
	 */
	private function test_get_stock_status_counts_with( $wp_query, $filter_callback = null ) {
		$query_vars                        = array_filter( $wp_query->query_vars );
		$query_vars['counts-cache-bypass'] = microtime( true );

		$actual_stock_status_counts = $this->sut->get_stock_status_counts( $query_vars, array( 'instock', 'outofstock', 'onbackorder' ) );

		$expected_stock_status_counts = array(
			'instock'     => 0,
			'outofstock'  => 0,
			'onbackorder' => 0,
		);

		if ( $filter_callback ) {
			$filtered_product_data = array_filter(
				$this->products_data,
				$filter_callback
			);
		} else {
			$filtered_product_data = $this->products_data;
		}

		foreach ( $filtered_product_data as $product_data ) {
			$expected_stock_status_counts[ $product_data['stock_status'] ] += 1;
		}

		$this->assertEqualsCanonicalizing( $expected_stock_status_counts, $actual_stock_status_counts );
	}

	/**
	 * Test filter price range.
	 *
	 * @param \WP_Query $wp_query        WP_Query instance.
	 * @param callable  $filter_callback Callback passed to filter test products.
	 */
	private function test_get_filtered_price_with( $wp_query, $filter_callback = null ) {
		$query_vars                        = array_filter( $wp_query->query_vars );
		$query_vars['counts-cache-bypass'] = microtime( true );

		$prices = array();

		if ( $filter_callback ) {
			$filtered_product_data = array_filter(
				$this->products_data,
				$filter_callback
			);
		} else {
			$filtered_product_data = $this->products_data;
		}

		foreach ( $filtered_product_data as $product_data ) {
			$prices[] = $product_data['regular_price'] ?? null;

			if ( isset( $product_data['variations'] ) ) {
				foreach ( $product_data['variations'] as $variation_data ) {
					$prices[] = $variation_data['props']['regular_price'] ?? null;
				}
			}
		}

		$prices = array_filter( $prices );
		$prices = array_map( 'intval', $prices );

		$expected_price_range = array(
			'min_price' => min( $prices ),
			'max_price' => max( $prices ),
		);

		$actual_price_range = (array) $this->sut->get_filtered_price( $query_vars );

		$this->assertEqualsCanonicalizing( $expected_price_range, $actual_price_range );
	}
}
