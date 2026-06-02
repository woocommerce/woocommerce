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
		add_action( 'admin_footer', array( self::class, 'output_scripts' ) );
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
		// NOTE: WP core sets dashicon glyphs on `.meta-box-sortables .postbox .toggle-indicator::before`
		// (and a 4-class variant for .closed). We must match that specificity, kill the icon font, and
		// !important the mask itself — otherwise the dashicon glyph wins and the chevrons look swapped.
		$chevron_down = self::mask_url( self::path( 'M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z' ) );
		$chevron_up   = self::mask_url( self::path( 'M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z' ) );
		$css         .= '.meta-box-sortables .postbox .handlediv .toggle-indicator::before,' . "\n";
		$css         .= '.postbox .handlediv .toggle-indicator::before {' . "\n";
		$css         .= "\tfont-family: sans-serif !important;\n";
		$css         .= "\tcontent: '' !important;\n";
		$css         .= "\tdisplay: inline-block !important;\n";
		$css         .= "\twidth: 20px !important;\n";
		$css         .= "\theight: 20px !important;\n";
		$css         .= "\tbackground-color: currentColor !important;\n";
		$css         .= "\tmask-image: {$chevron_down} !important;\n";
		$css         .= "\t-webkit-mask-image: {$chevron_down} !important;\n";
		$css         .= "\tmask-repeat: no-repeat !important;\n";
		$css         .= "\tmask-size: contain !important;\n";
		$css         .= "\tmask-position: center !important;\n";
		$css         .= "}\n";
		$css         .= '.meta-box-sortables .postbox.closed .handlediv .toggle-indicator::before,' . "\n";
		$css         .= '.postbox.closed .handlediv .toggle-indicator::before {' . "\n";
		$css         .= "\tmask-image: {$chevron_up} !important;\n";
		$css         .= "\t-webkit-mask-image: {$chevron_up} !important;\n";
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

		// Publish metabox row icons — replace Dashicons \f173/\f177/\f145 with @wordpress/icons SVGs.
		$icon_status     = self::mask_url(
			self::path( 'M12 18.5a6.5 6.5 0 1 1 0-13 6.5 6.5 0 0 1 0 13ZM4 12a8 8 0 1 1 16 0 8 8 0 0 1-16 0Zm11.53-1.47-1.06-1.06L11 12.94l-1.47-1.47-1.06 1.06L11 15.06l4.53-4.53Z', true )
		);
		$icon_visibility = self::mask_url(
			self::path( 'M3.99961 13C4.67043 13.3354 4.6703 13.3357 4.67017 13.3359L4.67298 13.3305C4.67621 13.3242 4.68184 13.3135 4.68988 13.2985C4.70595 13.2686 4.7316 13.2218 4.76695 13.1608C4.8377 13.0385 4.94692 12.8592 5.09541 12.6419C5.39312 12.2062 5.84436 11.624 6.45435 11.0431C7.67308 9.88241 9.49719 8.75 11.9996 8.75C14.502 8.75 16.3261 9.88241 17.5449 11.0431C18.1549 11.624 18.6061 12.2062 18.9038 12.6419C19.0523 12.8592 19.1615 13.0385 19.2323 13.1608C19.2676 13.2218 19.2933 13.2686 19.3093 13.2985C19.3174 13.3135 19.323 13.3242 19.3262 13.3305L19.3291 13.3359C19.3289 13.3357 19.3288 13.3354 19.9996 13C20.6704 12.6646 20.6703 12.6643 20.6701 12.664L20.6697 12.6632L20.6688 12.6614L20.6662 12.6563L20.6583 12.6408C20.6517 12.6282 20.6427 12.6108 20.631 12.5892C20.6078 12.5459 20.5744 12.4852 20.5306 12.4096C20.4432 12.2584 20.3141 12.0471 20.1423 11.7956C19.7994 11.2938 19.2819 10.626 18.5794 9.9569C17.1731 8.61759 14.9972 7.25 11.9996 7.25C9.00203 7.25 6.82614 8.61759 5.41987 9.9569C4.71736 10.626 4.19984 11.2938 3.85694 11.7956C3.68511 12.0471 3.55605 12.2584 3.4686 12.4096C3.42484 12.4852 3.39142 12.5459 3.36818 12.5892C3.35656 12.6108 3.34748 12.6282 3.34092 12.6408L3.33297 12.6563L3.33041 12.6614L3.32948 12.6632L3.32911 12.664C3.32894 12.6643 3.32879 12.6646 3.99961 13ZM11.9996 16C13.9326 16 15.4996 14.433 15.4996 12.5C15.4996 10.567 13.9326 9 11.9996 9C10.0666 9 8.49961 10.567 8.49961 12.5C8.49961 14.433 10.0666 16 11.9996 16Z' )
		);
		$icon_calendar   = self::mask_url(
			self::path( 'M12 18.5a6.5 6.5 0 1 1 0-13 6.5 6.5 0 0 1 0 13ZM4 12a8 8 0 1 1 16 0 8 8 0 0 1-16 0Zm9 1V8h-1.5v3.5h-2V13H13Z', true )
		);

		$css .= '#post-body .misc-pub-post-status::before,' . "\n";
		$css .= '#post-body #visibility::before,' . "\n";
		$css .= '.curtime #timestamp::before {' . "\n";
		$css .= "\tfont-family: none !important;\n";
		$css .= "\tcontent: '' !important;\n";
		$css .= "\tdisplay: inline-block !important;\n";
		$css .= "\twidth: 20px !important;\n";
		$css .= "\theight: 20px !important;\n";
		$css .= "\tbackground-color: currentColor !important;\n";
		$css .= "\tmask-repeat: no-repeat !important;\n";
		$css .= "\tmask-size: contain !important;\n";
		$css .= "\tmask-position: center !important;\n";
		$css .= "\tvertical-align: top !important;\n";
		$css .= "}\n";

		$icon_draft   = self::mask_url(
			self::path( 'M12 18.5a6.5 6.5 0 1 1 0-13 6.5 6.5 0 0 1 0 13ZM4 12a8 8 0 1 1 16 0 8 8 0 0 1-16 0Zm8 4a4 4 0 0 0 4-4H8a4 4 0 0 0 4 4Z', true )
		);
		$icon_pending = self::mask_url(
			self::path( 'M12 18.5a6.5 6.5 0 1 1 0-13 6.5 6.5 0 0 1 0 13ZM4 12a8 8 0 1 1 16 0 8 8 0 0 1-16 0Z', true )
		);

		$css .= '#post-body .misc-pub-post-status::before {' . "\n";
		$css .= "\tmask-image: {$icon_status};\n";
		$css .= "\t-webkit-mask-image: {$icon_status};\n";
		$css .= "}\n";
		$css .= '#post-body .misc-pub-post-status.wc-proto-is-draft::before {' . "\n";
		$css .= "\tmask-image: {$icon_draft};\n";
		$css .= "\t-webkit-mask-image: {$icon_draft};\n";
		$css .= "}\n";
		$css .= '#post-body .misc-pub-post-status.wc-proto-is-pending::before {' . "\n";
		$css .= "\tmask-image: {$icon_pending};\n";
		$css .= "\t-webkit-mask-image: {$icon_pending};\n";
		$css .= "}\n";
		$css .= '#post-body #visibility::before {' . "\n";
		$css .= "\tmask-image: {$icon_visibility};\n";
		$css .= "\t-webkit-mask-image: {$icon_visibility};\n";
		$css .= "}\n";
		$css .= '.curtime #timestamp::before {' . "\n";
		$css .= "\tmask-image: {$icon_calendar};\n";
		$css .= "\t-webkit-mask-image: {$icon_calendar};\n";
		$css .= "}\n";

		$css .= '</style>';
		return $css;
	}

	/**
	 * Output JS to keep the status icon in sync with the status select.
	 */
	public static function output_scripts(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || 'product' !== $screen->post_type ) {
			return;
		}
		?>
		<script>
		( function () {
			var statusRow    = document.querySelector( '#post-body .misc-pub-post-status' );
			var statusSelect = document.getElementById( 'post_status' );

			function syncStatusIcon() {
				if ( ! statusRow || ! statusSelect ) { return; }
				statusRow.classList.toggle( 'wc-proto-is-draft',   statusSelect.value === 'draft' );
				statusRow.classList.toggle( 'wc-proto-is-pending', statusSelect.value === 'pending' );
			}

			syncStatusIcon();

			if ( statusSelect ) {
				statusSelect.addEventListener( 'change', syncStatusIcon );
			}
		}() );
		</script>
		<?php
	}
}
