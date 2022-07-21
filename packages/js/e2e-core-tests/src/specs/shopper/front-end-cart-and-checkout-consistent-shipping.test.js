/**
 * Internal dependencies
 */
const {
	shopper,
	createSimpleProduct,
	selectOptionInSelect2,
	withRestApi,
	uiUnblocked,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const { it, describe, beforeAll } = require( '@jest/globals' );

const shippingZoneNameUS = 'US with Flat rate';
const shippingCountryUS = 'country:US';
const shippingRateUS = '10';

const shippingZoneNameMN = 'Mongolia with Flat rate';
const shippingCountryMN = 'country:MN';
const shippingRateMN = '5';

const runCartAndCheckoutConsistentShippingTest = () => {
	describe( 'Shipping calculation is consistent on cart page and checkout page', () => {
		let productId;

		beforeAll( async () => {
			productId = await createSimpleProduct();
			await withRestApi.deleteAllShippingZones( false );

			// Add a new shipping zone for United States with Flat rate
			await withRestApi.addShippingZoneAndMethod(
				shippingZoneNameUS,
				shippingCountryUS,
				' ',
				'flat_rate',
				shippingRateUS,
				[],
				false
			);

			// Add a new shipping zone for Mongolia with Flat rate
			await withRestApi.addShippingZoneAndMethod(
				shippingZoneNameMN,
				shippingCountryMN,
				' ',
				'flat_rate',
				shippingRateMN,
				[],
				false
			);
		} );

		beforeEach( async () => {
			await shopper.login();
		} );

		afterEach( async () => {
			await shopper.emptyCart();
			await shopper.logout();
		} );

		it( 'shows shipping costs on the cart page and checkout page for United States (has states)', async () => {
			// Add product to cart as a shopper
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );
			await shopper.goToCart();

			// Set shipping country to the United States
			await expect( page ).toClick( 'a.shipping-calculator-button' );
			await expect( page ).toClick(
				'#select2-calc_shipping_country-container'
			);
			await selectOptionInSelect2( 'United States (US)' );

			// Set shipping state to New York
			await expect( page ).toClick(
				'#select2-calc_shipping_state-container'
			);
			await selectOptionInSelect2( 'New York' );
			await expect( page ).toClick( 'button[name="calc_shipping"]' );

			await uiUnblocked();

			// Verify shipping costs on cart page
			await page.waitForSelector( '.order-total' );
			await expect( page ).toMatchElement( '.shipping .amount', {
				text: '$' + shippingRateUS + '.00',
			} );

			// Verify shipping costs on checkout page
			await shopper.goToCheckout();
			await expect( page ).toMatchElement(
				'#shipping_method > li > label',
				{
					text: 'Flat rate: ',
				}
			);
			await expect( page ).toMatchElement( '#shipping_method .amount', {
				text: '$' + shippingRateUS + '.00',
			} );
		} );

		it( 'shows shipping costs on the cart page and checkout page for Mongolia (has no states)', async () => {
			// Add product to cart as a shopper
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );
			await shopper.goToCart();

			// Set shipping country to Mongolia
			await expect( page ).toClick( 'a.shipping-calculator-button' );
			await expect( page ).toClick(
				'#select2-calc_shipping_country-container'
			);
			await selectOptionInSelect2( 'Mongolia' );

			await expect( page ).toClick( 'button[name="calc_shipping"]' );

			await uiUnblocked();

			// Verify shipping costs on cart page
			await page.waitForSelector( '.order-total' );
			await expect( page ).toMatchElement( '.shipping .amount', {
				text: '$' + shippingRateMN + '.00',
			} );

			// Verify shipping costs on checkout page
			await shopper.goToCheckout();
			await expect( page ).toMatchElement(
				'#shipping_method > li > label',
				{
					text: 'Flat rate: ',
				}
			);
			await expect( page ).toMatchElement( '#shipping_method .amount', {
				text: '$' + shippingRateMN + '.00',
			} );
		} );

		// eslint-disable-next-line jest/no-disabled-tests
		it.skip( 'shows no shipping available on the cart page and checkout page for Netherlands', async () => {
			// Add product to cart as a shopper
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );
			await shopper.goToCart();

			// Set shipping country to Netherlands
			await expect( page ).toClick( 'a.shipping-calculator-button' );
			await expect( page ).toClick(
				'#select2-calc_shipping_country-container'
			);
			await selectOptionInSelect2( 'Netherlands' );
			await expect( page ).toClick( 'button[name="calc_shipping"]' );

			await uiUnblocked();

			// Verify shipping costs on cart page
			await expect( page ).toMatchElement( '.shipping > td', {
				text: 'No shipping options were found for Netherlands.',
			} );

			// Verify shipping costs on checkout page
			await shopper.goToCheckout();
			await expect( page ).toMatchElement(
				'.woocommerce-shipping-totals > td',
				{
					// Test fails here because actual text is 'Enter your address to view shipping options.'
					// Logged in GitHub as issue #33205
					text: 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.',
				}
			);
		} );
	} );
};

// eslint-disable-next-line jest/no-export
module.exports = runCartAndCheckoutConsistentShippingTest;
