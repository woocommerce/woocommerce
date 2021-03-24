/**
 * Internal dependencies
 */
import { getCountryCode } from '~/dashboard/utils';

// Determine if a store should see the selective bundle install a/b experiment based on country and chosen industries
// from the profile wizard.
const SUPPORTED_COUNTRIES = [
	'US',
	'BR',
	'FR',
	'ID',
	'GB',
	'DE',
	'VN',
	'CA',
	'PL',
	'MY',
	'AU',
	'NG',
	'GR',
	'BE',
	'PT',
	'DK',
	'SE',
	'JP',
	'IE',
	'NZ',
];
export const isSelectiveBundleInstallSegmentation = ( country ) => {
	return SUPPORTED_COUNTRIES.includes( getCountryCode( country ) );
};
