/**
 * Shared type definitions for WooCommerce Analytics
 */

export interface SessionCookieData {
	session_id: string;
	landing_page: string;
	expires: string;
	is_engaged?: boolean;
}

export interface AnalyticsConfig {
	eventQueue: Array< { eventName: string; props?: Record< string, unknown > } >;
	commonProps: Record< string, unknown >;
	features: Record< string, boolean >;
	pages: Record< string, boolean >;
}

export type RecordEventFunction = ( event: string, properties?: Record< string, unknown > ) => void;

export interface QueuedEvent {
	eventName: string;
	props?: Record< string, unknown >;
}

export interface ApiEvent {
	event_name: string;
	properties: Record< string, unknown >;
}

export interface ApiFetchResponse {
	success: boolean;
	results: Array< { success: boolean; error?: string } >;
}
