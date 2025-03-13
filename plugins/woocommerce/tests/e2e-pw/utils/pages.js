/**
 * External dependencies
 */
import {
	goToPageEditor,
	insertBlockByShortcut,
	publishPage,
} from '@woocommerce/e2e-utils-playwright/src';

/**
 * Internal dependencies
 */
import { fillPageTitle } from './editor';
import ApiClient, { WP_API_PATH } from './api-client';
import { ADMIN_STATE_PATH } from '../playwright.config';

export const BLOCKS_CHECKOUT_PAGE = {
	name: 'blocks checkout',
	slug: 'blocks-checkout',
};

export const BLOCKS_CART_PAGE = {
	name: 'blocks cart',
	slug: 'blocks-cart',
};

export const CLASSIC_CHECKOUT_PAGE = {
	name: 'classic checkout',
	slug: 'classic-checkout',
};

export const CLASSIC_CART_PAGE = {
	name: 'classic cart',
	slug: 'classic-cart',
};

export async function pageExists( slug ) {
	const pages = await ApiClient.getInstance().get(
		`${ WP_API_PATH }/pages?slug=${ slug }`,
		{
			data: {
				_fields: [ 'id' ],
			},
		}
	);
	return pages.data.length > 0;
}

async function createShortcodePage( slug, title, shortcode ) {
	if ( ! ( await pageExists( slug ) ) ) {
		console.log( `Creating ${ title } page` );
		const page = await ApiClient.getInstance()
			.post( `${ WP_API_PATH }/pages`, {
				title,
				content: {
					raw: shortcode,
				},
				status: 'publish',
			} )
			.then( ( r ) => r.data );
		console.log(
			`Created page: ${ JSON.stringify( {
				title: page.title,
				slug: page.slug,
				id: page.id,
			} ) }`
		);
	}
}

export async function createClassicCheckoutPage() {
	await createShortcodePage(
		CLASSIC_CHECKOUT_PAGE.slug,
		CLASSIC_CHECKOUT_PAGE.name,
		'<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->'
	);
}

export async function createClassicCartPage() {
	await createShortcodePage(
		CLASSIC_CART_PAGE.slug,
		CLASSIC_CART_PAGE.name,
		'<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->'
	);
}

async function createBlocksPage( browser, slug, title, blockName ) {
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

export async function createBlocksCheckoutPage( browser ) {
	await createBlocksPage(
		browser,
		BLOCKS_CHECKOUT_PAGE.slug,
		BLOCKS_CHECKOUT_PAGE.name,
		'Checkout'
	);
}

export async function createBlocksCartPage( browser ) {
	await createBlocksPage(
		browser,
		BLOCKS_CART_PAGE.slug,
		BLOCKS_CART_PAGE.name,
		'Cart'
	);
}
