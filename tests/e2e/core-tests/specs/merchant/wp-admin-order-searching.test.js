/* eslint-disable jest/no-export, jest/no-disabled-tests, */

/**
 * Internal dependencies
 */
const {
	merchant,
	clearAndFillInput,
	searchForOrder,
	createSimpleProduct,
	addProductToOrder,
	clickUpdateOrder,
} = require( '@woocommerce/e2e-utils' );

const runOrderSearchingTest = () => {
	describe('WooCommerce Orders > Search orders', () => {
		let orderId;
		beforeAll(async () => {
			await merchant.login();
			await createSimpleProduct('Wanted Product');

			// Create new order for testing
			await merchant.openNewOrder();
			await page.waitForSelector('#order_status');
			await page.click('#customer_user');
			await page.click('span.select2-search > input.select2-search__field');
			await page.type('span.select2-search > input.select2-search__field', 'Customer');
			await page.waitFor(2000); // to avoid flakyness
			await page.keyboard.press('Enter');

			// Change the shipping data
			await page.waitFor(1000); // to avoid flakiness
			await page.waitForSelector('#_shipping_first_name');
			await clearAndFillInput('#_shipping_first_name', 'Tim');
			await clearAndFillInput('#_shipping_last_name', 'Clark');
			await clearAndFillInput('#_shipping_address_1', 'Oxford Ave');
			await clearAndFillInput('#_shipping_address_2', 'Linwood Ave');
			await clearAndFillInput('#_shipping_city', 'Buffalo');
			await clearAndFillInput('#_shipping_postcode', '14201');
			await page.waitFor(1000); // to avoid flakyness
			await page.click('#select2-_shipping_state-container');
			await page.select('select[name="_shipping_state"]', 'NY');

			// Get the post id
			const variablePostId = await page.$('#post_ID');
			orderId = (await(await variablePostId.getProperty('value')).jsonValue());

			// Save new order and add desired product to order
			await clickUpdateOrder('Order updated.', true);
			await addProductToOrder(orderId, 'Wanted Product');

			// Open All Orders view
			await merchant.openAllOrdersView();
		});

		it('can search for order by order id', async () => {
			await searchForOrder(orderId, orderId, 'John Doe');
		});

		it('can search for order by billing first name', async () => {
			await searchForOrder('John', orderId, 'John Doe');
		})

		it('can search for order by billing last name', async () => {
			await searchForOrder('Doe', orderId, 'John Doe');
		})

		it('can search for order by billing company name', async () => {
			await searchForOrder('Automattic', orderId, 'John Doe');
		})

		it('can search for order by billing first address', async () => {
			await searchForOrder('addr 1', orderId, 'John Doe');
		})

		it('can search for order by billing second address', async () => {
			await searchForOrder('addr 2', orderId, 'John Doe');
		})

		it('can search for order by billing city name', async () => {
			await searchForOrder('San Francisco', orderId, 'John Doe');
		})

		it('can search for order by billing post code', async () => {
			await searchForOrder('94107', orderId, 'John Doe');
		})

		it('can search for order by billing email', async () => {
			await searchForOrder('john.doe@example.com', orderId, 'John Doe');
		})

		it('can search for order by billing phone', async () => {
			await searchForOrder('123456789', orderId, 'John Doe');
		})

		it('can search for order by billing state', async () => {
			await searchForOrder('CA', orderId, 'John Doe');
		})

		it('can search for order by shipping first name', async () => {
			await searchForOrder('Tim', orderId, 'John Doe');
		})

		it('can search for order by shipping last name', async () => {
			await searchForOrder('Clark', orderId, 'John Doe');
		})

		it('can search for order by shipping first address', async () => {
			await searchForOrder('Oxford Ave', orderId, 'John Doe');
		})

		it('can search for order by shipping second address', async () => {
			await searchForOrder('Linwood Ave', orderId, 'John Doe');
		})

		it('can search for order by shipping city name', async () => {
			await searchForOrder('Buffalo', orderId, 'John Doe');
		})

		it('can search for order by shipping postcode name', async () => {
			await searchForOrder('14201', orderId, 'John Doe');
		})

		it('can search for order by shipping state name', async () => {
			await searchForOrder('NY', orderId, 'John Doe');
		})

		it('can search for order by item name', async () => {
			await searchForOrder('Wanted Product', orderId, 'John Doe');
		})
	});
};

module.exports = runOrderSearchingTest;
