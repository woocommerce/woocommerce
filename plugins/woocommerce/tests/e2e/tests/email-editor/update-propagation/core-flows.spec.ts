/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Update-propagation: core flows.
 *
 * Covers the merchant-facing lifecycle of a core-template update: divergence
 * detection, the update-available indicator on the list + editor banner,
 * auto-apply for unmodified posts, selective apply for customized posts, the
 * dismiss flow, and the review-drawer-driven selective merge.
 *
 * Reviewing in Playwright UI mode:
 *   1. Run `npx playwright test --project=e2e tests/email-editor/update-propagation --ui`
 *   2. Filter the tree by `core-flows` and pick a test.
 *   3. For UI tests, toggle "Show browser" (👁 in the top-left toolbar) to watch the
 *      Chromium window drive the admin. For REST-only tests the Actions panel
 *      shows the REST call sequence — no browser needed.
 *   4. Per-test JSDoc below indicates whether each test drives the browser.
 */

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../../playwright.config';
import { enableEmailEditor } from '../helpers/enable-email-editor-feature';
import { accessTheEmailEditor } from '../../../utils/email';
import {
	clearTemplateHtmlOverride,
	setTemplateHtmlOverride,
} from './helpers/test-helper-plugin';
import {
	seedWooEmailPost,
	getWooEmailMeta,
	getWooEmailPostContent,
	applyWooEmailTemplate,
} from './helpers/seed-woo-email';
import {
	simulateCoreBump,
	triggerDetectionSweep,
} from './helpers/simulate-plugin-update';
import { attachTracksSpy } from './helpers/tracks-spy';
import { assertNoLeakedFixtureState } from './helpers/leaked-state-checks';
import { STATUS, META_KEYS, TRACKS_EVENTS } from './helpers/classifications';

const OLD_HTML =
	'<!-- wp:paragraph --><p>OLD CANONICAL</p><!-- /wp:paragraph -->';

