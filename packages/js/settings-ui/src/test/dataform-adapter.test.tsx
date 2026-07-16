/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
import { DataForm, useFormValidity } from '@wordpress/dataviews';
import { act } from 'react';
import { createRoot } from 'react-dom/client';

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

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

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
	initialValues: SettingsValues = {},
	extra: Partial< DataFormAdapterOptions > = {}
): DataFormAdapterOptions => ( {
	schema: makeSchema( groups ),
	context: { page: 'test-page', section: '' },
	initialValues,
	...extra,
} );

const fieldOptions = (
	field: SettingsUIField,
	initialValues: SettingsValues = {}
) => makeOptions( [ { id: 'general', fields: [ field ] } ], initialValues );

const render = ( element: JSX.Element ) => {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	const root = createRoot( container );

	act( () => {
		root.render( element );
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

	describe( 'package control mapping', () => {
		it.each( [
			[ 'checkbox', 'boolean', 'checkbox' ],
			[ 'radio', 'text', 'radio' ],
			[ 'select', 'text', 'select' ],
			[ 'number', 'number', 'number' ],
			[ 'tel', 'telephone', 'telephone' ],
			[ 'text', 'text', 'text' ],
			[ 'email', 'email', 'email' ],
		] )(
			'maps %s to package type %s and control %s',
			( settingsType, dataFormType, control ) => {
				const settingsField: SettingsUIField = {
					id: 'field',
					label: 'Field',
					type: settingsType,
				};
				const field = buildDataFormField(
					settingsField,
					fieldOptions( settingsField )
				);

				expect( field.type ).toBe( dataFormType );
				expect( field.Edit ).toBe( control );
			}
		);

		it( 'maps textarea to the textarea edit config', () => {
			const settingsField: SettingsUIField = {
				id: 'field',
				label: 'Field',
				type: 'textarea',
			};
			const field = buildDataFormField(
				settingsField,
				fieldOptions( settingsField )
			);

			expect( field.Edit ).toEqual( { control: 'textarea' } );
		} );

		it( 'leaves array fields on the package array control', () => {
			const settingsField: SettingsUIField = {
				id: 'field',
				label: 'Field',
				type: 'array',
			};
			const field = buildDataFormField(
				settingsField,
				fieldOptions( settingsField )
			);

			expect( field.type ).toBe( 'array' );
			expect( field.Edit ).toBeUndefined();
		} );

		it( 'renders fields with a disabled state through the native renderer', () => {
			const settingsField: SettingsUIField = {
				id: 'field',
				label: 'Field',
				type: 'text',
				disabled: true,
			};
			const field = buildDataFormField(
				settingsField,
				fieldOptions( settingsField )
			);

			expect( typeof field.Edit ).toBe( 'function' );
		} );

		it( 'renders fields with custom attributes through the native renderer', () => {
			const settingsField: SettingsUIField = {
				id: 'field',
				label: 'Field',
				type: 'number',
				customAttributes: { min: 0, max: 10, step: 1 },
			};
			const field = buildDataFormField(
				settingsField,
				fieldOptions( settingsField )
			);

			expect( typeof field.Edit ).toBe( 'function' );
		} );

		it( 'renders unknown field types through the native renderer with a warning', () => {
			const settingsField: SettingsUIField = {
				id: 'field',
				label: 'Field',
				type: 'image_width',
			};
			const field = buildDataFormField(
				settingsField,
				fieldOptions( settingsField )
			);

			expect( field.type ).toBe( 'text' );
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
				fieldOptions( settingsField )
			);

			expect( field.readOnly ).toBe( true );

			const Render = field.render as React.ComponentType<
				Record< string, unknown >
			>;
			const { container, cleanup } = render( <Render /> );

			expect(
				container.querySelector( '.wc-settings-ui__info' )
			).not.toBeNull();
			expect( container.querySelector( 'em' )?.textContent ).toBe(
				'Allowed'
			);
			expect( container.querySelector( 'script' ) ).toBeNull();

			cleanup();
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
				fieldOptions( settingsField )
			);

			expect( field.elements ).toEqual( [
				{ value: '1', label: 'One' },
				{ value: 'two', label: 'Two' },
			] );
		} );
	} );

	describe( 'value round-trips', () => {
		it( 'normalizes checkbox vocabulary to booleans on read', () => {
			const settingsField: SettingsUIField = {
				id: 'flag',
				label: 'Flag',
				type: 'checkbox',
			};
			const field = buildDataFormField(
				settingsField,
				fieldOptions( settingsField )
			);

			expect( field.getValue?.( { item: { flag: 'yes' } } ) ).toBe(
				true
			);
			expect( field.getValue?.( { item: { flag: '1' } } ) ).toBe( true );
			expect( field.getValue?.( { item: { flag: 'no' } } ) ).toBe(
				false
			);
		} );

		it( 'preserves the initial checkbox value representation on write', () => {
			const settingsField: SettingsUIField = {
				id: 'flag',
				label: 'Flag',
				type: 'checkbox',
			};

			const yesNo = buildDataFormField(
				settingsField,
				fieldOptions( settingsField, { flag: 'no' } )
			);
			expect( yesNo.setValue?.( { item: {}, value: true } ) ).toEqual( {
				flag: 'yes',
			} );

			const boolean = buildDataFormField(
				settingsField,
				fieldOptions( settingsField, { flag: false } )
			);
			expect( boolean.setValue?.( { item: {}, value: true } ) ).toEqual( {
				flag: true,
			} );

			const numericString = buildDataFormField(
				settingsField,
				fieldOptions( settingsField, { flag: '0' } )
			);
			expect(
				numericString.setValue?.( { item: {}, value: true } )
			).toEqual( { flag: '1' } );
		} );

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
				fieldOptions( settingsField )
			);

			expect( field.setValue?.( { item: {}, value: '5' } ) ).toEqual( {
				unit: 5,
			} );
			expect( field.setValue?.( { item: {}, value: 'ten' } ) ).toEqual( {
				unit: 'ten',
			} );
		} );

		it( 'casts array values to string arrays', () => {
			const settingsField: SettingsUIField = {
				id: 'countries',
				label: 'Countries',
				type: 'array',
			};
			const field = buildDataFormField(
				settingsField,
				fieldOptions( settingsField )
			);

			expect(
				field.setValue?.( { item: {}, value: [ 'GB', 5 ] } )
			).toEqual( { countries: [ 'GB', '5' ] } );
		} );
	} );

	describe( 'visibility', () => {
		it( 'evaluates declarative visibility rules against the item', () => {
			const settingsField: SettingsUIField = {
				id: 'dependent',
				label: 'Dependent',
				type: 'text',
				visibility: { controller: 'mode', value: 'advanced' },
			};
			const field = buildDataFormField(
				settingsField,
				fieldOptions( settingsField )
			);

			expect( field.isVisible?.( { mode: 'advanced' } ) ).toBe( true );
			expect( field.isVisible?.( { mode: 'simple' } ) ).toBe( false );
		} );

		it( 'matches boolean rule expectations against checkbox vocabulary', () => {
			const settingsField: SettingsUIField = {
				id: 'dependent',
				label: 'Dependent',
				type: 'text',
				visibility: { controller: 'flag', value: true },
			};
			const field = buildDataFormField(
				settingsField,
				fieldOptions( settingsField )
			);

			expect( field.isVisible?.( { flag: 'yes' } ) ).toBe( true );
			expect( field.isVisible?.( { flag: '1' } ) ).toBe( true );
			expect( field.isVisible?.( { flag: true } ) ).toBe( true );
			expect( field.isVisible?.( { flag: 'no' } ) ).toBe( false );
		} );

		it( 'prefers registered visibility predicates and survives predicate errors', () => {
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
				fieldOptions( settingsField )
			);

			expect( field.isVisible?.( { mode: 'simple' } ) ).toBe( true );
			expect( field.isVisible?.( { mode: 'advanced' } ) ).toBe( false );

			__resetRegistry();
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

	describe( 'registered components', () => {
		it( 'renders registered components with the DataForm control contract', () => {
			const received: Array< SettingsEditControlProps > = [];
			const CustomField = ( props: SettingsEditControlProps ) => {
				received.push( props );
				return <div>Custom field</div>;
			};
			registerSettingsExtension( {
				scope: { page: 'test-page', section: '' },
				fieldOverrides: { store_name: CustomField },
			} );
			const settingsField: SettingsUIField = {
				id: 'store_name',
				label: 'Store name',
				type: 'text',
			};
			const options = fieldOptions( settingsField, {
				store_name: 'Initial',
			} );
			const adapter = createDataFormAdapter( options );
			const onChange = jest.fn();

			const { container, cleanup } = render(
				<DataForm
					data={ { store_name: 'Current' } }
					fields={ adapter.fields }
					form={ { fields: [ 'store_name' ] } }
					onChange={ onChange }
				/>
			);

			expect( container.textContent ).toContain( 'Custom field' );
			expect(
				received[ 0 ].field.getValue( {
					item: { store_name: 'Current' },
				} )
			).toBe( 'Current' );

			act( () => {
				received[ 0 ].onChange( { store_name: 'Changed' } );
			} );
			expect( onChange ).toHaveBeenCalledWith( {
				store_name: 'Changed',
			} );

			cleanup();
		} );
	} );

	describe( 'render sections', () => {
		const plainGroup: SettingsUIGroup = {
			id: 'plain-a',
			title: 'Plain A',
			fields: [ { id: 'a', label: 'A', type: 'text' } ],
		};
		const plainGroupB: SettingsUIGroup = {
			id: 'plain-b',
			title: 'Plain B',
			fields: [ { id: 'b', label: 'B', type: 'text' } ],
		};
		const describedGroup: SettingsUIGroup = {
			id: 'described',
			title: 'Described',
			description: 'Rich <strong>text</strong>',
			fields: [ { id: 'c', label: 'C', type: 'text' } ],
		};

		it( 'batches consecutive plain groups into one DataForm section', () => {
			const adapter = createDataFormAdapter(
				makeOptions( [ plainGroup, plainGroupB ] )
			);
			const sections = adapter.getRenderSections( {} );

			expect( sections ).toHaveLength( 1 );
			expect( sections[ 0 ].type ).toBe( 'dataform' );
			expect( sections[ 0 ].form.fields ).toHaveLength( 2 );
		} );

		it( 'renders group descriptions through the package card, batching with plain groups', () => {
			const adapter = createDataFormAdapter(
				makeOptions( [ plainGroup, describedGroup, plainGroupB ] )
			);
			const sections = adapter.getRenderSections( {} );

			expect( sections ).toHaveLength( 1 );
			expect( sections[ 0 ].type ).toBe( 'dataform' );
			expect( sections[ 0 ].form.fields ).toHaveLength( 3 );

			const { container, cleanup } = render(
				<DataForm
					data={ {} }
					fields={ adapter.fields }
					form={ sections[ 0 ].form }
					onChange={ () => {} }
				/>
			);

			const description = container.querySelector(
				'.dataforms-layouts-card__field-description'
			);
			expect( description?.textContent ).toBe( 'Rich text' );
			expect( description?.querySelector( 'strong' ) ).not.toBeNull();

			cleanup();
		} );

		it( 'falls back to the shell card for groups with header actions, preserving order', () => {
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
				fields: [ { id: 'd', label: 'D', type: 'text' } ],
			};
			const adapter = createDataFormAdapter(
				makeOptions( [ plainGroup, actionsGroup, plainGroupB ] )
			);
			const sections = adapter.getRenderSections( {} );

			expect( sections.map( ( section ) => section.type ) ).toEqual( [
				'dataform',
				'fallback',
				'dataform',
			] );
			expect(
				sections[ 1 ].type === 'fallback' && sections[ 1 ].reasons
			).toEqual( [ 'actions' ] );
		} );

		it( 'drops groups whose fields are all hidden', () => {
			const hiddenGroup: SettingsUIGroup = {
				id: 'hidden',
				title: 'Hidden',
				fields: [
					{
						id: 'h',
						label: 'H',
						type: 'text',
						visibility: { controller: 'mode', value: 'never' },
					},
				],
			};
			const adapter = createDataFormAdapter(
				makeOptions( [ plainGroup, hiddenGroup ] )
			);
			const sections = adapter.getRenderSections( { mode: 'other' } );

			expect( sections ).toHaveLength( 1 );
			expect( sections[ 0 ].form.fields ).toHaveLength( 1 );
		} );
	} );

	describe( 'validation', () => {
		const requiredField: SettingsUIField = {
			id: 'name',
			label: 'Name',
			type: 'text',
		};

		it( 'excludes hidden fields from the validation form', () => {
			const groups: SettingsUIGroup[] = [
				{
					id: 'general',
					fields: [
						{ id: 'mode', label: 'Mode', type: 'text' },
						{
							id: 'dependent',
							label: 'Dependent',
							type: 'text',
							visibility: {
								controller: 'mode',
								value: 'advanced',
							},
						},
					],
				},
			];
			const adapter = createDataFormAdapter(
				makeOptions(
					groups,
					{},
					{
						fieldRules: { dependent: { required: true } },
					}
				)
			);

			const hiddenForm = adapter.getValidationForm( {
				mode: 'simple',
			} );
			expect( hiddenForm.fields ).toEqual( [
				{ id: 'general', children: [ 'mode' ] },
			] );

			const visibleForm = adapter.getValidationForm( {
				mode: 'advanced',
			} );
			expect( visibleForm.fields ).toEqual( [
				{ id: 'general', children: [ 'mode', 'dependent' ] },
			] );
		} );

		it( 'flows field rules into DataForm validity', async () => {
			const options = makeOptions(
				[ { id: 'general', fields: [ requiredField ] } ],
				{ name: '' },
				{ fieldRules: { name: { required: true } } }
			);
			const adapter = createDataFormAdapter( options );

			const results: boolean[] = [];
			const Harness = () => {
				const [ values ] = useState< SettingsValues >( { name: '' } );
				const { isValid } = useFormValidity(
					values,
					adapter.fields,
					adapter.getValidationForm( values )
				);
				results.push( isValid );
				return null;
			};

			const { cleanup } = render( <Harness /> );
			await act( async () => Promise.resolve() );

			expect( results[ results.length - 1 ] ).toBe( false );

			cleanup();
		} );
	} );
} );
