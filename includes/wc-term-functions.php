<?php
/**
 * WooCommerce Terms
 *
 * Functions for handling terms/term meta.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Helper to get cached object terms and filter by field using wp_list_pluck().
 * Works as a cached alternative for wp_get_post_terms() and wp_get_object_terms().
 *
 * @since  3.0.0
 * @param  int    $object_id Object ID.
 * @param  string $taxonomy  Taxonomy slug.
 * @param  string $field     Field name.
 * @param  string $index_key Index key name.
 * @return array
 */
function wc_get_object_terms( $object_id, $taxonomy, $field = null, $index_key = null ) {
	// Test if terms exists. get_the_terms() return false when it finds no terms.
	$terms = get_the_terms( $object_id, $taxonomy );

	if ( $terms && ! is_wp_error( $terms ) ) {
		if ( ! is_null( $field ) ) {
			$terms = wp_list_pluck( $terms, $field, $index_key );
		}
	} else {
		$terms = array();
	}

	return $terms;
}

/**
 * Cached version of wp_get_post_terms().
 * This is a private function (internal use ONLY).
 *
 * @since  3.0.0
 * @param  int    $product_id Product ID.
 * @param  string $taxonomy   Taxonomy slug.
 * @param  array  $args       Query arguments.
 * @return array
 */
function _wc_get_cached_product_terms( $product_id, $taxonomy, $args = array() ) {
	$cache_key = 'wc_' . $taxonomy . md5( json_encode( $args ) );
	$terms     = wp_cache_get( $product_id, $cache_key );

	if ( false !== $terms ) {
		return $terms;
	}

	// @codingStandardsIgnoreStart
	$terms = wp_get_post_terms( $product_id, $taxonomy, $args );
	// @codingStandardsIgnoreEnd

	wp_cache_add( $product_id, $terms, $cache_key );

	return $terms;
}

/**
 * Wrapper for wp_get_post_terms which supports ordering by parent.
 *
 * NOTE: At this point in time, ordering by menu_order for example isn't possible with this function. wp_get_post_terms has no.
 *   filters which we can utilise to modify it's query. https://core.trac.wordpress.org/ticket/19094.
 *
 * @param  int    $product_id Product ID.
 * @param  string $taxonomy   Taxonomy slug.
 * @param  array  $args       Query arguments.
 * @return array
 */
function wc_get_product_terms( $product_id, $taxonomy, $args = array() ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	if ( empty( $args['orderby'] ) && taxonomy_is_product_attribute( $taxonomy ) ) {
		$args['orderby'] = wc_attribute_orderby( $taxonomy );
	}

	// Support ordering by parent.
	if ( ! empty( $args['orderby'] ) && in_array( $args['orderby'], array( 'name_num', 'parent' ) ) ) {
		$fields  = isset( $args['fields'] ) ? $args['fields'] : 'all';
		$orderby = $args['orderby'];

		// Unset for wp_get_post_terms.
		unset( $args['orderby'] );
		unset( $args['fields'] );

		$terms = _wc_get_cached_product_terms( $product_id, $taxonomy, $args );

		switch ( $orderby ) {
			case 'name_num' :
				usort( $terms, '_wc_get_product_terms_name_num_usort_callback' );
			break;
			case 'parent' :
				usort( $terms, '_wc_get_product_terms_parent_usort_callback' );
			break;
		}

		switch ( $fields ) {
			case 'names' :
				$terms = wp_list_pluck( $terms, 'name' );
				break;
			case 'ids' :
				$terms = wp_list_pluck( $terms, 'term_id' );
				break;
			case 'slugs' :
				$terms = wp_list_pluck( $terms, 'slug' );
				break;
		}
	} elseif ( ! empty( $args['orderby'] ) && 'menu_order' === $args['orderby'] ) {
		// wp_get_post_terms doesn't let us use custom sort order.
		$args['include'] = wc_get_object_terms( $product_id, $taxonomy, 'term_id' );

		if ( empty( $args['include'] ) ) {
			$terms = array();
		} else {
			// This isn't needed for get_terms.
			unset( $args['orderby'] );

			// Set args for get_terms.
			$args['menu_order'] = isset( $args['order'] ) ? $args['order'] : 'ASC';
			$args['hide_empty'] = isset( $args['hide_empty'] ) ? $args['hide_empty'] : 0;
			$args['fields']     = isset( $args['fields'] ) ? $args['fields'] : 'names';

			// Ensure slugs is valid for get_terms - slugs isn't supported.
			$args['fields']     = ( 'slugs' === $args['fields'] ) ? 'id=>slug' : $args['fields'];
			$terms              = get_terms( $taxonomy, $args );
		}
	} else {
		$terms = _wc_get_cached_product_terms( $product_id, $taxonomy, $args );
	}

	return apply_filters( 'woocommerce_get_product_terms' , $terms, $product_id, $taxonomy, $args );
}

