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
 *
 * @param string $raw_attributes
 *
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

		$attribute_taxonomies = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name != '' ORDER BY attribute_name ASC;" );

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
	return $attribute_name ? 'pa_' . wc_sanitize_taxonomy_name( $attribute_name ) : '';
}

/**
 * Get the attribute name used when storing values in post meta.
 *
 * @since 2.6.0
 * @param string $attribute_name Attribute name.
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
	$name       = str_replace( 'pa_', '', wc_sanitize_taxonomy_name( $name ) );
	$taxonomies = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_id', 'attribute_name' );

	return isset( $taxonomies[ $name ] ) ? (int) $taxonomies[ $name ] : 0;
}

/**
 * Get a product attributes label.
 *
 * @param string $name
 * @param object $product object Optional
 * @return string
 */
function wc_attribute_label( $name, $product = '' ) {
	if ( taxonomy_is_product_attribute( $name ) ) {
		$name       = wc_sanitize_taxonomy_name( str_replace( 'pa_', '', $name ) );
		$all_labels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
		$label      = isset( $all_labels[ $name ] ) ? $all_labels[ $name ] : $name;
	} elseif ( $product ) {
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
		}
		// Attempt to get label from product, as entered by the user.
		if ( false !== $product && ( $attributes = $product->get_attributes() ) && isset( $attributes[ sanitize_title( $name ) ] ) ) {
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
	) );
}

/**
 * Check if there are custom attribute types.
 *
 * @since  3.3.2
 * @return bool True if there are custom types, otherwise false.
 */
