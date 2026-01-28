/**
 * External dependencies
 */
import e2eUtils from '@woocommerce/e2e-utils-playwright';

const { createClient, WC_API_PATH } = e2eUtils;

/**
 * Internal dependencies
 */
import { admin } from '../test-data/data';
import playwrightConfig from '../playwright.config';
import type { RestApiClient, RestApiResponse } from '../fixtures/fixtures';

/**
 * Store details for updating store information.
 */
export interface StoreDetails {
	address: string;
	city: string;
	countryCode: string;
	zip: string;
}

/**
 * Product data for creating products.
 */
export interface ProductData {
	[ key: string ]: unknown;
}

/**
 * Shipping zone data.
 */
export interface ShippingZone {
	name: string;
	[ key: string ]: unknown;
}

/**
 * Shipping method data.
 */
export interface ShippingMethod {
	method_id: string;
	[ key: string ]: unknown;
}

/**
 * Product variation data.
 */
export interface ProductVariation {
	[ key: string ]: unknown;
}

/**
 * Query parameters for API requests.
 */
export interface QueryParams {
	[ key: string ]: unknown;
}

/**
 * Batch response data.
 */
export interface BatchResponse {
	[ key: string ]: unknown;
}

/**
 * Variation batch response.
 */
interface VariationBatchResponse {
	create: Array< { id: number } >;
}

const api = createClient( playwrightConfig.use?.baseURL as string, {
	type: 'basic',
	username: admin.username,
	password: admin.password,
} ) as RestApiClient;

