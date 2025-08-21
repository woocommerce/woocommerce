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

		// Check that the caption content is preserved with proper styling.
		$this->assertStringContainsString( '<caption style="caption-side: bottom; text-align: center; margin-top: 8px;">Table caption text</caption>', $rendered );
		// Check that the caption appears after the table content (before closing </table>).
		$this->assertStringContainsString( 'Table caption text</caption></table>', $rendered );
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
	 * Test it renders striped tables with thicker borders
	 */
	public function testItRendersStripedTablesWithThickerBorders(): void {
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

		// Check that the table renders with thicker borders for striped style (header separation).
		$this->assertStringContainsString( 'border-bottom: 3px solid', $rendered );
		// Check that footer has thicker top border.
		$this->assertStringContainsString( 'border-top: 3px solid', $rendered );
		// Check that striped rows have background color.
		$this->assertStringContainsString( 'background-color: #f8f9fa', $rendered );
		// Check that caption is preserved.
		$this->assertStringContainsString( 'Table caption.', $rendered );
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
		$this->assertStringContainsString( 'border-width:2px;', $rendered );
		$this->assertStringContainsString( 'border-color:#333333;', $rendered );
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
		$this->assertStringContainsString( 'min-width:100%', $rendered );
	}
}
