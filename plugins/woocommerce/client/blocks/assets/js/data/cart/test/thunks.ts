/**
 * External dependencies
 */
import { ExtensionCartUpdateArgs } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	changeCartItemQuantity,
	receiveCart,
	applyExtensionCartUpdate,
} from '../thunks';
import { apiFetchWithHeaders } from '../../shared-controls';
import { getIsCustomerDataDirty } from '../utils';

jest.mock( '../../shared-controls', () => ( {
	apiFetchWithHeaders: jest.fn(),
} ) );

jest.mock( '../notify-quantity-changes', () => ( {
	notifyQuantityChanges: jest.fn(),
} ) );

jest.mock( '../notify-errors', () => ( {
	updateCartErrorNotices: jest.fn(),
} ) );

jest.mock( '../utils', () => ( {
	getIsCustomerDataDirty: jest.fn( () => false ),
	setIsCustomerDataDirty: jest.fn(),
	setTriggerStoreSyncEvent: jest.fn(),
} ) );

const mockGetIsCustomerDataDirty =
	getIsCustomerDataDirty as jest.MockedFunction<
		typeof getIsCustomerDataDirty
	>;

const mockApiFetchWithHeaders = apiFetchWithHeaders as jest.MockedFunction<
	typeof apiFetchWithHeaders
>;

describe( 'changeCartItemQuantity', () => {
	const createChangeQuantityMocks = (
		cartItems: Record< string, number >
	) => {
		const mockDispatch = {
			receiveCart: jest.fn(),
			receiveError: jest.fn(),
			itemIsPendingQuantity: jest.fn(),
		};

		const mockSelect = {
			getCartItem: jest.fn( ( key: string ) => {
				if ( key in cartItems ) {
					return { quantity: cartItems[ key ] };
				}
				return null;
			} ),
		};

		return { dispatch: mockDispatch, select: mockSelect };
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should not make API call if quantity is unchanged', async () => {
		const { dispatch, select } = createChangeQuantityMocks( {
			'item-1': 5,
		} );

		await changeCartItemQuantity(
			'item-1',
			5
		)( { dispatch, select } as never );

		expect( mockApiFetchWithHeaders ).not.toHaveBeenCalled();
		expect( dispatch.itemIsPendingQuantity ).not.toHaveBeenCalled();
	} );

	it( 'should make API call when quantity changes', async () => {
		const { dispatch, select } = createChangeQuantityMocks( {
			'item-1': 1,
		} );

		mockApiFetchWithHeaders.mockResolvedValueOnce( {
			response: { items: [ { key: 'item-1', quantity: 5 } ] },
		} );

		await changeCartItemQuantity(
			'item-1',
			5
		)( { dispatch, select } as never );

		expect( mockApiFetchWithHeaders ).toHaveBeenCalledTimes( 1 );
		expect( mockApiFetchWithHeaders ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: '/wc/store/v1/cart/update-item',
				method: 'POST',
				data: {
					key: 'item-1',
					quantity: 5,
				},
				signal: expect.any( AbortSignal ),
			} )
		);
		expect( dispatch.itemIsPendingQuantity ).toHaveBeenCalledWith(
			'item-1'
		);
		expect( dispatch.itemIsPendingQuantity ).toHaveBeenCalledWith(
			'item-1',
			false
		);
	} );

	it( 'should abort previous request when same item quantity changes again', async () => {
		const cartItems: Record< string, number > = { 'item-1': 1 };

		const mockDispatch = {
			receiveCart: jest.fn(),
			receiveError: jest.fn(),
			itemIsPendingQuantity: jest.fn(),
		};

		const mockSelect = {
			getCartItem: jest.fn( ( key: string ) => {
				if ( key in cartItems ) {
					return { quantity: cartItems[ key ] };
				}
				return null;
			} ),
		};

		// Track abort signals
		const abortSignals: AbortSignal[] = [];

		// First request is slow
		mockApiFetchWithHeaders.mockImplementation(
			( options: { signal?: AbortSignal } ) => {
				if ( options.signal ) {
					abortSignals.push( options.signal );
				}
				return new Promise( ( resolve, reject ) => {
					// Check if already aborted
					if ( options.signal?.aborted ) {
						const error = new DOMException(
							'Aborted',
							'AbortError'
						);
						reject( error );
						return;
					}
					// Listen for abort
					options.signal?.addEventListener( 'abort', () => {
						const error = new DOMException(
							'Aborted',
							'AbortError'
						);
						reject( error );
					} );
					// Resolve after delay if not aborted
					setTimeout( () => {
						resolve( {
							response: {
								items: [ { key: 'item-1', quantity: 5 } ],
							},
						} );
					}, 100 );
				} );
			}
		);

		// Start first request (1→5)
		const promise1 = changeCartItemQuantity(
			'item-1',
			5
		)( { dispatch: mockDispatch, select: mockSelect } as never );

		// Start second request before first completes (should abort first)
		const promise2 = changeCartItemQuantity(
			'item-1',
			10
		)( { dispatch: mockDispatch, select: mockSelect } as never );

		await Promise.all( [ promise1, promise2 ] );

		// First signal should be aborted
		expect( abortSignals[ 0 ].aborted ).toBe( true );
		// Second signal should not be aborted
		expect( abortSignals[ 1 ].aborted ).toBe( false );

		// receiveCart should only be called once (for the second request)
		expect( mockDispatch.receiveCart ).toHaveBeenCalledTimes( 1 );
		// receiveError should NOT be called for aborted requests
		expect( mockDispatch.receiveError ).not.toHaveBeenCalled();
	} );

	it( 'should not abort requests for different items', async () => {
		const cartItems: Record< string, number > = {
			'item-a': 1,
			'item-b': 1,
		};

		const mockDispatch = {
			receiveCart: jest.fn(),
			receiveError: jest.fn(),
			itemIsPendingQuantity: jest.fn(),
		};

		const mockSelect = {
			getCartItem: jest.fn( ( key: string ) => {
				if ( key in cartItems ) {
					return { quantity: cartItems[ key ] };
				}
				return null;
			} ),
		};

		const abortSignals: AbortSignal[] = [];

		mockApiFetchWithHeaders.mockImplementation(
			( options: { signal?: AbortSignal } ) => {
				if ( options.signal ) {
					abortSignals.push( options.signal );
				}
				return Promise.resolve( {
					response: { items: [] },
				} );
			}
		);

		// Change different items
		const promise1 = changeCartItemQuantity(
			'item-a',
			5
		)( { dispatch: mockDispatch, select: mockSelect } as never );
		const promise2 = changeCartItemQuantity(
			'item-b',
			3
		)( { dispatch: mockDispatch, select: mockSelect } as never );

		await Promise.all( [ promise1, promise2 ] );

		// Neither should be aborted - they're different items
		expect( abortSignals[ 0 ].aborted ).toBe( false );
		expect( abortSignals[ 1 ].aborted ).toBe( false );

		// Both should complete
		expect( mockApiFetchWithHeaders ).toHaveBeenCalledTimes( 2 );
		expect( mockDispatch.receiveCart ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'should handle API errors', async () => {
		const { dispatch, select } = createChangeQuantityMocks( {
			'item-1': 1,
		} );

		mockApiFetchWithHeaders.mockRejectedValueOnce(
			new Error( 'Network error' )
		);

		await expect(
			changeCartItemQuantity(
				'item-1',
				5
			)( { dispatch, select } as never )
		).rejects.toThrow( 'Network error' );

		expect( dispatch.receiveError ).toHaveBeenCalledTimes( 1 );
		expect( dispatch.itemIsPendingQuantity ).toHaveBeenCalledWith(
			'item-1',
			false
		);
	} );
} );

