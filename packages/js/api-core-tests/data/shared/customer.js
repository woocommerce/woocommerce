/**
 * Customer billing object.
 *
 * Used in the following APIs:
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#customers
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#orders
 */
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

/**
 * Customer shipping object.
 *
 * Used in the following APIs:
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#customers
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#orders
 */
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
};

module.exports = {
	customerBilling,
	customerShipping,
};
