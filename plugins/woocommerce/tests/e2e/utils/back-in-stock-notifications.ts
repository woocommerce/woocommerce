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
import { wpCLI } from './cli';
import { expect, test as baseTest } from '../fixtures/fixtures';
import { admin } from '../test-data/data';

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
 * @param {APIRequest} request                  Playwright request fixture.
 * @param {string}     baseURL                  Test site base URL.
 * @param {Object}     options                  BIS option toggles.
 * @param {boolean}    [options.allowSignups]   Whether the signup form is rendered on product pages.
 * @param {boolean}    [options.doubleOptIn]    Whether signups require email verification before activating.
 * @param {boolean}    [options.requireAccount] Whether signups are limited to logged-in users.
 */
export async function setBISOptions(
	request: APIRequest,
	baseURL: string,
	options: {
		allowSignups?: boolean;
		doubleOptIn?: boolean;
		requireAccount?: boolean;
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
 * Submit the PDP sign-up form. Caller must already have the product page loaded.
 *
 * @param {Page}   page         Playwright page on the product detail.
 * @param {Object} [opts]       Fill options.
 * @param {string} [opts.email] Email address to enter (guest flow only; logged-in PDP hides the field).
 */
export async function signUpOnProductPage(
	page: Page,
	opts: {
		email?: string;
	} = {}
): Promise< void > {
	if ( opts.email !== undefined ) {
		await page
			.getByRole( 'textbox', {
				name: /Email address to be notified/i,
			} )
			.fill( opts.email );
	}

	await page.getByRole( 'button', { name: /Notify me/i } ).click();
}

/**
 * Submit the PDP signup form as a logged-out guest, regardless of the test's storageState.
 *
 * @param {Browser} browser   The test's browser fixture.
 * @param {string}  permalink The product permalink.
 * @param {string}  email     The guest's email address.
 */
export async function signUpAsGuest(
	browser: Browser,
	permalink: string,
	email: string
): Promise< void > {
	const guestContext = await browser.newContext( {
		storageState: { cookies: [], origins: [] },
	} );
	const guestPage = await guestContext.newPage();
	await guestPage.goto( permalink );
	await signUpOnProductPage( guestPage, { email } );

	// The form posts and reloads the PDP with a notice. Wait for that notice
	// before closing the context, or the submission can be aborted mid-flight
	// and the spec fails later, looking like a missing email.
	await expect(
		guestPage.getByText(
			/You have successfully signed up|Thanks for signing up/i
		)
	).toBeVisible();

	await guestContext.close();
}

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
	{ product: BISProduct },
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
 * Escape a string for literal use inside a regular expression.
 *
 * @param {string} value The string to escape.
 */
function escapeRegExp( value: string ): string {
	return value.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
}

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
async function openEmailInMailLog(
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

	const iframe = page.frameLocator(
		'#wp-mail-logging-modal-content-body-content iframe'
	);
	const anchor = iframe.locator( `a${ anchorId }` ).first();
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