describe( 'receiveCart', () => {
	const createReceiveCartMocks = ( {
		cartItems,
		pendingDelete,
	}: {
		cartItems: Array< { key: string } >;
		pendingDelete: string[];
	} ) => {
		let cartData = { items: cartItems, errors: [] as never[] };

		const mockDispatch = {
			setCartData: jest.fn( ( newCart ) => {
				cartData = newCart;
			} ),
			itemIsPendingDelete: jest.fn(),
			setErrorData: jest.fn(),
		};

		const mockSelect = {
			getCartData: jest.fn( () => cartData ),
			getCartErrors: jest.fn( () => [] ),
			getItemsPendingDelete: jest.fn( () => pendingDelete ),
			getItemsPendingQuantityUpdate: jest.fn( () => [] ),
			getProductsPendingAdd: jest.fn( () => [] ),
		};

		return { dispatch: mockDispatch, select: mockSelect };
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should clear pending delete for items removed server-side (e.g. bundle children)', () => {
		// Simulate: parent bundle "bundle-parent" was deleted by the user.
		// Its children "bundle-child-1" and "bundle-child-2" were marked
		// as pending delete by the extension but removed server-side
		// when the parent was deleted.
		const { dispatch, select } = createReceiveCartMocks( {
			cartItems: [
				{ key: 'bundle-parent' },
				{ key: 'bundle-child-1' },
				{ key: 'bundle-child-2' },
				{ key: 'simple-product' },
			],
			pendingDelete: [
				'bundle-parent',
				'bundle-child-1',
				'bundle-child-2',
			],
		} );

		// The API response after removing the parent no longer contains
		// the parent or its children — only the simple product remains.
		receiveCart( {
			items: [ { key: 'simple-product' } ],
			errors: [],
		} as never )( { dispatch, select } as never );

		// All three pending-delete items are gone from the cart,
		// so their pending status should be cleared.
		expect( dispatch.itemIsPendingDelete ).toHaveBeenCalledWith(
			'bundle-parent',
			false
		);
		expect( dispatch.itemIsPendingDelete ).toHaveBeenCalledWith(
			'bundle-child-1',
			false
		);
		expect( dispatch.itemIsPendingDelete ).toHaveBeenCalledWith(
			'bundle-child-2',
			false
		);
		expect( dispatch.itemIsPendingDelete ).toHaveBeenCalledTimes( 3 );
	} );

	it( 'should not clear pending delete for items still in the cart', () => {
		// An item is pending delete but still present in the response
		// (e.g. the API call hasn't finished processing yet).
		const { dispatch, select } = createReceiveCartMocks( {
			cartItems: [ { key: 'item-1' }, { key: 'item-2' } ],
			pendingDelete: [ 'item-1' ],
		} );

		receiveCart( {
			items: [ { key: 'item-1' }, { key: 'item-2' } ],
			errors: [],
		} as never )( { dispatch, select } as never );

		// item-1 is still in the cart, so pending delete should NOT be cleared.
		expect( dispatch.itemIsPendingDelete ).not.toHaveBeenCalled();
	} );

	it( 'should handle empty pending delete list', () => {
		const { dispatch, select } = createReceiveCartMocks( {
			cartItems: [ { key: 'item-1' } ],
			pendingDelete: [],
		} );

		receiveCart( {
			items: [ { key: 'item-1' } ],
			errors: [],
		} as never )( { dispatch, select } as never );

		expect( dispatch.itemIsPendingDelete ).not.toHaveBeenCalled();
	} );
} );

