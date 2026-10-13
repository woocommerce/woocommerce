/**
 * Global type declarations for WooCommerce Analytics
 */

declare global {
	interface Window {
		wcAnalytics?: {
			trackEndpoint: string;
			eventQueue: Array< { eventName: string; props?: Record< string, unknown > } >;
			commonProps: Record< string, unknown >;
			features: Record< string, boolean >;
			pages: Record< string, boolean >;
			breadcrumbs?: string[];
			assets_url: string;
			trackingScriptUrl?: string | null;
		};
		_wca?: {
			push: ( props: Record< string, unknown > ) => void;
		};
		wp_has_consent?: ( type: string ) => boolean;
		waitfor_consent_hook?: boolean;
		wp_consent_type?: string;
	}
}

// This export statement is required to make this file a module
export {};
