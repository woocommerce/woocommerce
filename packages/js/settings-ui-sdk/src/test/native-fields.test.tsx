/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { act } from 'react';
import { createRoot } from 'react-dom/client';

/**
 * Internal dependencies
 */
import { NativeSettingsField } from '../native-fields';
import type {
	SettingsFieldComponentProps,
	SettingsUIField,
	SettingsValue,
} from '../types';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const renderElement = ( element: JSX.Element ) => {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	const root = createRoot( container );

	act( () => {
		root.render( element );
	} );

	return { container, root };
};

const makeProps = (
	field: SettingsUIField,
	value: SettingsValue,
	onChange: ( next: SettingsValue ) => void = () => {}
): SettingsFieldComponentProps => ( {
	field,
	value,
	onChange,
	values: { [ field.id ]: value },
	initialValues: { [ field.id ]: value },
	setValue: () => {},
	setValues: () => {},
	context: { page: 'test-page' },
} );

describe( 'NativeSettingsField', () => {
	let cleanup: ( () => void ) | null = null;

	afterEach( () => {
		cleanup?.();
		cleanup = null;
	} );

	const render = ( element: JSX.Element ) => {
		const { container, root } = renderElement( element );
		cleanup = () => {
			act( () => {
				root.unmount();
			} );
			container.remove();
		};
		return container;
	};

	describe( 'number fields', () => {
		const numberField: SettingsUIField = {
			id: 'wc_test_number',
			label: 'Low stock threshold',
			type: 'number',
			customAttributes: { min: 0, step: 1 },
		};

		it( 'renders a number input with custom spin buttons instead of native spinners', () => {
			const container = render(
				<NativeSettingsField { ...makeProps( numberField, '5' ) } />
			);

			const input = container.querySelector( 'input[type="number"]' );
			expect( input ).not.toBeNull();
			expect( input?.getAttribute( 'min' ) ).toBe( '0' );
			expect( input?.getAttribute( 'step' ) ).toBe( '1' );

			expect(
				container.querySelector( '[aria-label="Increment"]' )
			).not.toBeNull();
			expect(
				container.querySelector( '[aria-label="Decrement"]' )
			).not.toBeNull();
		} );

		it( 'calls onChange with the stepped value when a spin button is clicked', () => {
			const onChange = jest.fn();
			const container = render(
				<NativeSettingsField
					{ ...makeProps( numberField, '5', onChange ) }
				/>
			);

			const increment = container.querySelector(
				'[aria-label="Increment"]'
			) as HTMLButtonElement;

			act( () => {
				increment.dispatchEvent(
					new MouseEvent( 'click', { bubbles: true } )
				);
			} );

			expect( onChange ).toHaveBeenCalledWith( '6' );
		} );
	} );

	describe( 'text fields', () => {
		it( 'renders text fields without spin buttons', () => {
			const container = render(
				<NativeSettingsField
					{ ...makeProps(
						{
							id: 'wc_test_text',
							label: 'Store name',
							type: 'text',
						},
						'hello'
					) }
				/>
			);

			expect(
				container.querySelector( 'input[type="text"]' )
			).not.toBeNull();
			expect(
				container.querySelector( '[aria-label="Increment"]' )
			).toBeNull();
		} );
	} );
} );
