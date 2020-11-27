/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	createSimpleProduct,
	uiUnblocked
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const runCartPageTest = () => {
	describe('Cart page', () => {
		beforeAll(async () => {
			await merchant.login();
			await createSimpleProduct();
			await merchant.logout();
		});

		it('should display no item in the cart', async () => {
			await shopper.goToCart();
			await expect(page).toMatchElement('.cart-empty', {text: 'Your cart is currently empty.'});
		});

		it('should add the product to the cart when "Add to cart" is clicked', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage('Simple product');

			await shopper.goToCart();
			await shopper.productIsInCart('Simple product');
		});

		it('should increase item qty when "Add to cart" of the same product is clicked', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage('Simple product');

			await shopper.goToCart();
			await shopper.productIsInCart('Simple product', 2);
		});

		it('should update qty when updated via qty input', async () => {
			await shopper.goToCart();
			await shopper.setCartQuantity('Simple product', 4);
			await expect(page).toClick('button', {text: 'Update cart'});
			await uiUnblocked();

			await shopper.productIsInCart('Simple product', 4);
		});

		it('should remove the item from the cart when remove is clicked', async () => {
			await shopper.goToCart();
			await shopper.removeFromCart('Simple product');
			await uiUnblocked();

			await expect(page).toMatchElement('.cart-empty', {text: 'Your cart is currently empty.'});
		});

		it('should update subtotal in cart totals when adding product to the cart', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage('Simple product');

			await shopper.goToCart();
			await shopper.productIsInCart('Simple product', 1);
			await expect(page).toMatchElement('.cart-subtotal .amount', {text: '$9.99'});

			await shopper.setCartQuantity('Simple product', 2);
			await expect(page).toClick('button', {text: 'Update cart'});
			await uiUnblocked();

			await expect(page).toMatchElement('.cart-subtotal .amount', {text: '$19.98'});
		});

		it('should go to the checkout page when "Proceed to Checkout" is clicked', async () => {
			await shopper.goToCart();
			await Promise.all([
				page.waitForNavigation({waitUntil: 'networkidle0'}),
				expect(page).toClick('.checkout-button', {text: 'Proceed to checkout'}),
			]);

			await expect(page).toMatchElement('#order_review');
		});
	});
};

module.exports = runCartPageTest;
