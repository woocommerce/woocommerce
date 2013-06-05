<?php

return new WC_Attribute_Helper();

class WC_Attribute_Helper {
	/**
	 * Get attribute taxonomies.
	 *
	 * @access public
	 * @return object
	 */
	public function get_attribute_taxonomies() {

		$transient_name = 'wc_attribute_taxonomies';

		if ( false === ( $attribute_taxonomies = get_transient( $transient_name ) ) ) {

			global $wpdb;

			$attribute_taxonomies = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies" );

			set_transient( $transient_name, $attribute_taxonomies );
		}

		return apply_filters( 'woocommerce_attribute_taxonomies', $attribute_taxonomies );
	}

	/**
	 * Get a product attributes name.
	 *
	 * @access public
	 * @param mixed $name
	 * @return string
	 */
	public function attribute_taxonomy_name( $name ) {
		return 'pa_' . woocommerce_sanitize_taxonomy_name( $name );
	}

	/**
	 * Get a product attributes label.
	 *
	 * @access public
	 * @param mixed $name
	 * @return string
	 */
	public function attribute_label( $name ) {
		global $wpdb;

		if ( taxonomy_is_product_attribute( $name ) ) {
			$name = woocommerce_sanitize_taxonomy_name( str_replace( 'pa_', '', $name ) );

			$label = $wpdb->get_var( $wpdb->prepare( "SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s;", $name ) );

			if ( ! $label )
				$label = ucfirst( $name );
		} else {
			$label = $name;
		}

		return apply_filters( 'woocommerce_attribute_label', $label, $name );
	}

	/**
	 * Get a product attributes orderby setting.
	 *
	 * @access public
	 * @param mixed $name
	 * @return string
	 */
	public function attribute_orderby( $name ) {
		global $wpdb;

		$name = str_replace( 'pa_', '', sanitize_title( $name ) );

		$orderby = $wpdb->get_var( $wpdb->prepare( "SELECT attribute_orderby FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = %s;", $name ) );

		return apply_filters( 'woocommerce_attribute_orderby', $orderby, $name );
	}

	/**
	 * Get an array of product attribute taxonomies.
	 *
	 * @access public
	 * @return array
	 */
	public function get_attribute_taxonomy_names() {
		$taxonomy_names = array();
		$attribute_taxonomies = $this->get_attribute_taxonomies();
		if ( $attribute_taxonomies ) {
			foreach ( $attribute_taxonomies as $tax ) {
				$taxonomy_names[] = $this->attribute_taxonomy_name( $tax->attribute_name );
			}
		}
		return $taxonomy_names;
	}
}