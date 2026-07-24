/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';

export function getFakeUser( role: string ) {
	const firstName = faker.person.firstName();
	const lastName = faker.person.lastName();
	const email = faker.internet.email( {
		firstName,
		lastName,
		provider: 'example.fakerjs.dev',
	} );

	return {
		email,
		first_name: firstName,
		last_name: lastName,
		role,
		username: faker.internet.username( { firstName, lastName } ),
		password: faker.internet.password(),
		billing: {
			first_name: firstName,
			last_name: lastName,
			address_1: '969 Market',
			address_2: '',
			city: 'San Francisco',
			state: 'CA',
			postcode: '94103',
			country: 'US',
			email,
			phone: '(555) 555-5555',
		},
		shipping: {
			first_name: firstName,
			last_name: lastName,
			address_1: '969 Market',
			address_2: '',
			city: 'San Francisco',
			state: 'CA',
			postcode: '94103',
			country: 'US',
			phone: '(555) 555-5555',
		},
	};
}

export function getFakeCustomer() {
	return getFakeUser( 'customer' );
}

export function getFakeProduct( options: any = {} ) {
	const dec = options.dec ?? 2;

	return {
		name: `${ faker.commerce.productName() }`,
		description: faker.commerce.productDescription(),
		regular_price: options.regular_price
			? options.regular_price
			: faker.commerce.price( { dec } ),
		type: options.type ? options.type : 'simple',
	};
}

export function getFakeCategory( options = { extraRandomTerm: false } ) {
	return {
		name: `${ faker.commerce.productMaterial() } ${ faker.commerce.department() } ${
			options.extraRandomTerm ? faker.string.alphanumeric( 5 ) : ''
		}`.trim(),
	};
}

// A unique taxonomy term name. Kept short so the generated `pa_*` attribute
// taxonomy slug stays within WordPress' length limit, and suffixed with random
// characters so parallel workers never share the same global term.
function getFakeTermName() {
	return `${ faker.commerce.productMaterial() } ${ faker.string.alphanumeric(
		5
	) }`;
}

// A unique product tag name, so parallel workers don't share global terms.
export function getFakeTag() {
	return {
		name: getFakeTermName(),
	};
}

// A unique product attribute (or attribute term) name.
export function getFakeAttribute() {
	return {
		name: getFakeTermName(),
	};
}
