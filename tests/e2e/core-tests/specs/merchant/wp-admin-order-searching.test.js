/* eslint-disable jest/no-export, jest/no-disabled-tests, */

/**
 * Internal dependencies
 */
const {
	merchant,
	clearAndFillInput,
	selectOptionInSelect2,
	searchForOrder,
	createSimpleProduct,
	addProductToOrder,
} = require( '@woocommerce/e2e-utils' );

let orderId;

const runOrderSearchingTest = () => {
	describe('WooCommerce Orders > Search orders', () => {
		beforeAll(async () => {
			await merchant.login();

			await createSimpleProduct('Wanted Product');

			// Create new order for testing
			await merchant.openNewOrder();
			await page.waitForSelector('#order_status');
			await page.select('#order_status', 'Pending payment');
			await page.waitForSelector('#customer_user');
			await page.click('#customer_user');
			await page.waitForSelector('input.select2-search__field');
			await selectOptionInSelect2('Customer', 'input.select2-search__field');

			// Change the shipping data
			await page.waitFor(1000); // to avoid flakiness
			await clearAndFillInput('#_shipping_first_name', 'Tim');
			await clearAndFillInput('#_shipping_last_name', 'Clark');
			await clearAndFillInput('#_shipping_address_1', 'Oxford Ave');
			await clearAndFillInput('#_shipping_address_2', 'Linwood Ave');
			await clearAndFillInput('#_shipping_city', 'Buffalo');
			await clearAndFillInput('#_shipping_postcode', '14201');
			await page.waitFor(1000); // to avoid flakiness
			await page.select('select[name="_shipping_state"]', 'NY');

			// Save new order
			await page.click('button.save_order');
			await page.waitForSelector('#message');

			// Get the post id
			const variablePostId = await page.$('#post_ID');
			orderId = (await(await variablePostId.getProperty('value')).jsonValue());

			await merchant.openAllOrdersView();

			await addProductToOrder(orderId, 'Wanted Product');

			await merchant.openAllOrdersView();
		});

		it('can search for order by order id', async () => {
			await searchForOrder(orderId, orderId);
		});

		it('can search for order by billing first name', async () => {
			await searchForOrder('John', orderId);
		})

		it('can search for order by billing last name', async () => {
			await searchForOrder('Doe', orderId);
		})

		it('can search for order by billing company name', async () => {
			await searchForOrder('Automattic', orderId);
		})

		it('can search for order by billing first address', async () => {
			await searchForOrder('addr 1', orderId);
		})

		it('can search for order by billing second address', async () => {
			await searchForOrder('addr 2', orderId);
		})

		it('can search for order by billing city name', async () => {
			await searchForOrder('San Francisco', orderId);
		})

		it('can search for order by billing post code', async () => {
			await searchForOrder('94107', orderId);
		})

		it('can search for order by billing email', async () => {
			await searchForOrder('john.doe@example.com', orderId);
		})

		it('can search for order by billing phone', async () => {
			await searchForOrder('123456789', orderId);
		})

		it('can search for order by billing state', async () => {
			await searchForOrder('CA', orderId);
		})

		it('can search for order by shipping first name', async () => {
			await searchForOrder('Tim', orderId);
		})

		it('can search for order by shipping last name', async () => {
			await searchForOrder('Clark', orderId);
		})

		it('can search for order by shipping first address', async () => {
			await searchForOrder('Oxford Ave', orderId);
		})

		it('can search for order by shipping second address', async () => {
			await searchForOrder('Linwood Ave', orderId);
		})

		it('can search for order by shipping city name', async () => {
			await searchForOrder('Buffalo', orderId);
		})

		it('can search for order by shipping postcode name', async () => {
			await searchForOrder('14201', orderId);
		})

		it('can search for order by shipping state name', async () => {
			await searchForOrder('NY', orderId);
		})

		it('can search for order by item name', async () => {
			await searchForOrder('Wanted Product', orderId);
		})
	});
};

module.exports = runOrderSearchingTest;
