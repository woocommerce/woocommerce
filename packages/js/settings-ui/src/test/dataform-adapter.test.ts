/**
 * External dependencies
 */
import type { DataFormControlProps } from '@wordpress/dataviews';
import type { ReactElement } from 'react';

/**
 * Internal dependencies
 */
import { buildDataFormField, createDataFormAdapter } from '../dataform-adapter';
import type { DataFormAdapterOptions } from '../dataform-adapter';
import { __resetRegistry, registerSettingsExtension } from '../registry';
import type {
	SettingsEditControlProps,
	SettingsUIField,
	SettingsUIGroup,
	SettingsUISchema,
	SettingsValues,
} from '../types';

const makeField = (
	overrides: Partial< SettingsUIField > = {}
): SettingsUIField => ( {
	id: 'field',
	label: 'Field',
	type: 'text',
	...overrides,
} );

const makeSchema = ( groups: SettingsUIGroup[] ): SettingsUISchema => ( {
	id: 'test-page',
	title: 'Test page',
	section: 'default',
	save: { adapter: 'none' },
	groups: Object.fromEntries(
		groups.map( ( group ) => [ group.id, group ] )
	),
} );

const makeOptions = (
	groups: SettingsUIGroup[],
	initialValues: SettingsValues = {}
): DataFormAdapterOptions => ( {
	schema: makeSchema( groups ),
	context: { page: 'test-page', section: '' },
	initialValues,
} );

const buildField = (
	field: SettingsUIField,
	initialValues: SettingsValues = {}
) =>
	buildDataFormField(
		field,
		makeOptions( [ { id: 'general', fields: [ field ] } ], initialValues )
	);

