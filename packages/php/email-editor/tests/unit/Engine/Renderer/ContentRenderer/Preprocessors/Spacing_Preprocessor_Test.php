<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Spacing_Preprocessor;

/**
 * Unit test for Spacing_Preprocessor
 */
class Spacing_Preprocessor_Test extends \Email_Editor_Unit_Test {

	/**
	 * Spacing_Preprocessor instance
	 *
	 * @var Spacing_Preprocessor
	 */
	private $preprocessor;

	/**
	 * Layout settings
	 *
	 * @var array{contentSize: string}
	 */
	private array $layout;

	/**
	 * Styles settings
	 *
	 * @var array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles
	 */
	private array $styles;

	/**
	 * Set up the test
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->preprocessor = new Spacing_Preprocessor();
		$this->layout       = array( 'contentSize' => '660px' );
		$this->styles       = array(
			'spacing' => array(
				'padding'  => array(
					'left'   => '10px',
					'right'  => '10px',
					'top'    => '10px',
					'bottom' => '10px',
				),
				'blockGap' => '10px',
			),
		);
	}

	/**
	 * Test it adds default horizontal spacing
	 */
	public function testItAddsDefaultVerticalSpacing(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/list',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
							array(
								'blockName'   => 'core/img',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$this->assertCount( 2, $result );
		$first_columns             = $result[0];
		$second_columns            = $result[1];
		$nested_column             = $first_columns['innerBlocks'][0];
		$nested_column_first_item  = $nested_column['innerBlocks'][0];
		$nested_column_second_item = $nested_column['innerBlocks'][1];

		// First elements should not have margin-top, but others should.
		$this->assertArrayNotHasKey( 'margin-top', $first_columns['email_attrs'] );
		$this->assertEquals( '10px', $nested_column_second_item['email_attrs']['margin-top'] );
		$this->assertArrayNotHasKey( 'margin-top', $nested_column['email_attrs'] );
		$this->assertArrayNotHasKey( 'margin-top', $nested_column_first_item['email_attrs'] );
		$this->assertArrayHasKey( 'margin-top', $nested_column_second_item['email_attrs'] );
		$this->assertEquals( '10px', $nested_column_second_item['email_attrs']['margin-top'] );

		// Root-level blocks should have root padding (new keys).
		$this->assertEquals( '10px', $first_columns['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $first_columns['email_attrs']['root-padding-right'] );
		$this->assertEquals( '10px', $second_columns['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $second_columns['email_attrs']['root-padding-right'] );

		// Legacy padding keys should not be present.
		$this->assertArrayNotHasKey( 'padding-left', $first_columns['email_attrs'] );
		$this->assertArrayNotHasKey( 'padding-right', $first_columns['email_attrs'] );
		$this->assertArrayNotHasKey( 'padding-left', $second_columns['email_attrs'] );
		$this->assertArrayNotHasKey( 'padding-right', $second_columns['email_attrs'] );

		// Nested blocks should not have root padding.
		$this->assertArrayNotHasKey( 'root-padding-left', $nested_column_first_item['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $nested_column_first_item['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-left', $nested_column_second_item['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $nested_column_second_item['email_attrs'] );
	}

	/**
	 * Test it adds padding-left to column blocks when parent columns has blockGap.left
	 */
	public function testItAddsPaddingLeftToColumnsWithBlockGap(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'style' => array(
						'spacing' => array(
							'blockGap' => array(
								'top'  => '20px',
								'left' => '30px',
							),
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result        = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$columns_block = $result[0];
		$first_column  = $columns_block['innerBlocks'][0];
		$second_column = $columns_block['innerBlocks'][1];
		$third_column  = $columns_block['innerBlocks'][2];

		// First column should not have padding-left.
		$this->assertArrayNotHasKey( 'padding-left', $first_column['email_attrs'] );

		// Second and third columns should have padding-left.
		$this->assertArrayHasKey( 'padding-left', $second_column['email_attrs'] );
		$this->assertEquals( '30px', $second_column['email_attrs']['padding-left'] );
		$this->assertArrayHasKey( 'padding-left', $third_column['email_attrs'] );
		$this->assertEquals( '30px', $third_column['email_attrs']['padding-left'] );
	}

	/**
	 * Test it passes preset variables through for columns blockGap (WP styles engine will handle transformation)
	 */
	public function testItPassesPresetVariablesThroughForColumnsBlockGap(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'style' => array(
						'spacing' => array(
							'blockGap' => array(
								'left' => 'var:preset|spacing|40',
							),
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result        = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$second_column = $result[0]['innerBlocks'][1];

		// Should pass through "var:preset|spacing|40" as-is. WP's styles engine will handle transformation.
		$this->assertEquals( 'var:preset|spacing|40', $second_column['email_attrs']['padding-left'] );
	}

	/**
	 * Test it does not add a horizontal column gap when blockGap.left is not explicitly set.
	 *
	 * The global (vertical) block spacing must not leak in as a horizontal column
	 * gap, since that spacing is not shown between columns in the editor and would
	 * widen the rendered email.
	 */
	public function testItDoesNotAddPaddingLeftWithoutBlockGapLeft(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'style' => array(
						'spacing' => array(
							'blockGap' => array(
								'top' => '20px',
								// No 'left' key.
							),
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result        = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$second_column = $result[0]['innerBlocks'][1];

		// Without an explicit blockGap.left, no horizontal gap should be applied.
		$this->assertArrayNotHasKey( 'padding-left', $second_column['email_attrs'] );
	}

	/**
	 * Test it adds padding-right to column blocks in RTL renders.
	 */
	public function testItAddsPaddingRightToColumnsInRtl(): void {
		$theme_json = $this->createMock( \WP_Theme_JSON::class );
		$context    = new \Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context(
			$theme_json,
			array( 'is_rtl' => true )
		);
		$blocks     = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'style' => array(
						'spacing' => array(
							'blockGap' => array(
								'left' => '30px',
							),
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result        = $this->preprocessor->preprocess_with_context( $blocks, $this->layout, $this->styles, $context );
		$second_column = $result[0]['innerBlocks'][1];

		$this->assertArrayNotHasKey( 'padding-left', $second_column['email_attrs'] );
		$this->assertSame( '30px', $second_column['email_attrs']['padding-right'] );
	}

	/**
	 * Test it skips root padding for core/post-content but applies it to its children
	 */
	public function testItDistributesRootPaddingThroughPostContent(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/post-content',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/paragraph',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/image',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result       = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$post_content = $result[0];
		$first_child  = $post_content['innerBlocks'][0];
		$second_child = $post_content['innerBlocks'][1];

		// core/post-content itself should NOT get root padding (it's a pass-through).
		$this->assertArrayNotHasKey( 'root-padding-left', $post_content['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $post_content['email_attrs'] );

		// Direct children of post-content should get root padding.
		$this->assertEquals( '10px', $first_child['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $first_child['email_attrs']['root-padding-right'] );
		$this->assertEquals( '10px', $second_child['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $second_child['email_attrs']['root-padding-right'] );
	}

	/**
	 * Test it distributes root padding through post-content nested inside a root-level group
	 */
	public function testItDistributesRootPaddingThroughNestedPostContent(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/post-content',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
							array(
								'blockName'   => 'core/image',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
		);

		$result       = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$group        = $result[0];
		$post_content = $group['innerBlocks'][0];
		$first_child  = $post_content['innerBlocks'][0];
		$second_child = $post_content['innerBlocks'][1];

		// Root-level group is a container — it does NOT get root padding itself.
		$this->assertArrayNotHasKey( 'root-padding-left', $group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $group['email_attrs'] );

		// Nested post-content should NOT get root padding (post-content never gets padding).
		$this->assertArrayNotHasKey( 'root-padding-left', $post_content['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $post_content['email_attrs'] );

		// Children of post-content inside root group SHOULD get padding (delegation chain).
		$this->assertEquals( '10px', $first_child['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $first_child['email_attrs']['root-padding-right'] );
		$this->assertEquals( '10px', $second_child['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $second_child['email_attrs']['root-padding-right'] );
	}

	/**
	 * Test content-group wrapping post-content is transparent in template-like structure
	 */
	public function testItMakesContentGroupTransparentWhenWrappingPostContent(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/site-title',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/group',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/post-content',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
					array(
						'blockName'   => 'core/group',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
		);

		$result        = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$root_group    = $result[0];
		$site_title    = $root_group['innerBlocks'][0];
		$content_group = $root_group['innerBlocks'][1];
		$post_content  = $content_group['innerBlocks'][0];
		$footer_group  = $root_group['innerBlocks'][2];

		// Root group: no padding (root container delegates).
		$this->assertArrayNotHasKey( 'root-padding-left', $root_group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $root_group['email_attrs'] );

		// Site title: gets root padding (non-container, receives delegation).
		$this->assertEquals( '10px', $site_title['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $site_title['email_attrs']['root-padding-right'] );

		// Content group: transparent (wraps post-content, delegates further).
		$this->assertArrayNotHasKey( 'root-padding-left', $content_group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $content_group['email_attrs'] );

		// Post-content: no padding (post-content never gets padding).
		$this->assertArrayNotHasKey( 'root-padding-left', $post_content['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $post_content['email_attrs'] );

		// Footer group: gets root padding (doesn't wrap post-content).
		$this->assertEquals( '10px', $footer_group['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $footer_group['email_attrs']['root-padding-right'] );
	}

	/**
	 * Test deeply nested post-content (group → group → post-content) delegates correctly
	 */
	public function testItDelegatesThroughDeeplyNestedPostContent(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/group',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/group',
								'attrs'       => array(),
								'innerBlocks' => array(
									array(
										'blockName'   => 'core/post-content',
										'attrs'       => array(),
										'innerBlocks' => array(
											array(
												'blockName' => 'core/paragraph',
												'attrs' => array(),
												'innerBlocks' => array(),
											),
										),
									),
								),
							),
						),
					),
				),
			),
		);

		$result       = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$root_group   = $result[0];
		$middle_group = $root_group['innerBlocks'][0];
		$inner_group  = $middle_group['innerBlocks'][0];
		$post_content = $inner_group['innerBlocks'][0];
		$user_block   = $post_content['innerBlocks'][0];

		// All container groups in the chain should be transparent (no padding).
		$this->assertArrayNotHasKey( 'root-padding-left', $root_group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $root_group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-left', $middle_group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $middle_group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-left', $inner_group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $inner_group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-left', $post_content['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $post_content['email_attrs'] );

		// User block inside post-content should get root padding.
		$this->assertEquals( '10px', $user_block['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $user_block['email_attrs']['root-padding-right'] );
	}

	/**
	 * Test it skips root padding for alignfull children of root-level containers
	 */
	public function testItSkipsRootPaddingForAlignfullBlocks(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/group',
						'attrs'       => array( 'align' => 'full' ),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/paragraph',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result          = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$root_group      = $result[0];
		$alignfull_child = $root_group['innerBlocks'][0];
		$normal_child    = $root_group['innerBlocks'][1];

		// Root-level group is a container — it does NOT get root padding itself.
		$this->assertArrayNotHasKey( 'root-padding-left', $root_group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $root_group['email_attrs'] );

		// Alignfull child should NOT get root padding (skipped for full-width).
		$this->assertArrayNotHasKey( 'root-padding-left', $alignfull_child['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $alignfull_child['email_attrs'] );

		// Normal child should get root padding.
		$this->assertEquals( '10px', $normal_child['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $normal_child['email_attrs']['root-padding-right'] );
	}

	/**
	 * Test root-level group delegates padding to direct children but not deeper
	 */
	public function testItDelegatesPaddingToDirectChildrenOnly(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/paragraph',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/group',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/image',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
		);

		$result           = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$root_group       = $result[0];
		$nested_paragraph = $root_group['innerBlocks'][0];
		$nested_group     = $root_group['innerBlocks'][1];
		$deeply_nested    = $nested_group['innerBlocks'][0];

		// Root-level group is a container — it does NOT get root padding itself.
		$this->assertArrayNotHasKey( 'root-padding-left', $root_group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $root_group['email_attrs'] );

		// Direct children of root group SHOULD get root padding.
		$this->assertEquals( '10px', $nested_paragraph['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $nested_paragraph['email_attrs']['root-padding-right'] );
		$this->assertEquals( '10px', $nested_group['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $nested_group['email_attrs']['root-padding-right'] );

		// Deeply nested blocks should NOT get root padding.
		$this->assertArrayNotHasKey( 'root-padding-left', $deeply_nested['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $deeply_nested['email_attrs'] );
	}

	/**
	 * Test blocks with explicit zero padding skip root padding, but non-zero padding does not
	 */
	public function testItSkipsRootPaddingForBlocksWithExplicitPadding(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(
					// Group with explicit 0px padding (edge-to-edge banner).
					array(
						'blockName'   => 'core/group',
						'attrs'       => array(
							'style' => array(
								'spacing' => array(
									'padding' => array(
										'left'  => '0px',
										'right' => '0px',
									),
								),
							),
						),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
					// Columns with explicit 0px padding.
					array(
						'blockName'   => 'core/columns',
						'attrs'       => array(
							'style' => array(
								'spacing' => array(
									'padding' => array(
										'left'  => '0px',
										'right' => '0px',
									),
								),
							),
						),
						'innerBlocks' => array(),
					),
					// Group with explicit 40px padding.
					array(
						'blockName'   => 'core/group',
						'attrs'       => array(
							'style' => array(
								'spacing' => array(
									'padding' => array(
										'left'  => '40px',
										'right' => '40px',
									),
								),
							),
						),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
					// Paragraph with no explicit padding (should get root padding).
					array(
						'blockName'   => 'core/paragraph',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result          = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$root_group      = $result[0];
		$banner_group    = $root_group['innerBlocks'][0];
		$banner_child    = $banner_group['innerBlocks'][0];
		$columns         = $root_group['innerBlocks'][1];
		$padded_group    = $root_group['innerBlocks'][2];
		$padded_child    = $padded_group['innerBlocks'][0];
		$plain_paragraph = $root_group['innerBlocks'][3];

		// Banner group (0px padding): skips root padding, children don't get delegation.
		$this->assertArrayNotHasKey( 'root-padding-left', $banner_group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $banner_group['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-left', $banner_child['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $banner_child['email_attrs'] );

		// Columns (0px padding): skips root padding.
		$this->assertArrayNotHasKey( 'root-padding-left', $columns['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $columns['email_attrs'] );

		// Padded group (40px): non-zero padding does NOT skip root padding.
		// The group gets root padding (its own 40px is internal content spacing).
		$this->assertEquals( '10px', $padded_group['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $padded_group['email_attrs']['root-padding-right'] );
		// Children of a non-zero padded group still get delegation.
		$this->assertArrayNotHasKey( 'root-padding-left', $padded_child['email_attrs'] );
		$this->assertArrayNotHasKey( 'root-padding-right', $padded_child['email_attrs'] );

		// Plain paragraph (no explicit padding): gets root padding.
		$this->assertEquals( '10px', $plain_paragraph['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $plain_paragraph['email_attrs']['root-padding-right'] );
	}

	/**
	 * Test a root-level group with its own padding wrapping post-content is a box:
	 * it takes the root inset itself and distributes its own padding to descendants
	 * as container padding (so the two paddings nest instead of stacking).
	 */
	public function testItDistributesContainerPaddingFromRootGroupWrappingPostContent(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(
					'style' => array(
						'spacing' => array(
							'padding' => array(
								'left'   => '20px',
								'right'  => '20px',
								'top'    => '15px',
								'bottom' => '15px',
							),
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/post-content',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
							array(
								'blockName'   => 'core/group',
								'attrs'       => array( 'align' => 'full' ),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
		);

		$result       = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$root_group   = $result[0];
		$post_content = $root_group['innerBlocks'][0];
		$paragraph    = $post_content['innerBlocks'][0];
		$alignfull    = $post_content['innerBlocks'][1];

		// The box suppresses its own padding and takes the root inset itself.
		$this->assertTrue( $root_group['email_attrs']['suppress-horizontal-padding'] );
		$this->assertEquals( '10px', $root_group['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $root_group['email_attrs']['root-padding-right'] );

		// Post-content is a pass-through and gets no container padding of its own.
		$this->assertArrayNotHasKey( 'container-padding-left', $post_content['email_attrs'] );
		$this->assertArrayNotHasKey( 'container-padding-right', $post_content['email_attrs'] );

		// Normal paragraph gets only the container padding (the box carries the root
		// inset), so the two paddings nest instead of stacking to 30px on the block.
		$this->assertArrayNotHasKey( 'root-padding-left', $paragraph['email_attrs'] );
		$this->assertEquals( '20px', $paragraph['email_attrs']['container-padding-left'] );
		$this->assertEquals( '20px', $paragraph['email_attrs']['container-padding-right'] );

		// Alignfull block skips container padding and spans the box width.
		$this->assertArrayNotHasKey( 'root-padding-left', $alignfull['email_attrs'] );
		$this->assertArrayNotHasKey( 'container-padding-left', $alignfull['email_attrs'] );
		$this->assertArrayNotHasKey( 'container-padding-right', $alignfull['email_attrs'] );
	}

	/**
	 * Test the box behavior is driven by the group's own padding, not its
	 * background: a backgrounded group with padding wrapping post-content still
	 * takes the root inset and distributes its own padding as container padding.
	 */
	public function testItTreatsBackgroundedGroupWithPaddingAsBox(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(
					'backgroundColor' => 'white',
					'style'           => array(
						'spacing' => array(
							'padding' => array(
								'left'   => '20px',
								'right'  => '20px',
								'top'    => '15px',
								'bottom' => '15px',
							),
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/post-content',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
		);

		$result    = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$box_group = $result[0];
		$paragraph = $box_group['innerBlocks'][0]['innerBlocks'][0];

		// The box suppresses its own padding and takes the root inset itself.
		$this->assertTrue( $box_group['email_attrs']['suppress-horizontal-padding'] );
		$this->assertEquals( '10px', $box_group['email_attrs']['root-padding-left'] );

		// The paragraph gets only the distributed container padding, not root padding.
		$this->assertArrayNotHasKey( 'root-padding-left', $paragraph['email_attrs'] );
		$this->assertEquals( '20px', $paragraph['email_attrs']['container-padding-left'] );
	}

	/**
	 * Test a box (group with padding) nested inside a transparent root group that
	 * delegates: the box takes the root inset and distributes its own padding to
	 * descendants as container padding.
	 */
	public function testItInsetsBoxNestedInTransparentRootGroup(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/group',
						'attrs'       => array(
							'backgroundColor' => 'white',
							'style'           => array(
								'spacing' => array(
									'padding' => array(
										'left'  => '20px',
										'right' => '20px',
									),
								),
							),
						),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/post-content',
								'attrs'       => array(),
								'innerBlocks' => array(
									array(
										'blockName'   => 'core/paragraph',
										'attrs'       => array(),
										'innerBlocks' => array(),
									),
								),
							),
						),
					),
				),
			),
		);

		$result    = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$box_group = $result[0]['innerBlocks'][0];
		$paragraph = $box_group['innerBlocks'][0]['innerBlocks'][0];

		// The box suppresses its own padding and is inset by root padding.
		$this->assertTrue( $box_group['email_attrs']['suppress-horizontal-padding'] );
		$this->assertEquals( '10px', $box_group['email_attrs']['root-padding-left'] );

		// Descendants get the distributed container padding but not root padding.
		$this->assertArrayNotHasKey( 'root-padding-left', $paragraph['email_attrs'] );
		$this->assertEquals( '20px', $paragraph['email_attrs']['container-padding-left'] );
	}

	/**
	 * Test container padding is distributed from nested group wrapping post-content
	 */
	public function testItDistributesContainerPaddingFromNestedGroupWrappingPostContent(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/group',
						'attrs'       => array(
							'style' => array(
								'spacing' => array(
									'padding' => array(
										'left'  => '25px',
										'right' => '25px',
									),
								),
							),
						),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/post-content',
								'attrs'       => array(),
								'innerBlocks' => array(
									array(
										'blockName'   => 'core/paragraph',
										'attrs'       => array(),
										'innerBlocks' => array(),
									),
								),
							),
						),
					),
				),
			),
		);

		$result        = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$root_group    = $result[0];
		$content_group = $root_group['innerBlocks'][0];
		$paragraph     = $content_group['innerBlocks'][0]['innerBlocks'][0];

		// Content group wrapping post-content should have suppress flag.
		$this->assertTrue( $content_group['email_attrs']['suppress-horizontal-padding'] );

		// Paragraph inside post-content should get container padding.
		$this->assertEquals( '25px', $paragraph['email_attrs']['container-padding-left'] );
		$this->assertEquals( '25px', $paragraph['email_attrs']['container-padding-right'] );
	}

	/**
	 * Test container padding is passed from styles (second pass) to user blocks
	 */
	public function testItAppliesContainerPaddingFromStyles(): void {
		$styles                        = $this->styles;
		$styles['__container_padding'] = array(
			'left'  => '20px',
			'right' => '20px',
		);

		// Simulate second pass: user blocks at top level (as post-content renders them).
		$blocks = array(
			array(
				'blockName'   => 'core/paragraph',
				'attrs'       => array(),
				'innerBlocks' => array(),
			),
			array(
				'blockName'   => 'core/group',
				'attrs'       => array( 'align' => 'full' ),
				'innerBlocks' => array(),
			),
		);

		$result    = $this->preprocessor->preprocess( $blocks, $this->layout, $styles );
		$paragraph = $result[0];
		$alignfull = $result[1];

		// Normal block gets container padding.
		$this->assertEquals( '20px', $paragraph['email_attrs']['container-padding-left'] );
		$this->assertEquals( '20px', $paragraph['email_attrs']['container-padding-right'] );

		// Alignfull block skips container padding.
		$this->assertArrayNotHasKey( 'container-padding-left', $alignfull['email_attrs'] );
		$this->assertArrayNotHasKey( 'container-padding-right', $alignfull['email_attrs'] );
	}

	/**
	 * Test container padding is applied once at the outermost block and not
	 * propagated to nested descendants: a columns block gets it, but the image
	 * inside a padded column does not receive it again.
	 */
	public function testItDoesNotPropagateContainerPaddingToNestedBlocks(): void {
		$styles                        = $this->styles;
		$styles['__container_padding'] = array(
			'left'  => '24px',
			'right' => '24px',
		);

		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'style' => array(
								'spacing' => array(
									'padding' => array(
										'left'  => '20px',
										'right' => '20px',
									),
								),
							),
						),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/image',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
		);

		$result  = $this->preprocessor->preprocess( $blocks, $this->layout, $styles );
		$columns = $result[0];
		$column  = $columns['innerBlocks'][0];
		$image   = $column['innerBlocks'][0];

		// The outermost columns block receives the container padding.
		$this->assertEquals( '24px', $columns['email_attrs']['container-padding-left'] );
		$this->assertEquals( '24px', $columns['email_attrs']['container-padding-right'] );

		// Nested blocks must not receive it again.
		$this->assertArrayNotHasKey( 'container-padding-left', $column['email_attrs'] );
		$this->assertArrayNotHasKey( 'container-padding-left', $image['email_attrs'] );
		$this->assertArrayNotHasKey( 'container-padding-right', $image['email_attrs'] );
	}

	/**
	 * Test template group without own padding does NOT set container padding
	 */
	public function testItDoesNotSetContainerPaddingWhenGroupHasNoPadding(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/post-content',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
		);

		$result    = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$group     = $result[0];
		$paragraph = $group['innerBlocks'][0]['innerBlocks'][0];

		// Group should NOT have suppress flag.
		$this->assertArrayNotHasKey( 'suppress-horizontal-padding', $group['email_attrs'] );

		// Paragraph should NOT have container padding.
		$this->assertArrayNotHasKey( 'container-padding-left', $paragraph['email_attrs'] );
		$this->assertArrayNotHasKey( 'container-padding-right', $paragraph['email_attrs'] );

		// Paragraph should still get root padding.
		$this->assertEquals( '10px', $paragraph['email_attrs']['root-padding-left'] );
		$this->assertEquals( '10px', $paragraph['email_attrs']['root-padding-right'] );
	}

	/**
	 * Test it rejects malicious values in blockGap
	 */
	public function testItRejectsMaliciousBlockGapValues(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'style' => array(
						'spacing' => array(
							'blockGap' => array(
								'left' => '30px"><script>alert("xss")</script>',
							),
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result        = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$second_column = $result[0]['innerBlocks'][1];

		// Should not have padding-left due to malicious value.
		$this->assertArrayNotHasKey( 'padding-left', $second_column['email_attrs'] );
	}

	/**
	 * Test preset variable references in container padding are resolved to pixel values
	 */
	public function testItResolvesPresetReferencesInContainerPadding(): void {
		$styles                    = $this->styles;
		$styles['__variables_map'] = array(
			'--wp--preset--spacing--20' => '24px',
		);

		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(
					'style' => array(
						'spacing' => array(
							'padding' => array(
								'left'  => 'var:preset|spacing|20',
								'right' => 'var:preset|spacing|20',
							),
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/post-content',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
		);

		$result    = $this->preprocessor->preprocess( $blocks, $this->layout, $styles );
		$paragraph = $result[0]['innerBlocks'][0]['innerBlocks'][0];

		// Container padding should be resolved pixel values, not raw preset references.
		$this->assertEquals( '24px', $paragraph['email_attrs']['container-padding-left'] );
		$this->assertEquals( '24px', $paragraph['email_attrs']['container-padding-right'] );
	}

	/**
	 * Builds a grid group block with the given layout and paragraph children.
	 *
	 * @param array $layout Group layout attribute.
	 * @param array $children_attrs Attributes for each paragraph child.
	 * @param array $group_style Group style attribute.
	 * @return array
	 */
	private function build_grid_group( array $layout, array $children_attrs, array $group_style = array() ): array {
		$children = array();
		foreach ( $children_attrs as $attrs ) {
			$children[] = array(
				'blockName'   => 'core/paragraph',
				'attrs'       => $attrs,
				'innerBlocks' => array(),
			);
		}

		return array(
			'blockName'   => 'core/group',
			'attrs'       => array_filter(
				array(
					'layout' => $layout,
					'style'  => $group_style,
				)
			),
			'innerBlocks' => $children,
		);
	}

	/**
	 * Test a two-column grid is flagged for table rendering, takes the root padding itself, and its items get no margin-top.
	 */
	public function testItFlagsTwoColumnGridAndLetsItSpaceItsOwnRows(): void {
		$blocks = array(
			$this->build_grid_group(
				array(
					'type'        => 'grid',
					'columnCount' => 2,
				),
				array( array(), array(), array() )
			),
		);

		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$grid   = $result[0];

		$this->assertTrue( $grid['email_attrs']['two-column-grid'] );
		$this->assertSame( '10px', $grid['email_attrs']['grid-row-gap'], 'Row gap should fall back to the global block gap.' );
		$this->assertSame( '10px', $grid['email_attrs']['grid-column-gap'], 'Column gap should fall back to the global block gap.' );
		$this->assertSame( '10px', $grid['email_attrs']['root-padding-left'], 'The grid should take the root padding as one inset.' );
		$this->assertSame( '10px', $grid['email_attrs']['root-padding-right'] );

		foreach ( $grid['innerBlocks'] as $item ) {
			$this->assertArrayNotHasKey( 'margin-top', $item['email_attrs'], 'Grid items must not get margin-top, or cells in a row would not line up.' );
			$this->assertArrayNotHasKey( 'root-padding-left', $item['email_attrs'], 'Grid items must not each get the root padding.' );
			$this->assertTrue( $item['email_attrs']['two-column-grid-item'] );
		}
	}

	/**
	 * Test a two-column grid uses its own blockGap per axis and resolves preset references to pixels.
	 */
	public function testItUsesTheGridGroupBlockGap(): void {
		$styles                    = $this->styles;
		$styles['__variables_map'] = array( '--wp--preset--spacing--20' => '1.5rem' );

		$blocks = array(
			$this->build_grid_group(
				array(
					'type'        => 'grid',
					'columnCount' => '2',
				),
				array( array(), array() ),
				array(
					'spacing' => array(
						'blockGap' => array(
							'top'  => '30px',
							'left' => 'var:preset|spacing|20',
						),
					),
				)
			),
		);

		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $styles );

		$this->assertSame( '30px', $result[0]['email_attrs']['grid-row-gap'] );
		$this->assertSame( '24px', $result[0]['email_attrs']['grid-column-gap'] );
	}

	/**
	 * Test a spacer fits a grid cell only when it has no fixed width.
	 */
	public function testItStacksTwoColumnGridsHoldingFixedWidthSpacers(): void {
		$layout = array(
			'type'        => 'grid',
			'columnCount' => 2,
		);
		$spacer = array(
			'blockName'   => 'core/spacer',
			'attrs'       => array( 'height' => '20px' ),
			'innerBlocks' => array(),
		);

		$grid                  = $this->build_grid_group( $layout, array( array() ) );
		$grid['innerBlocks'][] = $spacer;
		$fixed_width_grid      = $grid;
		$fixed_width_grid['innerBlocks'][1]['attrs']['width'] = '400px';

		$result = $this->preprocessor->preprocess( array( $grid, $fixed_width_grid ), $this->layout, $this->styles );

		$this->assertTrue( $result[0]['email_attrs']['two-column-grid'] );
		$this->assertArrayNotHasKey( 'two-column-grid', $result[1]['email_attrs'] );
	}

	/**
	 * Test a responsive grid (column count plus minimum column width) is laid out side by side only when both cells meet the minimum width.
	 *
	 * @testWith ["12rem", true]
	 *           ["315px", true]
	 *           ["316px", false]
	 *           ["400px", false]
	 * @param string $minimum_width Minimum column width.
	 * @param bool   $side_by_side Whether the grid should render as two columns.
	 */
	public function testItChecksResponsiveGridMinimumColumnWidth( string $minimum_width, bool $side_by_side ): void {
		$blocks = array(
			$this->build_grid_group(
				array(
					'type'               => 'grid',
					'columnCount'        => 2,
					'minimumColumnWidth' => $minimum_width,
				),
				array( array(), array() )
			),
		);

		// Content is 660px less 10px root padding per side, so each cell gets (640 - 10px gap) / 2 = 315px.
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertSame( $side_by_side, ! empty( $result[0]['email_attrs']['two-column-grid'] ) );
	}

	/**
	 * Test an explicit numeric zero gap is kept rather than replaced by the global gap.
	 */
	public function testItKeepsNumericZeroGridGap(): void {
		$blocks = array(
			$this->build_grid_group(
				array(
					'type'        => 'grid',
					'columnCount' => 2,
				),
				array( array(), array() ),
				array(
					'spacing' => array(
						'blockGap' => array(
							'top'  => 0,
							'left' => 0,
						),
					),
				)
			),
		);

		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertSame( '0px', $result[0]['email_attrs']['grid-row-gap'] );
		$this->assertSame( '0px', $result[0]['email_attrs']['grid-column-gap'] );
	}

	/**
	 * Grid layouts that can't be mapped to two table cells per row.
	 *
	 * @return array<string, array{0: array, 1: array}>
	 */
	public function stackedGridProvider(): array {
		return array(
			'three columns'           => array(
				array(
					'type'        => 'grid',
					'columnCount' => 3,
				),
				array(),
			),
			'auto columns'            => array(
				array(
					'type'               => 'grid',
					'minimumColumnWidth' => '12rem',
				),
				array(),
			),
			'item spans two columns'  => array(
				array(
					'type'        => 'grid',
					'columnCount' => 2,
				),
				array( 'style' => array( 'layout' => array( 'columnSpan' => 2 ) ) ),
			),
			'item spans two rows'     => array(
				array(
					'type'        => 'grid',
					'columnCount' => 2,
				),
				array( 'style' => array( 'layout' => array( 'rowSpan' => 2 ) ) ),
			),
			'item placed manually'    => array(
				array(
					'type'        => 'grid',
					'columnCount' => 2,
				),
				array(
					'style' => array(
						'layout' => array(
							'columnStart' => 2,
							'rowStart'    => 1,
						),
					),
				),
			),
			'flex layout, two counts' => array(
				array(
					'type'        => 'flex',
					'columnCount' => 2,
				),
				array(),
			),
		);
	}

	/**
	 * Test grids that can't be laid out as two cells per row keep the stacked spacing.
	 *
	 * @dataProvider stackedGridProvider
	 * @param array $layout Group layout attribute.
	 * @param array $first_child_attrs Attributes of the first grid item.
	 */
	public function testItLeavesOtherGridsStacked( array $layout, array $first_child_attrs ): void {
		$blocks = array( $this->build_grid_group( $layout, array( $first_child_attrs, array(), array() ) ) );

		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertArrayNotHasKey( 'two-column-grid', $result[0]['email_attrs'] );
		$this->assertSame( '10px', $result[0]['innerBlocks'][1]['email_attrs']['margin-top'] );
		$this->assertSame( '10px', $result[0]['innerBlocks'][2]['email_attrs']['margin-top'] );
	}

	/**
	 * Test a two-column grid stays stacked when it holds a block not known to fit a cell.
	 *
	 * @testWith ["core/gallery"]
	 *           ["core/post-content"]
	 *           ["woocommerce/product-collection"]
	 *           ["core/block"]
	 *           ["core/site-logo"]
	 *           ["core/social-links"]
	 *           ["core/buttons"]
	 *           ["core/quote"]
	 *           ["acme/custom-block"]
	 * @param string $block_name Block nested inside a grid item.
	 */
	public function testItStacksTwoColumnGridsHoldingFullWidthBlocks( string $block_name ): void {
		$grid                                  = $this->build_grid_group(
			array(
				'type'        => 'grid',
				'columnCount' => 2,
			),
			array( array(), array() )
		);
		$grid['innerBlocks'][1]['blockName']   = 'core/group';
		$grid['innerBlocks'][1]['innerBlocks'] = array(
			array(
				'blockName'   => $block_name,
				'attrs'       => array(),
				'innerBlocks' => array(),
			),
		);

		$result = $this->preprocessor->preprocess( array( $grid ), $this->layout, $this->styles );

		$this->assertArrayNotHasKey( 'two-column-grid', $result[0]['email_attrs'] );
		$this->assertSame( '10px', $result[0]['innerBlocks'][1]['email_attrs']['margin-top'] );
	}
}
