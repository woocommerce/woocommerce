/**
 * Internal dependencies
 */
import { getProductErrorMessage, WPError } from '../get-product-error-message';

describe( 'getProductErrorMessage', () => {
	it( 'should return the correct error message when one exists', () => {
		const error = {
			code: 'product_invalid_sku',
		} as WPError;
		const message = getProductErrorMessage( error );
		expect( message ).toBe( 'Invalid or duplicated SKU.' );
	} );

	it( 'should return a default message when the error code is not mapped', () => {
		const error = {} as WPError;
		const message = getProductErrorMessage( error );
		expect( message ).toBe( 'Failed to save product.' );
	} );
} );
