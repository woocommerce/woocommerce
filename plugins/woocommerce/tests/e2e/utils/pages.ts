/**
 * External dependencies
 */
import { createClient, WP_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import playwrightConfig from '../playwright.config';
import { admin } from '../test-data/data';

export const CLASSIC_CHECKOUT_PAGE = {
	name: 'classic checkout',
	slug: 'classic-checkout',
};

export const CLASSIC_CART_PAGE = {
	name: 'classic cart',
	slug: 'classic-cart',
};

export async function pageExists( slug: string ) {
	const apiClient = createClient( playwrightConfig.use.baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );
	const pages = await apiClient.get(
		`${ WP_API_PATH }/pages?slug=${ slug }`,
		{
			data: {
				_fields: [ 'id' ],
			},
		}
	);
	return pages.data.length > 0;
}

async function createShortcodePage(
	slug: string,
	title: string,
	shortcode: string
) {
	if ( ! ( await pageExists( slug ) ) ) {
		console.log( `Creating ${ title } page` );
		const apiClient = createClient( playwrightConfig.use.baseURL, {
			type: 'basic',
			username: admin.username,
			password: admin.password,
		} );
		const page = await apiClient
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
