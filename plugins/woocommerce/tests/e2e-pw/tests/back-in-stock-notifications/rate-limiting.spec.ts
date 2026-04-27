/**
 * External dependencies
 */
import { APIRequest, APIRequestContext } from '@playwright/test';
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test as baseTest, expect, request } from '../../fixtures/fixtures';
import { setOption, deleteOption } from '../../utils/options';

// Shared test tuning: keep limits low so tests stay fast. These values
// mirror the filter pins applied in tests/e2e-pw/bin/test-helper-apis.php
// (`woocommerce_bis_signup_rate_limit_max_per_ip` /
// `..._max_per_email`) — the spec and the pins must be kept in lockstep
// rather than depending on production defaults.
const IP_LIMIT = 5;
const EMAIL_LIMIT = 3;

type Product = { id: number; permalink: string };

// The helper REST endpoints (e2e-options/*, e2e-bis/*) expect requests sent via
// Playwright's native APIRequestContext (which auto-sets Content-Type: application/json
// and lets WP REST parse the body). The @woocommerce/e2e-utils-playwright axios client
// exposed as `restApi` doesn't do that, so it posts bodies the server can't decode and
// every call comes back 400. Route these helpers through the native request + setOption
// utils that the rest of the e2e suite uses.
async function enableBisSignups( req: APIRequest, baseURL: string ) {
	await setOption(
		req,
		baseURL,
		'woocommerce_customer_stock_notifications_allow_signups',
		'yes'
	);
}

async function disableBisSignups( req: APIRequest, baseURL: string ) {
	await deleteOption(
		req,
		baseURL,
		'woocommerce_customer_stock_notifications_allow_signups'
	);
}

async function resetRateLimiter( restApi: APIRequestContext ) {
	await restApi.post( 'e2e-bis/rate-limiter/reset', {} );
}

// `restApi` is the @woocommerce/e2e-utils-playwright axios-based client (not
// Playwright's native APIRequestContext, despite the type annotation). Axios
// responses expose the body as `.data`; there's no `.json()` method.
async function getNotificationCount(
	restApi: APIRequestContext,
	productId: number
): Promise< number > {
	const res = await restApi.get(
		`e2e-bis/notifications/count?product_id=${ productId }`
	);
	const body = ( res as unknown as { data: { count?: number } } ).data;
	return body?.count ?? 0;
}

async function createOutOfStockProduct(
	restApi: APIRequestContext
): Promise< Product > {
	// `restApi` is axios — its post/delete methods send the second arg directly
	// as the request body (no Playwright-style `{ data: ... }` wrapping).
	const response = await restApi.post( `${ WC_API_PATH }/products`, {
		name: `BIS rate-limit product ${ Date.now() }`,
		type: 'simple',
		regular_price: '10.00',
		manage_stock: true,
		stock_quantity: 0,
		stock_status: 'outofstock',
		status: 'publish',
	} );
	const data = (
		response as unknown as { data: { id: number; permalink: string } }
	 ).data;
	return { id: data.id, permalink: data.permalink };
}

async function deleteProduct( restApi: APIRequestContext, productId: number ) {
	await restApi.delete( `${ WC_API_PATH }/products/${ productId }`, {
		force: true,
	} );
}

async function submitBisSignup(
	anonRequest: APIRequestContext,
	product: Product,
	email: string
) {
	return anonRequest.post( product.permalink, {
		form: {
			wc_bis_register: '1',
			wc_bis_product_id: String( product.id ),
			wc_bis_email: email,
		},
		failOnStatusCode: false,
	} );
}

const test = baseTest.extend< {
	bisProduct: Product;
	anonRequest: APIRequestContext;
} >( {
	bisProduct: async ( { restApi }, use ) => {
		const product = await createOutOfStockProduct( restApi );
		await use( product );
		await deleteProduct( restApi, product.id );
	},
	anonRequest: async ( { playwright, baseURL }, use ) => {
		// Guest submissions need a request context without admin auth so
		// the form handler sees them as anonymous.
		const ctx = await playwright.request.newContext( { baseURL } );
		await use( ctx );
		await ctx.dispose();
	},
} );

