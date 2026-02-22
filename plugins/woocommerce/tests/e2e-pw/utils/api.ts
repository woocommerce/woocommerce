/**
 * External dependencies
 */
import { createClient, WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { admin } from '../test-data/data';
import playwrightConfig from '../playwright.config';

const api = createClient( playwrightConfig.use.baseURL, {
	type: 'basic',
	username: admin.username,
	password: admin.password,
} );

export const update = {
	storeDetails: async ( store: any ) => {
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

export const get = {
	coupons: async ( params: any ) => {
		const response = await api.get( `${ WC_API_PATH }/coupons`, params );
		return response.data;
	},
	orders: async ( params: any ) => {
		const response = await api.get( `${ WC_API_PATH }/orders`, params );
		return response.data;
	},
	products: async ( params: any ) => {
		const response = await api.get( `${ WC_API_PATH }/products`, params );
		return response.data;
	},
	productAttributes: async ( params: any ) => {
		const response = await api.get(
			`${ WC_API_PATH }/products/attributes`,
			params
		);
		return response.data;
	},
	productCategories: async ( params: any ) => {
		const response = await api.get(
			`${ WC_API_PATH }/products/categories`,
			params
		);
		return response.data;
	},
	productTags: async ( params: any ) => {
		const response = await api.get(
			`${ WC_API_PATH }/products/tags`,
			params
		);
		return response.data;
	},
	shippingClasses: async ( params: any ) => {
		const response = await api.get(
			`${ WC_API_PATH }/products/shipping_classes`,
			params
		);
		return response.data;
	},
	shippingZones: async ( params: any ) => {
		const response = await api.get(
			`${ WC_API_PATH }/shipping/zones`,
			params
		);
		return response.data;
	},
	taxClasses: async () => {
		const response = await api.get( `${ WC_API_PATH }/taxes/classes` );
		return response.data;
	},
	taxRates: async ( params: any ) => {
		const response = await api.get( `${ WC_API_PATH }/taxes`, params );
		return response.data;
	},
};

export const create = {
	product: async ( product: any ) => {
		const response = await api.post( `${ WC_API_PATH }/products`, product );

		return response.data.id;
	},
	shippingZone: async ( zone: any ) => {
		const response = await api.post(
			`${ WC_API_PATH }/shipping/zones`,
			zone
		);

		return response.data.id;
	},
	shippingMethod: async ( zoneId: number | string, method: any ) => {
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
	productVariations: async (
		productId: number | string,
		variations: any[]
	) => {
		const response = await api.post(
			`${ WC_API_PATH }/products/${ productId }/variations/batch`,
			{
				create: variations,
			}
		);

		return response.data.create.map( ( { id }: { id: number } ) => id );
	},
};

export const deletePost = {
	coupons: async ( ids: number[] ) => {
		const res = await api.post( `${ WC_API_PATH }/coupons/batch`, {
			delete: ids,
		} );
		return res.data;
	},
	product: async ( id: number ) => {
		await api.delete( `${ WC_API_PATH }/products/${ id }`, {
			force: true,
		} );
	},
	products: async ( ids: number[] ) => {
		const res = await api.post( `${ WC_API_PATH }/products/batch`, {
			delete: ids,
		} );
		return res.data;
	},
	productAttributes: async ( ids: number[] ) => {
		const res = await api.post(
			`${ WC_API_PATH }/products/attributes/batch`,
			{ delete: ids }
		);
		return res.data;
	},
	productCategories: async ( ids: number[] ) => {
		const res = await api.post(
			`${ WC_API_PATH }/products/categories/batch`,
			{ delete: ids }
		);
		return res.data;
	},
	productTags: async ( ids: number[] ) => {
		const res = await api.post( `${ WC_API_PATH }/products/tags/batch`, {
			delete: ids,
		} );
		return res.data;
	},
	order: async ( id: number ) => {
		await api.delete( `${ WC_API_PATH }/orders/${ id }`, {
			force: true,
		} );
	},
	orders: async ( ids: number[] ) => {
		const res = await api.post( `${ WC_API_PATH }/orders/batch`, {
			delete: ids,
		} );
		return res.data;
	},
	shippingClasses: async ( ids: number[] ) => {
		const res = await api.post(
			`${ WC_API_PATH }/products/shipping_classes/batch`,
			{ delete: ids }
		);
		return res.data;
	},
	shippingZone: async ( id: number ) => {
		const res = await api.delete(
			`${ WC_API_PATH }/shipping/zones/${ id }`,
			{ force: true }
		);
		return res.data;
	},
	taxClass: async ( slug: string ) => {
		const res = await api.delete(
			`${ WC_API_PATH }/taxes/classes/${ slug }`,
			{ force: true }
		);
		return res.data;
	},
	taxRates: async ( ids: number[] ) => {
		const res = await api.post( `${ WC_API_PATH }/taxes/batch`, {
			delete: ids,
		} );
		return res.data;
	},
};
