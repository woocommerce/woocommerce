type DeprecatedWcAdminFeatureFlags = {
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'activity-panels': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	analytics: boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'analytics-scheduled-import': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'experimental-iapi-mini-cart': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	coupons: boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'core-profiler': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'customize-store': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'customer-effort-score-tracks': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'import-products-task': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'experimental-fashion-sample-products': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'shipping-smart-defaults': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'shipping-setting-tour': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	homescreen: boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	marketing: boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'mobile-app-banner': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	onboarding: boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'onboarding-tasks': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'pattern-toolkit-full-composability': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'payment-gateway-suggestions': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'product-custom-fields': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	printful: boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'remote-inbox-notifications': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'remote-free-extensions': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'shipping-label-banner': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	subscriptions: boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'transient-notices': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'wc-pay-promotion': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'wc-pay-welcome-page': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'woo-mobile-welcome': boolean;
	/** @deprecated Deprecated since WooCommerce 11.1. This WC Admin feature flag shim will be removed in a future version of WooCommerce. */
	'launch-your-store': boolean;
};

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
			countries: Record< string, string >;
			siteTitle: string;
			homeUrl: string;
			admin: {
				woocommerce_payments_nox_profile?: {
					business_country_code: string;
				};
				wcpay_welcome_page_connect_nonce: string;
				currentUserData: {
					first_name: string;
				};
				plugins: {
					activePlugins: string[];
					installedPlugins: string[];
				};
				wcpayWelcomePageIncentive: {
					id: string;
					description: string;
					cta_label: string;
					tc_url: string;
				};
				currency?: {
					symbol: string;
				};
				preloadSettings?: {
					general?: {
						woocommerce_default_country: string;
					};
				};
				currentUserId: number;
				blueprint_upload_nonce?: string;
				blueprint_max_step_size_bytes?: number;
				onboarding?: {
					profile?: {
						industry?: number[];
					};
				};
				siteVisibilitySettings: Record< string, string >;
			};
		};
		wcAdminFeatures: DeprecatedWcAdminFeatureFlags & {
			'product-data-views': boolean;
			'experimental-blocks': boolean;
			'minified-js': boolean;
			'settings-ui': boolean;
			'store-alerts': boolean;
			'rest-api-v4': boolean;
			'order-detail-redesign': boolean;
			'product-variations-classic-redesign': boolean;
		};
		wp: {
			updates?: {
				ajax: (
					action,
					data: {
						slug?: string;
						plugin?: string;
						theme?: string;
						success?: function;
						error?: function;
					}
				) => JQuery.Promise;
			};
			autosave?: {
				server: {
					postChanged: () => boolean;
				};
			};
			media: {
				frames?: {
					img_select?: wp.media.frame;
				};
				( options: wp.media.frameOptions ): wp.media.frame;
				attachment: ( id: number ) => wp.media.attachment;
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
			};
		};
	}
	namespace wp.media {
		interface frame {
			open(): void;
			on( event: string, callback: Function ): void;
			state(): {
				get( state: string ): any;
			};
		}

		interface frameOptions {
			title?: string;
			button?: {
				text: string;
			};
			library: {
				type: string;
			};
			multiple?: boolean;
		}

		interface attachment {
			fetch(): Promise< void >;
			get( key: string ): unknown;
		}
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