test.describe( 'BIS sign-up rate limiting', () => {
	test.beforeAll( async ( { baseURL } ) => {
		await enableBisSignups( request, baseURL! );
	} );

	test.afterAll( async ( { restApi, baseURL } ) => {
		await resetRateLimiter( restApi );
		await disableBisSignups( request, baseURL! );
	} );

	test.beforeEach( async ( { restApi } ) => {
		await resetRateLimiter( restApi );
	} );

	test( 'rejects sign-ups that exceed the per-email limit', async ( {
		anonRequest,
		restApi,
		bisProduct,
	} ) => {
		const email = `rate-limit-${ Date.now() }@example.com`;

		// Baseline count.
		expect( await getNotificationCount( restApi, bisProduct.id ) ).toBe(
			0
		);

		// Submit up to the per-email limit — all should be accepted and
		// record at most one notification (subsequent identical submissions
		// are deduplicated by the signup service).
		for ( let i = 0; i < EMAIL_LIMIT; i++ ) {
			const res = await submitBisSignup( anonRequest, bisProduct, email );
			expect( res.status() ).toBeLessThan( 500 );
		}

		const countBefore = await getNotificationCount(
			restApi,
			bisProduct.id
		);
		expect( countBefore ).toBe( 1 );

		// One more should be rejected with the rate-limit notice and MUST
		// NOT create another BIS row.
		const blocked = await submitBisSignup( anonRequest, bisProduct, email );
		expect( blocked.status() ).toBeLessThan( 500 );
		expect( await blocked.text() ).toContain( 'signing up too fast' );

		const countAfter = await getNotificationCount( restApi, bisProduct.id );
		expect( countAfter ).toBe( countBefore );
	} );

	test( 'rejects sign-ups that exceed the per-IP limit across different emails', async ( {
		anonRequest,
		restApi,
		bisProduct,
	} ) => {
		// Each sign-up uses a unique email so the per-email counter never
		// trips — the per-IP counter is doing the work here.
		for ( let i = 0; i < IP_LIMIT; i++ ) {
			const res = await submitBisSignup(
				anonRequest,
				bisProduct,
				`ip-limit-${ Date.now() }-${ i }@example.com`
			);
			expect( res.status() ).toBeLessThan( 500 );
		}

		const countBefore = await getNotificationCount(
			restApi,
			bisProduct.id
		);
		expect( countBefore ).toBe( IP_LIMIT );

		const blocked = await submitBisSignup(
			anonRequest,
			bisProduct,
			`blocked-${ Date.now() }@example.com`
		);
		expect( await blocked.text() ).toContain( 'signing up too fast' );

		const countAfter = await getNotificationCount( restApi, bisProduct.id );
		expect( countAfter ).toBe( countBefore );
	} );

	test( 'resetting the rate limiter allows further sign-ups', async ( {
		anonRequest,
		restApi,
		bisProduct,
	} ) => {
		const email = `reset-${ Date.now() }@example.com`;

		for ( let i = 0; i < EMAIL_LIMIT; i++ ) {
			await submitBisSignup( anonRequest, bisProduct, email );
		}

		const blocked = await submitBisSignup( anonRequest, bisProduct, email );
		expect( await blocked.text() ).toContain( 'signing up too fast' );

		// Direct transient reset instead of sleeping through the TTL.
		await resetRateLimiter( restApi );

		// Reuse the same email so the assertion actually depends on the reset
		// clearing the counter — a fresh email would pass even without a reset
		// because its per-email counter would start at zero.
		const afterReset = await submitBisSignup(
			anonRequest,
			bisProduct,
			email
		);
		expect( await afterReset.text() ).not.toContain(
			'signing up too fast'
		);
	} );
} );
