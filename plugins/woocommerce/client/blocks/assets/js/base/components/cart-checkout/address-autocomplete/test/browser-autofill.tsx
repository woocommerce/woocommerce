/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from '@wordpress/element';
import * as wpData from '@wordpress/data';
import { cartStore } from '@woocommerce/block-data';
import type { StoreDescriptor } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { AddressAutocomplete } from '../address-autocomplete';
import {
	createMockProvider,
	installMockProvider,
	type MockProvider,
} from './utils/mock-provider';

// --- Mocks (same pattern as integration.tsx) ---

const mockUseCheckoutAddress = jest.fn();
jest.mock( '@woocommerce/base-context', () => ( {
	...jest.requireActual( '@woocommerce/base-context' ),
	useCheckoutAddress: () => mockUseCheckoutAddress(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

wpData.useSelect.mockImplementation(
	jest.fn().mockImplementation( ( passedMapSelect ) => {
		const mockedSelect = jest.fn().mockImplementation( ( storeName ) => {
			if ( storeName === 'wc/store/cart' || storeName === cartStore ) {
				return {
					getCartData() {
						return {
							shippingAddress: { country: 'DE' },
							billingAddress: { country: 'DE' },
						};
					},
				};
			}
			return jest.requireActual( '@wordpress/data' ).select( storeName );
		} );
		return passedMapSelect( mockedSelect, {
			dispatch: jest.requireActual( '@wordpress/data' ).dispatch,
		} );
	} )
);

wpData.useDispatch.mockImplementation( ( store: StoreDescriptor | string ) => {
	if ( store === cartStore || store === 'wc/store/cart' ) {
		return {
			...jest.requireActual( '@wordpress/data' ).useDispatch( store ),
			setShippingAddress: jest.fn(),
			setBillingAddress: jest.fn(),
		};
	}
	return jest.requireActual( '@wordpress/data' ).useDispatch( store );
} );

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSettingWithCoercion: jest
		.fn()
		.mockImplementation( ( value, fallback, typeguard ) => {
			if ( value === 'addressAutocompleteProviders' ) {
				return [
					{
						id: 'mock-test-provider',
						name: 'Mock Test Provider',
						branding_html: '<div>Mock Provider</div>',
					},
				];
			}
			return jest
				.requireActual( '@woocommerce/settings' )
				.getSettingWithCoercion( value, fallback, typeguard );
		} ),
} ) );

// --- Helper ---

const TestAddressField = ( {
	addressType = 'shipping' as const,
}: {
	addressType?: 'shipping' | 'billing';
} ) => {
	const [ value, setValue ] = useState( '' );
	return (
		<AddressAutocomplete
			addressType={ addressType }
			id={ `${ addressType }-address_1` }
			label="Address 1"
			onChange={ setValue }
			value={ value }
		/>
	);
};

/**
 * Dispatch a native InputEvent with the given inputType on the element.
 * This simulates what the browser does for autofill (insertReplacementText)
 * or user typing (insertText).
 */
function fireNativeInputEvent(
	element: HTMLInputElement,
	value: string,
	inputType: string
) {
	// Set the value on the native element (as the browser would).
	const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
		HTMLInputElement.prototype,
		'value'
	)?.set;
	nativeInputValueSetter?.call( element, value );

	// Dispatch the native input event with the specified inputType.
	const inputEvent = new InputEvent( 'input', {
		bubbles: true,
		cancelable: false,
		inputType,
		data: value,
	} );
	element.dispatchEvent( inputEvent );
}

// --- Tests ---

