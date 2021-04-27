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
	factories,
	selectOptionInSelect2,
} = require( '@woocommerce/e2e-utils' );

const searchString = 'Jane Smith';
const customerBilling = {
	firstname: 'Jane',
	lastname: 'Smith',
	company: 'Automattic',
	country: 'US',
	address_1: 'address1',
	address_2: 'address2',
	city: 'San Francisco',
	state: 'CA',
	postcode: '94107',
	phone: '123456789',
};

/**
 * Set the billing fields for the customer account for this test suite.
 *
 * @returns {Promise<void>}
 */
const updateCustomerBilling = async () => {
	const client = factories.api.withDefaultPermalinks;
	const customerEndpoint = 'wc/v3/customers/';
	const customers = await client.get( customerEndpoint, {
		search: 'Jane',
		role: 'all',
	} );
	if ( ! customers.data | ! customers.data.length ) {
		return;
	}

	const customerId = customers.data[0].id;
	const customerData = {
		id: customerId,
		billing: customerBilling,
	};
	await client.put( customerEndpoint + customerId, customerData );
};

const runOrderSearchingTest = () => {
	describe('WooCommerce Orders > Search orders', () => {
		let orderId;
		beforeAll( async () => {
			await createSimpleProduct('Wanted Product');
			await updateCustomerBilling();

			// Create new order for testing
			await merchant.login();
			await merchant.openNewOrder();
			await page.waitForSelector('#order_status');
			await page.click('#customer_user');
			await page.click('span.select2-search > input.select2-search__field');
			await page.type('span.select2-search > input.select2-search__field', searchString);
			await page.waitFor(2000); // to avoid flakyness
			await page.keyboard.press('Enter');

			// Change the shipping data
			await page.waitFor(1000); // to avoid flakiness
			await page.click('.billing-same-as-shipping');
			await page.keyboard.press('Enter');
			await page.waitForSelector('#_shipping_first_name');
			await clearAndFillInput('#_shipping_first_name', 'Tim');
			await clearAndFillInput('#_shipping_last_name', 'Clark');
			await clearAndFillInput('#_shipping_address_1', 'Oxford Ave');
			await clearAndFillInput('#_shipping_address_2', 'Linwood Ave');
			await clearAndFillInput('#_shipping_city', 'Buffalo');
			await clearAndFillInput('#_shipping_postcode', '14201');
			await selectOptionInSelect2('New York', '._shipping_state_field .select2');

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
			await searchForOrder(orderId, orderId, searchString);
		});

		it('can search for order by billing first name', async () => {
			await searchForOrder('Jane', orderId, searchString);
		})

		it('can search for order by billing last name', async () => {
			await searchForOrder('Smith', orderId, searchString);
		})

		it('can search for order by billing company name', async () => {
			await searchForOrder(customerBilling.company, orderId, searchString);
		})

		it('can search for order by billing first address', async () => {
			await searchForOrder(customerBilling.address_1, orderId, searchString);
		})

		it('can search for order by billing second address', async () => {
			await searchForOrder(customerBilling.address_2, orderId, searchString);
		})

		it('can search for order by billing city name', async () => {
			await searchForOrder(customerBilling.city, orderId, searchString);
		})

		it('can search for order by billing post code', async () => {
			await searchForOrder(customerBilling.postcode, orderId, searchString);
		})

		it('can search for order by billing email', async () => {
			await searchForOrder('customer@woocommercecoree2etestsuite.com', orderId, searchString);
		})

		it('can search for order by billing phone', async () => {
			await searchForOrder(customerBilling.phone, orderId, searchString);
		})

		it('can search for order by billing state', async () => {
			await searchForOrder(customerBilling.state, orderId, searchString);
		})

		it('can search for order by shipping first name', async () => {
			await searchForOrder('Tim', orderId, searchString);
		})

		it('can search for order by shipping last name', async () => {
			await searchForOrder('Clark', orderId, searchString);
		})

		it('can search for order by shipping first address', async () => {
			await searchForOrder('Oxford Ave', orderId, searchString);
		})

		it('can search for order by shipping second address', async () => {
			await searchForOrder('Linwood Ave', orderId, searchString);
		})

		it('can search for order by shipping city name', async () => {
			await searchForOrder('Buffalo', orderId, searchString);
		})

		it('can search for order by shipping postcode name', async () => {
			await searchForOrder('14201', orderId, searchString);
		})

		it('can search for order by shipping state name', async () => {
			await searchForOrder('New York', orderId, searchString);
		})

		it('can search for order by item name', async () => {
			await searchForOrder('Wanted Product', orderId, searchString);
		})
	});
};

module.exports = runOrderSearchingTest;
