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
		$main_term            = $this->get_product_main_term( (int) $post_id );

		if ( $main_term ) {
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

		return $this->convert_woocommerce_crumbs_to_core_items( $wc_crumbs, $items );
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
		$items = $this->replace_post_type_archive_breadcrumb_label( $items );
		$items = $this->replace_custom_post_type_single_breadcrumbs( $items );
		$items = $this->prepend_shop_page_to_product_taxonomy_breadcrumbs( $items );
		$items = $this->prepend_taxonomy_label_to_custom_taxonomy_breadcrumbs( $items );
		$items = $this->prepend_shop_page_to_product_search_breadcrumbs( $items );
		$items = $this->replace_product_tag_breadcrumb_label( $items );
		$items = $this->replace_post_tag_breadcrumb_label( $items );
		$items = $this->replace_author_breadcrumb_label( $items );
		$items = $this->replace_day_breadcrumb_label( $items );
		$items = $this->replace_search_breadcrumb_label( $items );
		$items = $this->prepend_my_account_page_to_endpoint_breadcrumbs( $items );
		$items = $this->replace_404_breadcrumb_label( $items );

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

		return $this->replace_breadcrumb_labels_by_url( $items, $shop_page_item['url'], $shop_page_item['label'] );
	}

	/**
	 * Replace custom post type archive breadcrumb labels with WooCommerce's archive labels.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_post_type_archive_breadcrumb_label( $items ) {
		if ( ! is_post_type_archive() || is_shop() || empty( $items ) ) {
			return $items;
		}

		$post_type = get_query_var( 'post_type' );

		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}

		if ( ! is_string( $post_type ) || 'product' === $post_type ) {
			return $items;
		}

		$post_type_object = get_post_type_object( $post_type );
		$archive_link     = get_post_type_archive_link( $post_type );

		if ( ! $post_type_object || ! $archive_link ) {
			return $items;
		}

		return $this->replace_current_archive_breadcrumb_label( $items, $post_type_object->labels->name, $archive_link );
	}

	/**
	 * Replace custom post type single breadcrumbs with WooCommerce's archive trail.
	 *
	 * WooCommerce does not add taxonomy breadcrumbs for non-product custom post types.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_custom_post_type_single_breadcrumbs( $items ) {
		if ( ! is_single() || empty( $items ) ) {
			return $items;
		}

		$post_type = get_post_type();

		if ( ! is_string( $post_type ) || in_array( $post_type, array( 'post', 'product' ), true ) ) {
			return $items;
		}

		$post_type_object = get_post_type_object( $post_type );
		$last_index       = array_key_last( $items );

		if ( ! $post_type_object || null === $last_index ) {
			return $items;
		}

		$modified_items = array_slice( $items, 0, $this->get_first_breadcrumb_insert_index( $items ) );
		$archive_link   = get_post_type_archive_link( $post_type );

		if ( ! empty( $post_type_object->has_archive ) && $archive_link ) {
			$modified_items[] = array(
				'label' => $post_type_object->labels->singular_name,
				'url'   => $archive_link,
			);
		}

		$modified_items[] = $items[ $last_index ];

		return $modified_items;
	}

	/**
	 * Prepend the shop page to product taxonomy breadcrumbs.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function prepend_shop_page_to_product_taxonomy_breadcrumbs( $items ) {
		if ( ! ( is_product_category() || is_product_tag() ) || ! $this->should_prepend_shop_page() ) {
			return $items;
		}

		return $this->prepend_shop_page_to_breadcrumbs( $items );
	}

	/**
	 * Prepend taxonomy labels to custom taxonomy breadcrumbs.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function prepend_taxonomy_label_to_custom_taxonomy_breadcrumbs( $items ) {
		if ( ! is_tax() || empty( $items ) ) {
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
		if ( ! is_search() || ! is_shop() || intval( get_option( 'page_on_front' ) ) === wc_get_page_id( 'shop' ) ) {
			return $items;
		}

		return $this->prepend_shop_page_to_breadcrumbs( $items );
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

		return $this->insert_parent_breadcrumb_item_if_missing_url( $items, $shop_page_item );
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

		/* translators: %s: search term */
		return $this->replace_current_archive_breadcrumb_label( $items, sprintf( __( 'Search results for &ldquo;%s&rdquo;', 'woocommerce' ), get_search_query() ) );
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
	 * Replace post tag breadcrumb labels with WooCommerce's tag archive label.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_post_tag_breadcrumb_label( $items ) {
		if ( ! is_tag() || empty( $items ) ) {
			return $items;
		}

		$current_term = $this->get_queried_term();

		if ( ! $current_term ) {
			return $items;
		}

		/* translators: %s: tag name */
		return $this->replace_current_archive_breadcrumb_label( $items, sprintf( __( 'Posts tagged &ldquo;%s&rdquo;', 'woocommerce' ), single_tag_title( '', false ) ), get_tag_link( $current_term->term_id ) );
	}

	/**
	 * Replace author breadcrumb labels with WooCommerce's author archive label.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_author_breadcrumb_label( $items ) {
		if ( ! is_author() || empty( $items ) ) {
			return $items;
		}

		$current_author = get_queried_object();

		if ( ! $current_author instanceof \WP_User ) {
			return $items;
		}

		/* translators: %s: author name */
		return $this->replace_current_archive_breadcrumb_label( $items, sprintf( __( 'Author: %s', 'woocommerce' ), $current_author->display_name ), get_author_posts_url( $current_author->ID ) );
	}

	/**
	 * Replace day archive breadcrumb labels with WooCommerce's zero-padded day label.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_day_breadcrumb_label( $items ) {
		if ( ! is_day() || empty( $items ) ) {
			return $items;
		}

		return $this->replace_current_archive_breadcrumb_label(
			$items,
			(string) get_the_time( 'd' ),
			get_day_link( (int) get_query_var( 'year' ), (int) get_query_var( 'monthnum' ), (int) get_query_var( 'day' ) )
		);
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

		return $this->insert_parent_breadcrumb_item_if_missing_url(
			$items,
			array(
				'label' => $my_account_page->post_title,
				'url'   => $my_account_url,
			)
		);
	}

	/**
	 * Replace Core's 404 breadcrumb label with WooCommerce's 404 label.
	 *
	 * @param array $items Array of breadcrumb items from Core.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_404_breadcrumb_label( $items ) {
		if ( ! is_404() || empty( $items ) ) {
			return $items;
		}

		return $this->replace_last_breadcrumb_label( $items, __( 'Error 404', 'woocommerce' ) );
	}

	/**
	 * Get the main product category term for breadcrumbs.
	 *
	 * @param int $post_id Post ID.
	 * @return \WP_Term|null Main product category term.
	 */
	private function get_product_main_term( int $post_id ) {
		if ( ! $post_id ) {
			return null;
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
			return null;
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

		return $main_term instanceof \WP_Term ? $main_term : null;
	}

	/**
	 * Get the shop page breadcrumb item.
	 *
	 * @param string $shop_url Shop archive URL.
	 * @return array|null Shop page breadcrumb item.
	 */
	private function get_shop_page_breadcrumb_item( $shop_url = '' ) {
		$shop_page  = $this->get_woocommerce_page_post( 'shop' );
		$shop_url   = $shop_url ? $shop_url : get_post_type_archive_link( 'product' );
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
	 * Check whether WooCommerce would prepend the shop page breadcrumb.
	 *
	 * @return bool Whether to prepend the shop page breadcrumb.
	 */
	private function should_prepend_shop_page() {
		$permalinks = wc_get_permalink_structure();
		$shop_page  = $this->get_woocommerce_page_post( 'shop' );

		return $shop_page
			&& isset( $permalinks['product_base'] )
			&& strstr( $permalinks['product_base'], '/' . $shop_page->post_name )
			&& intval( get_option( 'page_on_front' ) ) !== $shop_page->ID;
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
	 * Convert WooCommerce crumbs back to Core breadcrumb items.
	 *
	 * @param array $wc_crumbs WooCommerce breadcrumb crumbs.
	 * @param array $items Original Core breadcrumb items.
	 * @return array Core breadcrumb items.
	 */
	private function convert_woocommerce_crumbs_to_core_items( $wc_crumbs, $items ) {
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
	 * Replace breadcrumb labels for every item matching a URL.
	 *
	 * @param array  $items Array of breadcrumb items from Core.
	 * @param string $url URL to find.
	 * @param string $label Replacement label.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_breadcrumb_labels_by_url( $items, $url, $label ) {
		if ( ! $url ) {
			return $items;
		}

		foreach ( $items as $index => $item ) {
			if ( self::are_breadcrumb_urls_equal( $item['url'] ?? '', $url ) ) {
				$items[ $index ]['label'] = $label;
			}
		}

		return $items;
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
		return $this->replace_breadcrumb_label_at_index( $items, $this->get_current_archive_breadcrumb_index( $items, $archive_url ), $label );
	}

	/**
	 * Replace the final breadcrumb label.
	 *
	 * @param array  $items Array of breadcrumb items from Core.
	 * @param string $label Replacement label.
	 * @return array Modified breadcrumb items.
	 */
	private function replace_last_breadcrumb_label( $items, $label ) {
		return $this->replace_breadcrumb_label_at_index( $items, array_key_last( $items ), $label );
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
	 * Get the current archive breadcrumb item index.
	 *
	 * @param array  $items Array of breadcrumb items from Core.
	 * @param string $archive_url Archive URL.
	 * @return int|string|null Breadcrumb item index.
	 */
	private function get_current_archive_breadcrumb_index( $items, $archive_url = '' ) {
		$item_index = $this->get_breadcrumb_item_index_by_url( $items, $archive_url );

		if ( null !== $item_index ) {
			return $item_index;
		}

		$item_keys = array_keys( $items );

		if ( empty( $item_keys ) ) {
			return null;
		}

		$last_key = end( $item_keys );

		if ( (int) get_query_var( 'paged' ) > 1 && count( $item_keys ) > 1 && $this->is_pagination_breadcrumb_item( $items[ $last_key ] ) ) {
			return $item_keys[ count( $item_keys ) - 2 ];
		}

		return $last_key;
	}

	/**
	 * Check whether a breadcrumb item is Core's pagination item.
	 *
	 * @param array $item Breadcrumb item.
	 * @return bool Whether this is the pagination item.
	 */
	private function is_pagination_breadcrumb_item( $item ) {
		$paged = (int) get_query_var( 'paged' );

		if ( $paged <= 1 || ! isset( $item['label'] ) ) {
			return false;
		}

		return sprintf(
			/* translators: %s: page number */
			__( 'Page %s', 'default' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Match the Core Breadcrumbs block pagination label.
			number_format_i18n( $paged )
		) === (string) $item['label'];
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
