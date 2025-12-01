/**
 * External dependencies
 */
import { CartBillingAddress } from '@woocommerce/type-defs/cart';
import { extractName, formatAddress } from '@woocommerce/blocks/checkout/utils';

describe( 'extractName', () => {
	it.each( [
		[
			'{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}',
			'{name}',
		],
		[
			'{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}{name}',
			'{name}',
		],
		[
			'{first_name} {last_name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}',
			'{first_name} {last_name}',
		],
		[
			'{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{first_name} {last_name}\n{postcode}\n{country}',
			'{first_name} {last_name}',
		],
		[
			'{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{first_name_upper} {last_name}\n{postcode}\n{country}',
			'{first_name_upper} {last_name}',
		],
		[
			'{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{first_name_upper} {last_name_upper}\n{postcode}\n{country}',
			'{first_name_upper} {last_name_upper}',
		],
		[
			'{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{first_name} {last_name_upper}\n{postcode}\n{country}',
			'{first_name} {last_name_upper}',
		],
		[
			'{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{last_name_upper} {first_name} \n{postcode}\n{country}',
			'{last_name_upper} {first_name}',
		],
		[
			'{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{name_upper}\n{postcode}\n{country}',
			'{name_upper}',
		],
		[
			'{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{last_name_upper} {first_name_upper} \n{postcode}\n{country}',
			'{last_name_upper} {first_name_upper}',
		],
		[
			'{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{last_name} {first_name_upper} \n{postcode}\n{country}',
			'{last_name} {first_name_upper}',
		],
		[
			'{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{last_name} {first_name} \n{postcode}\n{country}',
			'{last_name} {first_name}',
		],
	] )(
		'should extract the name token from the format',
		( format, expected ) => {
			expect( extractName( format ) ).toBe( expected );
		}
	);
} );
describe( 'formatAddress', () => {
	const defaultAddress: CartBillingAddress = {
		first_name: 'John',
		last_name: 'Doe',
		address_1: '123 Yonge St',
		address_2: 'Apt 1',
		city: 'Toronto',
		state: 'ON',
		postcode: 'M5B1M4',
		country: 'CA',
		email: 'jon.doe@mail.com',
		company: 'WooCommerce',
		phone: '1234567890',
	};

	it.each( [
		[
			defaultAddress,
			'{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}',
			{
				name: 'John Doe',
				address: [
					'WooCommerce',
					'123 Yonge St',
					'Apt 1',
					'Toronto',
					'Ontario',
					'M5B1M4',
					'Canada',
				],
			},
		],
		// Switch name so it's not the first thing.
		[
			defaultAddress,
			'{company}\n{name}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}',
			{
				name: 'John Doe',
				address: [
					'WooCommerce',
					'123 Yonge St',
					'Apt 1',
					'Toronto',
					'Ontario',
					'M5B1M4',
					'Canada',
				],
			},
		],
		// Try with upper case name.
		[
			defaultAddress,
			'{company}\n{name_upper}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}',
			{
				name: 'JOHN DOE',
				address: [
					'WooCommerce',
					'123 Yonge St',
					'Apt 1',
					'Toronto',
					'Ontario',
					'M5B1M4',
					'Canada',
				],
			},
		],
		// Try with upper case first name and regular last name.
		[
			defaultAddress,
			'{company}\n{last_name} {first_name_upper}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}',
			{
				name: 'Doe JOHN',
				address: [
					'WooCommerce',
					'123 Yonge St',
					'Apt 1',
					'Toronto',
					'Ontario',
					'M5B1M4',
					'Canada',
				],
			},
		],
		// Try with regular first name and upper case last name.
		[
			defaultAddress,
			'{company}\n{last_name} {first_name_upper}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}',
			{
				name: 'Doe JOHN',
				address: [
					'WooCommerce',
					'123 Yonge St',
					'Apt 1',
					'Toronto',
					'Ontario',
					'M5B1M4',
					'Canada',
				],
			},
		],
		// Try with upper case values.
		[
			defaultAddress,
			'{company_upper}\n{name}\n{address_1_upper}\n{address_2_upper}\n{city_upper}\n{state_upper}\n{postcode_upper}\n{country_upper}',
			{
				name: 'John Doe',
				address: [
					'WOOCOMMERCE',
					'123 YONGE ST',
					'APT 1',
					'TORONTO',
					'ONTARIO',
					'M5B1M4',
					'CANADA',
				],
			},
		],
		// Try with missing values.
		[
			defaultAddress,
			'{company_upper}\n{name}\n\n\n{address_2_upper}\n{city_upper}\n{postcode_upper}\n{country_upper}',
			{
				name: 'John Doe',
				address: [
					'WOOCOMMERCE',
					'APT 1',
					'TORONTO',
					'M5B1M4',
					'CANADA',
				],
			},
		],
		// Try with an empty string.
		[
			defaultAddress,
			'',
			{
				name: '',
				address: [],
			},
		],
		// Try with a badly mangled string.
		[
			defaultAddress,
			'{company_uppe}\n{name}\n\n\naddress_2_upper!\n{city_upper}£postcode_upper}\n{country_upper',
			{
				name: 'John Doe',
				address: [
					'{company_uppe}',
					'address_2_upper!',
					'TORONTO£postcode_upper}',
					'{country_upper',
				],
			},
		],
		// Test empty address values.
		[
			{
				first_name: '',
				last_name: '',
				address_1: '',
				address_2: '',
				city: '',
				state: '',
				postcode: '',
				country: '',
				email: 'jon.doe@mail.com',
				company: 'WooCommerce',
				phone: '1234567890',
			},
			'{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}',
			{
				name: '',
				address: [ 'WooCommerce' ],
			},
		],
		// Test partial address values.
		[
			{
				first_name: 'Jon',
				last_name: '',
				address_1: '',
				address_2: '',
				city: 'Toronto',
				state: '',
				postcode: '',
				country: '',
				email: 'jon.doe@mail.com',
				company: 'WooCommerce',
				phone: '1234567890',
			},
			'{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}',
			{
				name: 'Jon',
				address: [ 'WooCommerce', 'Toronto' ],
			},
		],
	] )(
		'should format the address correctly',
		( address, format, expected ) => {
			const formattedAddress = formatAddress( address, format );
			expect( formattedAddress.name ).toBe( expected.name );
			expect( formattedAddress.address ).toEqual( expected.address );
		}
	);
} );
