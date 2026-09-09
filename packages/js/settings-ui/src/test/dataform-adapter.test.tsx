/**
 * External dependencies
 */
import { DataForm } from '@wordpress/dataviews';
import type { Field } from '@wordpress/dataviews';
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
	SettingsVisibilityPredicate,
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

// Unresolvable fields carry a control that throws when DataForm renders it.
const renderEditControl = ( field: Field< SettingsValues > ) =>
	( field.Edit as ( props: object ) => unknown )( {} );

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
		const typeExpectations: Array< [ string, string ] > = [
			[ 'text', 'text' ],
			[ 'password', 'password' ],
			[ 'datetime-local', 'datetime' ],
			[ 'date', 'date' ],
			[ 'time', 'text' ],
			[ 'info', 'text' ],
			[ 'email', 'email' ],
			[ 'url', 'url' ],
			[ 'tel', 'telephone' ],
			[ 'textarea', 'text' ],
			[ 'select', 'text' ],
			[ 'radio', 'text' ],
			[ 'checkbox', 'boolean' ],
			[ 'number', 'number' ],
			[ 'integer', 'integer' ],
			[ 'array', 'array' ],
		];

		it.each( typeExpectations )(
			'maps the "%s" settings type to DataForm type "%s"',
			( settingsType, dataFormType ) => {
				const field = buildDataFormField(
					{ ...textField, type: settingsType },
					createOptions( [ { ...textField, type: settingsType } ] )
				);

				expect( field.type ).toBe( dataFormType );
			}
		);

		// Naming a control DataForm already derives from the type would
		// restate its default, so only these types name one.
		it.each( [
			[ 'textarea', 'textarea' ],
			[ 'radio', 'radio' ],
			[ 'select', 'select' ],
			[ 'array', 'select' ],
		] )(
			'names the "%s" control for the "%s" type',
			( settingsType, editControl ) => {
				const field = buildDataFormField(
					{ ...textField, type: settingsType },
					createOptions( [] )
				);

				expect( field.Edit ).toBe( editControl );
			}
		);

		it.each( [
			'text',
			'password',
			'datetime-local',
			'date',
			'time',
			'email',
			'url',
			'tel',
			'checkbox',
			'number',
		] )( 'leaves the "%s" control to DataForm', ( settingsType ) => {
			const field = buildDataFormField(
				{ ...textField, type: settingsType },
				createOptions( [] )
			);

			expect( field.Edit ).toBeUndefined();
		} );

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

		it( 'renders sanitized info content without a Woo-owned control', () => {
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
				field: typeof field;
			} ) => JSX.Element;
			const { container } = renderElement(
				<Render item={ {} } field={ field } />
			);
			expect(
				container.querySelector( '.wc-settings-ui__info' )
			).toBeNull();
			// DataForm owns the label for a read-only field, so the render
			// contributes the description and nothing else.
			expect( container.textContent ).toBe( 'Useful information.' );
			expect( container.querySelector( 'strong' )?.textContent ).toBe(
				'information'
			);
			expect( container.querySelector( 'script' ) ).toBeNull();
		} );

		it.each( [
			'extension_defined',
			'constructor',
			'__proto__',
			'toString',
		] )(
			'fails closed for unknown type "%s" with no registered renderer',
			( type ) => {
				const field = buildDataFormField(
					{ ...textField, type },
					createOptions( [] )
				);

				expect( () => renderEditControl( field ) ).toThrow(
					`Field type "${ type }" is not supported.`
				);
			}
		);

		it( 'fails closed for an unknown type that carries options', () => {
			// Options alone resolve DataForm's adaptiveSelect control, so an
			// unresolved type has to fail before that fallback applies.
			const field = buildDataFormField(
				{
					...textField,
					type: 'extension_defined',
					options: [ { label: 'One', value: 'one' } ],
				},
				createOptions( [] )
			);

			expect( () => renderEditControl( field ) ).toThrow(
				'Field type "extension_defined" is not supported.'
			);
		} );
	} );

	describe( 'descriptions and components', () => {
		it( 'maps field descriptions to sanitized help elements', () => {
			const field = buildDataFormField(
				{
					...textField,
					description:
						'A <a href="https://example.com">link</a><script>alert("x")</script>.',
				},
				createOptions( [] )
			);

			const { container } = renderElement( <>{ field.description }</> );
			expect( container.querySelector( 'a' )?.textContent ).toBe(
				'link'
			);
			expect( container.querySelector( 'script' ) ).toBeNull();
			expect( container.textContent ).toBe( 'A link.' );
		} );

		it( 'strips group descriptions to plain text', () => {
			const schema: SettingsUISchema = {
				id: 'test-page',
				groups: {
					general: {
						id: 'general',
						title: 'General',
						description: 'Configure <strong>the basics</strong>.',
						fields: [ textField ],
					},
				},
			};
			const adapter = createDataFormAdapter( {
				schema,
				context,
				initialValues: {},
			} );

			const [ group ] = adapter.getForm( {} ).fields as Array< {
				description?: string;
			} >;
			expect( group.description ).toBe( 'Configure the basics.' );
		} );

		it( 'attaches a registered control as the field edit component', () => {
			const Registered = () => <div>Registered control</div>;
			registerSettingsExtension( {
				scope: { page: 'test-page' },
				components: { 'test/custom-field': Registered },
			} );

			const field = buildDataFormField(
				{ ...textField, component: 'test/custom-field' },
				createOptions( [] )
			);

			expect( field.Edit ).toBe( Registered );
		} );

		it( 'resolves an unknown type through a registered type renderer', () => {
			const Registered = () => <div>Extension control</div>;
			registerSettingsExtension( {
				scope: { page: 'test-page' },
				typeRenderers: { extension_defined: Registered },
			} );

			const field = buildDataFormField(
				{
					...textField,
					type: 'extension_defined',
					options: [ { label: 'One', value: 'one' } ],
				},
				createOptions( [] )
			);

			expect( field.Edit ).toBe( Registered );
			// Extension controls keep their options; only genuinely
			// unresolvable types fail.
			expect( field.elements ).toEqual( [
				{ label: 'One', value: 'one' },
			] );
		} );

		it( 'fails closed when a declared component is not registered', () => {
			jest.spyOn( console, 'warn' ).mockImplementation( () => undefined );

			const field = buildDataFormField(
				{ ...textField, component: 'test/missing-component' },
				createOptions( [] )
			);

			expect( () => renderEditControl( field ) ).toThrow(
				'Component "test/missing-component" is not registered.'
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

		it( 'compares array controller values by element equality', () => {
			const field = buildDataFormField(
				{
					...textField,
					visibility: {
						controller: 'regions',
						value: [ [ 'eu', 'us' ] ],
					},
				},
				createOptions( [] )
			);

			expect( field.isVisible?.( { regions: [ 'eu', 'us' ] } ) ).toBe(
				true
			);
			expect( field.isVisible?.( { regions: [ 'eu' ] } ) ).toBe( false );
			expect( field.isVisible?.( { regions: [ 'us', 'eu' ] } ) ).toBe(
				false
			);
		} );

		it( 'keeps disabled and visibility independent on the same field', () => {
			const field = buildDataFormField(
				{
					...textField,
					disabled: true,
					visibility: { controller: 'toggle', value: 'on' },
				},
				createOptions( [] )
			);

			expect( field.isDisabled ).toBe( true );
			expect( field.isVisible?.( { toggle: 'off' } ) ).toBe( false );
			expect( field.isVisible?.( { toggle: 'on' } ) ).toBe( true );
		} );

		it( 'coerces loose predicate results to strict booleans', () => {
			registerSettingsExtension( {
				scope: { page: 'test-page' },
				fieldVisibility: {
					// Third-party predicates are not held to the typed
					// boolean return.
					test_field: ( ( { values }: { values: SettingsValues } ) =>
						values.other === 'show' ||
						undefined ) as SettingsVisibilityPredicate,
				},
			} );
			const field = buildDataFormField(
				textField,
				createOptions( [ textField ] )
			);

			expect( field.isVisible?.( { other: 'show' } ) ).toBe( true );
			expect( field.isVisible?.( { other: 'hide' } ) ).toBe( false );
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

		it( 'drops a group whose only field is hidden by a loose predicate', () => {
			registerSettingsExtension( {
				scope: { page: 'test-page' },
				fieldVisibility: {
					test_field: ( () =>
						undefined ) as unknown as SettingsVisibilityPredicate,
				},
			} );
			const adapter = createDataFormAdapter(
				createOptions( [ textField ] )
			);

			// DataForm's layout hides the field, so keeping the group would
			// leave an empty titled card behind.
			expect( adapter.getForm( {} ).fields ).toEqual( [] );
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

	describe( 'validation rules', () => {
		it( 'leaves closed elements validation to DataForm', () => {
			const field = buildDataFormField(
				{
					...textField,
					type: 'select',
					options: [
						{ label: 'One', value: 'one' },
						{ label: 'Two', value: 'two' },
					],
				},
				createOptions( [] )
			);

			expect( field.isValid?.elements ).toBeUndefined();
		} );

		it( 'maps number range attributes to numeric constraints', () => {
			const field = buildDataFormField(
				{
					...textField,
					type: 'number',
					customAttributes: { min: '0', max: 100 },
				},
				createOptions( [] )
			);

			expect( field.isValid?.min ).toBe( 0 );
			expect( field.isValid?.max ).toBe( 100 );
		} );

		it( 'maps integer range attributes to numeric constraints', () => {
			const field = buildDataFormField(
				{
					...textField,
					type: 'integer',
					customAttributes: { min: '0', max: 100 },
				},
				createOptions( [] )
			);

			expect( field.isValid?.min ).toBe( 0 );
			expect( field.isValid?.max ).toBe( 100 );
		} );

		it( 'maps canonical numeric validation to range constraints', () => {
			const field = buildDataFormField(
				{
					...textField,
					type: 'integer',
					validation: { min: 1, max: 9 },
				},
				createOptions( [] )
			);

			expect( field.isValid?.min ).toBe( 1 );
			expect( field.isValid?.max ).toBe( 9 );
		} );

		it( 'maps date range attributes as strings', () => {
			const field = buildDataFormField(
				{
					...textField,
					type: 'date',
					customAttributes: { min: '2026-01-01', max: '2026-12-31' },
				},
				createOptions( [] )
			);

			expect( field.isValid?.min ).toBe( '2026-01-01' );
			expect( field.isValid?.max ).toBe( '2026-12-31' );
		} );

		it( 'ignores range attributes on types without a range rule', () => {
			const field = buildDataFormField(
				{ ...textField, customAttributes: { min: '5', max: '10' } },
				createOptions( [] )
			);

			expect( field.isValid?.min ).toBeUndefined();
			expect( field.isValid?.max ).toBeUndefined();
		} );

		it( 'maps length and pattern attributes', () => {
			const field = buildDataFormField(
				{
					...textField,
					customAttributes: {
						minlength: 2,
						maxlength: '10',
						pattern: '[a-z]+',
					},
				},
				createOptions( [] )
			);

			expect( field.isValid?.minLength ).toBe( 2 );
			expect( field.isValid?.maxLength ).toBe( 10 );
			expect( field.isValid?.pattern ).toBe( '[a-z]+' );
		} );

		it( 'honours a required attribute with presence semantics', () => {
			const present = buildDataFormField(
				{ ...textField, customAttributes: { required: 'required' } },
				createOptions( [] )
			);
			expect( present.isValid?.required ).toBe( true );

			const booleanFalse = buildDataFormField(
				{ ...textField, customAttributes: { required: false } },
				createOptions( [] )
			);
			expect( booleanFalse.isValid?.required ).toBeUndefined();

			const absent = buildDataFormField( textField, createOptions( [] ) );
			expect( absent.isValid?.required ).toBeUndefined();
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

		const brokenHiddenField: SettingsUIField = {
			id: 'hidden_field',
			label: 'Hidden field',
			type: 'text',
			component: 'test/missing-component',
			visibility: { controller: 'toggle', value: 'on' },
		};
		const toggleField: SettingsUIField = {
			id: 'toggle',
			label: 'Toggle',
			type: 'text',
		};

		it( 'keeps a hidden field with an unregistered component off the page', () => {
			const options = createOptions( [ toggleField, brokenHiddenField ] );
			const adapter = createDataFormAdapter( options );
			const data = { toggle: 'off', hidden_field: '' };

			const { container } = renderElement(
				<DataForm
					data={ data }
					fields={ adapter.fields }
					form={ adapter.getForm( data ) }
					onChange={ () => undefined }
				/>
			);

			expect( container.querySelectorAll( 'input' ) ).toHaveLength( 1 );
		} );

		it( 'fails closed once a field with an unregistered component is visible', () => {
			jest.spyOn( console, 'error' ).mockImplementation(
				() => undefined
			);
			const options = createOptions( [ toggleField, brokenHiddenField ] );
			const adapter = createDataFormAdapter( options );
			const data = { toggle: 'on', hidden_field: '' };
			const container = document.createElement( 'div' );
			document.body.appendChild( container );
			const root = createRoot( container );

			try {
				expect( () =>
					act( () => {
						root.render(
							<DataForm
								data={ data }
								fields={ adapter.fields }
								form={ adapter.getForm( data ) }
								onChange={ () => undefined }
							/>
						);
					} )
				).toThrow(
					'Component "test/missing-component" is not registered.'
				);
			} finally {
				act( () => root.unmount() );
				container.remove();
			}
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

		it.each( [
			[ 'text', 'text' ],
			[ 'password', 'password' ],
			[ 'email', 'email' ],
			[ 'url', 'url' ],
			[ 'tel', 'tel' ],
			[ 'number', 'number' ],
			[ 'checkbox', 'checkbox' ],
		] )(
			'lets DataForm resolve the "%s" type to input[type=%s]',
			( settingsType, inputType ) => {
				const field: SettingsUIField = {
					id: 'probe_field',
					label: 'Probe field',
					type: settingsType,
				};
				const options = createOptions( [ field ] );
				const adapter = createDataFormAdapter( options );
				const data = { probe_field: '' };

				const { container } = renderElement(
					<DataForm
						data={ data }
						fields={ adapter.fields }
						form={ adapter.getForm( data ) }
						onChange={ () => undefined }
					/>
				);

				expect( container.querySelector( 'input' )?.type ).toBe(
					inputType
				);
			}
		);

		it( 'never degrades a closed list to free text when options are empty', () => {
			// get_options() returns an empty list when its source is
			// unavailable. DataForm infers a select only from a non-empty
			// list, so leaving the control unnamed would fall back to a text
			// input and accept any value for a closed choice.
			const emptySelect: SettingsUIField = {
				id: 'country',
				label: 'Country',
				type: 'select',
				options: [],
			};
			const options = createOptions( [ emptySelect ] );
			const adapter = createDataFormAdapter( options );
			const data = { country: '' };

			const { container } = renderElement(
				<DataForm
					data={ data }
					fields={ adapter.fields }
					form={ adapter.getForm( data ) }
					onChange={ () => undefined }
				/>
			);

			expect( container.querySelector( 'input' ) ).toBeNull();
			expect( container.querySelector( 'textarea' ) ).toBeNull();
		} );

		it( 'renders a registered type renderer instead of the options fallback', () => {
			const Registered = () => <div>Extension control</div>;
			registerSettingsExtension( {
				scope: { page: 'test-page' },
				typeRenderers: { extension_defined: Registered },
			} );
			const extensionField: SettingsUIField = {
				id: 'extension_field',
				label: 'Extension field',
				type: 'extension_defined',
				options: [ { label: 'One', value: 'one' } ],
			};
			const options = createOptions( [ extensionField ] );
			const adapter = createDataFormAdapter( options );
			const data = { extension_field: 'one' };

			const { container } = renderElement(
				<DataForm
					data={ data }
					fields={ adapter.fields }
					form={ adapter.getForm( data ) }
					onChange={ () => undefined }
				/>
			);

			expect( container.textContent ).toContain( 'Extension control' );
			expect( container.querySelector( 'select' ) ).toBeNull();
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
			expect( container.querySelector( 'input' ) ).toBeNull();
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
