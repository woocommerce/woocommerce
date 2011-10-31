<?php
/**
 * Product Class
 * 
 * The WooCommerce product class handles individual product data.
 *
 * @class woocommerce_product
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce_product {
	
	var $id;
	var $product_custom_fields;
	var $exists;
	var $attributes;
	var $children;
	var $post;
	var $sku;
	var $price;
	var $visibility;
	var $stock;
	var $stock_status;
	var $backorders;
	var $manage_stock;
	var $sale_price;
	var $regular_price;
	var $weight;
	var $length;
	var $width;
	var $height;
	var $tax_status;
	var $tax_class;
	var $upsell_ids;
	var $crosssell_ids;
	var $product_type;
	var $total_stock;
	var $sale_price_dates_from;
	var $sale_price_dates_to;
	var $min_variation_price;
	var $max_variation_price;
	
	/**
	 * Loads all product data from custom fields
	 *
	 * @param   int		$id		ID of the product to load
	 */
	function woocommerce_product( $id ) {
		
		$this->id = (int) $id;

		$this->product_custom_fields = get_post_custom( $this->id );
		
		$this->exists = (sizeof($this->product_custom_fields)>0) ? true : false;

		// Define the data we're going to load: Key => Default value
		$load_data = array(
			'sku'			=> $this->id,
			'price' 		=> '',
			'visibility'	=> 'hidden',
			'stock'			=> 0,
			'stock_status'	=> 'instock',
			'backorders'	=> 'no',
			'manage_stock'	=> 'no',
			'sale_price'	=> '',
			'regular_price' => '',
			'weight'		=> '',
			'length'		=> '',
			'width'		=> '',
			'height'		=> '',
			'tax_status'	=> 'taxable',
			'tax_class'		=> '',
			'upsell_ids'	=> array(),
			'crosssell_ids' => array(),
			'sale_price_dates_from' => '',
			'sale_price_dates_to' 	=> '',
			'min_variation_price'	=> '',
			'max_variation_price'	=> ''
		);
		
		// Load the data from the custom fields
		foreach ($load_data as $key => $default) :
			$this->$key = (isset($this->product_custom_fields[$key][0]) && $this->product_custom_fields[$key][0]!=='') ? $this->product_custom_fields[$key][0] : $default;
		endforeach;
		
		// Load serialised data, unserialise twice to fix WP bug
		if (isset($this->product_custom_fields['product_attributes'][0])) $this->attributes = maybe_unserialize( maybe_unserialize( $this->product_custom_fields['product_attributes'][0] )); else $this->attributes = array();		
						
		// Get product type
		$terms = wp_get_object_terms( $id, 'product_type', array('fields' => 'names') );
		$this->product_type = (isset($terms[0])) ? sanitize_title($terms[0]) : 'simple';
		
		// total_stock (stock of parent and children combined)
		$this->total_stock = $this->stock;
		if (sizeof($this->get_children())>0) foreach ($this->get_children() as $child) :
			if (isset($child->product->variation_has_stock)) :
				if ($child->product->variation_has_stock) :
					$this->total_stock += $child->product->stock;
				endif;
			else :
				$this->total_stock += $child->product->stock;
			endif;
		endforeach;
		
		// Check sale
		$this->check_sale_price();
	}
	
	/**
     * Get SKU (Stock-keeping unit) - product uniqe ID
     * 
     * @return mixed
     */
    function get_sku() {
        return $this->sku;
    }
    
    
	/** Returns the product's children */
	function get_children() {
		
		if (!is_array($this->children)) :
		
			$this->children = array();
			
			if ($this->is_type('variable') || $this->is_type('grouped')) :
			
				$child_post_type = ($this->is_type('variable')) ? 'product_variation' : 'product';
			
				if ( $children_products =& get_children( 'post_parent='.$this->id.'&post_type='.$child_post_type.'&orderby=menu_order&order=ASC' ) ) :
	
					if ($children_products) foreach ($children_products as $child) :
						
						if ($this->is_type('variable')) :
							$child->product = &new woocommerce_product_variation( $child->ID, $this->id, $this->product_custom_fields );
						else :
							$child->product = &new woocommerce_product( $child->ID );
						endif;
						
					endforeach;
					$this->children = (array) $children_products;
				endif;
				
			endif;
			
		endif;
		
		return (array) $this->children;
	}

	/**
	 * Reduce stock level of the product
	 *
	 * @param   int		$by		Amount to reduce by
	 */
	function reduce_stock( $by = 1 ) {
		if ($this->managing_stock()) :
			$this->stock = $this->stock - $by;
			$this->total_stock = $this->total_stock - $by;
			update_post_meta($this->id, 'stock', $this->stock);
			
			// Out of stock attribute
			if (!$this->is_in_stock()) update_post_meta($this->id, 'stock_status', 'outofstock');
			
			return $this->stock;
		endif;
	}
	
	/**
	 * Increase stock level of the product
	 *
	 * @param   int		$by		Amount to increase by
	 */
	function increase_stock( $by = 1 ) {
		if ($this->managing_stock()) :
			$this->stock = $this->stock + $by;
			$this->total_stock = $this->total_stock + $by;
			update_post_meta($this->id, 'stock', $this->stock);
			
			// Out of stock attribute
			if ($this->is_in_stock()) update_post_meta($this->id, 'stock_status', 'instock');
			
			return $this->stock;
		endif;
	}
	
	/**
	 * Checks the product type
	 *
	 * @param   string		$type		Type to check against
	 */
	function is_type( $type ) {
		if (is_array($type) && in_array($this->product_type, $type)) return true;
		elseif ($this->product_type==$type) return true;
		return false;
	}
	
	/** Returns whether or not the product has any child product */
	function has_child() {
		return sizeof($this->get_children()) ? true : false;
	}
	
	/** Returns whether or not the product post exists */
	function exists() {
		if ($this->exists) return true;
		return false;
	}
	
	/** Returns whether or not the product is taxable */
	function is_taxable() {
		if ($this->tax_status=='taxable') return true;
		return false;
	}
	
	/** Returns whether or not the product shipping is taxable */
	function is_shipping_taxable() {
		if ($this->tax_status=='taxable' || $this->tax_status=='shipping') return true;
		return false;
	}
	
	/** Get the product's post data */
	function get_post_data() {
		if (empty($this->post)) :
			$this->post = get_post( $this->id );
		endif;
		
		return $this->post;
	}
	
	/** Get the title of the post */
	function get_title() {
		$this->get_post_data();
		return apply_filters('woocommerce_product_title', get_the_title($this->post->ID), $this);
	}

	
	/** Get the add to url */
	function add_to_cart_url() {
		global $woocommerce;
		
		if ($this->is_type('variable')) :
			$url = add_query_arg('add-to-cart', 'variation');
			$url = add_query_arg('product', $this->id, $url);
		elseif ( $this->has_child() ) :
			$url = add_query_arg('add-to-cart', 'group');
			$url = add_query_arg('product', $this->id, $url);
		else :
			$url = add_query_arg('add-to-cart', $this->id);
		endif;
		
		$url = $woocommerce->nonce_url( 'add_to_cart', $url );
		return $url;
	}
	
	/** Returns whether or not the product is stock managed */
	function managing_stock() {
		if (get_option('woocommerce_manage_stock')=='yes') :
			if (isset($this->manage_stock) && $this->manage_stock=='yes') return true;
		endif;
		return false;
	}
	
	/** Returns whether or not the product is in stock */
	function is_in_stock() {
		if ($this->managing_stock()) :
			if (!$this->backorders_allowed()) :
				if ($this->total_stock==0 || $this->total_stock<0) :
					return false;
				else :
					if ($this->stock_status=='instock') return true;
					return false;
				endif;
			else :
				if ($this->stock_status=='instock') return true;
				return false;
			endif;
		endif;
		if ($this->stock_status=='instock') return true;
		return false;
	}
	
	/** Returns whether or not the product can be backordered */
	function backorders_allowed() {
		if ($this->backorders=='yes' || $this->backorders=='notify') return true;
		return false;
	}
	
	/** Returns whether or not the product needs to notify the customer on backorder */
	function backorders_require_notification() {
		if ($this->backorders=='notify') return true;
		return false;
	}
	
	/**
     * Returns number of items available for sale.
     * 
     * @return int
     */
    function get_stock_quantity() {
        return (int)$this->stock;
    }

	/** Returns whether or not the product has enough stock for the order */
	function has_enough_stock( $quantity ) {
		
		if (!$this->managing_stock()) return true;

		if ($this->backorders_allowed()) return true;
		
		if ($this->stock >= $quantity) :
			return true;
		endif;
		
		return false;
		
	}
	
	/** Returns the availability of the product */
	function get_availability() {
	
		$availability = "";
		$class = "";
		
		if (!$this->managing_stock()) :
			if ($this->is_in_stock()) :
				//$availability = __('In stock', 'woothemes'); /* Lets not bother showing stock if its not managed and is available */
			else :
				$availability = __('Out of stock', 'woothemes');
				$class = 'out-of-stock';
			endif;
		else :
			if ($this->is_in_stock()) :
				if ($this->total_stock > 0) :
					$availability = __('In stock', 'woothemes');
					
					if ($this->backorders_allowed()) :
						if ($this->backorders_require_notification()) :
							$availability .= ' &ndash; '.$this->stock.' ';
							$availability .= __('available', 'woothemes');
							$availability .= __(' (backorders allowed)', 'woothemes');
						endif;
					else :
						$availability .= ' &ndash; '.$this->stock.' ';
						$availability .= __('available', 'woothemes');
					endif;
					
				else :
					
					if ($this->backorders_allowed()) :
						if ($this->backorders_require_notification()) :
							$availability = __('Available on backorder', 'woothemes');
						else :
							$availability = __('In stock', 'woothemes');
						endif;
					else :
						$availability = __('Out of stock', 'woothemes');
						$class = 'out-of-stock';
					endif;
					
				endif;
			else :
				if ($this->backorders_allowed()) :
					$availability = __('Available on backorder', 'woothemes');
				else :
					$availability = __('Out of stock', 'woothemes');
					$class = 'out-of-stock';
				endif;
			endif;
		endif;
		
		return array( 'availability' => $availability, 'class' => $class);
	}
	
	/** Returns whether or not the product is featured */
	function is_featured() {
		if (get_post_meta($this->id, 'featured', true)=='yes') return true;
		return false;
	}
	
	/** Returns whether or not the product is visible */
	function is_visible() {
	
		// Out of stock visibility
		if (get_option('woocommerce_hide_out_of_stock_items')=='yes') :
			if (!$this->is_in_stock()) return false;
		endif;
		
		// visibility setting
		if ($this->visibility=='hidden') return false;
		if ($this->visibility=='visible') return true;
		if ($this->visibility=='search' && is_search()) return true;
		if ($this->visibility=='search' && !is_search()) return false;
		if ($this->visibility=='catalog' && is_search()) return false;
		if ($this->visibility=='catalog' && !is_search()) return true;
	}
	
	/** Returns whether or not the product is on sale */
	function is_on_sale() {
		if ( $this->has_child() ) :
			
			foreach ($this->get_children() as $child) :
				if ( $child->product->sale_price==$child->product->price ) return true;
			endforeach;
			
		else :
		
			if ( $this->sale_price && $this->sale_price==$this->price ) return true;
		
		endif;
		return false;
	}
	
	/** Returns the product's weight */
	function get_weight() {
		if ($this->weight) return $this->weight;
	}
	
	/** Returns the product's price */
	function get_price() {
		return $this->price;
	}
	
	/** Returns the price (excluding tax) - ignores tax_class filters since the price may *include* tax and thus needs subtracting */
	function get_price_excluding_tax() {
		
		$price = $this->price;
			
		if (get_option('woocommerce_prices_include_tax')=='yes') :
		
			if ( $rate = $this->get_tax_base_rate() ) :
				
				if ( $rate>0 ) :
					
					$_tax = &new woocommerce_tax();

					$tax_amount = $_tax->calc_tax( $price, $rate, true );
					
					$price = $price - $tax_amount;
					
					// Round
					$price = round( $price * 100 ) / 100;

					// Format
					$price = number_format($price, 2, '.', '');
					
				endif;
				
			endif;
		
		endif;
		
		return $price;
	}
	
	/** Returns the tax class */
	function get_tax_class() {
		return apply_filters('woocommerce_product_tax_class', $this->tax_class, $this);
	}
	
	/** Returns the base tax rate */
	function get_tax_base_rate() {
		
		if ( $this->is_taxable() && get_option('woocommerce_calc_taxes')=='yes') :
			
			$_tax = &new woocommerce_tax();
			$rate = $_tax->get_shop_base_rate( $this->tax_class ); // Get tax class directly - ignore filters
			
			return $rate;
			
		endif;
		
	}
	
	/** Returns the price in html format */
	function get_price_html() {
		$price = '';
		if ($this->is_type('grouped')) :
			
			$min_price = '';
			$max_price = '';
			
			foreach ($this->get_children() as $child) :
				$child_price = $child->product->get_price();
				if ($child_price<$min_price || $min_price == '') $min_price = $child_price;
				if ($child_price>$max_price || $max_price == '') $max_price = $child_price;
			endforeach;
			
			$price .= '<span class="from">' . __('From:', 'woothemes') . ' </span>' . woocommerce_price($min_price);	
			
			$price = apply_filters('woocommerce_grouped_price_html', $price, $this);
				
		elseif ($this->is_type('variable')) :
			
			if ( !$this->min_variation_price || $this->min_variation_price !== $this->max_variation_price ) $price .= '<span class="from">' . __('From:', 'woothemes') . ' </span>';
			
			$price .= woocommerce_price($this->get_price());
			
			$price = apply_filters('woocommerce_variable_price_html', $price, $this);
			
		else :
			if ($this->price) :
				if ($this->is_on_sale() && isset($this->regular_price)) :
				
					$price .= '<del>'.woocommerce_price( $this->regular_price ).'</del> <ins>'.woocommerce_price($this->get_price()).'</ins>';
					
					$price = apply_filters('woocommerce_sale_price_html', $price, $this);
					
				else :
				
					$price .= woocommerce_price($this->get_price());
					
					$price = apply_filters('woocommerce_price_html', $price, $this);
					
				endif;
			elseif ($this->price === '' ) :
				return false;
			elseif ($this->price === '0' ) :
			
				$price = __('Free!', 'woothemes');  
				
				$price = apply_filters('woocommerce_free_price_html', $price, $this);
				
			endif;
		endif;
		
		return $price;
	}
	
	/** Returns the product rating in html format - ratings are stored in transient cache */
	function get_rating_html( $location = '' ) {
		
		if ($location) $location = '_'.$location;
		$star_size = apply_filters('woocommerce_star_rating_size'.$location, 16);

		if ( false === ( $average_rating = get_transient( $this->id . '_woocommerce_average_rating' ) ) ) :
		
			global $wpdb;

			$count = $wpdb->get_var("
				SELECT COUNT(meta_value) FROM $wpdb->commentmeta 
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID = $this->id
				AND comment_approved = '1'
				AND meta_value > 0
			");
			
			$ratings = $wpdb->get_var("
				SELECT SUM(meta_value) FROM $wpdb->commentmeta 
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID = $this->id
				AND comment_approved = '1'
			");
		
			if ( $count>0 ) :
				$average_rating = number_format($ratings / $count, 2);
			else :
				$average_rating = '';
			endif;
			
			set_transient( $this->id . '_woocommerce_average_rating', $average_rating );
		
		endif;

		if ( $average_rating>0 ) :
			return '<div class="star-rating" title="'.sprintf(__('Rated %s out of 5', 'woothemes'), $average_rating).'"><span style="width:'.($average_rating*$star_size).'px"><span class="rating">'.$average_rating.'</span> '.__('out of 5', 'woothemes').'</span></div>';
		else :
			return '';
		endif;
	}
	
	/** Returns the upsell product ids */
	function get_upsells() {
		return (array) maybe_unserialize( $this->upsell_ids );
	}
	
	/** Returns the crosssell product ids */
	function get_cross_sells() {
		return (array) maybe_unserialize( $this->crosssell_ids );
	}
	
	/** Returns the product categories */
	function get_categories( $sep = ', ', $before = '', $after = '' ) {
		return get_the_term_list($this->id, 'product_cat', $before, $sep, $after);
	}
	
	/** Returns the product tags */
	function get_tags( $sep = ', ', $before = '', $after = '' ) {
		return get_the_term_list($this->id, 'product_tag', $before, $sep, $after);
	}
	
	/** Get and return related products */
	function get_related( $limit = 5 ) {
		global $wpdb, $all_post_ids;
		// Related products are found from category and tag
		$tags_array = array(0);
		$cats_array = array(0);
		$tags = '';
		$cats = '';
		
		// Get tags
		$terms = wp_get_post_terms($this->id, 'product_tag');
		foreach ($terms as $term) {
			$tags_array[] = $term->term_id;
		}
		$tags = implode(',', $tags_array);
		
		$terms = wp_get_post_terms($this->id, 'product_cat');
		foreach ($terms as $term) {
			$cats_array[] = $term->term_id;
		}
		$cats = implode(',', $cats_array);

		$q = "
			SELECT p.ID
			FROM $wpdb->term_taxonomy AS tt, $wpdb->term_relationships AS tr, $wpdb->posts AS p, $wpdb->postmeta AS pm
			WHERE 
				p.ID != $this->id
				AND p.post_status = 'publish'
				AND p.post_date_gmt < NOW()
				AND p.post_type = 'product'
				AND pm.meta_key = 'visibility'
				AND pm.meta_value IN ('visible', 'catalog')
				AND pm.post_id = p.ID
				AND
				(
					(
						tt.taxonomy ='product_cat'
						AND tt.term_taxonomy_id = tr.term_taxonomy_id
						AND tr.object_id  = p.ID
						AND tt.term_id IN ($cats)
					)
					OR 
					(
						tt.taxonomy ='product_tag'
						AND tt.term_taxonomy_id = tr.term_taxonomy_id
						AND tr.object_id  = p.ID
						AND tt.term_id IN ($tags)
					)
				)
			GROUP BY tr.object_id
			ORDER BY RAND()
			LIMIT $limit;";
 
		$related = $wpdb->get_col($q);
		
		return $related;
	}
	
	/** Returns product attributes */
	function get_attributes() {
		return $this->attributes;
	}
	
	/** Returns whether or not the product has any attributes set */
	function has_attributes() {
		if (isset($this->attributes) && sizeof($this->attributes)>0) :
			foreach ($this->attributes as $attribute) :
				if ($attribute['is_visible']) return true;
			endforeach;
		endif;
		return false;
	}
	
	/** Lists a table of attributes for the product page */
	function list_attributes() {
		global $woocommerce;
		
		$attributes = $this->get_attributes();
		if ($attributes && sizeof($attributes)>0) :
			
			echo '<table cellspacing="0" class="shop_attributes">';
			$alt = 1;
			foreach ($attributes as $attribute) :
				if (!$attribute['is_visible']) continue;
				
				$alt = $alt*-1;
				echo '<tr class="';
				if ($alt==1) echo 'alt';
				echo '"><th>'.$woocommerce->attribute_label( $attribute['name'] ).'</th><td>';
				
				if ($attribute['is_taxonomy']) :
					$post_terms = wp_get_post_terms( $this->id, $attribute['name'] );
					$values = array();
					foreach ($post_terms as $term) :
						$values[] = $term->name;
					endforeach;
					echo implode(', ', $values);
				else :
					// Convert pipes to commas
					$value = explode('|', $attribute['value']);
					$value = implode(', ', $value);
					echo wpautop(wptexturize($value));
				endif;
				
				echo '</td></tr>';
			endforeach;
			echo '</table>';

		endif;
	}
	
	/**
     * Return an array of attributes used for variations, as well as their possible values
     * 
     * @return two dimensional array of attributes and their available values
     */   
    function get_available_attribute_variations() {      

        if (!$this->is_type('variable') || !$this->has_child()) return array();
        
        $attributes = $this->get_attributes();
        
        if(!is_array($attributes)) return array();
        
        $available_attributes = array();
        $children = $this->get_children();
        
        foreach ($attributes as $attribute) {
            if (!$attribute['is_variation']) continue;

            $values = array();
            $attribute_field_name = 'attribute_'.sanitize_title($attribute['name']);

            foreach ($children as $child) {
                /* @var $variation woocommerce_product_variation */
                $variation = $child->product;

                if ($variation instanceof woocommerce_product_variation) {
                	
                	if (get_post_status( $variation->get_variation_id() ) != 'publish') continue; // Disabled
                	
                    $vattributes = $variation->get_variation_attributes();

                    if (is_array($vattributes)) {
                        foreach ($vattributes as $name => $value) {
                            if ($name == $attribute_field_name) {
                                $values[] = $value;
                            }
                        }
                    }
                }
            }
            
            // empty value indicates that all options for given attribute are available
            if(in_array('', $values)) {
            	
            	// Get all options
            	if ($attribute['is_taxonomy']) :
	            	$options = array();
	            	$post_terms = wp_get_post_terms( $this->id, $attribute['name'] );
					foreach ($post_terms as $term) :
						$options[] = $term->slug;
					endforeach;
				else :
					$options = explode('|', $attribute['value']);
				endif;
				
				$options = array_map('trim', $options);
                
                $values = $options;
            }
            
            $available_attributes[$attribute['name']] = array_unique($values);
        }
        
        return $available_attributes;
    }
    
    /**
     * Checks sale data to see if the product is due to go on sale/sale has expired, and updates the main price
     */  
    function check_sale_price() {

    	if ($this->sale_price_dates_from && $this->sale_price_dates_from < strtotime('NOW')) :
    		
    		if ($this->sale_price && $this->price!==$this->sale_price) :
    			
    			$this->price = $this->sale_price;
    			update_post_meta($this->id, 'price', $this->price);
    			
    			// Grouped product prices and sale status are affected by children
    			$this->grouped_product_sync();
    			
    		endif;

    	endif;
    	
    	if ($this->sale_price_dates_to && $this->sale_price_dates_to < strtotime('NOW')) :
    		
    		if ($this->regular_price && $this->price!==$this->regular_price) :
    			
    			$this->price = $this->regular_price;
    			update_post_meta($this->id, 'price', $this->price);
		
				// Sale has expired - clear the schedule boxes
				update_post_meta($this->id, 'sale_price', '');
				update_post_meta($this->id, 'sale_price_dates_from', '');
				update_post_meta($this->id, 'sale_price_dates_to', '');
			
				// Grouped product prices and sale status are affected by children
    			$this->grouped_product_sync();
			
			endif;
    		
    	endif;
    }
    
    /**
	 * Sync grouped products with the childs lowest price (so they can be sorted by price accurately)
	 **/
	function grouped_product_sync() {
		
		global $wpdb;
		$post_parent = $wpdb->get_var("SELECT post_parent FROM $wpdb->posts WHERE ID = $this->id;");
		
		if (!$post_parent) return;
		
		$children_by_price = get_posts( array(
			'post_parent' 	=> $post_parent,
			'orderby' 	=> 'meta_value_num',
			'order'		=> 'asc',
			'meta_key'	=> 'price',
			'posts_per_page' => 1,
			'post_type' 	=> 'product',
			'fields' 		=> 'ids'
		));
		if ($children_by_price) :
			foreach ($children_by_price as $child) :
				$child_price = get_post_meta($child, 'price', true);
				update_post_meta( $post_parent, 'price', $child_price );
			endforeach;
		endif;
	}

}