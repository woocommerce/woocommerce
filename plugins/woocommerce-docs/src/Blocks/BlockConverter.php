<?php

namespace WooCommerceDocs\Blocks;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;

/**
 * Class MarkdownParser
 */
class BlockConverter {

	/** // phpcs:ignore Generic.Commenting.DocComment.MissingShort
	 *
	 * @var MarkdownParser The MarkdownParser instance.
	 */
	private $parser;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$environment = new Environment();
		$environment->addExtension( new CommonMarkCoreExtension() );
		$environment->addExtension( new GithubFlavoredMarkdownExtension() );
		$this->parser = new MarkdownConverter( $environment );
	}


	/**
	 * Convert Markdown to Gutenberg blocks.
	 *
	 * @param string $content The Markdown content.
	 *
	 * @return string
	 */
	public function convert_markdown_to_gb_blocks( $content ) {
		$markdown_without_frontmatter = $this->strip_frontmatter( $content );
		$html                         = $this->parser->convert( $markdown_without_frontmatter )->__toString();
		return $this->convert_html_to_blocks( $html );
	}

	/**
	 * Strip frontmatter from Markdown.
	 *
	 * @param string $content The Markdown content.
	 */
	public function strip_frontmatter( $content ) {
		return preg_replace( '/^---[\s\S]*?---/', '', $content );
	}

	/**
	 * Convert HTML to blocks.
	 *
	 * @param string $html The HTML content.
	 */
	private function convert_html_to_blocks( $html ) {
		$blocks_html = '';
		$dom         = new \DOMDocument();

		$dom->loadHTML( $html );
		$xpath = new \DOMXPath( $dom );
		$nodes = $xpath->query( '//body/*' );

		foreach ( $nodes as $node ) {
			$blocks_html .= $this->convert_node_to_block( $node );
		}

		return $blocks_html;
	}

	/**
	 * Convert a DOM node to a block.
	 *
	 * @param \DOMNode $node The DOM node.
	 */
	private function convert_node_to_block( $node ) {
		$node_name  = $node->nodeName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$node_value = $node->nodeValue; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$node_content = $this->convert_child_nodes_to_blocks_or_html( $node );

		switch ( $node_name ) {
			case 'blockquote':
				return $this->create_block( 'quote', $node_name, $node_content );
			case 'table':
				return $this->create_block( 'table', $node_name, $node_content );
			case 'pre':
				return $this->create_block( 'code', $node_name, $node_content );
			case 'p':
				return $this->create_block( 'paragraph', $node_name, $node_content );
			case 'h1':
				return $this->create_block( 'heading', $node_name, $node_content, array( 'level' => 1 ) );
			case 'h2':
				return $this->create_block( 'heading', $node_name, $node_content, array( 'level' => 2 ) );
			case 'h3':
				return $this->create_block( 'heading', $node_name, $node_content, array( 'level' => 3 ) );
			case 'h4':
				return $this->create_block( 'heading', $node_name, $node_content, array( 'level' => 4 ) );
			case 'h5':
				return $this->create_block( 'heading', $node_name, $node_content, array( 'level' => 5 ) );
			case 'h6':
				return $this->create_block( 'heading', $node_name, $node_content, array( 'level' => 6 ) );
			case 'ul':
				return $this->create_block( 'list', $node_name, $node_content, array( 'ordered' => false ) );
			case 'ol':
				return $this->create_block( 'list', $node_name, $node_content, array( 'ordered' => true ) );
			case 'li':
				return $this->create_block( 'list-item', $node_name, $node_content );
			case 'hr':
				return $this->create_block( 'separator', $node_name, null );
			default:
				return $node_value;
		}
	}

	/**
	 * Create a block.
	 *
	 * @param string $block_name The block name.
	 * @param string $node_name The node name.
	 * @param string $content The content.
	 * @param array  $attrs The attributes.
	 */
	private function create_block( $block_name, $node_name, $content = null, $attrs = array() ) {
		$json_attrs = count( $attrs ) > 0 ? ' ' . wp_json_encode( $attrs ) : '';

		$block_html = "<!-- wp:{$block_name}{$json_attrs} -->\n";

		// Special case for hr, at some point we could support other self-closing tags if needed.
		if ( 'hr' === $node_name ) {
			$block_html .= "<{$node_name} class=\"wp-block-separator has-alpha-channel-opacity\" />\n";
		} elseif ( null !== $content ) {
			// Gutenberg seems to require class name to avoid block recovery error on some blocks.
			if ( 'pre' === $node_name ) {
				$block_html .= "<pre class=\"wp-block-code\">{$content}</pre>\n";
			} elseif ( 'blockquote' === $node_name ) {
				$block_html .= "<blockquote class=\"wp-block-quote\">{$content}</blockquote>\n";
			} elseif ( 'table' === $node_name ) {
				$block_html .= "<figure class=\"wp-block-table\"><table>{$content}</table></figure>\n";
			} else {
				$block_html .= "<{$node_name}>{$content}</{$node_name}>\n";
			}
		}
		$block_html .= "<!-- /wp:{$block_name} -->\n";
		return $block_html;
	}

	/**
	 * Escape a full URL.
	 *
	 * @param mixed $url The URL to escape.
	 * @return string The escaped URL.
	 */
	private static function escape_full_url( $url ) {
		// Check if the URL is a relative link, relative URLs will be replaced later.
		$is_relative_link = ( strpos( $url, '://' ) === false );
		return $is_relative_link ? $url : esc_url( $url );
	}

	/**
	 * Convert child nodes to blocks.
	 *
	 * @param \DOMNode $node The DOM node.
	 */
	private function convert_child_nodes_to_blocks_or_html( $node ) {
		$content = '';

		foreach ( $node->childNodes as $child_node ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$node_type = $child_node->nodeType; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$node_name = $child_node->nodeName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			if ( XML_ELEMENT_NODE === $node_type ) {
				if ( 'td' === $node_name || 'thead' === $node_name || 'tbody' === $node_name || 'tr' === $node_name || 'th' === $node_name ) {
					$inline_content = $this->convert_child_nodes_to_blocks_or_html( $child_node );
					$content       .= "<{$node_name}>{$inline_content}</{$node_name}>";
				} elseif ( 'a' === $node_name ) {
					$href         = self::escape_full_url( $child_node->getAttribute( 'href' ) );
					$link_content = $this->convert_child_nodes_to_blocks_or_html( $child_node );
					$content     .= "<a href=\"{$href}\">{$link_content}</a>";
				} elseif ( 'em' === $node_name || 'strong' === $node_name ) {
					$inline_content = $this->convert_child_nodes_to_blocks_or_html( $child_node );
					$content       .= "<{$node_name}>{$inline_content}</{$node_name}>";
				} elseif ( 'img' === $node_name ) {
					// Only handle images as inline content for now due to how Markdown is processed by CommonMark.
					$src      = esc_url( $child_node->getAttribute( 'src' ) );
					$alt      = esc_attr( $child_node->getAttribute( 'alt' ) );
					$content .= "<img src=\"{$src}\" alt=\"{$alt}\" />";
				} elseif ( 'code' === $node_name ) {
					$inline_content = $this->convert_child_nodes_to_blocks_or_html( $child_node );
					$content       .= "<code>{$inline_content}</code>";
				} else {
					$content .= $this->convert_node_to_block( $child_node );
				}
			} elseif ( XML_TEXT_NODE === $node_type ) {
				$content .= $child_node->nodeValue; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
		}

		return $content;
	}

}
