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
 * touched. Every option is cleared even when clearing another one fails, so
 * one failed request cannot leave the rest set for the tests that follow.
 */
export async function resetFixtureState(): Promise< void > {
	const errors: unknown[] = [];

	for ( const clear of [
		clearAllTemplateHtmlOverrides,
		clearOptedInOverride,
		clearTransactionalEmailsOverride,
		disableFakeThirdPartyEmail,
	] ) {
		try {
			await clear();
		} catch ( error ) {
			errors.push( error );
		}
	}

	if ( errors.length > 0 ) {
		throw new AggregateError( errors, 'Fixture state reset failed.' );
	}
}
