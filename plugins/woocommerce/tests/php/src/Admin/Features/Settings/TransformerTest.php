<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin\Features\Settings;

use Automattic\WooCommerce\Admin\Features\Settings\Transformer;
use WC_Unit_Test_Case;

/**
 * Unit tests for Settings Data Transformer.
 *
 * @covers \Automattic\WooCommerce\Admin\Features\Settings\Transformer
 */
class TransformerTest extends WC_Unit_Test_Case {
	/**
	 * Transformer instance.
	 *
	 * @var Transformer
	 */
	private Transformer $transformer;

	/**
	 * Set things up before each test case.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->transformer = new Transformer();
	}

	/**
	 * @dataProvider provide_basic_transformations
	 * @param array $input Input data.
	 */
	public function test_basic_transformations( array $input ): void {
		$this->assertEquals( $input, $this->transformer->transform( $input ) );
	}

	/**
	 * Data provider for basic transformations.
	 *
	 * @return array
	 */
	public function provide_basic_transformations(): array {
		return array(
			'no_sections'    => array(
				array(
					'tab1' => array(
						'label' => 'Tab 1',
						'icon'  => 'icon1',
					),
				),
			),
			'empty_sections' => array(
				array(
					'tab1' => array(
						'label'    => 'Tab 1',
						'sections' => array(),
					),
				),
			),
		);
	}

	/**
	 * Test malformed input structure.
	 *
	 * @dataProvider provide_malformed_inputs
	 * @param array  $input Input data.
	 * @param string $message Message.
	 */
	public function test_malformed_input( array $input, string $message ): void {
		$this->assertEquals(
			$input,
			$this->transformer->transform( $input ),
			$message
		);
	}

	/**
	 * Data provider for malformed inputs.
	 *
	 * @return array
	 */
	public function provide_malformed_inputs(): array {
		return array(
			'non_array_tab'           => array(
				array(
					'tab1' => 'not_an_array',
				),
				'Non-array tab should remain unchanged',
			),
			'missing_required_fields' => array(
				array(
					'tab1' => array(
						'sections' => 'invalid',
					),
				),
				'Tab missing required fields should remain unchanged',
			),
			'invalid_section_format'  => array(
				array(
					'tab1' => array(
						'sections' => array(
							'section1' => 'not_an_array',
						),
					),
				),
				'Invalid section format should remain unchanged',
			),
			'null_sections'           => array(
				array(
					'tab1' => array(
						'sections' => null,
					),
				),
				'Null sections should remain unchanged',
			),
			'null_tab_content'        => array(
				array(
					'tab1' => null,
				),
				'Null tab content should remain unchanged',
			),
		);
	}

