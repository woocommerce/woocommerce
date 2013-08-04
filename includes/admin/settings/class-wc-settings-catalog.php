<?php
/**
 * WooCommerce General Settings
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Settings_Catalog' ) ) :

/**
 * WC_Settings_Catalog
 */
class WC_Settings_Catalog extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'catalog';
		$this->label = __( 'Catalog', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		return apply_filters('woocommerce_catalog_settings', array(

			array(	'title' => __( 'Catalog Options', 'woocommerce' ), 'type' => 'title','desc' => '', 'id' => 'catalog_options' ),

			array(
				'title' => __( 'Default Product Sorting', 'woocommerce' ),
				'desc' 		=> __( 'This controls the default sort order of the catalog.', 'woocommerce' ),
				'id' 		=> 'woocommerce_default_catalog_orderby',
				'css' 		=> 'min-width:150px;',
				'default'	=> 'title',
				'type' 		=> 'select',
				'options' => apply_filters('woocommerce_default_catalog_orderby_options', array(
					'menu_order' => __( 'Default sorting (custom ordering + name)', 'woocommerce' ),
					'popularity' => __( 'Popularity (sales)', 'woocommerce' ),
					'rating'     => __( 'Average Rating', 'woocommerce' ),
					'date'       => __( 'Sort by most recent', 'woocommerce' ),
					'price'      => __( 'Sort by price (asc)', 'woocommerce' ),
					'price-desc' => __( 'Sort by price (desc)', 'woocommerce' ),
				)),
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Shop Page Display', 'woocommerce' ),
				'desc' 		=> __( 'This controls what is shown on the product archive.', 'woocommerce' ),
				'id' 		=> 'woocommerce_shop_page_display',
				'css' 		=> 'min-width:150px;',
				'default'	=> '',
				'type' 		=> 'select',
				'options' => array(
					''  			=> __( 'Show products', 'woocommerce' ),
					'subcategories' => __( 'Show subcategories', 'woocommerce' ),
					'both'   		=> __( 'Show both', 'woocommerce' ),
				),
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Default Category Display', 'woocommerce' ),
				'desc' 		=> __( 'This controls what is shown on category archives.', 'woocommerce' ),
				'id' 		=> 'woocommerce_category_archive_display',
				'css' 		=> 'min-width:150px;',
				'default'	=> '',
				'type' 		=> 'select',
				'options' => array(
					''  			=> __( 'Show products', 'woocommerce' ),
					'subcategories' => __( 'Show subcategories', 'woocommerce' ),
					'both'   		=> __( 'Show both', 'woocommerce' ),
				),
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Add to cart', 'woocommerce' ),
				'desc' 		=> __( 'Redirect to the cart page after successful addition', 'woocommerce' ),
				'id' 		=> 'woocommerce_cart_redirect_after_add',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> 'start'
			),

			array(
				'desc' 		=> __( 'Enable AJAX add to cart buttons on archives', 'woocommerce' ),
				'id' 		=> 'woocommerce_enable_ajax_add_to_cart',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> 'end'
			),

			array( 'type' => 'sectionend', 'id' => 'catalog_options' ),

			array(	'title' => __( 'Product Data', 'woocommerce' ), 'type' => 'title', 'desc' => __( 'The following options affect the fields available on the edit product page.', 'woocommerce' ), 'id' => 'product_data_options' ),

			array(
				'title' => __( 'Product Fields', 'woocommerce' ),
				'desc' 		=> __( 'Enable the <strong>SKU</strong> field for products', 'woocommerce' ),
				'id' 		=> 'woocommerce_enable_sku',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> 'start'
			),

			array(
				'desc' 		=> __( 'Enable the <strong>weight</strong> field for products (some shipping methods may require this)', 'woocommerce' ),
				'id' 		=> 'woocommerce_enable_weight',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> ''
			),

			array(
				'desc' 		=> __( 'Enable the <strong>dimension</strong> fields for products (some shipping methods may require this)', 'woocommerce' ),
				'id' 		=> 'woocommerce_enable_dimensions',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> ''
			),

			array(
				'desc' 		=> __( 'Show <strong>weight and dimension</strong> values on the <strong>Additional Information</strong> tab', 'woocommerce' ),
				'id' 		=> 'woocommerce_enable_dimension_product_attributes',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> 'end'
			),

			array(
				'title' => __( 'Weight Unit', 'woocommerce' ),
				'desc' 		=> __( 'This controls what unit you will define weights in.', 'woocommerce' ),
				'id' 		=> 'woocommerce_weight_unit',
				'css' 		=> 'min-width:150px;',
				'default'	=> 'kg',
				'type' 		=> 'select',
				'options' => array(
					'kg'  => __( 'kg', 'woocommerce' ),
					'g'   => __( 'g', 'woocommerce' ),
					'lbs' => __( 'lbs', 'woocommerce' ),
					'oz' => __( 'oz', 'woocommerce' ),
				),
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Dimensions Unit', 'woocommerce' ),
				'desc' 		=> __( 'This controls what unit you will define lengths in.', 'woocommerce' ),
				'id' 		=> 'woocommerce_dimension_unit',
				'css' 		=> 'min-width:150px;',
				'default'	=> 'cm',
				'type' 		=> 'select',
				'options' => array(
					'm'  => __( 'm', 'woocommerce' ),
					'cm' => __( 'cm', 'woocommerce' ),
					'mm' => __( 'mm', 'woocommerce' ),
					'in' => __( 'in', 'woocommerce' ),
					'yd' => __( 'yd', 'woocommerce' ),
				),
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Product Ratings', 'woocommerce' ),
				'desc' 		=> __( 'Enable ratings on reviews', 'woocommerce' ),
				'id' 		=> 'woocommerce_enable_review_rating',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> 'start',
				'show_if_checked' => 'option',
				'autoload'      => false
			),

			array(
				'desc' 		=> __( 'Ratings are required to leave a review', 'woocommerce' ),
				'id' 		=> 'woocommerce_review_rating_required',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> '',
				'show_if_checked' => 'yes',
				'autoload'      => false
			),

			array(
				'desc' 		=> __( 'Show "verified owner" label for customer reviews', 'woocommerce' ),
				'id' 		=> 'woocommerce_review_rating_verification_label',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'		=> 'end',
				'show_if_checked' => 'yes',
				'autoload'      => false
			),

			array( 'type' => 'sectionend', 'id' => 'product_review_options' ),

			array(	'title' => __( 'Pricing Options', 'woocommerce' ), 'type' => 'title', 'desc' => __( 'The following options affect how prices are displayed on the frontend.', 'woocommerce' ), 'id' => 'pricing_options' ),

			array(
				'title' => __( 'Currency Position', 'woocommerce' ),
				'desc' 		=> __( 'This controls the position of the currency symbol.', 'woocommerce' ),
				'id' 		=> 'woocommerce_currency_pos',
				'css' 		=> 'min-width:150px;',
				'default'	=> 'left',
				'type' 		=> 'select',
				'options' => array(
					'left' => __( 'Left', 'woocommerce' ),
					'right' => __( 'Right', 'woocommerce' ),
					'left_space' => __( 'Left (with space)', 'woocommerce' ),
					'right_space' => __( 'Right (with space)', 'woocommerce' )
				),
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Thousand Separator', 'woocommerce' ),
				'desc' 		=> __( 'This sets the thousand separator of displayed prices.', 'woocommerce' ),
				'id' 		=> 'woocommerce_price_thousand_sep',
				'css' 		=> 'width:50px;',
				'default'	=> ',',
				'type' 		=> 'text',
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Decimal Separator', 'woocommerce' ),
				'desc' 		=> __( 'This sets the decimal separator of displayed prices.', 'woocommerce' ),
				'id' 		=> 'woocommerce_price_decimal_sep',
				'css' 		=> 'width:50px;',
				'default'	=> '.',
				'type' 		=> 'text',
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Number of Decimals', 'woocommerce' ),
				'desc' 		=> __( 'This sets the number of decimal points shown in displayed prices.', 'woocommerce' ),
				'id' 		=> 'woocommerce_price_num_decimals',
				'css' 		=> 'width:50px;',
				'default'	=> '2',
				'desc_tip'	=>  true,
				'type' 		=> 'number',
				'custom_attributes' => array(
					'min' 	=> 0,
					'step' 	=> 1
				)
			),

			array(
				'title'		=> __( 'Trailing Zeros', 'woocommerce' ),
				'desc' 		=> __( 'Remove zeros after the decimal point. e.g. <code>$10.00</code> becomes <code>$10</code>', 'woocommerce' ),
				'id' 		=> 'woocommerce_price_trim_zeros',
				'default'	=> 'yes',
				'type' 		=> 'checkbox'
			),

			array( 'type' => 'sectionend', 'id' => 'pricing_options' ),

			array(	'title' => __( 'Image Options', 'woocommerce' ), 'type' => 'title','desc' => sprintf(__( 'These settings affect the actual dimensions of images in your catalog - the display on the front-end will still be affected by CSS styles. After changing these settings you may need to <a href="%s">regenerate your thumbnails</a>.', 'woocommerce' ), 'http://wordpress.org/extend/plugins/regenerate-thumbnails/'), 'id' => 'image_options' ),

			array(
				'title' => __( 'Catalog Images', 'woocommerce' ),
				'desc' 		=> __( 'This size is usually used in product listings', 'woocommerce' ),
				'id' 		=> 'shop_catalog_image_size',
				'css' 		=> '',
				'type' 		=> 'image_width',
				'default'	=> array(
					'width' 	=> '150',
					'height'	=> '150',
					'crop'		=> true
				),
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Single Product Image', 'woocommerce' ),
				'desc' 		=> __( 'This is the size used by the main image on the product page.', 'woocommerce' ),
				'id' 		=> 'shop_single_image_size',
				'css' 		=> '',
				'type' 		=> 'image_width',
				'default'	=> array(
					'width' 	=> '300',
					'height'	=> '300',
					'crop'		=> 1
				),
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Product Thumbnails', 'woocommerce' ),
				'desc' 		=> __( 'This size is usually used for the gallery of images on the product page.', 'woocommerce' ),
				'id' 		=> 'shop_thumbnail_image_size',
				'css' 		=> '',
				'type' 		=> 'image_width',
				'default'	=> array(
					'width' 	=> '90',
					'height'	=> '90',
					'crop'		=> 1
				),
				'desc_tip'	=>  true,
			),

			array( 'type' => 'sectionend', 'id' => 'image_options' ),

		)); // End catalog settings
	}
}

endif;

return new WC_Settings_Catalog();