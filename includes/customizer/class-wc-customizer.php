<?php
/**
 * Adds options to the customizer for WooCommerce.
 *
 * @version 3.3.0
 * @package WooCommerce
 * @author  WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Customizer class.
 */
class WC_Customizer {

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
		$this->add_product_grid_section( $wp_customize );
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
			} );
		</script>
		<?php
	}

	/**
	 * Should our settings show?
	 *
	 * @return boolean
	 */
	public function is_active() {
		return is_woocommerce() || wc_post_content_has_shortcode( 'products' ) || ! current_theme_supports( 'woocommerce' );
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
				'label'       => __( 'Enable store notice', 'woocommerce' ),
				'section'     => 'woocommerce_store_notice',
				'settings'    => 'woocommerce_demo_store',
				'type'        => 'checkbox',
			)
		);
	}

	/**
	 * Product grid section.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function add_product_grid_section( $wp_customize ) {
		if ( has_filter( 'loop_shop_columns' ) ) {
			return;
		}
		$theme_support = get_theme_support( 'woocommerce' );
		$theme_support = is_array( $theme_support ) ? $theme_support[0]: false;

		$wp_customize->add_section(
			'woocommerce_product_grid',
			array(
				'title'           => __( 'Product Grid', 'woocommerce' ),
				'priority'        => 10,
				'active_callback' => array( $this, 'is_active' ),
				'panel'           => 'woocommerce',
			)
		);

		$wp_customize->add_setting(
			'woocommerce_catalog_columns',
			array(
				'default'              => 3,
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
				'section'     => 'woocommerce_product_grid',
				'settings'    => 'woocommerce_catalog_columns',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => isset( $theme_support['product_grid']['min_columns'] ) ? absint( $theme_support['product_grid']['min_columns'] ) : 1,
					'max'  => isset( $theme_support['product_grid']['max_columns'] ) ? absint( $theme_support['product_grid']['max_columns'] ) : '',
					'step' => 1,
				),
			)
		);

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

		$wp_customize->add_control(
			'woocommerce_catalog_rows',
			array(
				'label'       => __( 'Rows per page', 'woocommerce' ),
				'description' => __( 'How many rows of products should be shown per page?', 'woocommerce' ),
				'section'     => 'woocommerce_product_grid',
				'settings'    => 'woocommerce_catalog_rows',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => isset( $theme_support['product_grid']['min_rows'] ) ? absint( $theme_support['product_grid']['min_rows'] ): 1,
					'max'  => isset( $theme_support['product_grid']['max_rows'] ) ? absint( $theme_support['product_grid']['max_rows'] ): '',
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
		$theme_support = get_theme_support( 'woocommerce' );
		$theme_support = is_array( $theme_support ) ? $theme_support[0]: false;

		$wp_customize->add_section(
			'woocommerce_product_images',
			array(
				'title'           => __( 'Product Images', 'woocommerce' ),
				'priority'        => 20,
				'active_callback' => array( $this, 'is_active' ),
				'panel'           => 'woocommerce',
			)
		);

		if ( ! isset( $theme_support['single_image_width'] ) ) {
			$wp_customize->add_setting(
				'single_image_width',
				array(
					'default'              => 600,
					'type'                 => 'option',
					'capability'           => 'manage_woocommerce',
					'sanitize_callback'    => 'absint',
					'sanitize_js_callback' => 'absint',
				)
			);

			$wp_customize->add_control(
				'single_image_width',
				array(
					'label'       => __( 'Main image width', 'woocommerce' ),
					'description' => __( 'This is the width used by the main image on single product pages. These images will remain uncropped.', 'woocommerce' ),
					'section'     => 'woocommerce_product_images',
					'settings'    => 'single_image_width',
					'type'        => 'number',
					'input_attrs' => array(
						'min'  => 0,
						'step' => 1,
					),
				)
			);
		}

		if ( ! isset( $theme_support['thumbnail_image_width'] ) ) {
			$wp_customize->add_setting(
				'thumbnail_image_width',
				array(
					'default'              => 300,
					'type'                 => 'option',
					'capability'           => 'manage_woocommerce',
					'sanitize_callback'    => 'absint',
					'sanitize_js_callback' => 'absint',
				)
			);

			$wp_customize->add_control(
				'thumbnail_image_width',
				array(
					'label'       => __( 'Thumbnail width', 'woocommerce' ),
					'description' => __( 'This size is used for product archives and product listings.', 'woocommerce' ),
					'section'     => 'woocommerce_product_images',
					'settings'    => 'thumbnail_image_width',
					'type'        => 'number',
					'input_attrs' => array( 'min' => 0, 'step'  => 1 ),
				)
			);
		}

		include_once( WC_ABSPATH . 'includes/customizer/class-wc-customizer-control-cropping.php' );

		$wp_customize->add_setting(
			'woocommerce_thumbnail_cropping',
			array(
				'default'              => '1:1',
				'type'                 => 'option',
				'capability'           => 'manage_woocommerce',
				'sanitize_callback'    => 'wc_clean',
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
						'1:1'             => array(
							'label'       => __( '1:1', 'woocommerce' ),
							'description' => __( 'Images will be cropped into a square', 'woocommerce' ),
						),
						'custom'          => array(
							'label'       => __( 'Custom', 'woocommerce' ),
							'description' => __( 'Images will be cropped to a custom aspect ratio', 'woocommerce' ),
						),
						'uncropped'       => array(
							'label'       => __( 'Uncropped', 'woocommerce' ),
							'description' => __( 'Images will display using the aspect ratio in which they were uploaded', 'woocommerce' ),
						),
					),
				)
			)
		);
	}
}

new WC_Customizer();
