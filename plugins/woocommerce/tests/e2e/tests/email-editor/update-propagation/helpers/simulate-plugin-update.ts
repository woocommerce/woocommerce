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
import {
	setTemplateHtmlOverride,
	stampBackfillComplete,
} from './test-helper-plugin';

const baseURL = playwrightConfig.use?.baseURL ?? '';

function apiClient() {
	return createClient( baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );
}

export async function triggerDetectionSweep(): Promise< {
	touched: number;
	classifications: Record< number, string >;
} > {
	const client = apiClient();
	const res = await client.post(
		`${ TEST_HELPER_API_BASE }/trigger-sweep`,
		{}
	);
	const body = res?.data ?? {};
	return {
		touched: Number( body.touched ?? 0 ),
		classifications: ( body.classifications ?? {} ) as Record<
			number,
			string
		>,
	};
}

export async function triggerBackfill(): Promise< {
	ran: boolean;
	stamped: number;
} > {
	const client = apiClient();
	const res = await client.post(
		`${ TEST_HELPER_API_BASE }/trigger-backfill`,
		{}
	);
	const body = res?.data ?? {};
	return {
		ran: Boolean( body.ran ),
		stamped: Number( body.stamped ?? 0 ),
	};
}

/**
 * Simulate a core template version bump by seeding `oldHtml` as the active
 * canonical-content override for `emailId`. While the override is active, any
 * canonical-hash computation against current core resolves to `oldHtml`.
 *
 * Callers typically:
 *   1. Call simulateCoreBump() with the OLD html.
 *   2. Seed merchant post(s) with `storedSourceHash: 'AUTO_CURRENT'` (resolves to sha1(oldHtml)).
 *   3. Call clearTemplateHtmlOverride() from `./test-helper-plugin` — the live canonical
 *      now reverts to real current-core HTML, so the next sweep sees merchant posts
 *      as "behind core".
 *   4. Call triggerDetectionSweep() to classify.
 *
 * This helper deliberately does NOT clear the override automatically — sequencing
 * differs per scenario.
 */
export async function simulateCoreBump(
	emailId: string,
	oldHtml: string
): Promise< void > {
	// The detector's run_sweep() short-circuits when the backfill-complete fence is
	// not 'yes'. Core-flows and round-trip tests assume the system is operating
	// post-backfill (the fence is normally stamped by woocommerce_newly_installed,
	// but fresh wp-env installs sometimes don't fire that action). Stamp it here so
	// downstream triggerDetectionSweep() calls actually run.
	await stampBackfillComplete();
	await setTemplateHtmlOverride( emailId, oldHtml );
}
