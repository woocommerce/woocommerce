/**
 * External dependencies
 */
import type {
	DataFormControlProps,
	Field,
	NormalizedField,
} from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { createDataFormAdapter, getGroupValidity } from '../dataform-adapter';
import { __resetRegistry, registerSettingsExtension } from '../registry';
import type {
	SettingsFieldComponentProps,
	SettingsUISchema,
	SettingsValues,
} from '../types';

const context = { page: 'products', section: '' };

const createSchema = (): SettingsUISchema => ( {
	id: 'products',
	save: { adapter: 'form_post' },
	groups: {
		general: {
			id: 'general',
			title: 'General',
			fields: [
				{
					id: 'enabled',
					label: 'Enabled',
					type: 'checkbox',
					value: 'yes',
				},
				{
					id: 'choice',
					label: 'Choice',
					type: 'radio',
					value: 'one',
					options: [
						{ value: 'one', label: 'One' },
						{ value: 'two', label: 'Two' },
					],
				},
				{
					id: 'country',
					label: 'Country',
					type: 'select',
					value: 'GB',
					options: [ { value: 'GB', label: 'United Kingdom' } ],
				},
				{
					id: 'notes',
					label: 'Notes',
					type: 'textarea',
					value: '',
				},
				{
					id: 'methods',
					label: 'Methods',
					type: 'array',
					value: [ 'card' ],
					options: [ { value: 'card', label: 'Card' } ],
				},
			],
		},
	},
} );

const normalizeForControl = ( field: Field< SettingsValues > | undefined ) => {
	if ( ! field ) {
		throw new Error( 'Expected an adapted field.' );
	}

	return {
		...field,
		label: field.label || field.id,
		getValue:
			field.getValue ||
			( ( { item }: { item: SettingsValues } ) => item[ field.id ] ),
		setValue:
			field.setValue ||
			( ( { value }: { value: unknown } ) => ( {
				[ field.id ]: value,
			} ) ),
	} as NormalizedField< SettingsValues >;
};

