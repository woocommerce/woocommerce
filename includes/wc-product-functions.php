<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Product Functions
 *
 * Functions for product specific things.
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Functions
 * @version  3.0.0
 */

/**
 * Products wrapper for get_posts.
 *
 * This function should be used for product retrieval so that we have a data agnostic
 * way to get a list of products.
 *
 * Args:
 *      status array|string List of statuses to find. Default: any. Options: any, draft, pending, private and publish.
 *      type array|string Product type, e.g. Default: all. Options: all, simple, external, variable, variation, grouped.
 *      parent int post/product parent
 *      sku string Limit result set to products with specific SKU.
 *      category array Limit result set to products assigned to specific categories by slug
 *                     e.g. array('hoodie', 'cap', 't-shirt').
 *      tag array Limit result set to products assigned to specific tags (by slug)
 *                e.g. array('funky', 'retro', 'designer')
 *      shipping_class array Limit results set to products in specific shipping classes (by slug)
 *                           e.g. array('standard', 'next-day')
 *      limit int Maximum of products to retrieve.
 *      offset int Offset of products to retrieve.
 *      page int Page of products to retrieve. Ignored when using the 'offset' arg.
 *      exclude array Product IDs to exclude from the query.
 *      orderby string Order by date, title, id, modified, rand etc
 *      order string ASC or DESC
 *      return string Type of data to return. Allowed values:
 *          ids array of Product ids
 *          objects array of product objects (default)
 *      paginate bool If true, the return value will be an array with values:
 *          'products'      => array of data (return value above),
 *          'total'         => total number of products matching the query
 *          'max_num_pages' => max number of pages found
 *
 * @since  3.0.0
 * @param  array $args Array of args (above)
 * @return array|stdClass Number of pages and an array of product objects if
 *                             paginate is true, or just an array of values.
 */
function wc_get_products( $args ) {
	$args = wp_parse_args( $args, array(
		'status'         => array( 'draft', 'pending', 'private', 'publish' ),
		'type'           => array_merge( array_keys( wc_get_product_types() ) ),
		'parent'         => null,
		'sku'            => '',
		'category'       => array(),
		'tag'            => array(),
		'limit'          => get_option( 'posts_per_page' ),
		'offset'         => null,
		'page'           => 1,
		'include'        => array(),
		'exclude'        => array(),
		'orderby'        => 'date',
		'order'          => 'DESC',
		'return'         => 'objects',
		'paginate'       => false,
		'shipping_class' => array(),
	) );

	// Handle some BW compatibility arg names where wp_query args differ in naming.
	$map_legacy = array(
		'numberposts'    => 'limit',
		'post_status'    => 'status',
		'post_parent'    => 'parent',
		'posts_per_page' => 'limit',
		'paged'          => 'page',
	);

	foreach ( $map_legacy as $from => $to ) {
		if ( isset( $args[ $from ] ) ) {
			$args[ $to ] = $args[ $from ];
		}
	}

	return WC_Data_Store::load( 'product' )->get_products( $args );
}

/**
 * Main function for returning products, uses the WC_Product_Factory class.
 *
 * @since 2.2.0
 *
 * @param mixed $the_product Post object or post ID of the product.
 * @param array $deprecated Previously used to pass arguments to the factory, e.g. to force a type.
 * @return WC_Product|null
 */
function wc_get_product( $the_product = false, $deprecated = array() ) {
	if ( ! did_action( 'woocommerce_init' ) ) {
		wc_doing_it_wrong( __FUNCTION__, __( 'wc_get_product should not be called before the woocommerce_init action.', 'woocommerce' ), '2.5' );
		return false;
	}
	if ( ! empty( $deprecated ) ) {
		wc_deprecated_argument( 'args', '3.0', 'Passing args to wc_get_product is deprecated. If you need to force a type, construct the product class directly.' );
	}
	return WC()->product_factory->get_product( $the_product, $deprecated );
}

/**
 * Returns whether or not SKUS are enabled.
 * @return bool
 */
function wc_product_sku_enabled() {
	return apply_filters( 'wc_product_sku_enabled', true );
}

/**
 * Returns whether or not product weights are enabled.
 * @return bool
 */
function wc_product_weight_enabled() {
	return apply_filters( 'wc_product_weight_enabled', true );
}

/**
 * Returns whether or not product dimensions (HxWxD) are enabled.
 * @return bool
 */
function wc_product_dimensions_enabled() {
	return apply_filters( 'wc_product_dimensions_enabled', true );
}

/**
 * Clear all transients cache for product data.
 *
 * @param int $post_id (default: 0)
 */
