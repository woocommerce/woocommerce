/**
 * External dependencies
 */
import deprecated from '@wordpress/deprecated';

export const RETIRED_FEATURE_FLAG_DEPRECATION_VERSION = '11.0.0';

// Keep this dictionary in sync with $retired_feature_compatibility_removal_versions in
// plugins/woocommerce/src/Admin/Features/Features.php.
export const RETIRED_FEATURE_FLAGS = {
	'activity-panels': null,
	analytics: null,
	'analytics-scheduled-import': null,
	'experimental-iapi-mini-cart': null,
	coupons: null,
	'core-profiler': null,
	'customize-store': null,
	'customer-effort-score-tracks': null,
	'import-products-task': null,
	'experimental-fashion-sample-products': null,
	'shipping-smart-defaults': null,
	'shipping-setting-tour': null,
	homescreen: null,
	marketing: null,
	'mobile-app-banner': null,
	onboarding: null,
	'onboarding-tasks': null,
	'pattern-toolkit-full-composability': null,
	'payment-gateway-suggestions': null,
	'product-custom-fields': null,
	printful: null,
	'remote-inbox-notifications': null,
	'remote-free-extensions': null,
	'shipping-label-banner': null,
	subscriptions: null,
	'store-alerts': null,
	'transient-notices': null,
	'wc-pay-promotion': null,
	'wc-pay-welcome-page': null,
	'woo-mobile-welcome': null,
	'launch-your-store': null,
} as const;

type RetiredFeatureFlag = keyof typeof RETIRED_FEATURE_FLAGS;

export const getRetiredFeatureFlagRemovalVersion = (
	featureId: string
): string | null | undefined =>
	RETIRED_FEATURE_FLAGS[ featureId as RetiredFeatureFlag ];

export const isRetiredFeatureFlag = ( featureId: string ): boolean =>
	Object.hasOwn( RETIRED_FEATURE_FLAGS, featureId );

export const warnRetiredFeatureFlag = ( featureId: string ): void => {
	const removalVersion = getRetiredFeatureFlagRemovalVersion( featureId );

	deprecated( `wcAdminFeatures.${ featureId }`, {
		version: RETIRED_FEATURE_FLAG_DEPRECATION_VERSION,
		plugin: 'WooCommerce',
		hint: `The ${ featureId } WC Admin feature flag shim will be removed in ${
			removalVersion
				? `WooCommerce ${ removalVersion }`
				: 'a future version of WooCommerce'
		}.`,
	} );
};
