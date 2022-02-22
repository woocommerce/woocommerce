/**
 * Internal dependencies
 */
import type { CustomerDataType } from '../../../hooks/use-customer-data';

export const defaultBillingData: CustomerDataType[ 'billingData' ] = {
	first_name: '',
	last_name: '',
	company: '',
	address_1: '',
	address_2: '',
	city: '',
	state: '',
	postcode: '',
	country: '',
	email: '',
	phone: '',
};

export const defaultShippingAddress: CustomerDataType[ 'shippingAddress' ] = {
	first_name: '',
	last_name: '',
	company: '',
	address_1: '',
	address_2: '',
	city: '',
	state: '',
	postcode: '',
	country: '',
	phone: '',
};
