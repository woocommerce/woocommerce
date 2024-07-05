/**
 * Internal dependencies
 */
import {
	getProductErrorMessageAndProps,
	WPError,
} from '../get-product-error-message-and-props';

describe( 'getProductErrorMessageAndProps.message', () => {
	it( 'should return the correct error message and props when exists and the field is visible', () => {
		const error = {
			code: 'product_invalid_sku',
		} as WPError;
		const visibleTab = 'inventory';
		const { message, errorProps } = getProductErrorMessageAndProps(
			error,
			visibleTab
		);
		expect( message ).toBe( 'Invalid or duplicated SKU.' );
		expect( errorProps.explicitDismiss ).toBeFalsy();
	} );

	it( 'should return the correct error message and props when exists and the field is not visible', () => {
		const error = {
			code: 'product_invalid_sku',
		} as WPError;
		const visibleTab = 'general';
		const { message, errorProps } = getProductErrorMessageAndProps(
			error,
			visibleTab
		);
		expect( message ).toBe( 'Invalid or duplicated SKU.' );
		expect( errorProps.explicitDismiss ).toBeTruthy();
	} );

	it( 'should return a default message and props when the error code is not mapped', () => {
		const error = {} as WPError;
		const visibleTab = 'general';
		const { message, errorProps } = getProductErrorMessageAndProps(
			error,
			visibleTab
		);
		expect( message ).toBe( 'Failed to save product.' );
		expect( errorProps.explicitDismiss ).toBeFalsy();
	} );
} );