/**
 * Sort by name (numeric).
 * @param  WP_POST object $a
 * @param  WP_POST object $b
 * @return int
 */
function _wc_get_product_terms_name_num_usort_callback( $a, $b ) {
	$a_name = (float) $a->name;
	$b_name = (float) $b->name;

	if ( abs( $a_name - $b_name ) < 0.001 ) {
		return 0;
	}

	return ( $a_name < $b_name ) ? -1 : 1;
}

/**
 * Sort by parent.
 * @param  WP_POST object $a
 * @param  WP_POST object $b
 * @return int
 */
function _wc_get_product_terms_parent_usort_callback( $a, $b ) {
	if ( $a->parent === $b->parent ) {
		return 0;
	}
	return ( $a->parent < $b->parent ) ? 1 : -1;
}

/**
 * WooCommerce Dropdown categories.
 *
 * @param array $args Args to control display of dropdown.
 * @return string
 */
function wc_product_dropdown_categories( $args = array() ) {
	global $wp_query;

	$args = wp_parse_args( $args, array(
		'pad_counts'         => 1,
		'show_count'         => 1,
		'hierarchical'       => 1,
		'hide_empty'         => 1,
		'show_uncategorized' => 1,
		'orderby'            => 'name',
		'selected'           => isset( $wp_query->query_vars['product_cat'] ) ? $wp_query->query_vars['product_cat']: '',
		'menu_order'         => false,
		'show_option_none'   => __( 'Select a category', 'woocommerce' ),
		'option_none_value'  => '',
		'value_field'        => 'slug',
		'taxonomy'           => 'product_cat',
		'name'               => 'product_cat',
		'class'              => 'dropdown_product_cat',
	) );

	if ( 'order' === $args['orderby'] ) {
		$args['menu_order'] = 'asc';
		$args['orderby']    = 'name';
	}

	wp_dropdown_categories( $args );
}

/**
 * Custom walker for Product Categories.
 *
 * Previously used by wc_product_dropdown_categories, but wp_dropdown_categories has been fixed in core.
 *
 * @return mixed
 */
function wc_walk_category_dropdown_tree() {
	$args = func_get_args();

	if ( ! class_exists( 'WC_Product_Cat_Dropdown_Walker', false ) ) {
		include_once( WC()->plugin_path() . '/includes/walkers/class-product-cat-dropdown-walker.php' );
	}

	// the user's options are the third parameter
	if ( empty( $args[2]['walker'] ) || ! is_a( $args[2]['walker'], 'Walker' ) ) {
		$walker = new WC_Product_Cat_Dropdown_Walker;
	} else {
		$walker = $args[2]['walker'];
	}

	return call_user_func_array( array( &$walker, 'walk' ), $args );
}

/**
 * When a term is split, ensure meta data maintained.
 * @param  int $old_term_id
 * @param  int $new_term_id
 * @param  string $term_taxonomy_id
 * @param  string $taxonomy
 */
