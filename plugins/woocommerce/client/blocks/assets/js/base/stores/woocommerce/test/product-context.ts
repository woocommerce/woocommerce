/**
 * Internal dependencies
 */
import type {
	ProductContextStore,
	ProductContextContext,
} from '../product-context';

let mockContext: ProductContextContext;

const mockGetContext = jest.fn( () => mockContext );

let mockRegisteredStore: {
	state: ProductContextStore[ 'state' ];
	actions: ProductContextStore[ 'actions' ];
} | null = null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: mockGetContext,
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
		mockGetContext.mockClear();
		mockRegisteredStore = null;
		mockContext = {
			productId: 123,
			variationId: null,
		};

		jest.isolateModules( () => {
			require( '../product-context' );
		} );
	} );

	describe( 'setProductId action', () => {
		it( 'sets the product ID in context', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockRegisteredStore.actions.setProductId( 456 );

			expect( mockContext.productId ).toBe( 456 );
		} );
	} );

	describe( 'setVariationId action', () => {
		it( 'sets the variation ID in context', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockRegisteredStore.actions.setVariationId( 789 );

			expect( mockContext.variationId ).toBe( 789 );
		} );

		it( 'sets variation ID to null', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockContext.variationId = 789;

			mockRegisteredStore.actions.setVariationId( null );

			expect( mockContext.variationId ).toBeNull();
		} );
	} );
} );
