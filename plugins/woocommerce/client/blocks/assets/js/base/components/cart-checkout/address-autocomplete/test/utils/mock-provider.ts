/**
 * A mock address autocomplete provider for testing.
 *
 * Returns deterministic results and exposes jest spies on all methods
 * so tests can assert on search/select calls.
 */

/**
 * External dependencies
 */
import type { ClientAddressAutocompleteProvider } from '@woocommerce/types';

export const MOCK_PROVIDER_ID = 'mock-test-provider';

export const MOCK_SEARCH_RESULTS = [
	{
		label: '123 Example St, Berlin, Germany',
		id: 'result-1',
		matchedSubstrings: [ { length: 3, offset: 0 } ],
	},
	{
		label: '456 Sample Rd, Munich, Germany',
		id: 'result-2',
		matchedSubstrings: [ { length: 3, offset: 0 } ],
	},
];

export const MOCK_SELECTED_ADDRESS = {
	address_1: '123 Example St',
	address_2: '',
	city: 'Berlin',
	state: 'BE',
	postcode: '10115',
	country: 'DE',
};

export interface MockProvider extends ClientAddressAutocompleteProvider {
	search: jest.Mock;
	select: jest.Mock;
	canSearch: jest.Mock;
}

/**
 * Creates a fresh mock provider with jest spies. Call this in beforeEach
 * to get isolated spy state per test.
 */
export function createMockProvider(): MockProvider {
	return {
		id: MOCK_PROVIDER_ID,
		canSearch: jest.fn().mockReturnValue( true ),
		search: jest.fn().mockResolvedValue( MOCK_SEARCH_RESULTS ),
		select: jest.fn().mockResolvedValue( MOCK_SELECTED_ADDRESS ),
	};
}

/**
 * Installs the given mock provider onto window.wc.addressAutocomplete
 * as the active provider for both billing and shipping.
 */
export function installMockProvider( provider: MockProvider ): void {
	window.wc = {
		...( window.wc || {} ),
		addressAutocomplete: {
			providers: { [ MOCK_PROVIDER_ID ]: provider },
			activeProvider: {
				billing: provider,
				shipping: provider,
			},
			registerAddressAutocompleteProvider( p ) {
				return !! p;
			},
		},
	};
}
