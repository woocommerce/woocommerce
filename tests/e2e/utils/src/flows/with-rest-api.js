import factories from '../factories';
import {Coupon, Setting, SimpleProduct, Order} from '@woocommerce/api';

const client = factories.api.withDefaultPermalinks;
const onboardingProfileEndpoint = '/wc-admin/onboarding/profile';
const shippingZoneEndpoint = '/wc/v3/shipping/zones';
const shippingClassesEndpoint = '/wc/v3/products/shipping_classes';
const userEndpoint = '/wp/v2/users';
const systemStatusEndpoint = '/wc/v3/system_status';

/**
 * Utility function to delete all merchant created data store objects.
 *
 * @param repository
 * @param defaultObjectId
 * @param statuses Status of the object to check
 * @returns {Promise<void>}
 */
const deleteAllRepositoryObjects = async ( repository, defaultObjectId = null, statuses = [ 'draft', 'publish', 'trash' ] ) => {
	let objects;
	const minimum = defaultObjectId == null ? 0 : 1;

	for ( let s = 0; s < statuses.length; s++ ) {
		const status = statuses[ s ];
		objects = await repository.list( { status } );
		while (objects.length > minimum) {
			for (let o = 0; o < objects.length; o++) {
				// Skip default data store object
				if (objects[o].id == defaultObjectId) {
					continue;
				}
				// We may be getting a cached copy of the dataset and the object has already been deleted.
				try {
					await repository.delete(objects[o].id);
				} catch (e) {}
			}
			objects = await repository.list( { status } );
		}
	}
};

/**
 * Utility functions that use the REST API to process the requested function.
 */
