/**
 * External dependencies
 */
import type { APIRequest, Browser, Page } from '@playwright/test';
import {
	createClient,
	WC_API_PATH,
	type ApiClient,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { deleteOption, setOption } from './options';
import { expectEmail } from './email';
import { setFilterValue } from './filters';
import { wpCLI } from './cli';
import { expect, test as baseTest } from '../fixtures/fixtures';
import { admin } from '../test-data/data';
import { ADMIN_STATE_PATH, CUSTOMER_STATE_PATH } from '../playwright.config';

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
 * Option that gates the whole Back in Stock Notifications feature.
 *
 * @see src/Internal/Features/FeaturesController.php
 */
export const BIS_FEATURE_OPTION =
	'woocommerce_feature_customer_stock_notifications_enabled';

/**
 * Fail early, with the fix, when the env can't run these specs.
 *
 * Both are provisioned by `bin/test-env-setup.sh`, which only runs on env
 * create or `--update`. On a stale env the feature UI simply never renders and
 * notification batches keep their one-minute delay, so every spec fails as an
 * unexplained timeout.
 */
export async function assertBISEnvReady(): Promise< void > {
	// wp-env prefixes its own lines onto stdout, so match rather than compare.
	const checks = [
		{
			command: `wp option get ${ BIS_FEATURE_OPTION }`,
			expected: /^yes$/m,
			problem: `the "${ BIS_FEATURE_OPTION }" feature flag is not enabled, so none of the Back in Stock Notifications UI renders`,
		},
		{
			command: 'wp plugin list --status=active --field=name',
			expected: /^woocommerce-e2e-test-helper$/m,
			problem:
				'the "woocommerce-e2e-test-helper" plugin is not active, so the notifications batch delay is not zeroed',
		},
	];

	for ( const { command, expected, problem } of checks ) {
		// `wpCLI` rejects on a non-zero exit, and WP-CLI exits 1 when an option
		// is missing entirely — the stale-env case this guard exists to explain.
		// Treat a failed command as "not provisioned" so the message below wins.
		let stdout = '';
		try {
			( { stdout } = await wpCLI( command ) );
		} catch {
			stdout = '';
		}

		if ( ! expected.test( stdout ) ) {
			throw new Error(
				`Cannot run the Back in Stock Notifications specs: ${ problem }. Run \`pnpm env:e2e:start\` to re-provision the tests env.`
			);
		}
	}
}

/**
 * Configure the BIS feature options for a test. Omitted keys are left untouched.
 *
 * @param {APIRequest} request                         Playwright request fixture.
 * @param {string}     baseURL                         Test site base URL.
 * @param {Object}     options                         BIS option toggles.
 * @param {boolean}    [options.allowSignups]          Whether the signup form is rendered on product pages.
 * @param {boolean}    [options.doubleOptIn]           Whether signups require email verification before activating.
 * @param {boolean}    [options.requireAccount]        Whether signups are limited to logged-in users.
 * @param {boolean}    [options.createAccountOnSignup] Whether a guest signup also registers a customer account.
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
 * Delete all BIS feature options, restoring core defaults. Mirrors `setBISOptions()`.
 *
 * @param {APIRequest} request Playwright request fixture.
 * @param {string}     baseURL Test site base URL.
 */
export async function resetBISOptions(
	request: APIRequest,
	baseURL: string
): Promise< void > {
	for ( const option of Object.values( BIS_OPTIONS ) ) {
		await deleteOption( request, baseURL, option );
	}
}

/**
 * An out-of-stock simple product created for a spec.
 */
export type BISProduct = {
	id: number;
	name: string;
	permalink: string;
};

/**
 * Return a handle to an out-of-stock simple product.
 *
 * Deletion is not the caller's job: the `product` fixture queues the id for the
 * worker-scoped batch in `reapProducts()`.
 *
 * @param {ApiClient} restApi WP REST client.
 */
export async function createOutOfStockProduct(
	restApi: ApiClient
): Promise< BISProduct > {
	// Append a random suffix so parallel workers don't collide on the product name,
	// which would break the row-scoped selectors in the admin list-table specs.
	const name = `BIS Test Product ${ Date.now() }-${ Math.floor(
		Math.random() * 1e6
	) }`;

	const response = await restApi.post< {
		id: number;
		name: string;
		permalink: string;
	} >( `${ WC_API_PATH }/products`, {
		name,
		type: 'simple',
		regular_price: '9.99',
		manage_stock: false,
		stock_status: 'outofstock',
	} );
	const product = response.data;

	return {
		id: product.id,
		name: product.name,
		permalink: product.permalink,
	};
}

/**
 * A variation of an out-of-stock variable product created for a spec.
 */
export type BISVariation = {
	id: number;
	option: string;
	/**
	 * The name core interpolates into notices and email subjects for this
	 * variation — `Notification::get_product_name()` returns the variation's
	 * post title, which `WC_Product_Variation_Data_Store_CPT::generate_product_title()`
	 * builds as the parent title plus an attribute suffix, or the bare parent
	 * title when the variation has no attribute values of its own.
	 *
	 * The REST API's own `name` field is the formatted attribute list ("White"),
	 * not this, so it cannot stand in for it.
	 */
	notificationName: string;
};

/**
 * An out-of-stock variable product created for a spec.
 */
export type BISVariableProduct = BISProduct & {
	/** Label of the (product-level) variation attribute, e.g. `Color`. */
	attributeLabel: string;
	/** Name of the front-end variation `select`, e.g. `attribute_color`. */
	attributeSelect: string;
	/** Only set for the two-variation fixture. */
	inStockVariation?: BISVariation;
	outOfStockVariation: BISVariation;
};

/**
 * Label of the variation attribute used by the variable-product fixtures.
 *
 * A product-level (not global) attribute, so parallel workers can reuse the
 * same label without colliding on a shared taxonomy term.
 *
 * Keep it single-word ASCII: `attributeSelect` lowercases it to build the
 * select name, which only matches core's `sanitize_title()` for such labels.
 */
const BIS_VARIATION_ATTRIBUTE = 'Color';

/**
 * Return a handle to a variable product with one in-stock and one out-of-stock variation.
 *
 * Deletion is not the caller's job: the fixtures below queue the parent id for
 * the worker-scoped batch in `reapProducts()`, and deleting the parent takes
 * its variations with it.
 *
 * @param {ApiClient} restApi             WP REST client.
 * @param {Object}    [opts]              Creation options.
 * @param {boolean}   [opts.anyAttribute] Create a single attribute-less variation.
 */
export async function createOutOfStockVariableProduct(
	restApi: ApiClient,
	opts: { anyAttribute?: boolean } = {}
): Promise< BISVariableProduct > {
	const name = `BIS Test Variable Product ${ Date.now() }-${ Math.floor(
		Math.random() * 1e6
	) }`;

	const { data: product } = await restApi.post< {
		id: number;
		name: string;
		permalink: string;
	} >( `${ WC_API_PATH }/products`, {
		name,
		type: 'variable',
		attributes: [
			{
				name: BIS_VARIATION_ATTRIBUTE,
				visible: true,
				variation: true,
				options: [ 'Blue', 'White' ],
			},
		],
	} );

	// One request for both variations: the fixture runs before every test in
	// these specs, so a second round trip here is per-test overhead.
	const create = opts.anyAttribute
		? [
				{
					regular_price: '9.99',
					manage_stock: false,
					stock_status: 'outofstock',
					// The REST controller stores an empty `option` as an empty
					// `attribute_color` meta value, the same shape the admin
					// writes. Omitting the attribute entirely would store no
					// meta row at all, which `find_matching_product_variation()`
					// cannot match.
					attributes: [
						{ name: BIS_VARIATION_ATTRIBUTE, option: '' },
					],
				},
		  ]
		: [
				{
					regular_price: '9.99',
					manage_stock: false,
					stock_status: 'instock',
					attributes: [
						{ name: BIS_VARIATION_ATTRIBUTE, option: 'Blue' },
					],
				},
				{
					regular_price: '9.99',
					manage_stock: false,
					stock_status: 'outofstock',
					attributes: [
						{ name: BIS_VARIATION_ATTRIBUTE, option: 'White' },
					],
				},
		  ];

	const { data: batch } = await restApi.post< {
		create: Array< { id: number; stock_status: string } >;
	} >( `${ WC_API_PATH }/products/${ product.id }/variations/batch`, {
		create,
	} );

	const created = batch.create;

	// A short batch would otherwise surface as an opaque "cannot read
	// properties of undefined" from the variation lookups below.
	expect( created ).toHaveLength( create.length );

	// A variation that came back with the stock status we didn't ask for would
	// otherwise surface much later, as a form that never appears or an email
	// that never arrives.
	created.forEach( ( variation: { stock_status: string }, index: number ) => {
		expect( variation.stock_status ).toBe( create[ index ].stock_status );
	} );

	const notificationName = ( option: string ): string =>
		opts.anyAttribute ? product.name : `${ product.name } - ${ option }`;

	const outOfStock = {
		id: created[ opts.anyAttribute ? 0 : 1 ].id,
		option: 'White',
		notificationName: notificationName( 'White' ),
	};
	const inStock = opts.anyAttribute
		? undefined
		: {
				id: created[ 0 ].id,
				option: 'Blue',
				notificationName: notificationName( 'Blue' ),
		  };

	return {
		id: product.id,
		name: product.name,
		permalink: product.permalink,
		attributeLabel: BIS_VARIATION_ATTRIBUTE,
		attributeSelect: `attribute_${ BIS_VARIATION_ATTRIBUTE.toLowerCase() }`,
		inStockVariation: inStock,
		outOfStockVariation: outOfStock,
	};
}

/**
 * Per-product meta that opts a product out of stock notification signups.
 *
 * Set on the parent product: `EligibilityService::product_allows_signups()`
 * resolves a variation by recursing up to its parent, so a variation has no
 * opt-out of its own.
 *
 * @see Config::get_product_signups_meta_key()
 */
export const BIS_PRODUCT_SIGNUPS_META =
	'customer_stock_notifications_enable_signups';

/**
 * Opt a product out of (or back into) stock notification signups.
 *
 * @param {ApiClient} restApi   WP REST client.
 * @param {number}    productId Product id.
 * @param {boolean}   allowed   Whether signups are allowed for the product.
 */
export async function setProductSignupsAllowed(
	restApi: ApiClient,
	productId: number,
	allowed: boolean
): Promise< void > {
	await restApi.put( `${ WC_API_PATH }/products/${ productId }`, {
		meta_data: [
			{
				key: BIS_PRODUCT_SIGNUPS_META,
				value: allowed ? 'yes' : 'no',
			},
		],
	} );
}

/**
 * Restock a single variation via REST, leaving the rest of the product untouched.
 *
 * @param {ApiClient} restApi     WP REST client.
 * @param {number}    productId   Parent product id.
 * @param {number}    variationId Variation id.
 */
export async function restockVariation(
	restApi: ApiClient,
	productId: number,
	variationId: number
): Promise< void > {
	const response = await restApi.put< { stock_status: string } >(
		`${ WC_API_PATH }/products/${ productId }/variations/${ variationId }`,
		{
			stock_status: 'instock',
			manage_stock: false,
		}
	);

	expect( response.data.stock_status ).toBe( 'instock' );
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
	const response = await restApi.put< { stock_status: string } >(
		`${ WC_API_PATH }/products/${ productId }`,
		{
			stock_status: 'instock',
			manage_stock: false,
		}
	);

	// A 200 whose body doesn't reflect the requested stock change would
	// otherwise only surface ~20s later as an email timeout.
	expect( response.data.stock_status ).toBe( 'instock' );
}

/**
 * Locator for the PDP sign-up form wrapper.
 *
 * The wrapper is rendered whenever the product allows signups, and core's
 * `back-in-stock-form.js` toggles its `hidden` class from the `show_variation`
 * event — the class the variation specs assert on. A product whose parent opts
 * out of signups renders no wrapper at all, so that case is asserted on
 * presence instead.
 *
 * @param {Page} page Playwright page on the product detail.
 */
export function bisFormLocator( page: Page ) {
	return page.locator( '.wc_bis_form' );
}

/**
 * Locator for the hidden input carrying the product the sign-up targets.
 *
 * Starts out holding the parent id and is swapped to the variation id by
 * `found_variation`, so it is the assertion that the form targets the variation
 * the shopper picked rather than the product they landed on.
 *
 * @param {Page} page Playwright page on the product detail.
 */
export function bisTargetProductInput( page: Page ) {
	return page.locator( 'input[name="wc_bis_product_id"]' );
}

/**
 * Pick a variation on a variable product page and wait for core's variation AJAX to settle.
 *
 * The BIS form only reacts once WooCommerce has fetched the variation and fired
 * `found_variation`, so the wait is on core's own hidden `variation_id` input.
 * `.single_variation_wrap` cannot stand in for it — `VariationForm` shows that
 * wrapper at init, before any variation is picked — and waiting on core's state
 * rather than on `.wc_bis_form` keeps the helper usable in the tests that
 * assert the form's own visibility or absence.
 *
 * @param {Page}   page      Playwright page on the product detail.
 * @param {Object} product   The variable product handle.
 * @param {Object} variation The variation to select.
 */
export async function selectVariation(
	page: Page,
	product: BISVariableProduct,
	variation: BISVariation
): Promise< void > {
	await page
		.locator( `.variations select[name="${ product.attributeSelect }"]` )
		.selectOption( variation.option );

	await expect( page.locator( 'input[name="variation_id"]' ) ).toHaveValue(
		String( variation.id )
	);
}

/**
 * Locator for the account-creation consent checkbox on the PDP sign-up form.
 *
 * Its label is the store's registration privacy text, which the merchant can
 * edit, so it is located by name rather than by that label.
 *
 * @param {Page} page Playwright page on the product detail.
 */
export function bisConsentCheckbox( page: Page ) {
	return page.locator( 'input[name="wc_bis_opt_in"]' );
}

/**
 * Submit the PDP sign-up form. Caller must already have the product page loaded.
 *
 * @param {Page}    page           Playwright page on the product detail.
 * @param {Object}  [opts]         Fill options.
 * @param {string}  [opts.email]   Email address to enter (guest flow only; logged-in PDP hides the field).
 * @param {boolean} [opts.consent] Tick the account-creation consent checkbox (only rendered with `createAccountOnSignup`).
 */
export async function signUpOnProductPage(
	page: Page,
	opts: {
		email?: string;
		consent?: boolean;
	} = {}
): Promise< void > {
	if ( opts.email !== undefined ) {
		await page
			.getByRole( 'textbox', {
				name: /Email address to be notified/i,
			} )
			.fill( opts.email );
	}

	if ( opts.consent ) {
		await bisConsentCheckbox( page ).check();
	}

	await page.getByRole( 'button', { name: /Notify me/i } ).click();
}

/**
 * Submit the PDP signup form in a fresh browser context and wait for the success notice.
 *
 * Runs in its own context so the caller's page (usually an admin session that
 * goes on to read the mail log) is left untouched.
 *
 * @param {Browser} browser                          The test's browser fixture.
 * @param {string}  permalink                        The product permalink.
 * @param {Object}  opts                             Signup options.
 * @param {Object}  [opts.storageState]              Storage state for the signup context; a logged-out guest by default.
 * @param {string}  [opts.email]                     Email to enter; omit for a logged-in signup, where the field isn't rendered.
 * @param {boolean} [opts.consent]                   Tick the account-creation consent checkbox before submitting.
 * @param {RegExp}  [opts.expectedNotice]            Notice to wait for after the post; a generic success match by default.
 * @param {Object}  [opts.selectVariation]           Variation to pick before submitting, for variable products.
 * @param {Object}  [opts.selectVariation.product]   The variable product handle.
 * @param {Object}  [opts.selectVariation.variation] The variation to select.
 */
export async function signUpInNewContext(
	browser: Browser,
	permalink: string,
	opts: {
		storageState?: string | { cookies: []; origins: [] };
		email?: string;
		consent?: boolean;
		expectedNotice?: RegExp;
		selectVariation?: {
			product: BISVariableProduct;
			variation: BISVariation;
		};
	} = {}
): Promise< void > {
	const context = await browser.newContext( {
		storageState: opts.storageState ?? { cookies: [], origins: [] },
	} );
	const page = await context.newPage();

	// Closed in `finally`: these specs run on a single worker, so a context
	// left open by a failed signup would otherwise outlive the test.
	try {
		await page.goto( permalink );

		if ( opts.selectVariation ) {
			await selectVariation(
				page,
				opts.selectVariation.product,
				opts.selectVariation.variation
			);
		}

		await signUpOnProductPage( page, {
			email: opts.email,
			consent: opts.consent,
		} );

		// The form posts and reloads the PDP with a notice. Wait for that notice
		// before closing the context, or the submission can be aborted mid-flight
		// and the spec fails later, looking like a missing email.
		await expect(
			page.getByText(
				opts.expectedNotice ??
					/You have successfully signed up|Thanks for signing up/i
			)
		).toBeVisible();
	} finally {
		await context.close();
	}
}

/**
 * Submit the PDP signup form as a logged-out guest, regardless of the test's storageState.
 *
 * @param {Browser} browser                          The test's browser fixture.
 * @param {string}  permalink                        The product permalink.
 * @param {string}  email                            The guest's email address.
 * @param {Object}  [opts]                           Signup options.
 * @param {Object}  [opts.selectVariation]           Variation to pick before submitting, for variable products.
 * @param {Object}  [opts.selectVariation.product]   The variable product handle.
 * @param {Object}  [opts.selectVariation.variation] The variation to select.
 */
export async function signUpAsGuest(
	browser: Browser,
	permalink: string,
	email: string,
	opts: {
		selectVariation?: {
			product: BISVariableProduct;
			variation: BISVariation;
		};
	} = {}
): Promise< void > {
	await signUpInNewContext( browser, permalink, { email, ...opts } );
}

/**
 * Submit the PDP signup form as the shared logged-in customer, regardless of the test's storageState.
 *
 * The signup binds to the customer's account, which is what makes the emails
 * take their logged-in branch.
 *
 * @param {Browser} browser   The test's browser fixture.
 * @param {string}  permalink The product permalink.
 */
export async function signUpAsCustomer(
	browser: Browser,
	permalink: string
): Promise< void > {
	await signUpInNewContext( browser, permalink, {
		storageState: CUSTOMER_STATE_PATH,
	} );
}

/**
 * Find the customer account registered for an email address, if any.
 *
 * @param {ApiClient} restApi WP REST client.
 * @param {string}    email   The email address.
 */
export async function findCustomerByEmail(
	restApi: ApiClient,
	email: string
): Promise< BISCustomer | undefined > {
	const response = await restApi.get< BISCustomer[] >(
		`${ WC_API_PATH }/customers`,
		{
			email,
			role: 'all',
		}
	);

	return response.data.find(
		( customer: BISCustomer ) => customer.email === email
	);
}

/**
 * A customer account, as far as these specs need to know it.
 */
type BISCustomer = { id: number; email: string; username: string };

/**
 * Permanently delete a customer account created by a signup.
 *
 * @param {ApiClient} restApi    WP REST client.
 * @param {number}    customerId The customer id.
 */
export async function deleteCustomer(
	restApi: ApiClient,
	customerId: number
): Promise< void > {
	await restApi.delete( `${ WC_API_PATH }/customers/${ customerId }`, {
		force: true,
	} );
}

/**
 * Make every verification link look expired to the server for this page's context.
 *
 * Expiry is filter-driven rather than an option, so it is set through the
 * `e2e-filters` cookie the test helper plugin reads. A negative threshold
 * makes `time() - timestamp > threshold` true for any link, however fresh.
 *
 * @param {Page} page Playwright page whose context will follow the link.
 */
export async function expireVerificationLinks( page: Page ): Promise< void > {
	await setFilterValue(
		page,
		'woocommerce_customer_stock_notifications_verification_expiration_time_threshold',
		-1
	);
}

/**
 * Replace the action key in an email link with one that cannot match.
 *
 * @param {string} link The verify or unsubscribe link from the email.
 */
export function corruptEmailLinkKey( link: string ): string {
	const url = new URL( link );
	url.searchParams.set( 'email_link_action_key', 'not-the-real-key' );
	return url.toString();
}

/**
 * Text the email footer renders for a signup bound to an account.
 *
 * @see templates/emails/customer-stock-notification.php
 * @see templates/emails/customer-stock-notification-verified.php
 */
export const BIS_EMAIL_FOOTER = {
	loggedIn:
		/To manage your notifications, click here to log in to your account\./,
	guest: /To stop receiving these messages, click here to unsubscribe\./,
} as const;

/**
 * Build the admin notifications-list URL, optionally filtered to one product.
 *
 * Relative (no leading slash) so it resolves under any `baseURL` subdirectory,
 * matching the rest of the suite's navigation convention.
 *
 * @param {number} productId Product id to filter the list by.
 */
export function bisAdminListUrl( productId: number ): string {
	return `wp-admin/admin.php?page=wc-customer-stock-notifications&customer_stock_notifications_product_filter=${ productId }`;
}

/**
 * Assert the product's notifications list holds no row for an email address.
 *
 * Opens its own admin context because the list is an admin-only screen, and
 * closes it in `finally` so a failure doesn't leak a context into the run.
 *
 * @param {Browser} browser   The test's browser fixture.
 * @param {number}  productId Product the list is filtered by.
 * @param {string}  email     The signup email address that must not be listed.
 */
export async function expectNoSignupAsAdmin(
	browser: Browser,
	productId: number,
	email: string
): Promise< void > {
	const adminContext = await browser.newContext( {
		storageState: ADMIN_STATE_PATH,
	} );

	try {
		const adminPage = await adminContext.newPage();
		await adminPage.goto( bisAdminListUrl( productId ) );

		await expect(
			adminPage.getByRole( 'row' ).filter( {
				has: adminPage.getByText( email, { exact: true } ),
			} )
		).toHaveCount( 0 );
	} finally {
		await adminContext.close();
	}
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

/**
 * Ids of products created by the `product` fixture, deleted in one batch when
 * the worker finishes. A per-test DELETE costs ~0.45s, which is pure overhead
 * on a suite where every test needs its own product.
 */
const productsToReap: number[] = [];

/**
 * Delete every product the `product` fixture created, in a single request.
 */
async function reapProducts(): Promise< void > {
	if ( productsToReap.length === 0 ) {
		return;
	}

	const restApi = createClient( process.env.BASE_URL as string, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );

	await restApi
		.post( `${ WC_API_PATH }/products/batch`, {
			delete: productsToReap.splice( 0 ),
		} )
		.catch( () => {
			/* best-effort cleanup */
		} );
}

/**
 * Shared fixtures for the Back in Stock Notifications specs.
 */
export const test = baseTest.extend<
	{
		product: BISProduct;
		variableProduct: BISVariableProduct;
		anyAttributeVariableProduct: BISVariableProduct;
		accountEmail: string;
	},
	{ bisEnvReady: void }
>( {
	/**
	 * Verify the env can run these specs at all, once per worker rather than
	 * once per file — each check shells out through `wp-env run cli`. Doubles as
	 * the worker-scoped teardown that reaps the products the specs created.
	 */
	bisEnvReady: [
		async ( {}, use ) => {
			await assertBISEnvReady();
			await use();
			await reapProducts();
		},
		{ scope: 'worker', auto: true },
	],

	/**
	 * An out-of-stock simple product, created before the test. Its deletion is
	 * deferred to the worker-scoped batch above rather than awaited here.
	 */
	product: async ( { restApi }, use ) => {
		const product = await createOutOfStockProduct( restApi );
		// eslint-disable-next-line react-hooks/rules-of-hooks -- Playwright's fixture `use`, not a React hook.
		await use( product );
		productsToReap.push( product.id );
	},

	/**
	 * A variable product with one in-stock and one out-of-stock variation.
	 *
	 * Fixtures are lazy, so a spec that never references this pays nothing for it.
	 */
	variableProduct: async ( { restApi }, use ) => {
		const product = await createOutOfStockVariableProduct( restApi );
		// eslint-disable-next-line react-hooks/rules-of-hooks -- Playwright's fixture `use`, not a React hook.
		await use( product );
		// Deleting the parent takes its variations with it, so the worker-scoped
		// batch still needs only the one id.
		productsToReap.push( product.id );
	},

	/**
	 * A variable product with a single attribute-less out-of-stock variation.
	 */
	anyAttributeVariableProduct: async ( { restApi }, use ) => {
		const product = await createOutOfStockVariableProduct( restApi, {
			anyAttribute: true,
		} );
		// eslint-disable-next-line react-hooks/rules-of-hooks -- Playwright's fixture `use`, not a React hook.
		await use( product );
		productsToReap.push( product.id );
	},

	/**
	 * A guest email address that a signup may register an account for.
	 *
	 * Teardown looks the address up and deletes the account if one exists, so
	 * the customer is reaped whether the test failed on the notice, on the
	 * lookup, or on the email — the test itself never has to hold the id.
	 */
	accountEmail: async ( { restApi }, use ) => {
		const email = uniqueGuestEmail( 'bis-account' );
		// eslint-disable-next-line react-hooks/rules-of-hooks -- Playwright's fixture `use`, not a React hook.
		await use( email );

		const account = await findCustomerByEmail( restApi, email );
		if ( account ) {
			await deleteCustomer( restApi, account.id );
		}
	},
} );

/**
 * Anchor ids rendered by the Back in Stock Notifications email templates.
 *
 * Targeting these is more precise than scanning every href in the body: the
 * back-in-stock email carries both a product CTA and an unsubscribe link, and
 * a pattern loose enough to match one can match the other.
 *
 * @see templates/emails/customer-stock-notification.php
 * @see templates/emails/customer-stock-notification-verify.php
 * @see templates/emails/customer-stock-notification-verified.php
 */
export const BIS_EMAIL_LINKS = {
	// Product CTA in the back-in-stock email; verification link in the verify email.
	actionButton: '#notification__action_button',
	unsubscribe: '#notification__unsubscribe_link',
} as const;

/**
 * Element ids rendered inside the Back in Stock Notifications email templates.
 *
 * @see EmailTemplatesController::register_template_hooks()
 */
export const BIS_EMAIL_ELEMENTS = {
	productTitle: '#notification__product__title',
	// Only rendered when the notification has a variation attribute list, so
	// absent from a simple product's email.
	productAttributes: '#notification__product__attributes',
} as const;

/**
 * Frame locator for the email body inside an open WP Mail Logging modal.
 *
 * @param {Page} page Playwright page with the mail-log modal open.
 */
export function bisEmailBody( page: Page ) {
	return page.frameLocator(
		'#wp-mail-logging-modal-content-body-content iframe'
	);
}

/**
 * Escape a string for literal use inside a regular expression.
 *
 * @param {string} value The string to escape.
 */
export function escapeRegExp( value: string ): string {
	return value.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
}

/**
 * Sign-up notices core prints on the PDP after the form posts.
 *
 * Success notices are bound to the product name where core interpolates it,
 * so a notice for the wrong product fails instead of passing.
 *
 * @see SignupService::get_signup_user_message()
 * @see SignupService::get_error_message()
 * @see EmailActionController
 */
export const bisNotice = {
	/**
	 * Single opt-in success.
	 *
	 * @param {string} productName The product name.
	 */
	success: ( productName: string ): RegExp =>
		new RegExp(
			`You have successfully signed up! You will be notified when "${ escapeRegExp(
				productName
			) }" is back in stock\\.`
		),
	doubleOptIn:
		/Thanks for signing up! Please complete the sign-up process by following the verification link sent to your e-mail\./,
	/**
	 * Single opt-in success where the signup also registered an account.
	 *
	 * @param {string} productName The product name.
	 */
	accountCreated: ( productName: string ): RegExp =>
		new RegExp(
			`You have successfully signed up and will be notified when "${ escapeRegExp(
				productName
			) }" is back in stock! Note that a new account has been created for you; please check your e-mail for details\\.`
		),
	accountCreatedDoubleOptIn:
		/Thanks for signing up! An account has been created for you\. Please complete the sign-up process by following the verification link sent to your e-mail\./,
	alreadyJoined: /You have already joined this waitlist\./,
	accountRequired: /Please log in to sign up for stock notifications\./,
	/**
	 * Printed on the shop page after a verify link is followed.
	 *
	 * @param {string} productName The product name.
	 */
	verified: ( productName: string ): RegExp =>
		new RegExp(
			`Successfully verified stock notifications for "${ escapeRegExp(
				productName
			) }"\\.`
		),
	/**
	 * Printed on the shop page after an unsubscribe link is followed.
	 *
	 * @param {string} email       The unsubscribed email address.
	 * @param {string} productName The product name.
	 */
	unsubscribed: ( email: string, productName: string ): RegExp =>
		new RegExp(
			`Successfully unsubscribed ${ escapeRegExp(
				email
			) }\\. You will not receive a notification when "${ escapeRegExp(
				productName
			) }" becomes available\\.`
		),
	errors: {
		invalidEmail: /Invalid email address\./,
		invalidProduct: /Invalid product\./,
		missingConsent:
			/To proceed, please consent to the creation of a new account with your e-mail\./,
		failed: /Failed to sign up\. Please try again\./,
	},
} as const;

/**
 * Subject matchers for the three BIS emails, bound to a specific product.
 *
 * The product name is interpolated rather than wildcarded so a notification for
 * the wrong product fails the test instead of passing it.
 *
 * @see src/Internal/StockNotifications/Emails/
 */
const subjectMatcher = ( subject: string ): RegExp =>
	new RegExp( escapeRegExp( subject ) );

export const bisEmailSubject = {
	/**
	 * Verify email.
	 *
	 * @param {string} productName The product name.
	 */
	verify: ( productName: string ): RegExp =>
		subjectMatcher( `Join the "${ productName }" waitlist.` ),

	/**
	 * Verified email.
	 *
	 * @param {string} productName The product name.
	 */
	verified: ( productName: string ): RegExp =>
		subjectMatcher( `You have joined the "${ productName }" waitlist.` ),

	/**
	 * Back-in-stock email.
	 *
	 * @param {string} productName The product name.
	 */
	backInStock: ( productName: string ): RegExp =>
		subjectMatcher( `"${ productName }" is back in stock!` ),
} as const;

/**
 * Assert an email landed in the mail log, from a throwaway admin context.
 *
 * For specs whose own page is a guest or customer session: WP Mail Logging
 * is an admin-only screen. The context is closed in `finally` so a missing
 * email fails the test without leaking a context into the rest of the run.
 *
 * @param {Browser} browser              The test's browser fixture.
 * @param {string}  receiverEmailAddress The recipient email address.
 * @param {RegExp}  subject              The email subject (regular expression).
 * @param {number}  [expectedCount]      Expected number of matching rows. Defaults to 1.
 */
export async function expectEmailAsAdmin(
	browser: Browser,
	receiverEmailAddress: string,
	subject: RegExp,
	expectedCount = 1
): Promise< void > {
	const adminContext = await browser.newContext( {
		storageState: ADMIN_STATE_PATH,
	} );

	try {
		const adminPage = await adminContext.newPage();
		await expectEmail(
			adminPage,
			receiverEmailAddress,
			subject,
			expectedCount
		);
	} finally {
		await adminContext.close();
	}
}

/**
 * Open the WP Mail Logging entry for a given recipient and subject, leaving its modal open.
 *
 * Finds the row through `expectEmail()` so this shares its re-navigating poll
 * rather than reading the log once — an email dispatched after the first
 * navigation is still found.
 *
 * @param {Page}   page                 Playwright page.
 * @param {string} receiverEmailAddress The recipient email address.
 * @param {RegExp} subject              The email subject (regular expression).
 * @param {number} [expectedCount]      Expected number of matching rows. Defaults to 1.
 */
export async function openEmailInMailLog(
	page: Page,
	receiverEmailAddress: string,
	subject: RegExp,
	expectedCount = 1
): Promise< void > {
	const rows = await expectEmail(
		page,
		receiverEmailAddress,
		subject,
		expectedCount
	);

	// The log is sorted newest first, so the first row is the latest email.
	await rows.first().getByRole( 'button', { name: 'View log' } ).click();

	await expect(
		page.locator( '#wp-mail-logging-modal-content-body-content' )
	).toBeVisible();
}

/**
 * Close the WP Mail Logging modal, if this version of the plugin renders a close control.
 *
 * @param {Page} page Playwright page.
 */
async function closeMailLogModal( page: Page ): Promise< void > {
	const closeButton = page
		.locator(
			'#wp-mail-logging-modal-content-header-close, .wp-mail-logging-modal-close'
		)
		.first();

	// Some wp-mail-logging versions don't surface an explicit close button.
	// Check rather than swallowing a click failure, which would otherwise burn
	// the full action timeout on every call.
	if ( ( await closeButton.count() ) > 0 ) {
		await closeButton.click( { timeout: 2000 } ).catch( () => {} );
	}
}

/**
 * Open an email in WP Mail Logging and return the href of a specific anchor, selected by its id.
 *
 * Selecting the anchor by its stable template id names the link you mean
 * instead of inferring it from the URL, so the assertion can then check the
 * URL without circularity.
 *
 * @param {Page}   page                 Playwright page.
 * @param {string} receiverEmailAddress The recipient email address.
 * @param {RegExp} subject              The email subject (regular expression).
 * @param {string} anchorId             CSS id selector, e.g. `BIS_EMAIL_LINKS.actionButton`.
 * @param {number} [expectedCount]      Expected number of matching rows. Defaults to 1.
 */
export async function getEmailLinkById(
	page: Page,
	receiverEmailAddress: string,
	subject: RegExp,
	anchorId: string,
	expectedCount = 1
): Promise< string > {
	await openEmailInMailLog(
		page,
		receiverEmailAddress,
		subject,
		expectedCount
	);

	const anchor = bisEmailBody( page ).locator( `a${ anchorId }` ).first();
	await anchor.waitFor( { state: 'attached' } );

	const href = await anchor.getAttribute( 'href' );

	if ( ! href ) {
		throw new Error(
			`No anchor ${ anchorId } with an href found in email to ${ receiverEmailAddress } with subject ${ subject }`
		);
	}

	await closeMailLogModal( page );

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
