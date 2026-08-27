/**
 * External dependencies
 */
import { DataForm } from '@wordpress/dataviews';
import { act } from 'react';
import { createElement } from '@wordpress/element';
import { createRoot } from 'react-dom/client';

/**
 * Internal dependencies
 */
import { buildDataFormField, createDataFormAdapter } from '../dataform-adapter';
import type { DataFormAdapterOptions } from '../dataform-adapter';
import { __resetRegistry, registerSettingsExtension } from '../registry';
import type {
	SettingsUIField,
	SettingsUISchema,
	SettingsValues,
} from '../types';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const context = { page: 'test-page', section: 'default' };

const createSchema = ( fields: SettingsUIField[] ): SettingsUISchema => ( {
	id: 'test-page',
	title: 'Test page',
	section: 'default',
	save: { adapter: 'none' },
	groups: {
		general: {
			id: 'general',
			title: 'General',
			fields,
		},
	},
} );

const createOptions = (
	fields: SettingsUIField[],
	initialValues: SettingsValues = {}
): DataFormAdapterOptions => ( {
	schema: createSchema( fields ),
	context,
	initialValues,
} );

const textField: SettingsUIField = {
	id: 'test_field',
	label: 'Test field',
	type: 'text',
};

const mountedRoots: Array< () => void > = [];

const renderElement = ( element: JSX.Element ) => {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	const root = createRoot( container );
	act( () => {
		root.render( element );
	} );
	mountedRoots.push( () => {
		act( () => root.unmount() );
		container.remove();
	} );
	return { container };
};