function wc_has_custom_attribute_types() {
	$types = wc_get_attribute_types();

	return 1 < count( $types ) || ! array_key_exists( 'select', $types );
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

	return isset( $types[ $type ] ) ? $types[ $type ] : __( 'Select', 'woocommerce' );
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
 * @param  WC_Product_Attribute $attribute
 * @return bool
 */
function wc_attributes_array_filter_visible( $attribute ) {
	return $attribute && is_a( $attribute, 'WC_Product_Attribute' ) && $attribute->get_visible() && ( ! $attribute->is_taxonomy() || taxonomy_exists( $attribute->get_name() ) );
}

/**
 * Callback for array filter to get variation attributes only.
 *
 * @since  3.0.0
 * @param  WC_Product_Attribute $attribute
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

/**
 * Callback for array filter to get default attributes.  Will allow for '0' string values, but regard all other
 * class PHP FALSE equivalents normally.
 *
 * @since 3.1.0
 * @param mixed $attribute  Attribute being considered for exclusion from parent array.
 * @return bool
 */
function wc_array_filter_default_attributes( $attribute ) {
	return ( ! empty( $attribute ) || '0' === $attribute );
}

/**
 * Get attribute data by ID.
 *
 * @since  3.2.0
 * @param  int $id Attribute ID.
 * @return stdClass|null
 */
function wc_get_attribute( $id ) {
	global $wpdb;

	$data = $wpdb->get_row( $wpdb->prepare( "
		SELECT *
		FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
		WHERE attribute_id = %d
	 ", $id ) );

	if ( is_wp_error( $data ) || is_null( $data ) ) {
		return null;
	}

	$attribute               = new stdClass();
	$attribute->id           = (int) $data->attribute_id;
	$attribute->name         = $data->attribute_label;
	$attribute->slug         = wc_attribute_taxonomy_name( $data->attribute_name );
	$attribute->type         = $data->attribute_type;
	$attribute->order_by     = $data->attribute_orderby;
	$attribute->has_archives = (bool) $data->attribute_public;

	return $attribute;
}

/**
 * Create attribute.
 *
 * @since  3.2.0
 * @param  array $args Attribute arguments {
 *     Array of attribute parameters.
 *
 *     @type int    $id           Unique identifier, used to update an attribute.
 *     @type string $name         Attribute name. Always required.
 *     @type string $slug         Attribute alphanumeric identifier.
 *     @type string $type         Type of attribute.
 *                                Core by default accepts: 'select' and 'text'.
 *                                Default to 'select'.
 *     @type string $order_by     Sort order.
 *                                Accepts: 'menu_order', 'name', 'name_num' and 'id'.
 *                                Default to 'menu_order'.
 *     @type bool   $has_archives Enable or disable attribute archives. False by default.
 * }
 * @return int|WP_Error
 */
function wc_create_attribute( $args ) {
	global $wpdb;

	$args   = wp_unslash( $args );
	$id     = ! empty( $args['id'] ) ? intval( $args['id'] ) : 0;
	$format = array( '%s', '%s', '%s', '%s', '%d' );

	// Name is required.
	if ( empty( $args['name'] ) ) {
		return new WP_Error( 'missing_attribute_name', __( 'Please, provide an attribute name.', 'woocommerce' ), array( 'status' => 400 ) );
	}

	// Set the attribute slug.
	if ( empty( $args['slug'] ) ) {
		$slug = wc_sanitize_taxonomy_name( $args['name'] );
	} else {
		$slug = preg_replace( '/^pa\_/', '', wc_sanitize_taxonomy_name( $args['slug'] ) );
	}

	// Validate slug.
	if ( strlen( $slug ) >= 28 ) {
		return new WP_Error( 'invalid_product_attribute_slug_too_long', sprintf( __( 'Slug "%s" is too long (28 characters max). Shorten it, please.', 'woocommerce' ), $slug ), array( 'status' => 400 ) );
	} elseif ( wc_check_if_attribute_name_is_reserved( $slug ) ) {
		return new WP_Error( 'invalid_product_attribute_slug_reserved_name', sprintf( __( 'Slug "%s" is not allowed because it is a reserved term. Change it, please.', 'woocommerce' ), $slug ), array( 'status' => 400 ) );
	} elseif ( ( 0 === $id && taxonomy_exists( wc_attribute_taxonomy_name( $slug ) ) ) || ( isset( $args['old_slug'] ) && $args['old_slug'] !== $slug && taxonomy_exists( wc_attribute_taxonomy_name( $slug ) ) ) ) {
		/* translators: %s: attribute slug */
		return new WP_Error( 'invalid_product_attribute_slug_already_exists', sprintf( __( 'Slug "%s" is already in use. Change it, please.', 'woocommerce' ), $slug ), array( 'status' => 400 ) );
	}

	// Validate type.
	if ( empty( $args['type'] ) || ! array_key_exists( $args['type'], wc_get_attribute_types() ) ) {
		$args['type'] = 'select';
	}

	// Validate order by.
	if ( empty( $args['order_by'] ) || ! in_array( $args['order_by'], array( 'menu_order', 'name', 'name_num', 'id' ), true ) ) {
		$args['order_by'] = 'menu_order';
	}

	$data = array(
		'attribute_label'   => $args['name'],
		'attribute_name'    => $slug,
		'attribute_type'    => $args['type'],
		'attribute_orderby' => $args['order_by'],
		'attribute_public'  => isset( $args['has_archives'] ) ? (int) $args['has_archives'] : 0,
	);

	// Create or update.
	if ( 0 === $id ) {
		$results = $wpdb->insert(
			$wpdb->prefix . 'woocommerce_attribute_taxonomies',
			$data,
			$format
		);

		if ( is_wp_error( $results ) ) {
			return new WP_Error( 'cannot_create_attribute', $results->get_error_message(), array( 'status' => 400 ) );
		}

		$id = $wpdb->insert_id;

		/**
		 * Attribute added.
		 *
		 * @param int   $id   Added attribute ID.
		 * @param array $data Attribute data.
		 */
		do_action( 'woocommerce_attribute_added', $id, $data );
	} else {
		$results = $wpdb->update(
			$wpdb->prefix . 'woocommerce_attribute_taxonomies',
			$data,
			array( 'attribute_id' => $id ),
			$format,
			array( '%d' )
		);

		if ( false === $results ) {
			return new WP_Error( 'cannot_update_attribute', __( 'Could not update the attribute.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		// Set old_slug to check for database changes.
		$args['old_slug'] = ! empty( $args['old_slug'] ) ? $args['old_slug'] : $args['slug'];

		/**
		 * Attribute updated.
		 *
		 * @param int    $id        Added attribute ID.
		 * @param array  $data      Attribute data.
		 * @param string $old_slug  Attribute old name.
		 */
		do_action( 'woocommerce_attribute_updated', $id, $data, $args['old_slug'] );

		if ( $args['old_slug'] !== $args['slug'] ) {
			// Update taxonomies in the wp term taxonomy table.
			$wpdb->update(
				$wpdb->term_taxonomy,
				array( 'taxonomy' => wc_attribute_taxonomy_name( $data['attribute_name'] ) ),
				array( 'taxonomy' => 'pa_' . $args['old_slug'] )
			);

			// Update taxonomy ordering term meta.
			$table_name = get_option( 'db_version' ) < 34370 ? $wpdb->prefix . 'woocommerce_termmeta' : $wpdb->termmeta;
			$wpdb->update(
				$table_name,
				array( 'meta_key' => 'order_pa_' . sanitize_title( $data['attribute_name'] ) ),
				array( 'meta_key' => 'order_pa_' . sanitize_title( $args['old_slug'] ) )
			);

			// Update product attributes which use this taxonomy.
			$old_attribute_name_length = strlen( $args['old_slug'] ) + 3;
			$attribute_name_length     = strlen( $data['attribute_name'] ) + 3;

			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE( meta_value, %s, %s ) WHERE meta_key = '_product_attributes'",
				's:' . $old_attribute_name_length . ':"pa_' . $args['old_slug'] . '"',
				's:' . $attribute_name_length . ':"pa_' . $data['attribute_name'] . '"'
			) );

			// Update variations which use this taxonomy.
			$wpdb->update(
				$wpdb->postmeta,
				array( 'meta_key' => 'attribute_pa_' . sanitize_title( $data['attribute_name'] ) ),
				array( 'meta_key' => 'attribute_pa_' . sanitize_title( $args['old_slug'] ) )
			);
		}
	}

	// Clear cache and flush rewrite rules.
	wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );
	delete_transient( 'wc_attribute_taxonomies' );

	return $id;
}

/**
 * Update an attribute.
 *
 * For available args see wc_create_attribute().
 *
 * @since  3.2.0
 * @param  int $id Attribute ID.
 * @param  array $args Attribute arguments.
 * @return int|WP_Error
 */
function wc_update_attribute( $id, $args ) {
	global $wpdb;

	$attribute = wc_get_attribute( $id );

	$args['id'] = $attribute ? $attribute->id : 0;

	if ( $args['id'] && empty( $args['name'] ) ) {
		$args['name'] = $attribute->name;
	}

	$args['old_slug'] = $wpdb->get_var( $wpdb->prepare( "
		SELECT attribute_name
		FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
		WHERE attribute_id = %d
	", $args['id']
	) );

	return wc_create_attribute( $args );
}

/**
 * Delete attribute by ID.
 *
 * @since  3.2.0
 * @param  int $id Attribute ID.
 * @return bool
 */
function wc_delete_attribute( $id ) {
	global $wpdb;

	$name = $wpdb->get_var( $wpdb->prepare( "
		SELECT attribute_name
		FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
		WHERE attribute_id = %d
	", $id ) );

	$taxonomy = wc_attribute_taxonomy_name( $name );

	/**
	 * Before deleting an attribute.
	 *
	 * @param int    $id       Attribute ID.
	 * @param string $name     Attribute name.
	 * @param string $taxonomy Attribute taxonomy name.
	 */
	do_action( 'woocommerce_before_attribute_delete', $id, $name, $taxonomy );

	if ( $name && $wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = $id" ) ) {
		if ( taxonomy_exists( $taxonomy ) ) {
			$terms = get_terms( $taxonomy, 'orderby=name&hide_empty=0' );
			foreach ( $terms as $term ) {
				wp_delete_term( $term->term_id, $taxonomy );
			}
		}

		/**
		 * After deleting an attribute.
		 *
		 * @param int    $id       Attribute ID.
		 * @param string $name     Attribute name.
		 * @param string $taxonomy Attribute taxonomy name.
		 */
		do_action( 'woocommerce_attribute_deleted', $id, $name, $taxonomy );
		wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );
		delete_transient( 'wc_attribute_taxonomies' );

		return true;
	}

	return false;
}
