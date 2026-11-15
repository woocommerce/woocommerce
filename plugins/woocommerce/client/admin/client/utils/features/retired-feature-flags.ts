/**
 * External dependencies
 */
import deprecated from '@wordpress/deprecated';

type RetiredFeatureFlagMetadata = {
	deprecatedSince: string;
	removedIn: string | null;
};

// Keep this dictionary in sync with $retired_feature_compatibility_versions in
// plugins/woocommerce/src/Admin/Features/Features.php.
export const RETIRED_FEATURE_FLAGS = {
	'activity-panels': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	analytics: {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'analytics-scheduled-import': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'experimental-iapi-mini-cart': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	coupons: {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'core-profiler': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'customize-store': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'customer-effort-score-tracks': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'import-products-task': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'experimental-fashion-sample-products': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'shipping-smart-defaults': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'shipping-setting-tour': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	homescreen: {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	marketing: {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'mobile-app-banner': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	onboarding: {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'onboarding-tasks': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'pattern-toolkit-full-composability': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'payment-gateway-suggestions': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'product-custom-fields': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	printful: {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'remote-inbox-notifications': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'remote-free-extensions': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'shipping-label-banner': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	subscriptions: {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'transient-notices': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'wc-pay-promotion': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'wc-pay-welcome-page': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'woo-mobile-welcome': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
	'launch-your-store': {
		deprecatedSince: '11.1.0',
		removedIn: null,
	},
} as const satisfies Record< string, RetiredFeatureFlagMetadata >;

type RetiredFeatureFlag = keyof typeof RETIRED_FEATURE_FLAGS;

export const getRetiredFeatureFlagDeprecationVersion = (
	featureId: string
): string | undefined =>
	RETIRED_FEATURE_FLAGS[ featureId as RetiredFeatureFlag ]?.deprecatedSince;

export const getRetiredFeatureFlagRemovalVersion = (
	featureId: string
): string | null | undefined =>
	RETIRED_FEATURE_FLAGS[ featureId as RetiredFeatureFlag ]?.removedIn;

export const isRetiredFeatureFlag = ( featureId: string ): boolean =>
	Object.hasOwn( RETIRED_FEATURE_FLAGS, featureId );

export const warnRetiredFeatureFlag = ( featureId: string ): void => {
	const deprecationVersion =
		getRetiredFeatureFlagDeprecationVersion( featureId );

	if ( ! deprecationVersion ) {
		return;
	}
	const removalVersion = getRetiredFeatureFlagRemovalVersion( featureId );

	deprecated( `wcAdminFeatures.${ featureId }`, {
		since: deprecationVersion,
		plugin: 'WooCommerce',
		hint: `The ${ featureId } WC Admin feature flag shim will be removed in ${
			removalVersion
				? `WooCommerce ${ removalVersion }`
				: 'a future version of WooCommerce'
		}.`,
	} );
};
