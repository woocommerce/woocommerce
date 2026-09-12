/**
 * Internal dependencies
 */
import { isFeatureEnabled } from '~/utils/features';

export const hasTwoColumnLayout = (
	userPrefLayout: string,
	defaultHomescreenLayout: string,
	isSetupTaskListActive: boolean
) => {
	const hasTwoColumnContent =
		! isSetupTaskListActive || isFeatureEnabled( 'analytics' );

	return (
		( userPrefLayout || defaultHomescreenLayout ) === 'two_columns' &&
		hasTwoColumnContent
	);
};
