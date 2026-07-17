/**
 * Internal dependencies
 */
import { buildDataFormField, createDataFormAdapter } from '../dataform-adapter';
import type { DataFormAdapterOptions } from '../dataform-adapter';
import { __resetRegistry, registerSettingsExtension } from '../registry';
import type {
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
			{ settingsType: 'select', type: 'text', Edit: 'select' },
			{ settingsType: 'number', type: 'number', Edit: undefined },
			{ settingsType: 'tel', type: 'telephone', Edit: undefined },
			{ settingsType: 'text', type: 'text', Edit: undefined },
			{ settingsType: 'email', type: 'email', Edit: undefined },
			{
				settingsType: 'textarea',
				type: 'text',
				Edit: { control: 'textarea' },
			},
			{ settingsType: 'array', type: 'array', Edit: undefined },
		] )( 'maps $settingsType to $type', ( expected ) => {
			const field = buildField(
				makeField( { type: expected.settingsType } )
			);

			expect( field.type ).toBe( expected.type );
			expect( field.Edit ).toEqual( expected.Edit );
		} );

		it( 'maps disabled fields and canonical options', () => {
			const options = [ { value: 'one', label: 'One' } ];
			const field = buildField(
				makeField( { disabled: true, options } )
			);

			expect( field.isDisabled ).toBe( true );
			expect( field.elements ).toBe( options );
		} );

		it( 'maps canonical integer fields and validation rules', () => {
			const field = buildField(
				makeField( {
					type: 'integer',
					validation: { min: 0, max: 10 },
				} )
			);

			expect( field.type ).toBe( 'integer' );
			expect( field.isValid ).toEqual( { min: 0, max: 10 } );
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

	it( 'adapts registered validator arguments', () => {
		const validate = jest.fn( () => 'This value is invalid.' );
		const CustomField = () => null;
		registerSettingsExtension( {
			scope: { page: 'test-page', section: '' },
			fieldOverrides: {
				store_name: { component: CustomField, validate },
			},
		} );
		const settingsField = makeField( { id: 'store_name' } );
		const field = buildField( settingsField, { store_name: 'Initial' } );

		expect(
			field.isValid?.custom?.( { store_name: 'Current' }, {
				...field,
				getValue: ( { item } ) => item.store_name,
			} as never )
		).toBe( 'This value is invalid.' );
		expect( validate ).toHaveBeenCalledWith( {
			value: 'Current',
			values: { store_name: 'Current' },
			field: settingsField,
			context: { page: 'test-page', section: '' },
		} );
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
