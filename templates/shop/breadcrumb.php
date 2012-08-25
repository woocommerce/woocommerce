<?php
/**
 * Shop breadcrumb
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

global $post, $wp_query;

if( ! $home ) 
	$home = _x( 'Home', 'breadcrumb', 'woocommerce' );

$home_link = home_url();

if ( get_option('woocommerce_prepend_shop_page_to_urls') == "yes" && woocommerce_get_page_id( 'shop' ) && get_option( 'page_on_front' ) !== woocommerce_get_page_id( 'shop' ) )
	$prepend =  $before . '<a href="' . get_permalink( woocommerce_get_page_id('shop') ) . '">' . get_the_title( woocommerce_get_page_id('shop') ) . '</a> ' . $after . $delimiter;
else
	$prepend = '';

if ( ( ! is_home() && ! is_front_page() && ! ( is_post_type_archive() && get_option( 'page_on_front' ) == woocommerce_get_page_id( 'shop' ) ) ) || is_paged() ) {

	echo $wrap_before . $before  . '<a class="home" href="' . $home_link . '">' . $home . '</a> '  . $after . $delimiter ;

	if ( is_category() ) {

		$cat_obj = $wp_query->get_queried_object();
		$this_category = get_category( $cat_obj->term_id );
		
		if ( $this_category->parent != 0 ) {
			$parent_category = get_category( $this_category->parent );
			echo get_category_parents($parent_category, TRUE, $delimiter );
		}
		
		echo $before . single_cat_title( '', false ) . $after;

	} elseif ( is_tax('product_cat') ) {

		echo $prepend;
		$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );

		$parents = array();
		$parent = $term->parent;
		while ( $parent ) {
			$parents[] = $parent;
			$new_parent = get_term_by( 'id', $parent, get_query_var( 'taxonomy' ) );
			$parent = $new_parent->parent;
		}

		if ( ! empty( $parents ) ) {
			$parents = array_reverse( $parents );
			foreach ( $parents as $parent ) {
				$item = get_term_by( 'id', $parent, get_query_var( 'taxonomy' ));
				echo $before .  '<a href="' . get_term_link( $item->slug, 'product_cat' ) . '">' . $item->name . '</a>' . $after . $delimiter;
			}
		}

		$queried_object = $wp_query->get_queried_object();
		echo $before . $queried_object->name . $after;

	} elseif ( is_tax('product_tag') ) {

		$queried_object = $wp_query->get_queried_object();
		echo $prepend . $before . __('Products tagged &ldquo;', 'woocommerce') . $queried_object->name . '&rdquo;' . $after;

	} elseif ( is_day() ) {

		echo $before . '<a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a>' . $after . $delimiter;
		echo $before . '<a href="' . get_month_link(get_the_time('Y'),get_the_time('m')) . '">' . get_the_time('F') . '</a>' . $after . $delimiter;
		echo $before . get_the_time('d') . $after;

	} elseif ( is_month() ) {

		echo $before . '<a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a>' . $after . $delimiter;
		echo $before . get_the_time('F') . $after;

	} elseif ( is_year() ) {

		echo $before . get_the_time('Y') . $after;

	} elseif ( is_post_type_archive('product') && get_option('page_on_front') !== woocommerce_get_page_id('shop') ) {

		$_name = woocommerce_get_page_id( 'shop' ) ? get_the_title( woocommerce_get_page_id( 'shop' ) ) : ucwords( get_option( 'woocommerce_shop_slug' ) );

		if ( is_search() ) {

			echo $before . '<a href="' . get_post_type_archive_link('product') . '">' . $_name . '</a>' . $delimiter . __('Search results for &ldquo;', 'woocommerce') . get_search_query() . '&rdquo;' . $after;

		} elseif ( is_paged() ) {

			echo $before . '<a href="' . get_post_type_archive_link('product') . '">' . $_name . '</a>' . $after;

		} else {
		
			echo $before . $_name . $after;

		}

	} elseif ( is_single() && !is_attachment() ) {

		if ( get_post_type() == 'product' ) {

			echo $prepend;

			if ( $terms = wp_get_object_terms( $post->ID, 'product_cat' ) ) {
				$term = current( $terms );
				$parents = array();
				$parent = $term->parent;
				
				while ( $parent ) {
					$parents[] = $parent;
					$new_parent = get_term_by( 'id', $parent, 'product_cat' );
					$parent = $new_parent->parent;
				}
				
				if ( ! empty( $parents ) ) {
					$parents = array_reverse($parents);
					foreach ( $parents as $parent ) {
						$item = get_term_by( 'id', $parent, 'product_cat');
						echo $before . '<a href="' . get_term_link( $item->slug, 'product_cat' ) . '">' . $item->name . '</a>' . $after . $delimiter;
					}
				}
				
				echo $before . '<a href="' . get_term_link( $term->slug, 'product_cat' ) . '">' . $term->name . '</a>' . $after . $delimiter;
			
			}

			echo $before . get_the_title() . $after;

		} elseif ( get_post_type() != 'post' ) {
			
			$post_type = get_post_type_object( get_post_type() );
			$slug = $post_type->rewrite;
				echo $before . '<a href="' . get_post_type_archive_link( get_post_type() ) . '">' . $post_type->labels->singular_name . '</a>' . $after . $delimiter;
			echo $before . get_the_title() . $after;
		
		} else {
			
			$cat = current( get_the_category() );
			echo get_category_parents( $cat, true, $delimiter );
			echo $before . get_the_title() . $after;
		
		}

	} elseif ( is_404() ) {

		echo $before . __( 'Error 404', 'woocommerce' ) . $after;

	} elseif ( ! is_single() && ! is_page() && get_post_type() != 'post' ) {

		$post_type = get_post_type_object( get_post_type() );
		
		if ( $post_type )
			echo $before . $post_type->labels->singular_name . $after;

	} elseif ( is_attachment() ) {

		$parent = get_post( $post->post_parent );
		$cat = get_the_category( $parent->ID ); 
		$cat = $cat[0];
		echo get_category_parents( $cat, true, '' . $delimiter );
		echo $before . '<a href="' . get_permalink( $parent ) . '">' . $parent->post_title . '</a>' . $after . $delimiter;
		echo $before . get_the_title() . $after;

	} elseif ( is_page() && !$post->post_parent ) {

		echo $before . get_the_title() . $after;

	} elseif ( is_page() && $post->post_parent ) {

		$parent_id  = $post->post_parent;
		$breadcrumbs = array();
		
		while ( $parent_id ) {
			$page = get_page( $parent_id );
			$breadcrumbs[] = '<a href="' . get_permalink($page->ID) . '">' . get_the_title( $page->ID ) . '</a>';
			$parent_id  = $page->post_parent;
		}
		
		$breadcrumbs = array_reverse( $breadcrumbs );
		
		foreach ( $breadcrumbs as $crumb )
			echo $crumb . '' . $delimiter;

		echo $before . get_the_title() . $after;

	} elseif ( is_search() ) {

		echo $before . __( 'Search results for &ldquo;', 'woocommerce' ) . get_search_query() . '&rdquo;' . $after;

	} elseif ( is_tag() ) {

			echo $before . __( 'Posts tagged &ldquo;', 'woocommerce' ) . single_tag_title('', false) . '&rdquo;' . $after;

	} elseif ( is_author() ) {

		$userdata = get_userdata($author);
		echo $before . __( 'Author:', 'woocommerce' ) . ' ' . $userdata->display_name . $after;

	}

	if ( get_query_var( 'paged' ) )
		echo ' (' . __( 'Page', 'woocommerce' ) . ' ' . get_query_var( 'paged' ) . ')';

	echo $wrap_after;

}