	/**
	 * Test group grouping.
	 */
	public function test_group_grouping(): void {
		$input = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'  => 'title',
								'id'    => 'group_1',
								'title' => 'group 1',
								'desc'  => 'Description 1',
							),
							array(
								'type' => 'text',
								'id'   => 'setting1',
							),
							array(
								'type' => 'text',
								'id'   => 'setting2',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'group_1',
							),
						),
					),
				),
			),
		);

		$expected = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'     => 'group',
								'id'       => 'group_1',
								'title'    => 'group 1',
								'desc'     => 'Description 1',
								'settings' => array(
									array(
										'type' => 'text',
										'id'   => 'setting1',
									),
									array(
										'type' => 'text',
										'id'   => 'setting2',
									),
								),
							),
						),
					),
				),
			),
		);

		$this->assertEquals( $expected, $this->transformer->transform( $input ), 'Expected group to be transformed' );
	}


	/**
	 * Test group grouping with no ids present.
	 */
	public function test_group_grouping_no_ids(): void {
		$input = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'  => 'title',
								'title' => 'group 1',
								'desc'  => 'Description 1',
							),
							array(
								'type' => 'text',
								'id'   => 'setting1',
							),
							array(
								'type' => 'text',
								'id'   => 'setting2',
							),
							array(
								'type' => 'sectionend',
							),
						),
					),
					'section2' => array(
						'settings' => array(
							array(
								'type' => 'title',
							),
							array(
								'type' => 'text',
							),
						),
					),
				),
			),
		);

		$expected = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'     => 'group',
								'id'       => 'setting_group1',
								'title'    => 'group 1',
								'desc'     => 'Description 1',
								'settings' => array(
									array(
										'type' => 'text',
										'id'   => 'setting1',
									),
									array(
										'type' => 'text',
										'id'   => 'setting2',
									),
								),
							),
						),
					),
					'section2' => array(
						'settings' => array(
							array(
								'type' => 'title',
								'id'   => 'setting_title1',
							),
							array(
								'type' => 'text',
								'id'   => 'setting_field1',
							),
						),
					),
				),
			),
		);

		$this->assertEquals( $expected, $this->transformer->transform( $input ), 'Expected group to be transformed' );
	}


	/**
	 * Test multiple groups in a section.
	 */
	public function test_multiple_groups(): void {
		$input = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'  => 'title',
								'id'    => 'group_1',
								'title' => 'group 1',
							),
							array(
								'type' => 'text',
								'id'   => 'setting1',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'group_1',
							),
							array(
								'type'  => 'title',
								'id'    => 'group_2',
								'title' => 'group 2',
							),
							array(
								'type' => 'text',
								'id'   => 'setting2',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'group_2',
							),
						),
					),
				),
			),
		);

		$transformed = $this->transformer->transform( $input );
		$settings    = $transformed['tab1']['sections']['section1']['settings'];

		$expected = array(
			array(
				'type'     => 'group',
				'id'       => 'group_1',
				'title'    => 'group 1',
				'settings' => array(
					array(
						'type' => 'text',
						'id'   => 'setting1',
					),
				),
			),
			array(
				'type'     => 'group',
				'id'       => 'group_2',
				'title'    => 'group 2',
				'settings' => array(
					array(
						'type' => 'text',
						'id'   => 'setting2',
					),
				),
			),
		);

		$this->assertCount( 2, $settings );
		$this->assertEquals( $expected, $settings );
	}

		/**
		 * Test mixed valid and invalid groups.
		 */
	public function test_mixed_valid_and_invalid_groups(): void {
		$input = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							// Valid group.
							array(
								'type'  => 'title',
								'id'    => 'valid_group',
								'title' => 'Valid group',
							),
							array(
								'type' => 'text',
								'id'   => 'setting1',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'valid_group',
							),
							// Invalid group.
							array(
								'type'  => 'title',
								'id'    => 'invalid_group',
								'title' => 'Invalid group',
							),
							array(
								'type' => 'text',
								'id'   => 'setting2',
							),
							// No matching sectionend
							// Valid checkbox group.
							array(
								'type'          => 'checkbox',
								'id'            => 'check1',
								'title'         => 'Valid Group',
								'checkboxgroup' => 'start',
							),
							array(
								'type'          => 'checkbox',
								'id'            => 'check2',
								'checkboxgroup' => 'end',
							),
							// Invalid checkbox group.
							array(
								'type'          => 'checkbox',
								'id'            => 'check3',
								'title'         => 'Invalid Group',
								'checkboxgroup' => 'start',
							),
							// No matching end.
						),
					),
				),
			),
		);

		$expected = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							// Valid group gets transformed.
							array(
								'type'     => 'group',
								'id'       => 'valid_group',
								'title'    => 'Valid group',
								'settings' => array(
									array(
										'type' => 'text',
										'id'   => 'setting1',
									),
								),
							),
							// Invalid group remains untransformed.
							array(
								'type'  => 'title',
								'id'    => 'invalid_group',
								'title' => 'Invalid group',
							),
							array(
								'type' => 'text',
								'id'   => 'setting2',
							),
							// Valid checkbox group gets transformed.
							array(
								'id'       => 'setting_checkboxgroup1',
								'type'     => 'checkboxgroup',
								'title'    => 'Valid Group',
								'settings' => array(
									array(
										'type'          => 'checkbox',
										'id'            => 'check1',
										'title'         => 'Valid Group',
										'checkboxgroup' => 'start',
									),
									array(
										'type'          => 'checkbox',
										'id'            => 'check2',
										'checkboxgroup' => 'end',
									),
								),
							),
							// Invalid checkbox group remains untransformed.
							array(
								'type'          => 'checkbox',
								'id'            => 'check3',
								'title'         => 'Invalid Group',
								'checkboxgroup' => 'start',
							),
						),
					),
				),
			),
		);

		$this->assertEquals( $expected, $this->transformer->transform( $input ) );
	}

	/**
	 * Test checkbox group transformation.
	 */
	public function test_checkbox_group_transformation(): void {
		$input = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'          => 'checkbox',
								'id'            => 'check1',
								'title'         => 'Checkbox Group',
								'checkboxgroup' => 'start',
							),
							array(
								'type'          => 'checkbox',
								'id'            => 'check2',
								'checkboxgroup' => '',
							),
							array(
								'type'          => 'checkbox',
								'id'            => 'check3',
								'checkboxgroup' => 'end',
							),
						),
					),
				),
			),
		);

		$expected = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'id'       => 'setting_checkboxgroup2',
								'type'     => 'checkboxgroup',
								'title'    => 'Checkbox Group',
								'settings' => array(
									array(
										'type'          => 'checkbox',
										'id'            => 'check1',
										'title'         => 'Checkbox Group',
										'checkboxgroup' => 'start',
									),
									array(
										'type'          => 'checkbox',
										'id'            => 'check2',
										'checkboxgroup' => '',
									),
									array(
										'type'          => 'checkbox',
										'id'            => 'check3',
										'checkboxgroup' => 'end',
									),
								),
							),
						),
					),
				),
			),
		);

		$this->assertEquals( $expected, $this->transformer->transform( $input ), 'Expected checkbox group to be transformed' );
	}


	/**
	 * Test multiple independent checkboxes.
	 */
	public function test_independent_checkboxes(): void {
		$input = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type' => 'checkbox',
								'id'   => 'check1',
							),
							array(
								'type' => 'checkbox',
								'id'   => 'check2',
							),
						),
					),
				),
			),
		);

		$expected = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type' => 'checkbox',
								'id'   => 'check1',
							),
							array(
								'type' => 'checkbox',
								'id'   => 'check2',
							),
						),
					),
				),
			),
		);

		$this->assertEquals( $expected, $this->transformer->transform( $input ), 'Expected independent checkboxes to remain untransformed' );
	}

	/**
	 * Test that unmatched title and sectionend are left as is.
	 */
	public function test_unmatched_title_and_sectionend(): void {
		$input = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'  => 'title',
								'id'    => 'group_1',
								'title' => 'group 1',
							),
							array(
								'type' => 'text',
								'id'   => 'setting1',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'different_id', // Mismatched ID.
							),
						),
					),
				),
			),
		);

		$expected = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'  => 'title',
								'id'    => 'group_1',
								'title' => 'group 1',
							),
							array(
								'type' => 'text',
								'id'   => 'setting1',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'different_id',
							),
						),
					),
				),
			),
		);

		$this->assertEquals( $expected, $this->transformer->transform( $input ), 'Expected unmatched title and sectionend to remain untransformed' );
	}

	/**
	 * Test that incomplete checkbox groups are left as is.
	 */
	public function test_incomplete_checkbox_group(): void {
		$input = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'          => 'checkbox',
								'id'            => 'check1',
								'title'         => 'Checkbox 1',
								'checkboxgroup' => 'start',
							),
							array(
								'type'          => 'checkbox',
								'id'            => 'check2',
								'checkboxgroup' => '',
							),
							// Missing 'end' checkbox.
							array(
								'type' => 'text',
								'id'   => 'setting1',
							),
						),
					),
				),
			),
		);

		$expected = $input;

		$this->assertEquals( $expected, $this->transformer->transform( $input ), 'Expected incomplete checkbox group to remain untransformed' );
	}

	/**
	 * Test orphaned end checkbox.
	 */
	public function test_orphaned_end_checkbox(): void {
		$input = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'          => 'checkbox',
								'id'            => 'check1',
								'checkboxgroup' => 'end', // Orphaned end.
							),
							array(
								'type' => 'text',
								'id'   => 'setting1',
							),
						),
					),
				),
			),
		);

		$expected = $input;

		$this->assertEquals( $expected, $this->transformer->transform( $input ), 'Expected orphaned end checkbox to remain untransformed' );
	}

	/**
	 * Test group with checkbox inside.
	 */
	public function test_group_with_checkbox_inside(): void {
		$input = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'  => 'title',
								'id'    => 'group_1',
								'title' => 'group 1',
							),
							array(
								'type'          => 'checkbox',
								'id'            => 'check1',
								'title'         => 'Checkbox Group',
								'checkboxgroup' => 'start',
							),
							array(
								'type'          => 'checkbox',
								'id'            => 'check2',
								'checkboxgroup' => 'end',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'group_1',
							),
						),
					),
				),
			),
		);

		$expected = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'     => 'group',
								'id'       => 'group_1',
								'title'    => 'group 1',
								'settings' => array(
									array(
										'id'       => 'setting_checkboxgroup3',
										'type'     => 'checkboxgroup',
										'title'    => 'Checkbox Group',
										'settings' => array(
											array(
												'type'  => 'checkbox',
												'id'    => 'check1',
												'title' => 'Checkbox Group',
												'checkboxgroup' => 'start',
											),
											array(
												'type' => 'checkbox',
												'id'   => 'check2',
												'checkboxgroup' => 'end',
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

		$this->assertEquals( $expected, $this->transformer->transform( $input ), 'Expected nested sectionend and checkboxgroup to be transformed' );
	}

	/**
	 * Test group with malformed checkboxgroup inside. The checkbox items should still be included in the group.
	 */
	public function test_group_with_malformed_checkboxgroup_inside(): void {
		$input = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'  => 'title',
								'id'    => 'group_1',
								'title' => 'group 1',
							),
							array(
								'type'          => 'checkbox',
								'id'            => 'check1',
								'title'         => 'Checkbox Group',
								'checkboxgroup' => 'start',
							),
							array(
								'type'          => 'checkbox',
								'id'            => 'check2',
								// Starting a new group without closing the previous one.
								'checkboxgroup' => 'start',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'group_1',
							),
						),
					),
				),
			),
		);

		$expected = array(
			'tab1' => array(
				'sections' => array(
					'section1' => array(
						'settings' => array(
							array(
								'type'     => 'group',
								'id'       => 'group_1',
								'title'    => 'group 1',
								'settings' => array(
									array(
										'type'          => 'checkbox',
										'id'            => 'check1',
										'title'         => 'Checkbox Group',
										'checkboxgroup' => 'start',
									),
									array(
										'type'          => 'checkbox',
										'id'            => 'check2',
										'checkboxgroup' => 'start',
									),
								),
							),
						),
					),
				),
			),
		);

		$this->assertEquals( $expected, $this->transformer->transform( $input ), 'Expected nested sectionend and malformed checkboxgroup to be transformed' );
	}
}