function wc_delete_product_transients( $post_id = 0 ) {
	// Core transients
	$transients_to_clear = array(
		'wc_products_onsale',
		'wc_featured_products',
		'wc_outofstock_count',
		'wc_low_stock_count',
	);

	// Transient names that include an ID
	$post_transient_names = array(
		'wc_product_children_',
		'wc_var_prices_',
		'wc_related_',
		'wc_child_has_weight_',
		'wc_child_has_dimensions_',
	);

	if ( $post_id > 0 ) {
		foreach ( $post_transient_names as $transient ) {
			$transients_to_clear[] = $transient . $post_id;
		}

		// Does this product have a parent?
		$product = wc_get_product( $post_id );

		if ( $product && $product->get_parent_id() > 0 ) {
			wc_delete_product_transients( $product->get_parent_id() );
		}
	}

	// Delete transients
	foreach ( $transients_to_clear as $transient ) {
		delete_transient( $transient );
	}

	// Increments the transient version to invalidate cache
	WC_Cache_Helper::get_transient_version( 'product', true );

	do_action( 'woocommerce_delete_product_transients', $post_id );
}

/**
 * Function that returns an array containing the IDs of the products that are on sale.
 *
 * @since 2.0
 * @return array
 */
function wc_get_product_ids_on_sale() {
	// Load from cache
	$product_ids_on_sale = get_transient( 'wc_products_onsale' );

	// Valid cache found
	if ( false !== $product_ids_on_sale ) {
		return $product_ids_on_sale;
	}

	$data_store          = WC_Data_Store::load( 'product' );
	$on_sale_products    = $data_store->get_on_sale_products();
	$product_ids_on_sale = wp_parse_id_list( array_merge( wp_list_pluck( $on_sale_products, 'id' ), array_diff( wp_list_pluck( $on_sale_products, 'parent_id' ), array( 0 ) ) ) );

	set_transient( 'wc_products_onsale', $product_ids_on_sale, DAY_IN_SECONDS * 30 );

	return $product_ids_on_sale;
}

/**
 * Function that returns an array containing the IDs of the featured products.
 *
 * @since 2.1
 * @return array
 */
function wc_get_featured_product_ids() {
	// Load from cache
	$featured_product_ids = get_transient( 'wc_featured_products' );

	// Valid cache found
	if ( false !== $featured_product_ids )
		return $featured_product_ids;

	$data_store           = WC_Data_Store::load( 'product' );
	$featured             = $data_store->get_featured_product_ids();
	$product_ids          = array_keys( $featured );
	$parent_ids           = array_values( array_filter( $featured ) );
	$featured_product_ids = array_unique( array_merge( $product_ids, $parent_ids ) );

	set_transient( 'wc_featured_products', $featured_product_ids, DAY_IN_SECONDS * 30 );

	return $featured_product_ids;
}

/**
 * Filter to allow product_cat in the permalinks for products.
 *
 * @param  string  $permalink The existing permalink URL.
 * @param  WP_Post $post
 * @return string
 */
function wc_product_post_type_link( $permalink, $post ) {
	// Abort if post is not a product.
	if ( 'product' !== $post->post_type ) {
		return $permalink;
	}

	// Abort early if the placeholder rewrite tag isn't in the generated URL.
	if ( false === strpos( $permalink, '%' ) ) {
		return $permalink;
	}

	// Get the custom taxonomy terms in use by this post.
	$terms = get_the_terms( $post->ID, 'product_cat' );

	if ( ! empty( $terms ) ) {
		if ( function_exists( 'wp_list_sort' ) ) {
			$terms = wp_list_sort( $terms, 'term_id', 'ASC' );
		} else {
			usort( $terms, '_usort_terms_by_ID' );
		}
		$category_object = apply_filters( 'wc_product_post_type_link_product_cat', $terms[0], $terms, $post );
		$category_object = get_term( $category_object, 'product_cat' );
		$product_cat     = $category_object->slug;

		if ( $category_object->parent ) {
			$ancestors = get_ancestors( $category_object->term_id, 'product_cat' );
			foreach ( $ancestors as $ancestor ) {
				$ancestor_object = get_term( $ancestor, 'product_cat' );
				$product_cat     = $ancestor_object->slug . '/' . $product_cat;
			}
		}
	} else {
		// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
		$product_cat = _x( 'uncategorized', 'slug', 'woocommerce' );
	}

	$find = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		'%post_id%',
		'%category%',
		'%product_cat%',
	);

	$replace = array(
		date_i18n( 'Y', strtotime( $post->post_date ) ),
		date_i18n( 'm', strtotime( $post->post_date ) ),
		date_i18n( 'd', strtotime( $post->post_date ) ),
		date_i18n( 'H', strtotime( $post->post_date ) ),
		date_i18n( 'i', strtotime( $post->post_date ) ),
		date_i18n( 's', strtotime( $post->post_date ) ),
		$post->ID,
		$product_cat,
		$product_cat,
	);

	$permalink = str_replace( $find, $replace, $permalink );

	return $permalink;
}
add_filter( 'post_type_link', 'wc_product_post_type_link', 10, 2 );


