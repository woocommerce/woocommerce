<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Email_Css_Inliner;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

/**
 * Class responsible for extracting the main content from a WC_Email object.
 */
class WooContentProcessor {

	/**
	 * Email theme controller
	 * We use it to get email CSS.
	 *
	 * @var Theme_Controller
	 */
	private $theme_controller;

	/**
	 * CSS inliner
	 *
	 * @var Email_Css_Inliner
	 */
	private $css_inliner;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->theme_controller = Email_Editor_Container::container()->get( Theme_Controller::class );
		$this->css_inliner      = new Email_Css_Inliner();
	}

	/**
	 * Get the WooCommerce content excluding headers and footers.
	 *
	 * @param \WC_Email $wc_email WooCommerce email.
	 * @return string
	 */
	public function get_woo_content( \WC_Email $wc_email ): string {
		$woo_content          = $this->capture_woo_content( $wc_email );
		$woo_content_with_css = $this->inline_css( $woo_content );
		return $this->get_html_body_content( $woo_content_with_css );
	}

	/**
	 * Filter CSS for the email.
	 * The CSS was from email editor was already inlined.
	 * The method hookes to woocommerce_email_styles and removes CSS rules that we don't want to apply to the email.
	 *
	 * @param string $css CSS.
	 * @return string
	 */
	public function prepare_css( string $css ): string {
		remove_filter( 'woocommerce_email_styles', array( $this, 'prepare_css' ) );
		// Remove color and font-family declarations from WooCommerce CSS.
		$css = preg_replace( '/color\s*:\s*[^;]+;/', '', $css );
		$css = preg_replace( '/font-family\s*:\s*[^;]+;/', '', $css );
		return $css;
	}

	/**
	 * Get the content of the body tag from the HTML.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private function get_html_body_content( string $html ): string {
		// Extract content between <body> and </body> tags using regex.
		if ( preg_match( '/<body[^>]*>(.*?)<\/body>/is', $html, $matches ) ) {
			return $matches[1];
		}
		return $html;
	}

	/**
	 * Inline the CSS from the email theme and user email settings.
	 *
	 * @param string $woo_content WooCommerce content.
	 * @return string
	 */
	private function inline_css( string $woo_content ): string {
		if ( empty( $woo_content ) ) {
			return '';
		}
		$css = $this->theme_controller->get_stylesheet_for_rendering();
		return $this->css_inliner->from_html( $woo_content )->inline_css( $css )->render();
	}

	/**
	 * Capture the WooCommerce content excluding headers and footers.
	 *
	 * @param \WC_Email $wc_email WooCommerce email.
	 * @return string
	 */
	private function capture_woo_content( \WC_Email $wc_email ): string {
		return $wc_email->get_block_editor_email_template_content();
	}
}
