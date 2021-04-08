import factories from '../factories';
import {Coupon, Setting, SimpleProduct} from '@woocommerce/api';

const client = factories.api.withDefaultPermalinks;
const onboardingProfileEndpoint = '/wc-admin/onboarding/profile';
const shippingZoneEndpoint = '/wc/v3/shipping/zones';

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

	objects = await repository.list();
	while ( objects.length > minimum ) {
		for (let o = 0; o < objects.length; o++ ) {
			// Skip default data store object
			if ( objects[ o ].id == defaultObjectId ) {
				continue;
			}
			await repository.delete( objects[ o ].id );
		}
		objects = await repository.list();
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
	resetAllowTracking: async () => {
		const settingsClient = Setting.restRepository( client );

		const allowTracking = {
			id: 'woocommerce_allow_tracking',
			value: 'no'
		};
		const response = await settingsClient.update( 'advanced', allowTracking.id, allowTracking );
		expect( response.value ).toBe( 'no' );
	}
};
