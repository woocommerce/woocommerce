import { withRestApi } from './flows';

/**
 * Uses the WooCommerce API to get the environment context.
 */
export const getEnvironmentContext = async () => {
	try {
		const environment = await withRestApi.getSystemEnvironment();
		return {
			wpVersion: environment.wp_version,
			wcVersion: environment.version,
		}
	} catch ( error ) {
		// Prevent an error here causing tests to fail.
	}
}
