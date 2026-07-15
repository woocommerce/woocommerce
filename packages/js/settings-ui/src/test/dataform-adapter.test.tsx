/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { act } from 'react';
import { createRoot } from 'react-dom/client';
import type { DataFormControlProps } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import {
	buildDataFormField,
	buildDataFormFields,
	buildDataFormFormConfig,
} from '../dataform-adapter';
import type { DataFormAdapterRuntime } from '../dataform-adapter';
import { __resetRegistry, registerSettingsExtension } from '../registry';
import type {
	SettingsUIField,
	SettingsUISchema,
	SettingsValues,
} from '../types';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const makeSchema = ( fields: SettingsUIField[] ): SettingsUISchema => ( {
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

const makeRuntime = (
	fields: SettingsUIField[],
	initialValues: SettingsValues = {}
): DataFormAdapterRuntime => ( {
	schema: makeSchema( fields ),
	context: { page: 'test-page', section: '' },
	initialValues,
	setValue: jest.fn(),
	setValues: jest.fn(),
} );

const renderControl = (
	Edit: React.ComponentType< DataFormControlProps< SettingsValues > >,
	props: Partial< DataFormControlProps< SettingsValues > > & {
		data: SettingsValues;
	}
) => {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	const root = createRoot( container );

	act( () => {
		root.render(
			<Edit { ...( props as DataFormControlProps< SettingsValues > ) } />
		);
	} );

	return {
		container,
		cleanup: () => {
			act( () => root.unmount() );
			container.remove();
		},
	};
};

describe( 'dataform-adapter', () => {
	afterEach( () => {
		__resetRegistry();
	} );

	describe( 'field type mapping', () => {
		it.each( [
			[ 'checkbox', 'boolean' ],
			[ 'number', 'number' ],
			[ 'text', 'text' ],
			[ 'tel', 'telephone' ],
			[ 'datetime-local', 'datetime' ],
			[ 'select', 'text' ],
			[ 'radio', 'text' ],
			[ 'array', 'array' ],
		] )( 'maps %s to DataForm type %s', ( settingsType, dataFormType ) => {
			const settingsField: SettingsUIField = {
				id: 'field',
				label: 'Field',
				type: settingsType,
			};
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ] )
			);

			expect( field.type ).toBe( dataFormType );
		} );

		it( 'builds elements from options with stringified values', () => {
			const settingsField: SettingsUIField = {
				id: 'field',
				label: 'Field',
				type: 'select',
				options: [
					// PHP-supplied schemas can carry non-string values at
					// runtime despite the declared types.
					{ value: 1 as unknown as string, label: 'One' },
					{ value: 'two', label: 'Two' },
				],
			};
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ] )
			);

			expect( field.elements ).toEqual( [
				{ value: '1', label: 'One' },
				{ value: 'two', label: 'Two' },
			] );
		} );
	} );

	describe( 'getValue', () => {
		it( 'normalizes checkbox vocabulary to booleans', () => {
			const settingsField: SettingsUIField = {
				id: 'flag',
				label: 'Flag',
				type: 'checkbox',
			};
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ] )
			);

			expect( field.getValue?.( { item: { flag: 'yes' } } ) ).toBe(
				true
			);
			expect( field.getValue?.( { item: { flag: '1' } } ) ).toBe( true );
			expect( field.getValue?.( { item: { flag: true } } ) ).toBe( true );
			expect( field.getValue?.( { item: { flag: 'no' } } ) ).toBe(
				false
			);
		} );

		it( 'stringifies select values and defaults arrays', () => {
			const select: SettingsUIField = {
				id: 'unit',
				label: 'Unit',
				type: 'select',
			};
			const array: SettingsUIField = {
				id: 'countries',
				label: 'Countries',
				type: 'array',
			};
			const runtime = makeRuntime( [ select, array ] );

			expect(
				buildDataFormField( select, runtime ).getValue?.( {
					item: { unit: 5 },
				} )
			).toBe( '5' );
			expect(
				buildDataFormField( array, runtime ).getValue?.( {
					item: { countries: '' },
				} )
			).toEqual( [] );
		} );
	} );

	describe( 'setValue', () => {
		it( 'restores original option value types for selects', () => {
			const settingsField: SettingsUIField = {
				id: 'unit',
				label: 'Unit',
				type: 'select',
				options: [
					{ value: 5 as unknown as string, label: 'Five' },
					{ value: 'ten', label: 'Ten' },
				],
			};
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ] )
			);

			expect( field.setValue?.( { item: {}, value: '5' } ) ).toEqual( {
				unit: 5,
			} );
			expect( field.setValue?.( { item: {}, value: 'ten' } ) ).toEqual( {
				unit: 'ten',
			} );
		} );

		it( 'preserves the initial checkbox value representation', () => {
			const settingsField: SettingsUIField = {
				id: 'flag',
				label: 'Flag',
				type: 'checkbox',
			};

			const yesNo = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ], { flag: 'no' } )
			);
			expect( yesNo.setValue?.( { item: {}, value: true } ) ).toEqual( {
				flag: 'yes',
			} );
			expect( yesNo.setValue?.( { item: {}, value: false } ) ).toEqual( {
				flag: 'no',
			} );

			const boolean = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ], { flag: false } )
			);
			expect( boolean.setValue?.( { item: {}, value: true } ) ).toEqual( {
				flag: true,
			} );

			const numericString = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ], { flag: '0' } )
			);
			expect(
				numericString.setValue?.( { item: {}, value: true } )
			).toEqual( { flag: '1' } );
		} );

		it( 'casts array values to string arrays', () => {
			const settingsField: SettingsUIField = {
				id: 'countries',
				label: 'Countries',
				type: 'array',
			};
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ] )
			);

			expect(
				field.setValue?.( { item: {}, value: [ 'GB', 5 ] } )
			).toEqual( { countries: [ 'GB', '5' ] } );
			expect(
				field.setValue?.( { item: {}, value: 'not-an-array' } )
			).toEqual( { countries: [] } );
		} );
	} );

	describe( 'isVisible', () => {
		it( 'evaluates declarative visibility rules against the item', () => {
			const settingsField: SettingsUIField = {
				id: 'dependent',
				label: 'Dependent',
				type: 'text',
				visibility: { controller: 'mode', value: 'advanced' },
			};
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ] )
			);

			expect( field.isVisible?.( { mode: 'advanced' } ) ).toBe( true );
			expect( field.isVisible?.( { mode: 'simple' } ) ).toBe( false );
		} );

		it( 'prefers registered visibility predicates over rules', () => {
			const settingsField: SettingsUIField = {
				id: 'dependent',
				label: 'Dependent',
				type: 'text',
				visibility: { controller: 'mode', value: 'advanced' },
			};
			registerSettingsExtension( {
				scope: { page: 'test-page', section: '' },
				fieldVisibility: {
					dependent: ( { values } ) => values.mode === 'simple',
				},
			} );
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ] )
			);

			expect( field.isVisible?.( { mode: 'simple' } ) ).toBe( true );
			expect( field.isVisible?.( { mode: 'advanced' } ) ).toBe( false );
		} );

		it( 'renders the field visible when a predicate throws', () => {
			const settingsField: SettingsUIField = {
				id: 'dependent',
				label: 'Dependent',
				type: 'text',
			};
			registerSettingsExtension( {
				scope: { page: 'test-page', section: '' },
				fieldVisibility: {
					dependent: () => {
						throw new Error( 'boom' );
					},
				},
			} );
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ] )
			);

			expect( field.isVisible?.( {} ) ).toBe( true );
		} );
	} );

	describe( 'package controls', () => {
		it( 'uses the built-in radio control for unregistered radio fields', () => {
			const settingsField: SettingsUIField = {
				id: 'choice',
				label: 'Choice',
				type: 'radio',
				description: '<strong>Pick</strong> one',
				options: [ { value: 'a', label: 'A' } ],
			};
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ] )
			);

			expect( field.Edit ).toBe( 'radio' );
			expect( field.description ).toBe( 'Pick one' );
		} );

		it( 'leaves array fields on the built-in array control', () => {
			const settingsField: SettingsUIField = {
				id: 'countries',
				label: 'Countries',
				type: 'array',
			};
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ] )
			);

			expect( field.Edit ).toBeUndefined();
			expect( field.type ).toBe( 'array' );
		} );

		it( 'bridges radio fields when an extension component is registered', () => {
			registerSettingsExtension( {
				scope: { page: 'test-page', section: '' },
				typeRenderers: {
					radio: () => <div>Custom radio</div>,
				},
			} );
			const settingsField: SettingsUIField = {
				id: 'choice',
				label: 'Choice',
				type: 'radio',
			};
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ] )
			);

			expect( typeof field.Edit ).toBe( 'function' );
		} );

		it( 'renders info fields as read-only blocks with sanitized HTML', () => {
			const settingsField: SettingsUIField = {
				id: 'notice',
				label: 'Heads up',
				type: 'info',
				description: '<em>Allowed</em><script>alert("x")</script>',
			};
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ] )
			);

			expect( field.readOnly ).toBe( true );
			expect( field.render ).toBeDefined();

			const Render = field.render as React.ComponentType<
				Record< string, unknown >
			>;
			const container = document.createElement( 'div' );
			document.body.appendChild( container );
			const root = createRoot( container );
			act( () => {
				root.render( <Render /> );
			} );

			expect(
				container.querySelector( '.wc-settings-ui__info' )
			).not.toBeNull();
			expect( container.querySelector( 'strong' )?.textContent ).toBe(
				'Heads up'
			);
			expect( container.querySelector( 'em' )?.textContent ).toBe(
				'Allowed'
			);
			expect( container.querySelector( 'script' ) ).toBeNull();

			act( () => root.unmount() );
			container.remove();
		} );
	} );

	describe( 'bridge Edit', () => {
		it( 'renders the native field and routes changes as partial edits', () => {
			const settingsField: SettingsUIField = {
				id: 'store_name',
				label: 'Store name',
				type: 'text',
			};
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ], { store_name: 'Initial' } )
			);
			const onChange = jest.fn();
			const Edit = field.Edit as React.ComponentType<
				DataFormControlProps< SettingsValues >
			>;

			const { container, cleanup } = renderControl( Edit, {
				data: { store_name: 'Initial' },
				onChange,
			} );

			const wrapper = container.querySelector(
				'.wc-settings-ui__field.wc-settings-ui__field--text'
			);
			expect( wrapper ).not.toBeNull();

			const input = container.querySelector( 'input' );
			expect( input ).not.toBeNull();
			expect( ( input as HTMLInputElement ).value ).toBe( 'Initial' );

			act( () => {
				const valueSetter = Object.getOwnPropertyDescriptor(
					HTMLInputElement.prototype,
					'value'
				)?.set;
				valueSetter?.call( input, 'Changed' );
				( input as HTMLInputElement ).dispatchEvent(
					new Event( 'input', { bubbles: true, cancelable: true } )
				);
			} );

			expect( onChange ).toHaveBeenCalledWith( {
				store_name: 'Changed',
			} );

			cleanup();
		} );

		it( 'prefers registered extension components', () => {
			const CustomField = jest.fn( () => <div>Custom field</div> );
			registerSettingsExtension( {
				scope: { page: 'test-page', section: '' },
				fieldOverrides: { store_name: CustomField },
			} );
			const settingsField: SettingsUIField = {
				id: 'store_name',
				label: 'Store name',
				type: 'text',
			};
			const initialValues = { store_name: 'Initial' };
			const field = buildDataFormField(
				settingsField,
				makeRuntime( [ settingsField ], initialValues )
			);
			const Edit = field.Edit as React.ComponentType<
				DataFormControlProps< SettingsValues >
			>;

			const { container, cleanup } = renderControl( Edit, {
				data: { store_name: 'Current' },
				onChange: jest.fn(),
			} );

			expect( container.textContent ).toContain( 'Custom field' );
			const props = CustomField.mock.calls[ 0 ][ 0 ] as unknown as Record<
				string,
				unknown
			>;
			expect( props.value ).toBe( 'Current' );
			expect( props.initialValues ).toEqual( initialValues );
			expect( props.context ).toEqual( {
				page: 'test-page',
				section: '',
			} );

			cleanup();
		} );
	} );

	describe( 'form config', () => {
		it( 'lists field ids and suppresses layout labels for info fields', () => {
			const fields: SettingsUIField[] = [
				{ id: 'first', label: 'First', type: 'text' },
				{ id: 'notice', label: 'Notice', type: 'info' },
			];
			const group = {
				id: 'general',
				fields,
			};

			expect( buildDataFormFormConfig( group ) ).toEqual( {
				layout: { type: 'regular', labelPosition: 'top' },
				fields: [
					'first',
					{
						id: 'notice',
						layout: { type: 'regular', labelPosition: 'none' },
					},
				],
			} );
		} );

		it( 'builds one DataForm field per settings field', () => {
			const fields: SettingsUIField[] = [
				{ id: 'first', label: 'First', type: 'text' },
				{ id: 'second', label: 'Second', type: 'checkbox' },
			];
			const group = { id: 'general', fields };
			const built = buildDataFormFields( group, makeRuntime( fields ) );

			expect( built.map( ( field ) => field.id ) ).toEqual( [
				'first',
				'second',
			] );
		} );
	} );
} );
