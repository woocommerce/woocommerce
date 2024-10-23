declare global {
	interface Window {
		location: Location;
		pagenow: string;
		adminpage: string;
		wcSettings: {
			preloadOptions: Record< string, unknown >;
			adminUrl: string;
			currentUserId: number;
			currentThemeIsFSETheme: boolean;
			admin: {
				wcpay_welcome_page_connect_nonce: string;
				currentUserData: {
					first_name: string;
				};
				plugins: {
					activePlugins: string[];
					installedPlugins: string[];
				},
				wcpayWelcomePageIncentive: {
					id: string;
					description: string;
					cta_label: string;
					tc_url: string;
				};
				currency?: {
					symbol: string;
				};
				currentUserId: number;
				blueprint_upload_nonce?: string;
			};
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
			'pattern-toolkit-full-composability': boolean;
			printful: boolean;
			'product-pre-publish-modal': boolean;
			'product-custom-fields': boolean;
			'remote-inbox-notifications': boolean;
			'remote-free-extensions': boolean;
			settings: boolean;
			'shipping-label-banner': boolean;
			subscriptions: boolean;
			'store-alerts': boolean;
			'transient-notices': boolean;
			'wc-pay-promotion': boolean;
			'wc-pay-welcome-page': boolean;
			'woo-mobile-welcome': boolean;
			'shipping-smart-defaults': boolean;
			'shipping-setting-tour': boolean;
			'launch-your-store': boolean;
			'blueprint': boolean;
			'reactify-classic-payments-settings': boolean;
		};
		wp: {
			updates?: {
				ajax: ( action, data: {
					slug?: string;
					plugin?: string;
					theme?: string;
					success?: function;
					error?: function;
				} ) => JQuery.Promise;
			};
			autosave?: {
				server: {
					postChanged: () => boolean;
				};
			};
		};
		tinymce?: {
			get: ( name: string ) => {
				isHidden: () => boolean;
				isDirty: () => boolean;
			};
		};
		getUserSetting?: ( name: string ) => string | undefined;
		setUserSetting?: ( name: string, value: string ) => void;
		deleteUserSetting?: ( name: string ) => void;
		woocommerce_admin: {
			ajax_url: string;
			nonces: {
				gateway_toggle?: string;
			}
		}
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
