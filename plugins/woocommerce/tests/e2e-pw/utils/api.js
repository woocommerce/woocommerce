const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const config = require( '../playwright.config' );

let api;

// Ensure that global-setup.js runs before creating api client
if ( process.env.CONSUMER_KEY && process.env.CONSUMER_SECRET ) {
	api = new wcApi( {
		url: config.use.baseURL,
		consumerKey: process.env.CONSUMER_KEY,
		consumerSecret: process.env.CONSUMER_SECRET,
		version: 'wc/v3',
	} );
}

const update = {
	storeDetails: async ( store ) => {
		// ensure store address is US
		const res = await api.post( 'settings/general/batch', {
			update: [
				{
					id: 'woocommerce_store_address',
					value: store.address,
				},
				{
					id: 'woocommerce_store_city',
					value: store.city,
				},
				{
					id: 'woocommerce_default_country',
					value: store.countryCode,
				},
				{
					id: 'woocommerce_store_postcode',
					value: store.zip,
				},
			],
		} );
	},
	enableCashOnDelivery: async () => {
		await api.put( 'payment_gateways/cod', {
			enabled: true,
		} );
	},
	disableCashOnDelivery: async () => {
		await api.put( 'payment_gateways/cod', {
			enabled: false,
		} );
	},
};

const get = {
	defaultCountry: async () => {
		const response = await api.get(
			'settings/general/woocommerce_default_country'
		);

		const code = response.data.default;

		return code;
	},
};

const create = {
	product: async ( product ) => {
		const response = await api.post( 'products', {
			name: product.name,
			type: product.type,
			regular_price: product.price,
		} );

		return response.data.id;
	},
};

const deletePost = {
	product: async ( id ) => {
		await api.delete( `products/${ id }`, {
			force: true,
		} );
	},
	order: async ( id ) => {
		await api.delete( `orders/${ id }`, {
			force: true,
		} );
	},
};

module.exports = {
	update,
	get,
	create,
	deletePost,
};