describe( 'applyExtensionCartUpdate', () => {
	const mockResponse = {
		items: [],
		shipping_address: { address_1: '123 Ship St' },
		billing_address: { address_1: '456 Bill Ave' },
		totals: { total_price: '1000' },
	};

	const createMockDispatch = () => ( {
		receiveCart: jest.fn(),
		receiveError: jest.fn(),
	} );

	beforeEach( () => {
		jest.clearAllMocks();
		mockGetIsCustomerDataDirty.mockReturnValue( false );
		mockApiFetchWithHeaders.mockResolvedValue( {
			response: mockResponse,
		} );
	} );

	it( 'should include both addresses when customer data is not dirty', async () => {
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
		} )( { dispatch } as never );

		expect( dispatch.receiveCart ).toHaveBeenCalledWith( mockResponse );
	} );

	it( 'should strip both addresses when customer data is dirty and no overwrite specified', async () => {
		mockGetIsCustomerDataDirty.mockReturnValue( true );
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
		} )( { dispatch } as never );

		const received = dispatch.receiveCart.mock.calls[ 0 ][ 0 ];
		expect( received ).not.toHaveProperty( 'shipping_address' );
		expect( received ).not.toHaveProperty( 'billing_address' );
		expect( received ).toHaveProperty( 'totals' );
	} );

	it( 'should strip both addresses when customer data is dirty and overwriteDirtyCustomerData is false', async () => {
		mockGetIsCustomerDataDirty.mockReturnValue( true );
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
			overwriteDirtyCustomerData: false,
		} )( { dispatch } as never );

		const received = dispatch.receiveCart.mock.calls[ 0 ][ 0 ];
		expect( received ).not.toHaveProperty( 'shipping_address' );
		expect( received ).not.toHaveProperty( 'billing_address' );
		expect( received ).toHaveProperty( 'totals' );
	} );

	it( 'should include both addresses when overwriteDirtyCustomerData is true', async () => {
		mockGetIsCustomerDataDirty.mockReturnValue( true );
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
			overwriteDirtyCustomerData: true,
		} )( { dispatch } as never );

		const received = dispatch.receiveCart.mock.calls[ 0 ][ 0 ];
		expect( received ).toHaveProperty( 'shipping_address' );
		expect( received ).toHaveProperty( 'billing_address' );
	} );

	it( 'should overwrite only shipping_address when specified as object', async () => {
		mockGetIsCustomerDataDirty.mockReturnValue( true );
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
			overwriteDirtyCustomerData: { shipping_address: true },
		} )( { dispatch } as never );

		const received = dispatch.receiveCart.mock.calls[ 0 ][ 0 ];
		expect( received.shipping_address ).toEqual( {
			address_1: '123 Ship St',
		} );
		expect( received ).not.toHaveProperty( 'billing_address' );
		expect( received ).toHaveProperty( 'totals' );
	} );

	it( 'should overwrite only billing_address when specified as object', async () => {
		mockGetIsCustomerDataDirty.mockReturnValue( true );
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
			overwriteDirtyCustomerData: { billing_address: true },
		} )( { dispatch } as never );

		const received = dispatch.receiveCart.mock.calls[ 0 ][ 0 ];
		expect( received ).not.toHaveProperty( 'shipping_address' );
		expect( received.billing_address ).toEqual( {
			address_1: '456 Bill Ave',
		} );
	} );

	it( 'should overwrite both addresses when both specified in object', async () => {
		mockGetIsCustomerDataDirty.mockReturnValue( true );
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
			overwriteDirtyCustomerData: {
				shipping_address: true,
				billing_address: true,
			},
		} )( { dispatch } as never );

		const received = dispatch.receiveCart.mock.calls[ 0 ][ 0 ];
		expect( received.shipping_address ).toEqual( {
			address_1: '123 Ship St',
		} );
		expect( received.billing_address ).toEqual( {
			address_1: '456 Bill Ave',
		} );
	} );

	it( 'should strip both addresses when object has explicit false flags', async () => {
		mockGetIsCustomerDataDirty.mockReturnValue( true );
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
			overwriteDirtyCustomerData: {
				shipping_address: false,
				billing_address: false,
			},
		} )( { dispatch } as never );

		const received = dispatch.receiveCart.mock.calls[ 0 ][ 0 ];
		expect( received ).not.toHaveProperty( 'shipping_address' );
		expect( received ).not.toHaveProperty( 'billing_address' );
		expect( received ).toHaveProperty( 'totals' );
	} );

	it( 'should overwrite specified address even when customer data is not dirty', async () => {
		mockGetIsCustomerDataDirty.mockReturnValue( false );
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
			overwriteDirtyCustomerData: { shipping_address: true },
		} )( { dispatch } as never );

		const received = dispatch.receiveCart.mock.calls[ 0 ][ 0 ];
		// shipping_address should be included (overwrite requested)
		expect( received.shipping_address ).toEqual( {
			address_1: '123 Ship St',
		} );
		// billing_address should also be included (data is not dirty, no reason to strip)
		expect( received.billing_address ).toEqual( {
			address_1: '456 Bill Ave',
		} );
	} );

	it( 'should treat null as false (no overwrite)', async () => {
		mockGetIsCustomerDataDirty.mockReturnValue( true );
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
			overwriteDirtyCustomerData:
				null as unknown as ExtensionCartUpdateArgs[ 'overwriteDirtyCustomerData' ],
		} )( { dispatch } as never );

		const received = dispatch.receiveCart.mock.calls[ 0 ][ 0 ];
		expect( received ).not.toHaveProperty( 'shipping_address' );
		expect( received ).not.toHaveProperty( 'billing_address' );
	} );

	it( 'should treat an array as false (no overwrite)', async () => {
		mockGetIsCustomerDataDirty.mockReturnValue( true );
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
			overwriteDirtyCustomerData: [
				true,
			] as unknown as ExtensionCartUpdateArgs[ 'overwriteDirtyCustomerData' ],
		} )( { dispatch } as never );

		const received = dispatch.receiveCart.mock.calls[ 0 ][ 0 ];
		expect( received ).not.toHaveProperty( 'shipping_address' );
		expect( received ).not.toHaveProperty( 'billing_address' );
	} );

	it( 'should treat non-boolean address fields as false', async () => {
		mockGetIsCustomerDataDirty.mockReturnValue( true );
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
			overwriteDirtyCustomerData: {
				shipping_address: 'yes',
				billing_address: 1,
			} as unknown as ExtensionCartUpdateArgs[ 'overwriteDirtyCustomerData' ],
		} )( { dispatch } as never );

		const received = dispatch.receiveCart.mock.calls[ 0 ][ 0 ];
		expect( received ).not.toHaveProperty( 'shipping_address' );
		expect( received ).not.toHaveProperty( 'billing_address' );
	} );

	it( 'should default missing address fields to false', async () => {
		mockGetIsCustomerDataDirty.mockReturnValue( true );
		const dispatch = createMockDispatch();

		await applyExtensionCartUpdate( {
			namespace: 'test',
			data: {},
			overwriteDirtyCustomerData: {},
		} )( { dispatch } as never );

		const received = dispatch.receiveCart.mock.calls[ 0 ][ 0 ];
		expect( received ).not.toHaveProperty( 'shipping_address' );
		expect( received ).not.toHaveProperty( 'billing_address' );
	} );
} );
