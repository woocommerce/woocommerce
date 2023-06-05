const {
	ADMIN_USER,
	ADMIN_PASSWORD,
	ADMIN_USER_EMAIL,
	CUSTOMER_USER,
	CUSTOMER_PASSWORD,
	CUSTOMER_USER_EMAIL,
	CUSTOMER_FIRST_NAME,
	CUSTOMER_LAST_NAME,
	LANGUAGE,
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

const { en_GB } = require('./language/en-GB');
const { en_US } = require('./language/en-US');
const { es_ES } = require('./language/es-ES');
const { fr_FR } = require('./language/fr-FR');

const languageObject = {
	en_US,
	en_GB,
	es_ES,
	fr_FR
};

const storeDetails = {
	us: {
		store: {
			address: 'addr1',
			city: 'San Francisco',
			zip: '94107',
			email: admin.email,
			country: getTranslationFor('United States (US) — California'), // corresponding to the text value of the option,
			countryCode: 'US:CA',
		},
		expectedNumberOfIndustries: 8, // There are 8 checkboxes on the page (in the US), adjust this constant if we change that
		industries: {
			fashion: getTranslationFor('Fashion, apparel, and accessories'),
			health: getTranslationFor('Health and beauty'),
		},
		// For testing "Save Changes" feature, need to be different from the above
		industries2: {
			fashion: getTranslationFor('Fashion, apparel, and accessories'),
			health: getTranslationFor('Health and beauty'),
			foodAndDrinks: getTranslationFor('Food and drink'),
		},
		products: {
			physical: getTranslationFor('Physical products'),
			downloadable: getTranslationFor('Downloads'),
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
			other: getTranslationFor('Other'),
		},
		products: {
			physical: getTranslationFor('Physical products'),
			downloadable: getTranslationFor('Downloads'),
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
			other: getTranslationFor('Other'),
		},
		products: {
			physical: getTranslationFor('Physical products'),
			downloadable: getTranslationFor('Downloads'),
		},
	},
};

function getTranslationFor(textToTranslate) {
	let langCode = 'en_US';

	if ( LANGUAGE ) {
		if ( [ 'en_US', 'en_GB', 'es_ES', 'fr_FR' ].includes( LANGUAGE ) ) {
			langCode = LANGUAGE;
		} else {
			console.log( 'LANGUAGE input must be en_US, en_GB, es_ES or fr_FR' );
		}
	}
	return languageObject[ langCode ][ textToTranslate ];
}

module.exports = {
	storeDetails,
	admin,
	customer,
	getTranslationFor,
};
