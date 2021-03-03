/* eslint-disable jest/no-export, jest/no-standalone-expect */

/**
 * Internal dependencies
 */
const {
	merchant,
	uiUnblocked,
	verifyAndPublish,
	createSimpleProduct
} = require( '@woocommerce/e2e-utils' );

let productId;

const runProductEditDetailsTest = () => {
	describe('Products > Edit Product', () => {
		beforeAll(async () => {
			await merchant.login();
			productId = await createSimpleProduct();
		});

		it('can edit a product and save the changes', async () => {
			await merchant.goToProduct(productId);

			// Clear the input fields first, then add the new values
			await expect(page).toFill('#title', '');
			await expect(page).toFill('#_regular_price', '');

			await expect(page).toFill('#title', 'Awesome product');

			// Switch to text mode to work around the iframe
			await expect(page).toClick('#content-html');
			await expect(page).toFill('.wp-editor-area', 'This product is pretty awesome.');

			await expect(page).toFill('#_regular_price', '100.05');

			// Save the changes
			await expect(page).toClick('#publish');
			await verifyAndPublish('Product updated.');
			await uiUnblocked();

			// Verify the changes saved
			await expect(page).toMatchElement('#title', 'Awesome product');
			await expect(page).toMatchElement('.wp-editor-area', 'This product is pretty awesome.');
			await expect(page).toMatchElement('#_regular_price', '100.05');
		});
	});
}

module.exports = runProductEditDetailsTest;
