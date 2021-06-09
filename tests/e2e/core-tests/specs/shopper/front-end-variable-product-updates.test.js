/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	shopper,
	createVariableProduct,
} = require( '@woocommerce/e2e-utils' );
const config = require('config');

let variablePostIdValue;

const cartDialogMessage = 'Please select some product options before adding this product to your cart.';
const attributes = config.get( 'products.variable.attributes' )

const runVariableProductUpdateTest = () => {
	describe('Shopper > Update variable product',() => {
		beforeAll(async () => {
			variablePostIdValue = await createVariableProduct();
		});

		it('shopper can change variable attributes to the same value', async () => {
			await shopper.goToProduct(variablePostIdValue);

			for (const a of attributes) {
				const { name, options } = a;
				const attrHTMLId = `#${name.toLowerCase()}`;

				await expect(page).toSelect(attrHTMLId, options[0]);
			}

			await expect(page).toMatchElement('.woocommerce-variation-price', {
				text: '9.99'
			});
		});

		it('shopper can change attributes to combination with dimensions and weight', async () => {
			await shopper.goToProduct(variablePostIdValue);
			await expect(page).toSelect(
				`#${attributes[0].name.toLowerCase()}`,
				attributes[0].options[0]
			);
			await expect(page).toSelect(
				`#${attributes[1].name.toLowerCase()}`,
				attributes[1].options[1]
			);
			await expect(page).toSelect(
				`#${attributes[2].name.toLowerCase()}`,
				attributes[2].options[0]
			);

			await expect(page).toMatchElement('.woocommerce-variation-price', { text: '20.00' });
			await expect(page).toMatchElement('.woocommerce-variation-availability', { text: 'Out of stock' });
			await expect(page).toMatchElement('.woocommerce-product-attributes-item--weight', { text: '200 kg' });
			await expect(page).toMatchElement('.woocommerce-product-attributes-item--dimensions', { text: '10 × 20 × 15 cm' });

		});

		it('shopper can change variable product attributes to variation with a different price', async () => {
			await shopper.goToProduct(variablePostIdValue);
			await expect(page).toSelect(
				`#${attributes[0].name.toLowerCase()}`,
				attributes[0].options[0]
			);
			await expect(page).toSelect(
				`#${attributes[1].name.toLowerCase()}`,
				attributes[1].options[0]
			);
			await expect(page).toSelect(
				`#${attributes[2].name.toLowerCase()}`,
				attributes[2].options[1]
			);

			await expect(page).toMatchElement('.woocommerce-variation-price', { text: '11.99' });
		});

		it('shopper can reset variations', async () => {
			await shopper.goToProduct(variablePostIdValue);
			await expect(page).toSelect(
				`#${attributes[0].name.toLowerCase()}`,
				attributes[0].options[0]
			);
			await expect(page).toSelect(
				`#${attributes[1].name.toLowerCase()}`,
				attributes[1].options[1]
			);
			await expect(page).toSelect(
				`#${attributes[2].name.toLowerCase()}`,
				attributes[2].options[0]
			);

			await expect(page).toClick('.reset_variations');

			// Verify the reset by attempting to add the product to the cart
			const couponDialog = await expect(page).toDisplayDialog(async () => {
					await expect(page).toClick('.single_add_to_cart_button');
				});

			expect(couponDialog.message()).toMatch(cartDialogMessage);
		});

	});
	
};

module.exports = runVariableProductUpdateTest;
