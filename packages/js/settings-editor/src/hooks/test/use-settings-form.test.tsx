/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useSettingsForm } from '../use-settings-form';

describe( 'useSettingsForm', () => {
	const mockSettings: SettingsField[] = [
		{
			id: 'text1',
			type: 'text',
			title: 'Text Input',
			desc: 'Text Description',
			value: 'hello',
		},
		{
			id: 'group1',
			type: 'checkboxgroup',
			title: 'Checkbox Group',
			settings: [
				{
					id: 'check1',
					type: 'checkbox',
					value: 'yes',
					desc: 'Check 1',
				},
				{
					id: 'check2',
					type: 'checkbox',
					value: 'no',
					desc: 'Check 2',
				},
			],
		},
	];

	it( 'should initialize with correct data', () => {
		const { result } = renderHook( () => useSettingsForm( mockSettings ) );

		// Check initial data
		expect( result.current.data ).toEqual( {
			text1: 'hello',
			check1: 'yes',
			check2: 'no',
		} );

		// Check fields
		expect( result.current.fields ).toHaveLength( 3 ); // text1 + 2 checkboxes
		expect( result.current.fields[ 0 ] ).toEqual( {
			id: 'text1',
			type: 'text',
			label: 'Text Description',
			Edit: expect.any( Function ),
		} );

		// Check form structure
		expect( result.current.form.type ).toBe( 'regular' );
		expect( result.current.form.labelPosition ).toBe( 'top' );
		expect( result.current.form.fields ).toHaveLength( 2 ); // text1 + group1
	} );

	it( 'should update field values', () => {
		const { result } = renderHook( () => useSettingsForm( mockSettings ) );

		act( () => {
			result.current.updateField( { text1: 'updated' } );
		} );

		expect( result.current.data.text1 ).toBe( 'updated' );
		expect( result.current.isDirty ).toBe( true );
	} );

	it( 'should reset form to initial values', () => {
		const { result } = renderHook( () => useSettingsForm( mockSettings ) );

		// Make changes
		act( () => {
			result.current.updateField( { text1: 'changed' } );
			result.current.updateField( { check1: 'no' } );
		} );

		// Verify changes
		expect( result.current.data.text1 ).toBe( 'changed' );
		expect( result.current.data.check1 ).toBe( 'no' );
		expect( result.current.isDirty ).toBe( true );

		// Reset form
		act( () => {
			result.current.resetForm();
		} );

		// Verify reset
		expect( result.current.data.text1 ).toBe( 'hello' );
		expect( result.current.data.check1 ).toBe( 'yes' );
		expect( result.current.isDirty ).toBe( false );
	} );

	it( 'should track dirty state correctly', () => {
		const { result } = renderHook( () => useSettingsForm( mockSettings ) );

		// Initially clean
		expect( result.current.isDirty ).toBe( false );

		// Make a change
		act( () => {
			result.current.updateField( { text1: 'changed' } );
		} );

		// Should be dirty
		expect( result.current.isDirty ).toBe( true );

		// Change back to original value
		act( () => {
			result.current.updateField( { text1: 'hello' } );
		} );

		// Should be clean again
		expect( result.current.isDirty ).toBe( false );
	} );

	it( 'should handle multiple field updates', () => {
		const { result } = renderHook( () => useSettingsForm( mockSettings ) );

		act( () => {
			result.current.updateField( {
				text1: 'changed',
				check1: 'no',
				check2: 'yes',
			} );
		} );

		expect( result.current.data ).toEqual( {
			text1: 'changed',
			check1: 'no',
			check2: 'yes',
		} );
		expect( result.current.isDirty ).toBe( true );
	} );
} );