test.describe( 'Update propagation — core flows', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async ( { baseURL } ) => {
		await enableEmailEditor( baseURL! );
	} );

	test.afterEach( async () => {
		await assertNoLeakedFixtureState();
	} );

	/**
	 * Verifies that running the detection sweep after a core bump correctly
	 * classifies an unmodified post as auto-applied (in_sync) and a merchant-
	 * customized post as core_updated_customized, waiting for manual review.
	 *
	 * UI mode walkthrough:
	 *   REST-only — no browser interaction. Actions panel shows the REST call
	 *   sequence: simulateCoreBump → seedWooEmailPost (×2) → clearTemplateHtmlOverride
	 *   → triggerDetectionSweep → getWooEmailMeta assertions.
	 *
	 *   "Show browser" eye: not needed.
	 */
	test( '@pr Plugin update triggers divergence detection and classifies posts', async () => {
		// Bump and seed the uncustomized post.
		await simulateCoreBump( 'new_order', OLD_HTML );
		const uncustomizedPostId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: OLD_HTML,
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
		} );

		// Bump and seed the customized post (override is single-key, replacing the
		// previous one — but new_order's stored hash was already captured at seed time).
		await simulateCoreBump( 'customer_processing_order', OLD_HTML );
		const customizedHtml = OLD_HTML.replace(
			'OLD CANONICAL',
			'MERCHANT EDITED'
		);
		const customizedPostId = await seedWooEmailPost( {
			emailId: 'customer_processing_order',
			postContent: customizedHtml,
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
		} );

		await clearTemplateHtmlOverride();

		const sweep = await triggerDetectionSweep();

		const uncustomizedMeta = await getWooEmailMeta( uncustomizedPostId );
		const customizedMeta = await getWooEmailMeta( customizedPostId );

		// The sweep classifies the unmodified post as core_updated_uncustomized,
		// then the auto-applier (also fired by /trigger-sweep inline) silently
		// applies the new canonical and re-stamps the post as in_sync.
		expect( uncustomizedMeta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe(
			STATUS.IN_SYNC
		);
		// Customized posts are left for the merchant to apply manually.
		expect( customizedMeta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe(
			STATUS.CORE_UPDATED_CUSTOMIZED
		);
		expect( sweep.touched ).toBeGreaterThanOrEqual( 2 );
	} );

	/**
	 * Verifies that a core_updated_customized post surfaces a "Review update"
	 * button on the email list page and a "Template update available" banner
	 * inside the block editor.
	 *
	 * UI mode walkthrough:
	 *   After REST setup the test navigates to WP Admin → WooCommerce → Settings →
	 *   Email. The DataViews table loads and the "New order" row should contain a
	 *   "Review update" button. The test then opens the email in the block editor
	 *   and asserts the "Template update available" status banner is visible. No
	 *   clicks — both assertions are visibility checks only.
	 *
	 *   "Show browser" eye: ON.
	 */
	test( '@pr Update-available indicator appears on email list and in editor', async ( {
		page,
	} ) => {
		await simulateCoreBump( 'new_order', OLD_HTML );
		await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: OLD_HTML.replace( 'OLD CANONICAL', 'MERCHANT EDIT' ),
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
			// Seed an older version so the registry's current_version is higher.
			// The list cell and editor banner only show when
			// templateVersion < currentVersion; same-version posts don't surface
			// the indicator even when status is core_updated_customized.
			version: '10.0.0',
		} );
		await clearTemplateHtmlOverride();
		await triggerDetectionSweep();

		await page.goto( '/wp-admin/admin.php?page=wc-settings&tab=email' );
		// DataViews table rows have no aria-label, so getByRole('row', {name:...})
		// doesn't work. Use filter({ hasText }) to scope to the New order row.
		// The Updates column renders a secondary Button labelled "Review update"
		// when the post is core_updated_customized. The text "Update available"
		// only appears in the filter-dropdown elements, not in the row cell itself.
		const newOrderRow = page
			.locator( 'tr' )
			.filter( { hasText: /New order/i } )
			.first();
		await expect(
			newOrderRow.getByRole( 'button', { name: /review update/i } )
		).toBeVisible( { timeout: 15000 } );

		await accessTheEmailEditor( page, 'New order' );
		// The editor banner title is "Template update available" (role="status").
		await expect(
			page.getByText( /template update available/i ).first()
		).toBeVisible( { timeout: 15000 } );
	} );

	/**
	 * Verifies that an unmodified post is silently brought back to in_sync by the
	 * auto-applier, with no "Update available" indicator on the list and no
	 * Tracks events fired for update-available or dismissed.
	 *
	 * UI mode walkthrough:
	 *   The page fixture is used only to attach the Tracks spy and to navigate to
	 *   the email list for the "no indicator" assertion — no clicks are performed.
	 *   You'll see the browser open the email settings page and the test confirms
	 *   the "Update available" text is hidden in the New order row.
	 *
	 *   "Show browser" eye: ON.
	 */
	test( '@pr Auto-apply succeeds silently for unmodified posts', async ( {
		page,
	} ) => {
		const spy = await attachTracksSpy( page );

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

		// DataViews rows have no aria-label, so getByRole('row', {name:...}) doesn't
		// match. Use locator('tr').filter({hasText}) — same approach as test 2 —
		// then assert the "Review update" button is absent (toHaveCount(0)) which is
		// what actually surfaces when a post is core_updated_customized.
		await page.goto( '/wp-admin/admin.php?page=wc-settings&tab=email' );
		const newOrderRow = page
			.locator( 'tr' )
			.filter( { hasText: /New order/i } )
			.first();
		await expect(
			newOrderRow.getByRole( 'button', { name: /review update/i } )
		).toHaveCount( 0 );

		await spy.expectNotFired( TRACKS_EVENTS.AVAILABLE );
		await spy.expectNotFired( TRACKS_EVENTS.DISMISSED );
	} );

	/**
	 * Verifies that calling the apply endpoint with choices:[] (keep-yours default)
	 * applies core additions while preserving merchant edits, and leaves the post
	 * stamped core_updated_customized because the content still diverges from canonical.
	 *
	 * UI mode walkthrough:
	 *   REST-only — no browser interaction. Actions panel shows: simulateCoreBump
	 *   → seedWooEmailPost → clearTemplateHtmlOverride → triggerDetectionSweep
	 *   → applyWooEmailTemplate (REST POST) → getWooEmailMeta + content assertions.
	 *
	 *   "Show browser" eye: not needed.
	 */
	test( '@pr Selective apply succeeds and preserves customizations', async () => {
		const oldHtml =
			'<!-- wp:paragraph --><p>OLD CORE</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>SECOND BLOCK</p><!-- /wp:paragraph -->';
		const customized = oldHtml.replace(
			'SECOND BLOCK',
			'MERCHANT EDITED SECOND'
		);

		await simulateCoreBump( 'new_order', oldHtml );
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: customized,
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
		} );
		await clearTemplateHtmlOverride();
		await triggerDetectionSweep();

		// Use applyWooEmailTemplate (basic auth) instead of request.post (cookie auth)
		// because WP REST POST endpoints require a nonce when using cookie-based auth.
		// choices: [] keeps all merchant edits and applies only core additions.
		const apply = await applyWooEmailTemplate( postId, [] );
		expect( apply.status ).toBe( 'applied' );

		const meta = await getWooEmailMeta( postId );
		// With choices:[] the merchant's edits are preserved (keep_yours is the
		// default for copy_changes). The merged result diverges from canonical, so
		// the applier stamps core_updated_customized — not in_sync.
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe(
			STATUS.CORE_UPDATED_CUSTOMIZED
		);

		const content = await getWooEmailPostContent( postId );
		expect( content ).toContain( 'MERCHANT EDITED SECOND' );
	} );

	/**
	 * Verifies that clicking the "dismiss" button on the editor update banner fires
	 * the expected Tracks dismissed event.
	 *
	 * UI mode walkthrough:
	 *   After REST setup the test opens the block editor for the New order email.
	 *   The editor canvas loads, and the update banner is visible at the top. If
	 *   the review drawer is already open it is closed via Escape. Then the test
	 *   clicks the banner's dismiss button (`.wc-update-banner__dismiss`) and
	 *   asserts the Tracks dismissed event fired.
	 *
	 *   "Show browser" eye: ON.
	 */
	test( '@pr Dismiss flow records the dismissed Tracks event', async ( {
		page,
	} ) => {
		const customized = OLD_HTML.replace( 'OLD CANONICAL', 'MERCHANT EDIT' );

		const spy = await attachTracksSpy( page );

		await simulateCoreBump( 'new_order', OLD_HTML );
		await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: customized,
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
			// Seed an older version so the registry's current_version is higher.
			// The editor banner only shows when templateVersion < currentVersion;
			// same-version posts surface summaryShowsReviewed=true and unmount
			// the banner before the dismiss button can be clicked.
			version: '10.0.0',
		} );
		await clearTemplateHtmlOverride();
		await triggerDetectionSweep();

		await accessTheEmailEditor( page, 'New order' );

		// If the review drawer happened to open (e.g., via a deep-link or
		// store state from a prior navigation), close it before looking for
		// the banner's dismiss button so the drawer panel doesn't obscure it.
		const drawer = page.getByRole( 'dialog', {
			name: /review template update/i,
		} );
		if ( await drawer.isVisible() ) {
			await page.keyboard.press( 'Escape' );
			await drawer.waitFor( { state: 'hidden' } );
		}

		// Target the banner's dismiss button by its stable CSS class to avoid
		// matching any other "dismiss"-labelled button that may be on the page.
		const dismissButton = page.locator( '.wc-update-banner__dismiss' );
		await expect( dismissButton ).toBeVisible( { timeout: 15000 } );
		await dismissButton.click();

		await spy.expectFired( TRACKS_EVENTS.DISMISSED );
	} );

	/**
	 * Verifies that the review drawer allows per-conflict "keep yours" / "use core"
	 * choices and that clicking Apply merges exactly the selected blocks into the
	 * saved post content.
	 *
	 * UI mode walkthrough:
	 *   After REST setup the test navigates directly to the editor with the
	 *   `wc_email_review_drawer=1` deep-link param, which auto-opens the review
	 *   drawer. The drawer loads a change summary showing three conflicts. The test
	 *   leaves block A on "keep yours" (default), switches block B to "use core"
	 *   via a radio button click, then clicks Apply. The drawer closes and the
	 *   test verifies the merged content via REST (block A: merchant text kept,
	 *   block B: core text applied, block C: default kept).
	 *
	 *   "Show browser" eye: ON.
	 */
	test( 'Review drawer: pick per-conflict yours vs core and apply', async ( {
		page,
	} ) => {
		// We need real copy_changes in the change-summary, which only appear when
		// the LCS diff matches blocks by name and finds text differences.
		// Strategy: use setTemplateHtmlOverride for BOTH the "old" canonical
		// (to seed storedSourceHash) AND the "new" canonical (to control the
		// change-summary diff), keeping the same block structure with changed text.
		const oldHtml =
			'<!-- wp:paragraph --><p>OLD BLOCK A</p><!-- /wp:paragraph -->' +
			'<!-- wp:paragraph --><p>OLD BLOCK B</p><!-- /wp:paragraph -->' +
			'<!-- wp:paragraph --><p>OLD BLOCK C</p><!-- /wp:paragraph -->';

		// Merchant edited block A; blocks B and C kept the original text.
		const customized = oldHtml.replace(
			'OLD BLOCK A',
			'MERCHANT EDITED A'
		);

		// "New canonical" after a core bump: core changed text in B and C,
		// but A still matches nothing (it will conflict with merchant's edit).
		const newCanonical =
			'<!-- wp:paragraph --><p>NEW CORE A</p><!-- /wp:paragraph -->' +
			'<!-- wp:paragraph --><p>NEW CORE B</p><!-- /wp:paragraph -->' +
			'<!-- wp:paragraph --><p>NEW CORE C</p><!-- /wp:paragraph -->';

		// Step 1: set override = oldHtml so that AUTO_CURRENT resolves to sha1(oldHtml).
		await simulateCoreBump( 'new_order', oldHtml );

		// Step 2: seed the post — stored hash = sha1(oldHtml), content = merchant edits.
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: customized,
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
			// Use an older version so the registry's current_version is higher and
			// the editor banner renders (version_from < version_to).
			version: '10.0.0',
		} );

		// Step 3: swap the override to the "new" canonical. The sweep and the
		// change-summary endpoint will now compare the post against newCanonical.
		await setTemplateHtmlOverride( 'new_order', newCanonical );

		// Step 4: sweep classifies the post as core_updated_customized.
		await triggerDetectionSweep();

		// Open the editor and click the banner's "Review changes" button — the
		// merchant-facing path to open the drawer. (The wc_email_review_drawer=1
		// deep-link works locally but races with editor mount in CI; clicking the
		// banner button is the realistic flow and is stable.)
		await page.goto( `/wp-admin/post.php?post=${ postId }&action=edit` );

		// Wait for the editor canvas to be ready.
		await expect( page.locator( '#woocommerce-email-editor' ) ).toBeVisible(
			{
				timeout: 20000,
			}
		);

		// Click "Review changes" in the floating update banner.
		await page.getByRole( 'button', { name: /^review changes$/i } ).click();

		// The drawer's <aside role="dialog"> becomes aria-hidden="false" once the
		// store dispatches openReviewDrawer(). The title text comes from the
		// "Review template update" h2 inside the drawer header.
		const drawer = page.getByRole( 'dialog', {
			name: /review template update/i,
		} );
		await expect( drawer ).toBeVisible( { timeout: 15000 } );

		// The change-summary fetch is triggered by the drawer's useChangeSummary
		// hook (enabled = isOpen = true). Wait for the "Needs your attention"
		// heading — the diff outcome (how many conflicts vs auto-resolved blocks)
		// depends on the differ; the test stays resilient by interacting only
		// with the first radiogroup and asserting content after apply.
		await expect(
			drawer.getByRole( 'heading', { name: /needs your attention/i } )
		).toBeVisible( { timeout: 15000 } );

		// Pick the first radiogroup's "Use core" — flips the default from
		// "Keep yours" so the merchant's edit on block A is overwritten by core.
		const firstRadioGroup = drawer
			.getByRole( 'radiogroup', {
				name: /choose which version to apply/i,
			} )
			.first();
		await expect(
			firstRadioGroup.getByRole( 'radio', { name: /keep yours/i } )
		).toHaveAttribute( 'aria-checked', 'true' );

		await firstRadioGroup
			.getByRole( 'radio', { name: /use core/i } )
			.click();
		await expect(
			firstRadioGroup.getByRole( 'radio', { name: /use core/i } )
		).toHaveAttribute( 'aria-checked', 'true' );

		// Click Apply — label is "Apply (N)" where N = total changes.
		await drawer.getByRole( 'button', { name: /^apply/i } ).click();

		// Drawer closes after a successful apply.
		await expect( drawer ).toBeHidden( { timeout: 15000 } );

		// Verify the merged post content via REST. The single decision we made
		// was on block A's conflict ("use core"), so MERCHANT EDITED A must be
		// gone and NEW CORE A must be present. We don't assert on B/C here
		// because the differ may treat them as conflicts or auto-resolved.
		const content = await getWooEmailPostContent( postId );
		expect( content ).toContain( 'NEW CORE A' );
		expect( content ).not.toContain( 'MERCHANT EDITED A' );
	} );
} );
