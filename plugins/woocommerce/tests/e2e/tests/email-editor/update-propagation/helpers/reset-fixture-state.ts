/**
 * Internal dependencies
 */
import {
	clearAllTemplateHtmlOverrides,
	clearOptedInOverride,
	clearTransactionalEmailsOverride,
	disableFakeThirdPartyEmail,
} from './test-helper-plugin';

/**
 * Call from afterEach in every spec. Clears every option the test helper
 * plugin reads, so the next test starts from a store the fixtures have not
 * touched.
 */
export async function resetFixtureState(): Promise< void > {
	await clearAllTemplateHtmlOverrides();
	await clearOptedInOverride();
	await clearTransactionalEmailsOverride();
	await disableFakeThirdPartyEmail();
}
