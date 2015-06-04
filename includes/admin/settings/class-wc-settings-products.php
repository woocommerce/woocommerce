<?php
/**
 * WooCommerce Product Settings
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Settings_Products' ) ) :

/**
 * WC_Settings_Products
 */
class WC_Settings_Products extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id    = 'products';
		$this->label = __( 'Products', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {

		$sections = array(
			''          	=> __( 'General', 'woocommerce' ),
			'display'       => __( 'Display', 'woocommerce' ),
			'inventory' 	=> __( 'Inventory', 'woocommerce' ),
			'downloadable' 	=> __( 'Downloadable Products', 'woocommerce' ),
		);

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		if ( 'display' == $current_section ) {

			$settings = apply_filters( 'woocommerce_product_settings', array(

				array(
					'title' => __( 'Shop & Product Pages', 'woocommerce' ),
					'type' 	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'catalog_options'
				),

				array(
					'title'    => __( 'Shop Page', 'woocommerce' ),
					'desc'     => '<br/>' . sprintf( __( 'The base page can also be used in your <a href="%s">product permalinks</a>.', 'woocommerce' ), admin_url( 'options-permalink.php' ) ),
					'id'       => 'woocommerce_shop_page_id',
					'type'     => 'single_select_page',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width:300px;',
					'desc_tip' => __( 'This sets the base page of your shop - this is where your product archive will be.', 'woocommerce' ),
				),

				array(
					'title'    => __( 'Shop Page Display', 'woocommerce' ),
					'desc'     => __( 'This controls what is shown on the product archive.', 'woocommerce' ),
					'id'       => 'woocommerce_shop_page_display',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'default'  => '',
					'type'     => 'select',
					'options'  => array(
						''              => __( 'Show products', 'woocommerce' ),
						'subcategories' => __( 'Show categories &amp; subcategories', 'woocommerce' ),
						'both'          => __( 'Show both', 'woocommerce' ),
					),
					'desc_tip' =>  true,
				),

				array(
					'title'    => __( 'Default Category Display', 'woocommerce' ),
					'desc'     => __( 'This controls what is shown on category archives.', 'woocommerce' ),
					'id'       => 'woocommerce_category_archive_display',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'default'  => '',
					'type'     => 'select',
					'options'  => array(
						''              => __( 'Show products', 'woocommerce' ),
						'subcategories' => __( 'Show subcategories', 'woocommerce' ),
						'both'          => __( 'Show both', 'woocommerce' ),
					),
					'desc_tip' =>  true,
				),

				array(
					'title'    => __( 'Default Product Sorting', 'woocommerce' ),
					'desc'     => __( 'This controls the default sort order of the catalog.', 'woocommerce' ),
					'id'       => 'woocommerce_default_catalog_orderby',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'default'  => 'menu_order',
					'type'     => 'select',
					'options'  => apply_filters( 'woocommerce_default_catalog_orderby_options', array(
						'menu_order' => __( 'Default sorting (custom ordering + name)', 'woocommerce' ),
						'popularity' => __( 'Popularity (sales)', 'woocommerce' ),
						'rating'     => __( 'Average Rating', 'woocommerce' ),
						'date'       => __( 'Sort by most recent', 'woocommerce' ),
						'price'      => __( 'Sort by price (asc)', 'woocommerce' ),
						'price-desc' => __( 'Sort by price (desc)', 'woocommerce' ),
					) ),
					'desc_tip' =>  true,
				),

				array(
					'title'         => __( 'Add to cart behaviour', 'woocommerce' ),
					'desc'          => __( 'Redirect to the cart page after successful addition', 'woocommerce' ),
					'id'            => 'woocommerce_cart_redirect_after_add',
					'default'       => 'no',
					'type'          => 'checkbox',
					'checkboxgroup' => 'start'
				),

				array(
					'desc'          => __( 'Enable AJAX add to cart buttons on archives', 'woocommerce' ),
					'id'            => 'woocommerce_enable_ajax_add_to_cart',
					'default'       => 'yes',
					'type'          => 'checkbox',
					'checkboxgroup' => 'end'
				),

				array(
					'type' 	=> 'sectionend',
					'id' 	=> 'catalog_options'
				),

				array(
					'title' => __( 'Product Images', 'woocommerce' ),
					'type' 	=> 'title',
					'desc' 	=> sprintf( __( 'These settings affect the display and dimensions of images in your catalog - the display on the front-end will still be affected by CSS styles. After changing these settings you may need to <a href="%s">regenerate your thumbnails</a>.', 'woocommerce' ), 'http://wordpress.org/extend/plugins/regenerate-thumbnails/' ),
					'id' 	=> 'image_options'
				),

				array(
					'title'    => __( 'Catalog Images', 'woocommerce' ),
					'desc'     => __( 'This size is usually used in product listings', 'woocommerce' ),
					'id'       => 'shop_catalog_image_size',
					'css'      => '',
					'type'     => 'image_width',
					'default'  => array(
						'width'  => '300',
						'height' => '300',
						'crop'   => 1
					),
					'desc_tip' =>  true,
				),

				array(
					'title'    => __( 'Single Product Image', 'woocommerce' ),
					'desc'     => __( 'This is the size used by the main image on the product page.', 'woocommerce' ),
					'id'       => 'shop_single_image_size',
					'css'      => '',
					'type'     => 'image_width',
					'default'  => array(
						'width'  => '600',
						'height' => '600',
						'crop'   => 1
					),
					'desc_tip' =>  true,
				),

				array(
					'title'    => __( 'Product Thumbnails', 'woocommerce' ),
					'desc'     => __( 'This size is usually used for the gallery of images on the product page.', 'woocommerce' ),
					'id'       => 'shop_thumbnail_image_size',
					'css'      => '',
					'type'     => 'image_width',
					'default'  => array(
						'width'  => '180',
						'height' => '180',
						'crop'   => 1
					),
					'desc_tip' =>  true,
				),

				array(
					'title'         => __( 'Product Image Gallery', 'woocommerce' ),
					'desc'          => __( 'Enable Lightbox for product images', 'woocommerce' ),
					'id'            => 'woocommerce_enable_lightbox',
					'default'       => 'yes',
					'desc_tip'      => __( 'Include WooCommerce\'s lightbox. Product gallery images will open in a lightbox.', 'woocommerce' ),
					'type'          => 'checkbox'
				),

				array(
					'type' 	=> 'sectionend',
					'id' 	=> 'image_options'
				)

			));
		} elseif ( 'inventory' == $current_section ) {

			$settings = apply_filters( 'woocommerce_inventory_settings', array(

				array(
					'title' => __( 'Inventory', 'woocommerce' ),
					'type' 	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'product_inventory_options'
				),

				array(
					'title'   => __( 'Manage Stock', 'woocommerce' ),
					'desc'    => __( 'Enable stock management', 'woocommerce' ),
					'id'      => 'woocommerce_manage_stock',
					'default' => 'yes',
					'type'    => 'checkbox'
				),

				array(
					'title'             => __( 'Hold Stock (minutes)', 'woocommerce' ),
					'desc'              => __( 'Hold stock (for unpaid orders) for x minutes. When this limit is reached, the pending order will be cancelled. Leave blank to disable.', 'woocommerce' ),
					'id'                => 'woocommerce_hold_stock_minutes',
					'type'              => 'number',
					'custom_attributes' => array(
						'min'  => 0,
						'step' => 1
					),
					'css'               => 'width: 80px;',
					'default'           => '60',
					'autoload'          => false
				),

				array(
					'title'         => __( 'Notifications', 'woocommerce' ),
					'desc'          => __( 'Enable low stock notifications', 'woocommerce' ),
					'id'            => 'woocommerce_notify_low_stock',
					'default'       => 'yes',
					'type'          => 'checkbox',
					'checkboxgroup' => 'start',
					'autoload'      => false
				),

				array(
					'desc'          => __( 'Enable out of stock notifications', 'woocommerce' ),
					'id'            => 'woocommerce_notify_no_stock',
					'default'       => 'yes',
					'type'          => 'checkbox',
					'checkboxgroup' => 'end',
					'autoload'      => false
				),

				array(
					'title'    => __( 'Notification Recipient', 'woocommerce' ),
					'desc'     => '',
					'id'       => 'woocommerce_stock_email_recipient',
					'type'     => 'email',
					'default'  => get_option( 'admin_email' ),
					'autoload' => false
				),

				array(
					'title'             => __( 'Low Stock Threshold', 'woocommerce' ),
					'desc'              => '',
					'id'                => 'woocommerce_notify_low_stock_amount',
					'css'               => 'width:50px;',
					'type'              => 'number',
					'custom_attributes' => array(
						'min'  => 0,
						'step' => 1
					),
					'default'           => '2',
					'autoload'          => false
				),

				array(
					'title'             => __( 'Out Of Stock Threshold', 'woocommerce' ),
					'desc'              => '',
					'id'                => 'woocommerce_notify_no_stock_amount',
					'css'               => 'width:50px;',
					'type'              => 'number',
					'custom_attributes' => array(
						'min'  => 0,
						'step' => 1
					),
					'default'           => '0',
					'autoload'          => false
				),

				array(
					'title'    => __( 'Out Of Stock Visibility', 'woocommerce' ),
					'desc'     => __( 'Hide out of stock items from the catalog', 'woocommerce' ),
					'id'       => 'woocommerce_hide_out_of_stock_items',
					'default'  => 'no',
					'type'     => 'checkbox'
				),

				array(
					'title'    => __( 'Stock Display Format', 'woocommerce' ),
					'desc'     => __( 'This controls how stock is displayed on the frontend.', 'woocommerce' ),
					'id'       => 'woocommerce_stock_format',
					'css'      => 'min-width:150px;',
					'class'    => 'wc-enhanced-select',
					'default'  => '',
					'type'     => 'select',
					'options'  => array(
						''           => __( 'Always show stock e.g. "12 in stock"', 'woocommerce' ),
						'low_amount' => __( 'Only show stock when low e.g. "Only 2 left in stock" vs. "In Stock"', 'woocommerce' ),
						'no_amount'  => __( 'Never show stock amount', 'woocommerce' ),
					),
					'desc_tip' =>  true,
				),

				array(
					'type' 	=> 'sectionend',
					'id' 	=> 'product_inventory_options'
				),

			));

		} elseif ( 'downloadable' == $current_section ) {
			$settings = apply_filters( 'woocommerce_downloadable_products_settings', array(
				array(
					'title' => __( 'Downloadable Products', 'woocommerce' ),
					'type' 	=> 'title',
					'id' 	=> 'digital_download_options'
				),

				array(
					'title'    => __( 'File Download Method', 'woocommerce' ),
					'desc'     => __( 'Forcing downloads will keep URLs hidden, but some servers may serve large files unreliably. If supported, <code>X-Accel-Redirect</code>/ <code>X-Sendfile</code> can be used to serve downloads instead (server requires <code>mod_xsendfile</code>).', 'woocommerce' ),
					'id'       => 'woocommerce_file_download_method',
					'type'     => 'select',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'default'  => 'force',
					'desc_tip' =>  true,
					'options'  => array(
						'force'     => __( 'Force Downloads', 'woocommerce' ),
						'xsendfile' => __( 'X-Accel-Redirect/X-Sendfile', 'woocommerce' ),
						'redirect'  => __( 'Redirect only', 'woocommerce' ),
					),
					'autoload' => false
				),

				array(
					'title'         => __( 'Access Restriction', 'woocommerce' ),
					'desc'          => __( 'Downloads require login', 'woocommerce' ),
					'id'            => 'woocommerce_downloads_require_login',
					'type'          => 'checkbox',
					'default'       => 'no',
					'desc_tip'      => __( 'This setting does not apply to guest purchases.', 'woocommerce' ),
					'checkboxgroup' => 'start',
					'autoload'      => false
				),

				array(
					'desc'          => __( 'Grant access to downloadable products after payment', 'woocommerce' ),
					'id'            => 'woocommerce_downloads_grant_access_after_payment',
					'type'          => 'checkbox',
					'default'       => 'yes',
					'desc_tip'      => __( 'Enable this option to grant access to downloads when orders are "processing", rather than "completed".', 'woocommerce' ),
					'checkboxgroup' => 'end',
					'autoload'      => false
				),

				array(
					'type' 	=> 'sectionend',
					'id' 	=> 'digital_download_options'
				),

			));

		} else {
			$settings = apply_filters( 'woocommerce_products_general_settings', array(
				array(
					'title' 	=> __( 'Measurements', 'woocommerce' ),
					'type' 		=> 'title',
					'id' 		=> 'product_measurement_options'
				),

				array(
					'title'    => __( 'Weight Unit', 'woocommerce' ),
					'desc'     => __( 'This controls what unit you will define weights in.', 'woocommerce' ),
					'id'       => 'woocommerce_weight_unit',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'default'  => 'kg',
					'type'     => 'select',
					'options'  => array(
						'kg'  => __( 'kg', 'woocommerce' ),
						'g'   => __( 'g', 'woocommerce' ),
						'lbs' => __( 'lbs', 'woocommerce' ),
						'oz'  => __( 'oz', 'woocommerce' ),
					),
					'desc_tip' =>  true,
				),

				array(
					'title'    => __( 'Dimensions Unit', 'woocommerce' ),
					'desc'     => __( 'This controls what unit you will define lengths in.', 'woocommerce' ),
					'id'       => 'woocommerce_dimension_unit',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'default'  => 'cm',
					'type'     => 'select',
					'options'  => array(
						'm'  => __( 'm', 'woocommerce' ),
						'cm' => __( 'cm', 'woocommerce' ),
						'mm' => __( 'mm', 'woocommerce' ),
						'in' => __( 'in', 'woocommerce' ),
						'yd' => __( 'yd', 'woocommerce' ),
					),
					'desc_tip' =>  true,
				),

				array(
					'type' 	=> 'sectionend',
					'id' 	=> 'product_measurement_options'
				),

				array(
					'title' => __( 'Reviews', 'woocommerce' ),
					'type' 	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'product_rating_options'
				),

				array(
					'title'           => __( 'Product Ratings', 'woocommerce' ),
					'desc'            => __( 'Enable ratings on reviews', 'woocommerce' ),
					'id'              => 'woocommerce_enable_review_rating',
					'default'         => 'yes',
					'type'            => 'checkbox',
					'checkboxgroup'   => 'start',
					'show_if_checked' => 'option',
					'autoload'        => false
				),

				array(
					'desc'            => __( 'Ratings are required to leave a review', 'woocommerce' ),
					'id'              => 'woocommerce_review_rating_required',
					'default'         => 'yes',
					'type'            => 'checkbox',
					'checkboxgroup'   => '',
					'show_if_checked' => 'yes',
					'autoload'        => false
				),

				array(
					'desc'            => __( 'Show "verified owner" label for customer reviews', 'woocommerce' ),
					'id'              => 'woocommerce_review_rating_verification_label',
					'default'         => 'yes',
					'type'            => 'checkbox',
					'checkboxgroup'   => '',
					'show_if_checked' => 'yes',
					'autoload'        => false
				),

				array(
					'desc'            => __( 'Only allow reviews from "verified owners"', 'woocommerce' ),
					'id'              => 'woocommerce_review_rating_verification_required',
					'default'         => 'no',
					'type'            => 'checkbox',
					'checkboxgroup'   => 'end',
					'show_if_checked' => 'yes',
					'autoload'        => false
				),

				array(
					'type' 	=> 'sectionend',
					'id' 	=> 'product_rating_options'
				),

			));
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}
}

endif;

return new WC_Settings_Products();