/**
 * Get the placeholder image URL for products etc.
 *
 * @access public
 * @return string
 */
function wc_placeholder_img_src() {
	return apply_filters( 'woocommerce_placeholder_img_src', WC()->plugin_url() . '/assets/images/placeholder.png' );
}

/**
 * Get the placeholder image.
 *
 * @access public
 * @return string
 */
function wc_placeholder_img( $size = 'shop_thumbnail' ) {
	$dimensions = wc_get_image_size( $size );

	return apply_filters( 'woocommerce_placeholder_img', '<img src="' . wc_placeholder_img_src() . '" alt="' . esc_attr__( 'Placeholder', 'woocommerce' ) . '" width="' . esc_attr( $dimensions['width'] ) . '" class="woocommerce-placeholder wp-post-image" height="' . esc_attr( $dimensions['height'] ) . '" />', $size, $dimensions );
}

/**
 * Variation Formatting.
 *
 * Gets a formatted version of variation data or item meta.
 *
 * @param array|WC_Product_Variation $variation
 * @param bool $flat (default: false)
 * @param bool $include_names include attribute names/labels
 * @return string
 */
function wc_get_formatted_variation( $variation, $flat = false, $include_names = true ) {
	$return = '';

	if ( is_a( $variation, 'WC_Product_Variation' ) ) {
		$variation_attributes = $variation->get_attributes();
		$product              = $variation;
	} else {
		$product              = false;
		// Remove attribute_ prefix from names.
		$variation_attributes = array();
		if ( is_array( $variation ) ) {
			foreach ( $variation as $key => $value ) {
				$variation_attributes[ str_replace( 'attribute_', '', $key ) ] = $value;
			}
		}
	}

	$list_type = $include_names ? 'dl' : 'ul';

	if ( is_array( $variation_attributes ) ) {

		if ( ! $flat ) {
			$return = '<' . $list_type . ' class="variation">';
		}

		$variation_list = array();

		foreach ( $variation_attributes as $name => $value ) {
			if ( ! $value ) {
				continue;
			}

			// If this is a term slug, get the term's nice name
			if ( taxonomy_exists( $name ) ) {
				$term = get_term_by( 'slug', $value, $name );
				if ( ! is_wp_error( $term ) && ! empty( $term->name ) ) {
					$value = $term->name;
				}
			} else {
				$value = ucwords( str_replace( '-', ' ', $value ) );
			}

			if ( $include_names ) {
				if ( $flat ) {
					$variation_list[] = wc_attribute_label( $name, $product ) . ': ' . rawurldecode( $value );
				} else {
					$variation_list[] = '<dt>' . wc_attribute_label( $name, $product ) . ':</dt><dd>' . rawurldecode( $value ) . '</dd>';
				}
			} else {
				if ( $flat ) {
					$variation_list[] = rawurldecode( $value );
				} else {
					$variation_list[] = '<li>' . rawurldecode( $value ) . '</li>';
				}
			}
		}

		if ( $flat ) {
			$return .= implode( ', ', $variation_list );
		} else {
			$return .= implode( '', $variation_list );
		}

		if ( ! $flat ) {
			$return .= '</' . $list_type . '>';
		}
	}
	return $return;
}

/**
 * Function which handles the start and end of scheduled sales via cron.
 */
function wc_scheduled_sales() {
	$data_store = WC_Data_Store::load( 'product' );

	// Sales which are due to start
	$product_ids = $data_store->get_starting_sales();
	if ( $product_ids ) {
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			$sale_price = $product->get_sale_price();

			if ( $sale_price ) {
				$product->set_price( $sale_price );
				$product->set_date_on_sale_from( '' );
			} else {
				$product->set_date_on_sale_to( '' );
				$product->set_date_on_sale_from( '' );
			}

			$product->save();
		}

		delete_transient( 'wc_products_onsale' );
	}

	// Sales which are due to end
	$product_ids = $data_store->get_ending_sales();
	if ( $product_ids ) {
		foreach ( $product_ids as $product_id ) {
			$product       = wc_get_product( $product_id );
			$regular_price = $product->get_regular_price();
			$product->set_price( $regular_price );
			$product->set_sale_price( '' );
			$product->set_date_on_sale_to( '' );
			$product->set_date_on_sale_from( '' );
			$product->save();
		}

		WC_Cache_Helper::get_transient_version( 'product', true );
		delete_transient( 'wc_products_onsale' );
	}
}
add_action( 'woocommerce_scheduled_sales', 'wc_scheduled_sales' );

