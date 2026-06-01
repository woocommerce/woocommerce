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
	 * The CSS from the email editor was already inlined.
	 * The method hooks to woocommerce_email_styles and removes CSS rules that we don't want to apply to the email.
	 *
	 * Typography properties (font-size, font-weight, line-height, letter-spacing) are stripped
	 * because the email editor theme controls all typography via theme.json. Leaving these in
	 * the WooCommerce CSS would override the editor's heading sizes and weights.
	 *
	 * @since 10.8.0
	 * @param string $css CSS.
	 * @return string
	 */
	public function prepare_css( string $css ): string {
		remove_filter( 'woocommerce_email_styles', array( $this, 'prepare_css' ) );
		// Remove typography declarations from WooCommerce CSS.
		// The email editor theme.json controls all typography; WC CSS would override it.
		return (string) preg_replace(
			array(
				'/color\s*:\s*[^;]+;/',
				'/font-family\s*:\s*[^;]+;/',
				'/font-size\s*:\s*[^;]+;/',
				'/font-weight\s*:\s*[^;]+;/',
				'/line-height\s*:\s*[^;]+;/',
				'/letter-spacing\s*:\s*[^;]+;/',
			),
			'',
			$css
		);
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
		$css  = $this->theme_controller->get_stylesheet_for_rendering();
		$css .= $this->get_woo_content_styles();
		return $this->css_inliner->from_html( $woo_content )->inline_css( $css )->render();
	}

	/**
	 * Get CSS styles specific to WooCommerce email content.
	 *
	 * These styles target WooCommerce-specific HTML classes in the order details,
	 * totals, and other email content areas. They are needed because the WooCommerce
	 * email CSS selectors (prefixed with #body_content) do not match in the block
	 * email editor template structure.
	 *
	 * @since 10.8.0
	 * @return string CSS styles.
	 */
	private function get_woo_content_styles(): string {
		return '
			.email-order-details td,
			.email-order-details th {
				padding: 8px 12px;
			}
			.email-order-details td:first-child,
			.email-order-details th:first-child {
				padding-left: 0;
			}
			.email-order-details td:last-child,
			.email-order-details th:last-child {
				padding-right: 0;
			}
			.order-item-data td {
				border: 0;
				padding: 0;
				vertical-align: top;
			}
			.order-item-data img {
				border-radius: 4px;
			}
			.order-totals th,
			.order-totals td {
				font-weight: 400;
				padding-bottom: 5px;
				padding-top: 5px;
			}
			.order-totals-total th {
				font-weight: 700;
			}
			.order-totals-total td {
				font-weight: 700;
				font-size: 20px;
			}
			h2.email-order-detail-heading {
				font-size: 20px;
				font-weight: 700;
				line-height: 1.6;
			}
			h2.email-order-detail-heading span {
				font-size: 14px;
				font-weight: 400;
				color: #757575;
			}
			.email-order-item-meta {
				font-size: 14px;
				line-height: 1.4;
			}
		';
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
