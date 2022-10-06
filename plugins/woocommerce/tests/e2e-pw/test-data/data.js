const adminEmail =
	process.env.USE_WP_ENV === '1'
		? 'wordpress@example.com'
		: 'admin@woocommercecoree2etestsuite.com';

const storeDetails = {
	us: {
		store: {
			address: 'addr1',
			city: 'San Francisco',
			zip: '94107',
			email: adminEmail,
			country: 'United States (US) — California', // corresponding to the text value of the option
		},
		expectedIndustries: 8, // There are 8 checkboxes on the page (in the US), adjust this constant if we change that
		industries: {
			fashion: 'Fashion, apparel, and accessories',
			health: 'Health and beauty',
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
			email: adminEmail,
			country: 'Malta', // corresponding to the text value of the option
		},
		expectedIndustries: 7, // There are 7 checkboxes on the page (in Malta), adjust this constant if we change that
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
};
