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
			selectedAttributes: [],
		};

		jest.isolateModules( () => {
			require( '../product-context' );
		} );
	} );

	describe( 'setAttribute action', () => {
		it( 'adds new attribute when attribute does not exist', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockRegisteredStore.actions.setAttribute( 'pa_color', 'red' );

			expect( mockContext.selectedAttributes ).toHaveLength( 1 );
			expect( mockContext.selectedAttributes[ 0 ] ).toEqual( {
				attribute: 'pa_color',
				value: 'red',
			} );
		} );

		it( 'updates existing attribute when attribute already exists', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockContext.selectedAttributes = [
				{ attribute: 'pa_color', value: 'red' },
			];

			mockRegisteredStore.actions.setAttribute( 'pa_color', 'blue' );

			expect( mockContext.selectedAttributes ).toHaveLength( 1 );
			expect( mockContext.selectedAttributes[ 0 ] ).toEqual( {
				attribute: 'pa_color',
				value: 'blue',
			} );
		} );

		it( 'removes attribute when value is empty string', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockContext.selectedAttributes = [
				{ attribute: 'pa_color', value: 'red' },
				{ attribute: 'pa_size', value: 'large' },
			];

			mockRegisteredStore.actions.setAttribute( 'pa_color', '' );

			expect( mockContext.selectedAttributes ).toHaveLength( 1 );
			expect( mockContext.selectedAttributes[ 0 ] ).toEqual( {
				attribute: 'pa_size',
				value: 'large',
			} );
		} );

		it( 'does nothing when removing non-existent attribute', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockContext.selectedAttributes = [
				{ attribute: 'pa_color', value: 'red' },
			];

			mockRegisteredStore.actions.setAttribute( 'pa_size', '' );

			expect( mockContext.selectedAttributes ).toHaveLength( 1 );
			expect( mockContext.selectedAttributes[ 0 ] ).toEqual( {
				attribute: 'pa_color',
				value: 'red',
			} );
		} );

		it( 'adds multiple attributes correctly', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			mockRegisteredStore.actions.setAttribute( 'pa_color', 'red' );
			mockRegisteredStore.actions.setAttribute( 'pa_size', 'large' );
			mockRegisteredStore.actions.setAttribute( 'pa_material', 'cotton' );

			expect( mockContext.selectedAttributes ).toHaveLength( 3 );
			expect( mockContext.selectedAttributes ).toEqual( [
				{ attribute: 'pa_color', value: 'red' },
				{ attribute: 'pa_size', value: 'large' },
				{ attribute: 'pa_material', value: 'cotton' },
			] );
		} );

		it( 'handles null context gracefully', () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Store was not registered.' );
			}

			// @ts-expect-error Testing null context handling
			mockGetContext.mockReturnValueOnce( null );

			// Should not throw
			expect( () => {
				mockRegisteredStore?.actions.setAttribute( 'pa_color', 'red' );
			} ).not.toThrow();
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
