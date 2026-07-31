/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { getCheckoutData } from '../resolvers';
import { isEditor } from '../../utils/is-editor';
import { processErrorResponse } from '../../utils';

jest.mock( '@wordpress/data-controls' );
jest.mock( '@wordpress/api-fetch' );

jest.mock( '../../utils/is-editor', () => ( {
	isEditor: jest.fn( () => false ),
} ) );

jest.mock( '../../utils', () => ( {
	processErrorResponse: jest.fn(),
} ) );

const mockReceiveCartContents = jest.fn();
jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn( () => ( {
		receiveCartContents: mockReceiveCartContents,
	} ) ),
} ) );

jest.mock( '../../cart', () => ( {
	CART_STORE_KEY: 'wc/store/cart',
} ) );

const setCartHashCookie = ( present: boolean ) => {
	if ( present ) {
		document.cookie = 'woocommerce_cart_hash=abc123';
	} else {
		document.cookie =
			'woocommerce_cart_hash=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
	}
};

const makeDispatch = () => ( {
	receiveCheckoutData: jest.fn(),
} );

const makeSelect = ( orderId: number ) => () => ( {
	getOrderId: () => orderId,
} );

describe( 'getCheckoutData resolver', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		setCartHashCookie( true );
		( isEditor as jest.Mock ).mockReturnValue( false );
	} );

	it( 'dispatches preview data and skips the request in the editor', async () => {
		( isEditor as jest.Mock ).mockReturnValue( true );
		const dispatch = makeDispatch();

		await getCheckoutData()( {
			// @ts-expect-error partial dispatch for test
			dispatch,
			select: makeSelect( 0 ),
		} );

		expect( dispatch.receiveCheckoutData ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch ).not.toHaveBeenCalled();
	} );

	it( 'skips the request when an order is already hydrated', async () => {
		const dispatch = makeDispatch();

		await getCheckoutData()( {
			// @ts-expect-error partial dispatch for test
			dispatch,
			select: makeSelect( 5 ),
		} );

		expect( apiFetch ).not.toHaveBeenCalled();
		expect( dispatch.receiveCheckoutData ).not.toHaveBeenCalled();
	} );

	it( 'skips the request when the cart hash cookie is absent', async () => {
		setCartHashCookie( false );
		const dispatch = makeDispatch();

		await getCheckoutData()( {
			// @ts-expect-error partial dispatch for test
			dispatch,
			select: makeSelect( 0 ),
		} );

		expect( apiFetch ).not.toHaveBeenCalled();
		expect( dispatch.receiveCheckoutData ).not.toHaveBeenCalled();
	} );

	it( 'receives checkout data and pushes the experimental cart to the cart store', async () => {
		const response = {
			order_id: 0,
			customer_id: 0,
			__experimentalCart: { items: [], totals: {} },
		};
		( apiFetch as unknown as jest.Mock ).mockResolvedValue( response );
		const dispatch = makeDispatch();

		await getCheckoutData()( {
			// @ts-expect-error partial dispatch for test
			dispatch,
			select: makeSelect( 0 ),
		} );

		expect( dispatch.receiveCheckoutData ).toHaveBeenCalledWith( response );
		expect( mockReceiveCartContents ).toHaveBeenCalledWith(
			response.__experimentalCart
		);
	} );

	it( 'routes fetch failures through processErrorResponse', async () => {
		const error = { code: 'boom', message: 'Boom' };
		( apiFetch as unknown as jest.Mock ).mockRejectedValue( error );
		const dispatch = makeDispatch();

		await getCheckoutData()( {
			// @ts-expect-error partial dispatch for test
			dispatch,
			select: makeSelect( 0 ),
		} );

		expect( processErrorResponse ).toHaveBeenCalledWith( error );
		expect( dispatch.receiveCheckoutData ).not.toHaveBeenCalled();
		expect( console ).toHaveErrored();
	} );
} );
