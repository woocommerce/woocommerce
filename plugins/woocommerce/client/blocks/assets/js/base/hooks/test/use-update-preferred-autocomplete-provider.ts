/**
 * External dependencies
 */
import * as wpData from '@wordpress/data';
import { cartStore, checkoutStore } from '@woocommerce/block-data';
import { renderHook } from '@testing-library/react';

// Mock all problematic dependencies first - MUST be before any imports
jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import { useUpdatePreferredAutocompleteProvider } from '../use-update-preferred-autocomplete-provider';

// Mock settings
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
					{
						id: 'fallback',
						name: 'Fallback Test Provider',
						branding_html: '<div>Test Provider - Fallback</div>',
					},
				];
			}
			return jest
				.requireActual( '@woocommerce/settings' )
				.getSettingWithCoercion( value, fallback, typeguard );
		} ),
} ) );

const mockSetActiveAddressAutocompleteProvider = jest.fn();

wpData.useDispatch.mockImplementation( ( storeName ) => {
	if ( storeName === 'wc/store/checkout' ) {
		return {
			setActiveAddressAutocompleteProvider:
				mockSetActiveAddressAutocompleteProvider,
		};
	}
	return {};
} );

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
			if (
				storeName === 'wc/store/checkout' ||
				storeName === checkoutStore
			) {
				return {
					getRegisteredAutocompleteProviders() {
						return [];
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

/**
 * This is in a separate file as doing it in `index` led to an overly complicated set of mocks. Doing it here allows the test to be isolated.
 */
describe( 'Autocomplete country change handler', () => {
	it( 'should update provider when country changes', () => {
		window.wc = {
			addressAutocomplete: {
				registerAddressAutocompleteProvider: ( provider ) =>
					!! provider,
				activeProvider: { shipping: null, billing: null },
				providers: {
					'germany-only': {
						id: 'germany-only',
						canSearch: ( country: string ) => country === 'DE',
						search: async () => [],
						select: async () => ( {
							address_1: 'Some Street 1',
							address_2: '',
							city: 'Some City',
							postcode: '12345',
							country: 'DE',
							state: 'BE',
						} ),
					},
					fallback: {
						id: 'fallback',
						canSearch: ( country: string ) => !! country,
						search: async () => [],
						select: async () => ( {
							address_1: 'Some Street 1',
							address_2: '',
							city: 'Some City',
							postcode: '12345',
							country: 'US',
							state: 'CA',
						} ),
					},
				},
			},
		};

		// Call the hook with both billing and shipping.
		renderHook( () =>
			useUpdatePreferredAutocompleteProvider( 'shipping' )
		);
		renderHook( () => useUpdatePreferredAutocompleteProvider( 'billing' ) );

		// Verify that the provider update was called with 'germany-only' provider as it's preferred and supports DE country.
		expect( mockSetActiveAddressAutocompleteProvider ).toHaveBeenCalledWith(
			'germany-only',
			'shipping'
		);
		expect( mockSetActiveAddressAutocompleteProvider ).toHaveBeenCalledWith(
			'germany-only',
			'billing'
		);

		mockSetActiveAddressAutocompleteProvider.mockReset();
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
											country: 'US',
										},
										billingAddress: {
											country: 'DE',
										},
									};
								},
							};
						}
						if (
							storeName === 'wc/store/checkout' ||
							storeName === checkoutStore
						) {
							return {
								getRegisteredAutocompleteProviders() {
									return [];
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

		// Call it again now countries have changed.
		renderHook( () =>
			useUpdatePreferredAutocompleteProvider( 'shipping' )
		);

		// Verify that the provider update was called with fallback for shipping (US not supported) but still germany-only for billing as that is still DE.
		expect(
			mockSetActiveAddressAutocompleteProvider
		).toHaveBeenLastCalledWith( 'fallback', 'shipping' );

		renderHook( () => useUpdatePreferredAutocompleteProvider( 'billing' ) );
		expect(
			mockSetActiveAddressAutocompleteProvider
		).toHaveBeenLastCalledWith( 'germany-only', 'billing' );

		// Verify active provider on window was changed too
		expect( window.wc.addressAutocomplete.activeProvider.billing ).toBe(
			window.wc.addressAutocomplete.providers[ 'germany-only' ]
		);
		expect( window.wc.addressAutocomplete.activeProvider.shipping ).toBe(
			window.wc.addressAutocomplete.providers.fallback
		);
	} );
} );
