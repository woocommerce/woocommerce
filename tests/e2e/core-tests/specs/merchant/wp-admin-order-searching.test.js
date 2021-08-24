/**
 * Internal dependencies
 */
const {
	merchant,
	searchForOrder,
	createSimpleProduct,
	factories,
	createOrder,
} = require( '@woocommerce/e2e-utils' );

const searchString = 'John Doe';
const customerBilling = {
	first_name: 'John',
	last_name: 'Doe',
	company: 'Automattic',
	country: 'US',
	address_1: 'address1',
	address_2: 'address2',
	city: 'San Francisco',
	state: 'CA',
	postcode: '94107',
	phone: '123456789',
	email: 'john.doe@example.com',
};
const customerShipping = {
	first_name: 'Tim',
	last_name: 'Clark',
	company: 'Automattic',
	country: 'US',
	address_1: 'Oxford Ave',
	address_2: 'Linwood Ave',
	city: 'Buffalo',
	state: 'NY',
	postcode: '14201',
	phone: '123456789',
	email: 'john.doe@example.com',
};

/**
 * Set the billing fields for the customer account for this test suite.
 *
 * @returns {Promise<number>}
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
		shipping: customerShipping,
	};
	await client.put( customerEndpoint + customerId, customerData );
	return customerId;
};

const runOrderSearchingTest = () => {
	describe('WooCommerce Orders > Search orders', () => {
		let productId;
		let orderId;
		let customerId;

		beforeAll( async () => {
			productId = await createSimpleProduct('Wanted Product');
			customerId = await updateCustomerBilling();
			orderId = await createOrder({
				customerId,
				productId,
				customerBilling,
				customerShipping,
			});

			// Login and open All Orders view
			await merchant.login();
			await merchant.openAllOrdersView();
		});

		it('can search for order by order id', async () => {
			// Convert the order ID to string so we can search on it
			await searchForOrder(orderId.toString(), orderId, searchString);
		});

		it('can search for order by billing first name', async () => {
			await searchForOrder(customerBilling.first_name, orderId, searchString);
		})

		it('can search for order by billing last name', async () => {
			await searchForOrder(customerBilling.last_name, orderId, searchString);
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
			await searchForOrder(customerBilling.email, orderId, searchString);
		})

		it('can search for order by billing phone', async () => {
			await searchForOrder(customerBilling.phone, orderId, searchString);
		})

		it('can search for order by billing state', async () => {
			await searchForOrder(customerBilling.state, orderId, searchString);
		})

		it('can search for order by shipping first name', async () => {
			await searchForOrder(customerShipping.first_name, orderId, searchString);
		})

		it('can search for order by shipping last name', async () => {
			await searchForOrder(customerShipping.last_name, orderId, searchString);
		})

		it('can search for order by shipping first address', async () => {
			await searchForOrder(customerShipping.address_1, orderId, searchString);
		})

		it('can search for order by shipping second address', async () => {
			await searchForOrder(customerShipping.address_2, orderId, searchString);
		})

		it('can search for order by shipping city name', async () => {
			await searchForOrder(customerShipping.city, orderId, searchString);
		})

		it('can search for order by shipping postcode name', async () => {
			await searchForOrder(customerShipping.postcode, orderId, searchString);
		})

		/**
		 * shipping state is abbreviated. This test passes if billing and shipping state are the same
		 */
		it.skip('can search for order by shipping state name', async () => {
			await searchForOrder('New York', orderId, searchString);
		})

		it('can search for order by item name', async () => {
			await searchForOrder('Wanted Product', orderId, searchString);
		})
	});
};

module.exports = runOrderSearchingTest;
