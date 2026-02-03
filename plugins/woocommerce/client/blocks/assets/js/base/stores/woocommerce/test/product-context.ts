/**
 * Internal dependencies
 */
import type { ProductContextStore } from '../product-context';

let mockRegisteredStore: {
	state: ProductContextStore[ 'state' ];
	actions: ProductContextStore[ 'actions' ];
} | null = null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( _name, definition ) => {
			mockRegisteredStore = {
				state: definition.state,
				actions: definition.actions,
			};
			return mockRegisteredStore;
		} ),
	} ),
	{ virtual: true }
);

// Mock the products store import
jest.mock( '../products', () => ( {} ), { virtual: true } );

describe( 'Product Context Interactivity API Store', () => {
	beforeEach( () => {
		jest.resetModules();
		mockRegisteredStore = null;

		jest.isolateModules( () => {
			require( '../product-context' );
		} );
	} );

	describe( 'setProductId action', () => {
		it( 'sets the product ID in state', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockRegisteredStore.actions.setProductId( 456 );

			expect( mockRegisteredStore.state.productId ).toBe( 456 );
		} );
	} );

	describe( 'setVariationId action', () => {
		it( 'sets the variation ID in state', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockRegisteredStore.actions.setVariationId( 789 );

			expect( mockRegisteredStore.state.variationId ).toBe( 789 );
		} );

		it( 'sets variation ID to null', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockRegisteredStore.state.variationId = 789;

			mockRegisteredStore.actions.setVariationId( null );

			expect( mockRegisteredStore.state.variationId ).toBeNull();
		} );
	} );
} );
