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
		$wp_customize->add_section(
			'woocommerce_display_settings',
			array(
				'title'       => __( 'WooCommerce', 'woocommerce' ),
				'description' => __( 'These settings control how some parts of WooCommerce appear in your store.', 'woocommerce' ),
				'priority'    => 140,
				'active_callback' => 'is_woocommerce',
			)
		);

		/**
		 * Store Notice settings.
		 */
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
				'description' => __( "If enabled, this will be shown site-wide. It can be used to inform visitors about store events and promotions.", 'woocommerce' ),
				'section'     => 'woocommerce_display_settings',
				'settings'    => 'woocommerce_demo_store_notice',
				'type'        => 'textarea',
			)
		);

		$wp_customize->add_control(
			'woocommerce_demo_store',
			array(
				'label'       => __( 'Enable store notice', 'woocommerce' ),
				'section'     => 'woocommerce_display_settings',
				'settings'    => 'woocommerce_demo_store',
				'type'        => 'checkbox',
			)
		);

		/**
		 * Grid and image settings.
		 */
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
				'section'     => 'woocommerce_display_settings',
				'settings'    => 'single_image_width',
				'type'        => 'number',
				'input_attrs' => array( 'min' => 0, 'step'  => 1 ),
			)
		);

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
				'section'     => 'woocommerce_display_settings',
				'settings'    => 'thumbnail_image_width',
				'type'        => 'number',
				'input_attrs' => array( 'min' => 0, 'step'  => 1 ),
			)
		);

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
					'section'  => 'woocommerce_display_settings',
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
}

new WC_Customizer();
