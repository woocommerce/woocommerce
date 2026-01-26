/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	baseFieldTransformer,
	createChildrenWithRows,
	parseOptions,
	reorderGroupFields,
	registerFieldTypeTransformer,
} from '../field-transformers';
import type { ReactSettingsField } from '../types';

describe( 'field transformers', () => {
	it( 'parses options from a record', () => {
		const options = parseOptions( { usd: 'US Dollar', eur: 'Euro' } );

		expect( options ).toEqual( [
			{ label: 'US Dollar', value: 'usd' },
			{ label: 'Euro', value: 'eur' },
		] );
	} );

	it( 'parses options from an array', () => {
		const options = parseOptions( [
			{ label: 'One', value: '1', desc: 'First' },
			{ label: 'Two', value: '2' },
		] );

		expect( options ).toEqual( [
			{ label: 'One', value: '1', description: 'First' },
			{ label: 'Two', value: '2', description: undefined },
		] );
	} );

	it( 'reorders group fields using provided order', () => {
		const fieldIds = [ 'third', 'first', 'second' ];
		const orderConfig = {
			general: [ 'first', 'second' ],
		};

		expect(
			reorderGroupFields( fieldIds, 'general', orderConfig )
		).toEqual( [ 'first', 'second', 'third' ] );
	} );

	it( 'creates row group children for configured fields', () => {
		const children = createChildrenWithRows( [ 'a', 'b', 'c' ], [
			{ id: 'row1', fields: [ 'a', 'b' ] },
		] );

		expect( children ).toEqual( [
			{
				id: 'row1',
				layout: { type: 'row' },
				children: [ 'a', 'b' ],
			},
			{ id: 'c' },
		] );
	} );

	it( 'transforms checkbox fields with get/set handlers', () => {
		const field: ReactSettingsField = {
			id: 'checkbox_field',
			label: 'Checkbox field',
			type: 'checkbox',
		};

		const transformed = baseFieldTransformer( field );

		expect( transformed.type ).toBe( 'boolean' );
		expect( typeof transformed.getValue ).toBe( 'function' );
		expect( typeof transformed.setValue ).toBe( 'function' );

		const getValue = transformed.getValue as ( input: {
			item: Record< string, unknown >;
		} ) => boolean;
		const setValue = transformed.setValue as ( input: {
			item: Record< string, unknown >;
			value: boolean;
		} ) => Record< string, unknown >;

		expect( getValue( { item: { checkbox_field: 'yes' } } ) ).toBe(
			true
		);
		expect( setValue( { item: {}, value: true } ) ).toEqual( {
			checkbox_field: 'yes',
		} );
	} );

	it( 'renders unsupported field message safely', () => {
		const field: ReactSettingsField = {
			id: 'unsupported',
			label: 'Unsupported',
			type: 'unsupported_field_type',
		};

		const transformed = baseFieldTransformer( field );
		const Edit = transformed.Edit as React.ComponentType;

		const { getByText } = render( <Edit /> );

		expect(
			getByText( 'This setting is not available yet.' )
		).toBeInTheDocument();
	} );

	it( 'uses custom field type transformers when registered', () => {
		registerFieldTypeTransformer(
			'custom_transformer_test',
			( setting, baseField ) => ( {
			...baseField,
			type: 'text',
			Edit: () => <div>{ setting.label }</div>,
			} )
		);

		const field: ReactSettingsField = {
			id: 'custom_field',
			label: 'Custom field',
			type: 'custom_transformer_test',
		};

		const transformed = baseFieldTransformer( field );
		const Edit = transformed.Edit as React.ComponentType;

		const { getByText } = render( <Edit /> );

		expect( getByText( 'Custom field' ) ).toBeInTheDocument();
	} );
} );
