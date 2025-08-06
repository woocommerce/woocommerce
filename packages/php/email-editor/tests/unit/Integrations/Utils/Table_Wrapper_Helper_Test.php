<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;

/**
 * Unit test for Table_Wrapper_Helper class.
 */
class Table_Wrapper_Helper_Test extends \Email_Editor_Unit_Test {

	/**
	 * Test it renders a basic table cell.
	 */
	public function testItRendersBasicTableCell(): void {
		$result   = Table_Wrapper_Helper::render_table_cell( 'Test content' );
		$expected = '<td>Test content</td>';
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it renders a table cell with attributes.
	 */
	public function testItRendersTableCellWithAttributes(): void {
		$cell_attrs = array(
			'class' => 'test-class',
			'style' => 'background-color: red;',
			'align' => 'center',
		);
		$result     = Table_Wrapper_Helper::render_table_cell( 'Test content', $cell_attrs );
		$expected   = '<td class="test-class" style="background-color: red;" align="center">Test content</td>';
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it renders a table cell with empty attributes.
	 */
	public function testItRendersTableCellWithEmptyAttributes(): void {
		$cell_attrs = array(
			'class' => '',
			'style' => 'background-color: red;',
			'align' => '',
		);
		$result     = Table_Wrapper_Helper::render_table_cell( 'Test content', $cell_attrs );
		$expected   = '<td style="background-color: red;">Test content</td>';
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it renders an Outlook table cell.
	 */
	public function testItRendersOutlookTableCell(): void {
		$cell_attrs = array(
			'class' => 'test-class',
			'style' => 'background-color: red;',
		);
		$result     = Table_Wrapper_Helper::render_outlook_table_cell( 'Test content', $cell_attrs );
		$expected   = '<!--[if mso | IE]><td class="test-class" style="background-color: red;"><![endif]-->Test content<!--[if mso | IE]></td><![endif]-->';
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it renders a basic table wrapper.
	 */
	public function testItRendersBasicTableWrapper(): void {
		$result   = Table_Wrapper_Helper::render_table_wrapper( 'Test content' );
		$expected = '<table border="0" cellpadding="0" cellspacing="0" role="presentation">
		<tbody>
			<tr>
				<td>Test content</td>
			</tr>
		</tbody>
	</table>';
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it renders a table wrapper with custom attributes.
	 */
	public function testItRendersTableWrapperWithCustomAttributes(): void {
		$table_attrs = array(
			'class' => 'test-table',
			'style' => 'width: 100%;',
			'width' => '100%',
		);
		$cell_attrs  = array(
			'class' => 'test-cell',
			'align' => 'center',
		);
		$row_attrs   = array(
			'style' => 'background-color: blue;',
		);
		$result      = Table_Wrapper_Helper::render_table_wrapper( 'Test content', $table_attrs, $cell_attrs, $row_attrs );
		$expected    = '<table border="0" cellpadding="0" cellspacing="0" role="presentation" class="test-table" style="width: 100%;" width="100%">
		<tbody>
			<tr style="background-color: blue;">
				<td class="test-cell" align="center">Test content</td>
			</tr>
		</tbody>
	</table>';
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it renders a table wrapper without cell wrapper.
	 */
	public function testItRendersTableWrapperWithoutCellWrapper(): void {
		$content     = '<td>Cell 1</td><td>Cell 2</td>';
		$table_attrs = array(
			'class' => 'test-table',
		);
		$result      = Table_Wrapper_Helper::render_table_wrapper( $content, $table_attrs, array(), array(), false );
		$expected    = '<table border="0" cellpadding="0" cellspacing="0" role="presentation" class="test-table">
		<tbody>
			<tr>
				<td>Cell 1</td><td>Cell 2</td>
			</tr>
		</tbody>
	</table>';
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it renders an Outlook table wrapper.
	 */
	public function testItRendersOutlookTableWrapper(): void {
		$table_attrs = array(
			'class' => 'test-table',
			'align' => 'center',
		);
		$cell_attrs  = array(
			'class' => 'test-cell',
		);
		$result      = Table_Wrapper_Helper::render_outlook_table_wrapper( 'Test content', $table_attrs, $cell_attrs );
		$expected    = '<!--[if mso | IE]><table border="0" cellpadding="0" cellspacing="0" role="presentation" class="test-table" align="center">
		<tbody>
			<tr>
				<td class="test-cell"><![endif]-->Test content<!--[if mso | IE]></td>
			</tr>
		</tbody>
	</table><![endif]-->';
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it renders an Outlook table wrapper without cell wrapper.
	 */
	public function testItRendersOutlookTableWrapperWithoutCellWrapper(): void {
		$content     = '<td>Cell 1</td><td>Cell 2</td>';
		$table_attrs = array(
			'class' => 'test-table',
			'align' => 'center',
		);
		$result      = Table_Wrapper_Helper::render_outlook_table_wrapper( $content, $table_attrs, array(), array(), false );
		$expected    = '<!--[if mso | IE]><table border="0" cellpadding="0" cellspacing="0" role="presentation" class="test-table" align="center">
		<tbody>
			<tr>
				<![endif]--><td>Cell 1</td><td>Cell 2</td><!--[if mso | IE]>
			</tr>
		</tbody>
	</table><![endif]-->';
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it merges default table attributes correctly.
	 */
	public function testItMergesDefaultTableAttributesCorrectly(): void {
		$table_attrs = array(
			'class'  => 'custom-table',
			'border' => '1', // This should override the default.
		);
		$result      = Table_Wrapper_Helper::render_table_wrapper( 'Test content', $table_attrs );
		$expected    = '<table border="1" cellpadding="0" cellspacing="0" role="presentation" class="custom-table">
		<tbody>
			<tr>
				<td>Test content</td>
			</tr>
		</tbody>
	</table>';
		$this->assertSame( $expected, $result );
	}
}
