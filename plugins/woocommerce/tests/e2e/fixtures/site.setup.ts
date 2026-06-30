/**
 * External dependencies
 */
import { request } from '@playwright/test';
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test as setup } from './fixtures';
import { setComingSoon } from '../utils/coming-soon';
import { skipOnboardingWizard } from '../utils/onboarding';
import { setOption } from '../utils/options';

setup( 'setup site', async ( { baseURL, restApi } ) => {
	await setup.step( 'configure HPOS', async () => {
		const { DISABLE_HPOS } = process.env;
		console.log( `DISABLE_HPOS: ${ DISABLE_HPOS }` );

		const hposSettingRetries = 5;
		const value = DISABLE_HPOS === '1' ? 'no' : 'yes';
		let hposConfigured = false;

		for ( let i = 0; i < hposSettingRetries; i++ ) {
			try {
				console.log(
					`Trying to switch ${
						value === 'yes' ? 'on' : 'off'
					} HPOS...`
				);
				const response = await restApi.post(
					`${ WC_API_PATH }/settings/advanced/woocommerce_custom_orders_table_enabled`,
					{ value }
				);
				if ( response.data.value === value ) {
					console.log(
						`HPOS Switched ${
							value === 'yes' ? 'on' : 'off'
						} successfully`
					);
					hposConfigured = true;
					break;
				}
			} catch ( e ) {
				console.log(
					`HPOS setup failed. Retrying... ${ i }/${ hposSettingRetries }`
				);
				console.log( e );
			}
		}

		if ( ! hposConfigured ) {
			console.error(
				'Cannot proceed e2e test, HPOS configuration failed. Please check if the correct DISABLE_HPOS value was used and the test site has been setup correctly.'
			);
			process.exit( 1 );
		}

		const response = await restApi.get(
			`${ WC_API_PATH }/settings/advanced/woocommerce_custom_orders_table_enabled`
		);
		const dataValue = response.data.value;
		const enabledOption = response.data.options[ dataValue ];
		console.log(
			`HPOS configuration (woocommerce_custom_orders_table_enabled): ${ dataValue } - ${ enabledOption }`
		);
	} );

	await setup.step( 'enable product object caching', async () => {
		// Always run e2e with product object caching on (the new-install default), so any
		// flow that bypasses the product CRUD/cache interfaces surfaces as a failure. Set
		// explicitly rather than relying on the new-install default, so the state is
		// deterministic regardless of how the test site's DB was provisioned.
		await setOption(
			request,
			baseURL,
			'woocommerce_feature_product_instance_caching_enabled',
			'yes'
		);
	} );

	await setup.step( 'disable coming soon', async () => {
		await setComingSoon( { baseURL, enabled: 'no' } );
	} );

	await setup.step( 'disable onboarding wizard', async () => {
		await skipOnboardingWizard();
	} );

	await setup.step( 'determine if multisite', async () => {
		const response = await restApi.get( `${ WC_API_PATH }/system_status` );
		const { environment } = response.data;

		if ( environment.wp_multisite === false ) {
			delete process.env.IS_MULTISITE;
		} else {
			process.env.IS_MULTISITE = environment.wp_multisite;
			console.log( `IS_MULTISITE: ${ process.env.IS_MULTISITE }` );
		}
	} );

	await setup.step( 'general settings', async () => {
		await restApi.post( `${ WC_API_PATH }/settings/general/batch`, {
			update: [
				// Enable tax calculation globally so tax-dependent specs don't each
				// flip this global switch on/off mid-run. The shared baseline stays
				// tax-free because no standard-rate tax rate exists (the next step
				// clears any stray rates), so `core-parallel` specs asserting untaxed
				// cart/refund totals are unaffected. A spec that needs taxes scopes
				// its rate to a dedicated tax class and assigns only its own products
				// to that class (see `cart.spec.ts`), so other workers never match it.
				{ id: 'woocommerce_calc_taxes', value: 'yes' },
				{ id: 'woocommerce_allowed_countries', value: 'all' },
				{ id: 'woocommerce_currency', value: 'USD' },
				{ id: 'woocommerce_price_thousand_sep', value: ',' },
				{ id: 'woocommerce_price_decimal_sep', value: '.' },
				{ id: 'woocommerce_price_num_decimals', value: '2' },
				{ id: 'woocommerce_store_address', value: 'addr 1' },
				{ id: 'woocommerce_store_city', value: 'San Francisco' },
				{ id: 'woocommerce_default_country', value: 'US:CA' },
				{ id: 'woocommerce_store_postcode', value: '94107' },
			],
		} );
	} );

	await setup.step( 'tax display settings', async () => {
		// Pin the tax display mode deterministically. With taxes enabled globally,
		// display mode now affects how class-scoped tax specs (e.g. `cart.spec.ts`)
		// see prices, and a stray `incl` left by an interrupted blocks/settings-tax
		// run would otherwise break their ex-tax line-price assertions. `excl` is
		// the WooCommerce default: line prices shown ex-tax, totals tax-inclusive.
		await restApi.post( `${ WC_API_PATH }/settings/tax/batch`, {
			update: [
				{ id: 'woocommerce_prices_include_tax', value: 'no' },
				{ id: 'woocommerce_tax_display_shop', value: 'excl' },
				{ id: 'woocommerce_tax_display_cart', value: 'excl' },
			],
		} );
	} );

	await setup.step( 'clear tax rates', async () => {
		// With tax calculation enabled globally, the parallel baseline must have
		// zero tax rates so untaxed-total assertions stay deterministic. Delete any
		// rates left behind by an interrupted serial tax spec; specs that need a
		// rate create (and clean up) a class-scoped one of their own.
		let page = 1;
		let allRates: { id: number }[] = [];
		while ( true ) {
			const { data: chunk } = await restApi.get< { id: number }[] >(
				`${ WC_API_PATH }/taxes?per_page=100&page=${ page }`
			);
			allRates = allRates.concat( chunk );
			if ( chunk.length < 100 ) break;
			page++;
		}

		// The batch endpoint enforces a 100-item limit across all operations.
		for ( let i = 0; i < allRates.length; i += 100 ) {
			await restApi.post( `${ WC_API_PATH }/taxes/batch`, {
				delete: allRates.slice( i, i + 100 ).map( ( rate ) => rate.id ),
			} );
		}
	} );

	await setup.step( 'clear orphaned tax classes', async () => {
		// Specs that create a dedicated tax class (e.g. `cart.spec.ts`) clean up
		// after themselves, but interrupted runs can leave orphaned classes. Remove
		// any non-built-in class so the environment doesn't drift over time.
		const BUILT_IN_SLUGS = new Set( [
			'standard',
			'reduced-rate',
			'zero-rate',
		] );
		const { data: classes } = await restApi.get< { slug: string }[] >(
			`${ WC_API_PATH }/taxes/classes`
		);

		await Promise.all(
			classes
				.filter( ( cls ) => ! BUILT_IN_SLUGS.has( cls.slug ) )
				.map( ( cls ) =>
					restApi.delete(
						`${ WC_API_PATH }/taxes/classes/${ cls.slug }`,
						{ force: true }
					)
				)
		);
	} );

	await setup.step( 'enable offline payment gateways', async () => {
		// Enable COD and BACS once for the whole shared site so specs that need an
		// offline gateway at checkout don't each toggle them on/off. Toggling a
		// gateway off in a spec's afterAll would disable it for every other worker
		// mid-run, so the gateways are owned by the baseline instead. Set explicitly
		// rather than relying on defaults (both ship disabled on a fresh install).
		await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
			enabled: true,
		} );
		await restApi.put( `${ WC_API_PATH }/payment_gateways/bacs`, {
			enabled: true,
		} );
	} );

	await setup.step( 'enable baseline free shipping', async () => {
		// Provide one deterministic, always-available shipping method for every
		// cart so specs that complete checkout don't each create/delete their own
		// shipping zone. Concurrent zone churn makes shipping availability
		// non-deterministic, which destabilises the block/classic checkout address
		// rendering (a shipping method appearing or disappearing flips whether a
		// separate billing group / "Use same address for billing" checkbox shows).
		// Attach free shipping to zone 0 ("Locations not covered by your other
		// zones"), the catch-all fallback, so any cart not matched by a more
		// specific zone is offered free shipping. Free (cost 0) leaves order totals
		// unchanged. Idempotent: only add the method if it isn't already there.
		const { data: methods } = await restApi.get< { method_id: string }[] >(
			`${ WC_API_PATH }/shipping/zones/0/methods`
		);

		if ( ! methods.some( ( m ) => m.method_id === 'free_shipping' ) ) {
			await restApi.post( `${ WC_API_PATH }/shipping/zones/0/methods`, {
				method_id: 'free_shipping',
			} );
		}
	} );
} );
