/**
 * External dependencies
 */

import { Page } from '@playwright/test';

export const getBlockByName = ( {
	page,
	name,
}: {
	page: Page;
	name: string;
} ) => page.locator( `[data-block-name="${ name }"]` );
