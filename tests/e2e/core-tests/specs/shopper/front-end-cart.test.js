/**
 * Internal dependencies
 */
const {
	shopper,
	withRestApi,
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

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const singleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';
const twoProductPrice = singleProductPrice * 2;

const runCartPageTest = () => {
	describe('Cart page', () => {
		let productId;

		beforeAll(async () => {
			productId = await createSimpleProduct();
			await withRestApi.resetSettingsGroupToDefault('general');
			await withRestApi.resetSettingsGroupToDefault('products');
			await withRestApi.resetSettingsGroupToDefault('tax');
		});

		it('should display no item in the cart', async () => {
			await shopper.goToCart();
			await expect(page).toMatchElement('.cart-empty', {text: 'Your cart is currently empty.'});
		});

		it('should add the product to the cart from the shop page', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );

			await shopper.goToCart();
			await shopper.productIsInCart(simpleProductName);
		});

		it('should increase item qty when "Add to cart" of the same product is clicked', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );

			await shopper.goToCart();
			await shopper.productIsInCart(simpleProductName, 2);
		});

		it('should update qty when updated via qty input', async () => {
			await shopper.goToCart();
			await shopper.setCartQuantity(simpleProductName, 4);
			await expect(page).toClick('button', {text: 'Update cart'});
			await uiUnblocked();

			await shopper.productIsInCart(simpleProductName, 4);
		});

		it('should remove the item from the cart when remove is clicked', async () => {
			await shopper.goToCart();
			await shopper.removeFromCart( productId );
			await uiUnblocked();

			await expect(page).toMatchElement('.cart-empty', {text: 'Your cart is currently empty.'});
		});

		it('should update subtotal in cart totals when adding product to the cart', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );

			await shopper.goToCart();
			await shopper.productIsInCart(simpleProductName, 1);
			await expect(page).toMatchElement('.cart-subtotal .amount', {text: `$${ singleProductPrice }`});

			await shopper.setCartQuantity(simpleProductName, 2);
			await expect(page).toClick('button', {text: 'Update cart'});
			await uiUnblocked();

			await expect(page).toMatchElement('.cart-subtotal .amount', {text: `$${ twoProductPrice }`});
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
