/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Extracts the order ID from the current page URL.
 *
 * @param page - Playwright page object
 * @return The order ID or undefined if not found
 */
export function getOrderIdFromUrl( page: Page ): string | undefined {
	const regex = /order-received\/(\d+)/;
	const match = page.url().match( regex );
	return match?.[ 1 ];
}
