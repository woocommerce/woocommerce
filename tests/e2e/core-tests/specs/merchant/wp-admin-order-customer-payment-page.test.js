const { createSimpleProduct } = require( '@woocommerce/e2e-utils' );

/**
 * Internal dependencies
 */
const {
	merchant,
	createOrder,
} = require( '@woocommerce/e2e-utils' );

// TODO create a function for the logic below getConfigSimpleProduct(), see: https://github.com/woocommerce/woocommerce/issues/29072
const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const simpleProductPrice = config.has( 'products.simple.price' ) ? config.get( 'products.simple.price' ) : '9.99';

const runMerchantOrdersCustomerPaymentPage = () => {
	let orderId;
	let productId;

	describe('WooCommerce Merchant Flow: Orders > Customer Payment Page', () => {
		beforeAll(async () => {
			productId = await createSimpleProduct();
			orderId = await createOrder( { productId } );
			await merchant.login();
		});

		it('should show the customer payment page link on a pending payment order', async () => {
			await merchant.goToOrder( orderId );

			// Verify the order is still pending payment
			await expect( page ).toMatchElement( '#order_status', { text: 'Pending payment' } );

			// Verify the customer payment page link is displayed
			await expect(page).toMatchElement( 'label[for=order_status] > a' , { text: 'Customer payment page →' });
		});


		it('should load the customer payment page', async () => {
			// Verify the customer payment page link is displayed
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
		});

		it('can pay for the order through the customer payment page', async () => {
			// Make sure we're still on the customer payment page
			await expect(page).toMatchElement( 'h1.entry-title' , { text: 'Pay for order' });

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
