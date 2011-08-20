<?php
/**
 * Product Variation Class
 * 
 * The WooCommerce product variation class handles product variation data.
 *
 * @class woocommerce_product_variation
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce_product_variation extends woocommerce_product {
	
	var $variation;
	var $variation_data;
	var $variation_id;
	var $variation_has_weight;
	var $variation_has_price;
	var $variation_has_sale_price;
	var $variation_has_stock;
	var $variation_has_sku;
	
	/**
	 * Loads all product data from custom fields
	 *
	 * @param   int		$id		ID of the product to load
	 */
	function woocommerce_product_variation( $variation_id ) {
		
		$this->variation_id = $variation_id;
	
		$product_custom_fields = get_post_custom( $this->variation_id );
		
		$this->variation_data = array();
		
		foreach ($product_custom_fields as $name => $value) :
			
			if (!strstr($name, 'tax_')) continue;
			
			$this->variation_data[$name] = $value[0];
			
		endforeach;

		$this->get_variation_post_data();
		
		/* Get main product data from parent */
		$this->id = $this->variation->post_parent;
		
		$parent_custom_fields = get_post_custom( $this->id );
		
		// Define the data we're going to load: Key => Default value
		$load_data = array(
			'sku'			=> $this->id,
			'price' 		=> 0,
			'visibility'	=> 'hidden',
			'stock'			=> 0,
			'stock_status'	=> 'instock',
			'backorders'	=> 'no',
			'manage_stock'	=> 'no',
			'sale_price'	=> '',
			'regular_price' => '',
			'weight'		=> '',
			'tax_status'	=> 'taxable',
			'tax_class'		=> '',
			'upsell_ids'	=> array(),
			'crosssell_ids' => array()
		);
		
		// Load the data from the custom fields
		foreach ($load_data as $key => $default) :
			if (isset($parent_custom_fields[$key][0]) && !empty($parent_custom_fields[$key][0])) :
				$this->$key = $parent_custom_fields[$key][0];
			else :
				$this->$key = $default;
			endif;
		endforeach;
		
		// Load serialised data, unserialise twice to fix WP bug
		if (isset($product_custom_fields['product_attributes'][0])) $this->attributes = maybe_unserialize( maybe_unserialize( $product_custom_fields['product_attributes'][0] )); else $this->attributes = array();	

		
		$this->product_type = 'variable';
			
		if ($parent_custom_fields) :
			$this->exists = true;		
		else :
			$this->exists = false;	
		endif;
				
		/* Override parent data with variation */
		if (isset($product_custom_fields['sku'][0]) && !empty($product_custom_fields['sku'][0])) :
			$this->variation_has_sku = true;
			$this->sku = $product_custom_fields['sku'][0];
		endif;
		
		if (isset($product_custom_fields['stock'][0]) && $product_custom_fields['stock'][0]!=='') :
			$this->variation_has_stock = true;
			$this->stock = $product_custom_fields['stock'][0];
		endif;
		
		if (isset($product_custom_fields['weight'][0]) && $product_custom_fields['weight'][0]!=='') :
			$this->variation_has_weight = true;
			$this->weight = $product_custom_fields['weight'][0];
		endif;
		
		if (isset($product_custom_fields['price'][0]) && $product_custom_fields['price'][0]!=='') :
			$this->variation_has_price = true;
			$this->price = $product_custom_fields['price'][0];
			$this->regular_price = $product_custom_fields['price'][0];
		endif;
		
		if (isset($product_custom_fields['sale_price'][0]) && $product_custom_fields['sale_price'][0]!=='') :
			$this->variation_has_sale_price = true;
			$this->sale_price = $product_custom_fields['sale_price'][0];
			if ($this->sale_price < $this->price) $this->price = $this->sale_price;
		endif;
	}

	/** Get the product's post data */
	function get_variation_post_data() {
		if (empty($this->variation)) :
			$this->variation = get_post( $this->variation_id );
		endif;
		return $this->variation;
	}
	
	/** Returns the price in html format */
	function get_price_html() {
		if ($this->variation_has_price || $this->variation_has_sale_price) :
			$price = '';
			
			if ($this->price) :
				if ($this->variation_has_sale_price) :
					$price .= '<del>'.woocommerce_price( $this->regular_price ).'</del> <ins>'.woocommerce_price( $this->sale_price ).'</ins>';
				else :
					$price .= woocommerce_price( $this->price );
				endif;
			endif;
	
			return $price;
		else :
			return woocommerce_price(parent::get_price());
		endif;
	}
	
	/**
	 * Reduce stock level of the product
	 *
	 * @param   int		$by		Amount to reduce by
	 */
	function reduce_stock( $by = 1 ) {
		if ($this->variation_has_stock) :
			if ($this->managing_stock()) :
				$reduce_to = $this->stock - $by;
				update_post_meta($this->variation_id, 'stock', $reduce_to);
				return $reduce_to;
			endif;
		else :
			return parent::reduce_stock( $by );
		endif;
	}
	
	/**
	 * Increase stock level of the product
	 *
	 * @param   int		$by		Amount to increase by
	 */
	function increase_stock( $by = 1 ) {
		if ($this->variation_has_stock) :
			if ($this->managing_stock()) :
				$increase_to = $this->stock + $by;
				update_post_meta($this->variation_id, 'stock', $increase_to);
				return $increase_to;
			endif;
		else :
			return parent::increase_stock( $by );
		endif;
	}

}