/**
 * Internal dependencies
 */
 const {
	shopper,
	merchant,
	createSimpleProduct,
	setCheckbox,
	unsetCheckbox,
	settingsPageSaveChanges,
	utils,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
 const {
	it,
	describe,
	beforeAll,
	afterAll,
} = require( '@jest/globals' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

const runCartRedirectionTest = () => {
	describe('Cart > Redirect to cart from shop', () => {
		let simplePostIdValue;
		beforeAll(async () => {
			simplePostIdValue = await createSimpleProduct();

			// Set checkbox in settings to enable cart redirection
			await merchant.login();
			await merchant.openSettings('products');
			await setCheckbox('#woocommerce_cart_redirect_after_add');
			await settingsPageSaveChanges();

			await merchant.logout();
		});

		it('can redirect user to cart from shop page', async () => {
			await shopper.goToShop();

			// Add to cart from shop page
			const addToCartXPath = `//li[contains(@class, "type-product") and a/h2[contains(text(), "${ simpleProductName }")]]` +
			'//a[contains(@class, "add_to_cart_button") and contains(@class, "ajax_add_to_cart")';
			const [ addToCartButton ] = await page.$x( addToCartXPath + ']' );
			addToCartButton.click();
			await utils.waitForTimeout( 1000 ); // to avoid flakiness

			await shopper.productIsInCart(simpleProductName);
			await shopper.removeFromCart( simplePostIdValue );
		});

		it('can redirect user to cart from detail page', async () => {
			await shopper.goToProduct(simplePostIdValue);

			// Add to cart from detail page
			await shopper.addToCart();
			await utils.waitForTimeout( 1000 ); // to avoid flakiness

			await shopper.productIsInCart(simpleProductName);
			await shopper.removeFromCart( simplePostIdValue );
		});

		afterAll(async () => {
			await merchant.login();
			await merchant.openSettings('products');
			await unsetCheckbox('#woocommerce_cart_redirect_after_add');
			await settingsPageSaveChanges();
		});
	});
};

module.exports = runCartRedirectionTest;