function wc_taxonomy_metadata_update_content_for_split_terms( $old_term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
	global $wpdb;

	if ( get_option( 'db_version' ) < 34370 ) {
		if ( 'product_cat' === $taxonomy || taxonomy_is_product_attribute( $taxonomy ) ) {
			$old_meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->prefix}woocommerce_termmeta WHERE woocommerce_term_id = %d;", $old_term_id ) );

			// Copy across to split term
			if ( $old_meta_data ) {
				foreach ( $old_meta_data as $meta_data ) {
					$wpdb->insert(
						"{$wpdb->prefix}woocommerce_termmeta",
						array(
							'woocommerce_term_id' => $new_term_id,
							'meta_key'            => $meta_data->meta_key,
							'meta_value'          => $meta_data->meta_value,
						)
					);
				}
			}
		}
	}
}
add_action( 'split_shared_term', 'wc_taxonomy_metadata_update_content_for_split_terms', 10, 4 );

/**
 * Migrate data from WC term meta to WP term meta
 *
 * When the database is updated to support term meta, migrate WC term meta data across.
 * We do this when the new version is >= 34370, and the old version is < 34370 (34370 is when term meta table was added).
 *
 * @param  string $wp_db_version The new $wp_db_version.
 * @param  string $wp_current_db_version The old (current) $wp_db_version.
 */
function wc_taxonomy_metadata_migrate_data( $wp_db_version, $wp_current_db_version ) {
	if ( $wp_db_version >= 34370 && $wp_current_db_version < 34370 ) {
		global $wpdb;
		if ( $wpdb->query( "INSERT INTO {$wpdb->termmeta} ( term_id, meta_key, meta_value ) SELECT woocommerce_term_id, meta_key, meta_value FROM {$wpdb->prefix}woocommerce_termmeta;" ) ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_termmeta" );
		}
	}
}
add_action( 'wp_upgrade', 'wc_taxonomy_metadata_migrate_data', 10, 2 );

/**
 * WooCommerce Term Meta API
 *
 * WC tables for storing term meta are @deprecated from WordPress 4.4 since 4.4 has its own table.
 * This function serves as a wrapper, using the new table if present, or falling back to the WC table.
 *
 * @param mixed $term_id
 * @param string $meta_key
 * @param mixed $meta_value
 * @param string $prev_value (default: '')
 * @return bool
 */
function update_woocommerce_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
	return function_exists( 'update_term_meta' ) ? update_term_meta( $term_id, $meta_key, $meta_value, $prev_value ) : update_metadata( 'woocommerce_term', $term_id, $meta_key, $meta_value, $prev_value );
}

/**
 * WooCommerce Term Meta API
 *
 * WC tables for storing term meta are @deprecated from WordPress 4.4 since 4.4 has its own table.
 * This function serves as a wrapper, using the new table if present, or falling back to the WC table.
 *
 * @param mixed $term_id
 * @param mixed $meta_key
 * @param mixed $meta_value
 * @param bool $unique (default: false)
 * @return bool
 */
function add_woocommerce_term_meta( $term_id, $meta_key, $meta_value, $unique = false ) {
	return function_exists( 'add_term_meta' ) ? add_term_meta( $term_id, $meta_key, $meta_value, $unique ) : add_metadata( 'woocommerce_term', $term_id, $meta_key, $meta_value, $unique );
}

/**
 * WooCommerce Term Meta API
 *
 * WC tables for storing term meta are @deprecated from WordPress 4.4 since 4.4 has its own table.
 * This function serves as a wrapper, using the new table if present, or falling back to the WC table.
 *
 * @param mixed $term_id
 * @param string $meta_key
 * @param string $meta_value (default: '')
 * @param bool $deprecated (default: false)
 * @return bool
 */
function delete_woocommerce_term_meta( $term_id, $meta_key, $meta_value = '', $deprecated = false ) {
	return function_exists( 'delete_term_meta' ) ? delete_term_meta( $term_id, $meta_key, $meta_value ) : delete_metadata( 'woocommerce_term', $term_id, $meta_key, $meta_value );
}

