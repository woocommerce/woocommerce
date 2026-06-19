/**
 * External dependencies
 */
import { ApiClient, WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

const CALC_TAXES_PATH = `${ WC_API_PATH }/settings/general/woocommerce_calc_taxes`;

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
 * Enable or disable tax calculation (`woocommerce_calc_taxes`) and return its
 * previous enabled state.
 *
 * The call is idempotent — it only issues a PUT when the current state differs
 * from the target — so passing the returned previous value back restores the
 * setting without an extra read.
 *
 * Tax calculation is on by default in the shared site setup. Specs that need
 * to temporarily disable it (e.g. serial checkout/settings-tax specs) can
 * capture and restore the prior state:
 *
 *     const wasEnabled = await setTaxCalculationEnabled( restApi, false );
 *     // ...test...
 *     await setTaxCalculationEnabled( restApi, wasEnabled );
 *
 * @param restApi The REST API client (the `restApi` fixture).
 * @param enabled The target tax-calculation state.
 * @return The setting's enabled state before this call.
 */
export async function setTaxCalculationEnabled(
	restApi: ApiClient,
	enabled: boolean
): Promise< boolean > {
	const response = await restApi.get< { value: string } >( CALC_TAXES_PATH );
	const wasEnabled = response.data.value === 'yes';

	if ( wasEnabled !== enabled ) {
		await restApi.put( CALC_TAXES_PATH, {
			value: enabled ? 'yes' : 'no',
		} );
	}

	return wasEnabled;
}
