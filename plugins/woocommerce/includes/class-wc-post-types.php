<?php
/**
 * Post Types
 *
 * Registers post types and taxonomies.
 *
 * @package WooCommerce\Classes\Products
 * @version 2.5.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * Post types Class.
 */
class WC_Post_Types {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_post_status' ), 9 );
		add_action( 'init', array( __CLASS__, 'support_jetpack_omnisearch' ) );
		add_filter( 'term_updated_messages', array( __CLASS__, 'updated_term_messages' ) );
		add_filter( 'rest_api_allowed_post_types', array( __CLASS__, 'rest_api_allowed_post_types' ) );
		add_action( 'woocommerce_after_register_post_type', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );
		add_action( 'woocommerce_flush_rewrite_rules', array( __CLASS__, 'flush_rewrite_rules' ) );
		add_filter( 'gutenberg_can_edit_post_type', array( __CLASS__, 'gutenberg_can_edit_post_type' ), 10, 2 );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'gutenberg_can_edit_post_type' ), 10, 2 );
	}

	/**
	 * Register core taxonomies.
	 */
	public static function register_taxonomies() {

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( taxonomy_exists( 'product_type' ) ) {
			return;
		}

		do_action( 'woocommerce_register_taxonomy' );

		$permalinks = wc_get_permalink_structure();

		register_taxonomy(
			'product_type',
			apply_filters( 'woocommerce_taxonomy_objects_product_type', array( 'product' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_product_type',
				array(
					'hierarchical'      => false,
					'show_ui'           => false,
					'show_in_nav_menus' => false,
					'query_var'         => is_admin(),
					'rewrite'           => false,
					'public'            => false,
					'label'             => _x( 'Product type', 'Taxonomy name', 'woocommerce' ),
				)
			)
		);

		register_taxonomy(
			'product_visibility',
			apply_filters( 'woocommerce_taxonomy_objects_product_visibility', array( 'product', 'product_variation' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_product_visibility',
				array(
					'hierarchical'      => false,
					'show_ui'           => false,
					'show_in_nav_menus' => false,
					'query_var'         => is_admin(),
					'rewrite'           => false,
					'public'            => false,
					'label'             => _x( 'Product visibility', 'Taxonomy name', 'woocommerce' ),
				)
			)
		);

		register_taxonomy(
			'product_cat',
			apply_filters( 'woocommerce_taxonomy_objects_product_cat', array( 'product' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_product_cat',
				array(
					'hierarchical'          => true,
					'update_count_callback' => '_wc_term_recount',
					'label'                 => __( 'Categories', 'woocommerce' ),
					'labels'                => array(
						'name'                  => __( 'Product categories', 'woocommerce' ),
						'singular_name'         => __( 'Category', 'woocommerce' ),
						'menu_name'             => _x( 'Categories', 'Admin menu name', 'woocommerce' ),
						'search_items'          => __( 'Search categories', 'woocommerce' ),
						'all_items'             => __( 'All categories', 'woocommerce' ),
						'parent_item'           => __( 'Parent category', 'woocommerce' ),
						'parent_item_colon'     => __( 'Parent category:', 'woocommerce' ),
						'edit_item'             => __( 'Edit category', 'woocommerce' ),
						'update_item'           => __( 'Update category', 'woocommerce' ),
						'add_new_item'          => __( 'Add new category', 'woocommerce' ),
						'new_item_name'         => __( 'New category name', 'woocommerce' ),
						'not_found'             => __( 'No categories found', 'woocommerce' ),
						'item_link'             => __( 'Product Category Link', 'woocommerce' ),
						'item_link_description' => __( 'A link to a product category.', 'woocommerce' ),
					),
					'show_in_rest'          => true,
					'show_ui'               => true,
					'query_var'             => true,
					'capabilities'          => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),
					'rewrite'               => array(
						'slug'         => $permalinks['category_rewrite_slug'],
						'with_front'   => false,
						'hierarchical' => true,
					),
				)
			)
		);

		register_taxonomy(
			'product_tag',
			apply_filters( 'woocommerce_taxonomy_objects_product_tag', array( 'product' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_product_tag',
				array(
					'hierarchical'          => false,
					'update_count_callback' => '_wc_term_recount',
					'label'                 => __( 'Product tags', 'woocommerce' ),
					'labels'                => array(
						'name'                       => __( 'Product tags', 'woocommerce' ),
						'singular_name'              => __( 'Tag', 'woocommerce' ),
						'menu_name'                  => _x( 'Tags', 'Admin menu name', 'woocommerce' ),
						'search_items'               => __( 'Search tags', 'woocommerce' ),
						'all_items'                  => __( 'All tags', 'woocommerce' ),
						'edit_item'                  => __( 'Edit tag', 'woocommerce' ),
						'update_item'                => __( 'Update tag', 'woocommerce' ),
						'add_new_item'               => __( 'Add new tag', 'woocommerce' ),
						'new_item_name'              => __( 'New tag name', 'woocommerce' ),
						'popular_items'              => __( 'Popular tags', 'woocommerce' ),
						'separate_items_with_commas' => __( 'Separate tags with commas', 'woocommerce' ),
						'add_or_remove_items'        => __( 'Add or remove tags', 'woocommerce' ),
						'choose_from_most_used'      => __( 'Choose from the most used tags', 'woocommerce' ),
						'not_found'                  => __( 'No tags found', 'woocommerce' ),
						'item_link'                  => __( 'Product Tag Link', 'woocommerce' ),
						'item_link_description'      => __( 'A link to a product tag.', 'woocommerce' ),
					),
					'show_in_rest'          => true,
					'show_ui'               => true,
					'query_var'             => true,
					'capabilities'          => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),
					'rewrite'               => array(
						'slug'       => $permalinks['tag_rewrite_slug'],
						'with_front' => false,
					),
				)
			)
		);

		register_taxonomy(
			'product_shipping_class',
			apply_filters( 'woocommerce_taxonomy_objects_product_shipping_class', array( 'product', 'product_variation' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_product_shipping_class',
				array(
					'hierarchical'          => false,
					'update_count_callback' => '_update_post_term_count',
					'label'                 => __( 'Shipping classes', 'woocommerce' ),
					'labels'                => array(
						'name'              => __( 'Product shipping classes', 'woocommerce' ),
						'singular_name'     => __( 'Shipping class', 'woocommerce' ),
						'menu_name'         => _x( 'Shipping classes', 'Admin menu name', 'woocommerce' ),
						'search_items'      => __( 'Search shipping classes', 'woocommerce' ),
						'all_items'         => __( 'All shipping classes', 'woocommerce' ),
						'parent_item'       => __( 'Parent shipping class', 'woocommerce' ),
						'parent_item_colon' => __( 'Parent shipping class:', 'woocommerce' ),
						'edit_item'         => __( 'Edit shipping class', 'woocommerce' ),
						'update_item'       => __( 'Update shipping class', 'woocommerce' ),
						'add_new_item'      => __( 'Add new shipping class', 'woocommerce' ),
						'new_item_name'     => __( 'New shipping class Name', 'woocommerce' ),
					),
					'show_ui'               => false,
					'show_in_quick_edit'    => false,
					'show_in_nav_menus'     => false,
					'query_var'             => is_admin(),
					'capabilities'          => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),
					'rewrite'               => false,
				)
			)
		);

		global $wc_product_attributes;

		$wc_product_attributes = array();
		$attribute_taxonomies  = wc_get_attribute_taxonomies();

		if ( $attribute_taxonomies ) {
			foreach ( $attribute_taxonomies as $tax ) {
				$name = wc_attribute_taxonomy_name( $tax->attribute_name );

				if ( $name ) {
					$tax->attribute_public          = absint( isset( $tax->attribute_public ) ? $tax->attribute_public : 1 );
					$label                          = ! empty( $tax->attribute_label ) ? $tax->attribute_label : $tax->attribute_name;
					$wc_product_attributes[ $name ] = $tax;
					$taxonomy_data                  = array(
						'hierarchical'          => false,
						'update_count_callback' => '_update_post_term_count',
						'labels'                => array(
							/* translators: %s: attribute name */
							'name'              => sprintf( _x( 'Product %s', 'Product Attribute', 'woocommerce' ), $label ),
							'singular_name'     => $label,
							/* translators: %s: attribute name */
							'search_items'      => sprintf( __( 'Search %s', 'woocommerce' ), $label ),
							/* translators: %s: attribute name */
							'all_items'         => sprintf( __( 'All %s', 'woocommerce' ), $label ),
							/* translators: %s: attribute name */
							'parent_item'       => sprintf( __( 'Parent %s', 'woocommerce' ), $label ),
							/* translators: %s: attribute name */
							'parent_item_colon' => sprintf( __( 'Parent %s:', 'woocommerce' ), $label ),
							/* translators: %s: attribute name */
							'edit_item'         => sprintf( __( 'Edit %s', 'woocommerce' ), $label ),
							/* translators: %s: attribute name */
							'update_item'       => sprintf( __( 'Update %s', 'woocommerce' ), $label ),
							/* translators: %s: attribute name */
							'add_new_item'      => sprintf( __( 'Add new %s', 'woocommerce' ), $label ),
							/* translators: %s: attribute name */
							'new_item_name'     => sprintf( __( 'New %s', 'woocommerce' ), $label ),
							/* translators: %s: attribute name */
							'not_found'         => sprintf( __( 'No &quot;%s&quot; found', 'woocommerce' ), $label ),
							/* translators: %s: attribute name */
							'back_to_items'     => sprintf( __( '&larr; Back to "%s" attributes', 'woocommerce' ), $label ),
						),
						'show_ui'               => true,
						'show_in_quick_edit'    => false,
						'show_in_menu'          => false,
						'meta_box_cb'           => false,
						'query_var'             => 1 === $tax->attribute_public,
						'rewrite'               => false,
						'sort'                  => false,
						'public'                => 1 === $tax->attribute_public,
						'show_in_nav_menus'     => 1 === $tax->attribute_public && apply_filters( 'woocommerce_attribute_show_in_nav_menus', false, $name ),
						'capabilities'          => array(
							'manage_terms' => 'manage_product_terms',
							'edit_terms'   => 'edit_product_terms',
							'delete_terms' => 'delete_product_terms',
							'assign_terms' => 'assign_product_terms',
						),
					);

					if ( 1 === $tax->attribute_public && sanitize_title( $tax->attribute_name ) ) {
						$taxonomy_data['rewrite'] = array(
							'slug'         => trailingslashit( $permalinks['attribute_rewrite_slug'] ) . urldecode( sanitize_title( $tax->attribute_name ) ),
							'with_front'   => false,
							'hierarchical' => true,
						);
					}

					register_taxonomy( $name, apply_filters( "woocommerce_taxonomy_objects_{$name}", array( 'product' ) ), apply_filters( "woocommerce_taxonomy_args_{$name}", $taxonomy_data ) );
				}
			}
		}

		do_action( 'woocommerce_after_register_taxonomy' );
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types() {
		if ( ! is_blog_installed() || post_type_exists( 'product' ) ) {
			return;
		}

		do_action( 'woocommerce_register_post_type' );

		$permalinks = wc_get_permalink_structure();
		$supports   = array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'publicize', 'wpcom-markdown' );

		if ( 'yes' === get_option( 'woocommerce_enable_reviews', 'yes' ) ) {
			$supports[] = 'comments';
		}

		$shop_page_id = wc_get_page_id( 'shop' );

		if ( wc_current_theme_supports_woocommerce_or_fse() ) {
			$has_archive = $shop_page_id && get_post( $shop_page_id ) ? urldecode( get_page_uri( $shop_page_id ) ) : 'shop';
		} else {
			$has_archive = false;
		}

		// If theme support changes, we may need to flush permalinks since some are changed based on this flag.
		$theme_support = wc_current_theme_supports_woocommerce_or_fse() ? 'yes' : 'no';
		if ( get_option( 'current_theme_supports_woocommerce' ) !== $theme_support && update_option( 'current_theme_supports_woocommerce', $theme_support ) ) {
			update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		}

		register_post_type(
			'product',
			apply_filters(
				'woocommerce_register_post_type_product',
				array(
					'labels'              => array(
						'name'                  => __( 'Products', 'woocommerce' ),
						'singular_name'         => __( 'Product', 'woocommerce' ),
						'all_items'             => __( 'All Products', 'woocommerce' ),
						'menu_name'             => _x( 'Products', 'Admin menu name', 'woocommerce' ),
						'add_new'               => __( 'Add New', 'woocommerce' ),
						'add_new_item'          => __( 'Add new product', 'woocommerce' ),
						'edit'                  => __( 'Edit', 'woocommerce' ),
						'edit_item'             => __( 'Edit product', 'woocommerce' ),
						'new_item'              => __( 'New product', 'woocommerce' ),
						'view_item'             => __( 'View product', 'woocommerce' ),
						'view_items'            => __( 'View products', 'woocommerce' ),
						'search_items'          => __( 'Search products', 'woocommerce' ),
						'not_found'             => __( 'No products found', 'woocommerce' ),
						'not_found_in_trash'    => __( 'No products found in trash', 'woocommerce' ),
						'parent'                => __( 'Parent product', 'woocommerce' ),
						'featured_image'        => __( 'Product image', 'woocommerce' ),
						'set_featured_image'    => __( 'Set product image', 'woocommerce' ),
						'remove_featured_image' => __( 'Remove product image', 'woocommerce' ),
						'use_featured_image'    => __( 'Use as product image', 'woocommerce' ),
						'insert_into_item'      => __( 'Insert into product', 'woocommerce' ),
						'uploaded_to_this_item' => __( 'Uploaded to this product', 'woocommerce' ),
						'filter_items_list'     => __( 'Filter products', 'woocommerce' ),
						'items_list_navigation' => __( 'Products navigation', 'woocommerce' ),
						'items_list'            => __( 'Products list', 'woocommerce' ),
						'item_link'             => __( 'Product Link', 'woocommerce' ),
						'item_link_description' => __( 'A link to a product.', 'woocommerce' ),
					),
					'description'         => __( 'This is where you can browse products in this store.', 'woocommerce' ),
					'public'              => true,
					'show_ui'             => true,
					'menu_icon'           => 'dashicons-archive',
					'capability_type'     => 'product',
					'map_meta_cap'        => true,
					'publicly_queryable'  => true,
					'exclude_from_search' => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'rewrite'             => $permalinks['product_rewrite_slug'] ? array(
						'slug'       => $permalinks['product_rewrite_slug'],
						'with_front' => false,
						'feeds'      => true,
					) : false,
					'query_var'           => true,
					'supports'            => $supports,
					'has_archive'         => $has_archive,
					'show_in_nav_menus'   => true,
					'show_in_rest'        => true,
					'template'            => array(
						array(
							'woocommerce/product-tab',
							array(
								'id'    => 'general',
								'title' => __( 'General', 'woocommerce' ),
							),
							array(
								array(
									'woocommerce/product-section',
									array(
										'title'       => __( 'Basic details', 'woocommerce' ),
										'description' => __( 'This info will be displayed on the product page, category pages, social media, and search results.', 'woocommerce' ),
										'icon'        => array(
											'src' => plugins_url( '/assets/client/admin/product-editor/icons/section_basic.svg', WC_PLUGIN_FILE ),
										),
									),
									array(
										array(
											'woocommerce/product-name-field',
											array(
												'name' => 'Product name',
											),
										),
										array(
											'woocommerce/product-summary-field',
										),
										array(
											'core/columns',
											array(),
											array(
												array(
													'core/column',
													array(
														'templateLock' => 'all',
													),
													array(
														array(
															'woocommerce/product-pricing-field',
															array(
																'name' => 'regular_price',
																'label' => __( 'List price', 'woocommerce' ),
																'showPricingSection' => true,
															),
														),
													),
												),
												array(
													'core/column',
													array(
														'templateLock' => 'all',
													),
													array(
														array(
															'woocommerce/product-pricing-field',
															array(
																'name' => 'sale_price',
																'label' => __( 'Sale price', 'woocommerce' ),
															),
														),
													),
												),
											),
										),
									),
								),
								array(
									'woocommerce/product-section',
									array(
										'title'       => __( 'Images', 'woocommerce' ),
										'description' => sprintf(
											/* translators: %1$s: Images guide link opening tag. %2$s: Images guide link closing tag.*/
											__( 'Drag images, upload new ones or select files from your library. For best results, use JPEG files that are 1000 by 1000 pixels or larger. %1$sHow to prepare images?%2$s.', 'woocommerce' ),
											'<a href="http://woocommerce.com/#" target="_blank" rel="noreferrer">',
											'</a>'
										),
										'icon'        => array(
											'src' => plugins_url( '/assets/client/admin/product-editor/icons/section_images.svg', WC_PLUGIN_FILE ),
										),
									),
									array(
										array(
											'woocommerce/product-images-field',
											array(
												'images' => array(),
											),
										),
									),
								),
								array(
									'woocommerce/product-section',
									array(
										'title'       => __( 'Organization & visibility', 'woocommerce' ),
										'description' => __( 'Help customers find this product by assigning it to categories or featuring it across your sales channels.', 'woocommerce' ),
									),
									array(
										array(
											'woocommerce/product-category-field',
											array(
												'name' => 'categories',
											),
										),
									),
								),
							),
						),
						array(
							'woocommerce/product-tab',
							array(
								'id'    => 'pricing',
								'title' => __( 'Pricing', 'woocommerce' ),
							),
							array(
								array(
									'woocommerce/product-section',
									array(
										'title'       => __( 'Pricing', 'woocommerce' ),
										'description' => sprintf(
											/* translators: %1$s: Images guide link opening tag. %2$s: Images guide link closing tag.*/
											__( 'Set a competitive price, put the product on sale, and manage tax calculations. %1$sHow to price your product?%2$s', 'woocommerce' ),
											'<a href="https://woocommerce.com/posts/how-to-price-products-strategies-expert-tips/" target="_blank" rel="noreferrer">',
											'</a>'
										),
										'icon'        => array(
											'src' => plugins_url( '/assets/client/admin/product-editor/icons/section_pricing.svg', WC_PLUGIN_FILE ),
										),
									),
									array(
										array(
											'core/columns',
											array(),
											array(
												array(
													'core/column',
													array(
														'templateLock' => 'all',
													),
													array(
														array(
															'woocommerce/product-pricing-field',
															array(
																'name' => 'regular_price',
																'label' => __( 'List price', 'woocommerce' ),
																'showPricingSection' => true,
															),
														),
													),
												),
												array(
													'core/column',
													array(
														'templateLock' => 'all',
													),
													array(
														array(
															'woocommerce/product-pricing-field',
															array(
																'name' => 'sale_price',
																'label' => __( 'Sale price', 'woocommerce' ),
															),
														),
													),
												),
											),
										),
										array(
											'woocommerce/product-schedule-sale-fields',
										),
										array(
											'woocommerce/product-radio-field',
											array(
												'title'    => __( 'Charge sales tax on', 'woocommerce' ),
												'property' => 'tax_status',
												'options'  => array(
													array(
														'label' => __( 'Product and shipping', 'woocommerce' ),
														'value' => 'taxable',
													),
													array(
														'label' => __( 'Only shipping', 'woocommerce' ),
														'value' => 'shipping',
													),
													array(
														'label' => __( "Don't charge tax", 'woocommerce' ),
														'value' => 'none',
													),
												),
											),
										),
										array(
											'woocommerce/product-collapsible',
											array(
												'toggleText'       => __( 'Advanced', 'woocommerce' ),
												'initialCollapsed' => true,
												'persistRender'    => true,
											),
											array(
												array(
													'woocommerce/product-radio-field',
													array(
														'title'    => __( 'Tax class', 'woocommerce' ),
														'description' => sprintf(
															/* translators: %1$s: Learn more link opening tag. %2$s: Learn more link closing tag.*/
															__( 'Apply a tax rate if this product qualifies for tax reduction or exemption. %1$sLearn more%2$s.', 'woocommerce' ),
															'<a href="https://woocommerce.com/document/setting-up-taxes-in-woocommerce/#shipping-tax-class" target="_blank" rel="noreferrer">',
															'</a>'
														),
														'property' => 'tax_class',
														'options'  => array(
															array(
																'label' => __( 'Standard', 'woocommerce' ),
																'value' => '',
															),
															array(
																'label' => __( 'Reduced rate', 'woocommerce' ),
																'value' => 'reduced-rate',
															),
															array(
																'label' => __( 'Zero rate', 'woocommerce' ),
																'value' => 'zero-rate',
															),
														),
													),
												),
											),
										),
									),
								),
							),
						),
						array(
							'woocommerce/product-tab',
							array(
								'id'    => 'inventory',
								'title' => __( 'Inventory', 'woocommerce' ),
							),
							array(
								array(
									'woocommerce/product-section',
									array(
										'title'       => __( 'Inventory', 'woocommerce' ),
										'description' => sprintf(
											/* translators: %1$s: Inventory settings link opening tag. %2$s: Inventory settings link closing tag.*/
											__( 'Set up and manage inventory for this product, including status and available quantity. %1$sManage store inventory settings%2$s', 'woocommerce' ),
											'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory' ) . '" target="_blank" rel="noreferrer">',
											'</a>'
										),
										'icon'        => array(
											'src' => plugins_url( '/assets/client/admin/product-editor/icons/section_inventory.svg', WC_PLUGIN_FILE ),
										),
									),
									array(
										array(
											'woocommerce/product-sku-field',
										),
										array(
											'woocommerce/product-toggle-field',
											array(
												'label'    => __( 'Track stock quantity for this product', 'woocommerce' ),
												'property' => 'manage_stock',
											),
										),
										array(
											'woocommerce/conditional',
											array(
												'mustMatch' => array(
													'manage_stock' => array( true ),
												),
											),
											array(
												array(
													'woocommerce/product-inventory-quantity-field',
												),
											),
										),
										array(
											'woocommerce/conditional',
											array(
												'mustMatch' => array(
													'manage_stock' => array( false ),
												),
											),
											array(
												array(
													'woocommerce/product-radio-field',
													array(
														'title'    => __( 'Stock status', 'woocommerce' ),
														'property' => 'stock_status',
														'options'  => array(
															array(
																'label' => __( 'In stock', 'woocommerce' ),
																'value' => 'instock',
															),
															array(
																'label' => __( 'Out of stock', 'woocommerce' ),
																'value' => 'outofstock',
															),
															array(
																'label' => __( 'On backorder', 'woocommerce' ),
																'value' => 'onbackorder',
															),
														),
													),
												),
											),
										),
										array(
											'woocommerce/product-collapsible',
											array(
												'toggleText'       => __( 'Advanced', 'woocommerce' ),
												'initialCollapsed' => true,
												'persistRender'    => true,
											),
											array(
												array(
													'woocommerce/conditional',
													array(
														'mustMatch' => array(
															'manage_stock' => array( true ),
														),
													),
													array(
														array(
															'woocommerce/product-radio-field',
															array(
																'title'    => __( 'When out of stock', 'woocommerce' ),
																'property' => 'backorders',
																'options'  => array(
																	array(
																		'label' => __( 'Allow purchases', 'woocommerce' ),
																		'value' => 'yes',
																	),
																	array(
																		'label' => __(
																			'Allow purchases, but notify customers',
																			'woocommerce'
																		),
																		'value' => 'notify',
																	),
																	array(
																		'label' => __( "Don't allow purchases", 'woocommerce' ),
																		'value' => 'no',
																	),
																),
															),
														),
														array(
															'woocommerce/product-inventory-email-field',
														),
													),
												),
												array(
													'woocommerce/product-checkbox-field',
													array(
														'title'    => __(
															'Restrictions',
															'woocommerce'
														),
														'label'    => __(
															'Limit purchases to 1 item per order',
															'woocommerce'
														),
														'property' => 'sold_individually',
														'tooltip' => __(
															'When checked, customers will be able to purchase only 1 item in a single order. This is particularly useful for items that have limited quantity, like art or handmade goods.',
															'woocommerce'
														),
													),
												),

											),
										),

									),
								),
							),

						),
						array(
							'woocommerce/product-tab',
							array(
								'id'    => 'shipping',
								'title' => __( 'Shipping', 'woocommerce' ),
							),
							array(
								array(
									'woocommerce/product-section',
									array(
										'title'       => __( 'Fees & dimensions', 'woocommerce' ),
										'description' => sprintf(
											/* translators: %1$s: How to get started? link opening tag. %2$s: How to get started? link closing tag.*/
											__( 'Set up shipping costs and enter dimensions used for accurate rate calculations. %1$sHow to get started?%2$s.', 'woocommerce' ),
											'<a href="https://woocommerce.com/posts/how-to-calculate-shipping-costs-for-your-woocommerce-store/" target="_blank" rel="noreferrer">',
											'</a>'
										),
										'icon'        => array(
											'src' => plugins_url( '/assets/client/admin/product-editor/icons/section_shipping.svg', WC_PLUGIN_FILE ),
										),
									),
									array(
										array(
											'woocommerce/product-shipping-fee-fields',
											array(
												'title' => __( 'Shipping fee', 'woocommerce' ),
											),
										),
										array(
											'woocommerce/product-shipping-dimensions-fields',
										),
									),
								),
							),
						),
					),
					'template_lock'       => 'all',
				)
			)
		);

		register_post_type(
			'product_variation',
			apply_filters(
				'woocommerce_register_post_type_product_variation',
				array(
					'label'           => __( 'Variations', 'woocommerce' ),
					'public'          => false,
					'hierarchical'    => false,
					'supports'        => false,
					'capability_type' => 'product',
					'rewrite'         => false,
				)
			)
		);

		wc_register_order_type(
			'shop_order',
			apply_filters(
				'woocommerce_register_post_type_shop_order',
				array(
					'labels'              => array(
						'name'                  => __( 'Orders', 'woocommerce' ),
						'singular_name'         => _x( 'Order', 'shop_order post type singular name', 'woocommerce' ),
						'add_new'               => __( 'Add order', 'woocommerce' ),
						'add_new_item'          => __( 'Add new order', 'woocommerce' ),
						'edit'                  => __( 'Edit', 'woocommerce' ),
						'edit_item'             => __( 'Edit order', 'woocommerce' ),
						'new_item'              => __( 'New order', 'woocommerce' ),
						'view_item'             => __( 'View order', 'woocommerce' ),
						'search_items'          => __( 'Search orders', 'woocommerce' ),
						'not_found'             => __( 'No orders found', 'woocommerce' ),
						'not_found_in_trash'    => __( 'No orders found in trash', 'woocommerce' ),
						'parent'                => __( 'Parent orders', 'woocommerce' ),
						'menu_name'             => _x( 'Orders', 'Admin menu name', 'woocommerce' ),
						'filter_items_list'     => __( 'Filter orders', 'woocommerce' ),
						'items_list_navigation' => __( 'Orders navigation', 'woocommerce' ),
						'items_list'            => __( 'Orders list', 'woocommerce' ),
					),
					'description'         => __( 'This is where store orders are stored.', 'woocommerce' ),
					'public'              => false,
					'show_ui'             => true,
					'capability_type'     => 'shop_order',
					'map_meta_cap'        => true,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'show_in_menu'        => current_user_can( 'edit_others_shop_orders' ) ? 'woocommerce' : true,
					'hierarchical'        => false,
					'show_in_nav_menus'   => false,
					'rewrite'             => false,
					'query_var'           => false,
					'supports'            => array( 'title', 'comments', 'custom-fields' ),
					'has_archive'         => false,
				)
			)
		);

		wc_register_order_type(
			'shop_order_refund',
			apply_filters(
				'woocommerce_register_post_type_shop_order_refund',
				array(
					'label'                            => __( 'Refunds', 'woocommerce' ),
					'capability_type'                  => 'shop_order',
					'public'                           => false,
					'hierarchical'                     => false,
					'supports'                         => false,
					'exclude_from_orders_screen'       => false,
					'add_order_meta_boxes'             => false,
					'exclude_from_order_count'         => true,
					'exclude_from_order_views'         => false,
					'exclude_from_order_reports'       => false,
					'exclude_from_order_sales_reports' => true,
					'class_name'                       => 'WC_Order_Refund',
					'rewrite'                          => false,
				)
			)
		);

		if ( 'yes' === get_option( 'woocommerce_enable_coupons' ) ) {
			register_post_type(
				'shop_coupon',
				apply_filters(
					'woocommerce_register_post_type_shop_coupon',
					array(
						'labels'              => array(
							'name'                  => __( 'Coupons', 'woocommerce' ),
							'singular_name'         => __( 'Coupon', 'woocommerce' ),
							'menu_name'             => _x( 'Coupons', 'Admin menu name', 'woocommerce' ),
							'add_new'               => __( 'Add coupon', 'woocommerce' ),
							'add_new_item'          => __( 'Add new coupon', 'woocommerce' ),
							'edit'                  => __( 'Edit', 'woocommerce' ),
							'edit_item'             => __( 'Edit coupon', 'woocommerce' ),
							'new_item'              => __( 'New coupon', 'woocommerce' ),
							'view_item'             => __( 'View coupon', 'woocommerce' ),
							'search_items'          => __( 'Search coupons', 'woocommerce' ),
							'not_found'             => __( 'No coupons found', 'woocommerce' ),
							'not_found_in_trash'    => __( 'No coupons found in trash', 'woocommerce' ),
							'parent'                => __( 'Parent coupon', 'woocommerce' ),
							'filter_items_list'     => __( 'Filter coupons', 'woocommerce' ),
							'items_list_navigation' => __( 'Coupons navigation', 'woocommerce' ),
							'items_list'            => __( 'Coupons list', 'woocommerce' ),
						),
						'description'         => __( 'This is where you can add new coupons that customers can use in your store.', 'woocommerce' ),
						'public'              => false,
						'show_ui'             => true,
						'capability_type'     => 'shop_coupon',
						'map_meta_cap'        => true,
						'publicly_queryable'  => false,
						'exclude_from_search' => true,
						'show_in_menu'        => current_user_can( 'edit_others_shop_orders' ) ? 'woocommerce' : true,
						'hierarchical'        => false,
						'rewrite'             => false,
						'query_var'           => false,
						'supports'            => array( 'title' ),
						'show_in_nav_menus'   => false,
						'show_in_admin_bar'   => true,
					)
				)
			);
		}

		do_action( 'woocommerce_after_register_post_type' );
	}

	/**
	 * Customize taxonomies update messages.
	 *
	 * @param array $messages The list of available messages.
	 * @since 4.4.0
	 * @return bool
	 */
	public static function updated_term_messages( $messages ) {
		$messages['product_cat'] = array(
			0 => '',
			1 => __( 'Category added.', 'woocommerce' ),
			2 => __( 'Category deleted.', 'woocommerce' ),
			3 => __( 'Category updated.', 'woocommerce' ),
			4 => __( 'Category not added.', 'woocommerce' ),
			5 => __( 'Category not updated.', 'woocommerce' ),
			6 => __( 'Categories deleted.', 'woocommerce' ),
		);

		$messages['product_tag'] = array(
			0 => '',
			1 => __( 'Tag added.', 'woocommerce' ),
			2 => __( 'Tag deleted.', 'woocommerce' ),
			3 => __( 'Tag updated.', 'woocommerce' ),
			4 => __( 'Tag not added.', 'woocommerce' ),
			5 => __( 'Tag not updated.', 'woocommerce' ),
			6 => __( 'Tags deleted.', 'woocommerce' ),
		);

		$wc_product_attributes = array();
		$attribute_taxonomies  = wc_get_attribute_taxonomies();

		if ( $attribute_taxonomies ) {
			foreach ( $attribute_taxonomies as $tax ) {
				$name = wc_attribute_taxonomy_name( $tax->attribute_name );

				if ( $name ) {
					$label = ! empty( $tax->attribute_label ) ? $tax->attribute_label : $tax->attribute_name;

					$messages[ $name ] = array(
						0 => '',
						/* translators: %s: taxonomy label */
						1 => sprintf( _x( '%s added', 'taxonomy term messages', 'woocommerce' ), $label ),
						/* translators: %s: taxonomy label */
						2 => sprintf( _x( '%s deleted', 'taxonomy term messages', 'woocommerce' ), $label ),
						/* translators: %s: taxonomy label */
						3 => sprintf( _x( '%s updated', 'taxonomy term messages', 'woocommerce' ), $label ),
						/* translators: %s: taxonomy label */
						4 => sprintf( _x( '%s not added', 'taxonomy term messages', 'woocommerce' ), $label ),
						/* translators: %s: taxonomy label */
						5 => sprintf( _x( '%s not updated', 'taxonomy term messages', 'woocommerce' ), $label ),
						/* translators: %s: taxonomy label */
						6 => sprintf( _x( '%s deleted', 'taxonomy term messages', 'woocommerce' ), $label ),
					);
				}
			}
		}

		return $messages;
	}

	/**
	 * Register our custom post statuses, used for order status.
	 */
	public static function register_post_status() {

		$order_statuses = apply_filters(
			'woocommerce_register_shop_order_post_statuses',
			array(
				'wc-pending'    => array(
					'label'                     => _x( 'Pending payment', 'Order status', 'woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of orders */
					'label_count'               => _n_noop( 'Pending payment <span class="count">(%s)</span>', 'Pending payment <span class="count">(%s)</span>', 'woocommerce' ),
				),
				'wc-processing' => array(
					'label'                     => _x( 'Processing', 'Order status', 'woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of orders */
					'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>', 'woocommerce' ),
				),
				'wc-on-hold'    => array(
					'label'                     => _x( 'On hold', 'Order status', 'woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of orders */
					'label_count'               => _n_noop( 'On hold <span class="count">(%s)</span>', 'On hold <span class="count">(%s)</span>', 'woocommerce' ),
				),
				'wc-completed'  => array(
					'label'                     => _x( 'Completed', 'Order status', 'woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of orders */
					'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'woocommerce' ),
				),
				'wc-cancelled'  => array(
					'label'                     => _x( 'Cancelled', 'Order status', 'woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of orders */
					'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'woocommerce' ),
				),
				'wc-refunded'   => array(
					'label'                     => _x( 'Refunded', 'Order status', 'woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of orders */
					'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'woocommerce' ),
				),
				'wc-failed'     => array(
					'label'                     => _x( 'Failed', 'Order status', 'woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of orders */
					'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'woocommerce' ),
				),
				'auto-draft'    => array(
					'label'                     => _x( 'Auto Draft', 'Order status', 'woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of orders */
					'label_count'               => _n_noop( 'Auto draft <span class="count">(%s)</span>', 'Auto draft <span class="count">(%s)</span>', 'woocommerce' ),
				),
			)
		);

		foreach ( $order_statuses as $order_status => $values ) {
			register_post_status( $order_status, $values );
		}
	}

	/**
	 * Flush rules if the event is queued.
	 *
	 * @since 3.3.0
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( 'yes' === get_option( 'woocommerce_queue_flush_rewrite_rules' ) ) {
			update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
			self::flush_rewrite_rules();
		}
	}

	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	/**
	 * Disable Gutenberg for products.
	 *
	 * @param bool   $can_edit Whether the post type can be edited or not.
	 * @param string $post_type The post type being checked.
	 * @return bool
	 */
	public static function gutenberg_can_edit_post_type( $can_edit, $post_type ) {
		return 'product' === $post_type ? false : $can_edit;
	}

	/**
	 * Add Product Support to Jetpack Omnisearch.
	 */
	public static function support_jetpack_omnisearch() {
		if ( class_exists( 'Jetpack_Omnisearch_Posts' ) ) {
			new Jetpack_Omnisearch_Posts( 'product' );
		}
	}

	/**
	 * Added product for Jetpack related posts.
	 *
	 * @param  array $post_types Post types.
	 * @return array
	 */
	public static function rest_api_allowed_post_types( $post_types ) {
		$post_types[] = 'product';

		return $post_types;
	}
}

WC_Post_types::init();
