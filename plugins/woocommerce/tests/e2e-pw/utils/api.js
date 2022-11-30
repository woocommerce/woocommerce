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
	products: async ( params = { per_page: 20 } ) => {
		const response = await api
			.get( 'products', params )
			.then( ( response ) => response )
			.catch( ( error ) => {
				const message = 'Something went wrong when trying to list all products.'
					.concat(
						`\nResponse status: ${ error.response.status } ${ error.response.statusText }`
					)
					.concat(
						`\nResponse headers:\n${ JSON.stringify(
							error.response.headers,
							null,
							2
						) }`
					).concat( `\nResponse data:\n${ JSON.stringify(
					error.response.data,
					null,
					2
				) }
				` );

				throw new Error( message );
			} );

		return response.data;
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
