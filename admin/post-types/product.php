<?php
/**
 * Admin functions for the shop_product post type
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Products
 * @version     1.6.4
 */


/**
 * Duplicate a product link on products list
 *
 * @access public
 * @param mixed $actions
 * @param mixed $post
 * @return array
 */
function woocommerce_duplicate_product_link_row($actions, $post) {

	if ( function_exists( 'duplicate_post_plugin_activation' ) )
		return $actions;

	if ( ! current_user_can( 'manage_woocommerce' ) ) return $actions;

	if ( $post->post_type != 'product' )
		return $actions;

	$actions['duplicate'] = '<a href="' . wp_nonce_url( admin_url( 'admin.php?action=duplicate_product&amp;post=' . $post->ID ), 'woocommerce-duplicate-product_' . $post->ID ) . '" title="' . __("Make a duplicate from this product", 'woocommerce')
		. '" rel="permalink">' .  __("Duplicate", 'woocommerce') . '</a>';

	return $actions;
}

add_filter( 'post_row_actions', 'woocommerce_duplicate_product_link_row',10,2 );
add_filter( 'page_row_actions', 'woocommerce_duplicate_product_link_row',10,2 );


/**
 *  Duplicate a product link on edit screen
 *
 * @access public
 * @return void
 */
function woocommerce_duplicate_product_post_button() {
	global $post;

	if (function_exists('duplicate_post_plugin_activation')) return;

	if (!current_user_can('manage_woocommerce')) return;

	if( !is_object( $post ) ) return;

	if ($post->post_type!='product') return;

	if ( isset( $_GET['post'] ) ) :
		$notifyUrl = wp_nonce_url( admin_url( "admin.php?action=duplicate_product&post=" . $_GET['post'] ), 'woocommerce-duplicate-product_' . $_GET['post'] );
		?>
		<div id="duplicate-action"><a class="submitduplicate duplication" href="<?php echo esc_url( $notifyUrl ); ?>"><?php _e('Copy to a new draft', 'woocommerce'); ?></a></div>
		<?php
	endif;
}

add_action( 'post_submitbox_start', 'woocommerce_duplicate_product_post_button' );


/**
 * Columns for Products page
 *
 * @access public
 * @param mixed $columns
 * @return array
 */
function woocommerce_edit_product_columns($columns){
	global $woocommerce;

	$columns = array();

	$columns["cb"] = "<input type=\"checkbox\" />";
	$columns["thumb"] = __("Image", 'woocommerce');

	$columns["name"] = __("Name", 'woocommerce');

	if (get_option('woocommerce_enable_sku', true) == 'yes')
		$columns["sku"] = __("SKU", 'woocommerce');

	if (get_option('woocommerce_manage_stock')=='yes')
		$columns["is_in_stock"] = __("Stock", 'woocommerce');

	$columns["price"] = __("Price", 'woocommerce');

	$columns["product_cat"] = __("Categories", 'woocommerce');
	$columns["product_tag"] = __("Tags", 'woocommerce');
	$columns["featured"] = '<img src="' . $woocommerce->plugin_url() . '/assets/images/featured.png" alt="' . __("Featured", 'woocommerce') . '" class="tips" data-tip="' . __("Featured", 'woocommerce') . '" width="12" height="12" />';
	$columns["product_type"] = '<img src="' . $woocommerce->plugin_url() . '/assets/images/product_type_head.png" alt="' . __("Type", 'woocommerce') . '" class="tips" data-tip="' . __("Type", 'woocommerce') . '" width="14" height="12" />';
	$columns["date"] = __("Date", 'woocommerce');

	return $columns;
}

add_filter('manage_edit-product_columns', 'woocommerce_edit_product_columns');


/**
 * Custom Columns for Products page
 *
 * @access public
 * @param mixed $column
 * @return void
 */