describe( 'dataform-adapter', () => {
	afterEach( () => {
		__resetRegistry();
		jest.restoreAllMocks();
	} );

	describe( 'field mapping', () => {
		it.each( [
			{ settingsType: 'checkbox', type: 'boolean', Edit: undefined },
			{ settingsType: 'radio', type: 'text', Edit: 'radio' },
			{ settingsType: 'number', type: 'number', Edit: undefined },
			{ settingsType: 'integer', type: 'integer', Edit: undefined },
			{ settingsType: 'array', type: 'array', Edit: undefined },
			{ settingsType: 'color', type: 'color', Edit: undefined },
		] )(
			'keeps $settingsType on the package $type control',
			( expected ) => {
				const field = buildField(
					makeField( { type: expected.settingsType } )
				);

				expect( field.type ).toBe( expected.type );
				expect( field.Edit ).toEqual( expected.Edit );
			}
		);

		it.each( [
			[ 'select', 'text' ],
			[ 'tel', 'telephone' ],
			[ 'text', 'text' ],
			[ 'email', 'email' ],
			[ 'url', 'url' ],
			[ 'password', 'password' ],
			[ 'time', 'text' ],
			[ 'date', 'date' ],
			[ 'datetime-local', 'datetime' ],
			[ 'textarea', 'text' ],
		] )(
			'maps %s to a Woo UI override with type %s',
			( settingsType, type ) => {
				const field = buildField( makeField( { type: settingsType } ) );

				expect( field.type ).toBe( type );
				expect( field.Edit ).toEqual( expect.any( Function ) );
			}
		);

		it( 'renders disabled package controls read-only without changing options', () => {
			const options = [ { value: 'one', label: 'One' } ];
			const field = buildField(
				makeField( { type: 'number', disabled: true, options } )
			);

			expect( field.readOnly ).toBe( true );
			expect( field.elements ).toBe( options );
			expect( 'isDisabled' in field ).toBe( false );
		} );

		it( 'leaves numeric DataForm custom validation untouched', () => {
			const field = buildField(
				makeField( {
					type: 'integer',
					validation: { min: 0, max: 10 },
				} )
			);

			expect( field.type ).toBe( 'integer' );
			expect( field.isValid ).toBeUndefined();
		} );

		it( 'falls back unknown field types to text with a diagnostic', () => {
			const warn = jest
				.spyOn( console, 'warn' )
				.mockImplementation( () => {} );
			const field = buildField( makeField( { type: 'image_width' } ) );

			expect( field.type ).toBe( 'text' );
			expect(
				field.getValue?.( {
					item: { field: { width: 100 } as never },
				} )
			).toEqual( expect.any( String ) );
			expect( warn ).toHaveBeenCalledWith(
				expect.stringContaining( 'image_width' ),
				expect.anything()
			);
		} );

		it( 'converts package control descriptions to plain text', () => {
			const field = buildField(
				makeField( {
					type: 'number',
					description: '<strong>Useful</strong> <em>details</em>',
				} )
			);

			expect( field.description ).toBe( 'Useful details' );
		} );

		it( 'maps info fields to a read-only render function', () => {
			const field = buildField(
				makeField( {
					type: 'info',
					description: '<em>Allowed</em><script>alert("x")</script>',
				} )
			);

			expect( field.readOnly ).toBe( true );
			expect( field.render ).toEqual( expect.any( Function ) );
		} );
	} );

	describe( 'visibility', () => {
		it.each( [
			[ { mode: 'advanced' }, true ],
			[ { mode: 'simple' }, false ],
		] )( 'evaluates declarative rules for %p', ( values, expected ) => {
			const field = buildField(
				makeField( {
					id: 'dependent',
					visibility: { controller: 'mode', value: 'advanced' },
				} )
			);

			expect( field.isVisible?.( values ) ).toBe( expected );
		} );

		it.each( [
			[ true, true ],
			[ false, false ],
		] )( 'matches canonical checkbox value %p', ( value, expected ) => {
			const field = buildField(
				makeField( {
					id: 'dependent',
					visibility: { controller: 'flag', value: true },
				} )
			);

			expect( field.isVisible?.( { flag: value } ) ).toBe( expected );
		} );

		it( 'uses registered predicates and fails open', () => {
			const settingsField = makeField( {
				id: 'dependent',
				visibility: { controller: 'mode', value: 'advanced' },
			} );
			registerSettingsExtension( {
				scope: { page: 'test-page', section: '' },
				fieldVisibility: {
					dependent: ( { values } ) => values.mode === 'simple',
				},
			} );
			const field = buildField( settingsField );

			expect( field.isVisible?.( { mode: 'simple' } ) ).toBe( true );

			__resetRegistry();
			jest.spyOn( console, 'warn' ).mockImplementation( () => {} );
			registerSettingsExtension( {
				scope: { page: 'test-page', section: '' },
				fieldVisibility: {
					dependent: () => {
						throw new Error( 'boom' );
					},
				},
			} );

			expect( buildField( settingsField ).isVisible?.( {} ) ).toBe(
				true
			);
		} );
	} );

	it( 'wraps DataForm callbacks at the modern contract boundary', () => {
		const CustomField = () => null;
		registerSettingsExtension( {
			scope: { page: 'test-page', section: '' },
			fieldOverrides: {
				count: { component: CustomField },
			},
		} );
		const field = buildField(
			makeField( { id: 'count', type: 'number' } ),
			{ count: 1 }
		);
		const dataFormGetValue = jest.fn( ( { item } ) => item.count );
		const dataFormOnChange = jest.fn();
		const Edit = field.Edit as (
			props: DataFormControlProps< SettingsValues >
		) => ReactElement< SettingsEditControlProps >;
		const element = Edit( {
			data: { count: 1 },
			field: {
				id: 'count',
				label: 'Count',
				getValue: dataFormGetValue,
			},
			onChange: dataFormOnChange,
		} as DataFormControlProps< SettingsValues > );

		expect( element.props.onChange ).not.toBe( dataFormOnChange );
		expect( element.props.field.getValue ).not.toBe( dataFormGetValue );

		element.props.onChange( { count: 2 } );
		expect( dataFormOnChange ).toHaveBeenCalledWith( { count: 2 } );
		expect( element.props.field.getValue( { item: { count: 3 } } ) ).toBe(
			3
		);
		expect( dataFormGetValue ).toHaveBeenCalledWith( {
			item: { count: 3 },
		} );
	} );

	it( 'keeps registered validators out of the DataForm custom rule', () => {
		const CustomField = () => null;
		registerSettingsExtension( {
			scope: { page: 'test-page', section: '' },
			fieldOverrides: {
				count: {
					component: CustomField,
					validate: () => 'This value is invalid.',
				},
			},
		} );
		const field = buildField(
			makeField( { id: 'count', type: 'number' } ),
			{ count: 1 }
		);

		expect( field.Edit ).toEqual( expect.any( Function ) );
		expect( field.isValid ).toBeUndefined();
	} );

	describe( 'visible groups and validation', () => {
		it( 'omits groups whose fields are hidden', () => {
			const visibleGroup: SettingsUIGroup = {
				id: 'visible',
				fields: [ makeField( { id: 'visible-field' } ) ],
			};
			const hiddenGroup: SettingsUIGroup = {
				id: 'hidden',
				fields: [
					makeField( {
						id: 'hidden-field',
						visibility: { controller: 'mode', value: 'never' },
					} ),
				],
			};
			const adapter = createDataFormAdapter(
				makeOptions( [ visibleGroup, hiddenGroup ] )
			);

			expect( adapter.getVisibleGroups( { mode: 'other' } ) ).toEqual( [
				visibleGroup,
			] );
		} );

		it( 'omits hidden and disabled fields from the validation form', () => {
			const groups: SettingsUIGroup[] = [
				{
					id: 'general',
					fields: [
						makeField( { id: 'mode' } ),
						makeField( {
							id: 'dependent',
							visibility: {
								controller: 'mode',
								value: 'advanced',
							},
						} ),
						makeField( { id: 'disabled', disabled: true } ),
					],
				},
			];
			const adapter = createDataFormAdapter( makeOptions( groups ) );

			expect(
				adapter.getValidationForm( { mode: 'simple' } ).fields
			).toEqual( [ { id: 'general', children: [ 'mode' ] } ] );
			expect(
				adapter.getValidationForm( { mode: 'advanced' } ).fields
			).toEqual( [
				{ id: 'general', children: [ 'mode', 'dependent' ] },
			] );
		} );
	} );
} );
