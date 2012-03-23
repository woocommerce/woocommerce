<?php
/**
 * Product Variation Class
 * 
 * The WooCommerce product variation class handles product variation data.
 *
 * @class 		WC_Product_Variation
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class WC_Product_Variation extends WC_Product {
	
	var $variation_data;
	var $variation_id;
	var $variation_has_length;
	var $variation_has_width;
	var $variation_has_height;
	var $variation_has_weight;
	var $variation_has_price;
	var $variation_has_sale_price;
	var $variation_has_stock;
	var $variation_has_sku;
	var $variation_shipping_class;
	
	/**
	 * Loads all product data from custom fields
	 *
	 * @param   int		$id		ID of the product to load
	 */
	function __construct( $variation_id, $parent_id = '', $parent_custom_fields = '' ) {
		
		$this->variation_id = $variation_id;
	
		$product_custom_fields = get_post_custom( $this->variation_id );
		
		$this->exists = ( sizeof( $product_custom_fields) > 0 ) ? true : false;
		
		$this->variation_data = array();
		
		foreach ( $product_custom_fields as $name => $value ) :
			
			if ( ! strstr( $name, 'attribute_' ) ) continue;
			
			$this->variation_data[$name] = $value[0];
			
		endforeach;

		/* Get main product data from parent */
		$this->id = ($parent_id>0) ? $parent_id : wp_get_post_parent_id( $this->variation_id );
		if (!$parent_custom_fields) $parent_custom_fields = get_post_custom( $this->id );
		
		// Define the data we're going to load from the parent: Key => Default value
		$load_data = array(
			'sku'			=> '',
			'price' 		=> 0,
			'visibility'	=> 'hidden',
			'stock'			=> 0,
			'stock_status'	=> 'instock',
			'backorders'	=> 'no',
			'manage_stock'	=> 'no',
			'sale_price'	=> '',
			'regular_price' => '',
			'weight'		=> '',
			'length'		=> '',
			'width'			=> '',
			'height'		=> '',
			'tax_status'	=> 'taxable',
			'tax_class'		=> '',
			'upsell_ids'	=> array(),
			'crosssell_ids' => array()
		);
		
		// Load the data from the custom fields
		foreach ( $load_data as $key => $default ) 
			$this->$key = ( isset( $parent_custom_fields['_' . $key][0] ) && $parent_custom_fields['_' . $key][0] !== '' ) ? $parent_custom_fields['_' . $key][0] : $default;

		$this->product_type = 'variable';
		$this->visibility 	= 'visible';
		
		$this->variation_has_sku = $this->variation_has_stock = $this->variation_has_weight = $this->variation_has_length = $this->variation_has_width = $this->variation_has_height = $this->variation_has_price = $this->variation_has_sale_price = false;
				
		/* Override parent data with variation */
		if ( isset( $product_custom_fields['_sku'][0] ) && ! empty( $product_custom_fields['_sku'][0] ) ) {
			$this->variation_has_sku = true;
			$this->sku = $product_custom_fields['_sku'][0];
		}
		
		if ( isset( $product_custom_fields['_stock'][0] ) && $product_custom_fields['_stock'][0] !== '' ) {
			$this->variation_has_stock = true;
			$this->manage_stock = 'yes';
			$this->stock = $product_custom_fields['_stock'][0];
		}
		
		if ( isset( $product_custom_fields['_weight'][0] ) && $product_custom_fields['_weight'][0] !== '' ) {
			$this->variation_has_weight = true;
			$this->weight = $product_custom_fields['_weight'][0];
		}
		
		if ( isset( $product_custom_fields['_length'][0] ) && $product_custom_fields['_length'][0] !== '' ) {
			$this->variation_has_length = true;
			$this->length = $product_custom_fields['_length'][0];
		}
		
		if ( isset( $product_custom_fields['_width'][0] ) && $product_custom_fields['_width'][0] !== '' ) {
			$this->variation_has_width = true;
			$this->width = $product_custom_fields['_width'][0];
		}
		
		if ( isset( $product_custom_fields['_height'][0] ) && $product_custom_fields['_height'][0] !== '' ) {
			$this->variation_has_height = true;
			$this->height = $product_custom_fields['_height'][0];
		}
		
		if ( isset( $product_custom_fields['_price'][0] ) && $product_custom_fields['_price'][0] !== '' ) {
			$this->variation_has_price = true;
			$this->price = $product_custom_fields['_price'][0];
			$this->regular_price = $product_custom_fields['_price'][0];
		}
		
		if ( isset( $product_custom_fields['_sale_price'][0] ) && $product_custom_fields['_sale_price'][0] !== '' ) {
			$this->variation_has_sale_price = true;
			$this->sale_price = $product_custom_fields['_sale_price'][0];
			if ($this->sale_price < $this->price) $this->price = $this->sale_price;
		}
		
		if ( isset( $product_custom_fields['_downloadable'][0] ) && $product_custom_fields['_downloadable'][0] == 'yes' ) {
			$this->downloadable = 'yes';
		} else {
			$this->downloadable = 'no';
		}
		
		if ( isset( $product_custom_fields['_virtual'][0] ) && $product_custom_fields['_virtual'][0] == 'yes' ) {
			$this->virtual = 'yes';
		} else {
			$this->virtual = 'no';
		}
		
		$this->total_stock = $this->stock;
	}
	
	/**
     * Get variation ID
     * 
     * @return int
     */
    function get_variation_id() {
        return (int) $this->variation_id;
    }
    
    /**
     * Get variation attribute values
     * 
     * @return array of attributes and their values for this variation
     */
    function get_variation_attributes() {
        return $this->variation_data;
    }
	
	/**
     * Get variation attribute values
     * 
     * @return string containing the formatted price
     */
	function get_price_html() {
		if ($this->variation_has_price || $this->variation_has_sale_price) :
			$price = '';
			
			if ($this->price!=='') :
				if ($this->variation_has_sale_price) :
					$price .= '<del>'.woocommerce_price( $this->regular_price ).'</del> <ins>'.woocommerce_price( $this->sale_price ).'</ins>';
					$price = apply_filters('woocommerce_variation_sale_price_html', $price, $this);
				else :
					$price .= woocommerce_price( $this->price );
					$price = apply_filters('woocommerce_variation_price_html', $price, $this);
				endif;
			endif;
	
			return $price;
		else :
			return woocommerce_price(parent::get_price());
		endif;
	}
	
	/**
     * Gets the main product image
     */ 
    function get_image( $size = 'shop_thumbnail' ) {
    	global $woocommerce;
    	
    	if ($this->variation_id && has_post_thumbnail($this->variation_id)) :
			echo get_the_post_thumbnail($this->variation_id, $size); 
		elseif (has_post_thumbnail($this->id)) :
			echo get_the_post_thumbnail($this->id, $size); 
		elseif ($parent_id = wp_get_post_parent_id( $this->id ) && has_post_thumbnail($parent_id)) :
			echo get_the_post_thumbnail($parent_id, $size); 
		else :
			echo '<img src="'. woocommerce_placeholder_img_src() . '" alt="Placeholder" width="'.$woocommerce->get_image_size('shop_thumbnail_image_width').'" height="'.$woocommerce->get_image_size('shop_thumbnail_image_height').'" />'; 
		endif;
    }
	
	/**
	 * Reduce stock level of the product
	 *
	 * @param   int		$by		Amount to reduce by
	 */
	function reduce_stock( $by = 1 ) {
		global $woocommerce;
		
		if ( $this->variation_has_stock ) {
			if ( $this->managing_stock() ) {
				
				$this->stock = $this->stock - $by;
				$this->total_stock = $this->get_total_stock() - $by;
				update_post_meta( $this->variation_id, '_stock', $this->stock );
				
				// Parents out of stock attribute
				if ( ! $this->is_in_stock() ) {
				
					// Check parent
					$parent_product = new WC_Product( $this->id );
					
					if ( $parent_product->managing_stock() ) {
						if ( ! $parent_product->backorders_allowed() ) {
							if ( $parent_product->get_total_stock() == 0 || $parent_product->get_total_stock() < 0 ) {
								update_post_meta( $this->id, '_stock_status', 'outofstock' );
								$woocommerce->clear_product_transients( $this->id ); // Clear transient
							}
						}
					} else {
						if ( $parent_product->get_total_stock() == 0 || $parent_product->get_total_stock() < 0 ) {
							update_post_meta( $this->id, '_stock_status', 'outofstock' );
							$woocommerce->clear_product_transients( $this->id ); // Clear transient
						}
					}

				}
				
				return $this->stock;
			}
		} else {
			return parent::reduce_stock( $by );
		}
	}
	
	/**
	 * Increase stock level of the product
	 *
	 * @param   int		$by		Amount to increase by
	 */
	function increase_stock( $by = 1 ) {
		if ($this->variation_has_stock) :
			if ($this->managing_stock()) :

				$this->stock = $this->stock + $by;
				$this->total_stock = $this->get_total_stock() + $by;
				update_post_meta($this->variation_id, '_stock', $this->stock);
				
				// Parents out of stock attribute
				if ( $this->is_in_stock() ) update_post_meta($this->id, '_stock_status', 'instock');
				
				return $this->stock;
			endif;
		else :
			return parent::increase_stock( $by );
		endif;
	}
	
	/**
	 * Get the shipping class, and if not set, get the shipping class of the parent
	 */
	function get_shipping_class() {
		if (!$this->variation_shipping_class) :
			$classes = get_the_terms( $this->variation_id, 'product_shipping_class' );
			if ($classes && !is_wp_error($classes)) $this->variation_shipping_class = current($classes)->slug; else $this->variation_shipping_class = parent::get_shipping_class();
		endif;
		return $this->variation_shipping_class;
	}

}

/** Depreciated */
class woocommerce_product_variation extends WC_Product_Variation {
	public function __construct( $variation_id, $parent_id = '', $parent_custom_fields = '' ) { 
		_deprecated_function( 'woocommerce_product_variation', '1.4', 'WC_Product_Variation()' );
		parent::__construct( $variation_id, $parent_id, $parent_custom_fields ); 
	} 
}