describe( 'Settings UI DataForm adapter', () => {
	beforeEach( () => {
		__resetRegistry();
	} );

	it( 'maps schema fields to DataForm field types and controls', () => {
		const schema = createSchema();
		const adapter = createDataFormAdapter( {
			schema,
			context,
			initialValues: {
				enabled: 'yes',
				choice: 'one',
				country: 'GB',
				notes: '',
				methods: [ 'card' ],
			},
		} );

		const enabled = adapter.fields.find(
			( field ) => field.id === 'enabled'
		);
		const choice = adapter.fields.find(
			( field ) => field.id === 'choice'
		);
		const country = adapter.fields.find(
			( field ) => field.id === 'country'
		);
		const notes = adapter.fields.find( ( field ) => field.id === 'notes' );
		const methods = adapter.fields.find(
			( field ) => field.id === 'methods'
		);

		expect( enabled?.type ).toBe( 'boolean' );
		expect( enabled?.getValue?.( { item: { enabled: 'yes' } } ) ).toBe(
			true
		);
		expect(
			enabled?.setValue?.( { item: { enabled: 'yes' }, value: false } )
		).toEqual( { enabled: false } );
		expect( choice?.Edit ).toBe( 'radio' );
		expect( choice?.elements ).toEqual(
			schema.groups.general.fields[ 1 ].options
		);
		expect( country?.Edit ).toBe( 'select' );
		expect( notes?.Edit ).toEqual( { control: 'textarea' } );
		expect( methods?.type ).toBe( 'array' );
		expect( methods?.Edit ).toBe( 'array' );
	} );

	it( 'builds one DataForm with combined non-collapsible card groups', () => {
		const schema = createSchema();
		schema.groups.additional = {
			id: 'additional',
			title: 'Additional',
			fields: [ { id: 'extra', label: 'Extra', type: 'text' } ],
		};

		const adapter = createDataFormAdapter( {
			schema,
			context,
			initialValues: {},
		} );
		const sections = adapter.getRenderSections( {
			enabled: true,
			choice: 'one',
			country: 'GB',
			notes: '',
			methods: [ 'card' ],
			extra: '',
		} );

		expect( sections ).toHaveLength( 1 );
		expect( sections[ 0 ].type ).toBe( 'dataform' );
		expect( sections[ 0 ].form ).toEqual( {
			layout: { type: 'card' },
			fields: [
				{
					id: 'general',
					label: 'General',
					layout: {
						type: 'card',
						withHeader: true,
						isCollapsible: false,
					},
					children: [
						'enabled',
						'choice',
						'country',
						'notes',
						'methods',
					],
				},
				{
					id: 'additional',
					label: 'Additional',
					layout: {
						type: 'card',
						withHeader: true,
						isCollapsible: false,
					},
					children: [ 'extra' ],
				},
			],
		} );
	} );

	it( 'keeps groups with actions or descriptions in constrained fallback cards', () => {
		const schema = createSchema();
		schema.groups.general.description = '<p>Rich description</p>';
		schema.groups.additional = {
			id: 'additional',
			title: 'Additional',
			actions: [
				{ id: 'docs', label: 'Docs', href: 'https://example.com' },
			],
			fields: [ { id: 'extra', label: 'Extra', type: 'text' } ],
		};

		const adapter = createDataFormAdapter( {
			schema,
			context,
			initialValues: {},
		} );
		const sections = adapter.getRenderSections( {
			enabled: true,
			choice: 'one',
			country: 'GB',
			notes: '',
			methods: [ 'card' ],
			extra: '',
		} );

		expect( sections.map( ( section ) => section.type ) ).toEqual( [
			'fallback',
			'fallback',
		] );
		expect( sections[ 0 ].reasons ).toEqual( [ 'description' ] );
		expect( sections[ 1 ].reasons ).toEqual( [ 'actions' ] );
	} );

	it( 'compiles schema and registered visibility into DataForm fields', () => {
		const schema = createSchema();
		schema.groups.general.fields.push( {
			id: 'conditional',
			label: 'Conditional',
			type: 'text',
			visibility: { controller: 'enabled', value: true },
		} );
		registerSettingsExtension( {
			scope: context,
			fieldVisibility: {
				choice: ( { values } ) => values.country === 'GB',
			},
		} );

		const adapter = createDataFormAdapter( {
			schema,
			context,
			initialValues: {},
		} );
		const choice = adapter.fields.find(
			( field ) => field.id === 'choice'
		);
		const conditional = adapter.fields.find(
			( field ) => field.id === 'conditional'
		);

		expect( choice?.isVisible?.( { country: 'GB' } ) ).toBe( true );
		expect( choice?.isVisible?.( { country: 'US' } ) ).toBe( false );
		expect( conditional?.isVisible?.( { enabled: 'yes' } ) ).toBe( true );
		expect( conditional?.isVisible?.( { enabled: 'no' } ) ).toBe( false );
	} );

	it( 'bridges registered component props and partial updates', () => {
		let componentProps: SettingsFieldComponentProps | undefined;
		const CustomField = ( props: SettingsFieldComponentProps ) => {
			componentProps = props;
			return null;
		};
		registerSettingsExtension( {
			scope: context,
			components: { custom: CustomField },
		} );
		const schema = createSchema();
		schema.groups.general.fields = [
			{
				id: 'custom_field',
				label: 'Custom',
				type: 'custom',
				component: 'custom',
			},
		];
		const initialValues = { custom_field: 'initial', sibling: 'initial' };
		const adapter = createDataFormAdapter( {
			schema,
			context,
			initialValues,
		} );
		const field = normalizeForControl( adapter.fields[ 0 ] );
		const onChange = jest.fn();
		const Edit = field.Edit as (
			props: DataFormControlProps< SettingsValues >
		) => JSX.Element | null;
		const element = Edit( {
			data: { custom_field: 'current', sibling: 'current' },
			field,
			onChange,
		} );

		if ( element ) {
			( element.type as typeof CustomField )( element.props );
		}
		componentProps?.setValue( 'sibling', 'next' );
		componentProps?.setValues( {
			custom_field: 'changed',
			sibling: 'changed',
		} );

		expect( componentProps ).toMatchObject( {
			value: 'current',
			values: { custom_field: 'current', sibling: 'current' },
			initialValues,
			context,
		} );
		expect( onChange ).toHaveBeenNthCalledWith( 1, { sibling: 'next' } );
		expect( onChange ).toHaveBeenNthCalledWith( 2, {
			custom_field: 'changed',
			sibling: 'changed',
		} );
	} );

	it( 'maps named synchronous and asynchronous validators to isValid', async () => {
		const validator = jest.fn( async ( { value } ) =>
			value === 'taken' ? 'Already in use.' : null
		);
		registerSettingsExtension( {
			scope: context,
			validators: { unique: validator },
		} );
		const schema = createSchema();
		schema.groups.general.fields = [
			{
				id: 'slug',
				label: 'Slug',
				type: 'text',
				validation: { required: true, validator: 'unique' },
			},
		];
		const adapter = createDataFormAdapter( {
			schema,
			context,
			initialValues: { slug: '' },
		} );
		const field = normalizeForControl( adapter.fields[ 0 ] );

		expect( field.isValid.required ).toBe( true );
		await expect(
			field.isValid.custom?.( { slug: 'taken' }, field )
		).resolves.toBe( 'Already in use.' );
		expect( validator ).toHaveBeenCalledWith( {
			field: schema.groups.general.fields[ 0 ],
			value: 'taken',
			values: { slug: 'taken' },
			initialValues: { slug: '' },
			context,
			schema,
		} );
	} );

	it( 'provides an explicit Edit control for unsupported fields', () => {
		const warnSpy = jest
			.spyOn( console, 'warn' )
			.mockImplementation( () => undefined );
		const schema = createSchema();
		schema.groups.general.fields = [
			{ id: 'mystery', label: 'Mystery', type: 'unknown_type' },
		];
		const adapter = createDataFormAdapter( {
			schema,
			context,
			initialValues: {},
		} );
		const field = normalizeForControl( adapter.fields[ 0 ] );

		expect( field.Edit ).toEqual( expect.any( Function ) );
		expect( adapter.unsupportedFields ).toEqual( [
			schema.groups.general.fields[ 0 ],
		] );
		expect( warnSpy ).toHaveBeenCalledWith(
			expect.stringContaining( 'is not supported' ),
			expect.any( Object )
		);
		warnSpy.mockRestore();
	} );

	it( 'returns nested group validity for fallback DataForms', () => {
		const groupValidity = {
			field: { required: { type: 'invalid' as const } },
		};

		expect(
			getGroupValidity( { group: { children: groupValidity } }, 'group' )
		).toEqual( groupValidity );
	} );
} );
