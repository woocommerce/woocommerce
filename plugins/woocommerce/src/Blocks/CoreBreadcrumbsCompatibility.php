<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks;

/**
 * Adds WooCommerce compatibility behavior to the Core Breadcrumbs block.
 *
 * @internal
 */
final class CoreBreadcrumbsCompatibility {

	/**
	 * Whether the compatibility hooks have been initialized.
	 *
	 * @var bool
	 */
	private $is_initialized = false;

	/**
	 * Initialize Core Breadcrumbs compatibility hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		if ( $this->is_initialized ) {
			return;
		}

		add_filter( 'block_core_breadcrumbs_post_type_settings', array( $this, 'set_product_breadcrumbs_preferred_taxonomy' ), 10, 3 );
		add_filter( 'block_core_breadcrumbs_items', array( $this, 'apply_woocommerce_breadcrumb_filters' ), 10, 1 );

		$this->is_initialized = true;
	}

	/*
	 * Compatibility methods.
	 */

	/**
	 * Set the preferred taxonomy and term for the breadcrumbs block on the product post type.
	 *
	 * This method mimics the behavior of WC_Breadcrumb::add_crumbs_single() to ensure
	 * consistent breadcrumb term selection between WooCommerce's legacy breadcrumbs
	 * and the Core breadcrumbs block.
	 *
	 * @internal
	 *
	 * @param array  $settings The settings for the breadcrumbs block.
	 * @param string $post_type The post type.
	 * @param int    $post_id The current post ID.
	 * @return array The settings for the breadcrumbs block.
	 */
	public function set_product_breadcrumbs_preferred_taxonomy( $settings, $post_type, $post_id = 0 ) {
		if ( ! is_array( $settings ) || 'product' !== $post_type ) {
			return $settings;
		}

		$settings['taxonomy'] = 'product_cat';
		$post_id              = (int) $post_id;

		if ( ! $post_id ) {
			return $settings;
		}

		$terms = wc_get_product_terms(
			$post_id,
			'product_cat',
			/**
			 * Filters the arguments used to fetch product terms for breadcrumbs.
			 *
			 * @since 9.5.0
			 *
			 * @param array $args Array of arguments for `wc_get_product_terms()`.
			 */
			apply_filters(
				'woocommerce_breadcrumb_product_terms_args',
				array(
					'orderby' => 'parent',
					'order'   => 'DESC',
				)
			)
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $settings;
		}

		/**
		 * Filters the main term used in product breadcrumbs.
		 *
		 * @since 9.5.0
		 *
		 * @param \WP_Term   $main_term The main term to be used in breadcrumbs.
		 * @param \WP_Term[] $terms     Array of all product category terms.
		 */
		$main_term = apply_filters( 'woocommerce_breadcrumb_main_term', $terms[0], $terms );

		if ( $main_term instanceof \WP_Term ) {
			$settings['term'] = $main_term->slug;
		}

		return $settings;
	}

	/**
	 * Apply WooCommerce breadcrumb filters to Core breadcrumbs block items.
	 *
	 * This bridges the Core breadcrumbs block with WooCommerce's legacy breadcrumb filters,
	 * ensuring backward compatibility for sites that have customized breadcrumbs using
	 * the `woocommerce_get_breadcrumb` filter.
	 *
	 * @internal
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	public function apply_woocommerce_breadcrumb_filters( $items ) {
		if ( ! is_array( $items ) ) {
			return $items;
		}

		$items = $this->apply_woocommerce_core_breadcrumb_adjustments( $items );

		if ( ! has_filter( 'woocommerce_get_breadcrumb' ) ) {
			return $items;
		}

		// Convert Core format to WooCommerce format.
		// Core: array( 'url' => '...', 'label' => '...' )
		// Woo: array( 'label', 'url' ).
		$wc_crumbs = array_map(
			function ( $item ) {
				return array(
					$item['label'] ?? '',
					$item['url'] ?? '',
				);
			},
			$items
		);

		/**
		 * Filters the breadcrumb trail array.
		 *
		 * @since 2.3.0
		 *
		 * @param array               $crumbs The breadcrumb trail.
		 * @param \WC_Breadcrumb|null $breadcrumb The breadcrumb object (null when called from Core block).
		 */
		$wc_crumbs = apply_filters( 'woocommerce_get_breadcrumb', $wc_crumbs, null );

		$core_items = array();

		foreach ( $wc_crumbs as $index => $crumb ) {
			$item  = isset( $items[ $index ] ) && is_array( $items[ $index ] ) ? $items[ $index ] : array();
			$label = is_array( $crumb ) ? ( $crumb[0] ?? '' ) : '';
			$url   = is_array( $crumb ) ? ( $crumb[1] ?? '' ) : '';

			$item['label'] = $label;

			if ( $url ) {
				$item['url'] = $url;
			} else {
				unset( $item['url'] );
			}

			$core_items[] = $item;
		}

