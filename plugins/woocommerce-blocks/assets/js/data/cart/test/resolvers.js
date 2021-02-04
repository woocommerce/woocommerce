/**
 * Internal dependencies
 */
import { getCartData } from '../resolvers';
import { receiveCart, receiveError } from '../actions';
import { CART_API_ERROR } from '../constants';

jest.mock( '@wordpress/data-controls' );

describe( 'getCartData', () => {
	describe( 'yields with expected responses', () => {
		let fulfillment;
		const rewind = () => ( fulfillment = getCartData() );
		test(
			'when apiFetch returns a valid response, yields expected ' +
				'action',
			() => {
				rewind();
				fulfillment.next( 'https://example.org' );
				const { value } = fulfillment.next( {
					coupons: [],
					items: [],
					fees: [],
					itemsCount: 0,
					itemsWeight: 0,
					needsShipping: true,
					totals: {},
				} );
				expect( value ).toEqual(
					receiveCart( {
						coupons: [],
						items: [],
						fees: [],
						itemsCount: 0,
						itemsWeight: 0,
						needsShipping: true,
						totals: {},
					} )
				);
				const { done } = fulfillment.next();
				expect( done ).toBe( true );
			}
		);
	} );
	describe( 'yields with expected response when there is an error', () => {
		let fulfillment;
		const rewind = () => ( fulfillment = getCartData() );
		test(
			'when apiFetch returns a valid response, yields expected ' +
				'action',
			() => {
				rewind();
				fulfillment.next( 'https://example.org' );
				const { value } = fulfillment.next( undefined );
				expect( value ).toEqual( receiveError( CART_API_ERROR ) );
				const { done } = fulfillment.next();
				expect( done ).toBe( true );
			}
		);
	} );
} );
