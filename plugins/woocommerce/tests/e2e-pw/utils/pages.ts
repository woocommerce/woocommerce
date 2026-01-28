/**
 * External dependencies
 */
import type { Browser } from '@playwright/test';
import e2eUtils from '@woocommerce/e2e-utils-playwright';

const {
	createClient,
	goToPageEditor,
	insertBlockByShortcut,
	publishPage,
	WP_API_PATH,
} = e2eUtils;

/**
 * Internal dependencies
 */
import { fillPageTitle } from './editor';
import playwrightConfig, { ADMIN_STATE_PATH } from '../playwright.config';
import { admin } from '../test-data/data';
import type { RestApiClient, RestApiResponse } from '../fixtures/fixtures';

/**
 * Page configuration with name and slug.
 */
export interface PageConfig {
	name: string;
	slug: string;
}

export const BLOCKS_CHECKOUT_PAGE: PageConfig = {
	name: 'blocks checkout',
	slug: 'blocks-checkout',
};

export const BLOCKS_CART_PAGE: PageConfig = {
	name: 'blocks cart',
	slug: 'blocks-cart',
};

export const CLASSIC_CHECKOUT_PAGE: PageConfig = {
	name: 'classic checkout',
	slug: 'classic-checkout',
};

export const CLASSIC_CART_PAGE: PageConfig = {
	name: 'classic cart',
	slug: 'classic-cart',
};

/**
 * WordPress page response from REST API.
 */
interface WpPageResponse {
	id: number;
	title: { rendered: string };
	slug: string;
}

/**
 * Check if a page with the given slug exists.
 *
 * @param slug - Page slug to check
 * @return Promise resolving to true if page exists
 */
export async function pageExists( slug: string ): Promise< boolean > {
	const apiClient = createClient( playwrightConfig.use?.baseURL as string, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} ) as RestApiClient;
	const pages: RestApiResponse = await apiClient.get(
		`${ WP_API_PATH }/pages?slug=${ slug }`,
		{
			data: {
				_fields: [ 'id' ],
			},
		}
	);
	return ( pages.data as WpPageResponse[] ).length > 0;
}

/**
 * Create a page using a shortcode.
 *
 * @param slug      - Page slug
 * @param title     - Page title
 * @param shortcode - Shortcode content
 */
async function createShortcodePage(
	slug: string,
	title: string,
	shortcode: string
): Promise< void > {
	if ( ! ( await pageExists( slug ) ) ) {
		console.log( `Creating ${ title } page` );
		const apiClient = createClient(
			playwrightConfig.use?.baseURL as string,
			{
				type: 'basic',
				username: admin.username,
				password: admin.password,
			}
		) as RestApiClient;
		const response: RestApiResponse = await apiClient.post(
			`${ WP_API_PATH }/pages`,
			{
				title,
				content: {
					raw: shortcode,
				},
				status: 'publish',
			}
		);
		const page = response.data as WpPageResponse;
		console.log(
			`Created page: ${ JSON.stringify( {
				title: page.title,
				slug: page.slug,
				id: page.id,
			} ) }`
		);
	}
}

/**
 * Create the classic checkout page with shortcode.
 */
export async function createClassicCheckoutPage(): Promise< void > {
	await createShortcodePage(
		CLASSIC_CHECKOUT_PAGE.slug,
		CLASSIC_CHECKOUT_PAGE.name,
		'<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->'
	);
}

/**
 * Create the classic cart page with shortcode.
 */
export async function createClassicCartPage(): Promise< void > {
	await createShortcodePage(
		CLASSIC_CART_PAGE.slug,
		CLASSIC_CART_PAGE.name,
		'<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->'
	);
}

/**
 * Create a page using block editor.
 *
 * @param browser   - Playwright Browser object
 * @param slug      - Page slug
 * @param title     - Page title
 * @param blockName - Name of block to insert
 */
async function createBlocksPage(
	browser: Browser,
	slug: string,
	title: string,
	blockName: string
): Promise< void > {
	if ( ! ( await pageExists( slug ) ) ) {
		console.log( 'Creating Checkout Blocks page' );
		const context = await browser.newContext( {
			storageState: ADMIN_STATE_PATH,
		} );
		const page = await context.newPage();
		await goToPageEditor( { page } );
		await fillPageTitle( page, title );
		await insertBlockByShortcut( page, blockName );
		await publishPage( page, title );
		await page.close();
		await context.close();
	}
}

/**
 * Create the blocks checkout page.
 *
 * @param browser - Playwright Browser object
 */
export async function createBlocksCheckoutPage(
	browser: Browser
): Promise< void > {
	await createBlocksPage(
		browser,
		BLOCKS_CHECKOUT_PAGE.slug,
		BLOCKS_CHECKOUT_PAGE.name,
		'Checkout'
	);
}

/**
 * Create the blocks cart page.
 *
 * @param browser - Playwright Browser object
 */
export async function createBlocksCartPage(
	browser: Browser
): Promise< void > {
	await createBlocksPage(
		browser,
		BLOCKS_CART_PAGE.slug,
		BLOCKS_CART_PAGE.name,
		'Cart'
	);
}
