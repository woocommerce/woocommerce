/**
 * Internal dependencies
 */
import { getProductStatus } from '../get-product-status';

describe( 'getProductStatus', () => {
	it( 'should return unsaved when no product exists', () => {
		const status = getProductStatus( undefined );
		expect( status ).toBe( 'unsaved' );
	} );

	it( 'should return draft when the status is set to draft', () => {
		const status = getProductStatus( {
			id: 123,
			status: 'draft',
			stock_status: 'instock',
		} );
		expect( status ).toBe( 'draft' );
	} );

	it( 'should return draft when the status is set to draft', () => {
		const status = getProductStatus( {
			id: 123,
			status: 'publish',
			stock_status: 'instock',
		} );
		expect( status ).toBe( 'instock' );
	} );

	it( 'should return draft when the status is set to draft', () => {
		const status = getProductStatus( {
			id: 123,
			status: 'publish',
			stock_status: 'outofstock',
		} );
		expect( status ).toBe( 'outofstock' );
	} );
} );
