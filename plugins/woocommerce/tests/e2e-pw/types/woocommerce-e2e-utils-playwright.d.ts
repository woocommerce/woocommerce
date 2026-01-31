/**
 * Type declarations for @woocommerce/e2e-utils-playwright
 *
 * This module provides utilities for WooCommerce E2E tests using Playwright.
 */
declare module '@woocommerce/e2e-utils-playwright' {
	import type { Page, FrameLocator } from '@playwright/test';

	// API paths
	export const WC_API_PATH: string;
	export const WC_ADMIN_API_PATH: string;
	export const WP_API_PATH: string;

	// Auth types
	interface BasicAuth {
		type: 'basic';
		username: string;
		password: string;
	}

	interface OAuth1Auth {
		type: 'oauth1';
		consumerKey: string;
		consumerSecret: string;
	}

	type Auth = BasicAuth | OAuth1Auth;

	// API Response type
	interface ApiResponse< T = unknown > {
		data: T;
		status: number;
		headers: Record< string, string >;
	}

	// API Client interface
	interface ApiClient {
		get< T = unknown >(
			path: string,
			params?: Record< string, unknown >,
			debug?: boolean
		): Promise< ApiResponse< T > >;
		post< T = unknown >(
			path: string,
			data?: Record< string, unknown >,
			debug?: boolean
		): Promise< ApiResponse< T > >;
		put< T = unknown >(
			path: string,
			data?: Record< string, unknown >,
			debug?: boolean
		): Promise< ApiResponse< T > >;
		delete< T = unknown >(
			path: string,
			params?: Record< string, unknown >,
			debug?: boolean
		): Promise< ApiResponse< T > >;
	}

	export function createClient( baseURL: string, auth: Auth ): ApiClient;

	// Editor utilities
	interface PageContext {
		page: Page;
	}

	export function closeChoosePatternModal( context: PageContext ): Promise< void >;
	export function disableWelcomeModal( context: PageContext ): Promise< void >;
	export function openEditorSettings( context: PageContext ): Promise< void >;
	export function getCanvas( page: Page ): Promise< FrameLocator | Page >;
	export function goToPageEditor( context: PageContext ): Promise< void >;
	export function goToPostEditor( context: PageContext ): Promise< void >;
	export function insertBlock( page: Page, blockName: string ): Promise< void >;
	export function insertBlockByShortcut(
		page: Page,
		blockName: string
	): Promise< void >;
	export function transformIntoBlocks( page: Page ): Promise< void >;
	export function publishPage(
		page: Page,
		pageTitle: string,
		isPost?: boolean
	): Promise< void >;

	// Cart utilities
	export function addAProductToCart(
		page: Page,
		productId: string,
		quantity?: number
	): Promise< void >;
	export function addOneOrMoreProductToCart(
		page: Page,
		productName: string,
		quantityCount?: number
	): Promise< void >;

	// Checkout utilities
	interface CheckoutDetails {
		country?: string;
		firstName?: string;
		lastName?: string;
		address?: string;
		zip?: string;
		city?: string;
		state?: string;
		suburb?: string;
		province?: string;
		district?: string;
		department?: string;
		region?: string;
		parish?: string;
		county?: string;
		prefecture?: string;
		municipality?: string;
		phone?: string;
		isPostalCode?: boolean;
	}

	export function fillShippingCheckoutBlocks(
		page: Page,
		shippingDetails?: CheckoutDetails
	): Promise< void >;
	export function fillBillingCheckoutBlocks(
		page: Page,
		billingDetails?: CheckoutDetails
	): Promise< void >;

	// Order utilities
	export function getOrderIdFromUrl( page: Page ): string | undefined;
}
