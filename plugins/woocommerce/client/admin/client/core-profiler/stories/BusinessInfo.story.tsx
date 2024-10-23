/**
 * Internal dependencies
 */
import { BusinessInfo } from '../pages/BusinessInfo';

import '../style.scss';
import { WithSetupWizardLayout } from './WithSetupWizardLayout';

export const Basic = () => (
	<BusinessInfo
		sendEvent={ () => {} }
		navigationProgress={ 60 }
		context={ {
			geolocatedLocation: {
				latitude: '-37.83961',
				longitude: '144.94228',
				country_short: 'AU',
				country_long: 'Australia',
				region: 'Victoria',
				city: 'Port Melbourne',
			},
			userProfile: {},
			businessInfo: {},
			countries: [
				{
					key: 'US',
					label: 'United States',
				},
			],
			onboardingProfile: {
				is_store_country_set: false,
				industry: [ 'clothing_and_accessories' ],
				business_choice: 'im_just_starting_my_business',
			},
		} }
	/>
);

export default {
	title: 'WooCommerce Admin/Application/Core Profiler/Business Info',
	component: BusinessInfo,
	decorators: [ WithSetupWizardLayout ],
};
