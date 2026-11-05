/* eslint-disable rulesdir/no-raw-playwright-test-import */
/**
 * External dependencies
 */
import { randomUUID } from 'crypto';
import { test as base, expect, ConsoleMessage } from '@playwright/test';
import {
	STORAGE_STATE_PATH,
	DB_EXPORT_FILE,
	wpCLI,
	Admin,
	Editor,
	FrontendUtils,
	LocalPickupUtils,
	MiniCartUtils,
	PageUtils,
	PerformanceUtils,
	RequestUtils,
	ShippingUtils,
	WPTemplate,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { restoreBlocksDatabase } from './wp-cli';

/**
 * Set of console logging types observed to protect against unexpected yet
 * handled (i.e. not catastrophic) errors or warnings. Each key corresponds
 * to the Playwright ConsoleMessage type, its value the corresponding function
 * on the console global object.
 */
const OBSERVED_CONSOLE_MESSAGE_TYPES = [ 'warn', 'error' ] as const;

/**
 * Adds a page event handler to emit uncaught exception to process if one of
 * the observed console logging types is encountered.
 *
 * @param message The console message.
 */
function observeConsoleLogging( message: ConsoleMessage ) {
	const type = message.type();
	if (
		! OBSERVED_CONSOLE_MESSAGE_TYPES.includes(
			type as ( typeof OBSERVED_CONSOLE_MESSAGE_TYPES )[ number ]
		)
	) {
		return;
	}

	const text = message.text();

	// An exception is made for _blanket_ deprecation warnings: Those
	// which log regardless of whether a deprecated feature is in use.
	if ( text.includes( 'This is a global warning' ) ) {
		return;
	}

	// A chrome advisory warning about SameSite cookies is informational
	// about future changes, tracked separately for improvement in core.
	//
	// See: https://core.trac.wordpress.org/ticket/37000
	// See: https://www.chromestatus.com/feature/5088147346030592
	// See: https://www.chromestatus.com/feature/5633521622188032
	if ( text.includes( 'A cookie associated with a cross-site resource' ) ) {
		return;
	}

	// Viewing posts on the front end can result in this error, which
	// has nothing to do with Gutenberg.
	if ( text.includes( 'net::ERR_UNKNOWN_URL_SCHEME' ) ) {
		return;
	}

	// Not implemented yet.
	// Network errors are ignored only if we are intentionally testing
	// offline mode.
	// if (
	// 	text.includes( 'net::ERR_INTERNET_DISCONNECTED' ) &&
	// 	isOfflineMode()
	// ) {
	// 	return;
	// }

	// As of WordPress 5.3.2 in Chrome 79, navigating to the block editor
	// (Posts > Add New) will display a console warning about
	// non - unique IDs.
	// See: https://core.trac.wordpress.org/ticket/23165
	if ( text.includes( 'elements with non-unique id #_wpnonce' ) ) {
		return;
	}

	// Ignore all JQMIGRATE (jQuery migrate) deprecation warnings.
	if ( text.includes( 'JQMIGRATE' ) ) {
		return;
	}

	const logFunction =
		type as ( typeof OBSERVED_CONSOLE_MESSAGE_TYPES )[ number ];

	// Disable reason: We intentionally bubble up the console message
	// which, unless the test explicitly anticipates the logging via
	// @wordpress/jest-console matchers, will cause the intended test
	// failure.

	// eslint-disable-next-line no-console
	console[ logFunction ]( text );
}

export type ProductTemplate = { template: WPTemplate; productUrl: string };

type WCProduct = {
	id: number;
	name: string;
	type: string;
	regular_price: string;
	sale_price: string;
	attributes: unknown[];
	default_attributes: unknown[];
	images: { id: number }[];
	image?: { id: number } | null;
};

const test = base.extend<
	{
		admin: Admin;
		editor: Editor;
		pageUtils: PageUtils;
		frontendUtils: FrontendUtils;
		performanceUtils: PerformanceUtils;
		snapshotConfig: void;
		shippingUtils: ShippingUtils;
		localPickupUtils: LocalPickupUtils;
		miniCartUtils: MiniCartUtils;
		resetDatabaseAfterEachTest: boolean;
		createProductTemplate: (
			sourceProductSlug: string,
			options?: { content?: string }
		) => Promise< ProductTemplate >;
	},
	{
		requestUtils: RequestUtils;
		wpCoreVersion: number;
	}
>( {
	admin: async ( { page, pageUtils, editor, wpCoreVersion }, use ) => {
		await use( new Admin( { page, pageUtils, editor, wpCoreVersion } ) );
	},
	editor: async ( { page, wpCoreVersion }, use ) => {
		await use( new Editor( { page, wpCoreVersion } ) );
	},
	// Workers share one site, so a project that runs specs in parallel must turn
	// this off: one worker's reset would wipe the data of the others.
	resetDatabaseAfterEachTest: [ true, { option: true } ],
	page: async ( { page, resetDatabaseAfterEachTest }, use ) => {
		page.on( 'console', observeConsoleLogging );

		await use( page );

		// Clear local storage after each test.
		try {
			await page.evaluate( () => {
				window.localStorage.clear();
			} );
		} catch ( error ) {
			// Ignore errors if page is already closed/navigated away
			// eslint-disable-next-line no-console
			console.log( 'Failed to clear localStorage:', error.message );
		}

		// Dispose the current APIRequestContext to free up resources.
		await page.request.dispose();

		if ( resetDatabaseAfterEachTest ) {
			// Navigate away before resetting the DB so the page stops issuing
			// Store API requests against a half-dropped database. Otherwise late
			// in-flight fetches are served the WordPress install page (HTTP 200,
			// text/html) and surface as "The response is not a valid JSON
			// response." console noise.
			try {
				await page.goto( 'about:blank' );
			} catch ( error ) {
				// Ignore errors if page is already closed/navigated away.
				// eslint-disable-next-line no-console
				console.log(
					'Failed to navigate away before DB reset:',
					error.message
				);
			}

			// Reset the database to the initial state via snapshot import.
			await restoreBlocksDatabase( DB_EXPORT_FILE );
		}
	},
	pageUtils: async ( { page }, use ) => {
		await use( new PageUtils( { page } ) );
	},
	frontendUtils: async ( { page, requestUtils }, use ) => {
		await use( new FrontendUtils( page, requestUtils ) );
	},
	performanceUtils: async ( { page }, use ) => {
		await use( new PerformanceUtils( page ) );
	},
	shippingUtils: async ( { page, admin }, use ) => {
		await use( new ShippingUtils( page, admin ) );
	},
	localPickupUtils: async ( { page, admin }, use ) => {
		await use( new LocalPickupUtils( page, admin ) );
	},
	miniCartUtils: async ( { page, frontendUtils }, use ) => {
		await use( new MiniCartUtils( page, frontendUtils ) );
	},
	// Copies a sample product and creates a `single-product-<slug>` template
	// for the copy, so a spec can save a Single Product template without
	// changing the pages other workers visit. The copy is hidden from the
	// catalog. Both are deleted after the test.
	createProductTemplate: async ( { requestUtils }, use ) => {
		const productIds: number[] = [];
		const templateIds: string[] = [];

		await use( async ( sourceProductSlug, options = {} ) => {
			const [ source ] = await requestUtils.rest< WCProduct[] >( {
				path: 'wc/v3/products',
				params: { slug: sourceProductSlug },
			} );
			if ( ! source ) {
				throw new Error(
					`No product with slug ${ sourceProductSlug }`
				);
			}
			const suffix = randomUUID().slice( 0, 8 );
			// Copied field by field rather than with the duplicate endpoint, and
			// with a random slug: concurrent duplicates generate the same
			// "unique" SKU or slug and fail. The name leaves out the source name,
			// so specs that pick "Cap" or "Hoodie" by name never match a copy.
			const product = await requestUtils.rest<
				WCProduct & { slug: string; permalink: string }
			>( {
				method: 'POST',
				path: 'wc/v3/products',
				data: {
					name: `E2E copy ${ suffix }`,
					slug: `${ sourceProductSlug }-${ suffix }`,
					type: source.type,
					regular_price: source.regular_price,
					sale_price: source.sale_price,
					attributes: source.attributes,
					default_attributes: source.default_attributes,
					images: source.images.map( ( { id } ) => ( { id } ) ),
					catalog_visibility: 'hidden',
				},
			} );
			productIds.push( product.id );

			if ( source.type === 'variable' ) {
				const variations = await requestUtils.rest< WCProduct[] >( {
					path: `wc/v3/products/${ source.id }/variations`,
					params: { per_page: 100 },
				} );
				await requestUtils.rest( {
					method: 'POST',
					path: `wc/v3/products/${ product.id }/variations/batch`,
					data: {
						create: variations.map( ( variation ) => ( {
							regular_price: variation.regular_price,
							sale_price: variation.sale_price,
							attributes: variation.attributes,
							image: variation.image && {
								id: variation.image.id,
							},
						} ) ),
					},
				} );
			}

			const template = ( await requestUtils.createTemplate(
				'wp_template',
				{
					slug: `single-product-${ product.slug }`,
					title: `Single Product ${ product.slug }`,
					content: options.content ?? 'placeholder',
				}
			) ) as WPTemplate;
			templateIds.push( template.id );

			return { template, productUrl: product.permalink };
		} );

		await Promise.allSettled(
			templateIds.map( ( templateId ) =>
				requestUtils.revertTemplate( 'wp_template', templateId )
			)
		);
		await Promise.allSettled(
			productIds.map( ( productId ) =>
				requestUtils.rest( {
					method: 'DELETE',
					path: `wc/v3/products/${ productId }`,
					params: { force: true },
				} )
			)
		);
	},
	requestUtils: [
		async ( {}, use, workerInfo ) => {
			const requestUtils = await RequestUtils.setup( {
				baseURL: workerInfo.project.use.baseURL as string,
				storageStatePath: STORAGE_STATE_PATH,
			} );

			await use( requestUtils );
		},
		{ scope: 'worker', auto: true },
	],
	wpCoreVersion: [
		async ( {}, use ) => {
			const output = await wpCLI( 'core version' );
			const version = output.stdout.trim().split( '\n' ).at( -1 ) ?? '';

			// We can parse this as a float because WP never updates the minor
			// version over x.9.x. E.g., after 6.9.x, it will be 7.0.x.
			const parsedVersion = Number.parseFloat( version );

			if ( Number.isNaN( parsedVersion ) ) {
				throw new Error(
					`Failed to parse WordPress version: ${ version }`
				);
			}

			await use( parsedVersion );
		},
		{ scope: 'worker' },
	],
} );

export { test, expect };