function woocommerce_custom_product_columns( $column ) {
	global $post, $woocommerce;
	$product = new WC_Product($post->ID);

	switch ($column) {
		case "thumb" :
			echo $product->get_image();
		break;
		case "name" :
			$edit_link = get_edit_post_link( $post->ID );
			$title = _draft_or_post_title();
			$post_type_object = get_post_type_object( $post->post_type );
			$can_edit_post = current_user_can( $post_type_object->cap->edit_post, $post->ID );

			echo '<strong><a class="row-title" href="'.$edit_link.'">' . $title.'</a>';

			_post_states( $post );

			echo '</strong>';

			if ( $post->post_parent > 0 )
				echo '&nbsp;&nbsp;&larr; <a href="'. get_edit_post_link($post->post_parent) .'">'. get_the_title($post->post_parent) .'</a>';

			// Excerpt view
			if (isset($_GET['mode']) && $_GET['mode']=='excerpt') echo apply_filters('the_excerpt', $post->post_excerpt);

			// Get actions
			$actions = array();

			$actions['id'] = 'ID: ' . $post->ID;

			if ( $can_edit_post && 'trash' != $post->post_status ) {
				$actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="' . esc_attr( __( 'Edit this item inline', 'woocommerce' ) ) . '">' . __( 'Quick&nbsp;Edit', 'woocommerce' ) . '</a>';
			}
			if ( current_user_can( $post_type_object->cap->delete_post, $post->ID ) ) {
				if ( 'trash' == $post->post_status )
					$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash', 'woocommerce' ) ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-' . $post->post_type . '_' . $post->ID ) . "'>" . __( 'Restore', 'woocommerce' ) . "</a>";
				elseif ( EMPTY_TRASH_DAYS )
					$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash', 'woocommerce' ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash', 'woocommerce' ) . "</a>";
				if ( 'trash' == $post->post_status || !EMPTY_TRASH_DAYS )
					$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently', 'woocommerce' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently', 'woocommerce' ) . "</a>";
			}
			if ( $post_type_object->public ) {
				if ( in_array( $post->post_status, array( 'pending', 'draft' ) ) ) {
					if ( $can_edit_post )
						$actions['view'] = '<a href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;', 'woocommerce' ), $title ) ) . '" rel="permalink">' . __( 'Preview', 'woocommerce' ) . '</a>';
				} elseif ( 'trash' != $post->post_status ) {
					$actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'woocommerce' ), $title ) ) . '" rel="permalink">' . __( 'View', 'woocommerce' ) . '</a>';
				}
			}
			$actions = apply_filters( 'post_row_actions', $actions, $post );

			echo '<div class="row-actions">';

			$i = 0;
			$action_count = sizeof($actions);

			foreach ( $actions as $action => $link ) {
				++$i;
				( $i == $action_count ) ? $sep = '' : $sep = ' | ';
				echo "<span class='$action'>$link$sep</span>";
			}
			echo '</div>';

			get_inline_data( $post );

			/* Custom inline data for woocommerce */
			echo '
				<div class="hidden" id="woocommerce_inline_' . $post->ID . '">
					<div class="menu_order">' . $post->menu_order . '</div>
					<div class="sku">' . $product->sku . '</div>
					<div class="regular_price">' . $product->regular_price . '</div>
					<div class="sale_price">' . $product->sale_price . '</div>
					<div class="weight">' . $product->weight . '</div>
					<div class="length">' . $product->length . '</div>
					<div class="width">' . $product->width . '</div>
					<div class="height">' . $product->height . '</div>
					<div class="visibility">' . $product->visibility . '</div>
					<div class="stock_status">' . $product->stock_status . '</div>
					<div class="stock">' . $product->stock . '</div>
					<div class="manage_stock">' . $product->manage_stock . '</div>
					<div class="featured">' . $product->featured . '</div>
					<div class="product_type">' . $product->product_type . '</div>
					<div class="product_is_virtual">' . $product->virtual . '</div>
				</div>
			';

		break;
		case "sku" :
			if ($product->get_sku()) echo $product->get_sku(); else echo '<span class="na">&ndash;</span>';
		break;
		case "product_type" :
			if( $product->product_type == 'grouped' ):
				echo '<span class="product-type tips '.$product->product_type.'" data-tip="' . __('Grouped', 'woocommerce') . '"></span>';
			elseif ( $product->product_type == 'external' ):
				echo '<span class="product-type tips '.$product->product_type.'" data-tip="' . __('External/Affiliate', 'woocommerce') . '"></span>';
			elseif ( $product->product_type == 'simple' ):

				if ($product->is_virtual()) {
					echo '<span class="product-type tips virtual" data-tip="' . __('Virtual', 'woocommerce') . '"></span>';
				} elseif ($product->is_downloadable()) {
					echo '<span class="product-type tips downloadable" data-tip="' . __('Downloadable', 'woocommerce') . '"></span>';
				} else {
					echo '<span class="product-type tips '.$product->product_type.'" data-tip="' . __('Simple', 'woocommerce') . '"></span>';
				}

			elseif ( $product->product_type == 'variable' ):
				echo '<span class="product-type tips '.$product->product_type.'" data-tip="' . __('Variable', 'woocommerce') . '"></span>';
			else:
				// Assuming that we have other types in future
				echo '<span class="product-type tips '.$product->product_type.'" data-tip="' . ucwords($product->product_type) . '"></span>';
			endif;
		break;
		case "price":
			if ($product->get_price_html()) echo $product->get_price_html(); else echo '<span class="na">&ndash;</span>';
		break;
		case "product_cat" :
		case "product_tag" :
			if ( ! $terms = get_the_terms( $post->ID, $column ) ) {
				echo '<span class="na">&ndash;</span>';
			} else {
				foreach ( $terms as $term ) {
					$termlist[] = '<a href="' . admin_url( 'edit.php?' . $column . '=' . $term->slug . '&post_type=product' ) . ' ">' . $term->name . '</a>';
				}

				echo implode( ', ', $termlist );
			}
		break;
		case "featured" :
			$url = wp_nonce_url( admin_url('admin-ajax.php?action=woocommerce-feature-product&product_id=' . $post->ID), 'woocommerce-feature-product' );
			echo '<a href="'.$url.'" title="'.__('Change', 'woocommerce') .'">';
			if ($product->is_featured()) echo '<a href="'.$url.'"><img src="'.$woocommerce->plugin_url().'/assets/images/featured.png" alt="yes" height="14" width="14" />';
			else echo '<img src="'.$woocommerce->plugin_url().'/assets/images/featured-off.png" alt="no" height="14" width="14" />';
			echo '</a>';
		break;
		case "is_in_stock" :

			if ($product->is_in_stock()) {
				echo '<mark class="instock">' . __('In stock', 'woocommerce') . '</mark>';
			} else {
				echo '<mark class="outofstock">' . __('Out of stock', 'woocommerce') . '</mark>';
			}

			if ( $product->managing_stock() ) :
				echo ' &times; ' . $product->get_total_stock();
			endif;

		break;
	}
}

