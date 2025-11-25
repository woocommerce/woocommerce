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
jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

// Mock use select so we can override it when wc/store/checkout is accessed, but return the original select function if any other store is accessed.
wpData.useSelect.mockImplementation(
	jest.fn().mockImplementation( ( passedMapSelect ) => {
		const mockedSelect = jest.fn().mockImplementation( ( storeName ) => {
			if ( storeName === 'wc/store/cart' || storeName === cartStore ) {
				return {
					getCartData() {
						return {
							shippingAddress: {
								country: 'DE',
							},
							billingAddress: {
								country: 'DE',
							},
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
						id: 'generic-provider',
						name: 'Generic Provider',
						branding_html: '<div>Test Provider - Generic</div>',
					},
				];
			}
			return jest
				.requireActual( '@woocommerce/settings' )
				.getSettingWithCoercion( value, fallback, typeguard );
		} ),
} ) );
describe( 'Suggestions - when rendered in AddressAutocomplete component', () => {
	beforeAll( () => {
		const genericProvider = {
			id: 'generic-provider',
			// eslint-disable-next-line @typescript-eslint/no-unused-vars
			canSearch: ( country: string ) => {
				return true;
			},
			// eslint-disable-next-line @typescript-eslint/no-unused-vars
			search: async ( inputValue: string, country: string ) => {
				// Mock search results.
				return [
					{
						label: '123 Example St, Berlin, Germany',
						id: '1',
						matchedSubstrings: [ { length: 3, offset: 0 } ],
					},
					{
						label: '456 Sample Rd, Munich, Germany',
						id: '2',
						matchedSubstrings: [ { length: 3, offset: 0 } ],
					},
				];
			},
			// eslint-disable-next-line @typescript-eslint/no-unused-vars
			select: async ( addressId: string, country: string ) => {
				return {
					address_1: '123 Example St',
					address_2: 'Address 2',
					city: 'Berlin',
					state: 'BE',
					postcode: '10115',
					country: 'DE',
				};
			},
		};

		window.wc = {
			...( window.wc || {} ),
			addressAutocomplete: {
				providers: { 'generic-provider': genericProvider },
				activeProvider: { billing: null, shipping: null },
				registerAddressAutocompleteProvider( provider ) {
					return !! provider;
				},
			},
		};
	} );
	it( 'Shows suggestions when provider returns results', async () => {
		const Component = () => {
			const [ value, setValue ] = useState( '' );
			return (
				<AddressAutocomplete
					addressType="billing"
					id="billing-test"
					label="Address 1"
					onChange={ setValue }
					value={ value }
				/>
			);
		};
		const { container } = render( <Component /> );
		//screen.debug( container );
		expect(
			container.querySelectorAll( '.suggestions-list li' ).length
		).toBe( 0 );
		await act( async () => {
			await userEvent.type(
				screen.getByLabelText( 'Address 1' ),
				'1234'
			);
		} );

		await waitFor(
			() => {
				expect(
					container.querySelectorAll( '.suggestions-list li' ).length
				).toBe( 2 );
			},
			{ timeout: 3000 }
		);
	} );
	it( 'Shows branding element in results', async () => {
		const Component = () => {
			const [ value, setValue ] = useState( '' );
			return (
				<AddressAutocomplete
					addressType="billing"
					id="billing-test"
					label="Address 1"
					onChange={ setValue }
					value={ value }
				/>
			);
		};
		render( <Component /> );
		await act( async () => {
			await userEvent.type(
				screen.getByLabelText( 'Address 1' ),
				'1234'
			);
		} );

		await waitFor(
			() => {
				expect(
					screen.getByText( 'Test Provider - Generic' )
				).toBeInTheDocument();
			},
			{ timeout: 3000 }
		);
	} );
	it( 'Using arrow keys navigates through suggestions', async () => {
		const Component = () => {
			const [ value, setValue ] = useState( '' );
			return (
				<AddressAutocomplete
					addressType="billing"
					id="billing-test"
					label="Address 1"
					onChange={ setValue }
					value={ value }
				/>
			);
		};
		render( <Component /> );
		await act( async () => {
			await userEvent.type(
				screen.getByLabelText( 'Address 1' ),
				'1234'
			);
		} );
		await waitFor(
			() => {
				expect(
					screen.getByText( 'Test Provider - Generic' )
				).toBeInTheDocument();
			},
			{ timeout: 3000 }
		);
		const input = screen.getByLabelText( 'Address 1' );
		// Press down arrow to select first item.
		await act( async () => {
			await userEvent.type( input, '{arrowdown}' );
		} );
		let firstSuggestion = screen.getByText( '123' );
		expect( firstSuggestion.closest( 'li' ) ).toHaveClass( 'active' );
		// Press down arrow to select second item.
		await act( async () => {
			await userEvent.type( input, '{arrowdown}' );
		} );
		const secondSuggestion = screen.getByText( '456' );
		expect( secondSuggestion.closest( 'li' ) ).toHaveClass( 'active' );
		expect( firstSuggestion.closest( 'li' ) ).not.toHaveClass( 'active' );
		// Press up arrow to go back to first item.
		await act( async () => {
			await userEvent.type( input, '{arrowup}' );
		} );
		firstSuggestion = screen.getByText( '123' );
		expect( firstSuggestion.closest( 'li' ) ).toHaveClass( 'active' );
		expect( secondSuggestion.closest( 'li' ) ).not.toHaveClass( 'active' );
	} );

	describe( 'ARIA accessibility attributes', () => {
		it( 'Sets correct ARIA roles and attributes on input when suggestions are shown', async () => {
			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="billing"
						id="billing-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			render( <Component /> );
			const input = screen.getByLabelText( 'Address 1' );

			// Initially, no suggestions - check ARIA state
			expect( input ).toHaveAttribute( 'role', 'combobox' );
			expect( input ).toHaveAttribute( 'aria-autocomplete', 'list' );
			expect( input ).toHaveAttribute( 'aria-expanded', 'false' );
			expect( input ).not.toHaveAttribute( 'aria-owns' );
			expect( input ).not.toHaveAttribute( 'aria-activedescendant' );

			// Type to trigger suggestions
			await act( async () => {
				await userEvent.type( input, '1234' );
			} );

			await waitFor(
				() => {
					expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
				},
				{ timeout: 3000 }
			);

			// Check ARIA attributes when suggestions are shown
			expect( input ).toHaveAttribute( 'aria-expanded', 'true' );
			expect( input ).toHaveAttribute(
				'aria-owns',
				'address-suggestions-billing-list'
			);
			expect( input ).not.toHaveAttribute( 'aria-activedescendant' );

			// Check listbox exists with correct attributes
			const listbox = screen.getByRole( 'listbox' );
			expect( listbox ).toHaveAttribute(
				'id',
				'address-suggestions-billing-list'
			);
			expect( listbox ).toHaveAttribute(
				'aria-label',
				'Address suggestions'
			);
		} );

		it( 'Updates aria-activedescendant when navigating with keyboard', async () => {
			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="shipping"
						id="shipping-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			render( <Component /> );
			const input = screen.getByLabelText( 'Address 1' );

			// Type to trigger suggestions
			await act( async () => {
				await userEvent.type( input, '1234' );
			} );

			await waitFor(
				() => {
					expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
				},
				{ timeout: 3000 }
			);

			// Initially no active descendant
			expect( input ).not.toHaveAttribute( 'aria-activedescendant' );

			// Press down arrow to select first item
			await act( async () => {
				await userEvent.type( input, '{arrowdown}' );
			} );

			// Check aria-activedescendant points to first item
			expect( input ).toHaveAttribute(
				'aria-activedescendant',
				'suggestion-item-shipping-0'
			);

			// Press down arrow to select second item
			await act( async () => {
				await userEvent.type( input, '{arrowdown}' );
			} );

			// Check aria-activedescendant updates to second item
			expect( input ).toHaveAttribute(
				'aria-activedescendant',
				'suggestion-item-shipping-1'
			);

			// Press up arrow to go back to first item
			await act( async () => {
				await userEvent.type( input, '{arrowup}' );
			} );

			// Check aria-activedescendant points back to first item
			expect( input ).toHaveAttribute(
				'aria-activedescendant',
				'suggestion-item-shipping-0'
			);
		} );

		it( 'Sets correct ARIA attributes on suggestion items', async () => {
			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="billing"
						id="billing-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			render( <Component /> );

			// Type to trigger suggestions
			await act( async () => {
				await userEvent.type(
					screen.getByLabelText( 'Address 1' ),
					'1234'
				);
			} );

			await waitFor(
				() => {
					expect( screen.getAllByRole( 'option' ) ).toHaveLength( 2 );
				},
				{ timeout: 3000 }
			);

			const options = screen.getAllByRole( 'option' );

			// Check all options have correct initial ARIA state
			options.forEach( ( option, index ) => {
				expect( option ).toHaveAttribute(
					'id',
					`suggestion-item-billing-${ index }`
				);
				expect( option ).toHaveAttribute( 'aria-selected', 'false' );
				expect( option ).toHaveAttribute( 'tabIndex', '-1' );
			} );

			// Navigate to first option
			const input = screen.getByLabelText( 'Address 1' );
			await act( async () => {
				await userEvent.type( input, '{arrowdown}' );
			} );

			// Check first option is selected
			expect( options[ 0 ] ).toHaveAttribute( 'aria-selected', 'true' );
			expect( options[ 1 ] ).toHaveAttribute( 'aria-selected', 'false' );

			// Navigate to second option
			await act( async () => {
				await userEvent.type( input, '{arrowdown}' );
			} );

			// Check second option is selected
			expect( options[ 0 ] ).toHaveAttribute( 'aria-selected', 'false' );
			expect( options[ 1 ] ).toHaveAttribute( 'aria-selected', 'true' );
		} );

		it( 'Includes aria-live region for announcements', async () => {
			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="billing"
						id="billing-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			render( <Component /> );

			// Type to trigger suggestions
			await act( async () => {
				await userEvent.type(
					screen.getByLabelText( 'Address 1' ),
					'1234'
				);
			} );

			await waitFor(
				() => {
					expect( screen.getByRole( 'region' ) ).toBeInTheDocument();
				},
				{ timeout: 3000 }
			);

			// Check aria-live region exists
			const liveRegion = screen.getByRole( 'region' );
			expect( liveRegion ).toHaveAttribute( 'aria-live', 'polite' );

			// Check it contains the suggestions
			const listbox = screen.getByRole( 'listbox' );
			expect( liveRegion ).toContainElement( listbox );
		} );

		it( 'Clears ARIA attributes when suggestions are hidden', async () => {
			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="billing"
						id="billing-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			render( <Component /> );
			const input = screen.getByLabelText( 'Address 1' );

			// Type to trigger suggestions
			await act( async () => {
				await userEvent.type( input, '1234' );
			} );

			await waitFor(
				() => {
					expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
				},
				{ timeout: 3000 }
			);

			// Navigate to select an item
			await act( async () => {
				await userEvent.type( input, '{arrowdown}' );
			} );

			// Verify ARIA attributes are set
			expect( input ).toHaveAttribute( 'aria-expanded', 'true' );
			expect( input ).toHaveAttribute(
				'aria-activedescendant',
				'suggestion-item-billing-0'
			);

			// Press Escape to clear suggestions
			await act( async () => {
				await userEvent.type( input, '{Escape}' );
			} );

			// Check ARIA attributes are cleared
			expect( input ).toHaveAttribute( 'aria-expanded', 'false' );
			expect( input ).not.toHaveAttribute( 'aria-owns' );
			expect( input ).not.toHaveAttribute( 'aria-activedescendant' );

			// Suggestions should be gone
			expect( screen.queryByRole( 'listbox' ) ).not.toBeInTheDocument();
		} );

		it( 'Maintains focus on input during keyboard navigation', async () => {
			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="billing"
						id="billing-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			render( <Component /> );
			const input = screen.getByLabelText( 'Address 1' );

			// Focus the input
			await act( async () => {
				input.focus();
			} );
			expect( document.activeElement ).toBe( input );

			// Type to trigger suggestions
			await act( async () => {
				await userEvent.type( input, '1234' );
			} );

			await waitFor(
				() => {
					expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
				},
				{ timeout: 3000 }
			);

			// Navigate with arrow keys
			await act( async () => {
				await userEvent.type( input, '{arrowdown}' );
			} );

			// Focus should remain on input
			expect( document.activeElement ).toBe( input );

			await act( async () => {
				await userEvent.type( input, '{arrowdown}' );
			} );

			// Focus should still be on input
			expect( document.activeElement ).toBe( input );

			await act( async () => {
				await userEvent.type( input, '{arrowup}' );
			} );

			// Focus should still be on input
			expect( document.activeElement ).toBe( input );
		} );

		it( 'Pressing Escape key hides suggestions and resets all ARIA states', async () => {
			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="billing"
						id="billing-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			render( <Component /> );
			const input = screen.getByLabelText( 'Address 1' );

			// Type to trigger suggestions
			await act( async () => {
				await userEvent.type( input, '1234' );
			} );

			await waitFor(
				() => {
					expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
				},
				{ timeout: 3000 }
			);

			// Navigate to select an item (to set aria-activedescendant)
			await act( async () => {
				await userEvent.type( input, '{arrowdown}' );
			} );
			await act( async () => {
				await userEvent.type( input, '{arrowdown}' );
			} );

			// Verify suggestions are visible and ARIA attributes are set
			expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
			expect( screen.getAllByRole( 'option' ) ).toHaveLength( 2 );
			expect( input ).toHaveAttribute( 'aria-expanded', 'true' );
			expect( input ).toHaveAttribute(
				'aria-owns',
				'address-suggestions-billing-list'
			);
			expect( input ).toHaveAttribute(
				'aria-activedescendant',
				'suggestion-item-billing-1'
			);

			// Verify second option is selected
			const options = screen.getAllByRole( 'option' );
			expect( options[ 1 ] ).toHaveAttribute( 'aria-selected', 'true' );
			expect( options[ 1 ] ).toHaveClass( 'active' );

			// Press Escape to close suggestions
			await act( async () => {
				await userEvent.type( input, '{Escape}' );
			} );

			// Verify suggestions are hidden
			expect( screen.queryByRole( 'listbox' ) ).not.toBeInTheDocument();
			expect( screen.queryByRole( 'region' ) ).not.toBeInTheDocument();
			expect( screen.queryAllByRole( 'option' ) ).toHaveLength( 0 );

			// Verify all ARIA attributes are reset
			expect( input ).toHaveAttribute( 'role', 'combobox' );
			expect( input ).toHaveAttribute( 'aria-autocomplete', 'list' );
			expect( input ).toHaveAttribute( 'aria-expanded', 'false' );
			expect( input ).not.toHaveAttribute( 'aria-owns' );
			expect( input ).not.toHaveAttribute( 'aria-activedescendant' );

			// Verify focus remains on input
			expect( document.activeElement ).toBe( input );

			// Verify input value is preserved
			expect( input ).toHaveValue( '1234' );
		} );

		it( 'Escape key works at any point during navigation', async () => {
			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="shipping"
						id="shipping-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			const { container } = render( <Component /> );
			const input = screen.getByLabelText( 'Address 1' );

			// Test 1: Escape immediately after suggestions appear (no selection)
			await act( async () => {
				await userEvent.type( input, '123' );
			} );

			await waitFor(
				() => {
					expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
				},
				{ timeout: 3000 }
			);

			await act( async () => {
				await userEvent.type( input, '{Escape}' );
			} );

			expect( screen.queryByRole( 'listbox' ) ).not.toBeInTheDocument();

			// Test 2: Escape after navigating to first item
			await act( async () => {
				await userEvent.type( input, '4' ); // Now "1234"
			} );

			await waitFor(
				() => {
					expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
				},
				{ timeout: 3000 }
			);

			await act( async () => {
				await userEvent.type( input, '{arrowdown}' );
			} );

			expect( input ).toHaveAttribute(
				'aria-activedescendant',
				'suggestion-item-shipping-0'
			);

			await act( async () => {
				await userEvent.type( input, '{Escape}' );
			} );

			expect( screen.queryByRole( 'listbox' ) ).not.toBeInTheDocument();
			expect( input ).not.toHaveAttribute( 'aria-activedescendant' );

			// Verify we can still type and get suggestions again after Escape
			await act( async () => {
				await userEvent.clear( input );
				await userEvent.type( input, 'test' );
			} );

			await waitFor(
				() => {
					expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
				},
				{ timeout: 3000 }
			);

			expect(
				container.querySelectorAll( '.suggestions-list li' ).length
			).toBe( 2 );
		} );

		it( 'Pressing Enter key on selected suggestion calls correct dispatch function', async () => {
			const mockSetBillingAddress = jest.fn();
			const mockSetShippingAddress = jest.fn();

			// Override the mock for this specific test
			wpData.useDispatch.mockImplementation(
				( store: StoreDescriptor | string ) => {
					if ( store === cartStore || store === 'wc/store/cart' ) {
						return {
							...jest
								.requireActual( '@wordpress/data' )
								.useDispatch( store ),
							setShippingAddress: mockSetShippingAddress,
							setBillingAddress: mockSetBillingAddress,
						};
					}
					return jest
						.requireActual( '@wordpress/data' )
						.useDispatch( store );
				}
			);

			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="billing"
						id="billing-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			render( <Component /> );
			const input = screen.getByLabelText( 'Address 1' );

			// Type to trigger suggestions
			await act( async () => {
				await userEvent.type( input, '1234' );
			} );

			await waitFor(
				() => {
					expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
				},
				{ timeout: 3000 }
			);

			// Navigate to first suggestion
			await act( async () => {
				await userEvent.type( input, '{arrowdown}' );
			} );

			// Verify the suggestion is selected
			expect( input ).toHaveAttribute(
				'aria-activedescendant',
				'suggestion-item-billing-0'
			);

			// Press Enter to select the address
			await act( async () => {
				await userEvent.type( input, '{Enter}' );
			} );

			// Wait for the async select operation
			await waitFor(
				() => {
					expect( mockSetBillingAddress ).toHaveBeenCalled();
				},
				{ timeout: 3000 }
			);

			// Verify setBillingAddress was called with the correct data
			expect( mockSetBillingAddress ).toHaveBeenCalledWith( {
				address_1: '123 Example St',
				address_2: 'Address 2',
				city: 'Berlin',
				state: 'BE',
				postcode: '10115',
				country: 'DE',
			} );

			// Verify setShippingAddress was NOT called
			expect( mockSetShippingAddress ).not.toHaveBeenCalled();

			// Verify suggestions are hidden after selection
			await waitFor( () => {
				expect(
					screen.queryByRole( 'listbox' )
				).not.toBeInTheDocument();
			} );

			// Verify ARIA attributes are reset
			expect( input ).toHaveAttribute( 'aria-expanded', 'false' );
			expect( input ).not.toHaveAttribute( 'aria-activedescendant' );
			expect( input ).not.toHaveAttribute( 'aria-owns' );
		} );

		it( 'Pressing Enter on shipping address calls setShippingAddress', async () => {
			const mockSetBillingAddress = jest.fn();
			const mockSetShippingAddress = jest.fn();

			// Override the mock for this specific test
			wpData.useDispatch.mockImplementation(
				( store: StoreDescriptor | string ) => {
					if ( store === cartStore || store === 'wc/store/cart' ) {
						return {
							...jest
								.requireActual( '@wordpress/data' )
								.useDispatch( store ),
							setShippingAddress: mockSetShippingAddress,
							setBillingAddress: mockSetBillingAddress,
						};
					}
					return jest
						.requireActual( '@wordpress/data' )
						.useDispatch( store );
				}
			);

			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="shipping"
						id="shipping-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			render( <Component /> );
			const input = screen.getByLabelText( 'Address 1' );

			// Type to trigger suggestions
			await act( async () => {
				await userEvent.type( input, 'test' );
			} );

			await waitFor(
				() => {
					expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
				},
				{ timeout: 3000 }
			);

			// Navigate to second suggestion
			await act( async () => {
				await userEvent.type( input, '{arrowdown}' );
				await userEvent.type( input, '{arrowdown}' );
			} );

			// Press Enter to select the address
			await act( async () => {
				await userEvent.type( input, '{Enter}' );
			} );

			// Wait for the async select operation
			await waitFor(
				() => {
					expect( mockSetShippingAddress ).toHaveBeenCalled();
				},
				{ timeout: 3000 }
			);

			// Verify setShippingAddress was called
			expect( mockSetShippingAddress ).toHaveBeenCalledWith( {
				address_1: '123 Example St',
				address_2: 'Address 2',
				city: 'Berlin',
				state: 'BE',
				postcode: '10115',
				country: 'DE',
			} );

			// Verify setBillingAddress was NOT called
			expect( mockSetBillingAddress ).not.toHaveBeenCalled();
		} );

		it( 'Enter key does nothing when no suggestion is selected', async () => {
			const mockSetBillingAddress = jest.fn();
			const mockSetShippingAddress = jest.fn();

			// Override the mock for this specific test
			wpData.useDispatch.mockImplementation(
				( store: StoreDescriptor | string ) => {
					if ( store === cartStore || store === 'wc/store/cart' ) {
						return {
							...jest
								.requireActual( '@wordpress/data' )
								.useDispatch( store ),
							setShippingAddress: mockSetShippingAddress,
							setBillingAddress: mockSetBillingAddress,
						};
					}
					return jest
						.requireActual( '@wordpress/data' )
						.useDispatch( store );
				}
			);

			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="billing"
						id="billing-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			render( <Component /> );
			const input = screen.getByLabelText( 'Address 1' );

			// Type to trigger suggestions
			await act( async () => {
				await userEvent.type( input, '1234' );
			} );

			await waitFor(
				() => {
					expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
				},
				{ timeout: 3000 }
			);

			// Press Enter without selecting any suggestion
			await act( async () => {
				await userEvent.type( input, '{Enter}' );
			} );

			// Wait a moment to ensure no async operations happen
			await act( async () => {
				await new Promise( ( resolve ) => setTimeout( resolve, 100 ) );
			} );

			// Verify no dispatch functions were called
			expect( mockSetBillingAddress ).not.toHaveBeenCalled();
			expect( mockSetShippingAddress ).not.toHaveBeenCalled();

			// Suggestions should still be visible
			expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
		} );

		it( 'Clicking on a suggestion calls correct dispatch function', async () => {
			const mockSetBillingAddress = jest.fn();
			const mockSetShippingAddress = jest.fn();

			// Override the mock for this specific test
			wpData.useDispatch.mockImplementation(
				( store: StoreDescriptor | string ) => {
					if ( store === cartStore || store === 'wc/store/cart' ) {
						return {
							...jest
								.requireActual( '@wordpress/data' )
								.useDispatch( store ),
							setShippingAddress: mockSetShippingAddress,
							setBillingAddress: mockSetBillingAddress,
						};
					}
					return jest
						.requireActual( '@wordpress/data' )
						.useDispatch( store );
				}
			);

			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="billing"
						id="billing-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			render( <Component /> );

			// Type to trigger suggestions
			await act( async () => {
				await userEvent.type(
					screen.getByLabelText( 'Address 1' ),
					'1234'
				);
			} );

			await waitFor(
				() => {
					expect( screen.getAllByRole( 'option' ) ).toHaveLength( 2 );
				},
				{ timeout: 3000 }
			);

			const options = screen.getAllByRole( 'option' );

			// Click on the second suggestion
			await act( async () => {
				await userEvent.click( options[ 1 ] );
			} );

			// Wait for the async select operation
			await waitFor(
				() => {
					expect( mockSetBillingAddress ).toHaveBeenCalled();
				},
				{ timeout: 3000 }
			);

			// Verify setBillingAddress was called with the correct data
			expect( mockSetBillingAddress ).toHaveBeenCalledWith( {
				address_1: '123 Example St',
				address_2: 'Address 2',
				city: 'Berlin',
				state: 'BE',
				postcode: '10115',
				country: 'DE',
			} );

			// Verify setShippingAddress was NOT called
			expect( mockSetShippingAddress ).not.toHaveBeenCalled();

			// Verify suggestions are hidden after selection
			await waitFor( () => {
				expect(
					screen.queryByRole( 'listbox' )
				).not.toBeInTheDocument();
			} );
		} );

		it( 'Clicking on shipping address suggestion calls setShippingAddress', async () => {
			const mockSetBillingAddress = jest.fn();
			const mockSetShippingAddress = jest.fn();

			// Override the mock for this specific test
			wpData.useDispatch.mockImplementation(
				( store: StoreDescriptor | string ) => {
					if ( store === cartStore || store === 'wc/store/cart' ) {
						return {
							...jest
								.requireActual( '@wordpress/data' )
								.useDispatch( store ),
							setShippingAddress: mockSetShippingAddress,
							setBillingAddress: mockSetBillingAddress,
						};
					}
					return jest
						.requireActual( '@wordpress/data' )
						.useDispatch( store );
				}
			);

			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="shipping"
						id="shipping-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			render( <Component /> );

			// Type to trigger suggestions
			await act( async () => {
				await userEvent.type(
					screen.getByLabelText( 'Address 1' ),
					'test'
				);
			} );

			await waitFor(
				() => {
					expect( screen.getAllByRole( 'option' ) ).toHaveLength( 2 );
				},
				{ timeout: 3000 }
			);

			const options = screen.getAllByRole( 'option' );

			// Click on the first suggestion
			await act( async () => {
				await userEvent.click( options[ 0 ] );
			} );

			// Wait for the async select operation
			await waitFor(
				() => {
					expect( mockSetShippingAddress ).toHaveBeenCalled();
				},
				{ timeout: 3000 }
			);

			// Verify setShippingAddress was called
			expect( mockSetShippingAddress ).toHaveBeenCalledWith( {
				address_1: '123 Example St',
				address_2: 'Address 2',
				city: 'Berlin',
				state: 'BE',
				postcode: '10115',
				country: 'DE',
			} );

			// Verify setBillingAddress was NOT called
			expect( mockSetBillingAddress ).not.toHaveBeenCalled();
		} );

		it( 'Handles search function errors gracefully without breaking the input', async () => {
			// Create a provider that throws an error during search
			const errorProvider = {
				id: 'error-provider',
				canSearch: ( country: string ) => {
					return country === 'DE';
				},
				search: async () => {
					throw new Error( 'Search API failed' );
				},
				select: async () => {
					throw new Error( 'Select API failed' );
				},
			};

			// Replace the generic provider with our error provider
			window.wc.addressAutocomplete.providers[ 'generic-provider' ] =
				errorProvider;
			window.wc.addressAutocomplete.activeProvider.billing =
				errorProvider;

			const Component = () => {
				const [ value, setValue ] = useState( '' );
				return (
					<AddressAutocomplete
						addressType="billing"
						id="billing-test"
						label="Address 1"
						onChange={ setValue }
						value={ value }
					/>
				);
			};
			const { container } = render( <Component /> );
			const input = screen.getByLabelText( 'Address 1' );

			// Type to trigger search (which will fail)
			await act( async () => {
				await userEvent.type( input, '1234' );
			} );

			// Wait a bit to ensure the search attempt happens
			await act( async () => {
				await new Promise( ( resolve ) => setTimeout( resolve, 200 ) );
			} );

			// Verify no suggestions are shown
			expect( screen.queryByRole( 'listbox' ) ).not.toBeInTheDocument();
			expect(
				container.querySelectorAll( '.suggestions-list li' ).length
			).toBe( 0 );

			// Verify the input still works - we can continue typing
			await act( async () => {
				await userEvent.type( input, '5678' );
			} );

			// Verify the value was updated
			expect( input ).toHaveValue( '12345678' );

			// Verify ARIA attributes indicate no suggestions
			expect( input ).toHaveAttribute( 'aria-expanded', 'false' );
			expect( input ).not.toHaveAttribute( 'aria-owns' );
			expect( input ).not.toHaveAttribute( 'aria-activedescendant' );

			// Clear and type again to ensure it still works
			await act( async () => {
				await userEvent.clear( input );
				await userEvent.type( input, 'test address' );
			} );

			expect( input ).toHaveValue( 'test address' );

			// Still no suggestions should appear
			expect( screen.queryByRole( 'listbox' ) ).not.toBeInTheDocument();
		} );
	} );
} );
