type DeprecatedWcAdminFeatureFlags = {
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'activity-panels': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	analytics: boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'analytics-scheduled-import': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'experimental-iapi-mini-cart': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	coupons: boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'core-profiler': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'customize-store': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'customer-effort-score-tracks': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'import-products-task': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'experimental-fashion-sample-products': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'shipping-smart-defaults': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'shipping-setting-tour': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	homescreen: boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	marketing: boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'mobile-app-banner': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	onboarding: boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'onboarding-tasks': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'pattern-toolkit-full-composability': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'payment-gateway-suggestions': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'product-custom-fields': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	printful: boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'remote-inbox-notifications': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'remote-free-extensions': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'shipping-label-banner': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	subscriptions: boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'store-alerts': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'transient-notices': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'wc-pay-promotion': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'wc-pay-welcome-page': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
	'woo-mobile-welcome': boolean;
	/** @deprecated Deprecated since WooCommerce 11.0. This WC Admin feature flag shim will be removed in WooCommerce 11.5. */
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
