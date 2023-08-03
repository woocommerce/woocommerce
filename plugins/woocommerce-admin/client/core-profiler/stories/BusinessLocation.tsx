/**
 * Internal dependencies
 */
import { BusinessLocation } from '../pages/BusinessLocation';

import '../style.scss';
import { WithSetupWizardLayout } from './WithSetupWizardLayout';

export const Basic = () => (
	<BusinessLocation
		sendEvent={ () => {} }
		navigationProgress={ 80 }
		context={ {
			countries: [
				{
					key: 'US',
					label: 'United States',
				},
			],
		} }
	/>
);

export default {
	title: 'WooCommerce Admin/Application/Core Profiler/Business Location',
	component: BusinessLocation,
	decorators: [ WithSetupWizardLayout ],
};