export const update = {
	/**
	 * Update store details.
	 *
	 * @param store - Store details to update
	 */
	storeDetails: async ( store: StoreDetails ): Promise< void > => {
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

	/**
	 * Enable Cash on Delivery payment gateway.
	 */
	enableCashOnDelivery: async (): Promise< void > => {
		await api.put( 'payment_gateways/cod', {
			enabled: true,
		} );
	},

	/**
	 * Disable Cash on Delivery payment gateway.
	 */
	disableCashOnDelivery: async (): Promise< void > => {
		await api.put( 'payment_gateways/cod', {
			enabled: false,
		} );
	},
};

export const get = {
	/**
	 * Get coupons.
	 *
	 * @param params - Query parameters
	 * @return Promise resolving to coupons data
	 */
	coupons: async ( params: QueryParams ): Promise< unknown > => {
		const response: RestApiResponse = await api
			.get( `${ WC_API_PATH }/coupons`, params )
			.then( ( r: RestApiResponse ) => r );

		return response.data;
	},

	/**
	 * Get orders.
	 *
	 * @param params - Query parameters
	 * @return Promise resolving to orders data
	 */
	orders: async ( params: QueryParams ): Promise< unknown > => {
		const response: RestApiResponse = await api
			.get( `${ WC_API_PATH }/orders`, params )
			.then( ( r: RestApiResponse ) => r );

		return response.data;
	},

	/**
	 * Get products.
	 *
	 * @param params - Query parameters
	 * @return Promise resolving to products data
	 */
	products: async ( params: QueryParams ): Promise< unknown > => {
		const response: RestApiResponse = await api
			.get( `${ WC_API_PATH }/products`, params )
			.then( ( r: RestApiResponse ) => r );

		return response.data;
	},

	/**
	 * Get product attributes.
	 *
	 * @param params - Query parameters
	 * @return Promise resolving to product attributes data
	 */
	productAttributes: async ( params: QueryParams ): Promise< unknown > => {
		const response: RestApiResponse = await api
			.get( `${ WC_API_PATH }/products/attributes`, params )
			.then( ( r: RestApiResponse ) => r );

		return response.data;
	},

	/**
	 * Get product categories.
	 *
	 * @param params - Query parameters
	 * @return Promise resolving to product categories data
	 */
	productCategories: async ( params: QueryParams ): Promise< unknown > => {
		const response: RestApiResponse = await api
			.get( `${ WC_API_PATH }/products/categories`, params )
			.then( ( r: RestApiResponse ) => r );

		return response.data;
	},

	/**
	 * Get product tags.
	 *
	 * @param params - Query parameters
	 * @return Promise resolving to product tags data
	 */
	productTags: async ( params: QueryParams ): Promise< unknown > => {
		const response: RestApiResponse = await api
			.get( `${ WC_API_PATH }/products/tags`, params )
			.then( ( r: RestApiResponse ) => r );
		return response.data;
	},

	/**
	 * Get shipping classes.
	 *
	 * @param params - Query parameters
	 * @return Promise resolving to shipping classes data
	 */
	shippingClasses: async ( params: QueryParams ): Promise< unknown > => {
		const response: RestApiResponse = await api
			.get( `${ WC_API_PATH }/products/shipping_classes`, params )
			.then( ( r: RestApiResponse ) => r );

		return response.data;
	},

	/**
	 * Get shipping zones.
	 *
	 * @param params - Query parameters
	 * @return Promise resolving to shipping zones data
	 */
	shippingZones: async ( params: QueryParams ): Promise< unknown > => {
		const response: RestApiResponse = await api
			.get( `${ WC_API_PATH }/shipping/zones`, params )
			.then( ( r: RestApiResponse ) => r );

		return response.data;
	},

	/**
	 * Get tax classes.
	 *
	 * @return Promise resolving to tax classes data
	 */
	taxClasses: async (): Promise< unknown > => {
		const response: RestApiResponse = await api
			.get( `${ WC_API_PATH }/taxes/classes` )
			.then( ( r: RestApiResponse ) => r );

		return response.data;
	},

	/**
	 * Get tax rates.
	 *
	 * @param params - Query parameters
	 * @return Promise resolving to tax rates data
	 */
	taxRates: async ( params: QueryParams ): Promise< unknown > => {
		const response: RestApiResponse = await api
			.get( `${ WC_API_PATH }/taxes`, params )
			.then( ( r: RestApiResponse ) => r );

		return response.data;
	},
};

export const create = {
	/**
	 * Create a product.
	 *
	 * @param product - Product data
	 * @return Promise resolving to the product ID
	 */
	product: async ( product: ProductData ): Promise< number > => {
		const response: RestApiResponse = await api.post(
			`${ WC_API_PATH }/products`,
			product
		);

		return ( response.data as { id: number } ).id;
	},

	/**
	 * Create a shipping zone.
	 *
	 * @param zone - Shipping zone data
	 * @return Promise resolving to the zone ID
	 */
	shippingZone: async ( zone: ShippingZone ): Promise< number > => {
		const response: RestApiResponse = await api.post(
			`${ WC_API_PATH }/shipping/zones`,
			zone
		);

		return ( response.data as { id: number } ).id;
	},

	/**
	 * Create a shipping method for a zone.
	 *
	 * @param zoneId - Zone ID to add the method to
	 * @param method - Shipping method data
	 * @return Promise resolving to the method ID
	 */
	shippingMethod: async (
		zoneId: number | string,
		method: ShippingMethod
	): Promise< number > => {
		const response: RestApiResponse = await api.post(
			`${ WC_API_PATH }/shipping/zones/${ zoneId }/methods`,
			method
		);

		return ( response.data as { id: number } ).id;
	},

	/**
	 * Batch create product variations.
	 *
	 * @see {@link [Batch update product variations](https://woocommerce.github.io/woocommerce-rest-api-docs/#batch-update-product-variations)}
	 * @param productId  - Product ID to add variations to
	 * @param variations - Array of variations to add
	 * @return Array of variation IDs
	 */
	productVariations: async (
		productId: number | string,
		variations: ProductVariation[]
	): Promise< number[] > => {
		const response: RestApiResponse = await api.post(
			`${ WC_API_PATH }/products/${ productId }/variations/batch`,
			{
				create: variations,
			}
		);

		return ( response.data as VariationBatchResponse ).create.map(
			( { id } ) => id
		);
	},
};

export const deletePost = {
	/**
	 * Delete coupons by IDs.
	 *
	 * @param ids - Array of coupon IDs to delete
	 * @return Promise resolving to the batch response data
	 */
	coupons: async ( ids: number[] ): Promise< BatchResponse > => {
		const res: RestApiResponse = await api
			.post( `${ WC_API_PATH }/coupons/batch`, { delete: ids } )
			.then( ( response: RestApiResponse ) => response );

		return res.data as BatchResponse;
	},

	/**
	 * Delete a single product by ID.
	 *
	 * @param id - Product ID to delete
	 */
	product: async ( id: number ): Promise< void > => {
		await api.delete( `${ WC_API_PATH }/products/${ id }`, {
			force: true,
		} );
	},

	/**
	 * Delete products by IDs.
	 *
	 * @param ids - Array of product IDs to delete
	 * @return Promise resolving to the batch response data
	 */
	products: async ( ids: number[] ): Promise< BatchResponse > => {
		const res: RestApiResponse = await api
			.post( `${ WC_API_PATH }/products/batch`, { delete: ids } )
			.then( ( response: RestApiResponse ) => response );
		return res.data as BatchResponse;
	},

	/**
	 * Delete product attributes by IDs.
	 *
	 * @param id - Array of attribute IDs to delete
	 * @return Promise resolving to the batch response data
	 */
	productAttributes: async ( id: number[] ): Promise< BatchResponse > => {
		const res: RestApiResponse = await api
			.post( `${ WC_API_PATH }/products/attributes/batch`, {
				delete: id,
			} )
			.then( ( response: RestApiResponse ) => response );
		return res.data as BatchResponse;
	},

	/**
	 * Delete product categories by IDs.
	 *
	 * @param ids - Array of category IDs to delete
	 * @return Promise resolving to the batch response data
	 */
	productCategories: async ( ids: number[] ): Promise< BatchResponse > => {
		const res: RestApiResponse = await api
			.post( `${ WC_API_PATH }/products/categories/batch`, {
				delete: ids,
			} )
			.then( ( response: RestApiResponse ) => response );
		return res.data as BatchResponse;
	},

	/**
	 * Delete product tags by IDs.
	 *
	 * @param ids - Array of tag IDs to delete
	 * @return Promise resolving to the batch response data
	 */
	productTags: async ( ids: number[] ): Promise< BatchResponse > => {
		const res: RestApiResponse = await api
			.post( `${ WC_API_PATH }/products/tags/batch`, { delete: ids } )
			.then( ( response: RestApiResponse ) => response );
		return res.data as BatchResponse;
	},

	/**
	 * Delete a single order by ID.
	 *
	 * @param id - Order ID to delete
	 */
	order: async ( id: number ): Promise< void > => {
		await api.delete( `${ WC_API_PATH }/orders/${ id }`, {
			force: true,
		} );
	},

	/**
	 * Delete orders by IDs.
	 *
	 * @param ids - Array of order IDs to delete
	 * @return Promise resolving to the batch response data
	 */
	orders: async ( ids: number[] ): Promise< BatchResponse > => {
		const res: RestApiResponse = await api
			.post( `${ WC_API_PATH }/orders/batch`, { delete: ids } )
			.then( ( response: RestApiResponse ) => response );
		return res.data as BatchResponse;
	},

	/**
	 * Delete shipping classes by IDs.
	 *
	 * @param ids - Array of shipping class IDs to delete
	 * @return Promise resolving to the batch response data
	 */
	shippingClasses: async ( ids: number[] ): Promise< BatchResponse > => {
		const res: RestApiResponse = await api
			.post( `${ WC_API_PATH }/products/shipping_classes/batch`, {
				delete: ids,
			} )
			.then( ( response: RestApiResponse ) => response );
		return res.data as BatchResponse;
	},

	/**
	 * Delete a shipping zone by ID.
	 *
	 * @param id - Shipping zone ID to delete
	 * @return Promise resolving to the response data
	 */
	shippingZone: async ( id: number ): Promise< BatchResponse > => {
		const res: RestApiResponse = await api
			.delete( `${ WC_API_PATH }/shipping/zones/${ id }`, {
				force: true,
			} )
			.then( ( response: RestApiResponse ) => response );
		return res.data as BatchResponse;
	},

	/**
	 * Delete a tax class by slug.
	 *
	 * @param slug - Tax class slug to delete
	 * @return Promise resolving to the response data
	 */
	taxClass: async ( slug: string ): Promise< BatchResponse > => {
		const res: RestApiResponse = await api
			.delete( `${ WC_API_PATH }/taxes/classes/${ slug }`, {
				force: true,
			} )
			.then( ( response: RestApiResponse ) => response );
		return res.data as BatchResponse;
	},

	/**
	 * Delete tax rates by IDs.
	 *
	 * @param ids - Array of tax rate IDs to delete
	 * @return Promise resolving to the batch response data
	 */
	taxRates: async ( ids: number[] ): Promise< BatchResponse > => {
		const res: RestApiResponse = await api
			.post( `${ WC_API_PATH }/taxes/batch`, { delete: ids } )
			.then( ( response: RestApiResponse ) => response );
		return res.data as BatchResponse;
	},
};
