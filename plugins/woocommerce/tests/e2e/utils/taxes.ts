/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';
import { ApiClient, WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

const CALC_TAXES_PATH = `${ WC_API_PATH }/settings/general/woocommerce_calc_taxes`;

/**
 * A created tax rate plus the slug of the dedicated tax class it is scoped to.
 */
export type ScopedTaxRate = {
	id: number;
	rate: string;
	taxClassSlug: string;
};

/**
 * Assert that `woocommerce_calc_taxes` is `yes` and throw if it is not.
 *
 * Call this at the start of any `beforeAll` / fixture that depends on the
 * taxes-ON baseline set by `site.setup.ts`. An early, descriptive failure is
 * easier to diagnose than a silent wrong-total assertion deep in the test.
 *
 * @param restApi The REST API client (the `restApi` fixture).
 */
export async function assertTaxCalculationEnabled(
	restApi: ApiClient
): Promise< void > {
	const response = await restApi.get< { value: string } >( CALC_TAXES_PATH );
	if ( response.data.value !== 'yes' ) {
		throw new Error(
			`Expected woocommerce_calc_taxes=yes (site.setup baseline) but got "${ response.data.value }". ` +
				'A serial spec or afterAll hook may have turned taxes off — check for afterAll leaks or cross-project pollution.'
		);
	}
}

/**
 * Provide a tax rate scoped to its own dedicated tax class, then clean both up.
 *
 * Tax calculation is enabled globally in site setup with no standard rate, so
 * the shared baseline is tax-free. This creates a uniquely-named tax class and a
 * 25% rate scoped to it; only products assigned to that class are taxed, so
 * concurrent workers are never affected. The class and rate are always removed
 * afterwards, even if the test body throws.
 *
 * Use it from a Playwright fixture, passing the fixture's own `use` callback:
 *
 *     tax: async ( { restApi }, use ) => {
 *         await withScopedTaxClass( restApi, 'Cart Spec', use );
 *     },
 *
 * @param restApi The REST API client (the `restApi` fixture).
 * @param label   Human-readable prefix for the tax class and rate names.
 * @param use     Callback receiving the created rate and its tax class slug.
 */
export async function withScopedTaxClass(
	restApi: ApiClient,
	label: string,
	use: ( tax: ScopedTaxRate ) => Promise< void >
): Promise< void > {
	await assertTaxCalculationEnabled( restApi );

	const className = `${ label } ${ faker.string.alphanumeric( 8 ) }`;
	const { data: taxClass } = await restApi.post< { slug: string } >(
		`${ WC_API_PATH }/taxes/classes`,
		{ name: className }
	);

	let rate: { id: number; rate: string } | undefined;
	try {
		( { data: rate } = await restApi.post< { id: number; rate: string } >(
			`${ WC_API_PATH }/taxes`,
			{
				country: 'US',
				state: '*',
				cities: '*',
				postcodes: '*',
				rate: '25',
				name: `${ label } Tax`,
				shipping: false,
				class: taxClass.slug,
			}
		) );
		await use( { ...rate, taxClassSlug: taxClass.slug } );
	} finally {
		if ( rate ) {
			await restApi
				.delete( `${ WC_API_PATH }/taxes/${ rate.id }`, {
					force: true,
				} )
				.catch( console.error );
		}
		await restApi
			.delete( `${ WC_API_PATH }/taxes/classes/${ taxClass.slug }`, {
				force: true,
			} )
			.catch( console.error );
	}
}
