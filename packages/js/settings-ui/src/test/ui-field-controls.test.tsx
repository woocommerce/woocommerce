/* global Element, HTMLButtonElement, HTMLInputElement, HTMLTextAreaElement */

/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type { DataFormControlProps, FieldValidity } from '@wordpress/dataviews';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { SettingsUIPage } from '../settings-ui-page';
import { buildDataFormField } from '../dataform-adapter';
import { __resetRegistry, registerSettingsExtension } from '../registry';
import {
	createUIFieldEdit,
	flattenDataFormValidity,
} from '../ui-field-controls';
import type {
	SettingsEditControlProps,
	SettingsUIField,
	SettingsUISchema,
	SettingsValues,
} from '../types';
import { renderElement } from './helpers/render-element';

jest.mock( '@wordpress/admin-ui', () => ( {
	NavigableRegion: ( {
		children,
		className,
	}: {
		children: ReactNode;
		className?: string;
	} ) => <div className={ className }>{ children }</div>,
} ) );

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const makeSettingsField = (
	overrides: Partial< SettingsUIField > = {}
): SettingsUIField => ( {
	id: 'field',
	label: 'Field label',
	type: 'text',
	description: 'Helpful <strong>details</strong>.',
	...overrides,
} );

const makeControlProps = (
	settingsField: SettingsUIField,
	validity?: FieldValidity
): DataFormControlProps< SettingsValues > => ( {
	data: { [ settingsField.id ]: settingsField.value ?? '' },
	field: {
		id: settingsField.id,
		label: settingsField.label,
		header: settingsField.label,
		description: settingsField.description,
		placeholder: settingsField.placeholder,
		elements: settingsField.options,
		getValue: ( { item } ) => item[ settingsField.id ],
		setValue: ( { value } ) => ( { [ settingsField.id ]: value } ),
		render: () => null,
		Edit: null,
		hasElements: Boolean( settingsField.options?.length ),
		sort: () => 0,
		isValid: {},
		enableHiding: true,
		enableSorting: true,
		filterBy: false,
		readOnly: false,
	},
	onChange: jest.fn(),
	hideLabelFromVision: false,
	validity,
} );

const expectDescribedByTargetsExist = ( control: Element ) => {
	const describedBy = control.getAttribute( 'aria-describedby' ) || '';
	expect( describedBy ).not.toBe( '' );
	describedBy.split( /\s+/ ).forEach( ( id ) => {
		expect( document.getElementById( id ) ).not.toBeNull();
	} );
};

const renderControl = (
	control: Parameters< typeof createUIFieldEdit >[ 1 ],
	settingsField: SettingsUIField,
	validity?: FieldValidity
) => {
	const Edit = createUIFieldEdit( settingsField, control );
	return renderElement(
		<Edit { ...makeControlProps( settingsField, validity ) } />
	);
};