/**
 * Get attachment image attributes.
 *
 * @access public
 * @param array $attr
 * @return array
 */
function wc_get_attachment_image_attributes( $attr ) {
	if ( strstr( $attr['src'][0], 'woocommerce_uploads/' ) ) {
		$attr['src'][0] = wc_placeholder_img_src();
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'wc_get_attachment_image_attributes' );


/**
 * Prepare attachment for JavaScript.
 *
 * @access public
 * @param array $response
 * @return array
 */
function wc_prepare_attachment_for_js( $response ) {

	if ( isset( $response['url'] ) && strstr( $response['url'], 'woocommerce_uploads/' ) ) {
		$response['full']['url'] = wc_placeholder_img_src();
		if ( isset( $response['sizes'] ) ) {
			foreach ( $response['sizes'] as $size => $value ) {
				$response['sizes'][ $size ]['url'] = wc_placeholder_img_src();
			}
		}
	}

	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'wc_prepare_attachment_for_js' );

/**
 * Track product views.
 */
function wc_track_product_view() {
	if ( ! is_singular( 'product' ) || ! is_active_widget( false, false, 'woocommerce_recently_viewed_products', true ) ) {
		return;
	}

	global $post;

	if ( empty( $_COOKIE['woocommerce_recently_viewed'] ) )
		$viewed_products = array();
	else
		$viewed_products = (array) explode( '|', $_COOKIE['woocommerce_recently_viewed'] );

	if ( ! in_array( $post->ID, $viewed_products ) ) {
		$viewed_products[] = $post->ID;
	}

	if ( sizeof( $viewed_products ) > 15 ) {
		array_shift( $viewed_products );
	}

	// Store for session only
	wc_setcookie( 'woocommerce_recently_viewed', implode( '|', $viewed_products ) );
}

add_action( 'template_redirect', 'wc_track_product_view', 20 );

/**
 * Get product types.
 *
 * @since 2.2
 * @return array
 */
function wc_get_product_types() {
	return (array) apply_filters( 'product_type_selector', array(
		'simple'   => __( 'Simple product', 'woocommerce' ),
		'grouped'  => __( 'Grouped product', 'woocommerce' ),
		'external' => __( 'External/Affiliate product', 'woocommerce' ),
		'variable' => __( 'Variable product', 'woocommerce' ),
	) );
}

/**
 * Check if product sku is unique.
 *
 * @since 2.2
 * @param int $product_id
 * @param string $sku
 * @return bool
 */
function wc_product_has_unique_sku( $product_id, $sku ) {
	$data_store = WC_Data_Store::load( 'product' );
	$sku_found  = $data_store->is_existing_sku( $product_id, $sku );

	if ( apply_filters( 'wc_product_has_unique_sku', $sku_found, $product_id, $sku ) ) {
		return false;
	} else {
		return true;
	}
}

/**
 * Force a unique SKU.
 *
 * @since  3.0.0
 * @param  integer $product_id
 */
function wc_product_force_unique_sku( $product_id ) {
	$product = wc_get_product( $product_id );

	if ( $product && ( $current_sku = $product->get_sku( 'edit' ) ) ) {
		try {
			$new_sku = wc_product_generate_unique_sku( $product_id, $current_sku );

			if ( $current_sku !== $new_sku ) {
				$product->set_sku( $new_sku );
				$product->save();
			}
		} catch ( Exception $e ) {}
	}
}

/**
 * Recursively appends a suffix until a unique SKU is found.
 *
 * @since  3.0.0
 * @param  integer $product_id
 * @param  string  $sku
 * @param  integer $index
 * @return string
 */
function wc_product_generate_unique_sku( $product_id, $sku, $index = 0 ) {
	$generated_sku = 0 < $index ? $sku . '-' . $index : $sku;

	if ( ! wc_product_has_unique_sku( $product_id, $generated_sku ) ) {
		$generated_sku = wc_product_generate_unique_sku( $product_id, $sku, ( $index + 1 ) );
	}

	return $generated_sku;
}

/**
 * Get product ID by SKU.
 *
 * @since  2.3.0
 * @param  string $sku
 * @return int
 */
function wc_get_product_id_by_sku( $sku ) {
	global $wpdb;

	$data_store = WC_Data_Store::load( 'product' );
	$product_id = $data_store->get_product_id_by_sku( $sku );

	return ( $product_id ) ? intval( $product_id ) : 0;
}

/**
 * Get attibutes/data for an individual variation from the database and maintain it's integrity.
 * @param revisit?
 * @since  2.4.0
 * @param  int $variation_id
 * @return array
 */
function wc_get_product_variation_attributes( $variation_id ) {
	// Build variation data from meta
	$all_meta                = get_post_meta( $variation_id );
	$parent_id               = wp_get_post_parent_id( $variation_id );
	$parent_attributes       = array_filter( (array) get_post_meta( $parent_id, '_product_attributes', true ) );
	$found_parent_attributes = array();
	$variation_attributes    = array();

	// Compare to parent variable product attributes and ensure they match
	foreach ( $parent_attributes as $attribute_name => $options ) {
		if ( ! empty( $options['is_variation'] ) ) {
			$attribute                 = 'attribute_' . sanitize_title( $attribute_name );
			$found_parent_attributes[] = $attribute;
			if ( ! array_key_exists( $attribute, $variation_attributes ) ) {
				$variation_attributes[ $attribute ] = ''; // Add it - 'any' will be asumed
			}
		}
	}

	// Get the variation attributes from meta
	foreach ( $all_meta as $name => $value ) {
		// Only look at valid attribute meta, and also compare variation level attributes and remove any which do not exist at parent level
		if ( 0 !== strpos( $name, 'attribute_' ) || ! in_array( $name, $found_parent_attributes ) ) {
			unset( $variation_attributes[ $name ] );
			continue;
		}
		/**
		 * Pre 2.4 handling where 'slugs' were saved instead of the full text attribute.
		 * Attempt to get full version of the text attribute from the parent.
		 */
		if ( sanitize_title( $value[0] ) === $value[0] && version_compare( get_post_meta( $parent_id, '_product_version', true ), '2.4.0', '<' ) ) {
			foreach ( $parent_attributes as $attribute ) {
				if ( 'attribute_' . sanitize_title( $attribute['name'] ) !== $name ) {
					continue;
				}
				$text_attributes = wc_get_text_attributes( $attribute['value'] );

				foreach ( $text_attributes as $text_attribute ) {
					if ( sanitize_title( $text_attribute ) === $value[0] ) {
						$value[0] = $text_attribute;
						break;
					}
				}
			}
		}

		$variation_attributes[ $name ] = $value[0];
	}

	return $variation_attributes;
}

/**
 * Get all product cats for a product by ID, including hierarchy
 * @since  2.5.0
 * @param  int $product_id
 * @return array
 */
function wc_get_product_cat_ids( $product_id ) {
	$product_cats = wc_get_product_term_ids( $product_id, 'product_cat' );

	foreach ( $product_cats as $product_cat ) {
		$product_cats = array_merge( $product_cats, get_ancestors( $product_cat, 'product_cat' ) );
	}

	return $product_cats;
}

/**
 * Gets data about an attachment, such as alt text and captions.
 * @since 2.6.0
 * @param object|bool $product
 * @return array
 */
function wc_get_product_attachment_props( $attachment_id = null, $product = false ) {
	$props = array(
		'title'   => '',
		'caption' => '',
		'url'     => '',
		'alt'     => '',
		'src'     => '',
		'srcset'  => false,
		'sizes'   => false,
	);
	if ( $attachment = get_post( $attachment_id ) ) {
		$props['title']   = trim( strip_tags( $attachment->post_title ) );
		$props['caption'] = trim( strip_tags( $attachment->post_excerpt ) );
		$props['url']     = wp_get_attachment_url( $attachment_id );
		$props['alt']     = trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );

		// Large version.
		$src                 = wp_get_attachment_image_src( $attachment_id, 'full' );
		$props['full_src']   = $src[0];
		$props['full_src_w'] = $src[1];
		$props['full_src_h'] = $src[2];

		// Thumbnail version.
		$src                 = wp_get_attachment_image_src( $attachment_id, 'shop_thumbnail' );
		$props['thumb_src']   = $src[0];
		$props['thumb_src_w'] = $src[1];
		$props['thumb_src_h'] = $src[2];

		// Image source.
		$src             = wp_get_attachment_image_src( $attachment_id, 'shop_single' );
		$props['src']    = $src[0];
		$props['src_w']  = $src[1];
		$props['src_h']  = $src[2];
		$props['srcset'] = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $attachment_id, 'shop_single' ) : false;
		$props['sizes']  = function_exists( 'wp_get_attachment_image_sizes' ) ? wp_get_attachment_image_sizes( $attachment_id, 'shop_single' ) : false;

		// Alt text fallbacks
		$props['alt'] = empty( $props['alt'] ) ? $props['caption']                                               : $props['alt'];
		$props['alt'] = empty( $props['alt'] ) ? trim( strip_tags( $attachment->post_title ) )                   : $props['alt'];
		$props['alt'] = empty( $props['alt'] ) && $product ? trim( strip_tags( get_the_title( $product->ID ) ) ) : $props['alt'];
	}
	return $props;
}

