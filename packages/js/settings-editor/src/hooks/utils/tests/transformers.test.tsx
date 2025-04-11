/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	transformToInitialData,
	transformToField,
	transformToFormField,
	getLabelAndHelp,
} from '../transformers';

describe( 'dataforms-transformers', () => {
	describe( 'transformToInitialData', () => {
		it( 'should transform checkbox group settings correctly', () => {
			const settings: CheckboxGroupSettingsField = {
				id: 'group1',
				type: 'checkboxgroup',
				title: 'Group 1',
				settings: [
					{ id: 'setting1', type: 'checkbox', value: 'yes' },
					{ id: 'setting2', type: 'checkbox', value: 'no' },
					{ id: 'setting3', type: 'checkbox', value: false },
				],
			};

			const result = transformToInitialData( settings, {} );
			expect( result ).toEqual( {
				setting1: 'yes',
				setting2: 'no',
				setting3: 'no',
			} );
		} );

		it( 'should handle regular settings correctly', () => {
			const settings: SettingsField[] = [
				{ id: 'text1', type: 'text', value: 'hello' },
				{ id: 'select1', type: 'select', value: 'option1' },
				{ id: 'empty1', type: 'text', value: '' },
			];

			const result = settings.reduce(
				( acc, setting ) => transformToInitialData( setting, acc ),
				{}
			);
			expect( result ).toEqual( {
				text1: 'hello',
				select1: 'option1',
				empty1: '',
			} );
		} );
	} );

	describe( 'transformToField', () => {
		it( 'should transform text input fields correctly', () => {
			const setting: SettingsField = {
				id: 'text1',
				type: 'text',
				desc: 'Text Input',
				value: 'hello',
			};

			const result = transformToField( setting );
			expect( result ).toEqual( {
				id: 'text1',
				type: 'text',
				label: 'Text Input',
				Edit: expect.any( Function ),
			} );
		} );

		it( 'should transform checkbox fields correctly', () => {
			const setting: SettingsField = {
				id: 'check1',
				type: 'checkbox',
				desc: 'Checkbox Input',
				value: 'yes',
			};

			const result = transformToField( setting );
			expect( result ).toEqual( {
				id: 'check1',
				type: 'text',
				label: 'Checkbox Input',
				Edit: expect.any( Function ),
			} );
		} );

		it( 'should transform select fields with options correctly', () => {
			const setting: SettingsField = {
				id: 'select1',
				type: 'select',
				desc: 'Select Input',
				value: 'value1',
				options: {
					value1: 'Option 1',
					value2: 'Option 2',
				},
			};

			const result = transformToField( setting );
			expect( result ).toEqual( {
				id: 'select1',
				type: 'text',
				label: 'Select Input',
				elements: [
					{ label: 'Option 1', value: 'value1' },
					{ label: 'Option 2', value: 'value2' },
				],
				Edit: expect.any( Function ),
			} );
		} );

		it( 'should transform custom fields correctly', () => {
			const setting: CustomSettingsField = {
				id: 'custom1',
				type: 'custom',
				content: '<div>Custom Content</div>',
			};

			const result = transformToField( setting );
			expect( result ).toEqual( {
				id: 'custom1',
				type: 'text',
				Edit: expect.any( Function ),
			} );
		} );

		it( 'should transform group fields correctly', () => {
			const setting: GroupSettingsField = {
				id: 'group1',
				type: 'group',
				settings: [ { id: 'field1', type: 'text', value: 'value1' } ],
			};

			const result = transformToField( setting );
			expect( result ).toEqual( {
				id: 'group1',
				label: '',
				Edit: expect.any( Function ),
			} );
		} );

		it( 'should transform checkbox group into multiple fields', () => {
			const setting: CheckboxGroupSettingsField = {
				id: 'group1',
				type: 'checkboxgroup',
				title: 'Group 1',
				settings: [
					{
						id: 'check1',
						type: 'checkbox',
						desc: 'Check 1',
						value: 'yes',
					},
					{
						id: 'check2',
						type: 'checkbox',
						desc: 'Check 2',
						value: 'no',
					},
				],
			};

			const result = transformToField( setting );
			expect( Array.isArray( result ) ).toBe( true );
			expect( result ).toEqual( [
				{
					id: 'check1',
					type: 'text',
					label: 'Check 1',
					Edit: expect.any( Function ),
				},
				{
					id: 'check2',
					type: 'text',
					label: 'Check 2',
					Edit: expect.any( Function ),
				},
			] );
		} );

		it( 'should transform info view fields correctly', () => {
			const setting: InfoSettingsField = {
				id: 'info1',
				type: 'info',
				title: 'Info View',
				text: 'Info View',
			};

			const result = transformToField( setting );
			expect( result ).toEqual( {
				id: 'info1',
				label: 'Info View',
				type: 'text',
				Edit: expect.any( Function ),
			} );
		} );
	} );

	describe( 'transformToFormField', () => {
		it( 'should transform regular fields correctly', () => {
			const setting: SettingsField = {
				id: 'text1',
				type: 'text',
				title: 'Text Input',
				value: 'hello',
			};

			const result = transformToFormField( setting );
			expect( result ).toEqual( {
				id: 'text1',
				label: 'Text Input',
				children: [ 'text1' ],
			} );
		} );

		it( 'should transform checkbox group correctly', () => {
			const setting: CheckboxGroupSettingsField = {
				id: 'group1',
				type: 'checkboxgroup',
				title: 'Group 1',
				settings: [
					{ id: 'check1', type: 'checkbox', value: 'yes' },
					{ id: 'check2', type: 'checkbox', value: 'no' },
				],
			};

			const result = transformToFormField( setting );
			expect( result ).toEqual( {
				id: 'group1',
				label: 'Group 1',
				children: [ 'check1', 'check2' ],
			} );
		} );

		it( 'should return false for title and sectionend', () => {
			const title: SettingsField = {
				id: 'title1',
				type: 'title',
				value: '',
			};
			const sectionend: SettingsField = {
				id: 'end1',
				type: 'sectionend',
				value: '',
			};

			expect( transformToFormField( title ) ).toBe( false );
			expect( transformToFormField( sectionend ) ).toBe( false );
		} );

		it( 'should return id for special fields', () => {
			const custom: CustomSettingsField = {
				id: 'custom1',
				type: 'custom',
				content: '<div>Custom</div>',
			};
			const group: GroupSettingsField = {
				id: 'group1',
				type: 'group',
				settings: [],
			};
			const slotfill: SettingsField = {
				id: 'slot1',
				type: 'slotfill_placeholder',
				value: '',
				class: 'my-slot',
			};

			expect( transformToFormField( custom ) ).toBe( 'custom1' );
			expect( transformToFormField( group ) ).toBe( 'group1' );
			expect( transformToFormField( slotfill ) ).toBe( 'slot1' );
		} );
	} );

	describe( 'getLabelAndHelp', () => {
		it( 'should set help text to desc when desc_tip is true', () => {
			const setting: BaseSettingsField = {
				id: 'test',
				type: 'text',
				desc: 'Test description',
				desc_tip: true,
				value: 'test',
			};

			const result = getLabelAndHelp( setting );
			expect( result ).toEqual( {
				label: '',
				help: 'Test description',
			} );
		} );

		it( 'should set label and help text when both desc and desc_tip are provided', () => {
			const setting: BaseSettingsField = {
				id: 'test',
				type: 'text',
				desc: 'Main description',
				desc_tip: 'Helpful tip',
				value: 'test',
			};

			const result = getLabelAndHelp( setting );
			expect( result ).toEqual( {
				label: 'Main description',
				help: 'Helpful tip',
			} );
		} );

		it( 'should set empty help text when desc_tip is false', () => {
			const setting: BaseSettingsField = {
				id: 'test',
				type: 'text',
				desc: 'Test description',
				desc_tip: false,
				value: 'test',
			};

			const result = getLabelAndHelp( setting );
			expect( result ).toEqual( {
				label: 'Test description',
				help: '',
			} );
		} );

		it( 'should handle desc_tip undefined', () => {
			const setting: BaseSettingsField = {
				id: 'test',
				type: 'text',
				desc: 'Test description',
				value: 'test',
			};

			const result = getLabelAndHelp( setting );
			expect( result ).toEqual( {
				label: 'Test description',
				help: '',
			} );
		} );

		it( 'should use description if desc is not provided', () => {
			const setting: BaseSettingsField = {
				id: 'test',
				type: 'text',
				description: 'Test description',
				value: 'test',
			};

			const result = getLabelAndHelp( setting );
			expect( result ).toEqual( {
				label: 'Test description',
				help: '',
			} );
		} );

		it( 'should handle empty descriptions', () => {
			const setting: BaseSettingsField = {
				id: 'test',
				type: 'text',
				value: 'test',
			};

			const result = getLabelAndHelp( setting );
			expect( result ).toEqual( {
				label: '',
				help: '',
			} );
		} );
	} );
} );
