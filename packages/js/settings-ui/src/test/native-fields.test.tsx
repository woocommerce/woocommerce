/**
 * External dependencies
 */
import { speak } from '@wordpress/a11y';
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

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

const previousActEnvironment = globalThis.IS_REACT_ACT_ENVIRONMENT;
globalThis.IS_REACT_ACT_ENVIRONMENT = true;

afterAll( () => {
	globalThis.IS_REACT_ACT_ENVIRONMENT = previousActEnvironment;
} );

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

	const clickButton = ( button: HTMLElement ) => {
		act( () => {
			button.dispatchEvent(
				new MouseEvent( 'click', { bubbles: true } )
			);
		} );
	};

	const getSpinButton = ( container: HTMLElement, ariaLabel: string ) => {
		const button = container.querySelector(
			`button[aria-label="${ ariaLabel }"]`
		);

		if ( ! ( button instanceof HTMLButtonElement ) ) {
			throw new Error(
				`Expected a spin button labelled "${ ariaLabel }".`
			);
		}

		return button;
	};

	// Spin buttons stay perceivable when disabled (accessibleWhenDisabled),
	// so the disabled state surfaces as aria-disabled, not [disabled].
	const isSpinButtonDisabled = ( button: HTMLButtonElement ) =>
		button.disabled || button.getAttribute( 'aria-disabled' ) === 'true';

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
				getSpinButton( container, 'Increment Low stock threshold' )
			).toBeInstanceOf( HTMLButtonElement );
			expect(
				getSpinButton( container, 'Decrement Low stock threshold' )
			).toBeInstanceOf( HTMLButtonElement );
		} );

		it( 'calls onChange with the stepped value and announces it when a spin button is clicked', () => {
			const onChange = jest.fn();
			const container = render(
				<NativeSettingsField
					{ ...makeProps( numberField, '5', onChange ) }
				/>
			);

			clickButton(
				getSpinButton( container, 'Increment Low stock threshold' )
			);

			expect( onChange ).toHaveBeenCalledWith( '6' );
			expect( speak ).toHaveBeenCalledWith( '6' );
		} );

		it( 'disables the decrement button at the minimum value', () => {
			const container = render(
				<NativeSettingsField { ...makeProps( numberField, '0' ) } />
			);

			expect(
				isSpinButtonDisabled(
					getSpinButton( container, 'Decrement Low stock threshold' )
				)
			).toBe( true );
			expect(
				isSpinButtonDisabled(
					getSpinButton( container, 'Increment Low stock threshold' )
				)
			).toBe( false );
		} );

		it( 'disables the increment button at the maximum value', () => {
			const container = render(
				<NativeSettingsField
					{ ...makeProps(
						{
							...numberField,
							customAttributes: { min: 0, max: 10, step: 1 },
						},
						'10'
					) }
				/>
			);

			expect(
				isSpinButtonDisabled(
					getSpinButton( container, 'Increment Low stock threshold' )
				)
			).toBe( true );
			expect(
				isSpinButtonDisabled(
					getSpinButton( container, 'Decrement Low stock threshold' )
				)
			).toBe( false );
		} );

		it( 'falls back to a step of 1 when the schema provides a non-positive step', () => {
			const onChange = jest.fn();
			const container = render(
				<NativeSettingsField
					{ ...makeProps(
						{
							...numberField,
							customAttributes: { min: 0, step: 0 },
						},
						'5',
						onChange
					) }
				/>
			);

			clickButton(
				getSpinButton( container, 'Increment Low stock threshold' )
			);

			expect( onChange ).toHaveBeenCalledWith( '6' );
		} );

		it( 'clamps stepping to the maximum and avoids float precision drift', () => {
			const onChange = jest.fn();
			const container = render(
				<NativeSettingsField
					{ ...makeProps(
						{
							...numberField,
							customAttributes: { min: 0, max: 0.3, step: 0.2 },
						},
						'0.1',
						onChange
					) }
				/>
			);

			clickButton(
				getSpinButton( container, 'Increment Low stock threshold' )
			);

			expect( onChange ).toHaveBeenCalledWith( '0.3' );
		} );

		it( 'preserves current value precision when it exceeds step precision', () => {
			const onChange = jest.fn();
			const container = render(
				<NativeSettingsField
					{ ...makeProps(
						{
							...numberField,
							customAttributes: { min: 0, step: 0.1 },
						},
						'0.05',
						onChange
					) }
				/>
			);

			clickButton(
				getSpinButton( container, 'Increment Low stock threshold' )
			);

			expect( onChange ).toHaveBeenCalledWith( '0.15' );
		} );

		it( 'handles scientific-notation steps', () => {
			const onChange = jest.fn();
			const container = render(
				<NativeSettingsField
					{ ...makeProps(
						{
							...numberField,
							customAttributes: { min: 0, step: 1e-7 },
						},
						'0',
						onChange
					) }
				/>
			);

			clickButton(
				getSpinButton( container, 'Increment Low stock threshold' )
			);

			expect( onChange ).toHaveBeenCalledWith( '1e-7' );
		} );

		it( 'does not exceed toFixed precision limits for tiny scientific-notation steps', () => {
			const onChange = jest.fn();
			const container = render(
				<NativeSettingsField
					{ ...makeProps(
						{
							...numberField,
							customAttributes: { min: 0, step: 1e-200 },
						},
						'0',
						onChange
					) }
				/>
			);

			expect( () =>
				clickButton(
					getSpinButton( container, 'Increment Low stock threshold' )
				)
			).not.toThrow();
			expect( onChange ).toHaveBeenCalledWith( '1e-200' );
		} );

		it( 'steps onto the minimum from an empty value', () => {
			const onChange = jest.fn();
			const container = render(
				<NativeSettingsField
					{ ...makeProps(
						{
							...numberField,
							customAttributes: { min: 2, step: 1 },
						},
						'',
						onChange
					) }
				/>
			);

			clickButton(
				getSpinButton( container, 'Increment Low stock threshold' )
			);

			expect( onChange ).toHaveBeenCalledWith( '2' );
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
				container.querySelector( '.wc-settings-ui__number-control' )
			).toBeNull();
		} );
	} );
} );