/**
 * WooCommerce Term Meta API
 *
 * WC tables for storing term meta are @deprecated from WordPress 4.4 since 4.4 has its own table.
 * This function serves as a wrapper, using the new table if present, or falling back to the WC table.
 *
 * @param mixed $term_id
 * @param string $key
 * @param bool $single (default: true)
 * @return mixed
 */
function get_woocommerce_term_meta( $term_id, $key, $single = true ) {
	return function_exists( 'get_term_meta' ) ? get_term_meta( $term_id, $key, $single ) : get_metadata( 'woocommerce_term', $term_id, $key, $single );
}

/**
 * Move a term before the a	given element of its hierarchy level.
 *
 * @param int $the_term
 * @param int $next_id the id of the next sibling element in save hierarchy level
 * @param string $taxonomy
 * @param int $index (default: 0)
 * @param mixed $terms (default: null)
 * @return int
 */
function wc_reorder_terms( $the_term, $next_id, $taxonomy, $index = 0, $terms = null ) {
	if ( ! $terms ) {
		$terms = get_terms( $taxonomy, 'menu_order=ASC&hide_empty=0&parent=0' );
	}
	if ( empty( $terms ) ) {
		return $index;
	}

	$id	= $the_term->term_id;

	$term_in_level = false; // flag: is our term to order in this level of terms

	foreach ( $terms as $term ) {

		if ( $term->term_id == $id ) { // our term to order, we skip
			$term_in_level = true;
			continue; // our term to order, we skip
		}
		// the nextid of our term to order, lets move our term here
		if ( null !== $next_id && $term->term_id == $next_id ) {
			$index++;
			$index = wc_set_term_order( $id, $index, $taxonomy, true );
		}

		// set order
		$index++;
		$index = wc_set_term_order( $term->term_id, $index, $taxonomy );

		/**
		 * After a term has had it's order set.
		*/
		do_action( 'woocommerce_after_set_term_order', $term, $index, $taxonomy );

		// if that term has children we walk through them
		$children = get_terms( $taxonomy, "parent={$term->term_id}&menu_order=ASC&hide_empty=0" );
		if ( ! empty( $children ) ) {
			$index = wc_reorder_terms( $the_term, $next_id, $taxonomy, $index, $children );
		}
	}

	// no nextid meaning our term is in last position
	if ( $term_in_level && null === $next_id ) {
		$index = wc_set_term_order( $id, $index + 1, $taxonomy, true );
	}

	return $index;
}

/**
 * Set the sort order of a term.
 *
 * @param int $term_id
 * @param int $index
 * @param string $taxonomy
 * @param bool $recursive (default: false)
 * @return int
 */
function wc_set_term_order( $term_id, $index, $taxonomy, $recursive = false ) {

	$term_id 	= (int) $term_id;
	$index 		= (int) $index;

	// Meta name
	if ( taxonomy_is_product_attribute( $taxonomy ) ) {
		$meta_name = 'order_' . esc_attr( $taxonomy );
	} else {
		$meta_name = 'order';
	}

	update_woocommerce_term_meta( $term_id, $meta_name, $index );

	if ( ! $recursive ) {
		return $index;
	}

	$children = get_terms( $taxonomy, "parent=$term_id&menu_order=ASC&hide_empty=0" );

	foreach ( $children as $term ) {
		$index++;
		$index = wc_set_term_order( $term->term_id, $index, $taxonomy, true );
	}

	clean_term_cache( $term_id, $taxonomy );

	return $index;
}

/**
 * Add term ordering to get_terms.
 *
 * It enables the support a 'menu_order' parameter to get_terms for the product_cat taxonomy.
 * By default it is 'ASC'. It accepts 'DESC' too.
 *
 * To disable it, set it ot false (or 0).
 *
 * @param array $clauses
 * @param array $taxonomies
 * @param array $args
 * @return array
 */
