/**
 * This file contains objects that can be used as test data for scenarios around creating, retrieivng, updating, and deleting customers.
 *
 * For more details on the Product properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#customers
 *
 */

/**
 * A customer
 */
const customer = {
	email: 'john.doe@example.com',
	first_name: 'John',
	last_name: 'Doe',
	username: 'john.doe',
	billing: {
		first_name: 'John',
		last_name: 'Doe',
		company: '',
		address_1: '969 Market',
		address_2: '',
		city: 'San Francisco',
		state: 'CA',
		postcode: '94103',
		country: 'US',
		email: 'john.doe@example.com',
		phone: '(555) 555-5555',
	},
	shipping: {
		first_name: 'John',
		last_name: 'Doe',
		company: '',
		address_1: '969 Market',
		address_2: '',
		city: 'San Francisco',
		state: 'CA',
		postcode: '94103',
		country: 'US',
	},
};

module.exports = {
	customer,
};