describe( 'dataform adapter', () => {
	afterEach( () => {
		while ( mountedRoots.length > 0 ) {
			mountedRoots.pop()?.();
		}
		__resetRegistry();
		jest.restoreAllMocks();
	} );

	describe( 'field type mapping', () => {
		const typeExpectations: Array< [ string, string, unknown ] > = [
			[ 'text', 'text', 'text' ],
			[ 'password', 'password', 'password' ],
			[ 'datetime-local', 'datetime', 'datetime' ],
			[ 'date', 'date', 'date' ],
			[ 'time', 'text', 'text' ],
			[ 'email', 'email', 'email' ],
			[ 'url', 'url', 'url' ],
			[ 'tel', 'telephone', 'telephone' ],
			[ 'textarea', 'text', 'textarea' ],
			[ 'select', 'text', 'select' ],
			[ 'radio', 'text', 'radio' ],
			[ 'checkbox', 'boolean', 'checkbox' ],
			[ 'number', 'number', 'number' ],
			[ 'array', 'array', 'select' ],
		];

		it.each( typeExpectations )(
			'maps the "%s" settings type to DataForm type "%s" with the "%s" edit control',
			( settingsType, dataFormType, editControl ) => {
				const field = buildDataFormField(
					{ ...textField, type: settingsType },
					createOptions( [ { ...textField, type: settingsType } ] )
				);

				expect( field.type ).toBe( dataFormType );
				expect( field.Edit ).toBe( editControl );
			}
		);

		it( 'passes options through as elements', () => {
			const options = [
				{ label: 'One', value: 'one' },
				{ label: 'Two', value: 'two' },
			];
			const field = buildDataFormField(
				{ ...textField, type: 'select', options },
				createOptions( [] )
			);

			expect( field.elements ).toEqual( options );
		} );

		it( 'renders info fields read-only with only the sanitized description', () => {
			const infoField: SettingsUIField = {
				id: 'info_field',
				label: 'Read this',
				type: 'info',
				description:
					'Useful <strong>information</strong>.<script>alert(1)</script>',
			};
			const field = buildDataFormField(
				infoField,
				createOptions( [ infoField ] )
			);

			expect( field.readOnly ).toBe( true );
			expect( field.Edit ).toBeUndefined();

			const Render = field.render as ( props: {
				item: SettingsValues;
			} ) => JSX.Element;
			const { container } = renderElement( <Render item={ {} } /> );
			expect(
				container.querySelector( '.wc-settings-ui__info' )
			).not.toBeNull();
			expect( container.textContent ).toContain( 'Useful information.' );
			expect( container.querySelector( 'script' ) ).toBeNull();
			// DataForm owns the label for read-only fields.
			expect( container.textContent ).not.toContain( 'Read this' );
		} );

		it( 'maps field descriptions to sanitized help elements', () => {
			const field = buildDataFormField(
				{
					...textField,
					description:
						'See the <a href="https://woocommerce.com">docs</a>.',
				},
				createOptions( [] )
			);

			const { container } = renderElement( <>{ field.description }</> );
			const link = container.querySelector( 'a' );
			expect( link?.textContent ).toBe( 'docs' );
			expect( container.textContent ).toBe( 'See the docs.' );
		} );

		it( 'warns and maps an unknown type to a read-only field rendering nothing', () => {
			const warnSpy = jest
				.spyOn( console, 'warn' )
				.mockImplementation( () => undefined );
			const field = buildDataFormField(
				{ ...textField, type: 'extension_defined' },
				createOptions( [] )
			);

			expect( field.readOnly ).toBe( true );
			expect( field.Edit ).toBeUndefined();
			expect( ( field.render as () => null )() ).toBeNull();
			expect( warnSpy ).toHaveBeenCalledWith(
				expect.stringContaining(
					'Field type "extension_defined" is not supported.'
				),
				expect.any( Object )
			);
		} );
	} );

	describe( 'visibility', () => {
		it( 'maps a visibility rule to isVisible with single and list values', () => {
			const single = buildDataFormField(
				{
					...textField,
					visibility: { controller: 'toggle', value: 'on' },
				},
				createOptions( [] )
			);
			expect( single.isVisible?.( { toggle: 'on' } ) ).toBe( true );
			expect( single.isVisible?.( { toggle: 'off' } ) ).toBe( false );

			const list = buildDataFormField(
				{
					...textField,
					visibility: { controller: 'toggle', value: [ 'a', 'b' ] },
				},
				createOptions( [] )
			);
			expect( list.isVisible?.( { toggle: 'b' } ) ).toBe( true );
			expect( list.isVisible?.( { toggle: 'c' } ) ).toBe( false );

			const defaulted = buildDataFormField(
				{ ...textField, visibility: { controller: 'toggle' } },
				createOptions( [] )
			);
			expect( defaulted.isVisible?.( { toggle: true } ) ).toBe( true );
			expect( defaulted.isVisible?.( { toggle: false } ) ).toBe( false );
		} );

		it( 'prefers a registered predicate over the schema rule', () => {
			registerSettingsExtension( {
				scope: { page: 'test-page' },
				fieldVisibility: {
					test_field: ( { values } ) => values.other === 'show',
				},
			} );
			const field = buildDataFormField(
				{
					...textField,
					visibility: { controller: 'toggle', value: 'on' },
				},
				createOptions( [] )
			);

			expect(
				field.isVisible?.( { toggle: 'off', other: 'show' } )
			).toBe( true );
			expect( field.isVisible?.( { toggle: 'on', other: 'hide' } ) ).toBe(
				false
			);
		} );

		it( 'fails open and logs an error when a predicate throws', () => {
			const errorSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => undefined );
			registerSettingsExtension( {
				scope: { page: 'test-page' },
				fieldVisibility: {
					test_field: () => {
						throw new Error( 'broken predicate' );
					},
				},
			} );
			const field = buildDataFormField(
				textField,
				createOptions( [ textField ] )
			);

			expect( field.isVisible?.( {} ) ).toBe( true );
			expect( errorSpy ).toHaveBeenCalledWith(
				expect.stringContaining(
					'Visibility predicate for field "test_field" failed.'
				),
				expect.any( Object )
			);
		} );

		it( 'applies a predicate registered after the field is built', () => {
			const field = buildDataFormField(
				textField,
				createOptions( [ textField ] )
			);
			expect( field.isVisible?.( { other: 'hide' } ) ).toBe( true );

			registerSettingsExtension( {
				scope: { page: 'test-page' },
				fieldVisibility: {
					test_field: ( { values } ) => values.other === 'show',
				},
			} );

			expect( field.isVisible?.( { other: 'hide' } ) ).toBe( false );
			expect( field.isVisible?.( { other: 'show' } ) ).toBe( true );
		} );
	} );

	describe( 'form configuration', () => {
		it( 'maps groups to combined card fields', () => {
			const schema: SettingsUISchema = {
				id: 'test-page',
				groups: {
					titled: {
						id: 'titled',
						title: 'Titled group',
						fields: [ textField ],
					},
					untitled: {
						id: 'untitled',
						fields: [
							{ id: 'other_field', label: 'Other', type: 'text' },
						],
					},
				},
			};
			const adapter = createDataFormAdapter( {
				schema,
				context,
				initialValues: {},
			} );
			const form = adapter.getForm( {} );

			expect( form.fields ).toEqual( [
				{
					id: 'titled',
					label: 'Titled group',
					layout: { type: 'card', isCollapsible: false },
					children: [ 'test_field' ],
				},
				{
					id: 'untitled',
					label: undefined,
					layout: { type: 'card', withHeader: false },
					children: [ 'other_field' ],
				},
			] );
		} );

		it( 'drops a group when its predicate hides it or all fields are hidden', () => {
			registerSettingsExtension( {
				scope: { page: 'test-page' },
				groupVisibility: {
					by_predicate: ( { values } ) => values.show === 'yes',
				},
			} );
			const schema: SettingsUISchema = {
				id: 'test-page',
				groups: {
					by_predicate: {
						id: 'by_predicate',
						fields: [ textField ],
					},
					by_fields: {
						id: 'by_fields',
						fields: [
							{
								id: 'hidden_field',
								label: 'Hidden',
								type: 'text',
								visibility: {
									controller: 'toggle',
									value: 'on',
								},
							},
						],
					},
				},
			};
			const adapter = createDataFormAdapter( {
				schema,
				context,
				initialValues: {},
			} );

			const visible = adapter.getForm( { show: 'yes', toggle: 'on' } );
			expect(
				visible.fields?.map( ( f ) => ( f as { id: string } ).id )
			).toEqual( [ 'by_predicate', 'by_fields' ] );

			const hidden = adapter.getForm( { show: 'no', toggle: 'off' } );
			expect( hidden.fields ).toEqual( [] );
		} );

		it( 'fails open and logs an error when a group predicate throws', () => {
			const errorSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => undefined );
			const adapter = createDataFormAdapter( {
				schema: createSchema( [ textField ] ),
				context,
				initialValues: {},
			} );
			registerSettingsExtension( {
				scope: { page: 'test-page' },
				groupVisibility: {
					general: () => {
						throw new Error( 'broken predicate' );
					},
				},
			} );

			const form = adapter.getForm( {} );
			expect(
				form.fields?.map( ( f ) => ( f as { id: string } ).id )
			).toEqual( [ 'general' ] );
			expect( errorSpy ).toHaveBeenCalledWith(
				expect.stringContaining(
					'Visibility predicate for group "general" failed.'
				),
				expect.any( Object )
			);
		} );
	} );

	describe( 'disabled state', () => {
		it( 'honours a disabled custom attribute with presence semantics', () => {
			const attributeDisabled = buildDataFormField(
				{ ...textField, customAttributes: { disabled: 'disabled' } },
				createOptions( [] )
			);
			expect( attributeDisabled.isDisabled ).toBe( true );

			const attributeFalse = buildDataFormField(
				{ ...textField, customAttributes: { disabled: false } },
				createOptions( [] )
			);
			expect( attributeFalse.isDisabled ).toBe( false );

			const noAttribute = buildDataFormField(
				textField,
				createOptions( [] )
			);
			expect( noAttribute.isDisabled ).toBe( false );

			const fieldDisabled = buildDataFormField(
				{ ...textField, disabled: true },
				createOptions( [] )
			);
			expect( fieldDisabled.isDisabled ).toBe( true );
		} );
	} );

	describe( 'mounted DataForm behaviour', () => {
		it( 'honours isDisabled on package controls', () => {
			const enabledField: SettingsUIField = {
				id: 'enabled_field',
				label: 'Enabled field',
				type: 'text',
			};
			const disabledField: SettingsUIField = {
				id: 'disabled_field',
				label: 'Disabled field',
				type: 'text',
				disabled: true,
			};
			const options = createOptions( [ enabledField, disabledField ] );
			const adapter = createDataFormAdapter( options );
			const data = { enabled_field: 'a', disabled_field: 'b' };

			const { container } = renderElement(
				<DataForm
					data={ data }
					fields={ adapter.fields }
					form={ adapter.getForm( data ) }
					onChange={ () => undefined }
				/>
			);

			const inputs = Array.from( container.querySelectorAll( 'input' ) );
			expect( inputs.length ).toBe( 2 );
			expect(
				inputs.some(
					( input ) => input.value === 'a' && ! input.disabled
				)
			).toBe( true );
			expect(
				inputs.some(
					( input ) => input.value === 'b' && input.disabled
				)
			).toBe( true );
		} );

		it( 'shows an info field title exactly once', () => {
			const infoField: SettingsUIField = {
				id: 'info_field',
				label: 'Read this',
				type: 'info',
				description: 'Useful <strong>information</strong>.',
			};
			const options = createOptions( [ infoField ] );
			const adapter = createDataFormAdapter( options );
			const data = {};

			const { container } = renderElement(
				<DataForm
					data={ data }
					fields={ adapter.fields }
					form={ adapter.getForm( data ) }
					onChange={ () => undefined }
				/>
			);

			expect( container.textContent?.match( /Read this/g ) ).toHaveLength(
				1
			);
			expect( container.textContent ).toContain( 'Useful information.' );
		} );

		it( 'renders array fields as a closed multi-select', () => {
			const arrayField: SettingsUIField = {
				id: 'countries',
				label: 'Countries',
				type: 'array',
				options: [
					{ label: 'France', value: 'FR' },
					{ label: 'Spain', value: 'ES' },
				],
			};
			const options = createOptions( [ arrayField ] );
			const adapter = createDataFormAdapter( options );
			const data = { countries: [ 'FR' ] };

			const { container } = renderElement(
				<DataForm
					data={ data }
					fields={ adapter.fields }
					form={ adapter.getForm( data ) }
					onChange={ () => undefined }
				/>
			);

			const select = container.querySelector( 'select' );
			expect( select?.multiple ).toBe( true );
			expect(
				Array.from( select?.options ?? [] ).map( ( o ) => o.value )
			).toEqual( [ 'FR', 'ES' ] );
			// A closed control offers no free-text input.
			expect(
				container.querySelector( 'input[type="text"]' )
			).toBeNull();
		} );

		it( 'surfaces grouped validity through FieldValidity children', () => {
			const options = createOptions( [ textField ] );
			const adapter = createDataFormAdapter( options );
			const data = { test_field: 'value' };

			const { container } = renderElement(
				<DataForm
					data={ data }
					fields={ adapter.fields }
					form={ adapter.getForm( data ) }
					onChange={ () => undefined }
					validity={ {
						general: {
							children: {
								test_field: {
									custom: {
										type: 'invalid',
										message: 'This value is not allowed.',
									},
								},
							},
						},
					} }
				/>
			);

			// The controls defer validation display until they are touched.
			expect( container.textContent ).not.toContain(
				'This value is not allowed.'
			);

			const input = container.querySelector( 'input' );
			act( () => {
				input?.focus();
				input?.blur();
			} );

			expect( container.textContent ).toContain(
				'This value is not allowed.'
			);
		} );
	} );
} );