		return $core_items;
	}

	/**
	 * Apply WooCommerce breadcrumb behavior to Core breadcrumbs.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function apply_woocommerce_core_breadcrumb_adjustments( $items ) {
		if ( ! is_array( $items ) ) {
			return $items;
		}

		$items = $this->replace_product_archive_breadcrumb_label( $items );
		$items = $this->prepend_shop_page_to_product_taxonomy_breadcrumbs( $items );
		$items = $this->prepend_taxonomy_label_to_product_taxonomy_breadcrumbs( $items );
		$items = $this->prepend_shop_page_to_product_search_breadcrumbs( $items );
		$items = $this->replace_product_tag_breadcrumb_label( $items );
		$items = $this->replace_search_breadcrumb_label( $items );
		$items = $this->prepend_my_account_page_to_endpoint_breadcrumbs( $items );
		$items = $this->apply_home_breadcrumb_url_filter( $items );

		return $items;
	}

	/**
	 * Apply WooCommerce's Home breadcrumb URL filter.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function apply_home_breadcrumb_url_filter( $items ) {
		if ( ! has_filter( 'woocommerce_breadcrumb_home_url' ) ) {
			return $items;
		}

		$home_index = $this->get_breadcrumb_item_index_by_url( $items, home_url( '/' ) );

		if ( null === $home_index ) {
			return $items;
		}

		$default_home_url = home_url();

		/**
		 * Filters the Home breadcrumb URL.
		 *
		 * @param string $url The Home breadcrumb URL.
		 *
		 * @since 2.3.0
		 */
		$home_url = apply_filters( 'woocommerce_breadcrumb_home_url', $default_home_url );

		$items[ $home_index ]['url'] = is_string( $home_url ) ? $home_url : $default_home_url;

		return $items;
	}

	/**
	 * Replace product archive breadcrumb labels with the WooCommerce shop page title.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_product_archive_breadcrumb_label( $items ) {
		$shop_url = get_post_type_archive_link( 'product' );

		if ( ! $shop_url || null === $this->get_breadcrumb_item_index_by_url( $items, $shop_url ) ) {
			return $items;
		}

		$shop_page_item = $this->get_shop_page_breadcrumb_item( $shop_url );

		if ( empty( $shop_page_item ) ) {
			return $items;
		}

		foreach ( $items as $index => $item ) {
			if ( self::are_breadcrumb_urls_equal( $item['url'] ?? '', $shop_page_item['url'] ) ) {
				$items[ $index ]['label'] = $shop_page_item['label'];
			}
		}

		return $items;
	}

	/**
	 * Prepend the shop page to product taxonomy breadcrumbs.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function prepend_shop_page_to_product_taxonomy_breadcrumbs( $items ) {
		if ( ! ( is_product_category() || is_product_tag() ) ) {
			return $items;
		}

		$permalinks = wc_get_permalink_structure();
		$shop_page  = $this->get_woocommerce_page_post( 'shop' );

		if (
			! $shop_page ||
			! isset( $permalinks['product_base'] ) ||
			! strstr( $permalinks['product_base'], '/' . $shop_page->post_name ) ||
			intval( get_option( 'page_on_front' ) ) === $shop_page->ID
		) {
			return $items;
		}

		return $this->prepend_shop_page_to_breadcrumbs( $items );
	}

	/**
	 * Prepend taxonomy labels to product taxonomy breadcrumbs.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function prepend_taxonomy_label_to_product_taxonomy_breadcrumbs( $items ) {
		if ( ! is_product_taxonomy() || empty( $items ) ) {
			return $items;
		}

		$current_term = $this->get_queried_term();

		if ( ! $current_term || in_array( $current_term->taxonomy, array( 'product_brand', 'product_cat', 'product_tag' ), true ) ) {
			return $items;
		}

		$taxonomy = get_taxonomy( $current_term->taxonomy );

		if ( ! $taxonomy || ! $taxonomy->labels->name ) {
			return $items;
		}

		$insert_index = $this->get_first_breadcrumb_insert_index( $items );

		if ( isset( $items[ $insert_index ]['label'] ) && $taxonomy->labels->name === $items[ $insert_index ]['label'] ) {
			return $items;
		}

		return $this->insert_parent_breadcrumb_item(
			$items,
			array(
				'label' => $taxonomy->labels->name,
			)
		);
	}

	/**
	 * Prepend the shop page to product search breadcrumbs.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function prepend_shop_page_to_product_search_breadcrumbs( $items ) {
		if ( ! $this->is_product_search() || intval( get_option( 'page_on_front' ) ) === wc_get_page_id( 'shop' ) ) {
			return $items;
		}

		return $this->prepend_shop_page_to_breadcrumbs( $items );
	}

	/**
	 * Replace Core's search breadcrumb label with WooCommerce's search label.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_search_breadcrumb_label( $items ) {
		if ( ! is_search() || empty( $items ) ) {
			return $items;
		}

		$search_url = (int) get_query_var( 'paged' ) > 1 ? get_pagenum_link( 1 ) : '';

		/* translators: %s: search term */
		return $this->replace_current_archive_breadcrumb_label( $items, sprintf( __( 'Search results for &ldquo;%s&rdquo;', 'woocommerce' ), get_search_query() ), $search_url );
	}

	/**
	 * Replace product tag breadcrumb labels with WooCommerce's tag archive label.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_product_tag_breadcrumb_label( $items ) {
		if ( ! is_product_tag() || empty( $items ) ) {
			return $items;
		}

		$current_term = $this->get_queried_term();

		if ( ! $current_term ) {
			return $items;
		}

		$tag_link = get_term_link( $current_term, 'product_tag' );

		if ( is_wp_error( $tag_link ) ) {
			$tag_link = '';
		}

		/* translators: %s: product tag */
		return $this->replace_current_archive_breadcrumb_label( $items, sprintf( __( 'Products tagged &ldquo;%s&rdquo;', 'woocommerce' ), $current_term->name ), $tag_link );
	}

	/**
	 * Prepend the My Account page to account endpoint breadcrumbs.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function prepend_my_account_page_to_endpoint_breadcrumbs( $items ) {
		if ( ! is_wc_endpoint_url() || ! is_account_page() ) {
			return $items;
		}

		$my_account_page = $this->get_woocommerce_page_post( 'myaccount' );
		$my_account_url  = $my_account_page ? get_permalink( $my_account_page ) : '';

		if ( ! $my_account_page || ! $my_account_url ) {
			return $items;
		}

		$woocommerce = WC();

		if ( $woocommerce->query instanceof \WC_Query ) {
			$endpoint       = $woocommerce->query->get_current_endpoint();
			$action         = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used only to select the breadcrumb label.
			$endpoint_title = $endpoint ? $woocommerce->query->get_endpoint_title( $endpoint, $action ) : '';

			if ( $endpoint_title ) {
				$items = $this->replace_current_archive_breadcrumb_label( $items, $endpoint_title );
			}
		}

		return $this->insert_parent_breadcrumb_item_if_missing_url(
			$items,
			array(
				'label' => $my_account_page->post_title,
				'url'   => $my_account_url,
			)
		);
	}

	/*
	 * Utility methods.
	 */

	/**
	 * Check whether the current request is a product search.
	 *
	 * @return bool Whether the current request is a product search.
	 */
	private function is_product_search(): bool {
		if ( ! is_search() ) {
			return false;
		}

		$post_type = get_query_var( 'post_type' );

		if ( is_array( $post_type ) ) {
			return in_array( 'product', $post_type, true );
		}

		return 'product' === $post_type || is_shop();
	}

	/**
	 * Prepend the shop page to breadcrumb items.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function prepend_shop_page_to_breadcrumbs( $items ) {
		$shop_page_item = $this->get_shop_page_breadcrumb_item();

		if ( empty( $shop_page_item ) ) {
			return $items;
		}

		$shop_page_index = $this->get_breadcrumb_item_index_by_url( $items, $shop_page_item['url'] );

		if ( null !== $shop_page_index ) {
			return $this->replace_breadcrumb_label_at_index( $items, $shop_page_index, $shop_page_item['label'] );
		}

		return $this->insert_parent_breadcrumb_item_if_missing_url( $items, $shop_page_item );
	}

	/**
	 * Get the shop page breadcrumb item.
	 *
	 * @param string $shop_url Shop archive URL.
	 * @return array|null Shop page breadcrumb item.
	 */
	private function get_shop_page_breadcrumb_item( $shop_url = '' ) {
		$shop_page  = $this->get_woocommerce_page_post( 'shop' );
		$shop_url   = $shop_url ? $shop_url : (
			$shop_page ? get_permalink( $shop_page ) : get_post_type_archive_link( 'product' )
		);
		$shop_label = $shop_page ? get_the_title( $shop_page ) : '';

		if ( ! $shop_label ) {
			$product_post_type = get_post_type_object( 'product' );
			$shop_label        = $product_post_type ? $product_post_type->labels->name : '';
		}

		if ( ! $shop_url || ! $shop_label ) {
			return null;
		}

		return array(
			'label' => $shop_label,
			'url'   => $shop_url,
		);
	}

	/**
	 * Get a WooCommerce page post.
	 *
	 * @param string $page_name WooCommerce page name.
	 * @return \WP_Post|null WooCommerce page post.
	 */
	private function get_woocommerce_page_post( string $page_name ) {
		$page_id = wc_get_page_id( $page_name );

		return $page_id > 0 ? get_post( $page_id ) : null;
	}

	/**
	 * Get the queried term.
	 *
	 * @return \WP_Term|null Queried term.
	 */
	private function get_queried_term() {
		$queried_object = $GLOBALS['wp_query']->get_queried_object();

		return $queried_object instanceof \WP_Term ? $queried_object : null;
	}

	/**
	 * Replace the current archive breadcrumb label.
	 *
	 * @param array  $items Array of breadcrumb items from Core.
	 * @param string $label Replacement label.
	 * @param string $archive_url Archive URL.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_current_archive_breadcrumb_label( $items, $label, $archive_url = '' ) {
		$item_index = $this->get_breadcrumb_item_index_by_url( $items, $archive_url );

		if ( null === $item_index ) {
			$item_keys = array_keys( $items );

			if ( empty( $item_keys ) ) {
				return $items;
			}

			$item_index = end( $item_keys );
			$paged      = (int) get_query_var( 'paged' );

			if ( $paged > 1 && count( $item_keys ) > 1 && isset( $items[ $item_index ]['label'] ) ) {
				$pagination_label = sprintf(
					/* translators: %s: page number */
					__( 'Page %s', 'default' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Match the Core Breadcrumbs block pagination label.
					number_format_i18n( $paged )
				);

				if ( $pagination_label === (string) $items[ $item_index ]['label'] ) {
					$item_index = $item_keys[ count( $item_keys ) - 2 ];
				}
			}

			if ( ! empty( $items[ $item_index ]['url'] ) ) {
				return $items;
			}
		}

		return $this->replace_breadcrumb_label_at_index( $items, $item_index, $label );
	}

	/**
	 * Replace a breadcrumb label at an index.
	 *
	 * @param array           $items Array of breadcrumb items from Core.
	 * @param int|string|null $index Breadcrumb item index.
	 * @param string          $label Replacement label.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_breadcrumb_label_at_index( $items, $index, $label ) {
		if ( null === $index || ! isset( $items[ $index ] ) ) {
			return $items;
		}

		$items[ $index ]['label'] = $label;

		return $items;
	}

	/**
	 * Insert a parent breadcrumb item if its URL is not already present.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @param array $item Breadcrumb item to insert.
	 * @return array Modified breadcrumb items.
	 */
	private function insert_parent_breadcrumb_item_if_missing_url( $items, $item ) {
		if ( empty( $item['url'] ) || null !== $this->get_breadcrumb_item_index_by_url( $items, $item['url'] ) ) {
			return $items;
		}

		return $this->insert_parent_breadcrumb_item( $items, $item );
	}

	/**
	 * Insert a parent breadcrumb item after the home item.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @param array $item Breadcrumb item to insert.
	 * @return array Modified breadcrumb items.
	 */
	private function insert_parent_breadcrumb_item( $items, $item ) {
		array_splice( $items, $this->get_first_breadcrumb_insert_index( $items ), 0, array( $item ) );

		return $items;
	}

	/**
	 * Get the first breadcrumb item index matching a URL.
	 *
	 * @param array  $items Array of breadcrumb items from Core.
	 * @param string $url URL to find.
	 * @return int|string|null Breadcrumb item index.
	 */
	private function get_breadcrumb_item_index_by_url( $items, $url ) {
		if ( ! $url ) {
			return null;
		}

		foreach ( $items as $index => $item ) {
			if ( self::are_breadcrumb_urls_equal( $item['url'] ?? '', $url ) ) {
				return $index;
			}
		}

		return null;
	}

	/**
	 * Get the index where WooCommerce should insert parent breadcrumb items.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return int Breadcrumb insertion index.
	 */
	private function get_first_breadcrumb_insert_index( $items ) {
		if ( empty( $items ) ) {
			return 0;
		}

		$first_item = reset( $items );

		return self::are_breadcrumb_urls_equal( $first_item['url'] ?? '', home_url( '/' ) ) ? 1 : 0;
	}

	/**
	 * Check whether two breadcrumb URLs are equivalent.
	 *
	 * @param string $first_url First URL.
	 * @param string $second_url Second URL.
	 * @return bool Whether the URLs are equivalent.
	 */
	private static function are_breadcrumb_urls_equal( $first_url, $second_url ) {
		return untrailingslashit( (string) $first_url ) === untrailingslashit( (string) $second_url );
	}
}
