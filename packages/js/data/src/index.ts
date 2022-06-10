/**
 * External dependencies
 */
import '@wordpress/core-data';

// Export store names
export { SETTINGS_STORE_NAME } from './settings';
export { PLUGINS_STORE_NAME } from './plugins';
export { ONBOARDING_STORE_NAME } from './onboarding';
export { USER_STORE_NAME } from './user';
export { REVIEWS_STORE_NAME } from './reviews';
export { NOTES_STORE_NAME } from './notes';
export { REPORTS_STORE_NAME } from './reports';
export { COUNTRIES_STORE_NAME } from './countries';
export { NAVIGATION_STORE_NAME } from './navigation';
export { OPTIONS_STORE_NAME } from './options';
export { ITEMS_STORE_NAME } from './items';
export { PAYMENT_GATEWAYS_STORE_NAME } from './payment-gateways';
export { PRODUCTS_STORE_NAME } from './products';
export { ORDERS_STORE_NAME } from './orders';
export { PaymentGateway } from './payment-gateways/types';

// Export hooks
export { withSettingsHydration } from './settings/with-settings-hydration';
export { withOnboardingHydration } from './onboarding/with-onboarding-hydration';
export { withCurrentUserHydration } from './user/with-current-user-hydration';
export { withNavigationHydration } from './navigation/with-navigation-hydration';
export { withPluginsHydration } from './plugins/with-plugins-hydration';
export {
	withOptionsHydration,
	useOptionsHydration,
} from './options/with-options-hydration';
export { useSettings } from './settings/use-settings';
export { useUserPreferences } from './user/use-user-preferences';
export { useUser } from './user/use-user';

// Export utils
export { getVisibleTasks } from './onboarding/utils';
export { getLeaderboard, searchItemsByString } from './items/utils';
export {
	getFilterQuery,
	getSummaryNumbers,
	getReportTableData,
	getReportTableQuery,
	getReportChartData,
	getTooltipValueFormat,
} from './reports/utils';

// Export constants
export { pluginNames } from './plugins/constants';
export { EXPORT_STORE_NAME } from './export';
export { IMPORT_STORE_NAME } from './import';
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

// Export types
export * from './types';
export * from './countries/types';
export * from './onboarding/types';
export * from './plugins/types';
export * from './products/types';
export * from './orders/types';

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
import type { COUNTRIES_STORE_NAME } from './countries';
import type { PAYMENT_GATEWAYS_STORE_NAME } from './payment-gateways';
import type { PRODUCTS_STORE_NAME } from './products';
import type { ORDERS_STORE_NAME } from './orders';

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
	| typeof ITEMS_STORE_NAME
	| typeof COUNTRIES_STORE_NAME
	| typeof PAYMENT_GATEWAYS_STORE_NAME
	| typeof PRODUCTS_STORE_NAME
	| typeof ORDERS_STORE_NAME;

/**
 * Internal dependencies
 */
import { WPDataSelectors } from './types';
import { PaymentSelectors } from './payment-gateways/selectors';
import { PluginSelectors } from './plugins/selectors';
import { OnboardingSelectors } from './onboarding/selectors';
import { OptionsSelectors } from './options/types';
import { ProductsSelectors } from './products/selectors';
import { OrdersSelectors } from './orders/selectors';

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
	: T extends typeof PAYMENT_GATEWAYS_STORE_NAME
	? PaymentSelectors
	: T extends typeof USER_STORE_NAME
	? WPDataSelectors
	: T extends typeof OPTIONS_STORE_NAME
	? OptionsSelectors
	: T extends typeof NAVIGATION_STORE_NAME
	? WPDataSelectors
	: T extends typeof NOTES_STORE_NAME
	? WPDataSelectors
	: T extends typeof REPORTS_STORE_NAME
	? WPDataSelectors
	: T extends typeof ITEMS_STORE_NAME
	? WPDataSelectors
	: T extends typeof COUNTRIES_STORE_NAME
	? WPDataSelectors
	: T extends typeof PRODUCTS_STORE_NAME
	? ProductsSelectors
	: T extends typeof ORDERS_STORE_NAME
	? OrdersSelectors
	: never;

export interface WCDataSelector {
	< T extends WCDataStoreName >( storeName: T ): WCSelectorType< T >;
}

// Other exports
export { ActionDispatchers as PluginsStoreActions } from './plugins/actions';
export { ActionDispatchers as ProductsStoreActions } from './products/actions';
