/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';
import { createClient } from '@woocommerce/e2e-utils-playwright';

/**
 * Update-propagation: backward compatibility.
 *
 * Covers the five BC scenarios for sites that had email posts before the
 * RSM-137 stamp meta was introduced: Case A (content matches core, no stamp),
 * Case B (timestamps equal, content behind core), Case C (customized post —
 * critical safety: content must never be overwritten), the no-mass-fire
 * Tracks guard (backfill must not fire _available events), and idempotency
 * (second backfill is a no-op).
 *
 * Reviewing in Playwright UI mode:
 *   1. Run `npx playwright test --project=e2e tests/email-editor/update-propagation --ui`
 *   2. Filter the tree by `backward-compat` and pick a test.
 *   3. All tests are REST-only except "BC no mass-fire" which attaches a Tracks
 *      spy via the page fixture (but does not navigate or interact with the UI).
 *      For all tests, the Actions panel in UI mode shows the REST call sequence.
 *   4. "Show browser" eye is not needed for any test in this file.
 */

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../../playwright.config';
import { admin } from '../../../test-data/data';
import { enableEmailEditor } from '../helpers/enable-email-editor-feature';
import {
	clearTemplateHtmlOverride,
	setTemplateHtmlOverride,
} from './helpers/test-helper-plugin';
import {
	seedWooEmailPost,
	getWooEmailMeta,
	getWooEmailPostContent,
} from './helpers/seed-woo-email';
import {
	triggerBackfill,
	triggerDetectionSweep,
} from './helpers/simulate-plugin-update';
import { attachTracksSpy } from './helpers/tracks-spy';
import { assertNoLeakedFixtureState } from './helpers/leaked-state-checks';
import {
	STATUS,
	META_KEYS,
	TRACKS_EVENTS,
	TEST_HELPER_API_BASE,
} from './helpers/classifications';

const BACKFILL_COMPLETE_OPTION =
	'woocommerce_email_template_sync_backfill_complete';

// One-shot Tracks guard added by RSM-145: fires _backfill_completed at most
// once per site. Must be cleared alongside BACKFILL_COMPLETE_OPTION so the
// Tracks spy can observe the event each time a BC test re-runs the backfill.
const BACKFILL_COMPLETED_TRACKED_OPTION =
	'wc_email_sync_backfill_completed_tracked';

const OLD_HTML = '<!-- wp:paragraph --><p>OLD</p><!-- /wp:paragraph -->';

async function resetBackfillFence( baseURL: string ): Promise< void > {
	const client = createClient( baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );
	await client.post( `${ TEST_HELPER_API_BASE }/delete-option`, {
		option_name: BACKFILL_COMPLETE_OPTION,
	} );
	await client.post( `${ TEST_HELPER_API_BASE }/delete-option`, {
		option_name: BACKFILL_COMPLETED_TRACKED_OPTION,
	} );
}

