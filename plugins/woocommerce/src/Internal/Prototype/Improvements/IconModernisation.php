<?php
/**
 * IconModernisation prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces WooCommerce icon font glyphs with @wordpress/icons SVGs via CSS mask-image.
 * Covers product data tab icons. Activated via the 'icon_modernisation' dev panel flag.
 */
class IconModernisation {

	/**
	 * Register hooks. No-ops if the dev panel flag is off.
	 *
	 * @internal
	 */
	final public static function init(): void {
		if ( ! DevPanel::is_flag_enabled( 'icon_modernisation' ) ) {
			return;
		}
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_dependencies' ) );
		add_action( 'admin_head', array( self::class, 'inject_icon_css' ) );
	}

	/**
	 * Enqueue the @wordpress/theme design tokens on the classic editor page where they are not otherwise loaded.
	 */
	public static function enqueue_dependencies(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || 'product' !== $screen->post_type ) {
			return;
		}
		wp_enqueue_style(
			'wc-proto-design-tokens',
			plugins_url( 'assets/client/prototype/wp-design-tokens.css', WC_PLUGIN_FILE ),
			array(),
			'0.12.0'
		);
	}

	/**
	 * Inject CSS on the product edit screen.
	 */
	public static function inject_icon_css(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || 'product' !== $screen->post_type ) {
			return;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::build_css();
	}

	/**
	 * Build a CSS mask-image value wrapping one or more inline SVG path elements.
	 *
	 * @param string $paths_html Concatenated <path …/> elements.
	 */
	private static function mask_url( string $paths_html ): string {
		return "url(\"data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'>{$paths_html}</svg>\")";
	}

	/**
	 * Build a single SVG <path> element string.
	 *
	 * @param string $d       SVG path data.
	 * @param bool   $evenodd Whether to set fill-rule="evenodd".
	 */
	private static function path( string $d, bool $evenodd = false ): string {
		$fill_rule = $evenodd ? " fill-rule='evenodd'" : '';
		return "<path{$fill_rule} d='{$d}'/>";
	}

	/**
	 * Build the full CSS block replacing product data tab icons.
	 */
	private static function build_css(): string {
		$tabs = array(
			'general_tab'                 => self::mask_url(
				self::path( 'M4.75 4a.75.75 0 0 0-.75.75v7.826c0 .2.08.39.22.53l6.72 6.716a2.313 2.313 0 0 0 3.276-.001l5.61-5.611-.531-.53.532.528a2.315 2.315 0 0 0 0-3.264L13.104 4.22a.75.75 0 0 0-.53-.22H4.75ZM19 12.576a.815.815 0 0 1-.236.574l-5.61 5.611a.814.814 0 0 1-1.153 0L5.5 12.264V5.5h6.763l6.5 6.502a.816.816 0 0 1 .237.574ZM8.75 9.75a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z' )
			),
			'inventory_tab'               => self::mask_url(
				self::path( 'M11.934 7.406a1 1 0 0 0 .914.594H19a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5H5a.5.5 0 0 1-.5-.5V6a.5.5 0 0 1 .5-.5h5.764a.5.5 0 0 1 .447.276l.723 1.63Zm1.064-1.216a.5.5 0 0 0 .462.31H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.764a2 2 0 0 1 1.789 1.106l.445 1.084ZM8.5 10.5h7V12h-7v-1.5Zm7 3.5h-7v1.5h7V14Z', true )
			),
			'shipping_tab'                => self::mask_url(
				self::path( 'M3 6.75C3 5.784 3.784 5 4.75 5H15V7.313l.05.027 5.056 2.73.394.212v3.468a1.75 1.75 0 01-1.75 1.75h-.012a2.5 2.5 0 11-4.975 0H9.737a2.5 2.5 0 11-4.975 0H3V6.75zM13.5 14V6.5H4.75a.25.25 0 00-.25.25V14h.965a2.493 2.493 0 011.785-.75c.7 0 1.332.287 1.785.75H13.5zm4.535 0h.715a.25.25 0 00.25-.25v-2.573l-4-2.16v4.568a2.487 2.487 0 011.25-.335c.7 0 1.332.287 1.785.75zM6.282 15.5a1.002 1.002 0 00.968 1.25 1 1 0 10-.968-1.25zm9 0a1 1 0 101.937.498 1 1 0 00-1.938-.498z' )
			),
			'linked_product_tab'          => self::mask_url(
				self::path( 'M10 17.389H8.444A5.194 5.194 0 1 1 8.444 7H10v1.5H8.444a3.694 3.694 0 0 0 0 7.389H10v1.5ZM14 7h1.556a5.194 5.194 0 0 1 0 10.39H14v-1.5h1.556a3.694 3.694 0 0 0 0-7.39H14V7Zm-4.5 6h5v-1.5h-5V13Z' )
			),
			'attribute_tab'               => self::mask_url(
				self::path( 'M11.1 15.8H20v-1.5h-8.9v1.5zm0-8.6v1.5H20V7.2h-8.9zM6 13c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0-7c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z' )
			),
			'variations_tab'              => self::mask_url(
				self::path( 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2Zm.5 2v6.2h-6.8V4.4h6.2c.3 0 .5.2.5.5ZM5 4.5h6.2v6.8H4.4V5.1c0-.3.2-.5.5-.5ZM4.5 19v-6.2h6.8v6.8H5.1c-.3 0-.5-.2-.5-.5Zm14.5.5h-6.2v-6.8h6.8v6.2c0 .3-.2.5-.5.5Z' )
			),
			'marketplace-suggestions_tab' => self::mask_url(
				self::path( 'M10.5 4v4h3V4H15v4h1.5a1 1 0 011 1v4l-3 4v2a1 1 0 01-1 1h-3a1 1 0 01-1-1v-2l-3-4V9a1 1 0 011-1H9V4h1.5zm.5 12.5v2h2v-2l3-4v-3H8v3l3 4z' )
			),
			'advanced_tab'                => self::mask_url(
				self::path( 'm19 7.5h-7.628c-.3089-.87389-1.1423-1.5-2.122-1.5-.97966 0-1.81309.62611-2.12197 1.5h-2.12803v1.5h2.12803c.30888.87389 1.14231 1.5 2.12197 1.5.9797 0 1.8131-.62611 2.122-1.5h7.628z' ) .
				self::path( 'm19 15h-2.128c-.3089-.8739-1.1423-1.5-2.122-1.5s-1.8131.6261-2.122 1.5h-7.628v1.5h7.628c.3089.8739 1.1423 1.5 2.122 1.5s1.8131-.6261 2.122-1.5h2.128z' )
			),
		);

		$css          = '<style id="wc-proto-icon-modernisation">' . "\n";
		$css         .= '#woocommerce-product-data ul.product_data_tabs li a {' . "\n";
		$css         .= "\tdisplay: flex !important;\n";
		$css         .= "\talign-items: center !important;\n";
		$css         .= "\tgap: var(--wpds-dimension-gap-xs) !important;\n";
		$css         .= "\tcolor: var(--wpds-color-fg-content-neutral) !important;\n";
		$css         .= "\ttext-decoration: none !important;\n";
		$css         .= "\tborder: none !important;\n";
		$css         .= "\tborder-radius: var(--wpds-border-radius-sm) !important;\n";
		$css         .= "}\n";
		$css         .= '#woocommerce-product-data ul.product_data_tabs li a:focus,' . "\n";
		$css         .= '#woocommerce-product-data ul.product_data_tabs li a:focus-visible {' . "\n";
		$css         .= "\toutline: none !important;\n";
		$css         .= "\tbox-shadow: none !important;\n";
		$css         .= "}\n";
		$css         .= '#woocommerce-product-data ul.product_data_tabs li.active a {' . "\n";
		$css         .= "\tbackground-color: var(--wpds-color-bg-interactive-brand-weak-active) !important;\n";
		$css         .= "\tcolor: var(--wpds-color-fg-interactive-brand) !important;\n";
		$css         .= "\tbox-shadow: none !important;\n";
		$css         .= "}\n";
		$default_icon = self::mask_url(
			self::path( 'M17 4H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2ZM7 5.5h10a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5H7a.5.5 0 0 1-.5-.5V6a.5.5 0 0 1 .5-.5Z' ) .
			self::path( 'M15.5 7.5h-7V9h7V7.5Zm-7 3.5h7v1.5h-7V11Zm7 3.5h-7V16h7v-1.5Z' )
		);

		$css .= '#woocommerce-product-data ul.product_data_tabs li a::before {' . "\n";
		$css .= "\tfont-family: none !important;\n";
		$css .= "\tcontent: '' !important;\n";
		$css .= "\tdisplay: inline-block !important;\n";
		$css .= "\twidth: 16px !important;\n";
		$css .= "\theight: 16px !important;\n";
		$css .= "\tbackground-color: currentColor !important;\n";
		$css .= "\tmask-image: {$default_icon};\n";
		$css .= "\t-webkit-mask-image: {$default_icon};\n";
		$css .= "\tmask-repeat: no-repeat !important;\n";
		$css .= "\tmask-size: contain !important;\n";
		$css .= "\tmask-position: center !important;\n";
		$css .= "\tflex-shrink: 0 !important;\n";
		$css .= "}\n";

		/**
		 * Filters the map of product data tab CSS classes to mask-image values.
		 * Extensions can hook here to register icons for their own tabs.
		 *
		 * @since 10.9.0
		 * @param array<string,string> $tabs Map of tab class suffix (e.g. 'general_tab') to mask_url() string.
		 */
		$tabs = apply_filters( 'wc_proto_icon_modernisation_tabs', $tabs );

		foreach ( $tabs as $tab_class => $mask_value ) {
			$css .= "#woocommerce-product-data ul.product_data_tabs li.{$tab_class} a::before {\n";
			$css .= "\tmask-image: {$mask_value};\n";
			$css .= "\t-webkit-mask-image: {$mask_value};\n";
			$css .= "}\n";
		}

		// Add Media button icon — replace Dashicons \f104 with the image SVG.
		$image_icon = self::mask_url(
			self::path( 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM5 4.5h14c.3 0 .5.2.5.5v8.4l-3-2.9c-.3-.3-.8-.3-1 0L11.9 14 9 12c-.3-.2-.6-.2-.8 0l-3.6 2.6V5c-.1-.3.1-.5.4-.5zm14 15H5c-.3 0-.5-.2-.5-.5v-2.4l4.1-3 3 1.9c.3.2.7.2.9-.1L16 12l3.5 3.4V19c0 .3-.2.5-.5.5z' )
		);
		$css       .= 'span.wp-media-buttons-icon::before {' . "\n";
		$css       .= "\tfont-family: none !important;\n";
		$css       .= "\tcontent: '' !important;\n";
		$css       .= "\tdisplay: inline-block !important;\n";
		$css       .= "\twidth: 18px !important;\n";
		$css       .= "\theight: 18px !important;\n";
		$css       .= "\tbackground-color: currentColor !important;\n";
		$css       .= "\tmask-image: {$image_icon};\n";
		$css       .= "\t-webkit-mask-image: {$image_icon};\n";
		$css       .= "\tmask-repeat: no-repeat !important;\n";
		$css       .= "\tmask-size: contain !important;\n";
		$css       .= "\tmask-position: center !important;\n";
		$css       .= "\tvertical-align: middle !important;\n";
		$css       .= "}\n";

		// Help tip icons — replace Dashicons \f223 on ::after with the help SVG.
		$help_icon = self::mask_url(
			self::path( 'M12 4a8 8 0 1 1 .001 16.001A8 8 0 0 1 12 4Zm0 1.5a6.5 6.5 0 1 0-.001 13.001A6.5 6.5 0 0 0 12 5.5Zm.75 11h-1.5V15h1.5v1.5Zm-.445-9.234a3 3 0 0 1 .445 5.89V14h-1.5v-1.25c0-.57.452-.958.917-1.01A1.5 1.5 0 0 0 12 8.75a1.5 1.5 0 0 0-1.5 1.5H9a3 3 0 0 1 3.305-2.984Z' )
		);
		$css      .= '.woocommerce-help-tip::after,' . "\n";
		$css      .= '.woocommerce-product-type-tip::after {' . "\n";
		$css      .= "\tfont-family: none !important;\n";
		$css      .= "\tcontent: '' !important;\n";
		$css      .= "\tbackground-color: currentColor !important;\n";
		$css      .= "\tmask-image: {$help_icon};\n";
		$css      .= "\t-webkit-mask-image: {$help_icon};\n";
		$css      .= "\tmask-repeat: no-repeat !important;\n";
		$css      .= "\tmask-size: contain !important;\n";
		$css      .= "\tmask-position: center !important;\n";
		$css      .= "}\n";

		// Override WP core's circular focus ring on the toggle button.
		$css .= '.postbox .handlediv:focus,' . "\n";
		$css .= '.postbox .handlediv:focus-visible {' . "\n";
		$css .= "\tbox-shadow: none !important;\n";
		$css .= "\tborder-radius: 2px !important;\n";
		$css .= "\toutline: 2px solid var(--wpds-color-fg-interactive-brand) !important;\n";
		$css .= "\toutline-offset: 2px !important;\n";
		$css .= "}\n";

		// Meta box toggle — replace Dashicons \f142 with chevron-down (open) / chevron-up (closed).
		$chevron_down = self::mask_url( self::path( 'M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z' ) );
		$chevron_up   = self::mask_url( self::path( 'M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z' ) );
		$css         .= '.postbox .toggle-indicator::before {' . "\n";
		$css         .= "\tfont-family: none !important;\n";
		$css         .= "\tcontent: '' !important;\n";
		$css         .= "\tdisplay: inline-block !important;\n";
		$css         .= "\twidth: 20px !important;\n";
		$css         .= "\theight: 20px !important;\n";
		$css         .= "\tbackground-color: currentColor !important;\n";
		$css         .= "\tmask-image: {$chevron_down};\n";
		$css         .= "\t-webkit-mask-image: {$chevron_down};\n";
		$css         .= "\tmask-repeat: no-repeat !important;\n";
		$css         .= "\tmask-size: contain !important;\n";
		$css         .= "\tmask-position: center !important;\n";
		$css         .= "}\n";
		$css         .= '.postbox.closed .toggle-indicator::before {' . "\n";
		$css         .= "\tmask-image: {$chevron_up};\n";
		$css         .= "\t-webkit-mask-image: {$chevron_up};\n";
		$css         .= "}\n";

		// Meta box reorder arrows — replace Dashicons \f343/\f347 with arrow-up/arrow-down.
		$arrow_up   = self::mask_url( self::path( 'M12 3.9 6.5 9.5l1 1 3.8-3.7V20h1.5V6.8l3.7 3.7 1-1z' ) );
		$arrow_down = self::mask_url( self::path( 'm16.5 13.5-3.7 3.7V4h-1.5v13.2l-3.8-3.7-1 1 5.5 5.6 5.5-5.6z' ) );
		$css       .= '.postbox .order-higher-indicator::before {' . "\n";
		$css       .= "\tfont-family: none !important;\n";
		$css       .= "\tcontent: '' !important;\n";
		$css       .= "\tdisplay: inline-block !important;\n";
		$css       .= "\twidth: 20px !important;\n";
		$css       .= "\theight: 20px !important;\n";
		$css       .= "\tbackground-color: currentColor !important;\n";
		$css       .= "\tmask-image: {$arrow_up};\n";
		$css       .= "\t-webkit-mask-image: {$arrow_up};\n";
		$css       .= "\tmask-repeat: no-repeat !important;\n";
		$css       .= "\tmask-size: contain !important;\n";
		$css       .= "\tmask-position: center !important;\n";
		$css       .= "}\n";
		$css       .= '.postbox .order-lower-indicator::before {' . "\n";
		$css       .= "\tfont-family: none !important;\n";
		$css       .= "\tcontent: '' !important;\n";
		$css       .= "\tdisplay: inline-block !important;\n";
		$css       .= "\twidth: 20px !important;\n";
		$css       .= "\theight: 20px !important;\n";
		$css       .= "\tbackground-color: currentColor !important;\n";
		$css       .= "\tmask-image: {$arrow_down};\n";
		$css       .= "\t-webkit-mask-image: {$arrow_down};\n";
		$css       .= "\tmask-repeat: no-repeat !important;\n";
		$css       .= "\tmask-size: contain !important;\n";
		$css       .= "\tmask-position: center !important;\n";
		$css       .= "}\n";

		// Publish metabox edit links — replace "Edit" text with pencil SVG.
		$pencil = self::mask_url(
			self::path( 'M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z' )
		);
		$css   .= '.edit-visibility span[aria-hidden],' . "\n";
		$css   .= '.edit-timestamp span[aria-hidden] {' . "\n";
		$css   .= "\tfont-size: 0 !important;\n";
		$css   .= "}\n";
		$css   .= '.edit-visibility span[aria-hidden]::before,' . "\n";
		$css   .= '.edit-timestamp span[aria-hidden]::before {' . "\n";
		$css   .= "\tcontent: '' !important;\n";
		$css   .= "\tdisplay: inline-block !important;\n";
		$css   .= "\twidth: 16px !important;\n";
		$css   .= "\theight: 16px !important;\n";
		$css   .= "\tbackground-color: currentColor !important;\n";
		$css   .= "\tmask-image: {$pencil};\n";
		$css   .= "\t-webkit-mask-image: {$pencil};\n";
		$css   .= "\tmask-repeat: no-repeat !important;\n";
		$css   .= "\tmask-size: contain !important;\n";
		$css   .= "\tmask-position: center !important;\n";
		$css   .= "\tvertical-align: middle !important;\n";
		$css   .= "}\n";

		$css .= '</style>';
		return $css;
	}
}
