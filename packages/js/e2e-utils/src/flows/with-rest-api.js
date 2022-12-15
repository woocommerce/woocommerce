import factories from '../factories';
import { getSlug } from './utils';
import { Coupon, Setting, SimpleProduct, Order } from '@woocommerce/api';

const client = factories.api.withDefaultPermalinks;
const onboardingProfileEndpoint = '/wc-admin/onboarding/profile';
const productsEndpoint = '/wc/v3/products';
const productCategoriesEndpoint = '/wc/v3/products/categories';
const shippingClassesEndpoint = '/wc/v3/products/shipping_classes';
const shippingZoneEndpoint = '/wc/v3/shipping/zones';
const systemStatusEndpoint = '/wc/v3/system_status';
const taxClassesEndpoint = '/wc/v3/taxes/classes';
const taxRatesEndpoint = '/wc/v3/taxes';
const userEndpoint = '/wp/v2/users';

/**
 * Utility function to delete all merchant created data store objects.
 *
 * @param {unknown}       repository
 * @param {number | null} defaultObjectId
 * @param {Array<string>} statuses        Status of the object to check
 * @return {Promise<void>}
 */
const deleteAllRepositoryObjects = async (
	repository,
	defaultObjectId = null,
	statuses = [ 'draft', 'publish', 'trash' ]
) => {
	let objects;
	const minimum = defaultObjectId === null ? 0 : 1;

	for ( let s = 0; s < statuses.length; s++ ) {
		const status = statuses[ s ];
		objects = await repository.list( { status } );
		while ( objects.length > minimum ) {
			for ( let o = 0; o < objects.length; o++ ) {
				// Skip default data store object
				if ( objects[ o ].id === defaultObjectId ) {
					continue;
				}
				// We may be getting a cached copy of the dataset and the object has already been deleted.
				try {
					await repository.delete( objects[ o ].id );
				} catch ( e ) {}
			}
			objects = await repository.list( { status } );
		}
	}
};

/**
 * Utility to flatten a tax rate.
 *
 * @param {Object} taxRate Tax rate to be flattened.
 * @return {string} The flattened tax rate.
 */
const flattenTaxRate = ( taxRate ) => {
	return taxRate.rate + '/' + taxRate.class + '/' + taxRate.name;
};

/**
 * Utility functions that use the REST API to process the requested function.
 */
