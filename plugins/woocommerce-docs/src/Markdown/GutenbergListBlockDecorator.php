<?php

namespace WooCommerceDocs\Markdown;

use League\CommonMark\Node\Node;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;

/**
 * Decorates a node with a Gutenberg block html comment.
 */
final class GutenbergListBlockDecorator implements NodeRendererInterface {

	/**
	 * @var NodeRendererInterface $inner
	 *
	 * The inner renderer.
	 */
	private NodeRendererInterface $inner;

	/**
	 * @var string $block_name
	 *
	 * The Gutenberg block name.
	 */
	private string $block_name;


	/**
	 * Constructor.
	 *
	 * @param NodeRendererInterface               $inner
	 * @param string                              $block_name
	 * @param array<string, string|string[]|bool> $attributes
	 */
	public function __construct( NodeRendererInterface $inner, string $block_name ) {
		$this->inner      = $inner;
		$this->block_name = $block_name;
	}

	/**
	 * Render a node.
	 *
	 * @param Node                       $node
	 * @param ChildNodeRendererInterface $child_renderer
	 */
	public function render( Node $node, ChildNodeRendererInterface $child_renderer ) {
		ListBlock::assertInstanceOf( $node );

		$list_data = $node->getListData();
		$tag       = ListBlock::TYPE_BULLET === $list_data->type ? 'ul' : 'ol';

		if ( 'ol' === $tag ) {
			return new GutenbergComment( $this->block_name, $this->inner->render( $node, $child_renderer ), array( 'ordered' => true ) );
		}

		return new GutenbergComment( $this->block_name, $this->inner->render( $node, $child_renderer ) );
	}
}

