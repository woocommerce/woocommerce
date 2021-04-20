/**
 * External dependencies
 */
import '@wordpress/core-data';

/**
 * Internal dependencies
 */
import type { REVIEWS_STORE_NAME } from './reviews';
import type { SETTINGS_STORE_NAME } from './settings';
import type { PLUGINS_STORE_NAME } from './plugins';
import type { ONBOARDING_STORE_NAME } from './onboarding';
import type { USER_STORE_NAME } from './user';
import type { OPTIONS_STORE_NAME } from './options';
import type { NAVIGATION_STORE_NAME } from './navigation';
import type { NOTES_STORE_NAME } from './notes';
import type { REPORTS_STORE_NAME } from './reports';
import type { ITEMS_STORE_NAME } from './items';
import { OnboardingSelectors } from './onboarding/selectors';
import { WPDataSelectors } from './types';
import { PluginSelectors } from './plugins/selectors';

export * from './types';
export { SETTINGS_STORE_NAME } from './settings';
export { withSettingsHydration } from './settings/with-settings-hydration';
export { useSettings } from './settings/use-settings';

export { PLUGINS_STORE_NAME } from './plugins';
export type { Plugin } from './plugins/types';
export { pluginNames } from './plugins/constants';
export { withPluginsHydration } from './plugins/with-plugins-hydration';

export { ONBOARDING_STORE_NAME } from './onboarding';
export { withOnboardingHydration } from './onboarding/with-onboarding-hydration';

export { USER_STORE_NAME } from './user';
export { withCurrentUserHydration } from './user/with-current-user-hydration';
export { useUser } from './user/use-user';
export { useUserPreferences } from './user/use-user-preferences';

export { OPTIONS_STORE_NAME } from './options';
export {
	withOptionsHydration,
	useOptionsHydration,
} from './options/with-options-hydration';

export { REVIEWS_STORE_NAME } from './reviews';

export { NOTES_STORE_NAME } from './notes';

export { REPORTS_STORE_NAME } from './reports';

export { ITEMS_STORE_NAME } from './items';
export { getLeaderboard, searchItemsByString } from './items/utils';

export { NAVIGATION_STORE_NAME } from './navigation';
export { withNavigationHydration } from './navigation/with-navigation-hydration';

export {
	getFilterQuery,
	getSummaryNumbers,
	getReportTableData,
	getReportTableQuery,
	getReportChartData,
	getTooltipValueFormat,
} from './reports/utils';

export {
	MAX_PER_PAGE,
	QUERY_DEFAULTS,
	NAMESPACE,
	WC_ADMIN_NAMESPACE,
	WCS_NAMESPACE,
	SECOND,
	MINUTE,
	HOUR,
	DAY,
	WEEK,
	MONTH,
} from './constants';

export { EXPORT_STORE_NAME } from './export';

export { IMPORT_STORE_NAME } from './import';

export type WCDataStoreName =
	| typeof REVIEWS_STORE_NAME
	| typeof SETTINGS_STORE_NAME
	| typeof PLUGINS_STORE_NAME
	| typeof ONBOARDING_STORE_NAME
	| typeof USER_STORE_NAME
	| typeof OPTIONS_STORE_NAME
	| typeof NAVIGATION_STORE_NAME
	| typeof NOTES_STORE_NAME
	| typeof REPORTS_STORE_NAME
	| typeof ITEMS_STORE_NAME;

// As we add types to all the package selectors we can fill out these unknown types with real ones. See one
// of the already typed selectors for an example of how you can do this.
export type WCSelectorType< T > = T extends typeof REVIEWS_STORE_NAME
	? WPDataSelectors
	: T extends typeof SETTINGS_STORE_NAME
	? WPDataSelectors
	: T extends typeof PLUGINS_STORE_NAME
	? PluginSelectors
	: T extends typeof ONBOARDING_STORE_NAME
	? OnboardingSelectors
	: T extends typeof USER_STORE_NAME
	? WPDataSelectors
	: T extends typeof OPTIONS_STORE_NAME
	? WPDataSelectors
	: T extends typeof NAVIGATION_STORE_NAME
	? WPDataSelectors
	: T extends typeof NOTES_STORE_NAME
	? WPDataSelectors
	: T extends typeof REPORTS_STORE_NAME
	? WPDataSelectors
	: T extends typeof ITEMS_STORE_NAME
	? WPDataSelectors
	: never;

export interface WCDataSelector {
	< T extends WCDataStoreName >( storeName: T ): WCSelectorType< T >;
}
export * from './onboarding/selectors';