/**
 * Get product visibility options.
 *
 * @since 3.0.0
 * @return array
 */
function wc_get_product_visibility_options() {
	return apply_filters( 'woocommerce_product_visibility_options', array(
		'visible' => __( 'Visible', 'woocommerce' ),
		'catalog' => __( 'Catalog', 'woocommerce' ),
		'search'  => __( 'Search', 'woocommerce' ),
		'hidden'  => __( 'Hidden', 'woocommerce' ),
	) );
}

/**
 * Get min/max price meta query args.
 *
 * @since 3.0.0
 * @param array $args Min price and max price arguments.
 * @return array
 */
function wc_get_min_max_price_meta_query( $args ) {
	$min = isset( $args['min_price'] ) ? floatval( $args['min_price'] ) : 0;
	$max = isset( $args['max_price'] ) ? floatval( $args['max_price'] ) : 9999999999;

	/**
	 * Adjust if the store taxes are not displayed how they are stored.
	 * Max is left alone because the filter was already increased.
	 * Kicks in when prices excluding tax are displayed including tax.
	 */
	if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
		$tax_classes = array_merge( array( '' ), WC_Tax::get_tax_classes() );
		$class_min   = $min;

		foreach ( $tax_classes as $tax_class ) {
			if ( $tax_rates = WC_Tax::get_rates( $tax_class ) ) {
				$class_min = $min - WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $min, $tax_rates ) );
			}
		}

		$min = $class_min;
	}

	return array(
		'key'     => '_price',
		'value'   => array( $min, $max ),
		'compare' => 'BETWEEN',
		'type'    => 'NUMERIC',
	);
}

