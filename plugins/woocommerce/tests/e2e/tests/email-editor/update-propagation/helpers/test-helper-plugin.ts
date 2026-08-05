/**
 * External dependencies
 */
import { createClient } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { admin } from '../../../../test-data/data';
import playwrightConfig from '../../../../playwright.config';
import { TEST_HELPER_API_BASE } from './classifications';

const baseURL = playwrightConfig.use?.baseURL ?? '';

const OPTIONS = {
	TEMPLATE_HTML_OVERRIDE: 'wc_test_template_html_override',
	OPTED_IN_OVERRIDE: 'wc_test_opted_in_emails_override',
	TRANSACTIONAL_OVERRIDE: 'wc_test_transactional_emails_override',
	TRACKS_ENABLED: 'wc_test_tracks_enabled',
	FAKE_THIRD_PARTY_EMAIL_ENABLED: 'wc_test_fake_third_party_email_enabled',
} as const;

function apiClient() {
	return createClient( baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );
}

async function setOption( name: string, value: unknown ): Promise< void > {
	const client = apiClient();
	await client.post( `${ TEST_HELPER_API_BASE }/set-option`, {
		option_name: name,
		option_value: value,
	} );
}

async function deleteOption( name: string ): Promise< void > {
	const client = apiClient();
	await client.post( `${ TEST_HELPER_API_BASE }/delete-option`, {
		option_name: name,
	} );
}

export async function setTemplateHtmlOverride(
	emailId: string,
	html: string
): Promise< void > {
	await setOption( OPTIONS.TEMPLATE_HTML_OVERRIDE, { [ emailId ]: html } );
}

export async function clearTemplateHtmlOverride(): Promise< void > {
	await deleteOption( OPTIONS.TEMPLATE_HTML_OVERRIDE );
}

export async function clearAllTemplateHtmlOverrides(): Promise< void > {
	await clearTemplateHtmlOverride();
}

export async function setOptedInOverride(
	overrides: Record< string, { version: string } >
): Promise< void > {
	await setOption( OPTIONS.OPTED_IN_OVERRIDE, overrides );
}

export async function clearOptedInOverride(): Promise< void > {
	await deleteOption( OPTIONS.OPTED_IN_OVERRIDE );
}

export async function setTransactionalEmailsOverride(
	emailIds: string[]
): Promise< void > {
	await setOption( OPTIONS.TRANSACTIONAL_OVERRIDE, emailIds );
}

export async function clearTransactionalEmailsOverride(): Promise< void > {
	await deleteOption( OPTIONS.TRANSACTIONAL_OVERRIDE );
}

export async function enableTracksLog(): Promise< void > {
	await setOption( OPTIONS.TRACKS_ENABLED, 'yes' );
}

export async function disableTracksLog(): Promise< void > {
	await deleteOption( OPTIONS.TRACKS_ENABLED );
}

export async function enableFakeThirdPartyEmail(): Promise< void > {
	await setOption( OPTIONS.FAKE_THIRD_PARTY_EMAIL_ENABLED, 'yes' );
}

export async function disableFakeThirdPartyEmail(): Promise< void > {
	await deleteOption( OPTIONS.FAKE_THIRD_PARTY_EMAIL_ENABLED );
}

export async function stampBackfillComplete(): Promise< void > {
	await setOption(
		'woocommerce_email_template_sync_backfill_complete',
		'yes'
	);
}
