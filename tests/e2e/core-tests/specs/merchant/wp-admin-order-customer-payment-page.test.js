/* eslint-disable jest/no-export, jest/no-disabled-tests */
import {createSimpleProduct} from "@woocommerce/e2e-utils";

/**
 * Internal dependencies
 */
const {
	merchant,
	createSimpleOrder,
	addProductToOrder,
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const simpleProductPrice = config.has( 'products.simple.price' ) ? config.get( 'products.simple.price' ) : '9.99';

let orderId;

const runMerchantOrdersCustomerPaymentPage = () => {
	describe('WooCommerce Merchant Flow: Orders > Customer Payment Page', () => {
		beforeAll(async () => {
			await merchant.login();
			await createSimpleProduct();
			orderId = await createSimpleOrder();
			await addProductToOrder( orderId, simpleProductName );
		});

		it('should be able to view the customer order page and complete payment', async () => {
			await merchant.goToOrder( orderId );

			// We first need to click "Update" otherwise the link doesn't show
			await Promise.all([
				expect(page).toClick( 'button.save_order' ),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			]);

			// Verify the customer payment page link it showing
			await expect(page).toMatchElement( 'label[for=order_status] > a' , { text: 'Customer payment page →' });

			// Visit the page
			await Promise.all([
				expect(page).toClick( 'label[for=order_status] > a' ),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			]);

			// Verify we landed on the customer payment page
			await expect(page).toMatchElement( 'h1.entry-title' , { text: 'Pay for order' });
			await expect(page).toMatchElement( 'td.product-name' , { text: simpleProductName });
			await expect(page).toMatchElement( 'span.woocommerce-Price-amount.amount' , { text: simpleProductPrice });

			// Pay for the order
			await Promise.all([
				expect(page).toClick( '#place_order'),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			]);

			// Verify we landed on the order received page
			await expect(page).toMatchElement( 'h1.entry-title', { text: 'Order received' });
			await expect(page).toMatchElement( 'li.woocommerce-order-overview__order.order' , { text: orderId.toString() });
			await expect(page).toMatchElement( 'span.woocommerce-Price-amount.amount' , { text: simpleProductPrice });

		});

	});

};

module.exports = runMerchantOrdersCustomerPaymentPage;
