<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * ComingSoonTemplate class.
 *
 * @internal
 */
class ComingSoonTemplate extends AbstractPageTemplate {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'coming-soon';

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Page: Coming soon', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Let your shoppers know your site or part of your site is under construction.', 'woocommerce' );
	}

	/**
	 * Returns the page object assigned to this template/page.
	 *
	 * @return \WP_Post|null Post object or null.
	 */
	protected function get_placeholder_page() {
		return null;
	}

	/**
	 * True when viewing the coming soon page.
	 *
	 * @return boolean
	 */
	protected function is_active_template() {
		return false;
	}

	/**
	 * Returns the font family for the body and heading.
	 *
	 * When the current theme is not an FSE theme, we use the default fonts.
	 * When the current theme is an FSE theme, we use the fonts from the theme.json file if available except for the 'twentytwentyfour' theme.
	 *
	 * @return array
	 */
	public static function get_font_families() {
		$default_fonts = array(
			'heading' => 'cardo',
			'body'    => 'inter',
		);

		if ( ! wp_is_block_theme() ) {
			return $default_fonts;
		}

		$current_theme = wp_get_theme()->get_stylesheet();

		if ( 'twentytwentyfour' === $current_theme ) {
			return array(
				'heading' => 'heading',
				'body'    => 'body',
			);
		}

		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return $default_fonts;
		}

		$settings = wp_get_global_settings();
		if (
			! isset( $settings['typography']['fontFamilies']['theme'] )
			|| ! is_array( $settings['typography']['fontFamilies']['theme'] )
		) {
			return $default_fonts;
		}

		$theme_fonts = $settings['typography']['fontFamilies']['theme'];

		// Override default fonts if available in theme.json.
		if ( isset( $theme_fonts[0]['slug'] ) && ! empty( $theme_fonts[0]['slug'] ) ) {
			// Convert the font family to lowercase and replace spaces with hyphens.
			$default_fonts['heading'] = strtolower( str_replace( ' ', '-', $theme_fonts[0]['slug'] ) );
		}
		if ( isset( $theme_fonts[1]['slug'] ) && ! empty( $theme_fonts[1]['slug'] ) ) {
			$default_fonts['body']      = strtolower( str_replace( ' ', '-', $theme_fonts[1]['slug'] ) );
			$default_fonts['paragraph'] = $default_fonts['body'];
		}

		return $default_fonts;
	}
}
