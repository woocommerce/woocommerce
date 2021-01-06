/**
 * Internal dependencies
 */
import { getCountryCode } from '../../../../dashboard/utils';

// Determine if a store should see the selective bundle install a/b experiment based on country and chosen industries
// from the profile wizard.
export const isSelectiveBundleInstallSegmentation = (
	country,
	industrySlugs
) => {
	return (
		getCountryCode( country ) === 'US' &&
		( industrySlugs.includes( 'food-drink' ) ||
			industrySlugs.includes( 'other' ) )
	);
};
