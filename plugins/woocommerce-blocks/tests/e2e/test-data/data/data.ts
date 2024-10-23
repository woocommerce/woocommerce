const {
	ADMIN_USER,
	ADMIN_PASSWORD,
	ADMIN_USER_EMAIL,
	CUSTOMER_USER,
	CUSTOMER_PASSWORD,
	CUSTOMER_USER_EMAIL,
	CUSTOMER_FIRST_NAME,
	CUSTOMER_LAST_NAME,
} = process.env;

const admin = {
	username: ADMIN_USER ?? 'admin',
	password: ADMIN_PASSWORD ?? 'password',
	email: ADMIN_USER_EMAIL ?? 'admin@example.com',
};

const customer = {
	username: CUSTOMER_USER ?? 'customer',
	password: CUSTOMER_PASSWORD ?? 'password',
	email: CUSTOMER_USER_EMAIL ?? 'customer@example.com',
	first_name: CUSTOMER_FIRST_NAME ?? 'Jane',
	last_name: CUSTOMER_LAST_NAME ?? 'Smith',
	billing: {
		us: {
			first_name: 'Emily',
			last_name: 'Johnson',
			address: '456 Maple Street',
			city: 'Los Angeles',
			country: 'US',
			state: 'CA',
			zip: '90001',
			phone: '555 555-1234',
			email: 'emily.johnson@example.com',
		},
		uk: {
			first_name: 'Oliver',
			last_name: 'Taylor',
			address: '789 Oxford Street',
			city: 'London',
			country: 'UK',
			state: '',
			zip: 'W1D 2HG',
			phone: '+44 20 1234 5678',
			email: 'oliver.taylor@example.com',
		},
		af: {
			first_name: 'Mohammed',
			last_name: 'Hassan',
			address: '101 Kandahar Road',
			city: 'Kabul',
			country: 'AF',
			state: '',
			zip: '1001',
			phone: '+93 70 123 4567',
			email: 'mohammed.hassan@example.com',
		},
	},
};

// Reviews are ordered by when they were created.
// source: plugins/woocommerce-blocks/tests/e2e/bin/scripts/parallel/reviews.sh
const hoodieReviews = [
	{
		name: `${ customer.first_name } ${ customer.last_name }`,
		email: customer.email,
		review: 'Nice album!',
		rating: 5,
	},
	{
		name: `${ customer.first_name } ${ customer.last_name }`,
		email: customer.email,
		review: 'Not bad.',
		rating: 4,
	},
];

const capReviews = [
	{
		name: `${ customer.first_name } ${ customer.last_name }`,
		email: customer.email,
		review: 'Really awful.',
		rating: 1,
	},
	{
		name: `${ customer.first_name } ${ customer.last_name }`,
		email: customer.email,
		review: 'Bad!',
		rating: 2,
	},
];

const allReviews = hoodieReviews.concat( capReviews );

const storeDetails = {
	us: {
		store: {
			address: 'addr1',
			city: 'San Francisco',
			zip: '94107',
			email: admin.email,
			country: 'United States (US) — California', // corresponding to the text value of the option,
			countryCode: 'US:CA',
		},
	},
	malta: {
		store: {
			address: 'addr1',
			city: 'Valletta',
			zip: 'VT 1011',
			email: admin.email,
			country: 'Malta', // corresponding to the text value of the option,
			countryCode: 'MT',
		},
	},
	liberia: {
		store: {
			address: 'addr1',
			city: 'Kakata',
			zip: 'Division 1',
			email: admin.email,
			country: 'Liberia — Margibi', // corresponding to the text value of the option,
			countryCode: 'LR',
		},
	},
};

const translations = {
	lang: 'nl',
	locale: 'nl_NL',
};

module.exports = {
	admin,
	customer,
	hoodieReviews,
	capReviews,
	allReviews,
	storeDetails,
	translations,
};