/**
 * Get product tax class options.
 *
 * @since 3.0.0
 * @return array
 */
function wc_get_product_tax_class_options() {
	$tax_classes           = WC_Tax::get_tax_classes();
	$tax_class_options     = array();
	$tax_class_options[''] = __( 'Standard', 'woocommerce' );

	if ( ! empty( $tax_classes ) ) {
		foreach ( $tax_classes as $class ) {
			$tax_class_options[ sanitize_title( $class ) ] = $class;
		}
	}
	return $tax_class_options;
}

/**
 * Get stock status options.
 *
 * @since 3.0.0
 * @return array
 */
function wc_get_product_stock_status_options() {
	return array(
		'instock'    => __( 'In stock', 'woocommerce' ),
		'outofstock' => __( 'Out of stock', 'woocommerce' ),
	);
}

/**
 * Get backorder options.
 *
 * @since 3.0.0
 * @return array
 */
function wc_get_product_backorder_options() {
	return array(
		'no'     => __( 'Do not allow', 'woocommerce' ),
		'notify' => __( 'Allow, but notify customer', 'woocommerce' ),
		'yes'    => __( 'Allow', 'woocommerce' ),
	);
}

/**
 * Get related products based on product category and tags.
 *
 * @since  3.0.0
 * @param  int   $product_id  Product ID.
 * @param  int   $limit       Limit of results.
 * @param  array $exclude_ids Exclude IDs from the results.
 * @return array
 */
function wc_get_related_products( $product_id, $limit = 5, $exclude_ids = array() ) {
	global $wpdb;

	$product_id     = absint( $product_id );
	$exclude_ids    = array_merge( array( 0, $product_id ), $exclude_ids );
	$transient_name = 'wc_related_' . $product_id;
	$related_posts  = get_transient( $transient_name );
	$limit          = $limit > 0 ? $limit : 5;

	// We want to query related posts if they are not cached, or we don't have enough.
	if ( false === $related_posts || count( $related_posts ) < $limit ) {
		$cats_array = apply_filters( 'woocommerce_product_related_posts_relate_by_category', true, $product_id ) ? apply_filters( 'woocommerce_get_related_product_cat_terms', wc_get_product_term_ids( $product_id, 'product_cat' ), $product_id ) : array();
		$tags_array = apply_filters( 'woocommerce_product_related_posts_relate_by_tag', true, $product_id ) ? apply_filters( 'woocommerce_get_related_product_tag_terms', wc_get_product_term_ids( $product_id, 'product_tag' ), $product_id ) : array();

		// Don't bother if none are set, unless woocommerce_product_related_posts_force_display is set to true in which case all products are related.
		if ( empty( $cats_array ) && empty( $tags_array ) && ! apply_filters( 'woocommerce_product_related_posts_force_display', false, $product_id ) ) {
			$related_posts = array();
		} else {
			$data_store    = WC_Data_Store::load( 'product' );
			$related_posts = $data_store->get_related_products( $cats_array, $tags_array, $exclude_ids, $limit + 10, $product_id );
		}

		set_transient( $transient_name, $related_posts, DAY_IN_SECONDS );
	}

	shuffle( $related_posts );

	return array_slice( $related_posts, 0, $limit );
}

