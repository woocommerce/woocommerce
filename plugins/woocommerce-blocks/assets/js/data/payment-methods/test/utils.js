/**
 * Internal dependencies
 */
import { orderPaymentMethods } from '../utils';

describe( 'orderPaymentMethods', () => {
	it( 'orders methods correctly', () => {
		const order = [ 'cheque', 'cod', 'bacs', 'stripe' ];
		const methods = [ 'cod', 'bacs', 'stripe', 'cheque' ];
		const orderedMethods = orderPaymentMethods( order, methods );
		expect( orderedMethods ).toStrictEqual( order );
	} );
	it( 'orders methods correctly and appends missing ones', () => {
		const order = [ 'cheque', 'cod', 'bacs', 'stripe' ];
		const methods = [ 'cod', 'paypal', 'bacs', 'stripe', 'cheque' ];
		const orderedMethods = orderPaymentMethods( order, methods );
		expect( orderedMethods ).toStrictEqual( [
			'cheque',
			'cod',
			'bacs',
			'stripe',
			'paypal',
		] );
	} );
} );
