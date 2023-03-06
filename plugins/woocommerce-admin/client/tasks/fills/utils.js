/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

const onboardingData = getAdminSetting( 'onboarding' );

/**
 * Returns true if the merchant has indicated that they have another online shop while filling out the OBW
 */
export const isImportProduct = () => {
	return (
		window?.wcAdminFeatures?.[ 'import-products-task' ] &&
		onboardingData?.profile?.selling_venues &&
		onboardingData?.profile?.selling_venues !== 'no'
	);
};