add_action('manage_product_posts_custom_column', 'woocommerce_custom_product_columns', 2 );


/**
 * Make product columns sortable
 * https://gist.github.com/906872
 **/

/**
 * Make product columns sortable
 *
 * https://gist.github.com/906872
 *
 * @access public
 * @param mixed $columns
 * @return array
 */
function woocommerce_custom_product_sort($columns) {
	$custom = array(
		'is_in_stock' 	=> 'inventory',
		'price'			=> 'price',
		'featured'		=> 'featured',
		'sku'			=> 'sku',
		'name'			=> 'title'
	);
	return wp_parse_args( $custom, $columns );
}

add_filter( 'manage_edit-product_sortable_columns', 'woocommerce_custom_product_sort');


/**
 * Product column orderby
 *
 * http://scribu.net/wordpress/custom-sortable-columns.html#comment-4732
 *
 * @access public
 * @param mixed $vars
 * @return array
 */
function woocommerce_custom_product_orderby( $vars ) {
	if (isset( $vars['orderby'] )) :
		if ( 'inventory' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> '_stock',
				'orderby' 	=> 'meta_value_num'
			) );
		endif;
		if ( 'price' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> '_price',
				'orderby' 	=> 'meta_value_num'
			) );
		endif;
		if ( 'featured' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> '_featured',
				'orderby' 	=> 'meta_value'
			) );
		endif;
		if ( 'sku' == $vars['orderby'] ) :
			$vars = array_merge( $vars, array(
				'meta_key' 	=> '_sku',
				'orderby' 	=> 'meta_value'
			) );
		endif;
	endif;

	return $vars;
}

add_filter( 'request', 'woocommerce_custom_product_orderby' );


/**
 * Filter products by category, uses slugs for option values.
 *
 * Code adapted by Andrew Benbow
 *
 * @access public
 * @return void
 */
function woocommerce_products_by_category() {
	global $typenow, $wp_query;
    if ($typenow=='product') :
    	woocommerce_product_dropdown_categories();
    endif;
}

add_action('restrict_manage_posts','woocommerce_products_by_category');


/**
 * Filter products by type
 *
 * @access public
 * @return void
 */
function woocommerce_products_by_type() {
    global $typenow, $wp_query;
    if ($typenow=='product') :

    	// Types
		$terms = get_terms('product_type');
		$output = "<select name='product_type' id='dropdown_product_type'>";
		$output .= '<option value="">'.__('Show all product types', 'woocommerce').'</option>';
		foreach($terms as $term) :
			$output .="<option value='$term->slug' ";
			if ( isset( $wp_query->query['product_type'] ) ) $output .=selected($term->slug, $wp_query->query['product_type'], false);
			$output .=">";

				// Its was dynamic but did not support the translations
				if( $term->name == 'grouped' ):
					$output .= __('Grouped product', 'woocommerce');
				elseif ( $term->name == 'external' ):
					$output .= __('External/Affiliate product', 'woocommerce');
				elseif ( $term->name == 'simple' ):
					$output .= __('Simple product', 'woocommerce');
				elseif ( $term->name == 'variable' ):
					$output .= __('Variable', 'woocommerce');
				else:
					// Assuming that we have other types in future
					$output .= ucwords($term->name);
				endif;

			$output .=" ($term->count)</option>";
		endforeach;
		$output .="</select>";

		// Downloadable/virtual
		$output .= "<select name='product_subtype' id='dropdown_product_subtype'>";
		$output .= '<option value="">'.__('Show all sub-types', 'woocommerce').'</option>';

		$output .="<option value='downloadable' ";
		if ( isset( $_GET['product_subtype'] ) ) $output .= selected('downloadable', $_GET['product_subtype'], false);
		$output .=">".__('Downloadable', 'woocommerce')."</option>";

		$output .="<option value='virtual' ";
		if ( isset( $_GET['product_subtype'] ) ) $output .= selected('virtual', $_GET['product_subtype'], false);
		$output .=">".__('Virtual', 'woocommerce')."</option>";

		$output .="</select>";

		echo $output;
    endif;
}

add_action('restrict_manage_posts', 'woocommerce_products_by_type');


/**
 * Filter the products in admin based on options
 *
 * @access public
 * @param mixed $query
 * @return void
 */