describe( 'Browser autofill vs user typing — WOOPLUG-6341', () => {
	let mockProvider: MockProvider;

	beforeEach( () => {
		mockUseCheckoutAddress.mockReturnValue( {
			useShippingAsBilling: false,
			useBillingAsShipping: false,
		} );
		mockProvider = createMockProvider();
		installMockProvider( mockProvider );
	} );

	it( 'triggers search when user types into the field', async () => {
		render( <TestAddressField /> );
		const input = screen.getByLabelText( 'Address 1' );

		await act( async () => {
			await userEvent.type( input, '123 Main' );
		} );

		await waitFor(
			() => {
				expect( mockProvider.search ).toHaveBeenCalled();
			},
			{ timeout: 3000 }
		);
	} );

	it( 'should NOT trigger search when browser autofill fires insertReplacementText', async () => {
		render( <TestAddressField /> );
		const input = screen.getByLabelText( 'Address 1' ) as HTMLInputElement;

		// Simulate browser autofill: dispatch a native input event with
		// inputType "insertReplacementText", which is what Chrome/Firefox
		// produce when the browser's saved-address autofill populates a field.
		await act( async () => {
			fireNativeInputEvent(
				input,
				'742 Evergreen Terrace, Springfield',
				'insertReplacementText'
			);
		} );

		// Give the search effect time to fire.
		await act( async () => {
			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );
		} );

		expect( mockProvider.search ).not.toHaveBeenCalled();
	} );

	it( 'should NOT trigger search when value changes without keyboard input (fallback detection)', async () => {
		render( <TestAddressField /> );
		const input = screen.getByLabelText( 'Address 1' ) as HTMLInputElement;

		// Simulate a programmatic value change with no inputType and no
		// preceding keydown — e.g. a password manager or extension that
		// sets the value via script. The fallback keyboard-gating should
		// prevent search since userIsTypingRef was never set.
		await act( async () => {
			fireNativeInputEvent(
				input,
				'742 Evergreen Terrace, Springfield',
				''
			);
		} );

		await act( async () => {
			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );
		} );

		expect( mockProvider.search ).not.toHaveBeenCalled();
	} );

	it( 'should discard search results when :-webkit-autofill is detected (Safari Contacts)', async () => {
		render( <TestAddressField /> );
		const input = screen.getByLabelText( 'Address 1' ) as HTMLInputElement;

		// Safari Contacts fills character-by-character with insertText events
		// (indistinguishable from typing). A search may fire, but by the time
		// results resolve the field will have :-webkit-autofill applied.
		// Mock matches() to simulate this state so results are discarded.
		const originalMatches = input.matches.bind( input );
		input.matches = ( selector: string ) => {
			if ( selector === ':-webkit-autofill' ) {
				return true;
			}
			return originalMatches( selector );
		};

		// Type enough to trigger a search (simulates Safari Contacts fill).
		await act( async () => {
			await userEvent.type( input, '742 Evergreen' );
		} );

		await waitFor(
			() => {
				// The search should have been called (characters looked like typing).
				expect( mockProvider.search ).toHaveBeenCalled();
			},
			{ timeout: 3000 }
		);

		// But because :-webkit-autofill matched when results came back,
		// no suggestions should be visible.
		expect(
			document.querySelector(
				'.wc-block-components-address-autocomplete-suggestions'
			)
		).toBeNull();

		// Restore original matches.
		input.matches = originalMatches;
	} );

	it( 'resumes search normally after autofill if user starts typing again', async () => {
		render( <TestAddressField /> );
		const input = screen.getByLabelText( 'Address 1' );

		// First: browser autofill (should NOT trigger search)
		await act( async () => {
			fireNativeInputEvent(
				input as HTMLInputElement,
				'742 Evergreen Terrace',
				'insertReplacementText'
			);
		} );

		await act( async () => {
			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );
		} );

		mockProvider.search.mockClear();

		// Then: user clears and types manually (SHOULD trigger search)
		await act( async () => {
			await userEvent.clear( input );
			await userEvent.type( input, '456 Oak' );
		} );

		await waitFor(
			() => {
				expect( mockProvider.search ).toHaveBeenCalled();
			},
			{ timeout: 3000 }
		);
	} );
} );