function wc_terms_clauses( $clauses, $taxonomies, $args ) {
	global $wpdb;

	// No sorting when menu_order is false.
	if ( isset( $args['menu_order'] ) && ( false === $args['menu_order'] || 'false' === $args['menu_order'] ) ) {
		return $clauses;
	}

	// No sorting when orderby is non default.
	if ( isset( $args['orderby'] ) && 'name' !== $args['orderby'] ) {
		return $clauses;
	}

	// No sorting in admin when sorting by a column.
	if ( is_admin() && isset( $_GET['orderby'] ) ) {
		return $clauses;
	}

	// No need to filter counts
	if ( strpos( 'COUNT(*)', $clauses['fields'] ) !== false ) {
		return $clauses;
	}

	// WordPress should give us the taxonomies asked when calling the get_terms function. Only apply to categories and pa_ attributes.
	$found = false;

	foreach ( (array) $taxonomies as $taxonomy ) {
		if ( taxonomy_is_product_attribute( $taxonomy ) || in_array( $taxonomy, apply_filters( 'woocommerce_sortable_taxonomies', array( 'product_cat' ) ) ) ) {
			$found = true;
			break;
		}
	}

	if ( ! $found ) {
		return $clauses;
	}

	// Meta name.
	if ( ! empty( $taxonomies[0] ) && taxonomy_is_product_attribute( $taxonomies[0] ) ) {
		$meta_name = 'order_' . esc_attr( $taxonomies[0] );
	} else {
		$meta_name = 'order';
	}

	// Query fields.
	$clauses['fields'] = $clauses['fields'] . ', tm.meta_value';

	// Query join.
	if ( get_option( 'db_version' ) < 34370 ) {
		$clauses['join'] .= " LEFT JOIN {$wpdb->woocommerce_termmeta} AS tm ON (t.term_id = tm.woocommerce_term_id AND tm.meta_key = '" . esc_sql( $meta_name ) . "') ";
	} else {
		$clauses['join'] .= " LEFT JOIN {$wpdb->termmeta} AS tm ON (t.term_id = tm.term_id AND tm.meta_key = '" . esc_sql( $meta_name ) . "') ";
	}

	// Default to ASC.
	if ( ! isset( $args['menu_order'] ) || ! in_array( strtoupper( $args['menu_order'] ), array( 'ASC', 'DESC' ) ) ) {
		$args['menu_order'] = 'ASC';
	}

	$order = "ORDER BY tm.meta_value+0 " . $args['menu_order'];

	if ( $clauses['orderby'] ) {
		$clauses['orderby'] = str_replace( 'ORDER BY', $order . ',', $clauses['orderby'] );
	} else {
		$clauses['orderby'] = $order;
	}

	// Grouping.
	if ( strstr( $clauses['fields'], 'tr.object_id' ) ) {
		$clauses['orderby'] = ' GROUP BY t.term_id, tr.object_id ' . $clauses['orderby'];
	} else {
		$clauses['orderby'] = ' GROUP BY t.term_id ' . $clauses['orderby'];
	}

	return $clauses;
}

add_filter( 'terms_clauses', 'wc_terms_clauses', 99, 3 );

/**
 * Function for recounting product terms, ignoring hidden products.
 *
 * @param array $terms
 * @param string $taxonomy
 * @param bool $callback
 * @param bool $terms_are_term_taxonomy_ids
 */