function woocommerce_admin_product_filter_query( $query ) {
	global $typenow, $wp_query;

    if ( $typenow == 'product' ) {

    	// Subtypes
    	if ( ! empty( $_GET['product_subtype'] ) ) {
	    	if ( $_GET['product_subtype'] == 'downloadable' ) {
	        	$query->query_vars['meta_value'] 	= 'yes';
	        	$query->query_vars['meta_key'] 		= '_downloadable';
	        } elseif ( $_GET['product_subtype'] == 'virtual' ) {
	        	$query->query_vars['meta_value'] 	= 'yes';
	        	$query->query_vars['meta_key'] 		= '_virtual';
	        }
        }

        // Categories
        if ( isset( $_GET['product_cat'] ) && $_GET['product_cat'] == '0' ) {

        	$query->query_vars['tax_query'][] = array(
        		'taxonomy' => 'product_cat',
        		'field' => 'id',
				'terms' => get_terms( 'product_cat', array( 'fields' => 'ids' ) ),
				'operator' => 'NOT IN'
        	);

        }

	}

}

add_filter( 'parse_query', 'woocommerce_admin_product_filter_query' );


/**
 * Search by SKU or ID for products. Adapted from code by BenIrvin (Admin Search by ID)
 *
 * @access public
 * @param mixed $wp
 * @return void
 */
function woocommerce_admin_product_search( $wp ) {
    global $pagenow, $wpdb;

	if( 'edit.php' != $pagenow ) return;
	if( !isset( $wp->query_vars['s'] ) ) return;
	if ($wp->query_vars['post_type']!='product') return;

	if( '#' == substr( $wp->query_vars['s'], 0, 1 ) ) :

		$id = absint( substr( $wp->query_vars['s'], 1 ) );

		if( !$id ) return;

		unset( $wp->query_vars['s'] );
		$wp->query_vars['p'] = $id;

	elseif( 'SKU:' == substr( $wp->query_vars['s'], 0, 4 ) ) :

		$sku = trim( substr( $wp->query_vars['s'], 4 ) );

		if( !$sku ) return;

		$id = $wpdb->get_var('SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key="_sku" AND meta_value LIKE "%'.$sku.'%";');

		if( !$id ) return;

		unset( $wp->query_vars['s'] );
		$wp->query_vars['p'] = $id;
		$wp->query_vars['sku'] = $sku;

	endif;
}


/**
 * Label for the search by ID/SKU feature
 *
 * @access public
 * @param mixed $query
 * @return void
 */
function woocommerce_admin_product_search_label($query) {
	global $pagenow, $typenow, $wp;

    if ( 'edit.php' != $pagenow ) return $query;
    if ( $typenow!='product' ) return $query;

	$s = get_query_var( 's' );
	if ($s) return $query;

	$sku = get_query_var( 'sku' );
	if($sku) {
		$post_type = get_post_type_object($wp->query_vars['post_type']);
		return sprintf(__("[%s with SKU of %s]", 'woocommerce'), $post_type->labels->singular_name, $sku);
	}

	$p = get_query_var( 'p' );
	if ($p) {
		$post_type = get_post_type_object($wp->query_vars['post_type']);
		return sprintf(__("[%s with ID of %d]", 'woocommerce'), $post_type->labels->singular_name, $p);
	}

	return $query;
}

if ( is_admin() ) {
	add_action( 'parse_request', 'woocommerce_admin_product_search' );
	add_filter( 'get_search_query', 'woocommerce_admin_product_search_label' );
}


/**
 * Custom quick edit - form
 *
 * @access public
 * @param mixed $column_name
 * @param mixed $post_type
 * @return void
 */