export const withRestApi = {
	/**
	 * Reset onboarding to equivalent of new site.
	 * @returns {Promise<void>}
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

		const response = await client.put( onboardingProfileEndpoint, onboardingReset );
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
	 * Use api package to delete products.
	 *
	 * @return {Promise} Promise resolving once products have been deleted.
	 */
	deleteAllProducts: async () => {
		const repository = SimpleProduct.restRepository( client );
		await deleteAllRepositoryObjects( repository );
	},
	/**
	 * Use api package to delete all orders.
	 *
	 * @return {Promise} Promise resolving once orders have been deleted.
	 */
	deleteAllOrders: async () => {
		// We need to specfically filter on order status here to make sure we catch all orders to delete.
		const orderStatuses = ['pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed', 'trash'];
		const repository = Order.restRepository( client );
		await deleteAllRepositoryObjects( repository, null, orderStatuses );
	},
	/**
	 * Adds a shipping zone along with a shipping method using the API.
	 *
	 * @param zoneName Shipping zone name.
	 * @param zoneLocation Shiping zone location. Defaults to country:US. For states use: state:US:CA.
	 * @param zipCode Shipping zone zip code. Default is no zip code.
	 * @param zoneMethod Shipping method type. Defaults to flat_rate (use also: free_shipping or local_pickup).
	 * @param cost Shipping method cost. Default is no cost.
	 * @param additionalZoneMethods Array of additional zone methods to add to the shipping zone.
	 */
	addShippingZoneAndMethod: async (
		zoneName,
		zoneLocation = 'country:US',
		zipCode = '',
		zoneMethod = 'flat_rate',
		cost = '',
		additionalZoneMethods = [] ) => {

	   const path = 'wc/v3/shipping/zones';

	   const response = await client.post( path, { name: zoneName } );
	   expect(response.statusCode).toEqual(201);
	   let zoneId = response.data.id;

	   // Select shipping zone location
	   let [ zoneType, zoneCode ] = zoneLocation.split(/:(.+)/);
	   let zoneLocationPayload = [
		   {
			   code: zoneCode,
			   type: zoneType,
		   }
	   ];

	   // Fill shipping zone postcode if provided
	   if ( zipCode ) {
		   zoneLocationPayload.push( {
			   code: zipCode,
			   type: "postcode",
		   } );
	   }

	   const locationResponse = await client.put( path + `/${zoneId}/locations`, zoneLocationPayload );
	   expect(locationResponse.statusCode).toEqual(200);

	   // Add shipping zone method
	   let methodPayload = {
		   method_id: zoneMethod
	   }

	   const methodsResponse = await client.post( path + `/${zoneId}/methods`, methodPayload );
	   expect(methodsResponse.statusCode).toEqual(200);
	   let methodId = methodsResponse.data.id;

	   // Add in cost, if provided
	   if ( cost ) {
		   let costPayload = {
			   settings: {
				   cost: cost
			   }
		   }

		   const costResponse = await client.put( path + `/${zoneId}/methods/${methodId}`, costPayload );
		   expect(costResponse.statusCode).toEqual(200);
	   }

	   // Add any additional zones, if provided
	   if (additionalZoneMethods.length > 0) {
		   for ( let z = 0; z < additionalZoneMethods.length; z++ ) {
			   let response = await client.post( path + `/${zoneId}/methods`, { method_id: additionalZoneMethods[z] } );
			   expect(response.statusCode).toEqual(200);
		   }
	   }
    },
	/**
	 * Use api package to delete shipping zones.
	 *
	 * @return {Promise} Promise resolving once shipping zones have been deleted.
	 */
	deleteAllShippingZones: async () => {
		const shippingZones = await client.get( shippingZoneEndpoint );
		if ( shippingZones.data && shippingZones.data.length ) {
			for ( let z = 0; z < shippingZones.data.length; z++ ) {
				// The data store doesn't support deleting the default zone.
				if ( shippingZones.data[z].id == 0 ) {
					continue;
				}
				const response = await client.delete( shippingZoneEndpoint + `/${shippingZones.data[z].id}?force=true` );
				expect( response.statusCode ).toBe( 200 );
			}
		}
	},
	/**
	 * Use api package to delete shipping classes.
	 *
	 * @return {Promise} Promise resolving once shipping classes have been deleted.
	 */
	deleteAllShippingClasses: async () => {
		const shippingClasses = await client.get( shippingClassesEndpoint );
		if ( shippingClasses.data && shippingClasses.data.length ) {
			for ( let c = 0; c < shippingClasses.data.length; c++ ) {
				const response = await client.delete( shippingClassesEndpoint + `/${shippingClasses.data[c].id}?force=true` );
				expect( response.statusCode ).toBe( 200 );
			}
		}
	},
	/**
	 * Delete a customer account by their email address if the user exists.
	 *
	 * @param emailAddress Customer user account email address.
	 * @returns {Promise<void>}
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
					id: customers.data[c].id,
					force: true,
					reassign: 1,
				}
				await client.delete( userEndpoint + `/${ deleteUser.id }`, deleteUser );
			}
		}
	},
	/**
	 * Reset a settings group to default values except selects.
	 * @param settingsGroup
	 * @returns {Promise<void>}
	 */
	resetSettingsGroupToDefault: async ( settingsGroup ) => {
		const settingsClient = Setting.restRepository( client );
		const settings = await settingsClient.list( settingsGroup );
		if ( ! settings.length  ) {
			return;
		}

		for ( let s = 0; s < settings.length; s++ ) {
			// The rest api doesn't allow selects to be set to ''.
			if ( settings[s].type == 'select' && settings[s].default == '' ) {
				continue;
			}
			const defaultSetting = {
				group_id: settingsGroup,
				id: settings[s].id,
				value: settings[s].default,
			};

			const response = await settingsClient.update( settingsGroup, defaultSetting.id, defaultSetting );
			// Multi-selects have a default '' but return an empty [].
			if ( settings[s].type != 'multiselect' ) {
				expect( response.value ).toBe( defaultSetting.value );
			}
		}
	},
	/**
	 * Update a setting to the supplied value.
	 *
	 * @param {string} settingsGroup The settings group to update.
	 * @param {string} settingId The setting ID to update
	 * @param {object} payload An object with a key/value pair to update.
	 */
	updateSettingOption: async ( settingsGroup, settingId, payload = {} ) => {
		const settingsClient = Setting.restRepository( client );
		await settingsClient.update( settingsGroup, settingId, payload );
	},
	/**
	 * Update a payment gateway.
	 *
	 * @param {string} paymentGatewayId The ID of the payment gateway to update.
	 * @param {object} payload An object with the key/value pair to update.
	 */
	updatePaymentGateway: async ( paymentGatewayId, payload = {} ) => {
		const response = await client.put( `/wc/v3/payment_gateways/${paymentGatewayId}`, payload );
		expect( response.statusCode ).toBe( 200 );
	},
	/**
	 * Create a batch of orders using the "Batch Create Order" API endpoint.
	 *
	 * @param orders Array of orders to be created
	 */
	batchCreateOrders: async (orders) => {
		const path = '/wc/v3/orders/batch';
		const payload = { create: orders };

		const { statusCode } = await client.post(path, payload);

		expect(statusCode).toEqual(200);
	},
	/**
	 * Get the current environment from the WooCommerce system status API.
	 *
	 * For more details, see: https://woocommerce.github.io/woocommerce-rest-api-docs/#system-status-environment-properties
	 *
	 * @returns {Promise<object>} The environment object from the API response.
	 */
	getSystemEnvironment: async () => {
		const response = await client.get( systemStatusEndpoint );
		if ( response.data.environment ) {
			return response.data.environment;
		} else {
			return;
		}
	}
};
