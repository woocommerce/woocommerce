/**
 * Internal dependencies
 */
import type { RequestUtils } from './index';

export async function setFeatureFlag(
	this: RequestUtils,
	flag: string,
	value: boolean
) {
	return this.rest( {
		method: 'POST',
		path: '/e2e-feature-flags/update',
		data: { [ flag ]: value },
		failOnStatusCode: true,
	} );
}

export async function resetFeatureFlag( this: RequestUtils ) {
	return this.rest( {
		method: 'GET',
		path: '/e2e-feature-flags/reset',
		failOnStatusCode: true,
	} );
}