function woocommerce_admin_product_quick_edit( $column_name, $post_type ) {
	if ($column_name != 'price' || $post_type != 'product') return;
	?>
    <fieldset class="inline-edit-col-left">
		<div id="woocommerce-fields" class="inline-edit-col">

			<h4><?php _e('Product Data', 'woocommerce'); ?></h4>

			<?php if( get_option('woocommerce_enable_sku', true) !== 'no' ) : ?>

				<label>
				    <span class="title"><?php _e('SKU', 'woocommerce'); ?></span>
				    <span class="input-text-wrap">
						<input type="text" name="_sku" class="text sku" value="">
					</span>
				</label>
				<br class="clear" />

			<?php endif; ?>

			<div class="price_fields">
				<label>
				    <span class="title"><?php _e('Price', 'woocommerce'); ?></span>
				    <span class="input-text-wrap">
						<input type="text" name="_regular_price" class="text regular_price" placeholder="<?php _e('Regular price', 'woocommerce'); ?>" value="">
					</span>
				</label>
				<br class="clear" />
				<label>
				    <span class="title"><?php _e('Sale', 'woocommerce'); ?></span>
				    <span class="input-text-wrap">
						<input type="text" name="_sale_price" class="text sale_price" placeholder="<?php _e('Sale price', 'woocommerce'); ?>" value="">
					</span>
				</label>
				<br class="clear" />
			</div>

			<?php if ( get_option('woocommerce_enable_weight') == "yes" || get_option('woocommerce_enable_dimensions') == "yes" ) : ?>
			<div class="dimension_fields">

				<?php if ( get_option('woocommerce_enable_weight') == "yes" ) : ?>
					<label>
					    <span class="title"><?php _e('Weight', 'woocommerce'); ?></span>
					    <span class="input-text-wrap">
							<input type="text" name="_weight" class="text weight" placeholder="0.00" value="">
						</span>
					</label>
					<br class="clear" />
				<?php endif; ?>

				<?php if ( get_option('woocommerce_enable_dimensions') == "yes" ) : ?>
					<div class="inline-edit-group dimensions">
						<div>
						    <span class="title"><?php _e('L/W/H', 'woocommerce'); ?></span>
						    <span class="input-text-wrap">
								<input type="text" name="_length" class="text length" placeholder="<?php _e('Length', 'woocommerce'); ?>" value="">
								<input type="text" name="_width" class="text width" placeholder="<?php _e('Width', 'woocommerce'); ?>" value="">
								<input type="text" name="_height" class="text height" placeholder="<?php _e('Height', 'woocommerce'); ?>" value="">
							</span>
						</div>
					</div>
				<?php endif; ?>

			</div>
			<?php endif; ?>

			<label class="alignleft">
			    <span class="title"><?php _e('Visibility', 'woocommerce'); ?></span>
			    <span class="input-text-wrap">
			    	<select class="visibility" name="_visibility">
					<?php
						$options = array(
							'visible' => __('Catalog &amp; search', 'woocommerce'),
							'catalog' => __('Catalog', 'woocommerce'),
							'search' => __('Search', 'woocommerce'),
							'hidden' => __('Hidden', 'woocommerce')
						);
						foreach ($options as $key => $value) {
							echo '<option value="'.$key.'">'. $value .'</option>';
						}
					?>
					</select>
				</span>
			</label>
			<label class="alignleft featured">
				<input type="checkbox" name="_featured" value="1">
				<span class="checkbox-title"><?php _e('Featured', 'woocommerce'); ?></span>
			</label>
			<br class="clear" />
			<label class="alignleft">
			    <span class="title"><?php _e('In stock?', 'woocommerce'); ?></span>
			    <span class="input-text-wrap">
			    	<select class="stock_status" name="_stock_status">
					<?php
						$options = array(
							'instock' => __('In stock', 'woocommerce'),
							'outofstock' => __('Out of stock', 'woocommerce')
						);
						foreach ($options as $key => $value) {
							echo '<option value="'.$key.'">'. $value .'</option>';
						}
					?>
					</select>
				</span>
			</label>

			<div class="stock_fields">

				<?php if (get_option('woocommerce_manage_stock')=='yes') : ?>
					<label class="alignleft manage_stock">
						<input type="checkbox" name="_manage_stock" value="1">
						<span class="checkbox-title"><?php _e('Manage stock?', 'woocommerce'); ?></span>
					</label>
					<br class="clear" />
					<label class="stock_qty_field">
					    <span class="title"><?php _e('Stock Qty', 'woocommerce'); ?></span>
					    <span class="input-text-wrap">
							<input type="text" name="_stock" class="text stock" value="">
						</span>
					</label>
				<?php endif; ?>

			</div>

			<input type="hidden" name="woocommerce_quick_edit_nonce" value="<?php echo wp_create_nonce( 'woocommerce_quick_edit_nonce' ); ?>" />
		</div>
	</fieldset>
	<?php
}

add_action( 'quick_edit_custom_box',  'woocommerce_admin_product_quick_edit', 10, 2 );


/**
 * Custom quick edit - script
 *
 * @access public
 * @param mixed $hook
 * @return void
 */
function woocommerce_admin_product_quick_edit_scripts( $hook ) {
	global $woocommerce, $post_type;

	if ( $hook == 'edit.php' && $post_type == 'product' )
    	wp_enqueue_script( 'woocommerce_quick-edit', $woocommerce->plugin_url() . '/assets/js/admin/quick-edit.js', array('jquery') );
}

add_action( 'admin_enqueue_scripts', 'woocommerce_admin_product_quick_edit_scripts', 10 );


/**
 * Custom quick edit - save
 *
 * @access public
 * @param mixed $post_id
 * @param mixed $post
 * @return void
 */
