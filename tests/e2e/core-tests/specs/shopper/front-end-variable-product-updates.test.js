/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	createVariableProduct,
} = require( '@woocommerce/e2e-utils' );

let variablePostIdValue;

const cartDialogMessage = 'Please select some product options before adding this product to your cart.';

const runVariableProductUpdateTest = () => {
	describe('Shopper > Update variable product',() => {
		beforeAll(async () => {
			await merchant.login();
			variablePostIdValue = await createVariableProduct();
			await merchant.logout();
		});

		it('shopper can change variable attributes to the same value', async () => {
			await shopper.goToProduct(variablePostIdValue);
			await expect(page).toSelect('#attr-1', 'val1');
			await expect(page).toSelect('#attr-2', 'val1');
			await expect(page).toSelect('#attr-3', 'val1');

			await expect(page).toMatchElement('.woocommerce-variation-price', { text: '9.99' });
		});

		it('shopper can change attributes to combination with dimensions and weight', async () => {
			await shopper.goToProduct(variablePostIdValue);
			await expect(page).toSelect('#attr-1', 'val1');
			await expect(page).toSelect('#attr-2', 'val2');
			await expect(page).toSelect('#attr-3', 'val1');

			await expect(page).toMatchElement('.woocommerce-variation-price', { text: '20.00' });
			await expect(page).toMatchElement('.woocommerce-variation-availability', { text: 'Out of stock' });
			await expect(page).toMatchElement('.woocommerce-product-attributes-item--weight', { text: '200 kg' });
			await expect(page).toMatchElement('.woocommerce-product-attributes-item--dimensions', { text: '10 × 20 × 15 cm' });
		});

		it('shopper can change variable product attributes to variation with a different price', async () => {
			await shopper.goToProduct(variablePostIdValue);
			await expect(page).toSelect('#attr-1', 'val1');
			await expect(page).toSelect('#attr-2', 'val1');
			await expect(page).toSelect('#attr-3', 'val2');

			await expect(page).toMatchElement('.woocommerce-variation-price', { text: '11.99' });
		});

		it('shopper can reset variations', async () => {
			await shopper.goToProduct(variablePostIdValue);
			await expect(page).toSelect('#attr-1', 'val1');
			await expect(page).toSelect('#attr-2', 'val2');
			await expect(page).toSelect('#attr-3', 'val1');

			await expect(page).toClick('.reset_variations');

			// Verify the reset by attempting to add the product to the cart
			const couponDialog = await expect(page).toDisplayDialog(async () => {
				await expect(page).toClick('.single_add_to_cart_button');
			});

			expect(couponDialog.message()).toMatch(cartDialogMessage);

			// Accept the dialog
			await couponDialog.accept();
		});

	});

};

module.exports = runVariableProductUpdateTest;
