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

	it( 'should return null when no error message exists', () => {
		const error = {
			code: 'unanticipated_error_code',
		} as WPError;
		const status = getProductErrorMessage( error );
		expect( status ).toBeNull();
	} );
} );
