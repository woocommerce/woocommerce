declare global {
	interface Window {
		wcAdminFeatures: {
			'activity-panels': boolean;
			analytics: boolean;
			'product-block-editor': boolean;
			'experimental-blocks': boolean;
			coupons: boolean;
			'core-profiler': boolean;
			'customize-store': boolean;
			'import-products-task': boolean;
			'experimental-fashion-sample-products': boolean;
			'shipping-smart-defaults': boolean;
			'shipping-setting-tour': boolean;
			homescreen: boolean;
			marketing: boolean;
			'minified-js': boolean;
			'mobile-app-banner': boolean;
			navigation: boolean;
			onboarding: boolean;
			'onboarding-tasks': boolean;
			'pattern-toolkit-full-composability': boolean;
			'product-pre-publish-modal': boolean;
			'product-custom-fields': boolean;
			'remote-inbox-notifications': boolean;
			'remote-free-extensions': boolean;
			'payment-gateway-suggestions': boolean;
			settings: boolean;
			'shipping-label-banner': boolean;
			subscriptions: boolean;
			'store-alerts': boolean;
			'transient-notices': boolean;
			'woo-mobile-welcome': boolean;
			'wc-pay-promotion': boolean;
			'wc-pay-welcome-page': boolean;
			'async-product-editor-category-field': boolean;
			'launch-your-store': boolean;
			'product-editor-template-system': boolean;
		};
		wcTracks: {
			isEnabled: boolean;
			validateEvent: (
				name: string,
				properties: unknown,
			) => void;
			recordEvent: (
				name: string,
				properties: unknown,
			) => void;
		};
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