function woocommerce_admin_product_quick_edit_save( $post_id, $post ) {

	if ( !$_POST ) return $post_id;
	if ( is_int( wp_is_post_revision( $post_id ) ) ) return;
	if( is_int( wp_is_post_autosave( $post_id ) ) ) return;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
	if ( !isset($_POST['woocommerce_quick_edit_nonce']) || (isset($_POST['woocommerce_quick_edit_nonce']) && !wp_verify_nonce( $_POST['woocommerce_quick_edit_nonce'], 'woocommerce_quick_edit_nonce' ))) return $post_id;
	if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
	if ( $post->post_type != 'product' ) return $post_id;

	global $woocommerce, $wpdb;

	$product = new WC_Product( $post_id );

	// Save fields
	if(isset($_POST['_sku'])) update_post_meta($post_id, '_sku', esc_html(stripslashes($_POST['_sku'])));
	if(isset($_POST['_weight'])) update_post_meta($post_id, '_weight', esc_html(stripslashes($_POST['_weight'])));
	if(isset($_POST['_length'])) update_post_meta($post_id, '_length', esc_html(stripslashes($_POST['_length'])));
	if(isset($_POST['_width'])) update_post_meta($post_id, '_width', esc_html(stripslashes($_POST['_width'])));
	if(isset($_POST['_height'])) update_post_meta($post_id, '_height', esc_html(stripslashes($_POST['_height'])));
	if(isset($_POST['_stock_status'])) update_post_meta( $post_id, '_stock_status', stripslashes( $_POST['_stock_status'] ) );
	if(isset($_POST['_visibility'])) update_post_meta( $post_id, '_visibility', stripslashes( $_POST['_visibility'] ) );
	if(isset($_POST['_featured'])) update_post_meta( $post_id, '_featured', 'yes' ); else update_post_meta( $post_id, '_featured', 'no' );

	if ($product->is_type('simple') || $product->is_type('external')) {

		if(isset($_POST['_regular_price'])) update_post_meta( $post_id, '_regular_price', stripslashes( $_POST['_regular_price'] ) );
		if(isset($_POST['_sale_price'])) update_post_meta( $post_id, '_sale_price', stripslashes( $_POST['_sale_price'] ) );

		// Handle price - remove dates and set to lowest
		$price_changed = false;

		if(isset($_POST['_regular_price']) && stripslashes( $_POST['_regular_price'] )!=$product->regular_price) $price_changed = true;
		if(isset($_POST['_sale_price']) && stripslashes( $_POST['_sale_price'] )!=$product->sale_price) $price_changed = true;

		if ($price_changed) {
			update_post_meta( $post_id, '_sale_price_dates_from', '');
			update_post_meta( $post_id, '_sale_price_dates_to', '');

			if ($_POST['_sale_price'] != '') {
				update_post_meta( $post_id, '_price', stripslashes($_POST['_sale_price']) );
			} else {
				update_post_meta( $post_id, '_price', stripslashes($_POST['_regular_price']) );
			}
		}
	}

	// Handle stock
	if (!$product->is_type('grouped')) {
		if (isset($_POST['_manage_stock'])) {
			update_post_meta( $post_id, '_manage_stock', 'yes' );
			update_post_meta( $post_id, '_stock', (int) $_POST['_stock'] );
		} else {
			update_post_meta( $post_id, '_manage_stock', 'no' );
			update_post_meta( $post_id, '_stock', '0' );
		}
	}

	// Clear transient
	$woocommerce->clear_product_transients( $post_id );
}

add_action( 'save_post', 'woocommerce_admin_product_quick_edit_save', 10, 2 );


/**
 * Custom bulk edit - form
 *
 * @access public
 * @param mixed $column_name
 * @param mixed $post_type
 * @return void
 */
