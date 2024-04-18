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
export { SHIPPING_METHODS_STORE_NAME } from './shipping-methods';
export { PRODUCTS_STORE_NAME } from './products';
export { ORDERS_STORE_NAME } from './orders';
export { EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME } from './product-attributes';
export { EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME } from './product-shipping-classes';
export { EXPERIMENTAL_SHIPPING_ZONES_STORE_NAME } from './shipping-zones';
export { EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME } from './product-tags';
export { EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME } from './product-categories';
export { EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME } from './product-attribute-terms';
export { EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME } from './product-variations';
export { EXPERIMENTAL_PRODUCT_FORM_STORE_NAME } from './product-form';
export { EXPERIMENTAL_TAX_CLASSES_STORE_NAME } from './tax-classes';
export { PaymentGateway } from './payment-gateways/types';
export { ShippingMethod } from './shipping-methods/types';

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
export {
	ProductForm,
	ProductFormField,
	ProductFormSection,
} from './product-form/types';
export * from './onboarding/types';
export * from './plugins/types';
export * from './products/types';
export type {
	PartialProductVariation,
	ProductVariation,
	ProductVariationAttribute,
	ProductVariationImage,
} from './product-variations/types';
export {
	QueryProductAttribute,
	ProductAttributeSelectors,
} from './product-attributes/types';
export * from './product-shipping-classes/types';
export {
	ProductAttributeTerm,
	ProductAttributeTermsSelectors,
} from './product-attribute-terms/types';
export * from './orders/types';
export {
	ProductCategory,
	ProductCategoryImage,
	ProductCategorySelectors,
} from './product-categories/types';
export { TaxClass } from './tax-classes/types';
export { ProductTag, Query } from './product-tags/types';
export { WCUser } from './user/types';
export { UserPreferences } from './user/types';

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
import type { SHIPPING_METHODS_STORE_NAME } from './shipping-methods';
import type { PRODUCTS_STORE_NAME } from './products';
import type { ORDERS_STORE_NAME } from './orders';
import type { EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME } from './product-attributes';
import type { EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME } from './product-shipping-classes';
import type { EXPERIMENTAL_SHIPPING_ZONES_STORE_NAME } from './shipping-zones';
import type { EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME } from './product-tags';
import type { EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME } from './product-categories';
import type { EXPERIMENTAL_PRODUCT_FORM_STORE_NAME } from './product-form';
import type { EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME } from './product-attribute-terms';
import type { EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME } from './product-variations';
import type { EXPERIMENTAL_TAX_CLASSES_STORE_NAME } from './tax-classes';

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
	| typeof SHIPPING_METHODS_STORE_NAME
	| typeof PRODUCTS_STORE_NAME
	| typeof ORDERS_STORE_NAME
	| typeof EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
	| typeof EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
	| typeof EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME
	| typeof EXPERIMENTAL_SHIPPING_ZONES_STORE_NAME
	| typeof EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME
	| typeof EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME
	| typeof EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
	| typeof EXPERIMENTAL_TAX_CLASSES_STORE_NAME
	| typeof EXPERIMENTAL_PRODUCT_FORM_STORE_NAME;

/**
 * Internal dependencies
 */
import { WPDataSelectors } from './types';
import { PaymentSelectors } from './payment-gateways/selectors';
import { ShippingMethodsSelectors } from './shipping-methods/selectors';
import { PluginSelectors } from './plugins/selectors';
import { OnboardingSelectors } from './onboarding/selectors';
import { OptionsSelectors } from './options/types';
import { ProductsSelectors } from './products/selectors';
import { OrdersSelectors } from './orders/selectors';
import { ProductAttributeSelectors } from './product-attributes/types';
import { ProductShippingClassSelectors } from './product-shipping-classes/types';
import { ShippingZonesSelectors } from './shipping-zones/types';
import { ProductTagSelectors } from './product-tags/types';
import { ProductCategorySelectors } from './product-categories/types';
import { ProductAttributeTermsSelectors } from './product-attribute-terms/types';
import { ProductVariationSelectors } from './product-variations/types';
import { TaxClassSelectors } from './tax-classes/types';
import { ProductFormSelectors } from './product-form/selectors';

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
	: T extends typeof SHIPPING_METHODS_STORE_NAME
	? ShippingMethodsSelectors
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
	: T extends typeof EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
	? ProductAttributeSelectors
	: T extends typeof EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME
	? ProductShippingClassSelectors
	: T extends typeof EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME
	? ProductTagSelectors
	: T extends typeof EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME
	? ProductCategorySelectors
	: T extends typeof EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
	? ProductAttributeTermsSelectors
	: T extends typeof EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
	? ProductVariationSelectors
	: T extends typeof ORDERS_STORE_NAME
	? OrdersSelectors
	: T extends typeof EXPERIMENTAL_SHIPPING_ZONES_STORE_NAME
	? ShippingZonesSelectors
	: T extends typeof EXPERIMENTAL_TAX_CLASSES_STORE_NAME
	? TaxClassSelectors
	: T extends typeof EXPERIMENTAL_PRODUCT_FORM_STORE_NAME
	? ProductFormSelectors
	: never;

export interface WCDataSelector {
	< T extends WCDataStoreName >( storeName: T ): WCSelectorType< T >;
}

// Other exports
export { ActionDispatchers as PluginsStoreActions } from './plugins/actions';
export { CustomActionDispatchers as ProductAttributesActions } from './product-attributes/types';
export { ActionDispatchers as ProductTagsActions } from './product-tags/types';
export { ActionDispatchers as ProductCategoryActions } from './product-categories/types';
export { ActionDispatchers as ProductAttributeTermsActions } from './product-attribute-terms/types';
export { ActionDispatchers as ProductVariationsActions } from './product-variations/types';
export { ActionDispatchers as ProductsStoreActions } from './products/actions';
export { ActionDispatchers as ProductShippingClassesActions } from './product-shipping-classes/types';
export { ActionDispatchers as ShippingZonesActions } from './shipping-zones/types';
export { ActionDispatchers as TaxClassActions } from './tax-classes/types';
