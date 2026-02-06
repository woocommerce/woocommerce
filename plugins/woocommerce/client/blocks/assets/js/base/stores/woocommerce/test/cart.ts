/**
 * Internal dependencies
 */
import type { Store } from '../cart';
import {
    normalizeVariation,
    makeQueueKey,
    getInfoNoticesFromCartUpdates,
} from '../cart';

type MockStore = { state: Store[ 'state' ]; actions: Store[ 'actions' ] };

let mockRegisteredStore: MockStore | null = null;
const mockState = {
	restUrl: 'https://example.com/wp-json/',
	nonce: 'test-nonce-123',
    cart: {
        items: [],
        totals: { total_items: '0', total_items_tax: '0' },
    },
} as unknown as Store[ 'state' ];

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn(),
		store: jest.fn( ( _name, definition ) => {
			mockRegisteredStore = {
				state: mockState,
				actions: definition.actions,
			};
			return mockRegisteredStore;
		} ),
	} ),
	{ virtual: true }
);

jest.mock( '../legacy-events', () => ( {
	triggerAddedToCartEvent: jest.fn(),
} ) );

describe( 'WooCommerce Cart Interactivity API Store Helpers', () => {
    describe( 'normalizeVariation', () => {
        it( 'normalizes array variation with casing and ordering', () => {
            const variation = [
                { attribute: 'Color', value: 'Red' },
                { attribute: 'Size', value: 'Large' },
            ];
            const expected = JSON.stringify([
                { attribute: 'color', value: 'red' },
                { attribute: 'size', value: 'large' },
            ].sort((a, b) => a.attribute.localeCompare(b.attribute)));
            expect(normalizeVariation(variation)).toBe(expected);
        });

        it( 'normalizes object variation', () => {
            const variation = { Color: 'Blue', Size: 'Small' };
            const expected = JSON.stringify([
                { attribute: 'color', value: 'blue' },
                { attribute: 'size', value: 'small' },
            ].sort((a, b) => a.attribute.localeCompare(b.attribute)));
            expect(normalizeVariation(variation)).toBe(expected);
        });

        it( 'filters empty attributes and null values', () => {
            const variation = [
                { attribute: '', value: null },
                { attribute: 'Color', value: '' },
            ];
            expect(normalizeVariation(variation)).toBe('[]');
        });

        it( 'handles null/undefined', () => {
            expect(normalizeVariation(null)).toBe('');
            expect(normalizeVariation(undefined)).toBe('');
        });

        it( 'falls back to JSON.stringify on error', () => {
            const badVariation = { Color: 123 }; // non-string value, .toLowerCase() throws
            expect(normalizeVariation(badVariation as any)).toBe(JSON.stringify(badVariation));
        });
    });

    describe( 'makeQueueKey', () => {
        it( 'uses existing key if present', () => {
            const item = { key: 'server-key-123', id: 1, quantity: 1, type: 'simple' };
            expect(makeQueueKey(item)).toBe('server-key-123');
        });

        it( 'generates key from id and normalized variation', () => {
            const item = { id: 123, quantity: 1, type: 'variation', variation: [{ attribute: 'Color', value: 'Red' }] };
            const expected = '123::[{"attribute":"color","value":"red"}]';
            expect(makeQueueKey(item)).toBe(expected);
        });

        it( 'handles no variation', () => {
            const item = { id: 456, quantity: 1, type: 'simple' };
            expect(makeQueueKey(item)).toBe('456::');
        });
    });

    describe( 'getInfoNoticesFromCartUpdates', () => {
        const oldCart = {
            items: [
                { key: 'key1', id: 1, name: 'Product 1', quantity: 2 },
                { key: 'key2', id: 2, name: 'Product 2', quantity: 1 },
            ],
        } as any;

        const newCart = {
            items: [
                { key: 'key1', id: 1, name: 'Product 1', quantity: 3 }, // updated
                { key: 'key3', id: 3, name: 'Product 3', quantity: 1 }, // added
            ],
        } as any;

        it( 'detects added, updated, and deleted items', () => {
            const notices = getInfoNoticesFromCartUpdates(oldCart, newCart);

            expect(notices).toHaveLength(3);
            expect(notices[0].notice).toContain('"Product 2" was removed'); // deleted
            expect(notices[1].notice).toContain('"Product 3" was added'); // added
            expect(notices[2].notice).toContain('quantity of "Product 1" was changed to 3'); // updated
        });

        it( 'suppresses notices for pending changes', () => {
            const quantityChanges = {
                cartItemsPendingDelete: ['key2'],
                productsPendingAdd: [3], // Product 3 id
                cartItemsPendingQuantity: ['key1'],
            };

            const notices = getInfoNoticesFromCartUpdates(oldCart, newCart, quantityChanges);

            expect(notices).toHaveLength(0); // all suppressed
        });
    });
});

describe( 'WooCommerce Cart Interactivity API Store', () => {
	it( 'refreshCartItems passes cache: no-store to fetch to prevent browser caching', () => {
		const mockFetch = jest
			.fn()
			.mockResolvedValue(
				new Response(
					JSON.stringify( { items: [], totals: {}, errors: [] } )
				)
			);
		global.fetch = mockFetch;

		jest.isolateModules( () => require( '../cart' ) );

		const iterator = mockRegisteredStore?.actions.refreshCartItems();

		// Async actions are typed as void for consumers, but are actually generators internally.
		( iterator as unknown as Iterator< void > ).next();

		expect( mockFetch ).toHaveBeenCalledWith(
			'https://example.com/wp-json/wc/store/v1/cart',
			expect.objectContaining( {
				method: 'GET',
				cache: 'no-store',
			} )
		);
	} );
} );
