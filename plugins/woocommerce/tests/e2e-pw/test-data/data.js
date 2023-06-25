const {
	ADMIN_USER,
	ADMIN_PASSWORD,
	ADMIN_USER_EMAIL,
	CUSTOMER_USER,
	CUSTOMER_PASSWORD,
	CUSTOMER_USER_EMAIL,
	CUSTOMER_FIRST_NAME,
	CUSTOMER_LAST_NAME,
	USE_WP_ENV,
} = process.env;

const admin = {
	username: ADMIN_USER ?? 'admin',
	password: ADMIN_PASSWORD ?? 'password',
	email:
		ADMIN_USER_EMAIL ??
		( !! USE_WP_ENV
			? 'wordpress@example.com'
			: 'admin@woocommercecoree2etestsuite.com' ),
};

const customer = {
	username: CUSTOMER_USER ?? 'customer',
	password: CUSTOMER_PASSWORD ?? 'password',
	email: CUSTOMER_USER_EMAIL ?? 'customer@woocommercecoree2etestsuite.com',
	first_name: CUSTOMER_FIRST_NAME ?? 'Jane',
	last_name: CUSTOMER_LAST_NAME ?? 'Smith',
	billing: {
		us: {
			first_name: 'Maggie',
			last_name: 'Simpson',
			address: '123 Evergreen Terrace',
			city: 'Springfield',
			country: 'US',
			state: 'OR',
			zip: '97403',
			phone: '555 555-5555',
			email: 'customer@example.com',
		},
		malta: {
			first_name: 'Maggie',
			last_name: 'Simpson',
			address: '123 Evergreen Terrace',
			city: 'Valletta',
			country: 'MT',
			zip: 'VT 1011',
			phone: '555 555-5555',
			email: 'vt-customer@example.com',
		},
	},
};

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
		expectedNumberOfIndustries: 8, // There are 8 checkboxes on the page (in the US), adjust this constant if we change that
		industries: {
			fashion: 'Fashion, apparel, and accessories',
			health: 'Health and beauty',
		},
		// For testing "Save Changes" feature, need to be different from the above
		industries2: {
			fashion: 'Fashion, apparel, and accessories',
			health: 'Health and beauty',
			foodAndDrinks: 'Food and drink',
		},
		products: {
			physical: 'Physical products',
			downloadable: 'Downloads',
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
		expectedNumberOfIndustries: 7, // There are 7 checkboxes on the page (in Malta), adjust this constant if we change that
		industries: {
			other: 'Other',
		},
		products: {
			physical: 'Physical products',
			downloadable: 'Downloads',
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
		expectedNumberOfIndustries: 8, // There are 8 checkboxes on the page (in Liberia), adjust this constant if we change that
		industries: {
			other: 'Other',
		},
		products: {
			physical: 'Physical products',
			downloadable: 'Downloads',
		},
	},
};

module.exports = {
	storeDetails,
	admin,
	customer,
};