test.describe( 'Update propagation — backward compatibility', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async ( { baseURL } ) => {
		await enableEmailEditor( baseURL! );
	} );

	test.beforeEach( async ( { baseURL } ) => {
		// RSM-145 stamps this option on fresh installs via woocommerce_newly_installed
		// to suppress backfill on greenfield environments. BC scenarios need a clean
		// "pre-RSM-137" environment, so clear the option-fence before each test.
		await resetBackfillFence( baseURL! );
	} );

	test.afterEach( async () => {
		await assertNoLeakedFixtureState();
	} );

	/**
	 * Verifies that a pre-RSM-137 post whose content already matches the current
	 * canonical is stamped in_sync by the backfill and correctly participates in
	 * subsequent detection sweeps.
	 *
	 * UI mode walkthrough:
	 *   REST-only — no browser interaction. Actions panel shows: seedWooEmailPost
	 *   (stripStampMeta) → triggerBackfill → getWooEmailMeta assertions →
	 *   setTemplateHtmlOverride → triggerDetectionSweep → meta re-check.
	 *
	 *   "Show browser" eye: not needed.
	 */
	test( 'BC Case A — content matches current core, no stamp meta', async () => {
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			stripStampMeta: true,
		} );

		const backfill = await triggerBackfill();
		expect( backfill.stamped ).toBeGreaterThanOrEqual( 1 );

		const meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );
		expect( meta[ META_KEYS.SOURCE_HASH ]?.[ 0 ] ).toBeTruthy();

		// Simulate a core bump by setting the override to a different canonical.
		await setTemplateHtmlOverride( 'new_order', OLD_HTML );
		await triggerDetectionSweep();
		await clearTemplateHtmlOverride();

		// The sweep classifies the unmodified post as core_updated_uncustomized,
		// then the auto-applier (run inline by /trigger-sweep) silently applies
		// the new canonical and re-stamps the post as in_sync.
		const metaAfter = await getWooEmailMeta( postId );
		expect( metaAfter[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );
	} );

	/**
	 * Verifies that a pre-RSM-137 post with equal created/modified timestamps
	 * (indicating the content was never edited) is silently updated to the current
	 * canonical during backfill and stamped in_sync.
	 *
	 * UI mode walkthrough:
	 *   REST-only — no browser interaction. Actions panel shows: seedWooEmailPost
	 *   (stripStampMeta, equal timestamps, old content) → triggerBackfill →
	 *   meta assertion → REST GET to verify post_content was rewritten to canonical.
	 *
	 *   "Show browser" eye: not needed.
	 */
	test( 'BC Case B — timestamps equal and content behind core', async () => {
		const ts = '2024-01-01 12:00:00';

		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: OLD_HTML,
			postDateGmt: ts,
			postModifiedGmt: ts,
			stripStampMeta: true,
		} );

		const backfill = await triggerBackfill();
		expect( backfill.stamped ).toBeGreaterThanOrEqual( 1 );

		const meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );

		// Critical: the backfill rewrote post_content from OLD_HTML to current canonical.
		const content = await getWooEmailPostContent( postId );
		expect( content ).not.toContain( 'OLD' );
	} );

	/**
	 * Critical safety test: verifies that a pre-RSM-137 post whose content diverges
	 * from canonical (i.e., the merchant edited it) is stamped core_updated_customized
	 * by the backfill and that its content is NEVER overwritten — neither during
	 * backfill nor during subsequent detection sweeps.
	 *
	 * UI mode walkthrough:
	 *   REST-only — no browser interaction. Actions panel shows: seedWooEmailPost
	 *   (stripStampMeta, customized content) → triggerBackfill → meta + content
	 *   assertions → setTemplateHtmlOverride → triggerDetectionSweep → repeat
	 *   meta + content assertions confirming the merchant text is still intact.
	 *
	 *   "Show browser" eye: not needed.
	 */
	test( '@pr BC Case C — customized post content preserved (critical safety)', async () => {
		const customized =
			'<!-- wp:paragraph --><p>MERCHANT CUSTOM 1234</p><!-- /wp:paragraph -->';

		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: customized,
			postDateGmt: '2024-01-01 12:00:00',
			postModifiedGmt: '2024-06-15 09:00:00',
			stripStampMeta: true,
		} );

		const backfill = await triggerBackfill();
		expect( backfill.stamped ).toBeGreaterThanOrEqual( 1 );

		let meta = await getWooEmailMeta( postId );
		// Case C: content differs from canonical AND the post has been edited.
		// Backfill stamps core_updated_customized (does NOT rewrite post_content).
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe(
			STATUS.CORE_UPDATED_CUSTOMIZED
		);

		// CRITICAL: post content must be UNTOUCHED by backfill.
		const contentAfterBackfill = await getWooEmailPostContent( postId );
		expect( contentAfterBackfill ).toContain( 'MERCHANT CUSTOM 1234' );

		await setTemplateHtmlOverride( 'new_order', OLD_HTML );
		await triggerDetectionSweep();
		await clearTemplateHtmlOverride();

		meta = await getWooEmailMeta( postId );
		// Safety claim: classification is CUSTOMIZED, not UNCUSTOMIZED.
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe(
			STATUS.CORE_UPDATED_CUSTOMIZED
		);

		const contentAfterBump = await getWooEmailPostContent( postId );
		expect( contentAfterBump ).toContain( 'MERCHANT CUSTOM 1234' );
	} );

	/**
	 * Verifies that running backfill + detection sweep on a full set of 11 email
	 * types fires exactly one _backfill_completed Tracks event and zero
	 * _available events (guarding against a mass notification storm on upgrade).
	 *
	 * UI mode walkthrough:
	 *   The page fixture is used only to attach the Tracks spy — no navigation
	 *   or UI interaction occurs. The spy intercepts server-side Tracks events via
	 *   REST. Actions panel shows: seedWooEmailPost (×11) → triggerBackfill →
	 *   triggerDetectionSweep → spy.drain() → event count assertions.
	 *
	 *   "Show browser" eye: not needed.
	 */
	test( 'BC no mass-fire on first upgrade: zero _available, one _backfill_completed', async ( {
		page,
	} ) => {
		const spy = await attachTracksSpy( page );

		const emailIds = [
			'new_order',
			'cancelled_order',
			'failed_order',
			'customer_on_hold_order',
			'customer_processing_order',
			'customer_completed_order',
			'customer_refunded_order',
			'customer_invoice',
			'customer_note',
			'customer_reset_password',
			'customer_new_account',
		];
		for ( const id of emailIds ) {
			await seedWooEmailPost( {
				emailId: id,
				stripStampMeta: true,
			} );
		}

		const backfill = await triggerBackfill();
		expect( backfill.stamped ).toBeGreaterThanOrEqual( emailIds.length );

		await triggerDetectionSweep();

		// Drain all server + client events in one call. Each expectFired/expectNotFired
		// call invokes drain() independently, which reads and deletes the server log —
		// a second call would see an empty log. Assert both conditions against the same
		// snapshot to avoid missing events.
		const events = await spy.drain();
		const available = events.filter(
			( e ) => e.name === TRACKS_EVENTS.AVAILABLE
		);
		const backfillCompleted = events.filter(
			( e ) => e.name === TRACKS_EVENTS.BACKFILL_COMPLETED
		);
		expect(
			available.length,
			'No _available events should fire during backfill'
		).toBe( 0 );
		expect(
			backfillCompleted.length,
			'Exactly one _backfill_completed event should fire'
		).toBe( 1 );
	} );

	/**
	 * Verifies that running the backfill twice produces identical post meta,
	 * confirming the migration is safe to re-run (e.g., in case of interrupted
	 * deploys or duplicate cron fires).
	 *
	 * UI mode walkthrough:
	 *   REST-only — no browser interaction. Actions panel shows: seedWooEmailPost
	 *   (stripStampMeta) → triggerBackfill (first) → getWooEmailMeta snapshot →
	 *   triggerBackfill (second) → getWooEmailMeta equality assertion.
	 *
	 *   "Show browser" eye: not needed.
	 */
	test( 'BC migration is idempotent: second backfill is a no-op', async () => {
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			stripStampMeta: true,
		} );

		const first = await triggerBackfill();
		expect( first.stamped ).toBeGreaterThanOrEqual( 1 );
		const metaAfterFirst = await getWooEmailMeta( postId );

		await triggerBackfill();
		const metaAfterSecond = await getWooEmailMeta( postId );

		expect( metaAfterSecond ).toEqual( metaAfterFirst );
	} );
} );