function woocommerce_admin_product_bulk_edit( $column_name, $post_type ) {
	if ($column_name != 'price' || $post_type != 'product') return;
	?>
    <fieldset class="inline-edit-col-right">
		<div id="woocommerce-fields-bulk" class="inline-edit-col">

			<h4><?php _e('Product Data', 'woocommerce'); ?></h4>

			<div class="inline-edit-group">
				<label class="alignleft">
					<span class="title"><?php _e('Price', 'woocommerce'); ?></span>
				    <span class="input-text-wrap">
				    	<select class="change_regular_price change_to" name="change_regular_price">
						<?php
							$options = array(
								'' 	=> __('— No Change —', 'woocommerce'),
								'1' => __('Change to:', 'woocommerce')
							);
							foreach ($options as $key => $value) {
								echo '<option value="'.$key.'">'. $value .'</option>';
							}
						?>
						</select>
					</span>
				</label>
			    <label class="alignright">
			    	<input type="text" name="_regular_price" class="text regular_price" placeholder="<?php _e('Regular price', 'woocommerce'); ?>" value="">
			    </label>
			</div>

			<div class="inline-edit-group">
				<label class="alignleft">
				    <span class="title"><?php _e('Sale', 'woocommerce'); ?></span>
				    <span class="input-text-wrap">
				    	<select class="change_sale_price change_to" name="change_sale_price">
						<?php
							$options = array(
								'' 	=> __('— No Change —', 'woocommerce'),
								'1' => __('Change to:', 'woocommerce')
							);
							foreach ($options as $key => $value) {
								echo '<option value="'.$key.'">'. $value .'</option>';
							}
						?>
						</select>
					</span>
				</label>
				<label class="alignright">
					<input type="text" name="_sale_price" class="text sale_price" placeholder="<?php _e('Sale price', 'woocommerce'); ?>" value="">
				</label>
			</div>

			<?php if ( get_option('woocommerce_enable_weight') == "yes" ) : ?>
				<div class="inline-edit-group">
					<label class="alignleft">
					    <span class="title"><?php _e('Weight', 'woocommerce'); ?></span>
					    <span class="input-text-wrap">
					    	<select class="change_weight change_to" name="change_weight">
							<?php
								$options = array(
									'' 	=> __('— No Change —', 'woocommerce'),
									'1' => __('Change to:', 'woocommerce')
								);
								foreach ($options as $key => $value) {
									echo '<option value="'.$key.'">'. $value .'</option>';
								}
							?>
							</select>
						</span>
					</label>
					<label class="alignright">
						<input type="text" name="_weight" class="text weight" placeholder="0.00" value="">
					</label>
				</div>
			<?php endif; ?>

			<?php if ( get_option('woocommerce_enable_dimensions') == "yes" ) : ?>
				<div class="inline-edit-group dimensions">
					<label class="alignleft">
					    <span class="title"><?php _e('L/W/H', 'woocommerce'); ?></span>
					    <span class="input-text-wrap">
					    	<select class="change_dimensions change_to" name="change_dimensions">
							<?php
								$options = array(
									'' 	=> __('— No Change —', 'woocommerce'),
									'1' => __('Change to:', 'woocommerce')
								);
								foreach ($options as $key => $value) {
									echo '<option value="'.$key.'">'. $value .'</option>';
								}
							?>
							</select>
						</span>
					</label>
					<div class="alignright">
						<input type="text" name="_length" class="text length" placeholder="<?php _e('Length', 'woocommerce'); ?>" value="">
						<input type="text" name="_width" class="text width" placeholder="<?php _e('Width', 'woocommerce'); ?>" value="">
						<input type="text" name="_height" class="text height" placeholder="<?php _e('Height', 'woocommerce'); ?>" value="">
					</div>
				</div>
			<?php endif; ?>

			<label>
			    <span class="title"><?php _e('Visibility', 'woocommerce'); ?></span>
			    <span class="input-text-wrap">
			    	<select class="visibility" name="_visibility">
					<?php
						$options = array(
							'' => __('— No Change —', 'woocommerce'),
							'visible' => __('Catalog &amp; search', 'woocommerce'),
							'catalog' => __('Catalog', 'woocommerce'),
							'search' => __('Search', 'woocommerce'),
							'hidden' => __('Hidden', 'woocommerce')
						);
						foreach ($options as $key => $value) {
							echo '<option value="'.$key.'">'. $value .'</option>';
						}
					?>
					</select>
				</span>
			</label>
			<label>
			    <span class="title"><?php _e('Featured', 'woocommerce'); ?></span>
			    <span class="input-text-wrap">
			    	<select class="featured" name="_featured">
					<?php
						$options = array(
							'' => __('— No Change —', 'woocommerce'),
							'yes' => __('Yes', 'woocommerce'),
							'no' => __('No', 'woocommerce')
						);
						foreach ($options as $key => $value) {
							echo '<option value="'.$key.'">'. $value .'</option>';
						}
					?>
					</select>
				</span>
			</label>

			<label>
			    <span class="title"><?php _e('In stock?', 'woocommerce'); ?></span>
			    <span class="input-text-wrap">
			    	<select class="stock_status" name="_stock_status">
					<?php
						$options = array(
							'' => __('— No Change —', 'woocommerce'),
							'instock' => __('In stock', 'woocommerce'),
							'outofstock' => __('Out of stock', 'woocommerce')
						);
						foreach ($options as $key => $value) {
							echo '<option value="'.$key.'">'. $value .'</option>';
						}
					?>
					</select>
				</span>
			</label>
			<?php if (get_option('woocommerce_manage_stock')=='yes') : ?>
				<label>
				    <span class="title"><?php _e('Manage stock?', 'woocommerce'); ?></span>
				    <span class="input-text-wrap">
				    	<select class="manage_stock" name="_manage_stock">
						<?php
							$options = array(
								'' => __('— No Change —', 'woocommerce'),
								'yes' => __('Yes', 'woocommerce'),
								'no' => __('No', 'woocommerce')
							);
							foreach ($options as $key => $value) {
								echo '<option value="'.$key.'">'. $value .'</option>';
							}
						?>
						</select>
					</span>
				</label>

				<div class="inline-edit-group dimensions">
					<label class="alignleft stock_qty_field">
					    <span class="title"><?php _e('Stock Qty', 'woocommerce'); ?></span>
					    <span class="input-text-wrap">
					    	<select class="change_stock change_to" name="change_stock">
							<?php
								$options = array(
									'' 	=> __('— No Change —', 'woocommerce'),
									'1' => __('Change to:', 'woocommerce')
								);
								foreach ($options as $key => $value) {
									echo '<option value="'.$key.'">'. $value .'</option>';
								}
							?>
							</select>
						</span>
					</label>
					<label class="alignright">
						<input type="text" name="_stock" class="text stock" placeholder="<?php _e('Stock Qty', 'woocommerce'); ?>" value="">
					</label>
				</div>
			<?php endif; ?>
			<input type="hidden" name="woocommerce_bulk_edit_nonce" value="<?php echo wp_create_nonce( 'woocommerce_bulk_edit_nonce' ); ?>" />
		</div>
	</fieldset>
	<?php
}

add_action('bulk_edit_custom_box',  'woocommerce_admin_product_bulk_edit', 10, 2);


/**
 * Custom bulk edit - save
 *
 * @access public
 * @param mixed $post_id
 * @param mixed $post
 * @return void
 */
