/**
 * Internal dependencies
 */
import ApiClient, { WC_API_PATH } from '../utils/api-client';

const api = ApiClient.getInstance();

const update = {
	storeDetails: async ( store ) => {
		await api.post( 'settings/general/batch', {
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
	coupons: async ( params ) => {
		const response = await api
			.get( `${ WC_API_PATH }/coupons`, params )
			.then( ( r ) => r );

		return response.data;
	},
	orders: async ( params ) => {
		const response = await api
			.get( `${ WC_API_PATH }/orders`, params )
			.then( ( r ) => r );

		return response.data;
	},
	products: async ( params ) => {
		const response = await api
			.get( `${ WC_API_PATH }/products`, params )
			.then( ( r ) => r );

		return response.data;
	},
	productAttributes: async ( params ) => {
		const response = await api
			.get( `${ WC_API_PATH }/products/attributes`, params )
			.then( ( r ) => r );

		return response.data;
	},
	productCategories: async ( params ) => {
		const response = await api
			.get( `${ WC_API_PATH }/products/categories`, params )
			.then( ( r ) => r );

		return response.data;
	},
	productTags: async ( params ) => {
		const response = await api
			.get( `${ WC_API_PATH }/products/tags`, params )
			.then( ( r ) => r );
		return response.data;
	},
	shippingClasses: async ( params ) => {
		const response = await api
			.get( `${ WC_API_PATH }/products/shipping_classes`, params )
			.then( ( r ) => r );

		return response.data;
	},

	shippingZones: async ( params ) => {
		const response = await api
			.get( `${ WC_API_PATH }/shipping/zones`, params )
			.then( ( r ) => r );

		return response.data;
	},

	taxClasses: async () => {
		const response = await api
			.get( `${ WC_API_PATH }/taxes/classes` )
			.then( ( r ) => r );

		return response.data;
	},
	taxRates: async ( params ) => {
		const response = await api
			.get( `${ WC_API_PATH }/taxes`, params )
			.then( ( r ) => r );

		return response.data;
	},
};

const create = {
	product: async ( product ) => {
		const response = await api.post( `${ WC_API_PATH }/products`, product );

		return response.data.id;
	},
	shippingZone: async ( zone ) => {
		const response = await api.post(
			`${ WC_API_PATH }/shipping/zones`,
			zone
		);

		return response.data.id;
	},
	shippingMethod: async ( zoneId, method ) => {
		const response = await api.post(
			`${ WC_API_PATH }/shipping/zones/${ zoneId }/methods`,
			method
		);

		return response.data.id;
	},
	/**
	 * Batch create product variations.
	 *
	 * @see {@link [Batch update product variations](https://woocommerce.github.io/woocommerce-rest-api-docs/#batch-update-product-variations)}
	 * @param {number|string} productId  Product ID to add variations to
	 * @param {object[]}      variations Array of variations to add. See [Product variation properties](https://woocommerce.github.io/woocommerce-rest-api-docs/#product-variation-properties)
	 * @return {Promise<number[]>} Array of variation ID's.
	 */
	productVariations: async ( productId, variations ) => {
		const response = await api.post(
			`${ WC_API_PATH }/products/${ productId }/variations/batch`,
			{
				create: variations,
			}
		);

		return response.data.create.map( ( { id } ) => id );
	},
};

const deletePost = {
	coupons: async ( ids ) => {
		const res = await api
			.post( `${ WC_API_PATH }/coupons/batch`, { delete: ids } )
			.then( ( response ) => response );

		return res.data;
	},
	product: async ( id ) => {
		await api.delete( `${ WC_API_PATH }/products/${ id }`, {
			force: true,
		} );
	},
	products: async ( ids ) => {
		const res = await api
			.post( `${ WC_API_PATH }/products/batch`, { delete: ids } )
			.then( ( response ) => response );
		return res.data;
	},
	productAttributes: async ( id ) => {
		const res = await api
			.post( `${ WC_API_PATH }/products/attributes/batch`, {
				delete: id,
			} )
			.then( ( response ) => response );
		return res.data;
	},
	productCategories: async ( ids ) => {
		const res = await api
			.post( `${ WC_API_PATH }/products/categories/batch`, {
				delete: ids,
			} )
			.then( ( response ) => response );
		return res.data;
	},
	productTags: async ( ids ) => {
		const res = await api
			.post( `${ WC_API_PATH }/products/tags/batch`, { delete: ids } )
			.then( ( response ) => response );
		return res.data;
	},
	order: async ( id ) => {
		await api.delete( `${ WC_API_PATH }/orders/${ id }`, {
			force: true,
		} );
	},
	orders: async ( ids ) => {
		const res = await api
			.post( `${ WC_API_PATH }/orders/batch`, { delete: ids } )
			.then( ( response ) => response );
		return res.data;
	},
	shippingClasses: async ( ids ) => {
		const res = await api
			.post( `${ WC_API_PATH }/products/shipping_classes/batch`, {
				delete: ids,
			} )
			.then( ( response ) => response );
		return res.data;
	},
	shippingZone: async ( id ) => {
		const res = await api
			.delete( `${ WC_API_PATH }/shipping/zones/${ id }`, {
				force: true,
			} )
			.then( ( response ) => response );
		return res.data;
	},
	taxClass: async ( slug ) => {
		const res = await api
			.delete( `${ WC_API_PATH }/taxes/classes/${ slug }`, {
				force: true,
			} )
			.then( ( response ) => response );
		return res.data;
	},
	taxRates: async ( ids ) => {
		const res = await api
			.post( `${ WC_API_PATH }/taxes/batch`, { delete: ids } )
			.then( ( response ) => response );
		return res.data;
	},
};

module.exports = {
	update,
	get,
	create,
	deletePost,
};
