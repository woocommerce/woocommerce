/* eslint-disable jest/no-export, jest/no-standalone-expect */

/**
 * Internal dependencies
 */
const {
	merchant,
	uiUnblocked,
	verifyAndPublish,
	createSimpleProduct,
	createVariableProduct,
	withRestApi,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const { it, describe, beforeAll } = require( '@jest/globals' );

let productId;

const runProductEditDetailsTest = () => {
	describe( 'Products > Edit Product', () => {
		beforeAll( async () => {
			await merchant.login();
		} );

		beforeEach( async () => {
			productId = await createSimpleProduct();
		} );

		afterEach( async () => {
			await withRestApi.deleteProduct( productId );
		} );

		it( 'can edit a product and save the changes', async () => {
			await merchant.goToProduct( productId );

			// Clear the input fields first, then add the new values
			await expect( page ).toFill( '#title', '' );
			await expect( page ).toFill( '#_regular_price', '' );

			await expect( page ).toFill( '#title', 'Awesome product' );

			// Switch to text mode to work around the iframe
			await expect( page ).toClick( '#content-html' );
			await expect( page ).toFill(
				'.wp-editor-area',
				'This product is pretty awesome.'
			);

			await expect( page ).toFill( '#_regular_price', '100.05' );

			// Save the changes
			await verifyAndPublish( 'Product updated.' );
			await uiUnblocked();

			// Verify the changes saved
			await expect( page ).toMatchElement( '#title', 'Awesome product' );
			await expect( page ).toMatchElement(
				'.wp-editor-area',
				'This product is pretty awesome.'
			);
			await expect( page ).toMatchElement( '#_regular_price', '100.05' );
		} );
	} );

	describe( 'Products > Edit Product > Variations', () => {
		beforeAll( async () => {
			await merchant.login();
		} );

		beforeEach( async () => {
			productId = await createVariableProduct();
		} );

		afterEach( async () => {
			await withRestApi.deleteProduct( productId );
		} );

		// eslint-disable-next-line jest/expect-expect
		it( 'can edit just a single attribute of a product variation', async () => {
			const expectedVariationDetails = {
				regularPrice: '10',
			};

			await merchant.goToProduct( productId );
			await merchant.updateVariationDetails( expectedVariationDetails );

			// Wait until page is stable again after saving changes
			await uiUnblocked();

			await merchant.verifyVariationDetails( expectedVariationDetails );
		} );

		// eslint-disable-next-line jest/expect-expect
		it( 'can edit multiple attributes of a product variation', async () => {
			const expectedVariationDetails = {
				sku: 'ABCD0123',
				regularPrice: '10',
				salePrice: '8',
				weight: '2',
				length: '50',
				width: '50',
				height: '50',
				description: 'This variation is awesome!',
			};

			await merchant.goToProduct( productId );
			await merchant.updateVariationDetails( expectedVariationDetails );

			// Wait until page is stable again after saving changes
			await uiUnblocked();

			await merchant.verifyVariationDetails( expectedVariationDetails );
		} );
	} );
};

module.exports = runProductEditDetailsTest;
