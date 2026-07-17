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
	SettingsValue,
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

const getDataFormSection = (
	groups: SettingsUIGroup[],
	values: SettingsValues = {}
) => {
	const sections = createDataFormAdapter(
		makeOptions( groups )
	).getRenderSections( values );

	expect( sections ).toHaveLength( 1 );
	expect( sections[ 0 ].type ).toBe( 'dataform' );
	return sections[ 0 ];
};

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

		it( 'maps disabled fields to isDisabled', () => {
			const field = buildField( makeField( { disabled: true } ) );

			expect(
				field.isDisabled?.( { item: {}, field: field as never } )
			).toBe( true );
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

		it( 'maps canonical options to DataForm elements', () => {
			const field = buildField(
				makeField( {
					type: 'select',
					options: [
						{ value: 'one', label: 'One' },
						{ value: 'two', label: 'Two' },
					],
				} )
			);

			expect( field.elements ).toEqual( [
				{ value: 'one', label: 'One' },
				{ value: 'two', label: 'Two' },
			] );
		} );
	} );

	describe( 'canonical values', () => {
		it.each( [
			[ makeField( { id: 'flag', type: 'checkbox' } ), true ],
			[ makeField( { id: 'window', type: 'number' } ), 30 ],
			[ makeField( { id: 'countries', type: 'array' } ), [ 'GB', 'US' ] ],
			[
				makeField( { id: 'starts_at', type: 'datetime-local' } ),
				'2026-07-18T14:45:00.000Z',
			],
		] as Array< [ SettingsUIField, SettingsValue ] > )(
			'presents canonical values without conversion',
			( settingsField, value ) => {
				expect(
					buildField( settingsField ).getValue?.( {
						item: { [ settingsField.id ]: value },
					} )
				).toEqual( value );
			}
		);

		it( 'writes canonical values without conversion', () => {
			const field = buildField(
				makeField( { id: 'window', type: 'number' } )
			);

			expect( field.setValue?.( { item: {}, value: 35 } ) ).toEqual( {
				window: 35,
			} );
		} );

		it( 'normalizes a cleared DataForm value to null', () => {
			const field = buildField(
				makeField( { id: 'starts_at', type: 'datetime-local' } )
			);

			expect(
				field.setValue?.( { item: {}, value: undefined } )
			).toEqual( { starts_at: null } );
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

			expect( field.isVisible?.( {} ) ).toBe( true );
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
			field.isValid?.custom?.( { store_name: 'Current' }, field as never )
		).toBe( 'This value is invalid.' );
		expect( validate ).toHaveBeenCalledWith( {
			value: 'Current',
			values: { store_name: 'Current' },
			field: settingsField,
			context: { page: 'test-page', section: '' },
		} );
	} );

	describe( 'form construction', () => {
		const plainGroup: SettingsUIGroup = {
			id: 'plain-a',
			title: 'Plain A',
			fields: [ makeField( { id: 'a' } ) ],
		};
		const plainGroupB: SettingsUIGroup = {
			id: 'plain-b',
			title: 'Plain B',
			fields: [ makeField( { id: 'b' } ) ],
		};

		it( 'batches groups into combined form fields', () => {
			const describedGroup: SettingsUIGroup = {
				id: 'described',
				title: 'Described',
				description: 'Rich <strong>text</strong>',
				fields: [ makeField( { id: 'c' } ) ],
			};
			const section = getDataFormSection( [
				plainGroup,
				describedGroup,
				plainGroupB,
			] );

			expect( section.form.fields ).toEqual( [
				expect.objectContaining( { id: 'plain-a', children: [ 'a' ] } ),
				expect.objectContaining( {
					id: 'described',
					children: [ 'c' ],
					description: expect.any( Object ),
				} ),
				expect.objectContaining( { id: 'plain-b', children: [ 'b' ] } ),
			] );
		} );

		it( 'uses shell cards for groups with actions', () => {
			const actionsGroup: SettingsUIGroup = {
				id: 'with-actions',
				title: 'With actions',
				actions: [
					{
						id: 'manage',
						label: 'Manage',
						href: 'https://example.com',
					},
				],
				fields: [ makeField( { id: 'd' } ) ],
			};
			const sections = createDataFormAdapter(
				makeOptions( [ plainGroup, actionsGroup, plainGroupB ] )
			).getRenderSections( {} );

			expect( sections.map( ( section ) => section.type ) ).toEqual( [
				'dataform',
				'fallback',
				'dataform',
			] );
			expect(
				sections[ 1 ].type === 'fallback' && sections[ 1 ].group.id
			).toBe( 'with-actions' );
		} );

		it( 'omits groups whose fields are hidden', () => {
			const hiddenGroup: SettingsUIGroup = {
				id: 'hidden',
				fields: [
					makeField( {
						id: 'hidden-field',
						visibility: { controller: 'mode', value: 'never' },
					} ),
				],
			};
			const section = getDataFormSection( [ plainGroup, hiddenGroup ], {
				mode: 'other',
			} );

			expect( section.form.fields ).toHaveLength( 1 );
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
