/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import { useState } from '@wordpress/element';
import * as wpData from '@wordpress/data';
import { AddressAutocomplete } from '@woocommerce/base-components/cart-checkout/address-autocomplete/address-autocomplete';
import { cartStore, checkoutStore } from '@woocommerce/block-data';

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
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

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSettingWithCoercion: jest
		.fn()
		.mockImplementation( ( value, fallback, typeguard ) => {
			if ( value === 'addressAutocompleteProviders' ) {
				return [
					{
						id: 'germany-only',
						name: 'Test Provider Only Works In Germany',
						branding_html: '<div>Test Provider - DE</div>',
					},
				];
			}
			return jest
				.requireActual( '@woocommerce/settings' )
				.getSettingWithCoercion( value, fallback, typeguard );
		} ),
} ) );
describe( 'Address Autocomplete Component', () => {
	beforeAll( () => {
		const germanyOnlyProvider = {
			id: 'germany-only',
			canSearch: ( country: string ) => {
				return country === 'DE';
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
				providers: { 'germany-only': germanyOnlyProvider },
				activeProvider: { billing: null, shipping: null },
				registerAddressAutocompleteProvider( provider ) {
					return !! provider;
				},
			},
		};
	} );
	it( 'Switches provider when country changes', async () => {
		const { rerender } = render(
			<AddressAutocomplete
				addressType="billing"
				onChange={ () => {} }
				id="billing_address_1"
			/>
		);
		expect(
			window.wc.addressAutocomplete.activeProvider.billing?.id
		).toEqual( 'germany-only' );
		// Shipping should not be set since this component is for billing address.
		expect(
			window.wc.addressAutocomplete.activeProvider.shipping
		).toBeNull();

		// Mock use select so we can override it when wc/store/checkout is accessed, but return the original select function if any other store is accessed.
		wpData.useSelect.mockImplementation(
			jest.fn().mockImplementation( ( passedMapSelect ) => {
				const mockedSelect = jest
					.fn()
					.mockImplementation( ( storeName ) => {
						if (
							storeName === 'wc/store/cart' ||
							storeName === cartStore
						) {
							return {
								getCartData() {
									return {
										shippingAddress: {
											country: 'DE',
										},
										billingAddress: {
											country: 'US',
										},
									};
								},
							};
						}
						return jest
							.requireActual( '@wordpress/data' )
							.select( storeName );
					} );
				return passedMapSelect( mockedSelect, {
					dispatch: jest.requireActual( '@wordpress/data' ).dispatch,
				} );
			} )
		);

		rerender(
			<AddressAutocomplete
				addressType="billing"
				onChange={ () => {} }
				id="billing_address_1"
			/>
		);

		expect( window.wc.addressAutocomplete.activeProvider.billing ).toEqual(
			null
		);
		// Shipping should still not be set since this component is for billing address.
		expect(
			window.wc.addressAutocomplete.activeProvider.shipping
		).toBeNull();

		// Render as shipping now, expect the shipping values to update.
		rerender(
			<AddressAutocomplete
				addressType="shipping"
				onChange={ () => {} }
				id="billing_address_1"
			/>
		);
		expect(
			window.wc.addressAutocomplete.activeProvider.shipping?.id
		).toEqual( 'germany-only' );
	} );
} );
