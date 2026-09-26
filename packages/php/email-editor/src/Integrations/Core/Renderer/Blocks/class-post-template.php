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
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
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
 * holds the final `<ul><li>…</li></ul>`), so this renderer never re-runs the query. Each item's
 * image is extracted and rebuilt as a clean, responsive `<img>` sitting directly in its grid cell —
 * the same shape the Gallery renderer emits. This is deliberate: the images WordPress renders inside
 * a post-template `<li>` are wrapped in fixed-width, auto-layout tables (`<td width="520">`) that
 * were never sized for email, and a nested `width: 100%` image inside them collapses to a few pixels
 * in Gmail (the width has no resolvable basis). Hoisting the image into the grid cell gives it a
 * definite basis so it fills its column and scales down on mobile. An item with no image falls back
 * to its original markup untouched (text content stacks correctly on its own).
 */
class Post_Template extends Abstract_Block_Renderer {
	/**
	 * Upper bound on grid columns, matching the maximum the core grid layout control allows (its
	 * Columns range control tops out at 16). Honors any column count an author can pick in the editor,
	 * while still bounding an out-of-range hand-edited value so it can't emit a runaway number of cells.
	 */
	private const MAX_COLUMNS = 16;

	/**
	 * Per-cell padding (px) that stands in for the grid's `gap` between items.
	 */
	private const CELL_PADDING = 8;

	/**
	 * Responsive image style applied to every rebuilt grid image. `width: 100%` fills the column,
	 * `max-width: 100%` lets it scale down on narrow viewports, and `height: auto` keeps the ratio.
	 * The explicit `width` attribute (set alongside this) is the Outlook fallback, since Outlook
	 * ignores `max-width`.
	 */
	private const IMAGE_STYLE = 'border: 0; line-height: 100%; width: 100%; max-width: 100%; height: auto; display: block;';

	/**
	 * Renders the post-template block content using a table-based grid layout.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		if ( '' === trim( $block_content ) ) {
			return $block_content;
		}

		$dom          = new Dom_Document_Helper( $block_content );
		$list_element = $this->find_post_template_list( $dom );

		// If we can't find the post-template list, leave the original content untouched so we never
		// degrade output for markup shapes we don't recognize.
		if ( null === $list_element ) {
			return $block_content;
		}

		$items = $this->extract_list_items( $dom, $list_element );
		if ( empty( $items ) ) {
			return $block_content;
		}

		$columns = $this->get_column_count( $parsed_block, $dom, $list_element );

		// Single-column (list/flow/constrained) layouts already stack correctly in email; only the
		// multi-column grid/flex layouts need to be rebuilt as a table.
		if ( $columns < 2 ) {
			return $block_content;
		}

		// The layout width (minus the email's root padding) is what each cell's images are sized to,
		// so an image CDN / Outlook get a concrete pixel width rather than the intrinsic file width.
		$layout_width = (int) Styles_Helper::parse_value( $rendering_context->get_layout_width_without_padding() );

		return $this->build_grid_table( $items, $columns, $dom, $list_element, $layout_width );
	}

	/**
	 * Locate the post-template list within the rendered content.
	 *
	 * The list is matched by its `wp-block-post-template` class rather than by being the first
	 * `<ul>`, so a sibling list that happens to appear earlier in the markup can't be mistaken for
	 * the repeater. Returns null when no such list is present.
	 *
	 * @param Dom_Document_Helper $dom Parsed block content.
	 * @return \DOMElement|null
	 */
	private function find_post_template_list( Dom_Document_Helper $dom ): ?\DOMElement {
		foreach ( $dom->find_elements( 'ul' ) as $list_element ) {
			// Match `wp-block-post-template` as a whole class token, not a substring, so an unrelated
			// list whose class merely contains the string (e.g. `my-wp-block-post-template-wrapper`)
			// isn't mistaken for the repeater and rebuilt.
			$classes = preg_split( '/\s+/', trim( $dom->get_attribute_value( $list_element, 'class' ) ) );
			if ( is_array( $classes ) && in_array( 'wp-block-post-template', $classes, true ) ) {
				return $list_element;
			}
		}
		return null;
	}