export const withRestApi = {
	/**
	 * Reset onboarding to equivalent of new site.
	 *
	 * @return {Promise<void>}
	 */
	resetOnboarding: async () => {
		const onboardingReset = {
			completed: false,
			industry: [],
			business_extensions: [],
			skipped: false,
			product_types: [],
			product_count: '0',
			selling_venues: 'no',
			revenue: 'none',
			theme: '',
			setup_client: false,
			wccom_connected: false,
		};

		const response = await client.put(
			onboardingProfileEndpoint,
			onboardingReset
		);
		expect( response.statusCode ).toEqual( 200 );
	},
	/**
	 * Use api package to delete coupons.
	 *
	 * @return {Promise} Promise resolving once coupons have been deleted.
	 */
	deleteAllCoupons: async () => {
		const repository = Coupon.restRepository( client );
		await deleteAllRepositoryObjects( repository );
	},
	/**
	 * Use api package to delete a coupon.
	 *
	 * @param {number} couponId Coupon ID.
	 * @return {Promise} Promise resolving once coupon has been deleted.
	 */
	deleteCoupon: async ( couponId ) => {
		const repository = Coupon.restRepository( client );
		await repository.delete( couponId );
	},
	/**
	 * Use api package to delete products.
	 *
	 * @return {Promise} Promise resolving once products have been deleted.
	 */
	deleteAllProducts: async () => {
		const repository = SimpleProduct.restRepository( client );
		await deleteAllRepositoryObjects( repository );
	},
	/**
	 * Use api package to delete a product.
	 *
	 * @param {number} productId Product ID.
	 * @return {Promise} Promise resolving once the product has been deleted.
	 */
	deleteProduct: async ( productId ) => {
		const repository = SimpleProduct.restRepository( client );
		await repository.delete( productId );
	},
	/**
	 * Use the API to delete all product attributes.
	 *
	 * @param {boolean} testResponse Test the response status code.
	 * @return {Promise} Promise resolving once attributes have been deleted.
	 */
	deleteAllProductAttributes: async ( testResponse = true ) => {
		const productAttributesPath = productsEndpoint + '/attributes';
		const productAttributes = await client.get( productAttributesPath );
		if ( productAttributes.data && productAttributes.data.length ) {
			for ( let a = 0; a < productAttributes.data.length; a++ ) {
				const response = await client.delete(
					productAttributesPath +
						`/${ productAttributes.data[ a ].id }?force=true`
				);
				if ( testResponse ) {
					expect( response.status ).toBe( 200 );
				}
			}
		}
	},
	/**
	 * Use the API to delete all product categories.
	 *
	 * @param {boolean} testResponse Test the response status code.
	 * @return {Promise} Promise resolving once categories have been deleted.
	 */
	deleteAllProductCategories: async ( testResponse = true ) => {
		const productCategoriesPath = productsEndpoint + '/categories';
		const productCategories = await client.get( productCategoriesPath );
		if ( productCategories.data && productCategories.data.length ) {
			for ( let c = 0; c < productCategories.data.length; c++ ) {
				// The default `uncategorized` category can't be deleted
				if ( productCategories.data[ c ].slug === 'uncategorized' ) {
					continue;
				}
				const response = await client.delete(
					productCategoriesPath +
						`/${ productCategories.data[ c ].id }?force=true`
				);
				if ( testResponse ) {
					expect( response.status ).toBe( 200 );
				}
			}
		}
	},
	/**
	 * Use the API to delete all product tags.
	 *
	 * @param {boolean} testResponse Test the response status code.
	 * @return {Promise} Promise resolving once tags have been deleted.
	 */
	deleteAllProductTags: async ( testResponse = true ) => {
		const productTagsPath = productsEndpoint + '/tags';
		const productTags = await client.get( productTagsPath );
		if ( productTags.data && productTags.data.length ) {
			for ( let t = 0; t < productTags.data.length; t++ ) {
				const response = await client.delete(
					productTagsPath +
						`/${ productTags.data[ t ].id }?force=true`
				);
				if ( testResponse ) {
					expect( response.status ).toBe( 200 );
				}
			}
		}
	},
	/**
	 * Use api package to delete all orders.
	 *
	 * @return {Promise} Promise resolving once orders have been deleted.
	 */
	deleteAllOrders: async () => {
		// We need to specfically filter on order status here to make sure we catch all orders to delete.
		const orderStatuses = [
			'pending',
			'processing',
			'on-hold',
			'completed',
			'cancelled',
			'refunded',
			'failed',
			'trash',
		];
		const repository = Order.restRepository( client );
		await deleteAllRepositoryObjects( repository, null, orderStatuses );
	},
	/**
	 * Use api package to delete an order.
	 *
	 * @param {number} orderId Order ID.
	 * @return {Promise} Promise resolving once the order has been deleted.
	 */
	deleteOrder: async ( orderId ) => {
		const repository = Order.restRepository( client );
		await repository.delete( orderId );
	},
	/**
	 * Adds a shipping zone along with a shipping method using the API.
	 *
	 * @param {string}  zoneName              Shipping zone name.
	 * @param {string}  zoneLocation          Shiping zone location. Defaults to country:US. For states use: state:US:CA.
	 * @param {string}  zipCode               Shipping zone zip code. Default is no zip code.
	 * @param {string}  zoneMethod            Shipping method type. Defaults to flat_rate (use also: free_shipping or local_pickup).
	 * @param {string}  cost                  Shipping method cost. Default is no cost.
	 * @param {Array}   additionalZoneMethods Array of additional zone methods to add to the shipping zone.
	 * @param {boolean} testResponse          Test the response status code.
	 */
	addShippingZoneAndMethod: async (
		zoneName,
		zoneLocation = 'country:US',
		zipCode = '',
		zoneMethod = 'flat_rate',
		cost = '',
		additionalZoneMethods = [],
		testResponse = true
	) => {
		const path = 'wc/v3/shipping/zones';

		const response = await client.post( path, { name: zoneName } );
		if ( testResponse ) {
			expect( response.status ).toEqual( 201 );
		}
		const zoneId = response.data.id;

		// Select shipping zone location
		const [ zoneType, zoneCode ] = zoneLocation.split( /:(.+)/ );
		const zoneLocationPayload = [
			{
				code: zoneCode,
				type: zoneType,
			},
		];

		// Fill shipping zone postcode if provided
		if ( zipCode ) {
			zoneLocationPayload.push( {
				code: zipCode,
				type: 'postcode',
			} );
		}

		const locationResponse = await client.put(
			path + `/${ zoneId }/locations`,
			zoneLocationPayload
		);
		if ( testResponse ) {
			expect( locationResponse.status ).toEqual( 200 );
		}

		// Add shipping zone method
		const methodPayload = {
			method_id: zoneMethod,
		};

		const methodsResponse = await client.post(
			path + `/${ zoneId }/methods`,
			methodPayload
		);
		if ( testResponse ) {
			expect( methodsResponse.status ).toEqual( 200 );
		}
		const methodId = methodsResponse.data.id;

		// Add in cost, if provided
		if ( cost ) {
			const costPayload = {
				settings: {
					cost,
				},
			};

			const costResponse = await client.put(
				path + `/${ zoneId }/methods/${ methodId }`,
				costPayload
			);
			if ( testResponse ) {
				expect( costResponse.status ).toEqual( 200 );
			}
		}

		// Add any additional zones, if provided
		if ( additionalZoneMethods.length > 0 ) {
			for ( let z = 0; z < additionalZoneMethods.length; z++ ) {
				// eslint-disable-next-line no-shadow
				const response = await client.post(
					path + `/${ zoneId }/methods`,
					{ method_id: additionalZoneMethods[ z ] }
				);
				if ( testResponse ) {
					expect( response.status ).toBe( 200 );
				}
			}
		}
	},
	/**
	 * Use api package to delete shipping zones.
	 *
	 * @param {boolean} testResponse Test the response status code.
	 * @return {Promise} Promise resolving once shipping zones have been deleted.
	 */
	deleteAllShippingZones: async ( testResponse = true ) => {
		const shippingZones = await client.get( shippingZoneEndpoint );
		if ( shippingZones.data && shippingZones.data.length ) {
			for ( let z = 0; z < shippingZones.data.length; z++ ) {
				// The data store doesn't support deleting the default zone.
				if ( shippingZones.data[ z ].id === 0 ) {
					continue;
				}
				const response = await client.delete(
					shippingZoneEndpoint +
						`/${ shippingZones.data[ z ].id }?force=true`
				);
				if ( testResponse ) {
					expect( response.status ).toBe( 200 );
				}
			}
		}
	},
	/**
	 * Use api package to delete shipping classes.
	 *
	 * @param {boolean} testResponse Test the response status code.
	 * @return {Promise} Promise resolving once shipping classes have been deleted.
	 */
	deleteAllShippingClasses: async ( testResponse = true ) => {
		const shippingClasses = await client.get( shippingClassesEndpoint );
		if ( shippingClasses.data && shippingClasses.data.length ) {
			for ( let c = 0; c < shippingClasses.data.length; c++ ) {
				const response = await client.delete(
					shippingClassesEndpoint +
						`/${ shippingClasses.data[ c ].id }?force=true`
				);
				if ( testResponse ) {
					expect( response.status ).toBe( 200 );
				}
			}
		}
	},
	/**
	 * Delete a customer account by their email address if the user exists.
	 *
	 * @param {string} emailAddress Customer user account email address.
	 * @return {Promise<void>}
	 */
	deleteCustomerByEmail: async ( emailAddress ) => {
		const query = {
			search: emailAddress,
			context: 'edit',
		};
		const customers = await client.get( userEndpoint, query );

		if ( customers.data && customers.data.length ) {
			for ( let c = 0; c < customers.data.length; c++ ) {
				const deleteUser = {
					id: customers.data[ c ].id,
					force: true,
					reassign: 1,
				};
				await client.delete(
					userEndpoint + `/${ deleteUser.id }`,
					deleteUser
				);
			}
		}
	},
	/**
	 * Reset a settings group to default values except selects.
	 *
	 * @param {unknown} settingsGroup
	 * @param {boolean} testResponse  Test the response status code.
	 * @return {Promise<void>}
	 */
	resetSettingsGroupToDefault: async (
		settingsGroup,
		testResponse = true
	) => {
		const settingsClient = Setting.restRepository( client );
		const settings = await settingsClient.list( settingsGroup );
		if ( ! settings.length ) {
			return;
		}

		for ( let s = 0; s < settings.length; s++ ) {
			// The rest api doesn't allow selects to be set to ''.
			if (
				settings[ s ].type === 'select' &&
				settings[ s ].default === ''
			) {
				continue;
			}
			const defaultSetting = {
				group_id: settingsGroup,
				id: settings[ s ].id,
				value: settings[ s ].default,
			};

			const response = await settingsClient.update(
				settingsGroup,
				defaultSetting.id,
				defaultSetting
			);
			// Multi-selects have a default '' but return an empty [].
			if ( testResponse && settings[ s ].type !== 'multiselect' ) {
				expect( response.value ).toBe( defaultSetting.value );
			}
		}
	},
	/**
	 * Update a setting to the supplied value.
	 *
	 * @param {string} settingsGroup The settings group to update.
	 * @param {string} settingId     The setting ID to update
	 * @param {Object} payload       An object with a key/value pair to update.
	 */
	updateSettingOption: async ( settingsGroup, settingId, payload = {} ) => {
		const settingsClient = Setting.restRepository( client );
		await settingsClient.update( settingsGroup, settingId, payload );
	},
	/**
	 * Update a payment gateway.
	 *
	 * @param {string}  paymentGatewayId The ID of the payment gateway to update.
	 * @param {Object}  payload          An object with the key/value pair to update.
	 * @param {boolean} testResponse     Test the response status code.
	 */
	updatePaymentGateway: async (
		paymentGatewayId,
		payload = {},
		testResponse = true
	) => {
		const response = await client.put(
			`/wc/v3/payment_gateways/${ paymentGatewayId }`,
			payload
		);
		if ( testResponse ) {
			expect( response.status ).toEqual( 200 );
		}
	},
	/**
	 * Create a batch of orders using the "Batch Create Order" API endpoint.
	 *
	 * @param {Array}   orders       Array of orders to be created
	 * @param {boolean} testResponse Test the response status code.
	 */
	batchCreateOrders: async ( orders, testResponse = true ) => {
		const path = '/wc/v3/orders/batch';
		const payload = { create: orders };

		const response = await client.post( path, payload );
		if ( testResponse ) {
			expect( response.status ).toBe( 200 );
		}
	},
	/**
	 * Add tax classes.
	 *
	 * @param {Array<Object>} taxClasses Array of tax class objects.
	 * @return {Promise<void>} Promise resolving once tax classes have been added.
	 */
	addTaxClasses: async ( taxClasses ) => {
		// Only add tax classes which don't already exist.
		const existingTaxClasses = await client.get( taxClassesEndpoint );
		const existingTaxNames = existingTaxClasses.data.map(
			( taxClass ) => taxClass.name
		);
		const newTaxClasses = taxClasses.filter(
			( taxClass ) => ! existingTaxNames.includes( taxClass.name )
		);

		for ( const taxClass of newTaxClasses ) {
			await client.post( taxClassesEndpoint, taxClass );
		}
	},
	/**
	 * Add tax rates.
	 *
	 * @param {Array<Object>} taxRates Array of tax rate objects.
	 * @return {Promise<void>}
	 */
	addTaxRates: async ( taxRates ) => {
		// Only add rates which don't already exist
		const existingTaxRates = await client.get( taxRatesEndpoint );
		const existingRates = existingTaxRates.data.map( ( taxRate ) =>
			flattenTaxRate( taxRate )
		);

		for ( const taxRate of taxRates ) {
			if ( ! existingRates.includes( flattenTaxRate( taxRate ) ) ) {
				await client.post( taxRatesEndpoint, taxRate );
			}
		}
	},
	/**
	 * Get the current environment from the WooCommerce system status API.
	 *
	 * For more details, see: https://woocommerce.github.io/woocommerce-rest-api-docs/#system-status-environment-properties
	 *
	 * @return {Promise<object>} The environment object from the API response.
	 */
	getSystemEnvironment: async () => {
		const response = await client.get( systemStatusEndpoint );
		if ( response.data.environment ) {
			return response.data.environment;
		}
	},
	/**
	 * Create a product category and return the ID. If the category already exists, the ID of the existing category is returned.
	 *
	 * @param {string} categoryName The name of the category to create
	 * @return {Promise<number>} The ID of the category.
	 */
	createProductCategory: async ( categoryName ) => {
		const payload = { name: categoryName };
		let categoryId;

		// First, convert the name to slug for easier searching
		const categorySlug = getSlug( categoryName );
		const category = await client.get(
			`${ productCategoriesEndpoint }?slug=${ categorySlug }`
		);

		// If the length is 0, nothing was found, so create the category
		if ( category.data ) {
			// If the length is 0, no existing category was found, so create the category
			if ( category.data.length === 0 ) {
				const response = await client.post(
					productCategoriesEndpoint,
					payload
				);
				categoryId = response.data.id;
			} else {
				categoryId = category.data[ 0 ].id;
			}
		}
		return categoryId;
	},
};
