/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	createSimpleProduct,
	createVariableProduct,
	uiUnblocked
} = require( '@woocommerce/e2e-utils' );

let simplePostIdValue;
let variablePostIdValue;
const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

const runSingleProductPageTest = () => {
	describe('Single Product Page', () => {
		beforeAll(async () => {
			await merchant.login();
			simplePostIdValue = await createSimpleProduct();
			await merchant.logout();
		});

		it('should be able to add simple products to the cart', async () => {
			// Add 5 simple products to cart
			await shopper.goToProduct(simplePostIdValue);
			await expect(page).toFill('div.quantity input.qty', '5');
			await shopper.addToCart();
			await expect(page).toMatchElement('.woocommerce-message', {text: 'have been added to your cart.'});

			// Verify cart contents
			await shopper.goToCart();
			await shopper.productIsInCart(simpleProductName, 5);

			// Remove items from cart
			await shopper.removeFromCart(simpleProductName);
			await uiUnblocked();
			await expect(page).toMatchElement('.cart-empty', {text: 'Your cart is currently empty.'});
		});
	});

	describe.skip('Variable Product Page', () => {
		beforeAll(async () => {
			await merchant.login();
			variablePostIdValue = await createVariableProduct();
			await merchant.logout();
		});

		it('should be able to add variation products to the cart', async () => {
			// Add a product with one set of variations to cart
			await shopper.goToProduct(variablePostIdValue);
			await expect(page).toSelect('#attr-1', 'val1');
			await expect(page).toSelect('#attr-2', 'val1');
			await expect(page).toSelect('#attr-3', 'val1');
			await shopper.addToCart();
			await expect(page).toMatchElement('.woocommerce-message', {text: 'has been added to your cart.'});

			// Verify cart contents
			await shopper.goToCart();
			await shopper.productIsInCart('Variable Product with Three Variations');

			// Remove items from cart
			await shopper.removeFromCart('Variable Product with Three Variations');
			await uiUnblocked();
			await expect(page).toMatchElement('.cart-empty', {text: 'Your cart is currently empty.'});
		});
	});
};

module.exports = runSingleProductPageTest;
