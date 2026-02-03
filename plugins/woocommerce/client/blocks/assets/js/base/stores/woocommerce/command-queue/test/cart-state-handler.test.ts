/**
 * Cart State Handler Tests
 *
 * Tests the StateHandler implementation that bridges the Command Queue
 * with the WordPress Interactivity API cart store.
 */

import {
	createCartStateHandler,
	trackQuantityChange,
	type CartStateHandlerConfig,
} from '../cart-state-handler';
import type { CartState } from '../cart-commands';
import type { BatchResult } from '../types';
import type { CartItem, CartResponseTotals } from '@woocommerce/types';

/**
 * Create a minimal cart state for testing.
 */
function createMockCartState(
	items: ( CartItem | { id: number; quantity: number; type?: string } )[] = []
): CartState {
	return {
		items: items.map( ( item, index ) => ( {
			key: `item-${ index }`,
			id: item.id,
			quantity: item.quantity,
			type: 'type' in item ? item.type || 'simple' : 'simple',
		} ) ),
		coupons: [],
		fees: [],
		totals: {
			currency_code: 'USD',
			currency_minor_unit: 2,
			currency_prefix: '$',
			currency_suffix: '',
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			total_items: '1000',
			total_items_tax: '0',
			total_fees: '0',
			total_fees_tax: '0',
			total_discount: '0',
			total_discount_tax: '0',
			total_shipping: '0',
			total_shipping_tax: '0',
			total_tax: '0',
			total_price: '1000',
			tax_lines: [],
		} as CartResponseTotals,
		shipping_address: {
			first_name: '',
			last_name: '',
			company: '',
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
			phone: '',
		},
		billing_address: {
			first_name: '',
			last_name: '',
			company: '',
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
			phone: '',
			email: '',
		},
		needs_payment: true,
		needs_shipping: true,
		has_calculated_shipping: false,
		shipping_rates: [],
		items_count: items.reduce( ( sum, item ) => sum + item.quantity, 0 ),
		items_weight: 0,
		cross_sells: [],
		errors: [],
		payment_methods: [],
		payment_requirements: [],
		extensions: {},
	};
}

