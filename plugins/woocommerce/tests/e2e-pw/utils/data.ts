/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';

/**
 * User role type.
 */
type UserRole =
	| 'customer'
	| 'administrator'
	| 'shop_manager'
	| 'subscriber'
	| 'contributor'
	| 'author'
	| 'editor';

/**
 * Address interface for billing/shipping.
 */
interface FakeAddress {
	first_name: string;
	last_name: string;
	address_1: string;
	address_2: string;
	city: string;
	state: string;
	postcode: string;
	country: string;
	email?: string;
	phone: string;
}

/**
 * Fake user interface.
 */
export interface FakeUser {
	email: string;
	first_name: string;
	last_name: string;
	role: UserRole;
	username: string;
	password: string;
	billing: FakeAddress;
	shipping: Omit< FakeAddress, 'email' >;
}

/**
 * Options for generating fake products.
 */
interface FakeProductOptions {
	dec?: number;
	regular_price?: string;
	type?: 'simple' | 'grouped' | 'external' | 'variable';
}

/**
 * Fake product interface.
 */
export interface FakeProduct {
	name: string;
	description: string;
	regular_price: string;
	type: 'simple' | 'grouped' | 'external' | 'variable';
}

/**
 * Options for generating fake categories.
 */
interface FakeCategoryOptions {
	extraRandomTerm?: boolean;
}

/**
 * Fake category interface.
 */
export interface FakeCategory {
	name: string;
}

/**
 * Generate a fake user with the given role.
 *
 * @param role - The user role
 * @return Fake user data
 */
export function getFakeUser( role: UserRole ): FakeUser {
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

/**
 * Generate a fake customer user.
 *
 * @return Fake customer data
 */
export function getFakeCustomer(): FakeUser {
	return getFakeUser( 'customer' );
}

/**
 * Generate a fake product.
 *
 * @param options - Options for generating the product
 * @return Fake product data
 */
export function getFakeProduct(
	options: FakeProductOptions = {}
): FakeProduct {
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

/**
 * Generate a fake category.
 *
 * @param options - Options for generating the category
 * @return Fake category data
 */
export function getFakeCategory(
	options: FakeCategoryOptions = { extraRandomTerm: false }
): FakeCategory {
	return {
		name: `${ faker.commerce.productMaterial() } ${ faker.commerce.department() } ${
			options.extraRandomTerm ? faker.string.alphanumeric( 5 ) : ''
		}`.trim(),
	};
}
