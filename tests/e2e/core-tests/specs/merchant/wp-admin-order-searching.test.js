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
const itemName = 'Wanted Product';

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

/**
 * Data table to be fed into `it.each()`.
 */
const queries = [
	[customerBilling.first_name, 'billing first name'],
	[customerBilling.last_name, 'billing last name'],
	[customerBilling.company, 'billing company name'],
	[customerBilling.address_1, 'billing first address'],
	[customerBilling.address_2, 'billing second address'],
	[customerBilling.city, 'billing city name'],
	[customerBilling.postcode, 'billing post code'],
	[customerBilling.email, 'billing email'],
	[customerBilling.phone, 'billing phone'],
	[customerBilling.state, 'billing state'],
	[customerShipping.first_name, 'shipping first name'],
	[customerShipping.last_name, 'shipping last name'],
	[customerShipping.address_1, 'shipping first address'],
	[customerShipping.address_2, 'shipping second address'],
	[customerShipping.city, 'shipping city name'],
	[customerShipping.postcode, 'shipping post code'],
	[itemName, 'shipping item name']
];

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

		it.each(queries)(
			'can search for order containing "%s" as the %s',
			async (value) => {
				await searchForOrder(value, orderId, searchString);
			}
		);

		/**
		 * shipping state is abbreviated. This test passes if billing and shipping state are the same
		 */
		it.skip('can search for order by shipping state name', async () => {
			await searchForOrder('New York', orderId, searchString);
		})
	});
};

module.exports = runOrderSearchingTest;