describe( 'catalogue UI field controls', () => {
	afterEach( () => {
		__resetRegistry();
	} );

	it( 'associates input errors and rich details with the actual input', () => {
		const { container, cleanup } = renderControl(
			'text',
			makeSettingsField(),
			{ custom: { type: 'invalid', message: 'Invalid value.' } }
		);
		const input = container.querySelector( 'input' );

		expect( input ).toHaveAttribute( 'aria-invalid', 'true' );
		expect( container.textContent ).toContain( 'Invalid value.' );
		expectDescribedByTargetsExist( input as HTMLInputElement );
		cleanup();
	} );

	it( 'associates textarea errors with the actual textarea', () => {
		const { container, cleanup } = renderControl(
			'textarea',
			makeSettingsField( { type: 'textarea' } ),
			{ required: { type: 'invalid' } }
		);
		const textarea = container.querySelector( 'textarea' );

		expect( textarea ).toHaveAttribute( 'aria-invalid', 'true' );
		expect( container.textContent ).toContain( 'This field is required.' );
		expectDescribedByTargetsExist( textarea as HTMLTextAreaElement );
		cleanup();
	} );

	it( 'labels and describes the actual select trigger', () => {
		const { container, cleanup } = renderControl(
			'select',
			makeSettingsField( {
				type: 'select',
				value: 'one',
				options: [
					{ value: 'one', label: 'One' },
					{ value: 'two', label: 'Two' },
				],
			} ),
			{ elements: { type: 'invalid', message: 'Choose an option.' } }
		);
		const trigger = container.querySelector( 'button' );
		const labelId = trigger?.getAttribute( 'aria-labelledby' );

		expect( trigger ).toHaveAttribute( 'aria-invalid', 'true' );
		expect( document.getElementById( labelId || '' )?.textContent ).toBe(
			'Field label'
		);
		expectDescribedByTargetsExist( trigger as HTMLButtonElement );
		cleanup();
	} );

	it( 'shows the label for an empty-string select option', () => {
		const { container, cleanup } = renderControl(
			'select',
			makeSettingsField( {
				type: 'select',
				value: '',
				options: [
					{ value: '', label: 'Always show quantity' },
					{ value: 'low', label: 'Only show when low' },
				],
			} )
		);

		expect( container.querySelector( 'button' ) ).toHaveTextContent(
			'Always show quantity'
		);
		cleanup();
	} );

	it( 'passes native disabled state to UI controls', () => {
		const { container, cleanup } = renderControl(
			'text',
			makeSettingsField( { disabled: true } )
		);

		expect( container.querySelector( 'input' ) ).toBeDisabled();
		cleanup();
	} );

	it( 'flattens rule validity with invalid ahead of validating', () => {
		expect(
			flattenDataFormValidity( {
				required: { type: 'validating' },
				custom: { type: 'invalid', message: 'Custom error.' },
			} )
		).toEqual( { state: 'invalid', message: 'Custom error.' } );
		expect(
			flattenDataFormValidity( {
				required: { type: 'invalid' },
				custom: { type: 'invalid', message: 'Custom error.' },
			} )
		).toEqual( { state: 'invalid', message: undefined } );
	} );

	it( 'adapts registered controls to the stable Woo contract', () => {
		const received: SettingsEditControlProps[] = [];
		const Registered = ( props: SettingsEditControlProps ) => {
			received.push( props );
			return <button disabled={ props.disabled }>Registered</button>;
		};
		registerSettingsExtension( {
			scope: { page: 'test-page', section: '' },
			fieldOverrides: { field: { component: Registered } },
		} );
		const schema: SettingsUISchema = {
			id: 'test-page',
			section: 'default',
			save: { adapter: 'none' },
			groups: {
				general: {
					id: 'general',
					fields: [
						makeSettingsField( { disabled: true, value: 'value' } ),
					],
				},
			},
		};
		const { container, cleanup } = renderElement(
			<SettingsUIPage schema={ schema } />
		);

		expect( container.querySelector( 'button' ) ).toBeDisabled();
		expect( Object.keys( received[ 0 ] ).sort() ).toEqual( [
			'data',
			'disabled',
			'field',
			'hideLabelFromVision',
			'onChange',
			'validity',
		] );
		expect( received[ 0 ].data ).toEqual( { field: 'value' } );
		expect( received[ 0 ].disabled ).toBe( true );
		expect( received[ 0 ].hideLabelFromVision ).toBe( false );
		expect( received[ 0 ].validity ).toBeUndefined();
		expect( Object.keys( received[ 0 ].field ).sort() ).toEqual( [
			'description',
			'elements',
			'getValue',
			'id',
			'label',
			'placeholder',
		] );
		cleanup();
	} );

	it.each( [
		[ false, undefined ],
		[ true, [ { id: 'manage', label: 'Manage', href: '#' } ] ],
	] )(
		'renders disabled package fields read-only in card=%s layouts',
		( _regular, actions ) => {
			const field = makeSettingsField( {
				id: 'count',
				label: 'Count',
				type: 'number',
				value: 2,
				disabled: true,
				description: undefined,
			} );
			const schema: SettingsUISchema = {
				id: 'test-page',
				save: { adapter: 'none' },
				groups: {
					general: { id: 'general', actions, fields: [ field ] },
				},
			};
			const { container, cleanup } = renderElement(
				<SettingsUIPage schema={ schema } />
			);

			expect(
				container.querySelector( 'input[type="number"]' )
			).toBeNull();
			expect( container.textContent ).toContain( '2.00' );
			cleanup();
		}
	);

	it( 'marks disabled registered fields read-only only through their contract', () => {
		const Custom = () => null;
		registerSettingsExtension( {
			scope: { page: 'test-page', section: '' },
			fieldOverrides: { field: { component: Custom } },
		} );
		const settingsField = makeSettingsField( { disabled: true } );
		const field = buildDataFormField( settingsField, {
			schema: {
				id: 'test-page',
				groups: {
					general: { id: 'general', fields: [ settingsField ] },
				},
			},
			context: { page: 'test-page', section: '' },
			initialValues: {},
		} );

		expect( field.Edit ).toEqual( expect.any( Function ) );
		expect( field.readOnly ).toBeUndefined();
	} );
} );
