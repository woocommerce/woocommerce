/**
 * External dependencies
 */
import * as wpData from '@wordpress/data';
import type {
	ActionCreatorsOf,
	ConfigOf,
} from '@wordpress/data/build-types/types';

// Mock all problematic dependencies first - MUST be before any imports
jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	register: jest.fn(),
	subscribe: jest.fn(),
	createReduxStore: jest.fn(),
	dispatch: jest.fn(),
	select: jest.fn(),
} ) );

jest.mock( '@wordpress/data-controls', () => ( {
	controls: {},
} ) );

// Mock all internal dependencies
jest.mock( '../constants', () => ( {
	STORE_KEY: 'wc/store/checkout',
} ) );

jest.mock( '../selectors', () => ( {} ) );
jest.mock( '../actions', () => ( {} ) );
jest.mock( '../reducers', () => jest.fn() );
jest.mock( '../push-changes', () => ( {
	pushChanges: jest.fn(),
	flushChanges: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import { updateAutocompleteProvider } from '../autocomplete-utils';
import type { CheckoutStoreDescriptor } from '../index';

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

wpData.dispatch.mockImplementation( ( storeName ) => {
	if ( storeName === 'wc/store/checkout' ) {
		return {
			setActiveAddressAutocompleteProvider:
				mockSetActiveAddressAutocompleteProvider,
		};
	}
	return {};
} );

/**
 * This is in a separate file as doing it in `index` led to an overly complicated set of mocks. Doing it here allows the test to be isolated.
 */
describe( 'Autocomplete country change handler', () => {
	it( 'should update provider when country changes', () => {
		const checkoutActions = wpData.dispatch(
			'wc/store/checkout'
		) as ActionCreatorsOf< ConfigOf< CheckoutStoreDescriptor > >;
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

		updateAutocompleteProvider( checkoutActions, 'shipping', 'FR' );
		updateAutocompleteProvider( checkoutActions, 'billing', 'FR' );

		// Verify that the provider update was called with a fallback provider as germany-only only works for DE.
		expect( mockSetActiveAddressAutocompleteProvider ).toHaveBeenCalledWith(
			'fallback',
			'shipping'
		);
		expect( mockSetActiveAddressAutocompleteProvider ).toHaveBeenCalledWith(
			'fallback',
			'billing'
		);

		// Check active provider on window was changed too.
		expect( window.wc.addressAutocomplete.activeProvider.shipping ).toBe(
			window.wc.addressAutocomplete.providers.fallback
		);
		expect( window.wc.addressAutocomplete.activeProvider.billing ).toBe(
			window.wc.addressAutocomplete.providers.fallback
		);

		updateAutocompleteProvider( checkoutActions, 'shipping', 'DE' );

		// Verify that the provider update was called with germany-only for shipping.
		expect(
			mockSetActiveAddressAutocompleteProvider
		).toHaveBeenLastCalledWith( 'germany-only', 'shipping' );

		// Verify active provider on window was changed too
		expect( window.wc.addressAutocomplete.activeProvider.shipping ).toBe(
			window.wc.addressAutocomplete.providers[ 'germany-only' ]
		);
	} );
} );
