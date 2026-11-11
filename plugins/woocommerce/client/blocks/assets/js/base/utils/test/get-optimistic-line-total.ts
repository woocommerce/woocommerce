/**
 * Internal dependencies
 */
import { getOptimisticLineTotal } from '../get-optimistic-line-total';

describe( 'getOptimisticLineTotal', () => {
	// $12.50 with the 6 decimals of `raw_prices`.
	const item = {
		unitPrice: 12500000,
		precision: 6,
		minorUnit: 2,
		savedQuantity: 2,
		savedLineTotal: 2500,
	};

	it( 'returns the saved total for the saved quantity', () => {
		expect( getOptimisticLineTotal( { ...item, quantity: 2 } ) ).toBe(
			2500
		);
	} );

	it( 'multiplies the unit price by the new quantity when the pricing is linear', () => {
		expect( getOptimisticLineTotal( { ...item, quantity: 5 } ) ).toBe(
			6250
		);
		expect( getOptimisticLineTotal( { ...item, quantity: 1 } ) ).toBe(
			1250
		);
	} );

	it( 'rounds to the minor unit of the currency', () => {
		// $3.333333 each: 3 cost $10.00, as the server rounds them.
		expect(
			getOptimisticLineTotal( {
				unitPrice: 3333333,
				precision: 6,
				minorUnit: 2,
				savedQuantity: 1,
				savedLineTotal: 333,
				quantity: 3,
			} )
		).toBe( 1000 );
	} );

	it( 'supports currencies without decimals', () => {
		expect(
			getOptimisticLineTotal( {
				unitPrice: 1500000000,
				precision: 6,
				minorUnit: 0,
				savedQuantity: 1,
				savedLineTotal: 1500,
				quantity: 4,
			} )
		).toBe( 6000 );
	} );

	it( 'waits for the server when the pricing is not linear', () => {
		// A bulk discount: 2 cost $20.00, not $25.00.
		expect(
			getOptimisticLineTotal( {
				...item,
				savedLineTotal: 2000,
				quantity: 3,
			} )
		).toBe( 2000 );
	} );

	it( 'waits for the server when the unit price is unknown', () => {
		expect(
			getOptimisticLineTotal( {
				...item,
				unitPrice: Number.NaN,
				quantity: 3,
			} )
		).toBe( 2500 );
	} );
} );
