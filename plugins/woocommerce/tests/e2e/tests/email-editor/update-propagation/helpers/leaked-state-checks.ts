/**
 * External dependencies
 */
import { expect } from '@playwright/test';
import { createClient } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { admin } from '../../../../test-data/data';
import playwrightConfig from '../../../../playwright.config';
import { TEST_HELPER_API_BASE } from './classifications';
import {
	clearAllTemplateHtmlOverrides,
	clearOptedInOverride,
	clearTransactionalEmailsOverride,
	disableTracksLog,
	disableFakeThirdPartyEmail,
} from './test-helper-plugin';

const baseURL = playwrightConfig.use?.baseURL ?? '';

function apiClient() {
	return createClient( baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );
}

/**
 * Call from afterEach in every spec. Snapshots fixture state, force-cleans
 * everything, and asserts the snapshot was clean. A test that triggers leaked
 * cleanup fails the run even if its body assertions passed — the next test
 * still starts from a clean slate because cleanup ran before the assertion.
 */
export async function assertNoLeakedFixtureState(): Promise< void > {
	const client = apiClient();
	const tracksRes = await client.get( `${ TEST_HELPER_API_BASE }/tracks` );
	const trackCount = ( ( tracksRes?.data?.events ?? [] ) as unknown[] )
		.length;

	await clearAllTemplateHtmlOverrides();
	await clearOptedInOverride();
	await clearTransactionalEmailsOverride();
	await disableTracksLog();
	await disableFakeThirdPartyEmail();
	await client.delete( `${ TEST_HELPER_API_BASE }/tracks`, {} );

	expect( trackCount, 'Tracks log not drained at end of test' ).toBe( 0 );
}
