/**
 * External dependencies
 */
import { ApiClient, WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

const CALC_TAXES_PATH = `${ WC_API_PATH }/settings/general/woocommerce_calc_taxes`;

/**
 * Enable or disable tax calculation (`woocommerce_calc_taxes`) and return its
 * previous enabled state.
 *
 * The call is idempotent — it only issues a PUT when the current state differs
 * from the target — so passing the returned previous value back restores the
 * setting without an extra read.
 *
 * Tax calculation is off by default in the shared site setup, so most specs
 * enable it for their duration and restore the prior state afterwards:
 *
 *     const wasEnabled = await setTaxCalculationEnabled( restApi, true );
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
