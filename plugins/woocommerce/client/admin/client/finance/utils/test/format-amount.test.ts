/**
 * Internal dependencies
 */
import { formatAmount } from '../format-amount';

const store = {
	getCurrencyConfig: () => ( { code: 'USD' } ),
	formatAmount: ( amount: number | string ) => `$${ amount } store`,
};

describe( 'formatAmount', () => {
	it( 'uses the store formatter for the store currency', () => {
		expect( formatAmount( '12.50', 'USD', store ) ).toBe( '$12.5 store' );
	} );

	it( 'uses the browser locale for other currencies', () => {
		const expected = new Intl.NumberFormat( undefined, {
			style: 'currency',
			currency: 'EUR',
		} ).format( 12.5 );

		expect( formatAmount( '12.50', 'EUR', store ) ).toBe( expected );
	} );

	it( 'falls back to the raw amount and code for an unknown currency', () => {
		expect( formatAmount( '12.50', 'NOPE', store ) ).toBe( '12.50 NOPE' );
	} );

	it( 'falls back to the raw amount when it is not numeric', () => {
		expect( formatAmount( 'n/a', 'USD', store ) ).toBe( 'n/a USD' );
	} );
} );