function woocommerce_admin_product_bulk_edit_save( $post_id, $post ) {

	if ( is_int( wp_is_post_revision( $post_id ) ) ) return;
	if( is_int( wp_is_post_autosave( $post_id ) ) ) return;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
	if ( !isset($_REQUEST['woocommerce_bulk_edit_nonce']) || (isset($_REQUEST['woocommerce_bulk_edit_nonce']) && !wp_verify_nonce( $_REQUEST['woocommerce_bulk_edit_nonce'], 'woocommerce_bulk_edit_nonce' ))) return $post_id;
	if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
	if ( $post->post_type != 'product' ) return $post_id;

	global $woocommerce, $wpdb;

	$product = new WC_Product( $post_id );

	// Save fields
	if (isset($_REQUEST['change_weight']) && $_REQUEST['change_weight']==1)
		if(isset($_REQUEST['_weight'])) update_post_meta($post_id, '_weight', esc_html(stripslashes($_REQUEST['_weight'])));

	if (isset($_REQUEST['change_dimensions']) && $_REQUEST['change_dimensions']==1) {
		if(isset($_REQUEST['_length'])) update_post_meta($post_id, '_length', esc_html(stripslashes($_REQUEST['_length'])));
		if(isset($_REQUEST['_width'])) update_post_meta($post_id, '_width', esc_html(stripslashes($_REQUEST['_width'])));
		if(isset($_REQUEST['_height'])) update_post_meta($post_id, '_height', esc_html(stripslashes($_REQUEST['_height'])));
	}

	if(isset($_REQUEST['_stock_status']) && $_REQUEST['_stock_status']) update_post_meta( $post_id, '_stock_status', stripslashes( $_REQUEST['_stock_status'] ) );

	if(isset($_REQUEST['_visibility']) && $_REQUEST['_visibility']) update_post_meta( $post_id, '_visibility', stripslashes( $_REQUEST['_visibility'] ) );

	if(isset($_REQUEST['_featured']) && $_REQUEST['_featured']) update_post_meta( $post_id, '_featured', stripslashes( $_REQUEST['_featured'] ) );

	// Handle price - remove dates and set to lowest
	if ($product->is_type('simple') || $product->is_type('external')) {

		$price_changed = false;

		if (isset($_REQUEST['change_regular_price']) && $_REQUEST['change_regular_price']==1) {

			if(isset($_REQUEST['_regular_price'])) update_post_meta( $post_id, '_regular_price', stripslashes( $_REQUEST['_regular_price'] ) );

			if(isset($_REQUEST['_regular_price']) && stripslashes( $_REQUEST['_regular_price'] )!=$product->regular_price) {
				$price_changed = true;
				$product->regular_price = stripslashes( $_REQUEST['_regular_price'] );
			}

		}

		if (isset($_REQUEST['change_sale_price']) && $_REQUEST['change_sale_price']==1) {

			if(isset($_REQUEST['_sale_price'])) update_post_meta( $post_id, '_sale_price', stripslashes( $_REQUEST['_sale_price'] ) );

			if(isset($_REQUEST['_sale_price']) && stripslashes( $_REQUEST['_sale_price'] )!=$product->sale_price) {
				$price_changed = true;
				$product->sale_price = stripslashes( $_REQUEST['_sale_price'] );
			}

		}

		if ($price_changed) {
			update_post_meta( $post_id, '_sale_price_dates_from', '');
			update_post_meta( $post_id, '_sale_price_dates_to', '');

			if ($product->sale_price) {
				update_post_meta( $post_id, '_price', $product->sale_price );
			} else {
				update_post_meta( $post_id, '_price', $product->regular_price );
			}
		}
	}

	// Handle stock
	if (!$product->is_type('grouped')) {
		if (isset($_REQUEST['_manage_stock']) && $_REQUEST['_manage_stock']) {

			if ($_REQUEST['_manage_stock']=='yes') {
				update_post_meta( $post_id, '_manage_stock', 'yes' );

				if (isset($_REQUEST['change_stock']) && $_REQUEST['change_stock']==1) update_post_meta( $post_id, '_stock', (int) $_REQUEST['_stock'] );
			} else {
				update_post_meta( $post_id, '_manage_stock', 'no' );
				update_post_meta( $post_id, '_stock', '0' );
			}

		}
	}

	// Clear transient
	$woocommerce->clear_product_transients( $post_id );
}

add_action('save_post', 'woocommerce_admin_product_bulk_edit_save', 10, 2);


/**
 * Product sorting link
 *
 * Based on Simple Page Ordering by 10up (http://wordpress.org/extend/plugins/simple-page-ordering/)
 *
 * @access public
 * @param mixed $views
 * @return void
 */
function woocommerce_default_sorting_link( $views ) {
	global $post_type, $wp_query;

	if ( ! current_user_can('edit_others_pages') ) return $views;
	$class = ( isset( $wp_query->query['orderby'] ) && $wp_query->query['orderby'] == 'menu_order title' ) ? 'current' : '';
	$query_string = remove_query_arg(array( 'orderby', 'order' ));
	$query_string = add_query_arg( 'orderby', urlencode('menu_order title'), $query_string );
	$query_string = add_query_arg( 'order', urlencode('ASC'), $query_string );
	$views['byorder'] = '<a href="'. $query_string . '" class="' . $class . '">' . __('Sort Products', 'woocommerce') . '</a>';

	return $views;
}

add_filter( 'views_edit-product', 'woocommerce_default_sorting_link' );