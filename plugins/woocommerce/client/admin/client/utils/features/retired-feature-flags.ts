export const RETIRED_FEATURE_FLAGS = [
	'activity-panels',
	'analytics',
	'analytics-scheduled-import',
	'experimental-iapi-mini-cart',
	'coupons',
	'core-profiler',
	'customize-store',
	'customer-effort-score-tracks',
	'import-products-task',
	'experimental-fashion-sample-products',
	'shipping-smart-defaults',
	'shipping-setting-tour',
	'homescreen',
	'marketing',
	'mobile-app-banner',
	'onboarding',
	'onboarding-tasks',
	'pattern-toolkit-full-composability',
	'payment-gateway-suggestions',
	'product-custom-fields',
	'printful',
	'remote-inbox-notifications',
	'remote-free-extensions',
	'shipping-label-banner',
	'subscriptions',
	'store-alerts',
	'transient-notices',
	'wc-pay-promotion',
	'wc-pay-welcome-page',
	'woo-mobile-welcome',
	'launch-your-store',
] as const;

export const getRetiredFeatureFlagDeprecationMessage = (
	featureId: string
): string =>
	`Deprecated: The ${ featureId } WC Admin feature flag shim is deprecated and will be removed in WooCommerce 11.5.`;

export const isRetiredFeatureFlag = ( featureId: string ): boolean =>
	RETIRED_FEATURE_FLAGS.includes(
		featureId as ( typeof RETIRED_FEATURE_FLAGS )[ number ]
	);

export const warnRetiredFeatureFlag = ( featureId: string ): void => {
	// eslint-disable-next-line no-console
	console.warn( getRetiredFeatureFlagDeprecationMessage( featureId ) );
};
