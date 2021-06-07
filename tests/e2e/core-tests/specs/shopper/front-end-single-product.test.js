/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	createSimpleProduct,
	createVariableProduct,
	createGroupedProduct,
	uiUnblocked
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );

// Variables for simple product
const simpleProductName = config.get( 'products.simple.name' );
let simplePostIdValue;

// Variables for variable product
const variableProductName = config.get( 'products.variable.name' );
const variations = config.get( 'products.variations' );
const attributes = variations[0].attributes;
let variableProductId;

// Variables for grouped product
let groupedPostIdValue;

const runSingleProductPageTest = () => {
	describe('Single Product Page', () => {
		beforeAll(async () => {
			simplePostIdValue = await createSimpleProduct();
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
		});

		it('should be able to remove simple products from the cart', async () => {
			// Remove items from cart
			await shopper.removeFromCart(simpleProductName);
			await uiUnblocked();
			await expect(page).toMatchElement('.cart-empty', {text: 'Your cart is currently empty.'});
		});
	});

	describe('Variable Product Page', () => {
		beforeAll(async () => {
			variableProductId = await createVariableProduct();
		});

		it('should be able to add variation products to the cart', async () => {
			// Add a product with one set of variations to cart
			await shopper.goToProduct( variableProductId );
			
			for( const attr of attributes ){
				const selectElem = `#${attr.name.toLowerCase()}`;
				const value = attr.option;

				await expect(page).toSelect(selectElem, value);
			}
			
			await shopper.addToCart();
			await expect(page).toMatchElement('.woocommerce-message', {text: 'has been added to your cart.'});

			// Verify cart contents
			await shopper.goToCart();
			await shopper.productIsInCart(variableProductName);
		});

		it('should be able to remove variation products from the cart', async () => {
			// Remove items from cart
			await shopper.removeFromCart(variableProductName);
			await uiUnblocked();
			await expect(page).toMatchElement('.cart-empty', {text: 'Your cart is currently empty.'});
		});
	});

	describe('Grouped Product Page', () => {
		beforeAll(async () => {
			groupedPostIdValue = await createGroupedProduct();
		});

		it('should be able to add grouped products to the cart', async () => {
			// Add a grouped product to cart
			await shopper.goToProduct(groupedPostIdValue);
			await page.waitForSelector('form.grouped_form');
			await shopper.addToCart();
			await expect(page).toMatchElement('.woocommerce-error',
			 {text: 'Please choose the quantity of items you wish to add to your cart…'});
			const quantityFields = await page.$$('div.quantity input.qty');
			await quantityFields[0].click({clickCount: 3});
			await quantityFields[0].type('5');
			await quantityFields[1].click({clickCount: 3});
			await quantityFields[1].type('5');
			await shopper.addToCart();
			await expect(page).toMatchElement('.woocommerce-message',
			 {text: '“'+simpleProductName+' 1” and “'+simpleProductName+' 2” have been added to your cart.'});

			// Verify cart contents
			await shopper.goToCart();
			await shopper.productIsInCart(simpleProductName+' 1');
			await shopper.productIsInCart(simpleProductName+' 2');
		});

		it('should be able to remove grouped products from the cart', async () => {
			// Remove items from cart
			await shopper.removeFromCart(simpleProductName+' 1');
			await uiUnblocked();
			await expect(page).toMatchElement('.woocommerce-message', {text: '“'+simpleProductName+' 1” removed.'});
			await Promise.all( [
				// Reload page and perform item removal, since removeFromCart won't remove it when placed in a row
				page.reload(),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			] );
			await shopper.removeFromCart(simpleProductName+' 2');
			await uiUnblocked();
			await expect(page).toMatchElement('.woocommerce-message', {text: '“'+simpleProductName+' 2” removed.'});
			await expect(page).toMatchElement('.cart-empty', {text: 'Your cart is currently empty.'});
		});
	});
};

module.exports = runSingleProductPageTest;
