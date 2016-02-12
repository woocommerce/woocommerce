<?php
/**
 * WooCommerce Attribute Functions
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
 * Gets text attributes from a string.
 *
 * @since  2.4
 * @return array
 */
function wc_get_text_attributes( $raw_attributes ) {
	return array_map( 'trim', explode( WC_DELIMITER, html_entity_decode( $raw_attributes, ENT_QUOTES, get_bloginfo( 'charset' ) ) ) );
}

/**
 * Get attribute taxonomies.
 *
 * @return array of objects
 */
function wc_get_attribute_taxonomies() {
	if ( false === ( $attribute_taxonomies = get_transient( 'wc_attribute_taxonomies' ) ) ) {
		global $wpdb;

		$attribute_taxonomies = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies order by attribute_name ASC;" );

		set_transient( 'wc_attribute_taxonomies', $attribute_taxonomies );
	}

	return (array) array_filter( apply_filters( 'woocommerce_attribute_taxonomies', $attribute_taxonomies ) );
}

/**
 * Get a product attribute name.
 *
 * @param string $attribute_name Attribute name.
 * @return string
 */
function wc_attribute_taxonomy_name( $attribute_name ) {
	return 'pa_' . wc_sanitize_taxonomy_name( $attribute_name );
}

/**
 * Get a product attribute name by ID.
 *
 * @since  2.4.0
 * @param int $attribute_id Attribute ID.
 * @return string Return an empty string if attribute doesn't exist.
 */
function wc_attribute_taxonomy_name_by_id( $attribute_id ) {
	global $wpdb;

	$attribute_name = $wpdb->get_var( $wpdb->prepare( "
		SELECT attribute_name
		FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
		WHERE attribute_id = %d
	", $attribute_id ) );

	if ( $attribute_name && ! is_wp_error( $attribute_name ) ) {
		return wc_attribute_taxonomy_name( $attribute_name );
	}

	return '';
}

/**
 * Get a product attributes label.
 *
 * @param string $name
 * @param  object $product object Optional
 * @return string
 */
function wc_attribute_label( $name, $product = '' ) {
	global $wpdb;

	if ( taxonomy_is_product_attribute( $name ) ) {
		$name = wc_sanitize_taxonomy_name( str_replace( 'pa_', '', $name ) );

		$label = $wpdb->get_var( $wpdb->prepare( "SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s;", $name ) );

		if ( ! $label ) {
			$label = $name;
		}
	} elseif ( $product && ( $attributes = $product->get_attributes() ) && isset( $attributes[ sanitize_title( $name ) ]['name'] ) ) {
		// Attempt to get label from product, as entered by the user
		$label = $attributes[ sanitize_title( $name ) ]['name'];
	} else {
		$label = str_replace( '-', ' ', $name );
	}

	return apply_filters( 'woocommerce_attribute_label', $label, $name, $product );
}

/**
 * Get a product attributes orderby setting.
 *
 * @param mixed $name
 * @return string
 */
function wc_attribute_orderby( $name ) {
	global $wpdb;

	$name = str_replace( 'pa_', '', sanitize_title( $name ) );

	$orderby = $wpdb->get_var( $wpdb->prepare( "SELECT attribute_orderby FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = %s;", $name ) );

	return apply_filters( 'woocommerce_attribute_orderby', $orderby, $name );
}

/**
 * Get an array of product attribute taxonomies.
 *
 * @return array
 */
function wc_get_attribute_taxonomy_names() {
	$taxonomy_names = array();
	$attribute_taxonomies = wc_get_attribute_taxonomies();
	if ( $attribute_taxonomies ) {
		foreach ( $attribute_taxonomies as $tax ) {
			$taxonomy_names[] = wc_attribute_taxonomy_name( $tax->attribute_name );
		}
	}
	return $taxonomy_names;
}

/**
 * Get attribute types.
 *
 * @since  2.4.0
 * @return array
 */
function wc_get_attribute_types() {
	return (array) apply_filters( 'product_attributes_type_selector', array(
		'select' => __( 'Select', 'woocommerce' ),
		'text'   => __( 'Text', 'woocommerce' )
	) );
}

/**
 * Check if attribute name is reserved.
 * http://codex.wordpress.org/Function_Reference/register_taxonomy#Reserved_Terms.
 *
 * @since  2.4.0
 * @param  string $attribute_name
 * @return bool
 */
function wc_check_if_attribute_name_is_reserved( $attribute_name ) {
	// Forbidden attribute names
	$reserved_terms = array(
		'attachment', 'attachment_id', 'author', 'author_name', 'calendar', 'cat', 'category', 'category__and',
		'category__in', 'category__not_in', 'category_name', 'comments_per_page', 'comments_popup', 'cpage', 'day',
		'debug', 'error', 'exact', 'feed', 'hour', 'link_category', 'm', 'minute', 'monthnum', 'more', 'name',
		'nav_menu', 'nopaging', 'offset', 'order', 'orderby', 'p', 'page', 'page_id', 'paged', 'pagename', 'pb', 'perm',
		'post', 'post__in', 'post__not_in', 'post_format', 'post_mime_type', 'post_status', 'post_tag', 'post_type',
		'posts', 'posts_per_archive_page', 'posts_per_page', 'preview', 'robots', 's', 'search', 'second', 'sentence',
		'showposts', 'static', 'subpost', 'subpost_id', 'tag', 'tag__and', 'tag__in', 'tag__not_in', 'tag_id',
		'tag_slug__and', 'tag_slug__in', 'taxonomy', 'tb', 'term', 'type', 'w', 'withcomments', 'withoutcomments', 'year',
	);

	return in_array( $attribute_name, $reserved_terms );
}
