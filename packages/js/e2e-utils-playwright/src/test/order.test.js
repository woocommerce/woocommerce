/**
 * Internal dependencies
 */
import { getOrderIdFromUrl } from '../order';

describe( 'getOrderIdFromUrl', () => {
	it( 'should extract the order ID from a valid URL', () => {
		const page = { url: () => 'https://example.com/order-received/12345/' };
		const orderId = getOrderIdFromUrl( page );
		expect( orderId ).toBe( '12345' );
	} );

	it( 'should return undefined if the URL does not contain an order ID', () => {
		const page = { url: () => 'https://example.com/order-received/' };
		const orderId = getOrderIdFromUrl( page );
		expect( orderId ).toBeUndefined();
	} );

	it( 'should return undefined if the URL is not in the expected format', () => {
		const page = { url: () => 'https://example.com/other-page/12345/' };
		const orderId = getOrderIdFromUrl( page );
		expect( orderId ).toBeUndefined();
	} );
} );
