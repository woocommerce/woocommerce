/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Update-propagation: round-trip and idempotency.
 *
 * Covers four state-machine round-trips: (1) auto-apply returns an unmodified
 * post to in_sync, (2) selective apply with keep-yours keeps the post in the
 * customized state, (3) reset returns a customized post directly to in_sync,
 * and (4) the detection sweep is idempotent — a second run classifies the same
 * post identically and writes no new meta.
 *
 * Reviewing in Playwright UI mode:
 *   1. Run `npx playwright test --project=e2e tests/email-editor/update-propagation --ui`
 *   2. Filter the tree by `round-trip` and pick a test.
 *   3. All four tests are REST-only — no browser window is driven. The Actions
 *      panel in UI mode shows the full REST call sequence for each test.
 *   4. "Show browser" eye is not needed for any test in this file.
 */

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../../playwright.config';
import { enableEmailEditor } from '../helpers/enable-email-editor-feature';
import { clearTemplateHtmlOverride } from './helpers/test-helper-plugin';
import {
	seedWooEmailPost,
	getWooEmailMeta,
	applyWooEmailTemplate,
	resetWooEmailTemplate,
} from './helpers/seed-woo-email';
import {
	simulateCoreBump,
	triggerDetectionSweep,
} from './helpers/simulate-plugin-update';
import { assertNoLeakedFixtureState } from './helpers/leaked-state-checks';
import { STATUS, META_KEYS } from './helpers/classifications';

const OLD_HTML =
	'<!-- wp:paragraph --><p>OLD CANONICAL</p><!-- /wp:paragraph -->';

test.describe( 'Update propagation — round-trip and idempotency', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async ( { baseURL } ) => {
		await enableEmailEditor( baseURL! );
	} );

	test.afterEach( async () => {
		await assertNoLeakedFixtureState();
	} );

	/**
	 * Verifies the full auto-apply round-trip: after a core bump the detection
	 * sweep detects the divergence and the inline auto-applier immediately
	 * re-stamps an unmodified post as in_sync.
	 *
	 * UI mode walkthrough:
	 *   REST-only — no browser interaction. Actions panel shows: simulateCoreBump
	 *   → seedWooEmailPost → clearTemplateHtmlOverride → triggerDetectionSweep
	 *   → getWooEmailMeta assertion (STATUS.IN_SYNC).
	 *
	 *   "Show browser" eye: not needed.
	 */
	test( 'Auto-apply round-trip: uncustomized post returns to in_sync', async () => {
		await simulateCoreBump( 'new_order', OLD_HTML );
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: OLD_HTML,
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
		} );
		await clearTemplateHtmlOverride();

		await triggerDetectionSweep();

		const meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );
	} );

	/**
	 * Verifies that when a merchant applies a core update with choices:[] (keep-yours
	 * default for all conflicts), the post's diverged block is preserved and the
	 * status remains core_updated_customized rather than flipping to in_sync.
	 *
	 * UI mode walkthrough:
	 *   REST-only — no browser interaction. Actions panel shows: simulateCoreBump
	 *   → seedWooEmailPost (customized block B) → clearTemplateHtmlOverride →
	 *   triggerDetectionSweep → meta assertion (CUSTOMIZED) → applyWooEmailTemplate
	 *   (choices:[]) → meta re-assertion (still CUSTOMIZED).
	 *
	 *   "Show browser" eye: not needed.
	 */
	test( 'Selective apply round-trip: edit, bump, apply with keep-yours → stays customized', async () => {
		const oldHtml =
			'<!-- wp:paragraph --><p>OLD A</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>OLD B</p><!-- /wp:paragraph -->';

		await simulateCoreBump( 'new_order', oldHtml );
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: oldHtml.replace( 'OLD B', 'MERCHANT B' ),
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
		} );
		await clearTemplateHtmlOverride();
		await triggerDetectionSweep();

		let meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe(
			STATUS.CORE_UPDATED_CUSTOMIZED
		);

		// Use applyWooEmailTemplate (basic auth) instead of request.post (cookie auth)
		// because WP REST POST endpoints require a nonce when using cookie-based auth.
		// choices: [] keeps all merchant edits (keep_yours default), so the merged
		// result diverges from canonical and the applier stamps core_updated_customized.
		const apply = await applyWooEmailTemplate( postId, [] );
		expect( apply.status ).toBe( 'applied' );

		meta = await getWooEmailMeta( postId );
		// With choices:[] the merchant's diverged block is preserved, so the post
		// stays core_updated_customized rather than reaching in_sync.
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe(
			STATUS.CORE_UPDATED_CUSTOMIZED
		);
	} );

	/**
	 * Verifies that the reset endpoint replaces a customized post's content with
	 * the current canonical and stamps it in_sync in a single REST call.
	 *
	 * UI mode walkthrough:
	 *   REST-only — no browser interaction. Actions panel shows: seedWooEmailPost
	 *   (customized content) → resetWooEmailTemplate (REST POST) → response status
	 *   assertion → getWooEmailMeta assertion (STATUS.IN_SYNC).
	 *
	 *   "Show browser" eye: not needed.
	 */
	test( 'Reset round-trip: customized → reset → in_sync', async () => {
		const customized =
			'<!-- wp:paragraph --><p>MERCHANT CUSTOM</p><!-- /wp:paragraph -->';
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: customized,
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
		} );

		// Use resetWooEmailTemplate (basic auth) instead of request.post (cookie auth)
		// because WP REST POST endpoints require a nonce when using cookie-based auth.
		// The reset endpoint returns the post-reset sync status directly (not "applied").
		const reset = await resetWooEmailTemplate( postId );
		expect( reset.status ).toBe( STATUS.IN_SYNC );

		const meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );
	} );

	/**
	 * Verifies that running the detection sweep twice in a row produces the same
	 * classification and identical meta for an already-classified customized post,
	 * confirming the sweep does not mutate already-correct state.
	 *
	 * UI mode walkthrough:
	 *   REST-only — no browser interaction. Actions panel shows: simulateCoreBump
	 *   → seedWooEmailPost (merchant edit) → clearTemplateHtmlOverride →
	 *   triggerDetectionSweep (first) → getWooEmailMeta snapshot →
	 *   triggerDetectionSweep (second, sweep2.classifications assertion) →
	 *   getWooEmailMeta equality assertion.
	 *
	 *   "Show browser" eye: not needed.
	 */
	test( 'Detection sweep is idempotent: second run touches zero posts', async () => {
		await simulateCoreBump( 'new_order', OLD_HTML );
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: OLD_HTML.replace( 'OLD CANONICAL', 'MERCHANT EDIT' ),
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
		} );
		await clearTemplateHtmlOverride();

		await triggerDetectionSweep();
		const metaBefore = await getWooEmailMeta( postId );

		const sweep2 = await triggerDetectionSweep();
		const metaAfter = await getWooEmailMeta( postId );

		expect( sweep2.classifications[ postId ] ).toBe(
			metaBefore[ META_KEYS.STATUS ]?.[ 0 ]
		);
		expect( metaAfter ).toEqual( metaBefore );
	} );
} );
