/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import defaultConfig from '../playwright.config';

const testURL = new URL( defaultConfig.use?.baseURL as string );

/**
 * Filter specification stored in cookies.
 */
interface FilterSpec {
	value: unknown;
	priority: number;
}

/**
 * Collection of filter specifications keyed by hook name.
 */
interface FilterSpecs {
	[ hook: string ]: FilterSpec;
}

/**
 * Request that a WordPress filter be established for the specified hook and returning the specified value.
 *
 * 'Under the hood', this is done by communicating to a server-side plugin via cookies. Therefore, for the server-side
 * code to observe the requested filter, you may need to `page.reload()` prior to writing assertions that rely on your
 * filter.
 *
 * @param page     - Playwright Page object
 * @param hook     - WordPress hook name
 * @param value    - Value to return from the filter
 * @param priority - Filter priority (default: 10)
 */
export async function setFilterValue(
	page: Page,
	hook: string,
	value: unknown,
	priority = 10
): Promise< void > {
	const context = page.context();
	const existingCookies = await context.cookies();
	let filterSpecs: FilterSpecs = {};

	for ( const cookie of existingCookies ) {
		if ( cookie.name === 'e2e-filters' ) {
			filterSpecs = JSON.parse( cookie.value ) as FilterSpecs;
			break;
		}
	}

	filterSpecs[ hook ] = {
		value,
		priority,
	};

	await context.addCookies( [
		{
			name: 'e2e-filters',
			value: JSON.stringify( filterSpecs ),
			path: '/',
			domain: testURL.hostname,
		},
	] );
}

/**
 * Clears any server-side filters setup via setFilterValue().
 *
 * As with its sister function, this mechanism relies on cookies and therefore a call to `page.reload()` may be required
 * before performing further assertions.
 *
 * @param page - Playwright Page object
 */
export async function clearFilters( page: Page ): Promise< void > {
	await page.context().addCookies( [
		{
			name: 'e2e-filters',
			value: '',
			path: '/',
			domain: testURL.hostname,
		},
	] );
}
