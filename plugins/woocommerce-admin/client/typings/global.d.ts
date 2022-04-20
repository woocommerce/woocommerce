declare global {
	interface Window {
		wcSettings: {
			preloadOptions: Record< string, unknown >;
			adminUrl: string;
		};
		wcAdminFeatures: {
			'activity-panels': boolean;
			analytics: boolean;
			coupons: boolean;
			'customer-effort-score-tracks': boolean;
			homescreen: boolean;
			marketing: boolean;
			'minified-js': boolean;
			'mobile-app-banner': boolean;
			navigation: boolean;
			onboarding: boolean;
			'onboarding-tasks': boolean;
			'payment-gateway-suggestions': boolean;
			'remote-inbox-notifications': boolean;
			'remote-free-extensions': boolean;
			settings: boolean;
			'shipping-label-banner': boolean;
			subscriptions: boolean;
			'store-alerts': boolean;
			'transient-notices': boolean;
			'wc-pay-promotion': boolean;
			'wc-pay-welcome-page': boolean;
		};
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