/**
 * Retrieves product term ids for a taxonomy.
 *
 * @since  3.0.0
 * @param  int    $product_id Product ID.
 * @param  string $taxonomy   Taxonomy slug.
 * @return array
 */
function wc_get_product_term_ids( $product_id, $taxonomy ) {
	$terms = get_the_terms( $product_id, $taxonomy );
	return ! empty( $terms ) ? wp_list_pluck( $terms, 'term_id' ) : array();
}

/**
 * For a given product, and optionally price/qty, work out the price with tax included, based on store settings.
 * @since  3.0.0
 * @param  WC_Product $product
 * @param  array $args
 * @return float
 */
function wc_get_price_including_tax( $product, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'qty'   => '',
		'price' => '',
	) );

	$price = '' !== $args['price'] ? max( 0.0, (float) $args['price'] ) : $product->get_price();
	$qty   = '' !== $args['qty'] ? max( 0.0, (float) $args['qty'] ) : 1;

	if ( '' === $price ) {
		return '';
	} elseif ( empty( $qty ) ) {
		return 0.0;
	}

	$line_price   = $price * $qty;
	$return_price = $line_price;

	if ( $product->is_taxable() ) {
		if ( ! wc_prices_include_tax() ) {
			$tax_rates    = WC_Tax::get_rates( $product->get_tax_class() );
			$taxes        = WC_Tax::calc_tax( $line_price, $tax_rates, false );
			$tax_amount   = WC_Tax::get_tax_total( $taxes );
			$return_price = round( $line_price + $tax_amount, wc_get_price_decimals() );
		} else {
			$tax_rates      = WC_Tax::get_rates( $product->get_tax_class() );
			$base_tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );

			/**
			 * If the customer is excempt from VAT, remove the taxes here.
			 * Either remove the base or the user taxes depending on woocommerce_adjust_non_base_location_prices setting.
			 */
			if ( ! empty( WC()->customer ) && WC()->customer->get_is_vat_exempt() ) {
				$remove_taxes = apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ? WC_Tax::calc_tax( $line_price, $base_tax_rates, true ) : WC_Tax::calc_tax( $line_price, $tax_rates, true );
				$remove_tax   = array_sum( $remove_taxes );
				$return_price = round( $line_price - $remove_tax, wc_get_price_decimals() );

			/**
			 * The woocommerce_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing with out of base locations.
			 * e.g. If a product costs 10 including tax, all users will pay 10 regardless of location and taxes.
			 * This feature is experimental @since 2.4.7 and may change in the future. Use at your risk.
			 */
			} elseif ( $tax_rates !== $base_tax_rates && apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
				$base_taxes   = WC_Tax::calc_tax( $line_price, $base_tax_rates, true );
				$modded_taxes = WC_Tax::calc_tax( $line_price - array_sum( $base_taxes ), $tax_rates, false );
				$return_price = round( $line_price - array_sum( $base_taxes ) + array_sum( $modded_taxes ), wc_get_price_decimals() );
			}
		}
	}
	return apply_filters( 'woocommerce_get_price_including_tax', $return_price, $qty, $product );
}

/**
 * For a given product, and optionally price/qty, work out the price with tax excluded, based on store settings.
 * @since  3.0.0
 * @param  WC_Product $product
 * @param  array $args
 * @return float
 */
function wc_get_price_excluding_tax( $product, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'qty'   => '',
		'price' => '',
	) );

	$price = '' !== $args['price'] ? max( 0.0, (float) $args['price'] ) : $product->get_price();
	$qty   = '' !== $args['qty'] ? max( 0.0, (float) $args['qty'] ) : 1;

	if ( '' === $price ) {
		return '';
	} elseif ( empty( $qty ) ) {
		return 0.0;
	}

	if ( $product->is_taxable() && wc_prices_include_tax() ) {
		$tax_rates  = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
		$taxes      = WC_Tax::calc_tax( $price * $qty, $tax_rates, true );
		$price      = WC_Tax::round( $price * $qty - array_sum( $taxes ) );
	} else {
		$price = $price * $qty;
	}

	return apply_filters( 'woocommerce_get_price_excluding_tax', $price, $qty, $product );
}

/**
 * Returns the price including or excluding tax, based on the 'woocommerce_tax_display_shop' setting.
 * @since  3.0.0
 * @param  WC_Product $product
 * @param  array $args
 * @return float
 */
function wc_get_price_to_display( $product, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'qty'   => 1,
		'price' => $product->get_price(),
	) );

	$price = $args['price'];
	$qty   = $args['qty'];

	return 'incl' === get_option( 'woocommerce_tax_display_shop' ) ? wc_get_price_including_tax( $product, array( 'qty' => $qty, 'price' => $price ) ) : wc_get_price_excluding_tax( $product, array( 'qty' => $qty, 'price' => $price ) );
}

