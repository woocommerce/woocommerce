import factories from '../factories';
import { Setting } from '@woocommerce/api';

const client = factories.api.withDefaultPermalinks;
const onboardingProfileEndpoint = '/wc-admin/onboarding/profile';
const shippingZoneEndpoint = '/wc/v3/shipping/zones';

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
