/**
 * Internal dependencies
 */
import { changeCartItemQuantity } from '../thunks';
import { apiFetchWithHeaders } from '../../shared-controls';

jest.mock( '../../shared-controls', () => ( {
	apiFetchWithHeaders: jest.fn(),
} ) );

const mockApiFetchWithHeaders = apiFetchWithHeaders as jest.MockedFunction<
	typeof apiFetchWithHeaders
>;

describe( 'changeCartItemQuantity', () => {
	const createMockDispatchAndSelect = (
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
		const { dispatch, select } = createMockDispatchAndSelect( {
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
		const { dispatch, select } = createMockDispatchAndSelect( {
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
		const { dispatch, select } = createMockDispatchAndSelect( {
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
