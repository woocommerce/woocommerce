<?php
/**
 * Email CSS Inliner class file.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Css_Inliner;
use Pelago\Emogrifier\CssInliner;

/**
 * Class for inlining CSS in HTML emails.
 */
class Email_Css_Inliner implements Css_Inliner {

	/**
	 * The CSS inliner instance.
	 *
	 * @var CssInliner
	 */
	private CssInliner $inliner;

	/**
	 * Creates a new instance from HTML content.
	 *
	 * @param string $unprocessed_html The HTML content to process.
	 * @return self
	 */
	public function from_html( string $unprocessed_html ): self {
		$that          = new self();
		$that->inliner = CssInliner::fromHtml( $unprocessed_html );
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
			throw new \LogicException( 'You must call from_html before calling inline_css' );
		}
		return $this->inliner->render();
	}
}
