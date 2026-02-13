<?php
/**
 * HTML to Markdown converter for product descriptions.
 *
 * Uses WP_HTML_Processor to parse HTML and convert to clean markdown.
 *
 * @package Automattic\WooCommerce\Internal\MarkdownProductFeed
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MarkdownProductFeed;

use WP_HTML_Processor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts product description HTML to clean markdown using WP_HTML_Processor.
 *
 * This class uses WordPress's HTML5-compliant streaming parser to iterate
 * through HTML tokens and emit the corresponding markdown. It maintains a
 * small state stack for list context, links, blockquotes, and table buffering.
 *
 * @since 10.6.0
 */
class HtmlToMarkdown {

	/**
	 * Stack tracking nested UL/OL contexts.
	 *
	 * Each entry is an associative array with:
	 *   - 'type'    => 'ul' or 'ol'
	 *   - 'counter' => int (only used for OL)
	 *
	 * @var array<int, array{type: string, counter: int}>
	 */
	private array $list_stack = array();

	/**
	 * Stashed href from the most recent <a> open tag.
	 *
	 * @var string|null
	 */
	private ?string $link_href = null;

	/**
	 * Whether the processor is currently inside a <blockquote>.
	 *
	 * @var bool
	 */
	private bool $in_blockquote = false;

	/**
	 * Nesting depth for blockquotes (supports nested blockquotes).
	 *
	 * @var int
	 */
	private int $blockquote_depth = 0;

	/**
	 * Buffer for accumulating table data.
	 *
	 * Structure:
	 *   - 'rows'       => array of rows, each row is an array of cell strings
	 *   - 'current_row' => array of cells in the current row
	 *   - 'in_cell'    => bool whether currently inside a TD/TH
	 *   - 'cell_text'  => string accumulating text for the current cell
	 *   - 'has_header'  => bool whether a THEAD was encountered
	 *   - 'in_thead'    => bool whether currently inside THEAD
	 *
	 * @var array|null
	 */
	private ?array $table_buffer = null;

	/**
	 * The markdown output being assembled.
	 *
	 * @var string
	 */
	private string $output = '';

	/**
	 * Convert HTML to markdown.
	 *
	 * @since 10.6.0
	 *
	 * @param string $html The HTML string to convert.
	 * @return string The resulting markdown string.
	 */
	public function convert( string $html ): string {
		$html = trim( $html );

		if ( '' === $html ) {
			return '';
		}

		$this->reset_state();

		// Strip script and style elements before parsing — WP_HTML_Processor
		// aborts when it encounters raw text elements it cannot fully handle.
		$html = (string) preg_replace( '/<(script|style)\b[^>]*>.*?<\/\1>/si', '', $html );

		$processor = WP_HTML_Processor::create_fragment( $html );

		if ( null === $processor ) {
			// If the processor cannot be created, return stripped tags as fallback.
			return wp_strip_all_tags( $html );
		}

		while ( $processor->next_token() ) {
			$type = $processor->get_token_type();
			$name = $processor->get_token_name();

			if ( null === $name ) {
				continue;
			}

			if ( '#text' === $type ) {
				$this->handle_text( $processor->get_modifiable_text() );
			} elseif ( '#tag' === $type && ! $processor->is_tag_closer() ) {
				$this->handle_open_tag( $name, $processor );
			} elseif ( '#tag' === $type && $processor->is_tag_closer() ) {
				$this->handle_close_tag( $name );
			}
		}

		// Flush any remaining table buffer.
		if ( null !== $this->table_buffer ) {
			$this->flush_table();
		}

		$result = $this->normalize_output( $this->output );

		/**
		 * Filters the markdown output after HTML-to-markdown conversion.
		 *
		 * @since 10.6.0
		 *
		 * @param string $result The converted markdown string.
		 * @param string $html   The original HTML input.
		 */
		return apply_filters( 'woocommerce_markdown_feed_html_convert', $result, $html );
	}

