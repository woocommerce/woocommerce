<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Twenty Seventeen support.
 *
 * @class   WC_Twenty_Seventeen
 * @since   2.6.9
 * @version 2.6.9
 * @package WooCommerce/Classes
 */
class WC_Twenty_Seventeen {

	/**
	 * Theme init.
	 */
	public static function init() {
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

		add_action( 'woocommerce_before_main_content', array( __CLASS__, 'output_content_wrapper' ), 10 );
		add_action( 'woocommerce_after_main_content', array( __CLASS__, 'output_content_wrapper_end' ), 10 );
		add_filter( 'woocommerce_enqueue_styles', array( __CLASS__, 'enqueue_styles' ) );
		add_filter( 'twentyseventeen_custom_colors_css', array( __CLASS__, 'custom_colors_css' ), 10, 3 );
	}

	/**
	 * Enqueue CSS for this theme.
	 *
	 * @param  array $styles
	 * @return array
	 */
	public static function enqueue_styles( $styles ) {
		unset( $styles['woocommerce-general'] );

		$styles['woocommerce-twenty-seventeen'] = array(
			'src'     => str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/css/twenty-seventeen.css',
			'deps'    => '',
			'version' => WC_VERSION,
			'media'   => 'all',
		);

		return apply_filters( 'woocommerce_twenty_seventeen_styles', $styles );
	}

	/**
	 * Open the Twenty Seventeen wrapper.
	 */
	public static function output_content_wrapper() { ?>
		<div class="wrap">
			<div id="primary" class="content-area twentyseventeen">
				<main id="main" class="site-main" role="main">
		<?php
	}

	/**
	 * Close the Twenty Seventeen wrapper.
	 */
	public static function output_content_wrapper_end() { ?>
				</main>
			</div>
			<?php get_sidebar(); ?>
		</div>
		<?php
	}

	/**
	 * Custom colors.
	 *
	 * @param  string $css
	 * @param  string $hue
	 * @param  string $saturation
	 * @return string
	 */
	public static function custom_colors_css( $css, $hue, $saturation ) {
		$css .= '
			.colors-custom .select2-container--default .select2-selection--single {
				border-color: hsl( ' . $hue . ', ' . $saturation . ', 73% );
			}
			.colors-custom .select2-container--default .select2-selection__rendered {
				color: hsl( ' . $hue . ', ' . $saturation . ', 40% );
			}
			.colors-custom .select2-container--default .select2-selection--single .select2-selection__arrow b {
				border-color: hsl( ' . $hue . ', ' . $saturation . ', 40% ) transparent transparent transparent;
			}
			.colors-custom .select2-container--focus .select2-selection {
				border-color: #000;
			}
			.colors-custom .select2-container--focus .select2-selection--single .select2-selection__arrow b {
				border-color: #000 transparent transparent transparent;
			}
			.colors-custom .select2-container--focus .select2-selection .select2-selection__rendered {
				color: #000;
			}
		';
		return $css;
	}
}

WC_Twenty_Seventeen::init();
