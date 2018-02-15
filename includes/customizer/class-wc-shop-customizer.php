<?php
/**
 * Adds options to the customizer for WooCommerce.
 *
 * @version 3.3.0
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Shop_Customizer class.
 */
class WC_Shop_Customizer {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'customize_register', array( $this, 'add_sections' ) );
		add_action( 'customize_controls_print_styles', array( $this, 'add_styles' ) );
		add_action( 'customize_controls_print_scripts', array( $this, 'add_scripts' ), 30 );
	}

	/**
	 * Add settings to the customizer.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function add_sections( $wp_customize ) {
		$wp_customize->add_panel( 'woocommerce', array(
			'priority'       => 200,
			'capability'     => 'manage_woocommerce',
			'theme_supports' => '',
			'title'          => __( 'WooCommerce', 'woocommerce' ),
		) );

		$this->add_store_notice_section( $wp_customize );
		$this->add_product_catalog_section( $wp_customize );
		$this->add_product_images_section( $wp_customize );
	}

	/**
	 * CSS styles to improve our form.
	 */
	public function add_styles() {
		?>
		<style type="text/css">
			.woocommerce-cropping-control {
				margin: 0 40px 1em 0;
				padding: 0;
				display:inline-block;
				vertical-align: top;
			}

			.woocommerce-cropping-control input[type=radio] {
				margin-top: 1px;
			}

			.woocommerce-cropping-control span.woocommerce-cropping-control-aspect-ratio {
				margin-top: .5em;
				display:block;
			}

			.woocommerce-cropping-control span.woocommerce-cropping-control-aspect-ratio input {
				width: auto;
				display: inline-block;
			}
		</style>
		<?php
	}

	/**
	 * Scripts to improve our form.
	 */
	public function add_scripts() {
		$min_rows    = wc_get_theme_support( 'product_grid::min_rows', 1 );
		$max_rows    = wc_get_theme_support( 'product_grid::max_rows', '' );
		$min_columns = wc_get_theme_support( 'product_grid::min_columns', 1 );
		$max_columns = wc_get_theme_support( 'product_grid::max_columns', '' );

		/* translators: %d: Setting value */
		$min_notice = __( 'The minimum allowed setting is %d', 'woocommerce' );
		/* translators: %d: Setting value */
		$max_notice = __( 'The maximum allowed setting is %d', 'woocommerce' );
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$( document.body ).on( 'change', '.woocommerce-cropping-control input[type="radio"]', function() {
					var $wrapper = $( this ).closest( '.woocommerce-cropping-control' ),
						value    = $wrapper.find( 'input:checked' ).val();

					if ( 'custom' === value ) {
						$wrapper.find( '.woocommerce-cropping-control-aspect-ratio' ).slideDown( 200 );
					} else {
						$wrapper.find( '.woocommerce-cropping-control-aspect-ratio' ).hide();
					}

					return false;
				} );

				wp.customize.bind( 'ready', function() { // Ready?
					$( '.woocommerce-cropping-control' ).find( 'input:checked' ).change();
				} );

				wp.customize.section( 'woocommerce_product_catalog', function( section ) {
					section.expanded.bind( function( isExpanded ) {
						if ( isExpanded ) {
							wp.customize.previewer.previewUrl.set( '<?php echo esc_js( wc_get_page_permalink( 'shop' ) ); ?>' );
						}
					} );
				} );

				wp.customize.section( 'woocommerce_product_images', function( section ) {
					section.expanded.bind( function( isExpanded ) {
						if ( isExpanded ) {
							wp.customize.previewer.previewUrl.set( '<?php echo esc_js( wc_get_page_permalink( 'shop' ) ); ?>' );
						}
					} );
				} );

				wp.customize( 'woocommerce_catalog_columns', function( setting ) {
					setting.bind( function( value ) {
						var min = parseInt( '<?php echo esc_js( $min_columns ); ?>', 10 );
						var max = parseInt( '<?php echo esc_js( $max_columns ); ?>', 10 );

						value = parseInt( value, 10 );

						if ( max && value > max ) {
							setting.notifications.add( 'max_columns_error', new wp.customize.Notification(
								'max_columns_error',
								{
									type   : 'error',
									message: '<?php echo esc_js( sprintf( $max_notice, $max_columns ) ); ?>'
								}
							) );
						} else {
							setting.notifications.remove( 'max_columns_error' );
						}

						if ( min && value < min ) {
							setting.notifications.add( 'min_columns_error', new wp.customize.Notification(
								'min_columns_error',
								{
									type   : 'error',
									message: '<?php echo esc_js( sprintf( $min_notice, $min_columns ) ); ?>'
								}
							) );
						} else {
							setting.notifications.remove( 'min_columns_error' );
						}
					} );
				} );

				wp.customize( 'woocommerce_catalog_rows', function( setting ) {
					setting.bind( function( value ) {
						var min = parseInt( '<?php echo esc_js( $min_rows ); ?>', 10 );
						var max = parseInt( '<?php echo esc_js( $max_rows ); ?>', 10 );

						value = parseInt( value, 10 );

						if ( max && value > max ) {
							setting.notifications.add( 'max_rows_error', new wp.customize.Notification(
								'max_rows_error',
								{
									type   : 'error',
									message: '<?php echo esc_js( sprintf( $min_notice, $max_rows ) ); ?>'
								}
							) );
						} else {
							setting.notifications.remove( 'max_rows_error' );
						}

						if ( min && value < min ) {
							setting.notifications.add( 'min_rows_error', new wp.customize.Notification(
								'min_rows_error',
								{
									type   : 'error',
									message: '<?php echo esc_js( sprintf( $min_notice, $min_rows ) ); ?>'
								}
							) );
						} else {
							setting.notifications.remove( 'min_rows_error' );
						}
					} );
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Sanitize the shop page & category display setting.
	 *
	 * @param string $value '', 'subcategories', or 'both'.
	 * @return string
	 */
	public function sanitize_archive_display( $value ) {
		$options = array( '', 'subcategories', 'both' );

		return in_array( $value, $options, true ) ? $value : '';
	}

	/**
	 * Sanitize the catalog orderby setting.
	 *
	 * @param string $value An array key from the below array.
	 * @return string
	 */
	public function sanitize_default_catalog_orderby( $value ) {
		$options = apply_filters( 'woocommerce_default_catalog_orderby_options', array(
			'menu_order' => __( 'Default sorting (custom ordering + name)', 'woocommerce' ),
			'popularity' => __( 'Popularity (sales)', 'woocommerce' ),
			'rating'     => __( 'Average rating', 'woocommerce' ),
			'date'       => __( 'Sort by most recent', 'woocommerce' ),
			'price'      => __( 'Sort by price (asc)', 'woocommerce' ),
			'price-desc' => __( 'Sort by price (desc)', 'woocommerce' ),
		) );

		return array_key_exists( $value, $options ) ? $value : 'menu_order';
	}

	/**
	 * Store notice section.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	private function add_store_notice_section( $wp_customize ) {
		$wp_customize->add_section(
			'woocommerce_store_notice',
			array(
				'title'    => __( 'Store Notice', 'woocommerce' ),
				'priority' => 10,
				'panel'    => 'woocommerce',
			)
		);

		$wp_customize->add_setting(
			'woocommerce_demo_store',
			array(
				'default'              => 'no',
				'type'                 => 'option',
				'capability'           => 'manage_woocommerce',
				'sanitize_callback'    => 'wc_bool_to_string',
				'sanitize_js_callback' => 'wc_string_to_bool',
			)
		);

		$wp_customize->add_setting(
			'woocommerce_demo_store_notice',
			array(
				'default'           => __( 'This is a demo store for testing purposes &mdash; no orders shall be fulfilled.', 'woocommerce' ),
				'type'              => 'option',
				'capability'        => 'manage_woocommerce',
				'sanitize_callback' => 'wp_kses_post',
			)
		);

		$wp_customize->add_control(
			'woocommerce_demo_store_notice',
			array(
				'label'       => __( 'Store notice', 'woocommerce' ),
				'description' => __( 'If enabled, this text will be shown site-wide. You can use it to show events or promotions to visitors!', 'woocommerce' ),
				'section'     => 'woocommerce_store_notice',
				'settings'    => 'woocommerce_demo_store_notice',
				'type'        => 'textarea',
			)
		);

		$wp_customize->add_control(
			'woocommerce_demo_store',
			array(
				'label'    => __( 'Enable store notice', 'woocommerce' ),
				'section'  => 'woocommerce_store_notice',
				'settings' => 'woocommerce_demo_store',
				'type'     => 'checkbox',
			)
		);
	}

	/**
	 * Product catalog section.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function add_product_catalog_section( $wp_customize ) {
		$wp_customize->add_section(
			'woocommerce_product_catalog',
			array(
				'title'    => __( 'Product Catalog', 'woocommerce' ),
				'priority' => 10,
				'panel'    => 'woocommerce',
			)
		);

		$wp_customize->add_setting(
			'woocommerce_shop_page_display',
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_woocommerce',
				'sanitize_callback' => array( $this, 'sanitize_archive_display' ),
			)
		);

		$wp_customize->add_control(
			'woocommerce_shop_page_display',
			array(
				'label'       => __( 'Shop page display', 'woocommerce' ),
				'description' => __( 'Choose what to display on the main shop page.', 'woocommerce' ),
				'section'     => 'woocommerce_product_catalog',
				'settings'    => 'woocommerce_shop_page_display',
				'type'        => 'select',
				'choices'     => array(
					''              => __( 'Show products', 'woocommerce' ),
					'subcategories' => __( 'Show categories', 'woocommerce' ),
					'both'          => __( 'Show categories &amp; products', 'woocommerce' ),
				),
			)
		);

		$wp_customize->add_setting(
			'woocommerce_category_archive_display',
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_woocommerce',
				'sanitize_callback' => array( $this, 'sanitize_archive_display' ),
			)
		);

		$wp_customize->add_control(
			'woocommerce_category_archive_display',
			array(
				'label'       => __( 'Category display', 'woocommerce' ),
				'description' => __( 'Choose what to display on product category pages.', 'woocommerce' ),
				'section'     => 'woocommerce_product_catalog',
				'settings'    => 'woocommerce_category_archive_display',
				'type'        => 'select',
				'choices'     => array(
					''              => __( 'Show products', 'woocommerce' ),
					'subcategories' => __( 'Show subcategories', 'woocommerce' ),
					'both'          => __( 'Show subcategories &amp; products', 'woocommerce' ),
				),
			)
		);

		$wp_customize->add_setting(
			'woocommerce_default_catalog_orderby',
			array(
				'default'           => 'menu_order',
				'type'              => 'option',
				'capability'        => 'manage_woocommerce',
				'sanitize_callback' => array( $this, 'sanitize_default_catalog_orderby' ),
			)
		);

		$wp_customize->add_control(
			'woocommerce_default_catalog_orderby',
			array(
				'label'       => __( 'Default product sorting', 'woocommerce' ),
				'description' => __( 'How should products be sorted in the catalog by default?', 'woocommerce' ),
				'section'     => 'woocommerce_product_catalog',
				'settings'    => 'woocommerce_default_catalog_orderby',
				'type'        => 'select',
				'choices'     => apply_filters( 'woocommerce_default_catalog_orderby_options', array(
					'menu_order' => __( 'Default sorting (custom ordering + name)', 'woocommerce' ),
					'popularity' => __( 'Popularity (sales)', 'woocommerce' ),
					'rating'     => __( 'Average rating', 'woocommerce' ),
					'date'       => __( 'Sort by most recent', 'woocommerce' ),
					'price'      => __( 'Sort by price (asc)', 'woocommerce' ),
					'price-desc' => __( 'Sort by price (desc)', 'woocommerce' ),
				) ),
			)
		);

		// The following settings should be hidden if the theme is declaring the values.
		if ( has_filter( 'loop_shop_columns' ) ) {
			return;
		}

		$wp_customize->add_setting(
			'woocommerce_catalog_columns',
			array(
				'default'              => 4,
				'type'                 => 'option',
				'capability'           => 'manage_woocommerce',
				'sanitize_callback'    => 'absint',
				'sanitize_js_callback' => 'absint',
			)
		);

		$wp_customize->add_control(
			'woocommerce_catalog_columns',
			array(
				'label'       => __( 'Products per row', 'woocommerce' ),
				'description' => __( 'How many products should be shown per row?', 'woocommerce' ),
				'section'     => 'woocommerce_product_catalog',
				'settings'    => 'woocommerce_catalog_columns',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => wc_get_theme_support( 'product_grid::min_columns', 1 ),
					'max'  => wc_get_theme_support( 'product_grid::max_columns', '' ),
					'step' => 1,
				),
			)
		);

		// Only add this setting if something else isn't managing the number of products per page.
		if ( ! has_filter( 'loop_shop_per_page' ) ) {
			$wp_customize->add_setting(
				'woocommerce_catalog_rows',
				array(
					'default'              => 4,
					'type'                 => 'option',
					'capability'           => 'manage_woocommerce',
					'sanitize_callback'    => 'absint',
					'sanitize_js_callback' => 'absint',
				)
			);
		}

		$wp_customize->add_control(
			'woocommerce_catalog_rows',
			array(
				'label'       => __( 'Rows per page', 'woocommerce' ),
				'description' => __( 'How many rows of products should be shown per page?', 'woocommerce' ),
				'section'     => 'woocommerce_product_catalog',
				'settings'    => 'woocommerce_catalog_rows',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => wc_get_theme_support( 'product_grid::min_rows', 1 ),
					'max'  => wc_get_theme_support( 'product_grid::max_rows', '' ),
					'step' => 1,
				),
			)
		);
	}

	/**
	 * Product images section.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	private function add_product_images_section( $wp_customize ) {
		if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) {
			$regen_description = ''; // Nothing to report; Jetpack will handle magically.
		} elseif ( apply_filters( 'woocommerce_background_image_regeneration', true ) && ! is_multisite() ) {
			$regen_description = __( 'After publishing your changes, new image sizes will be generated automatically.', 'woocommerce' );
		} elseif ( apply_filters( 'woocommerce_background_image_regeneration', true ) && is_multisite() ) {
			$regen_description = sprintf( __( 'After publishing your changes, new image sizes may not be shown until you regenerate thumbnails. You can do this from the <a href="%1$s" target="_blank">tools section in WooCommerce</a> or by using a plugin such as <a href="%2$s" target="_blank">Regenerate Thumbnails</a>.', 'woocommerce' ), admin_url( 'admin.php?page=wc-status&tab=tools' ), 'https://en-gb.wordpress.org/plugins/regenerate-thumbnails/' );
		} else {
			$regen_description = sprintf( __( 'After publishing your changes, new image sizes may not be shown until you <a href="%2$s" target="_blank">Regenerate Thumbnails</a>.', 'woocommerce' ), 'https://en-gb.wordpress.org/plugins/regenerate-thumbnails/' );
		}

		$wp_customize->add_section(
			'woocommerce_product_images',
			array(
				'title'       => __( 'Product Images', 'woocommerce' ),
				'description' => $regen_description,
				'priority'    => 20,
				'panel'       => 'woocommerce',
			)
		);

		if ( ! wc_get_theme_support( 'single_image_width' ) ) {
			$wp_customize->add_setting(
				'woocommerce_single_image_width',
				array(
					'default'              => 600,
					'type'                 => 'option',
					'capability'           => 'manage_woocommerce',
					'sanitize_callback'    => 'absint',
					'sanitize_js_callback' => 'absint',
				)
			);

			$wp_customize->add_control(
				'woocommerce_single_image_width',
				array(
					'label'       => __( 'Main image width', 'woocommerce' ),
					'description' => __( 'Image size used for the main image on single product pages. These images will remain uncropped.', 'woocommerce' ),
					'section'     => 'woocommerce_product_images',
					'settings'    => 'woocommerce_single_image_width',
					'type'        => 'number',
					'input_attrs' => array(
						'min'  => 0,
						'step' => 1,
					),
				)
			);
		}

		if ( ! wc_get_theme_support( 'thumbnail_image_width' ) ) {
			$wp_customize->add_setting(
				'woocommerce_thumbnail_image_width',
				array(
					'default'              => 300,
					'type'                 => 'option',
					'capability'           => 'manage_woocommerce',
					'sanitize_callback'    => 'absint',
					'sanitize_js_callback' => 'absint',
				)
			);

			$wp_customize->add_control(
				'woocommerce_thumbnail_image_width',
				array(
					'label'       => __( 'Thumbnail width', 'woocommerce' ),
					'description' => __( 'Image size used for products in the catalog.', 'woocommerce' ),
					'section'     => 'woocommerce_product_images',
					'settings'    => 'woocommerce_thumbnail_image_width',
					'type'        => 'number',
					'input_attrs' => array(
						'min'  => 0,
						'step' => 1,
					),
				)
			);
		}

		include_once WC_ABSPATH . 'includes/customizer/class-wc-customizer-control-cropping.php';

		$wp_customize->add_setting(
			'woocommerce_thumbnail_cropping',
			array(
				'default'           => '1:1',
				'type'              => 'option',
				'capability'        => 'manage_woocommerce',
				'sanitize_callback' => 'wc_clean',
			)
		);

		$wp_customize->add_setting(
			'woocommerce_thumbnail_cropping_custom_width',
			array(
				'default'              => '4',
				'type'                 => 'option',
				'capability'           => 'manage_woocommerce',
				'sanitize_callback'    => 'absint',
				'sanitize_js_callback' => 'absint',
			)
		);

		$wp_customize->add_setting(
			'woocommerce_thumbnail_cropping_custom_height',
			array(
				'default'              => '3',
				'type'                 => 'option',
				'capability'           => 'manage_woocommerce',
				'sanitize_callback'    => 'absint',
				'sanitize_js_callback' => 'absint',
			)
		);

		$wp_customize->add_control(
			new WC_Customizer_Control_Cropping(
				$wp_customize,
				'woocommerce_thumbnail_cropping',
				array(
					'section'  => 'woocommerce_product_images',
					'settings' => array(
						'cropping'      => 'woocommerce_thumbnail_cropping',
						'custom_width'  => 'woocommerce_thumbnail_cropping_custom_width',
						'custom_height' => 'woocommerce_thumbnail_cropping_custom_height',
					),
					'label'    => __( 'Thumbnail cropping', 'woocommerce' ),
					'choices'  => array(
						'1:1'       => array(
							'label'       => __( '1:1', 'woocommerce' ),
							'description' => __( 'Images will be cropped into a square', 'woocommerce' ),
						),
						'custom'    => array(
							'label'       => __( 'Custom', 'woocommerce' ),
							'description' => __( 'Images will be cropped to a custom aspect ratio', 'woocommerce' ),
						),
						'uncropped' => array(
							'label'       => __( 'Uncropped', 'woocommerce' ),
							'description' => __( 'Images will display using the aspect ratio in which they were uploaded', 'woocommerce' ),
						),
					),
				)
			)
		);
	}
}

new WC_Shop_Customizer();