	/**
	 * Reset all internal state for a new conversion.
	 *
	 * @return void
	 */
	private function reset_state(): void {
		$this->list_stack       = array();
		$this->link_href        = null;
		$this->in_blockquote    = false;
		$this->blockquote_depth = 0;
		$this->table_buffer     = null;
		$this->output           = '';
	}

	/**
	 * Handle a text token.
	 *
	 * @param string $text The text content (already entity-decoded by WP_HTML_Processor).
	 * @return void
	 */
	private function handle_text( string $text ): void {
		// If we're inside a table cell, buffer the text.
		if ( null !== $this->table_buffer && $this->table_buffer['in_cell'] ) {
			$this->table_buffer['cell_text'] .= $text;
			return;
		}

		// Apply blockquote line prefixing if needed.
		if ( $this->in_blockquote ) {
			$text = $this->prefix_blockquote_lines( $text );
		}

		$this->output .= $text;
	}

	/**
	 * Handle an opening HTML tag.
	 *
	 * @param string            $tag_name  The uppercase tag name.
	 * @param WP_HTML_Processor $processor The processor instance for reading attributes.
	 * @return void
	 */
	private function handle_open_tag( string $tag_name, WP_HTML_Processor $processor ): void {
		switch ( $tag_name ) {
			case 'H1':
			case 'H2':
			case 'H3':
			case 'H4':
			case 'H5':
			case 'H6':
				$this->handle_heading_open( $tag_name );
				break;

			case 'P':
				// No prefix needed; paragraph break is on close.
				break;

			case 'STRONG':
			case 'B':
				$this->output .= '**';
				break;

			case 'EM':
			case 'I':
				$this->output .= '*';
				break;

			case 'A':
				$href            = $processor->get_attribute( 'href' );
				$this->link_href = is_string( $href ) ? $href : null;
				$this->output   .= '[';
				break;

			case 'IMG':
				$this->handle_img( $processor );
				break;

			case 'UL':
				$this->list_stack[] = array(
					'type'    => 'ul',
					'counter' => 0,
				);
				break;

			case 'OL':
				$this->list_stack[] = array(
					'type'    => 'ol',
					'counter' => 0,
				);
				break;

			case 'LI':
				$this->handle_li_open();
				break;

			case 'BLOCKQUOTE':
				++$this->blockquote_depth;
				$this->in_blockquote = true;
				$this->output       .= "\n\n" . str_repeat( '> ', $this->blockquote_depth );
				break;

			case 'BR':
				$this->output .= "\n";
				if ( $this->in_blockquote ) {
					$this->output .= str_repeat( '> ', $this->blockquote_depth );
				}
				break;

			case 'TABLE':
				$this->table_buffer = array(
					'rows'        => array(),
					'current_row' => array(),
					'in_cell'     => false,
					'cell_text'   => '',
					'has_header'  => false,
					'in_thead'    => false,
				);
				break;

			case 'THEAD':
				if ( null !== $this->table_buffer ) {
					$this->table_buffer['in_thead']   = true;
					$this->table_buffer['has_header'] = true;
				}
				break;

			case 'TBODY':
			case 'TFOOT':
				// Structural elements; no markdown output needed.
				break;

			case 'TR':
				if ( null !== $this->table_buffer ) {
					$this->table_buffer['current_row'] = array();
				}
				break;

			case 'TD':
			case 'TH':
				if ( null !== $this->table_buffer ) {
					$this->table_buffer['in_cell']   = true;
					$this->table_buffer['cell_text'] = '';
				}
				break;

			case 'HR':
				$this->output .= "\n\n---\n\n";
				break;

			case 'CODE':
				$this->output .= '`';
				break;

			case 'PRE':
				$this->output .= "\n\n```\n";
				break;

			case 'DEL':
			case 'S':
				$this->output .= '~~';
				break;

			case 'SUP':
				$this->output .= '<sup>';
				break;

			case 'SUB':
				$this->output .= '<sub>';
				break;

			default:
				// Unknown tags: pass through, just emit text content.
				break;
		}
	}

