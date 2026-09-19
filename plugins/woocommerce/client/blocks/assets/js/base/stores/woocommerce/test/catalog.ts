/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { CatalogState } from '../types';
import { normalizeAttributeName, attributeNamesMatch } from '../catalog';

// The acknowledgement string the module's `store()` call passes. Copied
// here rather than imported, so the test fails if the source value ever
// drifts from what the other stores in this folder pass.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

let mockRegisteredStore: { state: CatalogState } | null = null;

let mockStoreState: CatalogState;

const getMockStoreState = (): CatalogState => {
	if ( mockRegisteredStore === null ) {
		throw new Error( 'Expected the woocommerce store to be registered.' );
	}
	return mockRegisteredStore.state;
};

const mockProduct = {
	id: 42,
	name: 'Test Product',
} as ProductResponseItem;

const mockVariation = {
	id: 99,
	name: 'Test Variation',
} as ProductResponseItem;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( namespace, definition ) => {
			if ( namespace === 'woocommerce' ) {
				// Simulate the real library's behaviour: every `store()`
				// call for the same namespace merges its state onto one
				// shared object and returns the same registered store, so a
				// later call passing the shared acknowledgement string still
				// resolves it.
				mockRegisteredStore ??= { state: {} as CatalogState };
				if ( definition?.state ) {
					// `Object.defineProperties` (not `Object.assign`)
					// preserves accessor properties (e.g. the scope layer's
					// `productScope` getter) as live getters instead of
					// invoking them once and freezing the result.
					Object.defineProperties(
						mockRegisteredStore.state,
						Object.getOwnPropertyDescriptors( definition.state )
					);
				}
				return mockRegisteredStore;
			}
			return {};
		} ),
	} ),
	{ virtual: true }
);

describe( 'woocommerce store — catalog layer', () => {
	beforeEach( () => {
		mockRegisteredStore = null;
		( store as jest.Mock ).mockClear();

		jest.isolateModules( () => require( '../index' ) );
		mockStoreState = getMockStoreState();
	} );

	describe( 'store registration', () => {
		it( 'registers the woocommerce store exactly once, with the shared acknowledgement string', () => {
			const woocommerceCalls = ( store as jest.Mock ).mock.calls.filter(
				( [ namespace ] ) => namespace === 'woocommerce'
			);

			expect( woocommerceCalls ).toHaveLength( 1 );
			expect( woocommerceCalls[ 0 ][ 2 ] ).toEqual( {
				lock: universalLock,
			} );
		} );

		it( 'still resolves the same store from a later store( "woocommerce", {}, { lock } ) call using that string', () => {
			mockStoreState.products = { 42: mockProduct };

			const { state: laterState } = store< { state: CatalogState } >(
				'woocommerce',
				{},
				{ lock: universalLock }
			);

			expect( laterState.products[ 42 ] ).toBe( mockProduct );
		} );
	} );

	describe( 'state.products and state.productVariations', () => {
		it( 'reads back a server-seeded product by id', () => {
			mockStoreState.products = { 42: mockProduct };

			expect( mockStoreState.products[ 42 ] ).toBe( mockProduct );
		} );

		it( 'reads undefined for a product id the server never seeded', () => {
			mockStoreState.products = { 42: mockProduct };

			expect( mockStoreState.products[ 999 ] ).toBeUndefined();
		} );

		it( 'reads back a server-seeded variation by id', () => {
			mockStoreState.productVariations = { 99: mockVariation };

			expect( mockStoreState.productVariations[ 99 ] ).toBe(
				mockVariation
			);
		} );

		it( 'reads undefined for a variation id the server never seeded', () => {
			mockStoreState.productVariations = { 99: mockVariation };

			expect( mockStoreState.productVariations[ 999 ] ).toBeUndefined();
		} );
	} );

	describe( 'state.template', () => {
		it( 'reads back the server-seeded productId and variation', () => {
			mockStoreState.template = {
				productId: 42,
				variation: [
					{ attribute: 'attribute_pa_color', value: 'Blue' },
					{ attribute: 'attribute_pa_size', value: 'Medium' },
				],
			};

			expect( mockStoreState.template.productId ).toBe( 42 );
			expect( mockStoreState.template.variation ).toEqual( [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
				{ attribute: 'attribute_pa_size', value: 'Medium' },
			] );
		} );

		it( 'defaults to no product and an empty variation before the server seeds it', () => {
			expect( mockStoreState.template.productId ).toBe( 0 );
			expect( mockStoreState.template.variation ).toEqual( [] );
		} );
	} );
} );

describe( 'normalizeAttributeName', () => {
	it( 'strips the attribute_ and attribute_pa_ prefixes', () => {
		expect( normalizeAttributeName( 'attribute_pa_color' ) ).toBe(
			'color'
		);
		expect( normalizeAttributeName( 'attribute_size' ) ).toBe( 'size' );
	} );

	it( 'replaces hyphens with spaces and lowercases the result', () => {
		expect( normalizeAttributeName( 'Numeric-Size' ) ).toBe(
			'numeric size'
		);
	} );
} );

describe( 'attributeNamesMatch', () => {
	it( 'matches names that normalize to the same value', () => {
		expect( attributeNamesMatch( 'attribute_pa_color', 'Color' ) ).toBe(
			true
		);
	} );

	it( 'does not match names that normalize differently', () => {
		expect( attributeNamesMatch( 'Color', 'Size' ) ).toBe( false );
	} );
} );
