<?php
if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * WC_Report_Stock class
 */
class WC_Report_Stock extends WP_List_Table {

    /**
     * __construct function.
     *
     * @access public
     */
    function __construct(){
        parent::__construct( array(
            'singular'  => __( 'Stock', 'woocommerce' ),
            'plural'    => __( 'Stock', 'woocommerce' ),
            'ajax'      => false
        ) );
    }

    /**
     * Don't need this
     */
    function display_tablenav() {}

	/**
	 * Output the report
	 */
	public function output_report() {
		$this->prepare_items();
		echo '<div id="poststuff" class="woocommerce-reports-wide">';
		$this->display();
		echo '</div>';
	}

    /**
     * column_default function.
     *
     * @access public
     * @param mixed $user
     * @param mixed $column_name
     */
    function column_default( $product_id, $column_name ) {
    	global $woocommerce, $wpdb, $product;

    	if ( ! $product || $product->id !== $product_id )
    		$product = get_product( $product_id );

        switch( $column_name ) {
        	case 'stock_status' :

        	break;
        	case 'product' :
        		echo $product->get_title();
        	break;
        	case 'stock_level' :
        		echo $product->get_stock_quantity();
        	break;
        }
	}

    /**
     * get_columns function.
     *
     * @access public
     */
    function get_columns(){
        $columns = array(
			'stock_status' => __( 'Stock Status', 'woocommerce' ),
			'product'      => __( 'Product', 'woocommerce' ),
			'stock_level'  => __( 'Units in stock', 'woocommerce' ),
        );

        return $columns;
    }

    /**
     * prepare_items function.
     *
     * @access public
     */
    public function prepare_items() {
        global $wpdb;

		$current_page = 1;
		$per_page     = 999999999999;

        /**
         * Init column headers
         */
        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

        /**
         * Get Products
         */
        // Low/No stock lists
		$lowstockamount = get_option('woocommerce_notify_low_stock_amount');
		if (!is_numeric($lowstockamount)) $lowstockamount = 1;

		$nostockamount = get_option('woocommerce_notify_no_stock_amount');
		if (!is_numeric($nostockamount)) $nostockamount = 0;

		// Get low in stock simple/downloadable/virtual products. Grouped don't have stock. Variations need a separate query.
		$args = array(
			'post_type'			=> 'product',
			'post_status' 		=> 'publish',
			'posts_per_page' 	=> -1,
			'meta_query' => array(
				array(
					'key' 		=> '_manage_stock',
					'value' 	=> 'yes'
				),
				array(
					'key' 		=> '_stock',
					'value' 	=> $lowstockamount,
					'compare' 	=> '<=',
					'type' 		=> 'NUMERIC'
				)
			),
			'tax_query' => array(
				array(
					'taxonomy' 	=> 'product_type',
					'field' 	=> 'name',
					'terms' 	=> array('simple'),
					'operator' 	=> 'IN'
				)
			),
			'fields' => 'id=>parent'
		);

		$low_stock_products = array_flip( (array) get_posts($args) );

		$this->items = $low_stock_products;
    }
}