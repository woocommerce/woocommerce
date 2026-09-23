<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection;

use WP_Block_Patterns_Registry;
use WP_Block_Template;
use WP_Query;
use WP_Term;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

/**
 * Utility methods used for the Product Collection block.
 * {@internal This class and its methods are not intended for public use.}
 */
class Utils {

	/**
	 * Smallest products-per-page value the archive template may request.
	 */
	const MIN_ARCHIVE_PRODUCTS_PER_PAGE = 1;

	/**
	 * Largest products-per-page value the archive template may request.
	 * Matches the upper bound of the editor control.
	 */
	const MAX_ARCHIVE_PRODUCTS_PER_PAGE = 100;

	/**
	 * How deep to follow template parts and patterns when looking for the
	 * inherited Product Collection in an archive template.
	 */
	const ARCHIVE_TEMPLATE_SCAN_DEPTH = 3;

	/**
	 * Products per page requested by the block template that will render the
	 * current product archive, or null when the template does not set one.
	 *
	 * A Product Collection block whose query type is "Default" renders the
	 * archive's main query, so its page size is decided in
	 * WC_Query::product_query(), before any block renders. When that template's
	 * inherited Product Collection carries a `query.archivePerPage` attribute,
	 * this returns it so the main query is sized by the template. Pagination,
	 * Product Results Count and the filter blocks all read the same main query,
	 * so they stay in sync with the collection.
	 *
	 * The template is resolved the way the template loader resolves it: the
	 * request's template hierarchy, filtered through the `{$type}_template_hierarchy`
	 * hooks that WooCommerce and themes register, matched against the available
	 * block templates in order of specificity.
	 *
	 * @since 11.3.0
	 *
	 * @return int|null Products per page, or null when the template does not set one.
	 */
	public static function get_archive_template_products_per_page() {
		if ( is_admin() || ! wp_is_block_theme() ) {
			return null;
		}

		$template = self::get_current_archive_block_template();
		if ( ! $template instanceof WP_Block_Template || empty( $template->content ) ) {
			return null;
		}

		return self::get_archive_products_per_page_from_content( $template->content );
	}

	/**
	 * Read the products-per-page value of the first Product Collection block in
	 * the given template content whose query type is "Default".
	 *
	 * Template parts and patterns referenced by the content are followed, so the
	 * collection is found wherever the template places it.
	 *
	 * @since 11.3.0
	 *
	 * @param string $content Serialized block template content.
	 * @return int|null Products per page within the allowed range, or null.
	 */
	public static function get_archive_products_per_page_from_content( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return null;
		}

