<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Html_Processing_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Renders a `core/post-template` block (the repeater inside a Query Loop) for email.
 *
 * WordPress renders `core/post-template` as a `<ul class="wp-block-post-template">` whose grid is
 * laid out with CSS grid/flex. Email clients (Outlook especially) don't support those, so the grid
 * collapses to a single stacked column. This renderer re-flows the already-rendered `<li>` items
 * into an email-safe, table-based column layout — mirroring how the Gallery renderer arranges
 * images (see {@see Gallery::build_gallery_table()}).
 *
 * The list items arrive already rendered (post-template is a dynamic block, so `$block_content`
 * holds the final `<ul><li>…</li></ul>`), so this renderer only rearranges them — it never
 * re-runs the query.
 */
class Post_Template extends Abstract_Block_Renderer {
	/**
	 * Upper bound on grid columns, matching the range the core Gallery renderer allows. Keeps a very
	 * wide grid from producing unreadably narrow cells while still honoring the author's column choice.
	 */
	private const MAX_COLUMNS = 8;

	/**
	 * Per-cell padding (px) that stands in for the grid's `gap` between items.
	 */
	private const CELL_PADDING = 8;

	/**
	 * Renders the post-template block content using a table-based grid layout.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$items = $this->extract_list_items( $block_content );

		// If we can't find the expected list, leave the original content untouched so we never
		// degrade output for markup shapes we don't recognize.
		if ( empty( $items ) ) {
			return $block_content;
		}

		$columns = $this->get_column_count( $parsed_block, $block_content );

		// Single-column (list/flow/constrained) layouts already stack correctly in email; only the
		// multi-column grid/flex layouts need to be rebuilt as a table.
		if ( $columns < 2 ) {
			return $block_content;
		}

		return $this->build_grid_table( $items, $columns, $block_content );
	}

	/**
	 * Extract the inner HTML of each direct-child `<li>` of the post-template list.
	 *
	 * Only the post-template's own list is handled: a nested list inside post content (e.g. a
	 * `core/list` in an excerpt) must not be mistaken for the repeater, so the wrapper is required to
	 * carry the `wp-block-post-template` class before its items are collected.
	 *
	 * @param string $block_content The rendered post-template block HTML.
	 * @return array<int, string> Inner HTML of each list item, in document order.
	 */
	private function extract_list_items( string $block_content ): array {
		if ( '' === trim( $block_content ) ) {
			return array();
		}

		$dom  = new Dom_Document_Helper( $block_content );
		$list = $dom->find_element( 'ul' );
		if ( null === $list ) {
			return array();
		}

		if ( false === strpos( $dom->get_attribute_value( $list, 'class' ), 'wp-block-post-template' ) ) {
			return array();
		}

		$items = array();
		foreach ( $list->childNodes as $node ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( $node instanceof \DOMElement && 'li' === $node->tagName ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$items[] = $dom->get_element_inner_html( $node );
			}
		}

		return $items;
	}

	/**
	 * Determine how many columns the grid should render.
	 *
	 * Prefers the block's own `layout.columnCount` attribute and falls back to the `columns-N` class
	 * WordPress core stamps on the rendered list, so it still works when the parsed attributes are
	 * sparse. Non-grid/flex layouts always resolve to a single (stacked) column.
	 *
	 * @param array  $parsed_block Parsed block data.
	 * @param string $block_content The rendered post-template block HTML.
	 * @return int Column count (at least 1).
	 */
	private function get_column_count( array $parsed_block, string $block_content ): int {
		$layout = $parsed_block['attrs']['layout'] ?? array();
		$type   = is_array( $layout ) && isset( $layout['type'] ) ? (string) $layout['type'] : '';

		// A layout that is neither grid nor flex (default, constrained, flow) stacks in one column.
		if ( '' !== $type && 'grid' !== $type && 'flex' !== $type ) {
			return 1;
		}

		$columns = 0;
		if ( is_array( $layout ) && isset( $layout['columnCount'] ) && is_numeric( $layout['columnCount'] ) ) {
			$columns = (int) $layout['columnCount'];
		}

		// Fallback: read the `columns-N` class WordPress core adds to the list wrapper.
		if ( $columns < 1 ) {
			$dom  = new Dom_Document_Helper( $block_content );
			$list = $dom->find_element( 'ul' );
			if ( $list && preg_match( '/(?:^|\s)columns-(\d+)(?:\s|$)/', $dom->get_attribute_value( $list, 'class' ), $matches ) ) {
				$columns = (int) $matches[1];
			}
		}

		if ( $columns < 1 ) {
			return 1;
		}

		return min( self::MAX_COLUMNS, $columns );
	}

	/**
	 * Build the grid as one `<table>` per row wrapped in a container table.
	 *
	 * Follows the tiled-gallery pattern ({@see Gallery::build_gallery_table()}): items are chunked
	 * into rows of `$columns` and each row is its own fixed-layout table, so every cell keeps a
	 * consistent width regardless of how many items the final (possibly partial) row holds.
	 *
	 * @param array<int, string> $items Inner HTML of each list item.
	 * @param int                $columns Number of columns.
	 * @param string             $block_content The original block HTML (for wrapper classes).
	 * @return string Grid table HTML.
	 */
	private function build_grid_table( array $items, int $columns, string $block_content ): string {
		$rows       = array();
		$item_count = count( $items );
		for ( $i = 0; $i < $item_count; $i += $columns ) {
			$rows[] = $this->build_grid_row( array_slice( $items, $i, $columns ), $columns );
		}
		$grid_content = implode( '', $rows );

		$original_class = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'ul', 'class' ) ?? '';

		$table_attrs = array(
			'class' => trim( 'email-block-post-template ' . Html_Processing_Helper::clean_css_classes( $original_class ) ),
			'style' => 'width: 100%; border-collapse: collapse;',
			'width' => '100%',
		);

		return Table_Wrapper_Helper::render_table_wrapper( $grid_content, $table_attrs );
	}

	/**
	 * Build a single grid row as its own fixed-layout table.
	 *
	 * Every cell is a fixed `100 / $columns` percent wide and a partial final row is padded with
	 * empty cells, so items stay aligned to their column and keep a uniform width across rows (unlike
	 * the gallery, which stretches a partial row to fill the width). This matches how a CSS grid keeps
	 * column tracks consistent — important when the items are logos that shouldn't change size row to
	 * row.
	 *
	 * @param array<int, string> $row_items Inner HTML of the items in this row.
	 * @param int                $columns Total number of columns.
	 * @return string Row table HTML.
	 */
	private function build_grid_row( array $row_items, int $columns ): string {
		$cell_width_percent = 100 / $columns;
		$cells              = '';

		for ( $col = 0; $col < $columns; $col++ ) {
			$cell_attrs = array(
				'style'  => sprintf(
					'width: %s; padding: %dpx; vertical-align: top; text-align: center;',
					Html_Processing_Helper::sanitize_css_value( sprintf( '%.4f%%', $cell_width_percent ) ),
					self::CELL_PADDING
				),
				'valign' => 'top',
			);
			$cells     .= Table_Wrapper_Helper::render_table_cell( $row_items[ $col ] ?? '', $cell_attrs );
		}

		return sprintf(
			'<table role="presentation" style="width: %s; border-collapse: collapse; table-layout: fixed;"><tr>%s</tr></table>',
			Html_Processing_Helper::sanitize_css_value( '100%' ),
			$cells
		);
	}
}
