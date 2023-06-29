<?php

use League\CommonMark\Node\Node;
use League\CommonMark\Util\HtmlElement;

/**
 * Decorates a node with a Gutenberg block html comment.
 */
final class GutenbergBlockDecorator implements NodeRendererInterface {

	private NodeRendererInterface $inner;

	private string $tag;

	/** @var array<string, string|string[]|bool> */
	private array $attributes;

	private bool $self_closing;

	/**
	 * Constructor.
	 *
	 * @param NodeRendererInterface               $inner
	 * @param string                              $block_name
	 * @param array<string, string|string[]|bool> $attributes
	 */
	public function __construct( NodeRendererInterface $inner, string $block_name, array $attributes = array() ) {
		$this->inner      = $inner;
		$this->block_name = $block_name;
		$this->attributes = $attributes;
	}

	/**
	 * Render a node.
	 *
	 * @param Node                       $node
	 * @param ChildNodeRendererInterface $child_renderer
	 */
	public function render( Node $node, ChildNodeRendererInterface $child_renderer ) {
		// TODO:  handle attributes somehow

		$gberg_block_open  = '<!-- wp:' . $this->block_name . ' -->';
		$gberg_block_close = '<!-- /wp:' . $this->block_name . ' -->';

		$html_content = $this->inner->render( $node, $child_renderer );
		$content      = "$gberg_block_open\n$html_content\n$gberg_block_close";

		return $content;
	}
}

