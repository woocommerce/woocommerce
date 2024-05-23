const defaultConfig = require( '/qit/tests/e2e/qit-playwright.config' );
const testURL    = new URL( defaultConfig.use.baseURL );

/**
 * Request that a WordPress filter be established for the specified hook and returning the specified value.
 *
 * 'Under the hood', this is done by communicating to a server-side plugin via cookies. Therefore, for the server-side
 * code to observe the requested filter, you may need to `page.reload()` prior to writing assertions that rely on your
 * filter.
 *
 * @param page
 * @param hook
 * @param value
 * @param priority
 */
export async function setFilterValue( page, hook, value, priority = 10 ) {
	const context         = page.context();
	const existingCookies = await context.cookies();
	let   filterSpecs     = {};

	for ( const cookie of existingCookies ) {
		if ( cookie.name === 'e2e-filters' ) {
			filterSpecs = JSON.parse( cookie.value );
			break;
		}
	}

	filterSpecs[hook] = {
		value:    value,
		priority: priority
	};

	await context.addCookies( [ {
		name:  'e2e-filters',
		value:  JSON.stringify( filterSpecs ),
		path:   '/',
		domain: testURL.hostname
	} ] );
}

/**
 * Clears any server-side filters setup via setFilterValue().
 *
 * As with its sister function, this mechanism relies on cookies and therefore a call to `page.reload()` may be required
 * before performing further assertions.
 *
 * @param page
 */
export async function clearFilters( page ) {
	await page.context().addCookies( [ {
		name:  'e2e-filters',
		value:  '',
		path:   '/',
		domain: testURL.hostname
	} ] );
}