function _wc_term_recount( $terms, $taxonomy, $callback = true, $terms_are_term_taxonomy_ids = true ) {
	global $wpdb;

	// Standard callback.
	if ( $callback ) {
		_update_post_term_count( $terms, $taxonomy );
	}

	$exclude_term_ids            = array();
	$product_visibility_term_ids = wc_get_product_visibility_term_ids();

	if ( $product_visibility_term_ids['exclude-from-catalog'] ) {
		$exclude_term_ids[] = $product_visibility_term_ids['exclude-from-catalog'];
	}

	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && $product_visibility_term_ids['outofstock'] ) {
		$exclude_term_ids[] = $product_visibility_term_ids['outofstock'];
	}

	$query = array(
		'fields' => "
			SELECT COUNT( DISTINCT ID ) FROM {$wpdb->posts} p
		",
		'join'   => '',
		'where'  => "
			WHERE 1=1
			AND p.post_status = 'publish'
			AND p.post_type = 'product'

		",
	);

	if ( count( $exclude_term_ids ) ) {
		$query['join']  .= " LEFT JOIN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ( " . implode( ',', array_map( 'absint', $exclude_term_ids ) ) . " ) ) AS exclude_join ON exclude_join.object_id = p.ID";
		$query['where'] .= " AND exclude_join.object_id IS NULL";
	}

	// Pre-process term taxonomy ids.
	if ( ! $terms_are_term_taxonomy_ids ) {
		// We passed in an array of TERMS in format id=>parent.
		$terms = array_filter( (array) array_keys( $terms ) );
	} else {
		// If we have term taxonomy IDs we need to get the term ID.
		$term_taxonomy_ids = $terms;
		$terms             = array();
		foreach ( $term_taxonomy_ids as $term_taxonomy_id ) {
			$term    = get_term_by( 'term_taxonomy_id', $term_taxonomy_id, $taxonomy->name );
			$terms[] = $term->term_id;
		}
	}

	// Exit if we have no terms to count.
	if ( empty( $terms ) ) {
		return;
	}

	// Ancestors need counting.
	if ( is_taxonomy_hierarchical( $taxonomy->name ) ) {
		foreach ( $terms as $term_id ) {
			$terms = array_merge( $terms, get_ancestors( $term_id, $taxonomy->name ) );
		}
	}

	// Unique terms only.
	$terms = array_unique( $terms );

	// Count the terms.
	foreach ( $terms as $term_id ) {
		$terms_to_count = array( absint( $term_id ) );

		if ( is_taxonomy_hierarchical( $taxonomy->name ) ) {
			// We need to get the $term's hierarchy so we can count its children too
			if ( ( $children = get_term_children( $term_id, $taxonomy->name ) ) && ! is_wp_error( $children ) ) {
				$terms_to_count = array_unique( array_map( 'absint', array_merge( $terms_to_count, $children ) ) );
			}
		}

		// Generate term query
		$term_query          = $query;
		$term_query['join'] .= " INNER JOIN ( SELECT object_id FROM {$wpdb->term_relationships} INNER JOIN {$wpdb->term_taxonomy} using( term_taxonomy_id ) WHERE term_id IN ( " . implode( ',', array_map( 'absint', $terms_to_count ) ) . " ) ) AS include_join ON include_join.object_id = p.ID";

		// Get the count
		$count = $wpdb->get_var( implode( ' ', $term_query ) );

		// Update the count
		update_woocommerce_term_meta( $term_id, 'product_count_' . $taxonomy->name, absint( $count ) );
	}

	delete_transient( 'wc_term_counts' );
}

/**
 * Recount terms after the stock amount changes.
 *
 * @param int $product_id
 */
function wc_recount_after_stock_change( $product_id ) {
	if ( 'yes' !== get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		return;
	}

	$product_terms = get_the_terms( $product_id, 'product_cat' );

	if ( $product_terms ) {
		$product_cats = array();

		foreach ( $product_terms as $term ) {
			$product_cats[ $term->term_id ] = $term->parent;
		}

		_wc_term_recount( $product_cats, get_taxonomy( 'product_cat' ), false, false );
	}

	$product_terms = get_the_terms( $product_id, 'product_tag' );

	if ( $product_terms ) {
		$product_tags = array();

		foreach ( $product_terms as $term ) {
			$product_tags[ $term->term_id ] = $term->parent;
		}

		_wc_term_recount( $product_tags, get_taxonomy( 'product_tag' ), false, false );
	}
}
add_action( 'woocommerce_product_set_stock_status', 'wc_recount_after_stock_change' );