/**
 * Returns the product categories in a list.
 *
 * @param int $product_id
 * @param string $sep (default: ', ').
 * @param string $before (default: '').
 * @param string $after (default: '').
 * @return string
 */
function wc_get_product_category_list( $product_id, $sep = ', ', $before = '', $after = '' ) {
	return get_the_term_list( $product_id, 'product_cat', $before, $sep, $after );
}

/**
 * Returns the product tags in a list.
 *
 * @param int $product_id
 * @param string $sep (default: ', ').
 * @param string $before (default: '').
 * @param string $after (default: '').
 * @return string
 */
function wc_get_product_tag_list( $product_id, $sep = ', ', $before = '', $after = '' ) {
	return get_the_term_list( $product_id, 'product_tag', $before, $sep, $after );
}

/**
 * Callback for array filter to get visible only.
 * @since  3.0.0
 * @param  WC_Product $product
 * @return bool
 */
function wc_products_array_filter_visible( $product ) {
	return $product && is_a( $product, 'WC_Product' ) && $product->is_visible();
}

/**
 * Callback for array filter to get products the user can edit only.
 *
 * @since  3.0.0
 * @param  WC_Product $product
 * @return bool
 */
function wc_products_array_filter_editable( $product ) {
	return $product && is_a( $product, 'WC_Product' ) && current_user_can( 'edit_product', $product->get_id() );
}

/**
 * Sort an array of products by a value.
 * @since  3.0.0
 * @param  array $products
 * @param  string $orderby
 * @return array
 */
function wc_products_array_orderby( $products, $orderby = 'date', $order = 'desc' ) {
	$orderby = strtolower( $orderby );
	$order   = strtolower( $order );
	switch ( $orderby ) {
		case 'title' :
		case 'id' :
		case 'date' :
		case 'modified' :
		case 'menu_order' :
		case 'price' :
			usort( $products, 'wc_products_array_orderby_' . $orderby );
			break;
		default :
			shuffle( $products );
			break;
	}
	if ( 'desc' === $order ) {
		$products = array_reverse( $products );
	}
	return $products;
}

/**
 * Sort by title.
 * @since  3.0.0
 * @param  WC_Product object $a
 * @param  WC_Product object $b
 * @return int
 */
function wc_products_array_orderby_title( $a, $b ) {
	return strcasecmp( $a->get_name(), $b->get_name() );
}

/**
 * Sort by id.
 * @since  3.0.0
 * @param  WC_Product object $a
 * @param  WC_Product object $b
 * @return int
 */
function wc_products_array_orderby_id( $a, $b ) {
	if ( $a->get_id() === $b->get_id() ) {
		return 0;
	}
	return ( $a->get_id() < $b->get_id() ) ? -1 : 1;
}

/**
 * Sort by date.
 * @since  3.0.0
 * @param  WC_Product object $a
 * @param  WC_Product object $b
 * @return int
 */
function wc_products_array_orderby_date( $a, $b ) {
	if ( $a->get_date_created() === $b->get_date_created() ) {
		return 0;
	}
	return ( $a->get_date_created() < $b->get_date_created() ) ? -1 : 1;
}

/**
 * Sort by modified.
 * @since  3.0.0
 * @param  WC_Product object $a
 * @param  WC_Product object $b
 * @return int
 */
function wc_products_array_orderby_modified( $a, $b ) {
	if ( $a->get_date_modified() === $b->get_date_modified() ) {
		return 0;
	}
	return ( $a->get_date_modified() < $b->get_date_modified() ) ? -1 : 1;
}

/**
 * Sort by menu order.
 * @since  3.0.0
 * @param  WC_Product object $a
 * @param  WC_Product object $b
 * @return int
 */
function wc_products_array_orderby_menu_order( $a, $b ) {
	if ( $a->get_menu_order() === $b->get_menu_order() ) {
		return 0;
	}
	return ( $a->get_menu_order() < $b->get_menu_order() ) ? -1 : 1;
}

/**
 * Sort by price low to high.
 * @since  3.0.0
 * @param  WC_Product object $a
 * @param  WC_Product object $b
 * @return int
 */
function wc_products_array_orderby_price( $a, $b ) {
	if ( $a->get_price() === $b->get_price() ) {
		return 0;
	}
	return ( $a->get_price() < $b->get_price() ) ? -1 : 1;
}

/**
 * Queue a product for syncing at the end of the request.
 *
 * @param  int $product_id
 */
function wc_deferred_product_sync( $product_id ) {
	global $wc_deferred_product_sync;

	if ( empty( $wc_deferred_product_sync ) ) {
		$wc_deferred_product_sync = array();
	}

	$wc_deferred_product_sync[] = $product_id;
}
