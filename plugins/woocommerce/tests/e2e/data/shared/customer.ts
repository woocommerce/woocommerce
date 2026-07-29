/**
 * Customer billing object.
 *
 * Used in the following APIs:
 * https://developer.woocommerce.com/docs/apis/rest-api/v3/customers/
 * https://developer.woocommerce.com/docs/apis/rest-api/v3/orders/
 */
export const customerBilling = {
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

export const customerBillingSearchTest = {
	first_name: 'Johnsearch',
	last_name: 'Doesearch',
	company: 'Automatticsearch',
	country: 'USsearch',
	address_1: 'address1search',
	address_2: 'address2search',
	city: 'San Franciscosearch',
	state: 'CAsearch',
	postcode: '94107search',
	phone: '123456789search',
	email: 'john.doe@example.comsearch',
};

/**
 * Customer shipping object.
 *
 * Used in the following APIs:
 * https://developer.woocommerce.com/docs/apis/rest-api/v3/customers/
 * https://developer.woocommerce.com/docs/apis/rest-api/v3/orders/
 */
export const customerShipping = {
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

export const customerShippingSearchTest = {
	first_name: 'Timsearch',
	last_name: 'Clarksearch',
	company: 'Automatticsearch',
	country: 'USsearch',
	address_1: 'Oxford Avesearch',
	address_2: 'Linwood Avesearch',
	city: 'Buffalosearch',
	state: 'NYsearch',
	postcode: '14201search',
	phone: '123456789search',
};
