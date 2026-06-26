/**
 * External dependencies
 */
import { ApiClient, WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Set a payment gateway's enabled state and return its previous enabled state.
 *
 * The call is idempotent — it only issues a PUT when the current state differs
 * from the target — so passing the returned previous value back restores the
 * gateway without an extra read.
 *
 * Pair the two calls in beforeAll/afterAll to guard global state under parallel
 * runs (offline gateways are enabled by the shared site setup, so the restore
 * is normally a no-op):
 *
 *     const wasEnabled = await setGatewayEnabled( restApi, 'cod', true );
 *     // ...test...
 *     await setGatewayEnabled( restApi, 'cod', wasEnabled );
 *
 * @param restApi   The REST API client (the `restApi` fixture).
 * @param gatewayId The gateway id, e.g. `cod` or `bacs`.
 * @param enabled   The target enabled state.
 * @return The gateway's enabled state before this call.
 */
export async function setGatewayEnabled(
	restApi: ApiClient,
	gatewayId: string,
	enabled: boolean
): Promise< boolean > {
	const response = await restApi.get< { enabled: boolean } >(
		`${ WC_API_PATH }/payment_gateways/${ gatewayId }`
	);
	const wasEnabled = Boolean( response.data.enabled );

	if ( wasEnabled !== enabled ) {
		await restApi.put( `${ WC_API_PATH }/payment_gateways/${ gatewayId }`, {
			enabled,
		} );
	}

	return wasEnabled;
}
