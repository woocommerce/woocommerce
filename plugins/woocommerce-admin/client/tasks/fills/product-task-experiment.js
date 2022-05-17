/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

const onboardingData = getAdminSetting( 'onboarding' );

export const isImportProductExperiment = () => {
	return (
		window?.wcAdminFeatures?.[ 'experimental-import-products-task' ] &&
		onboardingData?.profile?.selling_venues &&
		onboardingData?.profile?.selling_venues !== 'no'
	);
};
