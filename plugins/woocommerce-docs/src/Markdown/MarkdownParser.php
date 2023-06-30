<?php

namespace WooCommerceDocs\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Renderer\Block\ParagraphRenderer;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Renderer\Block\HeadingRenderer;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Renderer\Block\ListBlockRenderer;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Renderer\Block\ListItemRenderer;
use League\CommonMark\Extension\CommonMark\Node\Inline\Text;
use League\CommonMark\Renderer\Inline\TextRenderer;

/**
 * Class MarkdownParser
 */
class MarkdownParser {

	/**
	 * @var MarkdownParser
	 *
	 * The MarkdownParser instance.
	 */
	private $parser;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$environment = new Environment();
		$environment->addExtension( new CommonMarkCoreExtension() );

		$this->add_gutenberg_block_decorators( $environment );
		$this->parser = new MarkdownConverter( $environment );
	}

	/**
	 * Add the Gutenberg block decorators to the parser.
	 *
	 * @param Environment $environment The CommonMark environment.
	 */
	private function add_gutenberg_block_decorators( $environment ) {
		$environment->addRenderer( Text::class, new TextRenderer() );
		// $environment->addRenderer( Paragraph::class, new GutenbergBlockDecorator( new ParagraphRenderer(), 'paragraph' ) );
		$environment->addRenderer( Heading::class, new GutenbergBlockDecorator( new HeadingRenderer(), 'heading' ) );
		$environment->addRenderer( ListBlock::class, new GutenbergListBlockDecorator( new ListBlockRenderer(), 'list' ) );
		$environment->addRenderer( ListItem::class, new GutenbergBlockDecorator( new ListItemRenderer(), 'list-item' ) );
	}

	/**
	 * Convert Markdown to HTML.
	 *
	 * @param string $content The Markdown content.
	 *
	 * @return string
	 */
	public function convert( $content ) {
		return $this->parser->convert( $content );
	}
}
