/* eslint-disable rulesdir/no-raw-playwright-test-import */
/**
 * External dependencies
 */
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
} from '@woocommerce/e2e-utils';

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
	page: async ( { page }, use, testInfo ) => {
		page.on( 'console', observeConsoleLogging );

		// QAO-524 PROBE (diagnostic, revert before merge): characterise the
		// CI-only Blocks e2e build-log errors that observeConsoleLogging
		// surfaces as bare text without URLs. Aggregate in memory and flush a
		// single summary per test to avoid log spam and timing skew.
		//   - net failures -> net::ERR_INSUFFICIENT_RESOURCES request storm
		//   - HTTP >= 400   -> the 404s and the 500s (with 5xx response bodies)
		const failByKey = new Map< string, number >();
		const httpErrByKey = new Map< string, number >();
		const server5xxBodies = new Map< string, string >();
		const pendingBodyReads: Array< Promise< void > > = [];
		let totalRequests = 0;
		let totalFailures = 0;
		let abortedCount = 0;
		let inflight = 0;
		let peakInflight = 0;
		const redact = ( u: string ): string =>
			u.replace(
				/([?&](?:_wpnonce|nonce|_ajax_nonce)=)[^&]+/gi,
				'$1<redacted>'
			);
		// Path-only key for the request storm (collapses numeric ids).
		const normalizePath = ( u: string ): string => {
			try {
				const url = new URL( u );
				return (
					url.host + url.pathname.replace( /\/\d+(?=\/|$)/g, '/<id>' )
				);
			} catch {
				return u;
			}
		};
		// Path + sanitised query for HTTP-error attribution (query context
		// matters, e.g. ?context=edit on the product_attribute 404).
		const normalizeHttp = ( u: string ): string => {
			try {
				const url = new URL( u );
				return redact( url.host + url.pathname + url.search );
			} catch {
				return redact( u );
			}
		};
		const settle = (): void => {
			if ( inflight > 0 ) {
				inflight--;
			}
		};
		page.on( 'request', () => {
			totalRequests++;
			inflight++;
			if ( inflight > peakInflight ) {
				peakInflight = inflight;
			}
		} );
		page.on( 'requestfinished', settle );
		page.on( 'requestfailed', ( req ) => {
			settle();
			const err = req.failure()?.errorText || 'unknown';
			// net::ERR_ABORTED is benign request cancellation on navigation /
			// teardown; count it but keep it out of the storm tally.
			if ( err === 'net::ERR_ABORTED' ) {
				abortedCount++;
				return;
			}
			totalFailures++;
			const key = `${ err } ${ req.method() } ${ req.resourceType() } ${ normalizePath(
				req.url()
			) }`;
			failByKey.set( key, ( failByKey.get( key ) || 0 ) + 1 );
		} );
		// Context-level (not page-level): the 500s fire on secondary pages the
		// editor opens (frontend / preview), which a page-scoped listener
		// misses. The context covers every page opened during the test.
		page.context().on( 'response', ( resp ) => {
			const status = resp.status();
			if ( status < 400 ) {
				return;
			}
			let where = '';
			try {
				where = ` page=${ new URL( resp.frame().url() ).pathname }`;
			} catch {
				where = '';
			}
			const key = `${ status } ${ resp
				.request()
				.method() } ${ normalizeHttp( resp.url() ) }${ where }`;
			httpErrByKey.set( key, ( httpErrByKey.get( key ) || 0 ) + 1 );
			// Capture the body of the first sample of each 5xx: WP fatals /
			// REST error payloads carry the root cause. Passive read, tracked
			// so the per-test summary can wait for it.
			if ( status >= 500 && ! server5xxBodies.has( key ) ) {
				server5xxBodies.set( key, '(pending)' );
				const ct = resp.headers()[ 'content-type' ] || '';
				if ( /image\/|font\/|octet-stream|video\//i.test( ct ) ) {
					server5xxBodies.set( key, `(binary ${ ct })` );
				} else {
					pendingBodyReads.push(
						resp
							.text()
							.then( ( body ) => {
								server5xxBodies.set(
									key,
									`ct=${ ct } len=${
										body.length
									} body=${ JSON.stringify(
										body.slice( 0, 300 )
									) }`
								);
							} )
							.catch( () => {
								server5xxBodies.set(
									key,
									'(body unavailable)'
								);
							} )
					);
				}
			}
		} );

		await use( page );

		// Let in-flight 5xx body reads settle, but never block teardown.
		if ( pendingBodyReads.length ) {
			const timeout = new Promise< void >( ( resolve ) => {
				setTimeout( resolve, 3000 );
			} );
			await Promise.race( [
				Promise.allSettled( pendingBodyReads ),
				timeout,
			] );
		}

		const has5xx = [ ...httpErrByKey.keys() ].some( ( k ) =>
			k.startsWith( '5' )
		);
		if ( totalFailures > 0 || has5xx || peakInflight >= 100 ) {
			const fmt = ( m: Map< string, number > ): string =>
				[ ...m.entries() ]
					.sort( ( a, b ) => b[ 1 ] - a[ 1 ] )
					.slice( 0, 12 )
					.map( ( [ k, n ] ) => `\n    ${ n }x ${ k }` )
					.join( '' );
			const bodies = [ ...server5xxBodies.entries() ]
				.map( ( [ k, b ] ) => `\n    ${ k } => ${ b }` )
				.join( '' );
			// eslint-disable-next-line no-console
			console.error(
				`[QAO524-PROBE] test="${ testInfo.title }" requests=${ totalRequests } netFailures=${ totalFailures } aborted=${ abortedCount } peakInflight=${ peakInflight }` +
					( failByKey.size
						? `\n  NETFAIL (storm):${ fmt( failByKey ) }`
						: '' ) +
					( httpErrByKey.size
						? `\n  HTTP>=400:${ fmt( httpErrByKey ) }`
						: '' ) +
					( bodies ? `\n  5xx bodies:${ bodies }` : '' )
			);
		}

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

		await wpCLI( `db reset --yes` );
		// Reset the database to the initial state via snapshot import.
		await wpCLI( `db import ${ DB_EXPORT_FILE }` );
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
