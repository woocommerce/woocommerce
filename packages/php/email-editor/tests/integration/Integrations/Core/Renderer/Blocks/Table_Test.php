<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

/**
 * Integration test for Table class
 */
class Table_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Table renderer instance
	 *
	 * @var Table
	 */
	private $table_renderer;

	/**
	 * Content of the table block
	 *
	 * @var string
	 */
	private $table_content = '
    <figure class="wp-block-table">
        <table>
            <thead>
                <tr>
                    <th>Header 1</th>
                    <th>Header 2</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Cell 1</td>
                    <td>Cell 2</td>
                </tr>
                <tr>
                    <td>Cell 3</td>
                    <td>Cell 4</td>
                </tr>
            </tbody>
        </table>
    </figure>
  ';

	/**
	 * Simple table content without figure wrapper
	 *
	 * @var string
	 */
	private $simple_table_content = '
    <table>
        <thead>
            <tr>
                <th>Header 1</th>
                <th>Header 2</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Cell 1</td>
                <td>Cell 2</td>
            </tr>
        </tbody>
    </table>
  ';

	/**
	 * Parse table block configuration
	 *
	 * @var array
	 */
	private $parsed_table = array(
		'blockName'    => 'core/table',
		'attrs'        => array(
			'textAlign' => 'left',
			'style'     => array(),
		),
		'email_attrs'  => array(
			'width' => '640px',
			'color' => '#000000',
		),
		'innerBlocks'  => array(),
		'innerHTML'    => '',
		'innerContent' => array(),
	);

	/**
	 * Instance of Rendering_Context class
	 *
	 * @var Rendering_Context
	 */
	private $rendering_context;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->table_renderer    = new Table();
		$theme_controller        = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Test it renders table content
	 */
	public function testItRendersTableContent(): void {
		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $this->table_content;

		$rendered = $this->table_renderer->render( $this->table_content, $parsed_table, $this->rendering_context );
		$this->assertStringContainsString( 'Header 1', $rendered );
		$this->assertStringContainsString( 'Header 2', $rendered );
		$this->assertStringContainsString( 'Cell 1', $rendered );
		$this->assertStringContainsString( 'Cell 2', $rendered );
		$this->assertStringContainsString( 'Cell 3', $rendered );
		$this->assertStringContainsString( 'Cell 4', $rendered );
	}

	/**
	 * Test it extracts table from figure wrapper
	 */
	public function testItExtractsTableFromFigureWrapper(): void {
		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $this->table_content;

		$rendered = $this->table_renderer->render( $this->table_content, $parsed_table, $this->rendering_context );
		$this->assertStringNotContainsString( '<figure', $rendered );
		$this->assertStringNotContainsString( '</figure>', $rendered );
		$this->assertStringContainsString( '<table', $rendered );
		$this->assertStringContainsString( '</table>', $rendered );
	}

	/**
	 * Test it renders table without figure wrapper
	 */
	public function testItRendersTableWithoutFigureWrapper(): void {
		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $this->simple_table_content;

		$rendered = $this->table_renderer->render( $this->simple_table_content, $parsed_table, $this->rendering_context );
		$this->assertStringContainsString( 'Header 1', $rendered );
		$this->assertStringContainsString( 'Cell 1', $rendered );
		$this->assertStringContainsString( '<table', $rendered );
		$this->assertStringContainsString( '</table>', $rendered );
	}

	/**
	 * Test it renders email-compatible table attributes
	 */
	public function testItRendersEmailCompatibleTableAttributes(): void {
		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $this->simple_table_content;

		$rendered = $this->table_renderer->render( $this->simple_table_content, $parsed_table, $this->rendering_context );
		$this->assertStringContainsString( 'border="1"', $rendered );
		$this->assertStringContainsString( 'cellpadding="8"', $rendered );
		$this->assertStringContainsString( 'cellspacing="0"', $rendered );
		$this->assertStringContainsString( 'role="presentation"', $rendered );
		$this->assertStringContainsString( 'width="100%"', $rendered );
		$this->assertStringContainsString( 'border-collapse: collapse', $rendered );
	}

	/**
	 * Test it renders email-compatible cell attributes
	 */
	public function testItRendersEmailCompatibleCellAttributes(): void {
		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $this->simple_table_content;

		$rendered = $this->table_renderer->render( $this->simple_table_content, $parsed_table, $this->rendering_context );
		$this->assertStringContainsString( 'valign="top"', $rendered );
		$this->assertStringContainsString( 'vertical-align: top', $rendered );
		$this->assertStringContainsString( 'border: 1px solid', $rendered );
		$this->assertStringContainsString( 'padding: 8px', $rendered );
	}

	/**
	 * Test it preserves figcaption content as table caption
	 */
	public function testItPreservesFigcaptionAsCaption(): void {
		$table_with_caption = '
		<figure class="wp-block-table">
			<table>
				<thead>
					<tr>
						<th>Header 1</th>
						<th>Header 2</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Cell 1</td>
						<td>Cell 2</td>
					</tr>
				</tbody>
			</table>
			<figcaption>Table caption text</figcaption>
		</figure>
		';

		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $table_with_caption;

		$rendered = $this->table_renderer->render( $table_with_caption, $parsed_table, $this->rendering_context );

		// Check that the caption content is preserved with proper styling outside the table.
		$this->assertStringContainsString( '<div style="text-align: center; margin-top: 8px;', $rendered );
		$this->assertStringContainsString( '>Table caption text</div>', $rendered );
		// Check that the caption is not inside the table element.
		$this->assertStringNotContainsString( '<caption', $rendered );
	}

	/**
	 * Test it renders tables with rich content (links, paragraphs, etc.)
	 */
	public function testItRendersTablesWithRichContent(): void {
		$table_with_rich_content = '
		<figure class="wp-block-table">
			<table>
				<thead>
					<tr>
						<th>Header with <strong>bold</strong></th>
						<th>Header with <em>italic</em></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Cell with <a href="https://example.com">link</a></td>
						<td>Cell with <span style="color: red;">styled text</span></td>
					</tr>
					<tr>
						<td>Cell with <p>paragraph</p></td>
						<td>Cell with <code>code</code></td>
					</tr>
				</tbody>
			</table>
		</figure>
		';

		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $table_with_rich_content;

		$rendered = $this->table_renderer->render( $table_with_rich_content, $parsed_table, $this->rendering_context );

		// Check that rich content is preserved.
		$this->assertStringContainsString( '<strong>bold</strong>', $rendered );
		$this->assertStringContainsString( '<em>italic</em>', $rendered );
		$this->assertStringContainsString( '<a href="https://example.com">link</a>', $rendered );
		$this->assertStringContainsString( '<span style="color: red;">styled text</span>', $rendered );
		$this->assertStringContainsString( '<p>paragraph</p>', $rendered );
		$this->assertStringContainsString( '<code>code</code>', $rendered );
	}

	/**
	 * Test it renders striped tables with background styling and header/footer borders
	 */
	public function testItRendersStripedTablesWithBackgroundStyling(): void {
		$striped_table_content = '
		<!-- wp:table {"className":"is-style-stripes","backgroundColor":"light-green-cyan"} -->
		<figure class="wp-block-table is-style-stripes">
			<table class="has-light-green-cyan-background-color has-background has-fixed-layout">
				<thead>
					<tr>
						<th>Header</th>
						<th>Number</th>
						<th>Col 3</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Test</td>
						<td>One</td>
						<td>Photo</td>
					</tr>
					<tr>
						<td>Test</td>
						<td>Two</td>
						<td>Test</td>
					</tr>
					<tr>
						<td>Test</td>
						<td>Three</td>
						<td>This</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td>Footer</td>
						<td></td>
						<td></td>
					</tr>
				</tfoot>
			</table>
			<figcaption class="wp-element-caption">Table caption.</figcaption>
		</figure>
		<!-- /wp:table -->
		';

		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $striped_table_content;
		$parsed_table['attrs']     = array(
			'className'       => 'is-style-stripes',
			'backgroundColor' => 'light-green-cyan',
		);

		$rendered = $this->table_renderer->render( $striped_table_content, $parsed_table, $this->rendering_context );

		// Check that striped rows have background color.
		$this->assertStringContainsString( 'background-color: #f8f9fa', $rendered );
		// Check that caption is preserved outside the table.
		$this->assertStringContainsString( 'Table caption.', $rendered );
		// Check that the table has a border.
		$this->assertStringContainsString( 'border: 1px solid', $rendered );
		// Check that header has thicker bottom border (when no custom border is set).
		$this->assertStringContainsString( 'border-bottom: 3px solid', $rendered );
		// Check that footer has thicker top border (when no custom border is set).
		$this->assertStringContainsString( 'border-top: 3px solid', $rendered );
	}

	/**
	 * Test it renders text alignment
	 */
	public function testItRendersTextAlignment(): void {
		$parsed_table                       = $this->parsed_table;
		$parsed_table['innerHTML']          = $this->simple_table_content;
		$parsed_table['attrs']['textAlign'] = 'center';

		$rendered = $this->table_renderer->render( $this->simple_table_content, $parsed_table, $this->rendering_context );
		$this->assertStringContainsString( 'text-align:center;', $rendered );
		$this->assertStringContainsString( 'align="center"', $rendered );
	}

	/**
	 * Test it renders custom colors
	 */
	public function testItRendersCustomColors(): void {
		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $this->simple_table_content;
		$parsed_table['attrs']['style']['color']['background'] = '#ff0000';
		$parsed_table['attrs']['style']['color']['text']       = '#00ff00';

		$rendered = $this->table_renderer->render( $this->simple_table_content, $parsed_table, $this->rendering_context );
		$this->assertStringContainsString( 'background-color:#ff0000', $rendered );
		$this->assertStringContainsString( 'color:#00ff00;', $rendered );
	}

	/**
	 * Test it uses inherited color from email_attrs when no color is specified
	 */
	public function testItUsesInheritedColorFromEmailAttrs(): void {
		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $this->simple_table_content;

		unset( $parsed_table['attrs']['style']['color'] );

		$parsed_table['email_attrs'] = array(
			'color' => '#ff0000',
		);

		$rendered = $this->table_renderer->render( $this->simple_table_content, $parsed_table, $this->rendering_context );
		$this->assertStringContainsString( 'color:#ff0000;', $rendered );
	}



	/**
	 * Test it renders table with custom styles
	 */
	public function testItRendersTableWithCustomStyles(): void {
		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $this->simple_table_content;
		$parsed_table['attrs']['style']['spacing']['padding']['top']    = '20px';
		$parsed_table['attrs']['style']['spacing']['padding']['bottom'] = '20px';
		$parsed_table['attrs']['style']['typography']['fontSize']       = '18px';

		$rendered = $this->table_renderer->render( $this->simple_table_content, $parsed_table, $this->rendering_context );
		$this->assertStringContainsString( 'padding-top:20px;', $rendered );
		$this->assertStringContainsString( 'padding-bottom:20px;', $rendered );
		$this->assertStringContainsString( 'font-size:18px;', $rendered );
	}

	/**
	 * Test it renders table with border styles
	 */
	public function testItRendersTableWithBorderStyles(): void {
		$parsed_table                                      = $this->parsed_table;
		$parsed_table['innerHTML']                         = $this->simple_table_content;
		$parsed_table['attrs']['style']['border']['width'] = '2px';
		$parsed_table['attrs']['style']['border']['color'] = '#333333';

		$rendered = $this->table_renderer->render( $this->simple_table_content, $parsed_table, $this->rendering_context );
		// Check that the table has the custom border (color may be processed by Styles_Helper).
		$this->assertStringContainsString( 'border: 2px solid', $rendered );
		// Check that individual cells have padding and consistent borders.
		$this->assertStringContainsString( 'padding: 8px', $rendered );
		// Check that header/footer borders are NOT applied when custom border is set.
		$this->assertStringNotContainsString( 'border-bottom: 3px solid', $rendered );
		$this->assertStringNotContainsString( 'border-top: 3px solid', $rendered );
	}

	/**
	 * Test it removes background classes from table
	 */
	public function testItRemovesBackgroundClassesFromTable(): void {
		$table_content_with_background = '<figure class="wp-block-table"><table class="has-background has-blue-background-color">' .
			'<thead><tr><th>Header</th></tr></thead>' .
			'<tbody><tr><td>Cell</td></tr></tbody>' .
			'</table></figure>';

		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $table_content_with_background;

		$rendered = $this->table_renderer->render( $table_content_with_background, $parsed_table, $this->rendering_context );
		$this->assertStringNotContainsString( 'has-background', $rendered );
		$this->assertStringContainsString( 'has-blue-background-color', $rendered );
	}

	/**
	 * Test it removes border classes from table
	 */
	public function testItRemovesBorderClassesFromTable(): void {
		$table_content_with_border = '<figure class="wp-block-table"><table class="has-border has-top-border">' .
			'<thead><tr><th>Header</th></tr></thead>' .
			'<tbody><tr><td>Cell</td></tr></tbody>' .
			'</table></figure>';

		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $table_content_with_border;

		$rendered = $this->table_renderer->render( $table_content_with_border, $parsed_table, $this->rendering_context );
		$this->assertStringNotContainsString( 'has-border', $rendered );
		$this->assertStringNotContainsString( 'has-top-border', $rendered );
	}

	/**
	 * Test it renders table wrapper with proper structure
	 */
	public function testItRendersTableWrapperWithProperStructure(): void {
		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $this->simple_table_content;

		$rendered = $this->table_renderer->render( $this->simple_table_content, $parsed_table, $this->rendering_context );
		$this->assertStringContainsString( 'email-table-block', $rendered );
		$this->assertStringContainsString( 'border-collapse: separate', $rendered );
		$this->assertMatchesRegularExpression( '/min-width:\s*100%/i', $rendered );
	}

	/**
	 * Test it renders table with border color attribute
	 */
	public function testItRendersTableWithBorderColorAttribute(): void {
		$parsed_table                         = $this->parsed_table;
		$parsed_table['innerHTML']            = $this->simple_table_content;
		$parsed_table['attrs']['borderColor'] = 'vivid-purple';

		$rendered = $this->table_renderer->render( $this->simple_table_content, $parsed_table, $this->rendering_context );
		// The border color should be translated from the slug to an actual color value.
		// Since we don't have the actual color translation in tests, we check for the border structure.
		$this->assertStringContainsString( 'border: 1px solid', $rendered );
		// Check that individual cells have padding but no borders.
		$this->assertStringContainsString( 'padding: 8px', $rendered );
	}

	/**
	 * Test it renders table with custom border width and color
	 */
	public function testItRendersTableWithCustomBorderWidthAndColor(): void {
		$parsed_table                                      = $this->parsed_table;
		$parsed_table['innerHTML']                         = $this->simple_table_content;
		$parsed_table['attrs']['borderColor']              = 'vivid-purple';
		$parsed_table['attrs']['style']['border']['width'] = '22px';

		$rendered = $this->table_renderer->render( $this->simple_table_content, $parsed_table, $this->rendering_context );
		// Check that the custom border width is applied to the table.
		$this->assertStringContainsString( 'border: 22px solid', $rendered );
		// Check that individual cells have padding and consistent borders.
		$this->assertStringContainsString( 'padding: 8px', $rendered );
		// Check that header/footer borders are NOT applied when custom border is set.
		$this->assertStringNotContainsString( 'border-bottom: 3px solid', $rendered );
		$this->assertStringNotContainsString( 'border-top: 3px solid', $rendered );
	}

	/**
	 * Test it keeps caption outside table borders
	 */
	public function testItKeepsCaptionOutsideTableBorders(): void {
		$table_with_caption = '
		<figure class="wp-block-table">
			<table>
				<thead>
					<tr>
						<th>Header 1</th>
						<th>Header 2</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Cell 1</td>
						<td>Cell 2</td>
					</tr>
				</tbody>
			</table>
			<figcaption>Table caption text</figcaption>
		</figure>
		';

		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $table_with_caption;

		$rendered = $this->table_renderer->render( $table_with_caption, $parsed_table, $this->rendering_context );

		// Check that the caption appears outside the table as a div.
		$this->assertStringContainsString( '<div style="text-align: center; margin-top: 8px;', $rendered );
		$this->assertStringContainsString( '>Table caption text</div>', $rendered );
		// Check that the caption is not inside the table element.
		$this->assertStringNotContainsString( '<caption', $rendered );
	}

	/**
	 * Test it applies per-cell text alignment
	 */
	public function testItAppliesPerCellTextAlignment(): void {
		$table_with_alignment = '
		<figure class="wp-block-table">
			<table>
				<thead>
					<tr>
						<th class="has-text-align-center" data-align="center">Centered Header</th>
						<th class="has-text-align-left" data-align="left">Left Header</th>
						<th class="has-text-align-right" data-align="right">Right Header</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="has-text-align-center" data-align="center">Centered Cell</td>
						<td class="has-text-align-left" data-align="left">Left Cell</td>
						<td class="has-text-align-right" data-align="right">Right Cell</td>
					</tr>
				</tbody>
			</table>
		</figure>
		';

		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $table_with_alignment;

		$rendered = $this->table_renderer->render( $table_with_alignment, $parsed_table, $this->rendering_context );

		// Check that center alignment is applied.
		$this->assertStringContainsString( 'text-align: center;', $rendered );
		// Check that left alignment is applied.
		$this->assertStringContainsString( 'text-align: left;', $rendered );
		// Check that right alignment is applied.
		$this->assertStringContainsString( 'text-align: right;', $rendered );
	}

	/**
	 * Test it falls back to data-align when class is not present
	 */
	public function testItFallsBackToDataAlign(): void {
		$table_with_data_align = '
		<figure class="wp-block-table">
			<table>
				<thead>
					<tr>
						<th data-align="center">Centered Header</th>
						<th data-align="right">Right Header</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td data-align="center">Centered Cell</td>
						<td data-align="right">Right Cell</td>
					</tr>
				</tbody>
			</table>
		</figure>
		';

		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $table_with_data_align;

		$rendered = $this->table_renderer->render( $table_with_data_align, $parsed_table, $this->rendering_context );

		// Check that alignments are applied from data-align attributes.
		$this->assertStringContainsString( 'text-align: center;', $rendered );
		$this->assertStringContainsString( 'text-align: right;', $rendered );
	}

	/**
	 * Test it applies fixed table layout when has-fixed-layout class is present
	 */
	public function testItAppliesFixedTableLayout(): void {
		$table_with_fixed_layout = '
		<figure class="wp-block-table">
			<table class="has-fixed-layout">
				<thead>
					<tr>
						<th>Header 1</th>
						<th>Header 2</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Cell 1</td>
						<td>Cell 2</td>
					</tr>
				</tbody>
			</table>
		</figure>
		';

		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $table_with_fixed_layout;

		$rendered = $this->table_renderer->render( $table_with_fixed_layout, $parsed_table, $this->rendering_context );

		// Check that table-layout: fixed is applied.
		$this->assertStringContainsString( 'table-layout: fixed;', $rendered );
	}

	/**
	 * Test it does not apply fixed layout when class is not present
	 */
	public function testItDoesNotApplyFixedLayoutWithoutClass(): void {
		$table_without_fixed_layout = '
		<figure class="wp-block-table">
			<table>
				<thead>
					<tr>
						<th>Header 1</th>
						<th>Header 2</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Cell 1</td>
						<td>Cell 2</td>
					</tr>
				</tbody>
			</table>
		</figure>
		';

		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $table_without_fixed_layout;

		$rendered = $this->table_renderer->render( $table_without_fixed_layout, $parsed_table, $this->rendering_context );

		// Check that table-layout: fixed is NOT applied.
		$this->assertStringNotContainsString( 'table-layout: fixed;', $rendered );
	}

	/**
	 * Test it preserves alignment classes for editor UI compatibility
	 */
	public function testItPreservesAlignmentClassesForEditorUI(): void {
		$table_with_alignment_classes = '
		<figure class="wp-block-table">
			<table>
				<thead>
					<tr>
						<th class="has-text-align-center">Centered Header</th>
						<th class="has-text-align-right">Right Header</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="has-text-align-center">Centered Cell</td>
						<td class="has-text-align-right">Right Cell</td>
					</tr>
				</tbody>
			</table>
		</figure>
		';

		$parsed_table              = $this->parsed_table;
		$parsed_table['innerHTML'] = $table_with_alignment_classes;

		$rendered = $this->table_renderer->render( $table_with_alignment_classes, $parsed_table, $this->rendering_context );

		// Check that alignment classes are preserved for editor UI compatibility.
		$this->assertStringContainsString( 'has-text-align-center', $rendered );
		$this->assertStringContainsString( 'has-text-align-right', $rendered );

		// Check that alignment styles are also applied correctly.
		$this->assertStringContainsString( 'text-align: center;', $rendered );
		$this->assertStringContainsString( 'text-align: right;', $rendered );
	}

	/**
	 * Test it handles figure without class attribute gracefully
	 */
	public function testItHandlesFigureWithoutClassAttribute(): void {
		$input               = '<figure><table><tbody><tr><td>Cell</td></tr></tbody></table></figure>';
		$parsed              = $this->parsed_table;
		$parsed['innerHTML'] = $input;
		$rendered            = $this->table_renderer->render( $input, $parsed, $this->rendering_context );
		$this->assertStringContainsString( '<table', $rendered );
	}
}
