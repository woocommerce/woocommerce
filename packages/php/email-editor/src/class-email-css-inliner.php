<?php
/**
 * Email CSS Inliner class file.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Css_Inliner;

/**
 * Class for inlining CSS in HTML emails.
 */
class Email_Css_Inliner implements Css_Inliner {

	/**
	 * The CSS inliner instance.
	 *
	 * Runtime type: Pelago\Emogrifier\CssInliner | Automattic\WooCommerce\Vendor\Pelago\Emogrifier\CssInliner
	 * Both classes extend AbstractHtmlProcessor and implement:
	 * - static fromHtml(string $html): static
	 * - inlineCss(string $css = ''): self
	 * - render(): string
	 *
	 * @var object|null
	 */
	private $inliner;

	/**
	 * Creates a new instance from HTML content.
	 *
	 * @param string $unprocessed_html The HTML content to process.
	 * @return self
	 */
	public function from_html( string $unprocessed_html ): self {
		$inliner_class = $this->get_inliner_class();
		$that          = new self();
		$that->inliner = $inliner_class::fromHtml( $unprocessed_html );
		return $that;
	}

	/**
	 * Inlines the provided CSS.
	 *
	 * @param string $css The CSS to inline.
	 * @return self
	 * @throws \LogicException If from_html() was not called first.
	 */
	public function inline_css( string $css = '' ): self {
		if ( ! isset( $this->inliner ) ) {
			throw new \LogicException( 'You must call from_html before calling inline_css' );
		}
		/** Ignore PHPStan analysis for dynamic inliner method call. @phpstan-ignore-next-line */
		$this->inliner->inlineCss( $css );
		return $this;
	}

	/**
	 * Renders the HTML with inlined CSS.
	 *
	 * @return string The processed HTML.
	 * @throws \LogicException If from_html() was not called first.
	 */
	public function render(): string {
		if ( ! isset( $this->inliner ) ) {
			throw new \LogicException( 'You must call from_html before calling render' );
		}
		/** Ignore PHPStan analysis for dynamic inliner method call. @phpstan-ignore-next-line */
		return $this->inliner->render();
	}

	/**
	 * Get the inliner class.
	 *
	 * Returns the fully qualified class name for the available CSS inliner.
	 * Runtime return type: 'Pelago\Emogrifier\CssInliner' | 'Automattic\WooCommerce\Vendor\Pelago\Emogrifier\CssInliner'
	 *
	 * @return string Fully qualified class name
	 * @throws \Exception If the inliner class is not found.
	 */
	private function get_inliner_class(): string {
		if ( class_exists( 'Automattic\WooCommerce\Vendor\Pelago\Emogrifier\CssInliner' ) ) {
			return 'Automattic\WooCommerce\Vendor\Pelago\Emogrifier\CssInliner';
		}
		if ( class_exists( 'Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\CssInliner' ) ) {
			return 'Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\CssInliner';
		}
		throw new \Exception( 'CssInliner class not found' );
	}
}
