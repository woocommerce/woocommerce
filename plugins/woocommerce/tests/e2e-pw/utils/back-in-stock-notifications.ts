/**
 * External dependencies
 */
import type { APIRequest, Page } from '@playwright/test';
import { WC_API_PATH, type ApiClient } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { setOption } from './options';
import { expect } from '../fixtures/fixtures';

/**
 * Names of the Back in Stock Notifications options in core.
 *
 * The external plugin used `wc_bis_*` slugs; core uses these.
 */
export const BIS_OPTIONS = {
	allowSignups: 'woocommerce_customer_stock_notifications_allow_signups',
	doubleOptIn:
		'woocommerce_customer_stock_notifications_require_double_opt_in',
	requireAccount: 'woocommerce_customer_stock_notifications_require_account',
	createAccountOnSignup:
		'woocommerce_customer_stock_notifications_create_account_on_signup',
} as const;

/**
 * Configure the BIS feature options for a test. Omitted keys are left untouched.
 *
 * @param {APIRequest} request                         Playwright request fixture.
 * @param {string}     baseURL                         Test site base URL.
 * @param {Object}     options                         BIS option toggles.
 * @param {boolean}    [options.allowSignups]          Whether the signup form is rendered on product pages.
 * @param {boolean}    [options.doubleOptIn]           Whether signups require email verification before activating.
 * @param {boolean}    [options.requireAccount]        Whether signups are limited to logged-in users.
 * @param {boolean}    [options.createAccountOnSignup] Whether a new account is created for guest signups.
 */
export async function setBISOptions(
	request: APIRequest,
	baseURL: string,
	options: {
		allowSignups?: boolean;
		doubleOptIn?: boolean;
		requireAccount?: boolean;
		createAccountOnSignup?: boolean;
	}
): Promise< void > {
	const toYesNo = ( v: boolean | undefined ): string | undefined => {
		if ( v === undefined ) {
			return undefined;
		}
		return v ? 'yes' : 'no';
	};

	const entries: Array< [ string, string | undefined ] > = [
		[ BIS_OPTIONS.allowSignups, toYesNo( options.allowSignups ) ],
		[ BIS_OPTIONS.doubleOptIn, toYesNo( options.doubleOptIn ) ],
		[ BIS_OPTIONS.requireAccount, toYesNo( options.requireAccount ) ],
		[
			BIS_OPTIONS.createAccountOnSignup,
			toYesNo( options.createAccountOnSignup ),
		],
	];

	for ( const [ name, value ] of entries ) {
		if ( value !== undefined ) {
			await setOption( request, baseURL, name, value );
		}
	}
}

/**
 * Return a handle to an out-of-stock product. Caller is responsible for calling cleanup().
 *
 * @param {ApiClient} restApi           WP REST client.
 * @param {Object}    [opts]            Product shape.
 * @param {string}    [opts.type]       Product type (`simple` or `variable`). Defaults to `simple`.
 * @param {string}    [opts.namePrefix] Prefix used to build a unique product name.
 */
export async function createOutOfStockProduct(
	restApi: ApiClient,
	opts: {
		type?: 'simple' | 'variable';
		namePrefix?: string;
	} = {}
): Promise< {
	id: number;
	name: string;
	permalink: string;
	cleanup: () => Promise< void >;
} > {
	const { type = 'simple', namePrefix = 'BIS Test Product' } = opts;
	const name = `${ namePrefix } ${ Date.now() }`;

	const response = await restApi.post< {
		id: number;
		name: string;
		permalink: string;
	} >( `${ WC_API_PATH }/products`, {
		name,
		type,
		regular_price: '9.99',
		manage_stock: false,
		stock_status: 'outofstock',
	} );
	const product = response.data;

	return {
		id: product.id,
		name: product.name,
		permalink: product.permalink,
		async cleanup() {
			await restApi
				.delete( `${ WC_API_PATH }/products/${ product.id }`, {
					force: true,
				} )
				.catch( () => {
					/* best-effort cleanup */
				} );
		},
	};
}

/**
 * Return a handle to a variable product with one out-of-stock and one
 * in-stock variation. Caller is responsible for calling cleanup().
 *
 * Used to exercise the variable-product BIS flow: the parent product doesn't
 * carry a stock status that surfaces the form — instead the form appears
 * after the shopper selects the out-of-stock variation.
 *
 * @param {ApiClient} restApi           WP REST client.
 * @param {Object}    [opts]            Product shape.
 * @param {string}    [opts.namePrefix] Prefix used to build a unique product name.
 */
export async function createOutOfStockVariableProduct(
	restApi: ApiClient,
	opts: {
		namePrefix?: string;
	} = {}
): Promise< {
	id: number;
	name: string;
	permalink: string;
	outOfStockAttribute: string;
	inStockAttribute: string;
	cleanup: () => Promise< void >;
} > {
	const { namePrefix = 'BIS Test Variable Product' } = opts;
	const name = `${ namePrefix } ${ Date.now() }`;
	const outOfStockAttribute = 'Small';
	const inStockAttribute = 'Large';

	// Create a variable parent with a single local attribute.
	const parentResponse = await restApi.post< {
		id: number;
		name: string;
		permalink: string;
	} >( `${ WC_API_PATH }/products`, {
		name,
		type: 'variable',
		attributes: [
			{
				name: 'Size',
				visible: true,
				variation: true,
				options: [ outOfStockAttribute, inStockAttribute ],
			},
		],
	} );
	const parent = parentResponse.data;

	// Create variations in an explicit order: out-of-stock first (so it's
	// the default when the shopper hasn't picked an option yet won't matter;
	// tests pick explicitly) and in-stock second.
	for ( const [ attribute, stockStatus ] of [
		[ outOfStockAttribute, 'outofstock' ],
		[ inStockAttribute, 'instock' ],
	] as const ) {
		await restApi.post(
			`${ WC_API_PATH }/products/${ parent.id }/variations`,
			{
				regular_price: '9.99',
				stock_status: stockStatus,
				manage_stock: false,
				attributes: [ { name: 'Size', option: attribute } ],
			}
		);
	}

	return {
		id: parent.id,
		name: parent.name,
		permalink: parent.permalink,
		outOfStockAttribute,
		inStockAttribute,
		async cleanup() {
			await restApi
				.delete( `${ WC_API_PATH }/products/${ parent.id }`, {
					force: true,
				} )
				.catch( () => {
					/* best-effort cleanup */
				} );
		},
	};
}

