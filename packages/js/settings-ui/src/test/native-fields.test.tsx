/* global HTMLButtonElement, HTMLInputElement, HTMLSelectElement, HTMLTextAreaElement, MouseEvent */

/**
 * External dependencies
 */
import { speak } from '@wordpress/a11y';
import { createElement } from '@wordpress/element';
import { act } from 'react';

/**
 * Internal dependencies
 */
import { __resetWarnings } from '../diagnostics';
import { NativeSettingsField } from '../native-fields';
import type {
	SettingsFieldComponentProps,
	SettingsUIField,
	SettingsValue,
} from '../types';
import { renderElement } from './helpers/render-element';

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const makeProps = (
	field: SettingsUIField,
	value: SettingsValue,
	onChange: ( next: SettingsValue ) => void = () => undefined
): SettingsFieldComponentProps => ( {
	field,
	value,
	onChange,
	values: { [ field.id ]: value },
	initialValues: { [ field.id ]: value },
	setValue: () => undefined,
	setValues: () => undefined,
	context: { page: 'test-page' },
} );

const makeField = (
	type: string,
	overrides: Partial< SettingsUIField > = {}
): SettingsUIField => ( {
	id: `field-${ type }`,
	label: `${ type } field`,
	type,
	...overrides,
} );

describe( 'NativeSettingsField compatibility renderer', () => {
	let warn: jest.SpyInstance;

	beforeEach( () => {
		warn = jest
			.spyOn( console, 'warn' )
			.mockImplementation( () => undefined );
	} );

	afterEach( () => {
		__resetWarnings();
		jest.restoreAllMocks();
	} );

	it( 'warns once when the deprecated renderer is used', () => {
		const first = renderElement(
			<NativeSettingsField
				{ ...makeProps( makeField( 'text' ), 'first' ) }
			/>
		);
		const second = renderElement(
			<NativeSettingsField
				{ ...makeProps( makeField( 'text' ), 'second' ) }
			/>
		);

		expect( warn ).toHaveBeenCalledTimes( 1 );
		expect( warn ).toHaveBeenCalledWith(
			expect.stringContaining( 'NativeSettingsField is deprecated' )
		);

		first.cleanup();
		second.cleanup();
	} );

	it( 'renders and updates text-family fields', () => {
		const onChange = jest.fn();
		const { container, cleanup } = renderElement(
			<NativeSettingsField
				{ ...makeProps(
					makeField( 'email' ),
					'test@example.com',
					onChange
				) }
			/>
		);
		const input = container.querySelector( 'input' ) as HTMLInputElement;

		expect( input ).toHaveAttribute( 'type', 'email' );
		expect( input ).toHaveValue( 'test@example.com' );
		const valueSetter = Object.getOwnPropertyDescriptor(
			HTMLInputElement.prototype,
			'value'
		)?.set;
		if ( ! valueSetter ) {
			throw new Error( 'Expected HTMLInputElement value setter.' );
		}
		act( () => {
			valueSetter.call( input, 'next@example.com' );
			input.dispatchEvent(
				new Event( 'input', { bubbles: true, cancelable: true } )
			);
		} );
		expect( onChange ).toHaveBeenCalledWith( 'next@example.com' );

		cleanup();
	} );

	it( 'renders checkbox fields with legacy truthy values', () => {
		const { container, cleanup } = renderElement(
			<NativeSettingsField
				{ ...makeProps(
					makeField( 'checkbox', { disabled: true } ),
					'yes'
				) }
			/>
		);
		const input = container.querySelector( 'input' ) as HTMLInputElement;

		expect( input ).toBeChecked();
		expect( input ).toBeDisabled();
		cleanup();
	} );

	it( 'renders textarea fields', () => {
		const { container, cleanup } = renderElement(
			<NativeSettingsField
				{ ...makeProps( makeField( 'textarea' ), 'Several lines' ) }
			/>
		);

		expect(
			container.querySelector( 'textarea' ) as HTMLTextAreaElement
		).toHaveValue( 'Several lines' );
		cleanup();
	} );

	it( 'renders select fields with their options', () => {
		const field = makeField( 'select', {
			options: [
				{ label: 'One', value: 'one' },
				{ label: 'Two', value: 'two' },
			],
		} );
		const { container, cleanup } = renderElement(
			<NativeSettingsField { ...makeProps( field, 'two' ) } />
		);

		expect(
			container.querySelector( 'select' ) as HTMLSelectElement
		).toHaveValue( 'two' );
		cleanup();
	} );

	it( 'renders array fields as multi-select controls', () => {
		const field = makeField( 'array', {
			options: [
				{ label: 'One', value: 'one' },
				{ label: 'Two', value: 'two' },
			],
		} );
		const { container, cleanup } = renderElement(
			<NativeSettingsField { ...makeProps( field, [ 'one', 'two' ] ) } />
		);
		const select = container.querySelector( 'select' ) as HTMLSelectElement;

		expect( select ).toHaveAttribute( 'multiple' );
		expect(
			Array.from( select.selectedOptions ).map(
				( option ) => option.value
			)
		).toEqual( [ 'one', 'two' ] );
		cleanup();
	} );

	it( 'renders sanitized info fields', () => {
		const { container, cleanup } = renderElement(
			<NativeSettingsField
				{ ...makeProps(
					makeField( 'info', {
						description:
							'<strong>Safe</strong><script>bad()</script>',
					} ),
					''
				) }
			/>
		);

		expect( container.innerHTML ).toContain( '<strong>Safe</strong>' );
		expect( container.querySelector( 'script' ) ).toBeNull();
		cleanup();
	} );

	it( 'steps number fields and announces the next value', () => {
		const onChange = jest.fn();
		const field = makeField( 'number', {
			customAttributes: { min: 0, step: 1 },
		} );
		const { container, cleanup } = renderElement(
			<NativeSettingsField { ...makeProps( field, '5', onChange ) } />
		);
		const button = container.querySelector(
			'button[aria-label="Increment number field"]'
		) as HTMLButtonElement;

		act( () => {
			button.dispatchEvent(
				new MouseEvent( 'click', { bubbles: true } )
			);
		} );
		expect( onChange ).toHaveBeenCalledWith( '6' );
		expect( speak ).toHaveBeenCalledWith( '6' );
		cleanup();
	} );

	it( 'falls back to a text control for unsupported types', () => {
		const { container, cleanup } = renderElement(
			<NativeSettingsField
				{ ...makeProps( makeField( 'custom-type' ), 'value' ) }
			/>
		);

		expect(
			container.querySelector( 'input[type="text"]' )
		).not.toBeNull();
		expect( warn ).toHaveBeenCalledWith(
			expect.stringContaining( 'custom-type' ),
			expect.any( Object )
		);
		cleanup();
	} );
} );
