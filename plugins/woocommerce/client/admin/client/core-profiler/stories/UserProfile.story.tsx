/**
 * Internal dependencies
 */
import { UserProfile } from '../pages/UserProfile';

import '../style.scss';
import { WithSetupWizardLayout } from './WithSetupWizardLayout';

export const Basic = () => (
	<UserProfile
		sendEvent={ () => {} }
		navigationProgress={ 40 }
		context={ {
			userProfile: {},
		} }
	/>
);

export default {
	title: 'WooCommerce Admin/Application/Core Profiler/User Profile',
	component: UserProfile,
	decorators: [ WithSetupWizardLayout ],
};