/**
 * Overrides the original term count for product categories and tags with the product count.
 * that takes catalog visibility into account.
 *
 * @param array $terms
 * @param string|array $taxonomies
 * @return array
 */
function wc_change_term_counts( $terms, $taxonomies ) {
	if ( is_admin() || is_ajax() ) {
		return $terms;
	}

	if ( ! isset( $taxonomies[0] ) || ! in_array( $taxonomies[0], apply_filters( 'woocommerce_change_term_counts', array( 'product_cat', 'product_tag' ) ) ) ) {
		return $terms;
	}

	$term_counts = $o_term_counts = get_transient( 'wc_term_counts' );

	foreach ( $terms as &$term ) {
		if ( is_object( $term ) ) {
			$term_counts[ $term->term_id ] = isset( $term_counts[ $term->term_id ] ) ? $term_counts[ $term->term_id ] : get_woocommerce_term_meta( $term->term_id, 'product_count_' . $taxonomies[0] , true );

			if ( '' !== $term_counts[ $term->term_id ] ) {
				$term->count = absint( $term_counts[ $term->term_id ] );
			}
		}
	}

	// Update transient
	if ( $term_counts != $o_term_counts ) {
		set_transient( 'wc_term_counts', $term_counts, DAY_IN_SECONDS * 30 );
	}

	return $terms;
}
add_filter( 'get_terms', 'wc_change_term_counts', 10, 2 );

/**
 * Return products in a given term, and cache value.
 *
 * To keep in sync, product_count will be cleared on "set_object_terms".
 *
 * @param int $term_id
 * @param string $taxonomy
 * @return array
 */
function wc_get_term_product_ids( $term_id, $taxonomy ) {
	$product_ids = get_woocommerce_term_meta( $term_id, 'product_ids', true );

	if ( false === $product_ids || ! is_array( $product_ids ) ) {
		$product_ids = get_objects_in_term( $term_id, $taxonomy );
		update_woocommerce_term_meta( $term_id, 'product_ids', $product_ids );
	}

	return $product_ids;
}

/**
 * When a post is updated and terms recounted (called by _update_post_term_count), clear the ids.
 * @param int    $object_id  Object ID.
 * @param array  $terms      An array of object terms.
 * @param array  $tt_ids     An array of term taxonomy IDs.
 * @param string $taxonomy   Taxonomy slug.
 * @param bool   $append     Whether to append new terms to the old terms.
 * @param array  $old_tt_ids Old array of term taxonomy IDs.
 */
function wc_clear_term_product_ids( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
	foreach ( $old_tt_ids as $term_id ) {
		delete_woocommerce_term_meta( $term_id, 'product_ids' );
	}
	foreach ( $tt_ids as $term_id ) {
		delete_woocommerce_term_meta( $term_id, 'product_ids' );
	}
}
add_action( 'set_object_terms', 'wc_clear_term_product_ids', 10, 6 );

/**
 * Get full list of product visibilty term ids.
 *
 * @since  3.0.0
 * @return int[]
 */
function wc_get_product_visibility_term_ids() {
	if ( ! taxonomy_exists( 'product_visibility' ) ) {
		wc_doing_it_wrong( __FUNCTION__, 'wc_get_product_visibility_term_ids should not be called before taxonomies are registered (woocommerce_after_register_post_type action).', '3.1' );
		return array();
	}
	return array_map( 'absint', wp_parse_args(
		wp_list_pluck(
			get_terms( array(
				'taxonomy' => 'product_visibility',
				'hide_empty' => false,
			) ),
			'term_taxonomy_id',
			'name'
		),
		array(
			'exclude-from-catalog' => 0,
			'exclude-from-search'  => 0,
			'featured'             => 0,
			'outofstock'           => 0,
			'rated-1'              => 0,
			'rated-2'              => 0,
			'rated-3'              => 0,
			'rated-4'              => 0,
			'rated-5'              => 0,
		)
	) );
}