	/**
	 * Extract the inner HTML of each direct-child `<li>` of the post-template list.
	 *
	 * Only direct children are collected, so a nested list inside a post's content (e.g. a
	 * `core/list` in an excerpt) contributes its markup to the item it lives in rather than being
	 * mistaken for additional repeater items.
	 *
	 * @param Dom_Document_Helper $dom Parsed block content.
	 * @param \DOMElement         $list_element The post-template list element.
	 * @return array<int, string> Inner HTML of each list item, in document order.
	 */
	private function extract_list_items( Dom_Document_Helper $dom, \DOMElement $list_element ): array {
		$items = array();
		foreach ( $list_element->childNodes as $node ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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
	 * @param array               $parsed_block Parsed block data.
	 * @param Dom_Document_Helper $dom Parsed block content.
	 * @param \DOMElement         $list_element The post-template list element.
	 * @return int Column count (at least 1).
	 */
	private function get_column_count( array $parsed_block, Dom_Document_Helper $dom, \DOMElement $list_element ): int {
		$layout = $parsed_block['attrs']['layout'] ?? array();
		$type   = is_array( $layout ) && isset( $layout['type'] ) && is_string( $layout['type'] ) ? $layout['type'] : '';

		// A layout that is neither grid nor flex (default, constrained, flow) stacks in one column.
		if ( '' !== $type && 'grid' !== $type && 'flex' !== $type ) {
			return 1;
		}

		$columns = 0;
		if ( is_array( $layout ) && isset( $layout['columnCount'] ) && is_numeric( $layout['columnCount'] ) ) {
			$columns = (int) $layout['columnCount'];
		}

		// Fallback: read the `columns-N` class WordPress core adds to the list wrapper.
		if ( $columns < 1 && preg_match( '/(?:^|\s)columns-(\d+)(?:\s|$)/', $dom->get_attribute_value( $list_element, 'class' ), $matches ) ) {
			$columns = (int) $matches[1];
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
	 * @param array<int, string>  $items Inner HTML of each list item.
	 * @param int                 $columns Number of columns.
	 * @param Dom_Document_Helper $dom Parsed block content.
	 * @param \DOMElement         $list_element The post-template list element (for wrapper classes).
	 * @param int                 $layout_width Available layout width in px.
	 * @return string Grid table HTML.
	 */
	private function build_grid_table( array $items, int $columns, Dom_Document_Helper $dom, \DOMElement $list_element, int $layout_width ): string {
		$cell_width = $this->get_cell_width( $layout_width, $columns );

		$rows       = array();
		$item_count = count( $items );
		for ( $i = 0; $i < $item_count; $i += $columns ) {
			$rows[] = $this->build_grid_row( array_slice( $items, $i, $columns ), $columns, $cell_width );
		}
		$grid_content = implode( '', $rows );

		$original_class = $dom->get_attribute_value( $list_element, 'class' );

		$table_attrs = array(
			'class' => trim( 'email-block-post-template ' . Html_Processing_Helper::clean_css_classes( $original_class ) ),
			'style' => 'width: 100%; border-collapse: collapse;',
			'width' => '100%',
		);

		return Table_Wrapper_Helper::render_table_wrapper( $grid_content, $table_attrs );
	}

	/**
	 * Estimate the rendered pixel width of a single grid cell's content area.
	 *
	 * The layout width is split evenly across the columns and the per-cell padding is removed from
	 * both sides. Used to give each rebuilt image a concrete `width` attribute (the Outlook fallback)
	 * instead of the intrinsic file width, which Outlook would otherwise honor literally and blow the
	 * cell open.
	 *
	 * @param int $layout_width Available layout width in px.
	 * @param int $columns Number of columns (>= 2; a grid is only built for multi-column layouts).
	 * @return int Cell content width in px (at least 1).
	 */
	private function get_cell_width( int $layout_width, int $columns ): int {
		$cell_width = (int) floor( $layout_width / $columns ) - ( 2 * self::CELL_PADDING );
		return max( 1, $cell_width );
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
	 * @param int                $cell_width Cell content width in px.
	 * @return string Row table HTML.
	 */
	private function build_grid_row( array $row_items, int $columns, int $cell_width ): string {
		$cell_width_percent = 100 / $columns;
		$cells              = '';

		for ( $col = 0; $col < $columns; $col++ ) {
			$cell_content = isset( $row_items[ $col ] ) ? $this->prepare_item_content( $row_items[ $col ], $cell_width ) : '';
			$cell_attrs   = array(
				'style'  => sprintf(
					'width: %s; padding: %dpx; vertical-align: top; text-align: center;',
					Html_Processing_Helper::sanitize_css_value( sprintf( '%.4f%%', $cell_width_percent ) ),
					self::CELL_PADDING
				),
				'valign' => 'top',
			);
			$cells       .= Table_Wrapper_Helper::render_table_cell( $cell_content, $cell_attrs );
		}

		return sprintf(
			'<table role="presentation" style="width: %s; border-collapse: collapse; table-layout: fixed;"><tr>%s</tr></table>',
			Html_Processing_Helper::sanitize_css_value( '100%' ),
			$cells
		);
	}

	/**
	 * Turn a rendered `<li>`'s inner HTML into email-safe cell content.
	 *
	 * Each image in the item is rebuilt as a clean, responsive `<img>` (preserving its link) sitting
	 * directly in the cell, so its width resolves against the grid column instead of collapsing inside
	 * the fixed-width wrapper tables WordPress renders around it. Any non-image content the card holds
	 * (post title, date, excerpt) is kept below the image, so a post grid isn't reduced to bare images.
	 *
	 * When the card is image-only (e.g. a featured-image sponsor grid) the leftover is nothing but the
	 * now-empty wrapper shells, which are dropped so the output stays a clean logo grid. An item with no
	 * image at all is returned unchanged — its text stacks correctly without intervention.
	 *
	 * @param string $item_html Inner HTML of a single list item.
	 * @param int    $cell_width Cell content width in px.
	 * @return string Cell content HTML.
	 */
	private function prepare_item_content( string $item_html, int $cell_width ): string {
		// `stripos` (not `strpos`) so an uppercase `<IMG>` fast-path isn't skipped. In practice the
		// item HTML arrives lowercased by DOM serialization, but this keeps the guard correct if a
		// caller ever passes raw markup.
		if ( false === stripos( $item_html, '<img' ) ) {
			return $item_html;
		}

		$item_dom       = new Dom_Document_Helper( $item_html );
		$images         = array();
		$remove_targets = array();

		foreach ( $item_dom->find_elements( 'img' ) as $img_element ) {
			// Record every image up front so none can linger in the preserved remainder — whether we
			// rebuild it below or drop it as unrenderable. Targets are computed now (before any removal)
			// so the media counts stay accurate.
			$remove_targets[] = $this->find_image_removal_target( $img_element );

			$normalized_img = $this->normalize_image_for_email( $item_dom->get_outer_html( $img_element ), $cell_width );
			if ( '' === $normalized_img ) {
				// The image had no usable src (e.g. an unsafe URL esc_url rejected); drop it rather than
				// leak the original unsanitized tag through the remainder.
				continue;
			}

			$href = $this->find_link_href( $img_element );
			if ( '' !== $href ) {
				$images[] = '<a href="' . esc_url( $href ) . '">' . $normalized_img . '</a>';
			} else {
				$images[] = $normalized_img;
			}
		}

		// The `<img` match was not a real image element (e.g. it sat inside a comment); leave the
		// content untouched.
		if ( empty( $remove_targets ) ) {
			return $item_html;
		}

		// Strip every image found — including any we couldn't rebuild — so an unrenderable, unsanitized
		// `<img>` (e.g. one carrying `onerror`) can never survive through the remainder, then keep
		// whatever real content remains (title/date/excerpt). Empty wrapper shells are dropped.
		foreach ( $remove_targets as $target ) {
			$item_dom->remove_element( $target );
		}

		return implode( '', $images ) . $this->extract_remaining_content( $item_dom );
	}

	/**
	 * Choose which element to strip when hoisting an image, so the preserved remainder is clean.
	 *
	 * Climbs from the image through every ancestor that wraps nothing but that single image — no text,
	 * no other media — and returns the outermost such wrapper. This removes the whole empty
	 * `<figure>`/`<a>`/layout-table shell WordPress renders around a featured image in one go, rather
	 * than leaving hollow, padded cells behind. Climbing stops as soon as an ancestor holds real
	 * content, so a sibling title/date (outside the image's wrapper) and a `<figcaption>` (whose text
	 * lives on the figure) are both preserved.
	 *
	 * @param \DOMElement $img_element The image element being hoisted.
	 * @return \DOMElement The element to remove from the item.
	 */
	private function find_image_removal_target( \DOMElement $img_element ): \DOMElement {
		$target = $img_element;
		$parent = $img_element->parentNode; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		while ( $parent instanceof \DOMElement ) {
			// Stop once the ancestor carries text (e.g. a caption or a sibling title) or wraps more
			// than just this one image — removing it would take real content with it.
			if ( '' !== trim( $parent->textContent ) || 1 !== $this->count_media_descendants( $parent ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				break;
			}
			$target = $parent;
			$parent = $parent->parentNode; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}
		return $target;
	}

	/**
	 * Count the media elements (images and embeds) contained within an element.
	 *
	 * @param \DOMElement $element The element to inspect.
	 * @return int
	 */
	private function count_media_descendants( \DOMElement $element ): int {
		$count = $element->getElementsByTagName( 'img' )->length;
		foreach ( array( 'video', 'audio', 'iframe', 'svg' ) as $tag_name ) {
			$count += $element->getElementsByTagName( $tag_name )->length;
		}
		return $count;
	}

	/**
	 * Read back the card content left after the images were removed, or an empty string when nothing
	 * but structural wrapper shells remain (so image-only cards stay a clean grid).
	 *
	 * @param Dom_Document_Helper $item_dom The item DOM after image removal.
	 * @return string
	 */
	private function extract_remaining_content( Dom_Document_Helper $item_dom ): string {
		$this->strip_unsafe_markup( $item_dom );

		$remainder = $item_dom->get_root_html();

		// Treat the remainder as empty unless it carries visible text or embedded media — otherwise it
		// is just the leftover wrapper markup (empty figures/tables) the image used to live in.
		if ( '' === trim( str_replace( "\xc2\xa0", '', wp_strip_all_tags( $remainder ) ) )
			&& ! preg_match( '/<(img|video|audio|iframe|svg)\b/i', $remainder ) ) {
			return '';
		}

		return $remainder;
	}

	/**
	 * Strip markup that has no place in an email from the preserved remainder: `<script>`/`<style>`
	 * elements and inline event-handler (`on*`) attributes.
	 *
	 * The images beside this content are already sanitized when they're rebuilt, so this keeps the
	 * reconstructed cell internally consistent. It intentionally leaves `style` attributes and all
	 * structural markup in place, so legitimate card content (title/date/excerpt) renders unchanged —
	 * core never emits scripts or handlers there, making this a no-op for real content. Scoped to this
	 * renderer's grid path only; it operates on the local item DOM and touches no shared helper.
	 *
	 * @param Dom_Document_Helper $item_dom The item DOM to clean in place.
	 */
	private function strip_unsafe_markup( Dom_Document_Helper $item_dom ): void {
		foreach ( array( 'script', 'style' ) as $tag_name ) {
			foreach ( $item_dom->find_elements( $tag_name ) as $element ) {
				$item_dom->remove_element( $element );
			}
		}

		foreach ( $item_dom->find_elements( '*' ) as $element ) {
			$attributes = $element->attributes;
			if ( null === $attributes ) {
				continue;
			}
			// Collect handler attribute names first, then remove — mutating the live attribute map
			// mid-iteration would skip entries.
			$handler_attributes = array();
			foreach ( $attributes as $attribute ) {
				if ( 0 === stripos( $attribute->name, 'on' ) ) {
					$handler_attributes[] = $attribute->name;
				}
			}
			foreach ( $handler_attributes as $attribute_name ) {
				$element->removeAttribute( $attribute_name );
			}
		}
	}

	/**
	 * Return the href of the nearest ancestor `<a>` of the given image, or an empty string.
	 *
	 * @param \DOMElement $img_element The image element.
	 * @return string
	 */
	private function find_link_href( \DOMElement $img_element ): string {
		$parent = $img_element->parentNode; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		while ( $parent instanceof \DOMElement ) {
			if ( 'a' === $parent->tagName ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				return $parent->getAttribute( 'href' );
			}
			$parent = $parent->parentNode; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}
		return '';
	}

	/**
	 * Sanitize a raw `<img>` and normalize it for a grid cell.
	 *
	 * Reuses {@see Html_Processing_Helper::sanitize_image_html()} for the security pass (attribute
	 * allowlist, URL/style sanitizing), then pins the display width to the cell and replaces the
	 * web-only styling with the responsive email style. WordPress stores the intrinsic file width
	 * (e.g. `width="1024"`) which Outlook honors literally, and the core web style carries a
	 * `width: 100%` that collapses once the image is out of a CSS grid — so both are overwritten with
	 * a concrete cell width plus {@see self::IMAGE_STYLE}.
	 *
	 * @param string $img_html Raw `<img>` HTML.
	 * @param int    $cell_width Cell content width in px.
	 * @return string Normalized `<img>` HTML, or an empty string when the image has no usable src.
	 */
	private function normalize_image_for_email( string $img_html, int $cell_width ): string {
		if ( '' === $img_html ) {
			return '';
		}

		$sanitized = Html_Processing_Helper::sanitize_image_html( $img_html );

		$html = new \WP_HTML_Tag_Processor( $sanitized );
		if ( ! $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			return '';
		}

		$src = $html->get_attribute( 'src' );
		if ( ! is_string( $src ) || '' === $src ) {
			return '';
		}

		// Scale the stored height to the cell width so the image keeps its aspect ratio in clients
		// that read the attributes (Outlook). A missing/oversized/non-numeric dimension just leaves
		// the height to `height: auto` in the style.
		$raw_width  = $html->get_attribute( 'width' );
		$raw_height = $html->get_attribute( 'height' );
		$width      = is_string( $raw_width ) && is_numeric( $raw_width ) ? (int) $raw_width : 0;
		$height     = is_string( $raw_height ) && is_numeric( $raw_height ) ? (int) $raw_height : 0;
		if ( $width > 0 && $height > 0 ) {
			$scaled_height = max( 1, (int) round( $height * ( $cell_width / $width ) ) );
			$html->set_attribute( 'height', esc_attr( (string) $scaled_height ) );
		} else {
			$html->remove_attribute( 'height' );
		}

		$html->set_attribute( 'width', esc_attr( (string) $cell_width ) );

		// Drop the web-only class (harmless in email, and the core/image renderer strips it too) and
		// replace the web styling with the responsive email style.
		$html->remove_attribute( 'class' );
		$html->set_attribute( 'style', esc_attr( self::IMAGE_STYLE ) );

		return $html->get_updated_html();
	}
}