	/**
	 * Handle a closing HTML tag.
	 *
	 * @param string $tag_name The uppercase tag name.
	 * @return void
	 */
	private function handle_close_tag( string $tag_name ): void {
		switch ( $tag_name ) {
			case 'H1':
			case 'H2':
			case 'H3':
			case 'H4':
			case 'H5':
			case 'H6':
				$this->output .= "\n\n";
				break;

			case 'P':
				$this->output .= "\n\n";
				break;

			case 'STRONG':
			case 'B':
				$this->output .= '**';
				break;

			case 'EM':
			case 'I':
				$this->output .= '*';
				break;

			case 'A':
				$href = $this->link_href ?? '';
				if ( '' !== $href ) {
					$this->output .= '](' . $href . ')';
				} else {
					$this->output .= ']()';
				}
				$this->link_href = null;
				break;

			case 'UL':
			case 'OL':
				if ( ! empty( $this->list_stack ) ) {
					array_pop( $this->list_stack );
				}
				// Add trailing newline after list.
				$this->output .= "\n";
				break;

			case 'LI':
				// No suffix needed; content already emitted inline.
				break;

			case 'BLOCKQUOTE':
				--$this->blockquote_depth;
				$this->in_blockquote = $this->blockquote_depth > 0;
				$this->output       .= "\n\n";
				break;

			case 'TABLE':
				$this->flush_table();
				break;

			case 'THEAD':
				if ( null !== $this->table_buffer ) {
					$this->table_buffer['in_thead'] = false;
				}
				break;

			case 'TBODY':
			case 'TFOOT':
				// Structural elements; no markdown output needed.
				break;

			case 'TR':
				if ( null !== $this->table_buffer ) {
					$this->table_buffer['rows'][]      = array(
						'cells'     => $this->table_buffer['current_row'],
						'is_header' => $this->table_buffer['in_thead'],
					);
					$this->table_buffer['current_row'] = array();
				}
				break;

			case 'TD':
			case 'TH':
				if ( null !== $this->table_buffer ) {
					$this->table_buffer['current_row'][] = trim( $this->table_buffer['cell_text'] );
					$this->table_buffer['in_cell']       = false;
					$this->table_buffer['cell_text']     = '';
				}
				break;

			case 'CODE':
				$this->output .= '`';
				break;

			case 'PRE':
				$this->output .= "\n```\n\n";
				break;

			case 'DEL':
			case 'S':
				$this->output .= '~~';
				break;

			case 'SUP':
				$this->output .= '</sup>';
				break;

			case 'SUB':
				$this->output .= '</sub>';
				break;

			default:
				// Unknown tags: no suffix.
				break;
		}
	}

	/**
	 * Handle a heading open tag with level offset.
	 *
	 * Description headings are offset by +2 so they nest under the product title
	 * in the markdown feed. H1 becomes ###, H2 becomes ####, etc.
	 *
	 * @param string $tag_name The heading tag name (H1-H6).
	 * @return void
	 */
	private function handle_heading_open( string $tag_name ): void {
		$level  = (int) substr( $tag_name, 1 );
		$level  = min( $level + 2, 6 );
		$hashes = str_repeat( '#', $level );

		$this->output .= "\n\n" . $hashes . ' ';
	}

	/**
	 * Handle an IMG tag (void element).
	 *
	 * @param WP_HTML_Processor $processor The processor for reading attributes.
	 * @return void
	 */
	private function handle_img( WP_HTML_Processor $processor ): void {
		$src = $processor->get_attribute( 'src' );
		$alt = $processor->get_attribute( 'alt' );

		if ( empty( $src ) || ! is_string( $src ) ) {
			return;
		}

		$alt_text = is_string( $alt ) ? $alt : '';

		// If inside a table cell, buffer the image markdown.
		if ( null !== $this->table_buffer && $this->table_buffer['in_cell'] ) {
			$this->table_buffer['cell_text'] .= '![' . $alt_text . '](' . $src . ')';
			return;
		}

		$this->output .= '![' . $alt_text . '](' . $src . ')';
	}

