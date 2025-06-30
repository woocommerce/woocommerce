<?php

declare( strict_types = 1);

/**
 * Layered Navigation Widget for brands WC 2.6 version
 *
 * Important: For internal use only by the Automattic\WooCommerce\Internal\Brands package.
 *
 * @package WooCommerce\Widgets
 * @version 9.4.0
 * @extends WP_Widget
 */
class WC_Widget_Brand_Nav extends WC_Widget {
	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {

		/* Widget variable settings. */
		$this->widget_cssclass    = 'woocommerce widget_brand_nav widget_layered_nav';
		$this->widget_description = __( 'Shows brands in a widget which lets you narrow down the list of products when viewing products.', 'woocommerce' );
		$this->widget_id          = 'woocommerce_brand_nav';
		$this->widget_name        = __( 'WooCommerce Brand Layered Nav', 'woocommerce' );

		add_filter( 'woocommerce_product_subcategories_args', array( $this, 'filter_out_cats' ) );

		/* Create the widget. */
		parent::__construct();
	}

	/**
	 * Filter out all categories and not display them
	 *
	 * @param array $cat_args Category arguments.
	 */
	public function filter_out_cats( $cat_args ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['filter_product_brand'] ) ) {
			return array( 'taxonomy' => '' );
		}

		return $cat_args;
	}

	/**
	 * Return the currently viewed taxonomy name.
	 *
	 * @return string
	 */
	protected function get_current_taxonomy() {
		return is_tax() ? get_queried_object()->taxonomy : '';
	}

	/**
	 * Return the currently viewed term ID.
	 *
	 * @return int
	 */
	protected function get_current_term_id() {
		return absint( is_tax() ? get_queried_object()->term_id : 0 );
	}

	/**
	 * Return the currently viewed term slug.
	 *
	 * @return int
	 */
	protected function get_current_term_slug() {
		return absint( is_tax() ? get_queried_object()->slug : 0 );
	}

	/**
	 * Widget function.
	 *
	 * @see WP_Widget
	 *
	 * @param array $args Arguments.
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$attribute_array      = array();
		$attribute_taxonomies = wc_get_attribute_taxonomies();

		if ( ! empty( $attribute_taxonomies ) ) {
			foreach ( $attribute_taxonomies as $tax ) {
				$taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
				if ( taxonomy_exists( $taxonomy_name ) ) {
					$attribute_array[] = $taxonomy_name;
				}
			}
		}

		if ( ! is_post_type_archive( 'product' ) && ! is_tax( array_merge( $attribute_array, array( 'product_cat', 'product_tag', 'product_brand' ) ) ) ) {
			return;
		}

		$_chosen_attributes = WC_Query::get_layered_nav_chosen_attributes();

		$current_term = $attribute_array && is_tax( $attribute_array ) ? get_queried_object()->term_id : '';
		$current_tax  = $attribute_array && is_tax( $attribute_array ) ? get_queried_object()->taxonomy : '';

		/**
		 * Filter the widget's title.
		 *
		 * @since 9.4.0
		 *
		 * @param string $title Widget title
		 * @param array $instance The settings for the particular instance of the widget.
		 * @param string $woo_widget_idbase The widget's id base.
		 */
		$title        = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$taxonomy     = 'product_brand';
		$display_type = isset( $instance['display_type'] ) ? $instance['display_type'] : 'list';

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		// Get only parent terms. Methods will recursively retrieve children.
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'parent'     => 0,
			)
		);

		if ( empty( $terms ) ) {
			return;
		}

		ob_start();

		$this->widget_start( $args, $instance );

		if ( 'dropdown' === $display_type ) {
			$found = $this->layered_nav_dropdown( $terms, $taxonomy );
		} else {
			$found = $this->layered_nav_list( $terms, $taxonomy );
		}

		$this->widget_end( $args );

		// Force found when option is selected - do not force found on taxonomy attributes.
		if ( ! is_tax() && is_array( $_chosen_attributes ) && array_key_exists( $taxonomy, $_chosen_attributes ) ) {
			$found = true;
		}

		if ( ! $found ) {
			ob_end_clean();
		} else {
			echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}

	/**
	 * Update function.
	 *
	 * @see WP_Widget->update
	 *
	 * @param array $new_instance The new settings for the particular instance of the widget.
	 * @param array $old_instance The old settings for the particular instance of the widget.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		global $woocommerce;

		if ( empty( $new_instance['title'] ) ) {
			$new_instance['title'] = __( 'Brands', 'woocommerce' );
		}

		$instance['title']        = wp_strip_all_tags( stripslashes( $new_instance['title'] ) );
		$instance['display_type'] = stripslashes( $new_instance['display_type'] );

		return $instance;
	}

	/**
	 * Form function.
	 *
	 * @see WP_Widget->form
	 *
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function form( $instance ) {
		global $woocommerce;

		if ( ! isset( $instance['display_type'] ) ) {
			$instance['display_type'] = 'list';
		}
		?>
		<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'woocommerce' ); ?></label>
		<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : ''; ?>" />
		</p>

		<p><label for="<?php echo esc_attr( $this->get_field_id( 'display_type' ) ); ?>"><?php esc_html_e( 'Display Type:', 'woocommerce' ); ?></label>
		<select id="<?php echo esc_attr( $this->get_field_id( 'display_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_type' ) ); ?>">
			<option value="list" <?php selected( $instance['display_type'], 'list' ); ?>><?php esc_html_e( 'List', 'woocommerce' ); ?></option>
			<option value="dropdown" <?php selected( $instance['display_type'], 'dropdown' ); ?>><?php esc_html_e( 'Dropdown', 'woocommerce' ); ?></option>
		</select></p>
		<?php
	}

	/**
	 * Get current page URL for layered nav items.
	 *
	 * @param  string $taxonomy Taxonomy.
	 * @return string
	 */
	protected function get_page_base_url( $taxonomy ) {
		if ( defined( 'SHOP_IS_ON_FRONT' ) ) {
			$link = home_url();
		} elseif ( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) {
			$link = get_post_type_archive_link( 'product' );
		} elseif ( is_product_category() ) {
			$link = get_term_link( get_query_var( 'product_cat' ), 'product_cat' );
		} elseif ( is_product_tag() ) {
			$link = get_term_link( get_query_var( 'product_tag' ), 'product_tag' );
		} elseif ( is_tax() ) {
			// Handle any taxonomy archive, including attributes
			$queried_object = get_queried_object();
			if ( is_null( $queried_object ) ) {
				$link = get_post_type_archive_link( 'product' );
			} else {
				$link = get_term_link( $queried_object->term_id, $queried_object->taxonomy );
			}
		} else {
			$link = get_post_type_archive_link( 'product' );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended

		// Min/Max.
		if ( isset( $_GET['min_price'] ) ) {
			$link = add_query_arg( 'min_price', wc_clean( wp_unslash( $_GET['min_price'] ) ), $link );
		}

		if ( isset( $_GET['max_price'] ) ) {
			$link = add_query_arg( 'max_price', wc_clean( wp_unslash( $_GET['max_price'] ) ), $link );
		}

		// Orderby.
		if ( isset( $_GET['orderby'] ) ) {
			$link = add_query_arg( 'orderby', wc_clean( wp_unslash( $_GET['orderby'] ) ), $link );
		}

		/**
		 * Search Arg.
		 * To support quote characters, first they are decoded from &quot; entities, then URL encoded.
		 */
		if ( get_search_query() ) {
			$link = add_query_arg( 's', rawurlencode( htmlspecialchars_decode( get_search_query() ) ), $link );
		}

		// Post Type Arg.
		if ( isset( $_GET['post_type'] ) ) {
			$link = add_query_arg( 'post_type', wc_clean( wp_unslash( $_GET['post_type'] ) ), $link );
		}

		// Min Rating Arg.
		if ( isset( $_GET['min_rating'] ) ) {
			$link = add_query_arg( 'min_rating', wc_clean( wp_unslash( $_GET['min_rating'] ) ), $link );
		}

		// All current filters.
		$_chosen_attributes = WC_Query::get_layered_nav_chosen_attributes();
		if ( $_chosen_attributes ) {
			foreach ( $_chosen_attributes as $name => $data ) {
				if ( $name === $taxonomy ) {
					continue;
				}
				$filter_name = sanitize_title( str_replace( 'pa_', '', $name ) );
				if ( ! empty( $data['terms'] ) ) {
					$link = add_query_arg( 'filter_' . $filter_name, implode( ',', $data['terms'] ), $link );
				}
				if ( 'or' === $data['query_type'] ) {
					$link = add_query_arg( 'query_type_' . $filter_name, 'or', $link );
				}
			}
		}

		return $link;
	}

	/**
	 * Gets the currently selected attributes
	 *
	 * @return array
	 */
	public function get_chosen_attributes() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['filter_product_brand'] ) ) {
			$filter_product_brand = wc_clean( wp_unslash( $_GET['filter_product_brand'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return array_map( 'intval', explode( ',', $filter_product_brand ) );
		}

		return array();
	}

	/**
	 * Show dropdown layered nav.
	 *
	 * @param  array  $terms Terms.
	 * @param  string $taxonomy Taxonomy.
	 * @param  int    $depth Depth.
	 * @return bool Will nav display?
	 */
	protected function layered_nav_dropdown( $terms, $taxonomy, $depth = 0 ) {
		$found = false;

		if ( $taxonomy !== $this->get_current_taxonomy() ) {
			$term_counts        = $this->get_filtered_term_product_counts( wp_list_pluck( $terms, 'term_id' ), $taxonomy, 'or' );
			$_chosen_attributes = $this->get_chosen_attributes();

			if ( 0 === $depth ) {
				echo '<select class="wc-brand-dropdown-layered-nav-' . esc_attr( $taxonomy ) . '">';
				echo '<option value="">' . esc_html__( 'Any Brand', 'woocommerce' ) . '</option>';
			}

			foreach ( $terms as $term ) {
				// If on a term page, skip that term in widget list.
				if ( $term->term_id === $this->get_current_term_id() ) {
					continue;
				}

				// Get count based on current view.
				$current_values = ! empty( $_chosen_attributes ) ? $_chosen_attributes : array();
				$option_is_set  = in_array( $term->term_id, $current_values, true );
				$count          = isset( $term_counts[ $term->term_id ] ) ? $term_counts[ $term->term_id ] : 0;

				// Only show options with count > 0.
				if ( 0 < $count ) {
					$found = true;
				} elseif ( 0 === $count && ! $option_is_set ) {
					continue;
				}

				echo '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( $option_is_set, true, false ) . '>' . esc_html( str_repeat( '&nbsp;', 2 * $depth ) . $term->name ) . '</option>';

				$child_terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => true,
						'parent'     => $term->term_id,
					)
				);

				if ( ! empty( $child_terms ) ) {
					$found |= $this->layered_nav_dropdown( $child_terms, $taxonomy, $depth + 1 );
				}
			}

			if ( 0 === $depth ) {
				$link = $this->get_page_base_url( $taxonomy );
				echo '</select>';

				wc_enqueue_js(
					"
					jQuery( '.wc-brand-dropdown-layered-nav-" . esc_js( $taxonomy ) . "' ).change( function() {
						var slug = jQuery( this ).val();
						location.href = '" . preg_replace( '%\/page\/[0-9]+%', '', str_replace( array( '&amp;', '%2C' ), array( '&', ',' ), esc_js( add_query_arg( 'filtering', '1', esc_url_raw( $link ) ) ) ) ) . '&filter_' . esc_js( $taxonomy ) . "=' + jQuery( this ).val();
					});
				"
				);
			}
		}

		return $found;
	}

	/**
	 * Show list based layered nav.
	 *
	 * @param  array  $terms Terms.
	 * @param  string $taxonomy Taxonomy.
	 * @param  int    $depth Depth.
	 * @return bool   Will nav display?
	 */
	protected function layered_nav_list( $terms, $taxonomy, $depth = 0 ) {
		// List display.
		echo '<ul class="' . ( 0 === $depth ? '' : 'children ' ) . 'wc-brand-list-layered-nav-' . esc_attr( $taxonomy ) . '">';

		$term_counts        = $this->get_filtered_term_product_counts( wp_list_pluck( $terms, 'term_id' ), $taxonomy, 'or' );
		$_chosen_attributes = $this->get_chosen_attributes();
		$current_values     = ! empty( $_chosen_attributes ) ? $_chosen_attributes : array();
		$found              = false;

		$filter_name = 'filter_' . $taxonomy;

		foreach ( $terms as $term ) {
			$option_is_set = in_array( $term->term_id, $current_values, true );
			$count         = isset( $term_counts[ $term->term_id ] ) ? $term_counts[ $term->term_id ] : 0;

			// skip the term for the current archive.
			if ( $this->get_current_term_id() === $term->term_id ) {
				continue;
			}

			// Only show options with count > 0.
			if ( 0 < $count ) {
				$found = true;
			} elseif ( 0 === $count && ! $option_is_set ) {
				continue;
			}

			$current_filter = isset( $_GET[ $filter_name ] ) ? explode( ',', wc_clean( wp_unslash( $_GET[ $filter_name ] ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_filter = array_map( 'intval', $current_filter );

			if ( ! in_array( $term->term_id, $current_filter, true ) ) {
				$current_filter[] = $term->term_id;
			}

			$link = $this->get_page_base_url( $taxonomy );

			// Add current filters to URL.
			foreach ( $current_filter as $key => $value ) {
				// Exclude query arg for current term archive term.
				if ( $value === $this->get_current_term_id() ) {
					unset( $current_filter[ $key ] );
				}

				// Exclude self so filter can be unset on click.
				if ( $option_is_set && $value === $term->term_id ) {
					unset( $current_filter[ $key ] );
				}
			}

			if ( ! empty( $current_filter ) ) {
				$link = add_query_arg(
					array(
						'filtering'  => '1',
						$filter_name => implode( ',', $current_filter ),
					),
					$link
				);
			}

			echo '<li class="wc-layered-nav-term ' . ( $option_is_set ? 'chosen' : '' ) . '">';

			echo ( $count > 0 || $option_is_set ) ? '<a href="' . esc_url( apply_filters( 'woocommerce_layered_nav_link', $link ) ) . '">' : '<span>'; // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

			echo esc_html( $term->name );

			echo ( $count > 0 || $option_is_set ) ? '</a> ' : '</span> ';

			echo wp_kses_post( apply_filters( 'woocommerce_layered_nav_count', '<span class="count">(' . absint( $count ) . ')</span>', $count, $term ) );// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

			$child_terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => true,
					'parent'     => $term->term_id,
				)
			);

			if ( ! empty( $child_terms ) ) {
				$found |= $this->layered_nav_list( $child_terms, $taxonomy, $depth + 1 );
			}

			echo '</li>';
		}

		echo '</ul>';

		return $found;
	}

	/**
	 * Count products within certain terms, taking the main WP query into consideration.
	 *
	 * @param  array  $term_ids Term IDs.
	 * @param  string $taxonomy Taxonomy.
	 * @param  string $query_type Query type.
	 * @return array
	 */
	protected function get_filtered_term_product_counts( $term_ids, $taxonomy, $query_type = 'and' ) {
		global $wpdb;

		$tax_query  = WC_Query::get_main_tax_query();
		$meta_query = WC_Query::get_main_meta_query();

		if ( 'or' === $query_type ) {
			foreach ( $tax_query as $key => $query ) {
				if ( is_array( $query ) && $taxonomy === $query['taxonomy'] ) {
					unset( $tax_query[ $key ] );
				}
			}
		}

		$meta_query     = new WP_Meta_Query( $meta_query );
		$tax_query      = new WP_Tax_Query( $tax_query );
		$meta_query_sql = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
		$tax_query_sql  = $tax_query->get_sql( $wpdb->posts, 'ID' );

		// Generate query.
		$query             = array();
		$query['select']   = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) as term_count, terms.term_id as term_count_id";
		$query['from']     = "FROM {$wpdb->posts}";
		$query['join']     = "
			INNER JOIN {$wpdb->term_relationships} AS term_relationships ON {$wpdb->posts}.ID = term_relationships.object_id
			INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
			INNER JOIN {$wpdb->terms} AS terms USING( term_id )
			" . $tax_query_sql['join'] . $meta_query_sql['join'];
		$query['where']    = "
			WHERE {$wpdb->posts}.post_type IN ( 'product' )
			AND {$wpdb->posts}.post_status = 'publish'
			" . $tax_query_sql['where'] . $meta_query_sql['where'] . '
			AND terms.term_id IN (' . implode( ',', array_map( 'absint', $term_ids ) ) . ')
		';
		$query['group_by'] = 'GROUP BY terms.term_id';
		$query             = apply_filters( 'woocommerce_get_filtered_term_product_counts_query', $query ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$query             = implode( ' ', $query );

		// We have a query - let's see if cached results of this query already exist.
		$query_hash = md5( $query );

		$cache = apply_filters( 'woocommerce_layered_nav_count_maybe_cache', true ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		if ( true === $cache ) {
			$cached_counts = (array) get_transient( 'wc_layered_nav_counts_' . sanitize_title( $taxonomy ) );
		} else {
			$cached_counts = array();
		}

		if ( ! isset( $cached_counts[ $query_hash ] ) ) {
			$results                      = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$counts                       = array_map( 'absint', wp_list_pluck( $results, 'term_count', 'term_count_id' ) );
			$cached_counts[ $query_hash ] = $counts;
			if ( true === $cache ) {
				set_transient( 'wc_layered_nav_counts_' . sanitize_title( $taxonomy ), $cached_counts, HOUR_IN_SECONDS );
			}
		}

		return array_map( 'absint', (array) $cached_counts[ $query_hash ] );
	}
}
