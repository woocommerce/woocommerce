/**
 * Internal dependencies
 */
import {
	createAddItemCommand,
	createRemoveItemCommand,
	createUpdateQuantityCommand,
	createBatchAddItemCommands,
	isCartItem,
	type CartState,
} from '../cart-commands';

// Mock cart state factory
function createMockCartState(
	overrides: Partial< CartState > = {}
): CartState {
	return {
		items: [],
		coupons: [],
		shippingRates: [],
		shippingAddress: {
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
		billingAddress: {
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
		itemsCount: 0,
		itemsWeight: 0,
		crossSells: [],
		needsPayment: true,
		needsShipping: true,
		hasCalculatedShipping: false,
		fees: [],
		totals: {
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
			total_items: '0',
			total_items_tax: '0',
			total_fees: '0',
			total_fees_tax: '0',
			total_discount: '0',
			total_discount_tax: '0',
			total_shipping: '0',
			total_shipping_tax: '0',
			total_price: '0',
			total_tax: '0',
			tax_lines: [],
		},
		errors: [],
		paymentMethods: [],
		paymentRequirements: [],
		extensions: {},
		...overrides,
	};
}

// Create a full cart item (as returned by server)
function createMockCartItem( overrides = {} ) {
	return {
		key: 'item-key-123',
		id: 1,
		type: 'simple',
		quantity: 1,
		catalog_visibility: 'visible' as const,
		quantity_limits: {
			minimum: 1,
			maximum: 99,
			multiple_of: 1,
			editable: true,
		},
		name: 'Test Product',
		summary: 'Test summary',
		short_description: 'Short desc',
		description: 'Long desc',
		sku: 'TEST-001',
		low_stock_remaining: null,
		backorders_allowed: false,
		show_backorder_badge: false,
		sold_individually: false,
		permalink: 'https://example.com/product/test',
		images: [],
		variation: [],
		prices: {
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
			price: '1000',
			regular_price: '1000',
			sale_price: '1000',
			price_range: null,
			raw_prices: {
				precision: 6,
				price: '1000000',
				regular_price: '1000000',
				sale_price: '1000000',
			},
		},
		totals: {
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
			line_subtotal: '1000',
			line_subtotal_tax: '0',
			line_total: '1000',
			line_total_tax: '0',
		},
		extensions: {},
		item_data: [],
		...overrides,
	};
}

describe( 'Cart Commands', () => {
	const TEST_NONCE = 'test-nonce-123';

	describe( 'isCartItem', () => {
		it( 'should return true for full cart items', () => {
			const cartItem = createMockCartItem();
			expect( isCartItem( cartItem ) ).toBe( true );
		} );

		it( 'should return false for optimistic items', () => {
			const optimisticItem = {
				id: 1,
				quantity: 1,
				type: 'simple',
			};
			expect( isCartItem( optimisticItem ) ).toBe( false );
		} );
	} );

	describe( 'createAddItemCommand', () => {
		it( 'should create a command with unique ID', () => {
			const command1 = createAddItemCommand(
				{ id: 1, quantity: 1 },
				TEST_NONCE
			);
			const command2 = createAddItemCommand(
				{ id: 1, quantity: 1 },
				TEST_NONCE
			);

			expect( command1.id ).toMatch( /^cmd-\d+-\w+$/ );
			expect( command2.id ).toMatch( /^cmd-\d+-\w+$/ );
			expect( command1.id ).not.toBe( command2.id );
		} );

		it( 'should have type "add-item"', () => {
			const command = createAddItemCommand(
				{ id: 1, quantity: 2 },
				TEST_NONCE
			);
			expect( command.type ).toBe( 'add-item' );
		} );

		it( 'should clone payload to prevent external mutations', () => {
			const payload = { id: 1, quantity: 2 };
			const command = createAddItemCommand( payload, TEST_NONCE );

			payload.quantity = 99;

			expect( command.payload.quantity ).toBe( 2 );
		} );

		describe( 'optimistic updates', () => {
			it( 'should add new item to empty cart', () => {
				const command = createAddItemCommand(
					{ id: 42, quantity: 3, type: 'simple' },
					TEST_NONCE
				);
				const state = createMockCartState();

				command.applyOptimistic( state );

				expect( state.items ).toHaveLength( 1 );
				expect( state.items[ 0 ] ).toMatchObject( {
					id: 42,
					quantity: 3,
					type: 'simple',
				} );
			} );

			it( 'should update existing item quantity', () => {
				const command = createAddItemCommand(
					{ id: 1, quantity: 5 },
					TEST_NONCE
				);
				const existingItem = createMockCartItem( {
					id: 1,
					quantity: 2,
				} );
				const state = createMockCartState( {
					items: [ existingItem ],
				} );

				command.applyOptimistic( state );

				expect( state.items ).toHaveLength( 1 );
				expect( state.items[ 0 ].quantity ).toBe( 5 );
			} );

			it( 'should not update quantity for sold_individually items', () => {
				const command = createAddItemCommand(
					{ id: 1, quantity: 5 },
					TEST_NONCE
				);
				const existingItem = createMockCartItem( {
					id: 1,
					quantity: 1,
					sold_individually: true,
				} );
				const state = createMockCartState( {
					items: [ existingItem ],
				} );

				command.applyOptimistic( state );

				expect( state.items ).toHaveLength( 1 );
				// Quantity stays at 1 due to sold_individually
				expect( state.items[ 0 ].quantity ).toBe( 1 );
			} );

			it( 'should handle variation items', () => {
				const command = createAddItemCommand(
					{
						id: 10,
						quantity: 2,
						type: 'variation',
						variation: [
							{ attribute: 'Color', value: 'Red' },
							{ attribute: 'Size', value: 'Large' },
						],
					},
					TEST_NONCE
				);
				const state = createMockCartState();

				command.applyOptimistic( state );

				expect( state.items ).toHaveLength( 1 );
				expect( state.items[ 0 ] ).toMatchObject( {
					id: 10,
					quantity: 2,
					type: 'variation',
				} );
				expect( state.items[ 0 ].variation ).toHaveLength( 2 );
			} );
		} );

		describe( 'buildRequest', () => {
			it( 'should build add-item request for new items', () => {
				const command = createAddItemCommand(
					{ id: 1, quantity: 2 },
					TEST_NONCE
				);
				const state = createMockCartState();

				// Apply optimistic to set the isUpdate flag
				command.applyOptimistic( state );

				const request = command.buildRequest();

				expect( request.method ).toBe( 'POST' );
				expect( request.path ).toBe( '/wc/store/v1/cart/add-item' );
				expect( request.headers ).toEqual( { Nonce: TEST_NONCE } );
				expect( request.body ).toEqual( { id: 1, quantity: 2 } );
			} );

			it( 'should build update-item request for existing items', () => {
				const command = createAddItemCommand(
					{ id: 1, quantity: 5 },
					TEST_NONCE
				);
				const existingItem = createMockCartItem( {
					key: 'existing-key',
					id: 1,
					quantity: 2,
				} );
				const state = createMockCartState( {
					items: [ existingItem ],
				} );

				// Apply optimistic to set the isUpdate flag
				command.applyOptimistic( state );

				const request = command.buildRequest();

				expect( request.path ).toBe( '/wc/store/v1/cart/update-item' );
				expect( request.body ).toEqual( {
					key: 'existing-key',
					quantity: 5,
				} );
			} );

			it( 'should include variation in add-item request', () => {
				const command = createAddItemCommand(
					{
						id: 10,
						quantity: 1,
						variation: [ { attribute: 'Color', value: 'Blue' } ],
					},
					TEST_NONCE
				);
				const state = createMockCartState();
				command.applyOptimistic( state );

				const request = command.buildRequest();

				expect( request.body ).toEqual( {
					id: 10,
					quantity: 1,
					variation: [ { attribute: 'Color', value: 'Blue' } ],
				} );
			} );
		} );

		describe( 'transformResponse', () => {
			it( 'should return cart state from valid response', () => {
				const command = createAddItemCommand(
					{ id: 1, quantity: 1 },
					TEST_NONCE
				);
				const response = { items: [], totals: {} };

				const result = command.transformResponse?.( response );

				expect( result ).toBe( response );
			} );

			it( 'should return null for invalid response', () => {
				const command = createAddItemCommand(
					{ id: 1, quantity: 1 },
					TEST_NONCE
				);

				expect( command.transformResponse?.( null ) ).toBeNull();
				expect( command.transformResponse?.( 'string' ) ).toBeNull();
				expect(
					command.transformResponse?.( { error: true } )
				).toBeNull();
			} );
		} );
	} );

	describe( 'createRemoveItemCommand', () => {
		it( 'should create a command with type "remove-item"', () => {
			const command = createRemoveItemCommand(
				{ key: 'item-key' },
				TEST_NONCE
			);
			expect( command.type ).toBe( 'remove-item' );
		} );

		describe( 'optimistic updates', () => {
			it( 'should remove item from cart', () => {
				const command = createRemoveItemCommand(
					{ key: 'to-remove' },
					TEST_NONCE
				);
				const state = createMockCartState( {
					items: [
						createMockCartItem( { key: 'keep-1', id: 1 } ),
						createMockCartItem( { key: 'to-remove', id: 2 } ),
						createMockCartItem( { key: 'keep-2', id: 3 } ),
					],
				} );

				command.applyOptimistic( state );

				expect( state.items ).toHaveLength( 2 );
				expect( state.items.map( ( i ) => i.key ) ).toEqual( [
					'keep-1',
					'keep-2',
				] );
			} );

			it( 'should handle non-existent key gracefully', () => {
				const command = createRemoveItemCommand(
					{ key: 'non-existent' },
					TEST_NONCE
				);
				const state = createMockCartState( {
					items: [ createMockCartItem( { key: 'existing' } ) ],
				} );

				// Should not throw
				command.applyOptimistic( state );

				expect( state.items ).toHaveLength( 1 );
			} );
		} );

		describe( 'buildRequest', () => {
			it( 'should build remove-item request', () => {
				const command = createRemoveItemCommand(
					{ key: 'item-to-remove' },
					TEST_NONCE
				);

				const request = command.buildRequest();

				expect( request.method ).toBe( 'POST' );
				expect( request.path ).toBe( '/wc/store/v1/cart/remove-item' );
				expect( request.headers ).toEqual( { Nonce: TEST_NONCE } );
				expect( request.body ).toEqual( { key: 'item-to-remove' } );
			} );
		} );
	} );

	describe( 'createUpdateQuantityCommand', () => {
		it( 'should create a command with type "update-quantity"', () => {
			const command = createUpdateQuantityCommand(
				{ key: 'item-key', quantity: 5 },
				TEST_NONCE
			);
			expect( command.type ).toBe( 'update-quantity' );
		} );

		describe( 'optimistic updates', () => {
			it( 'should update item quantity', () => {
				const command = createUpdateQuantityCommand(
					{ key: 'item-key', quantity: 10 },
					TEST_NONCE
				);
				const state = createMockCartState( {
					items: [
						createMockCartItem( { key: 'item-key', quantity: 2 } ),
					],
				} );

				command.applyOptimistic( state );

				expect( state.items[ 0 ].quantity ).toBe( 10 );
			} );

			it( 'should handle non-existent key gracefully', () => {
				const command = createUpdateQuantityCommand(
					{ key: 'non-existent', quantity: 5 },
					TEST_NONCE
				);
				const state = createMockCartState( {
					items: [ createMockCartItem( { key: 'other-key' } ) ],
				} );

				// Should not throw
				command.applyOptimistic( state );

				// Original item unchanged
				expect( state.items[ 0 ].quantity ).toBe( 1 );
			} );
		} );

		describe( 'buildRequest', () => {
			it( 'should build update-item request', () => {
				const command = createUpdateQuantityCommand(
					{ key: 'item-key', quantity: 7 },
					TEST_NONCE
				);

				const request = command.buildRequest();

				expect( request.method ).toBe( 'POST' );
				expect( request.path ).toBe( '/wc/store/v1/cart/update-item' );
				expect( request.body ).toEqual( {
					key: 'item-key',
					quantity: 7,
				} );
			} );
		} );
	} );

	describe( 'createBatchAddItemCommands', () => {
		it( 'should create multiple commands for batch items', () => {
			const items = [
				{ id: 1, quantity: 2, type: 'simple' },
				{ id: 2, quantity: 1, type: 'simple' },
				{ id: 3, quantity: 5, type: 'simple' },
			];

			const commands = createBatchAddItemCommands( items, TEST_NONCE );

			expect( commands ).toHaveLength( 3 );
			commands.forEach( ( cmd, i ) => {
				expect( cmd.type ).toBe( 'add-item' );
				expect( cmd.payload.id ).toBe( items[ i ].id );
				expect( cmd.payload.quantity ).toBe( items[ i ].quantity );
			} );
		} );

		it( 'should return unique IDs for each command', () => {
			const items = [
				{ id: 1, quantity: 1, type: 'simple' },
				{ id: 2, quantity: 1, type: 'simple' },
			];

			const commands = createBatchAddItemCommands( items, TEST_NONCE );
			const ids = commands.map( ( c ) => c.id );

			expect( new Set( ids ).size ).toBe( ids.length );
		} );

		it( 'should handle empty array', () => {
			const commands = createBatchAddItemCommands( [], TEST_NONCE );
			expect( commands ).toHaveLength( 0 );
		} );
	} );
} );
