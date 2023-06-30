<?php

namespace WooCommerceDocs\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Renderer\Block\ParagraphRenderer;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Renderer\Block\HeadingRenderer;


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
		// $environment->addRenderer( 'League\CommonMark\Node\Block\Document', new GutenbergBlockDecorator( $environment->getRenderer(), 'core/paragraph' ) );

		$environment->addRenderer( Paragraph::class, new GutenbergBlockDecorator( new ParagraphRenderer(), 'paragraph' ) );
		$environment->addRenderer( Heading::class, new GutenbergBlockDecorator( new HeadingRenderer(), 'heading' ) );
		// $environment->addRenderer( 'League\CommonMark\Node\Block\ListBlock', new GutenbergBlockDecorator( $environment->getRenderer(), 'core/list' ) );
		// $environment->addRenderer( 'League\CommonMark\Node\Block\ListItem', new GutenbergBlockDecorator( $environment->getRenderer(), 'core/list-item' ) );
		// $environment->addRenderer( 'League\CommonMark\Node\Block\ThematicBreak', new GutenbergBlockDecorator( $environment->getRenderer(), 'core/separator' ) );
		// $environment->addRenderer( 'League\CommonMark\Node\Block\IndentedCode', new GutenbergBlockDecorator( $environment->getRenderer(), 'core/code' ) );
		// $environment->addRenderer( 'League\CommonMark\Node\Block\FencedCode', new GutenbergBlockDecorator( $environment->getRenderer(), 'core/code' ) );
		// $environment->addRenderer( 'League\CommonMark\Node\Block\BlockQuote', new GutenbergBlockDecorator( $environment->getRenderer(), 'core/quote' ) );
		// $environment->addRenderer( 'League\CommonMark\Node\Block\HtmlBlock', new GutenbergBlockDecorator( $environment->getRenderer(), 'core/html' ) );
		// $environment->addRenderer( 'League\CommonMark\Node\Block\ThematicBreak', new GutenbergBlockDecorator( $environment->getRenderer(), 'core/separator' ) );
		// $environment->addRenderer( 'League\CommonMark\Node\Block\Heading', new GutenbergBlockDecorator( $environment->getRenderer(), 'core/heading' ) );
		// $environment->addRenderer( 'League\CommonMark\Node\Block\Link', new GutenbergBlockDecorator( $environment->getRenderer(), 'core/paragraph' ) );
		// $environment->addRenderer( 'League\CommonMark\Node\Block\Image', new GutenbergBlockDecorator( $environment->getRenderer(), 'core/image' ) );
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