		return self::find_inherited_collection_products_per_page( parse_blocks( $content ), self::ARCHIVE_TEMPLATE_SCAN_DEPTH );
	}

	/**
	 * Resolve the block template WordPress will use for the current product archive request.
	 *
	 * @return WP_Block_Template|null
	 */
	private static function get_current_archive_block_template() {
		$type      = '';
		$templates = array();

		// Same order as wp-includes/template-loader.php: a search is checked before archives.
		if ( is_search() ) {
			$type      = 'search';
			$templates = array( 'search.php' );
		} elseif ( is_post_type_archive( 'product' ) ) {
			$type      = 'archive';
			$templates = array( 'archive-product.php', 'archive.php' );
		} elseif ( is_tax( get_object_taxonomies( 'product' ) ) ) {
			$type = 'taxonomy';
			$term = get_queried_object();
			if ( $term instanceof WP_Term && ! empty( $term->slug ) ) {
				$slug_decoded = urldecode( $term->slug );
				if ( $slug_decoded !== $term->slug ) {
					$templates[] = "taxonomy-{$term->taxonomy}-{$slug_decoded}.php";
				}
				$templates[] = "taxonomy-{$term->taxonomy}-{$term->slug}.php";
				$templates[] = "taxonomy-{$term->taxonomy}-{$term->term_id}.php";
				$templates[] = "taxonomy-{$term->taxonomy}.php";
			}
			$templates[] = 'taxonomy.php';
		} else {
			return null;
		}

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress core hook, documented in wp-includes/template.php.
		$templates = apply_filters( "{$type}_template_hierarchy", $templates );

		// A PHP template of equal or higher specificity in the theme wins over less specific block templates.
		$php_template = locate_template( $templates );
		if ( $php_template ) {
			$relative = str_replace(
				array( get_stylesheet_directory() . '/', get_template_directory() . '/' ),
				'',
				$php_template
			);
			$index    = array_search( $relative, $templates, true );
			if ( false !== $index ) {
				$templates = array_slice( $templates, 0, $index + 1 );
			}
		}

		$slugs = array_map(
			static function ( $template ) {
				return preg_replace( '/\.php$/', '', $template );
			},
			$templates
		);

		$block_templates = get_block_templates( array( 'slug__in' => $slugs ), 'wp_template' );
		if ( empty( $block_templates ) ) {
			return null;
		}

		$priorities = array_flip( $slugs );
		usort(
			$block_templates,
			static function ( $a, $b ) use ( $priorities ) {
				return ( $priorities[ $a->slug ] ?? PHP_INT_MAX ) <=> ( $priorities[ $b->slug ] ?? PHP_INT_MAX );
			}
		);

		return $block_templates[0];
	}

	/**
	 * Walk parsed blocks, following template parts and patterns, for the first
	 * inherited Product Collection that sets a products-per-page value.
	 *
	 * @param array $blocks Parsed blocks.
	 * @param int   $depth  Remaining depth for following template parts and patterns.
	 * @return int|null
	 */
	private static function find_inherited_collection_products_per_page( $blocks, $depth ) {
		if ( ! is_array( $blocks ) || $depth < 0 ) {
			return null;
		}

		$flattened = BlockTemplateUtils::flatten_blocks( $blocks );

		foreach ( $flattened as $block ) {
			$name = $block['blockName'] ?? '';

			if ( 'woocommerce/product-collection' === $name ) {
				$query = $block['attrs']['query'] ?? array();
				if ( ! empty( $query['inherit'] ) && isset( $query['archivePerPage'] ) ) {
					return self::sanitize_archive_products_per_page( $query['archivePerPage'] );
				}
				continue;
			}

			if ( $depth < 1 ) {
				continue;
			}

			$nested_content = null;
			if ( 'core/template-part' === $name && ! empty( $block['attrs']['slug'] ) ) {
				$nested_content = BlockTemplateUtils::get_template_part( sanitize_title( $block['attrs']['slug'] ) );
			} elseif ( 'core/pattern' === $name && ! empty( $block['attrs']['slug'] ) ) {
				$pattern        = WP_Block_Patterns_Registry::get_instance()->get_registered( $block['attrs']['slug'] );
				$nested_content = $pattern['content'] ?? null;
			}

			if ( is_string( $nested_content ) && '' !== $nested_content ) {
				$found = self::find_inherited_collection_products_per_page( parse_blocks( $nested_content ), $depth - 1 );
				if ( null !== $found ) {
					return $found;
				}
			}
		}

		return null;
	}

	/**
	 * Accept a products-per-page value only when it is a whole number within the allowed range.
	 *
	 * @param mixed $value Raw attribute value.
	 * @return int|null
	 */
	private static function sanitize_archive_products_per_page( $value ) {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$per_page = (int) $value;
		if ( (float) $per_page !== (float) $value ) {
			return null;
		}

		if ( $per_page < self::MIN_ARCHIVE_PRODUCTS_PER_PAGE || $per_page > self::MAX_ARCHIVE_PRODUCTS_PER_PAGE ) {
			return null;
		}

		return $per_page;
	}

	/**
	 * Prepare and execute a query for the Product Collection block.
	 * This method is used by the Product Collection block and the No Results block.
	 *
	 * @param WP_Block $block Block instance.
	 */
	public static function prepare_and_execute_query( $block ) {
		$page_key = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
		// phpcs:ignore WordPress.Security.NonceVerification
		$page = empty( $_GET[ $page_key ] ) ? 1 : (int) $_GET[ $page_key ];

		// Use global query if needed.
		$use_global_query = ( isset( $block->context['query']['inherit'] ) && $block->context['query']['inherit'] );
		if ( $use_global_query ) {
			global $wp_query;
			$query = clone $wp_query;
		} else {
			$query_args = build_query_vars_from_query_block( $block, $page );
			$query      = new WP_Query( $query_args );
		}

		return $query;
	}

	/**
	 * Get the random order seed for a Product Collection.
	 *
	 * @since 11.0.0
	 *
	 * @param int   $query_id      Product Collection query ID.
	 * @param array $query_context Product Collection query context.
	 * @return int Random order seed.
	 */
	public static function get_random_order_seed( $query_id, $query_context = array() ) {
		$seed_context         = array(
			'blog_id'           => get_current_blog_id(),
			'queried_object_id' => get_queried_object_id(),
			'query_id'          => absint( $query_id ),
			'rotation_key'      => wp_date( 'Y-m-d' ),
			'query'             => $query_context,
		);
		$encoded_seed_context = wp_json_encode( $seed_context );
		$seed_hash            = (int) sprintf( '%u', crc32( is_string( $encoded_seed_context ) ? $encoded_seed_context : '' ) );

		return ( $seed_hash % 2147483646 ) + 1;
	}

	/**
	 * Helper function that constructs a WP_Query args array from
	 * a Product Collection or global query.
	 *
	 * @param WP_Block $block Block instance.
	 * @param int      $page  Current query's page.
	 *
	 * @return array Returns the constructed WP_Query arguments.
	 */
	public static function get_query_vars( $block, $page ) {
		if ( ! empty( $block->context['query'] ) && ! $block->context['query']['inherit'] ) {
			return build_query_vars_from_query_block( $block, $page );
		}

		global $wp_query;
		return array_filter( $wp_query->query_vars );
	}

	/**
	 * Remove query array from tax or meta query by searching for arrays that
	 * contain exact key => value pair.
	 *
	 * @param array  $queries tax_query or meta_query.
	 * @param string $key     Array key to search for.
	 * @param mixed  $value   Value to compare with search result.
	 *
	 * @return array
	 */
	public static function remove_query_array( $queries, $key, $value ) {
		if ( ! is_array( $queries ) || empty( $queries ) ) {
			return $queries;
		}

		foreach ( $queries as $query_key => $query ) {
			if ( isset( $query[ $key ] ) && $query[ $key ] === $value ) {
				unset( $queries[ $query_key ] );
			}

			if ( isset( $query['relation'] ) || ! isset( $query[ $key ] ) ) {
				$queries[ $query_key ] = self::remove_query_array( $query, $key, $value );
			}
		}

		return self::remove_empty_array_recursive( $queries );
	}

	/**
	 * Parse WP Query's front-end context for the Product Collection block.
	 *
	 * The sourceData structure depends on the context type as follows:
	 * - site:    [ ]
	 * - order:   [ 'orderId'    => int ]
	 * - cart:    [ 'productIds' => int[] ]
	 * - archive: [ 'taxonomy'   => string, 'termId' => int ]
	 * - product: [ 'productId'  => int ]
	 *
	 * @return array $context {
	 *     @type string  $type        The context type. Possible values are 'site', 'order', 'cart', 'archive', 'product'.
	 *     @type array   $sourceData  The context source data. Can be the product ID of the viewed product, the order ID of the current order, etc.
	 * }
	 */
	public static function parse_frontend_location_context() {
		global $wp_query;

		// Default context.
		// Hint: The Shop page uses the default context.
		$type        = 'site';
		$source_data = array();

		if ( ! ( $wp_query instanceof WP_Query ) ) {

			return array(
				'type'       => $type,
				'sourceData' => $source_data,
			);
		}

		// As more areas are blockified, expected future contexts include:
		// - is_checkout_pay_page().
		// - is_view_order_page().
		if ( is_order_received_page() ) {

			$type        = 'order';
			$source_data = array( 'orderId' => absint( $wp_query->query_vars['order-received'] ) );

		} else {
			// Check if we're in a cart block context.
			$current_page       = $wp_query->get_queried_object();
			$has_cart_block     = $current_page && \WC_Blocks_Utils::has_block_in_page( $current_page, 'woocommerce/cart' );
			$has_checkout_block = $current_page && \WC_Blocks_Utils::has_block_in_page( $current_page, 'woocommerce/checkout' );
			$is_cart_available  = isset( WC()->cart ) && is_a( WC()->cart, 'WC_Cart' );

			if ( ( $has_cart_block || $has_checkout_block || is_cart() || is_checkout() ) && $is_cart_available ) {
				$type  = 'cart';
				$items = array();
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					if ( ! isset( $cart_item['product_id'] ) ) {
						continue;
					}

					$items[] = absint( $cart_item['product_id'] );
				}
				$items       = array_unique( array_filter( $items ) );
				$source_data = array( 'productIds' => $items );

			} elseif ( is_product_taxonomy() ) {

				$source      = $wp_query->get_queried_object();
				$is_valid    = is_a( $source, 'WP_Term' );
				$taxonomy    = $is_valid ? $source->taxonomy : '';
				$term_id     = $is_valid ? $source->term_id : '';
				$type        = 'archive';
				$source_data = array(
					'taxonomy' => wc_clean( $taxonomy ),
					'termId'   => absint( $term_id ),
				);

			} elseif ( is_product() ) {

				$source      = $wp_query->get_queried_object();
				$product_id  = is_a( $source, 'WP_Post' ) ? absint( $source->ID ) : 0;
				$type        = 'product';
				$source_data = array( 'productId' => $product_id );
			}
		}

		$context = array(
			'type'       => $type,
			'sourceData' => $source_data,
		);

		return $context;
	}

	/**
	 * Remove falsy item from array, recursively.
	 *
	 * @param array $array The input array to filter.
	 */
	private static function remove_empty_array_recursive( $array ) {
		$array = array_filter( $array );
		foreach ( $array as $key => $item ) {
			if ( is_array( $item ) ) {
				$array[ $key ] = self::remove_empty_array_recursive( $item );
			}
		}
		return $array;
	}
}
