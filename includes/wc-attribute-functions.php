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
	return array_filter( array_map( 'trim', explode( WC_DELIMITER, html_entity_decode( $raw_attributes, ENT_QUOTES, get_bloginfo( 'charset' ) ) ) ), 'wc_get_text_attributes_filter_callback' );
}

/**
 * See if an attribute is actually valid.
 * @since  3.0.0
 * @param  string $value
 * @return bool
 */
function wc_get_text_attributes_filter_callback( $value ) {
	return '' !== $value;
}

/**
 * Implode an array of attributes using WC_DELIMITER.
 * @since  3.0.0
 * @param  array $attributes
 * @return string
 */
function wc_implode_text_attributes( $attributes ) {
	return implode( ' ' . WC_DELIMITER . ' ', $attributes );
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
 * Get the attribute name used when storing values in post meta.
 *
 * @param string $attribute_name Attribute name.
 * @since 2.6.0
 * @return string
 */
function wc_variation_attribute_name( $attribute_name ) {
	return 'attribute_' . sanitize_title( $attribute_name );
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
 * Get a product attribute ID by name.
 *
 * @since  2.6.0
 * @param string $name Attribute name.
 * @return int
 */
function wc_attribute_taxonomy_id_by_name( $name ) {
	$name       = str_replace( 'pa_', '', $name );
	$taxonomies = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_id', 'attribute_name' );

	return isset( $taxonomies[ $name ] ) ? (int) $taxonomies[ $name ] : 0;
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
		$name       = wc_sanitize_taxonomy_name( str_replace( 'pa_', '', $name ) );
		$all_labels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
		$label      = isset( $all_labels[ $name ] ) ? $all_labels[ $name ] : $name;
	} elseif ( $product ) {
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
		}
		// Attempt to get label from product, as entered by the user.
		if ( ( $attributes = $product->get_attributes() ) && isset( $attributes[ sanitize_title( $name ) ] ) ) {
			$label = $attributes[ sanitize_title( $name ) ]->get_name();
		} else {
			$label = $name;
		}
	} else {
		$label = $name;
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
	global $wc_product_attributes, $wpdb;

	$name = str_replace( 'pa_', '', sanitize_title( $name ) );

	if ( isset( $wc_product_attributes[ 'pa_' . $name ] ) ) {
		$orderby = $wc_product_attributes[ 'pa_' . $name ]->attribute_orderby;
	} else {
		$orderby = $wpdb->get_var( $wpdb->prepare( "SELECT attribute_orderby FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = %s;", $name ) );
	}

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
	if ( ! empty( $attribute_taxonomies ) ) {
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
		'text'   => __( 'Text', 'woocommerce' ),
	) );
}

/**
 * Get attribute type label.
 *
 * @since  3.0.0
 * @param  string $type Attribute type slug.
 * @return string
 */
function wc_get_attribute_type_label( $type ) {
	$types = wc_get_attribute_types();

	return isset( $types[ $type ] ) ? $types[ $type ] : ucfirst( $type );
}

/**
 * Check if attribute name is reserved.
 * https://codex.wordpress.org/Function_Reference/register_taxonomy#Reserved_Terms.
 *
 * @since  2.4.0
 * @param  string $attribute_name
 * @return bool
 */
function wc_check_if_attribute_name_is_reserved( $attribute_name ) {
	// Forbidden attribute names
	$reserved_terms = array(
		'attachment',
		'attachment_id',
		'author',
		'author_name',
		'calendar',
		'cat',
		'category',
		'category__and',
		'category__in',
		'category__not_in',
		'category_name',
		'comments_per_page',
		'comments_popup',
		'cpage',
		'day',
		'debug',
		'error',
		'exact',
		'feed',
		'hour',
		'link_category',
		'm',
		'minute',
		'monthnum',
		'more',
		'name',
		'nav_menu',
		'nopaging',
		'offset',
		'order',
		'orderby',
		'p',
		'page',
		'page_id',
		'paged',
		'pagename',
		'pb',
		'perm',
		'post',
		'post__in',
		'post__not_in',
		'post_format',
		'post_mime_type',
		'post_status',
		'post_tag',
		'post_type',
		'posts',
		'posts_per_archive_page',
		'posts_per_page',
		'preview',
		'robots',
		's',
		'search',
		'second',
		'sentence',
		'showposts',
		'static',
		'subpost',
		'subpost_id',
		'tag',
		'tag__and',
		'tag__in',
		'tag__not_in',
		'tag_id',
		'tag_slug__and',
		'tag_slug__in',
		'taxonomy',
		'tb',
		'term',
		'type',
		'w',
		'withcomments',
		'withoutcomments',
		'year',
	);

	return in_array( $attribute_name, $reserved_terms );
}

/**
 * Callback for array filter to get visible only.
 *
 * @since  3.0.0
 * @param  WC_Product $product
 * @return bool
 */
function wc_attributes_array_filter_visible( $attribute ) {
	return $attribute && is_a( $attribute, 'WC_Product_Attribute' ) && $attribute->get_visible() && ( ! $attribute->is_taxonomy() || taxonomy_exists( $attribute->get_name() ) );
}

/**
 * Callback for array filter to get variation attributes only.
 *
 * @since  3.0.0
 * @param  WC_Product $product
 * @return bool
 */
function wc_attributes_array_filter_variation( $attribute ) {
	return $attribute && is_a( $attribute, 'WC_Product_Attribute' ) && $attribute->get_variation();
}

/**
 * Check if an attribute is included in the attributes area of a variation name.
 *
 * @since  3.0.2
 * @param  string $attribute Attribute value to check for
 * @param  string $name      Product name to check in
 * @return bool
 */
function wc_is_attribute_in_product_name( $attribute, $name ) {
	$is_in_name = stristr( $name, ' ' . $attribute . ',' ) || 0 === stripos( strrev( $name ), strrev( ' ' . $attribute ) );
	return apply_filters( 'woocommerce_is_attribute_in_product_name', $is_in_name, $attribute, $name );
}
