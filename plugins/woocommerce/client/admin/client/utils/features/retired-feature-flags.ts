/**
 * External dependencies
 */
import deprecated from '@wordpress/deprecated';

export const RETIRED_FEATURE_FLAG_DEPRECATION_VERSION = '11.0.0';

// Keep this dictionary in sync with $retired_feature_compatibility_removal_versions in
// plugins/woocommerce/src/Admin/Features/Features.php.
export const RETIRED_FEATURE_FLAGS = {
	'activity-panels': '11.5',
	analytics: '11.5',
	'analytics-scheduled-import': '11.5',
	'experimental-iapi-mini-cart': '11.5',
	coupons: '11.5',
	'core-profiler': '11.5',
	'customize-store': '11.5',
	'customer-effort-score-tracks': '11.5',
	'import-products-task': '11.5',
	'experimental-fashion-sample-products': '11.5',
	'shipping-smart-defaults': '11.5',
	'shipping-setting-tour': '11.5',
	homescreen: '11.5',
	marketing: '11.5',
	'mobile-app-banner': '11.5',
	onboarding: '11.5',
	'onboarding-tasks': '11.5',
	'pattern-toolkit-full-composability': '11.5',
	'payment-gateway-suggestions': '11.5',
	'product-custom-fields': '11.5',
	printful: '11.5',
	'remote-inbox-notifications': '11.5',
	'remote-free-extensions': '11.5',
	'shipping-label-banner': '11.5',
	subscriptions: '11.5',
	'store-alerts': '11.5',
	'transient-notices': '11.5',
	'wc-pay-promotion': '11.5',
	'wc-pay-welcome-page': '11.5',
	'woo-mobile-welcome': '11.5',
	'launch-your-store': '11.5',
} as const;

type RetiredFeatureFlag = keyof typeof RETIRED_FEATURE_FLAGS;

export const getRetiredFeatureFlagRemovalVersion = (
	featureId: string
): string | undefined =>
	RETIRED_FEATURE_FLAGS[ featureId as RetiredFeatureFlag ];

export const isRetiredFeatureFlag = ( featureId: string ): boolean =>
	Object.prototype.hasOwnProperty.call( RETIRED_FEATURE_FLAGS, featureId );

export const warnRetiredFeatureFlag = ( featureId: string ): void => {
	const removalVersion = getRetiredFeatureFlagRemovalVersion( featureId );

	deprecated( `wcAdminFeatures.${ featureId }`, {
		version: RETIRED_FEATURE_FLAG_DEPRECATION_VERSION,
		plugin: 'WooCommerce',
		hint: `The ${ featureId } WC Admin feature flag shim will be removed in WooCommerce ${
			removalVersion ?? 'a future version'
		}.`,
	} );
};