/**
 * Restock a product via REST (used by receiving-notifications.spec.ts to trigger the stock-sync action).
 *
 * @param {ApiClient} restApi   WP REST client.
 * @param {number}    productId Product id.
 */
export async function restockProduct(
	restApi: ApiClient,
	productId: number
): Promise< void > {
	await restApi.put( `${ WC_API_PATH }/products/${ productId }`, {
		stock_status: 'instock',
		manage_stock: false,
	} );
}

/**
 * Submit the PDP sign-up form. Caller must already have the product page loaded.
 *
 * @param {Page}    page                     Playwright page on the product detail.
 * @param {Object}  [opts]                   Fill options.
 * @param {string}  [opts.email]             Email address to enter (guest flow only; logged-in PDP hides the field).
 * @param {boolean} [opts.tickOptInCheckbox] Tick the privacy opt-in checkbox before submitting.
 */
export async function signUpOnProductPage(
	page: Page,
	opts: {
		email?: string;
		tickOptInCheckbox?: boolean;
	} = {}
): Promise< void > {
	if ( opts.email !== undefined ) {
		await page
			.getByRole( 'textbox', {
				name: /Email address to be notified/i,
			} )
			.fill( opts.email );
	}

	if ( opts.tickOptInCheckbox ) {
		await page.locator( 'input[name="wc_bis_opt_in"]' ).check();
	}

	await page.getByRole( 'button', { name: /Notify me/i } ).click();
}

/**
 * Open an email in WP Mail Logging and extract the first href matching a regular expression from its HTML body.
 *
 * Use this for follow-through flows where a subsequent step needs the URL embedded in the email.
 *
 * @param {Page}   page                 Playwright page.
 * @param {string} receiverEmailAddress The recipient email address.
 * @param {RegExp} subject              The email subject (regular expression).
 * @param {RegExp} hrefPattern          Pattern the target href should match (e.g. /email_link_action=verify/).
 */
export async function getLinkFromEmailBody(
	page: Page,
	receiverEmailAddress: string,
	subject: RegExp,
	hrefPattern: RegExp
): Promise< string > {
	await page.goto(
		`wp-admin/tools.php?page=wpml_plugin_log&search[place]=receiver&search[term]=${ encodeURIComponent(
			receiverEmailAddress
		) }&orderby=timestamp&order=desc`
	);

	const row = page
		.getByRole( 'row' )
		.filter( {
			has: page.getByRole( 'cell', {
				name: receiverEmailAddress,
				exact: true,
			} ),
		} )
		.filter( { has: page.getByText( subject ) } )
		.first();

	await expect( row ).toBeVisible();
	await row.getByRole( 'button', { name: 'View log' } ).click();

	const modalContent = page.locator(
		'#wp-mail-logging-modal-content-body-content'
	);
	await expect( modalContent ).toBeVisible();

	const iframe = page.frameLocator(
		'#wp-mail-logging-modal-content-body-content iframe'
	);
	// Wait until iframe content is attached and has anchors rendered.
	await iframe.locator( 'a' ).first().waitFor( { state: 'attached' } );

	const href = await iframe
		.locator( 'a' )
		.evaluateAll( ( anchors, pattern ) => {
			const regex = new RegExp( pattern );
			for ( const a of anchors as HTMLAnchorElement[] ) {
				if ( regex.test( a.href ) ) {
					return a.href;
				}
			}
			return null;
		}, hrefPattern.source );

	if ( ! href ) {
		throw new Error(
			`No link matching ${ hrefPattern } found in email to ${ receiverEmailAddress } with subject ${ subject }`
		);
	}

	// Close the modal for clean state.
	await page
		.locator(
			'#wp-mail-logging-modal-content-header-close, .wp-mail-logging-modal-close'
		)
		.first()
		.click()
		.catch( () => {
			/* Some wp-mail-logging versions don't surface an explicit close button — fine. */
		} );

	return href;
}

/**
 * Run any pending Action Scheduler jobs via the process-waiting-actions mu-plugin.
 *
 * @param {Page} page Playwright page (can be on any URL).
 */
export async function triggerStockNotificationsBatch(
	page: Page
): Promise< void > {
	await page.goto( '?process-waiting-actions' );
}

/**
 * Generate a unique guest email address for a test so mail-log assertions don't collide.
 *
 * @param {string} prefix Short descriptor of the test.
 */
export function uniqueGuestEmail( prefix = 'bis' ): string {
	return `${ prefix }-${ Date.now() }-${ Math.floor(
		Math.random() * 1000
	) }@example.com`;
}