describe( 'createCartStateHandler', () => {
	let mockCartState: CartState;
	let mockShowErrorNotice: jest.Mock;
	let mockUpdateNotices: jest.Mock;
	let config: CartStateHandlerConfig;

	beforeEach( () => {
		jest.clearAllMocks();
		mockCartState = createMockCartState( [
			{ id: 1, quantity: 2 },
			{ id: 2, quantity: 1 },
		] );
		mockShowErrorNotice = jest.fn();
		mockUpdateNotices = jest.fn();
		config = {
			getCartState: () => mockCartState,
			setCartState: ( cart ) => {
				mockCartState = cart;
			},
			// Inject mock implementations to avoid dynamic imports
			showErrorNotice: mockShowErrorNotice,
			updateNotices: mockUpdateNotices,
		};
	} );

	describe( 'getState', () => {
		it( 'should return current cart state', () => {
			const handler = createCartStateHandler( config );
			expect( handler.getState() ).toBe( mockCartState );
		} );

		it( 'should return updated state after changes', () => {
			const handler = createCartStateHandler( config );
			const newCart = createMockCartState( [ { id: 3, quantity: 5 } ] );
			mockCartState = newCart;
			expect( handler.getState() ).toBe( newCart );
		} );
	} );

	describe( 'setState', () => {
		it( 'should update cart state via setCartState callback', () => {
			const handler = createCartStateHandler( config );
			const newCart = createMockCartState( [ { id: 3, quantity: 5 } ] );

			handler.setState( newCart );

			expect( mockCartState ).toBe( newCart );
		} );
	} );

	describe( 'cloneState', () => {
		it( 'should create a deep clone of cart state', () => {
			const handler = createCartStateHandler( config );
			const cloned = handler.cloneState?.( mockCartState );

			expect( cloned ).not.toBe( mockCartState );
			expect( cloned ).toEqual( mockCartState );
		} );

		it( 'should not share references with original', () => {
			const handler = createCartStateHandler( config );
			const cloned = handler.cloneState?.( mockCartState );

			// Modify the clone
			if ( cloned ) {
				cloned.items[ 0 ].quantity = 999;
			}

			// Original should be unchanged
			expect( mockCartState.items[ 0 ].quantity ).toBe( 2 );
		} );
	} );

	describe( 'onProcessingStart', () => {
		it( 'should be defined', () => {
			const handler = createCartStateHandler( config );
			expect( handler.onProcessingStart ).toBeDefined();
		} );

		it( 'should not throw when called', () => {
			const handler = createCartStateHandler( config );
			expect( () => handler.onProcessingStart?.() ).not.toThrow();
		} );
	} );

	describe( 'onProcessingEnd', () => {
		it( 'should be defined', () => {
			const handler = createCartStateHandler( config );
			expect( handler.onProcessingEnd ).toBeDefined();
		} );

		it( 'should call onCartUpdated when successful', () => {
			const onCartUpdated = jest.fn();
			const handler = createCartStateHandler( {
				...config,
				onCartUpdated,
			} );

			handler.onProcessingStart?.();

			const result: BatchResult< CartState > = {
				results: new Map(),
				finalState: mockCartState,
				hasErrors: false,
				errors: [],
			};

			handler.onProcessingEnd?.( result );

			expect( onCartUpdated ).toHaveBeenCalledWith(
				expect.any( Object )
			);
		} );

		it( 'should not call onCartUpdated when there are errors', () => {
			const onCartUpdated = jest.fn();
			const handler = createCartStateHandler( {
				...config,
				onCartUpdated,
			} );

			handler.onProcessingStart?.();

			const result: BatchResult< CartState > = {
				results: new Map(),
				finalState: null,
				hasErrors: true,
				errors: [ new Error( 'Test error' ) ],
			};

			handler.onProcessingEnd?.( result );

			expect( onCartUpdated ).not.toHaveBeenCalled();
		} );

		it( 'should not call onCartUpdated when finalState is null', () => {
			const onCartUpdated = jest.fn();
			const handler = createCartStateHandler( {
				...config,
				onCartUpdated,
			} );

			handler.onProcessingStart?.();

			const result: BatchResult< CartState > = {
				results: new Map(),
				finalState: null,
				hasErrors: false,
				errors: [],
			};

			handler.onProcessingEnd?.( result );

			expect( onCartUpdated ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'onErrors', () => {
		it( 'should be defined', () => {
			const handler = createCartStateHandler( config );
			expect( handler.onErrors ).toBeDefined();
		} );

		it( 'should call showErrorNotice for each error', () => {
			const handler = createCartStateHandler( config );
			const errors = [
				new Error( 'Test error 1' ),
				new Error( 'Test error 2' ),
			];

			handler.onErrors?.( errors );

			expect( mockShowErrorNotice ).toHaveBeenCalledTimes( 2 );
			expect( mockShowErrorNotice ).toHaveBeenCalledWith(
				errors[ 0 ],
				undefined
			);
			expect( mockShowErrorNotice ).toHaveBeenCalledWith(
				errors[ 1 ],
				undefined
			);
		} );

		it( 'should pass errorMessages to showErrorNotice', () => {
			const errorMessages = { custom_code: 'Custom message' };
			const handler = createCartStateHandler( {
				...config,
				errorMessages,
			} );
			const errors = [ new Error( 'Test error' ) ];

			handler.onErrors?.( errors );

			expect( mockShowErrorNotice ).toHaveBeenCalledWith(
				errors[ 0 ],
				errorMessages
			);
		} );
	} );

	describe( 'showCartUpdatesNotices option', () => {
		it( 'should not call updateNotices when showCartUpdatesNotices=false', () => {
			const onCartUpdated = jest.fn();
			const handler = createCartStateHandler( {
				...config,
				showCartUpdatesNotices: false,
				onCartUpdated,
			} );

			handler.onProcessingStart?.();

			const result: BatchResult< CartState > = {
				results: new Map(),
				finalState: mockCartState,
				hasErrors: false,
				errors: [],
			};

			handler.onProcessingEnd?.( result );

			// updateNotices should not be called when showCartUpdatesNotices is false
			expect( mockUpdateNotices ).not.toHaveBeenCalled();
			// onCartUpdated should still be called
			expect( onCartUpdated ).toHaveBeenCalled();
		} );

		it( 'should default showCartUpdatesNotices to true', () => {
			const handler = createCartStateHandler( config );

			// Just verify it's created without error
			expect( handler ).toBeDefined();
		} );
	} );
} );

describe( 'trackQuantityChange', () => {
	it( 'should track add operations', () => {
		const changes = trackQuantityChange( 'add', 123 );

		expect( changes ).toEqual( {
			productsPendingAdd: [ 123 ],
		} );
	} );

	it( 'should track remove operations', () => {
		const changes = trackQuantityChange( 'remove', 'item-key-123' );

		expect( changes ).toEqual( {
			cartItemsPendingDelete: [ 'item-key-123' ],
		} );
	} );

	it( 'should track update operations', () => {
		const changes = trackQuantityChange( 'update', 'item-key-456' );

		expect( changes ).toEqual( {
			cartItemsPendingQuantity: [ 'item-key-456' ],
		} );
	} );
} );
