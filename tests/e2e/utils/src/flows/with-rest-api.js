import factories from '../factories';
import {Coupon, Setting, SimpleProduct} from '@woocommerce/api';

const client = factories.api.withDefaultPermalinks;
const onboardingProfileEndpoint = '/wc-admin/onboarding/profile';
const shippingZoneEndpoint = '/wc/v3/shipping/zones';
const userEndpoint = '/wp/v2/users';

/**
 * Utility function to delete all merchant created data store objects.
 *
 * @param repository
 * @param defaultObjectId
 * @returns {Promise<void>}
 */
const deleteAllRepositoryObjects = async ( repository, defaultObjectId = null ) => {
	let objects;
	const minimum = defaultObjectId == null ? 0 : 1;
	const statuses = ['draft','publish','trash'];

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
	}
};