	/**
	 * Handle an LI open tag.
	 *
	 * Determines the correct list marker (- or 1.) based on the parent list type
	 * and handles indentation for nested lists.
	 *
	 * @return void
	 */
	private function handle_li_open(): void {
		if ( empty( $this->list_stack ) ) {
			// No list context; emit as a plain dash.
			$this->output .= "\n- ";
			return;
		}

		$depth  = count( $this->list_stack ) - 1;
		$indent = str_repeat( '  ', $depth );
		$list   = &$this->list_stack[ count( $this->list_stack ) - 1 ];

		if ( 'ol' === $list['type'] ) {
			++$list['counter'];
			$this->output .= "\n" . $indent . $list['counter'] . '. ';
		} else {
			$this->output .= "\n" . $indent . '- ';
		}
	}

	/**
	 * Prefix lines with blockquote markers for nested blockquotes.
	 *
	 * @param string $text The text to prefix.
	 * @return string The text with blockquote prefixes on newlines.
	 */
	private function prefix_blockquote_lines( string $text ): string {
		$prefix = str_repeat( '> ', $this->blockquote_depth );

		return str_replace( "\n", "\n" . $prefix, $text );
	}

	/**
	 * Flush the table buffer and emit a markdown table.
	 *
	 * @return void
	 */
	private function flush_table(): void {
		if ( null === $this->table_buffer || empty( $this->table_buffer['rows'] ) ) {
			$this->table_buffer = null;
			return;
		}

		$rows               = $this->table_buffer['rows'];
		$this->table_buffer = null;

		// Determine column count from the widest row.
		$col_count = 0;
		foreach ( $rows as $row ) {
			$cell_count = count( $row['cells'] );
			if ( $cell_count > $col_count ) {
				$col_count = $cell_count;
			}
		}

		if ( 0 === $col_count ) {
			return;
		}

		$has_header_rows = $this->has_header_rows( $rows );

		// If no explicit header, inject a separator row after the first row.
		if ( ! $has_header_rows ) {
			$rows[0]['is_header'] = true;
		}

		$this->output .= "\n\n";

		$header_emitted = false;

		foreach ( $rows as $row ) {
			$cells = $this->pad_cells( $row['cells'], $col_count );

			$this->output .= '| ' . implode( ' | ', $cells ) . " |\n";

			// Emit separator after the header row.
			if ( $row['is_header'] && ! $header_emitted ) {
				$this->output  .= '| ' . implode( ' | ', array_fill( 0, $col_count, '---' ) ) . " |\n";
				$header_emitted = true;
			}
		}

		$this->output .= "\n";
	}

	/**
	 * Check if any rows in the table are header rows.
	 *
	 * @param array $rows The table rows.
	 * @return bool Whether any rows are header rows.
	 */
	private function has_header_rows( array $rows ): bool {
		foreach ( $rows as $row ) {
			if ( $row['is_header'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Pad a cells array to the required column count.
	 *
	 * @param array $cells     The cells to pad.
	 * @param int   $col_count The target column count.
	 * @return array The padded cells array.
	 */
	private function pad_cells( array $cells, int $col_count ): array {
		$cell_count = count( $cells );
		while ( $cell_count < $col_count ) {
			$cells[] = '';
			++$cell_count;
		}
		return $cells;
	}

	/**
	 * Normalize the final markdown output.
	 *
	 * Collapses excessive blank lines, trims leading/trailing whitespace.
	 *
	 * @param string $markdown The raw markdown output.
	 * @return string The normalized markdown.
	 */
	private function normalize_output( string $markdown ): string {
		// Collapse 3+ consecutive newlines into 2 (one blank line).
		$markdown = (string) preg_replace( "/\n{3,}/", "\n\n", $markdown );

		return trim( $markdown );
	}
}
