/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../../playwright.config';
import { enableEmailEditor } from '../helpers/enable-email-editor-feature';
import { accessTheEmailEditor } from '../../../utils/email';
import { clearTemplateHtmlOverride } from './helpers/test-helper-plugin';
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
import { setTemplateHtmlOverride } from './helpers/test-helper-plugin';

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

		await page.goto( '/wp-admin/admin.php?page=wc-settings&tab=email' );
		const newOrderRow = page.getByRole( 'row', { name: /New order/i } );
		await expect(
			newOrderRow.getByText( /update available/i )
		).toBeHidden();

		await spy.expectNotFired( TRACKS_EVENTS.AVAILABLE );
		await spy.expectNotFired( TRACKS_EVENTS.DISMISSED );
	} );

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
		const customized = oldHtml.replace( 'OLD BLOCK A', 'MERCHANT EDITED A' );

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

		// Open the editor directly via the deep-link param that auto-opens the
		// review drawer (wc_email_review_drawer=1 is consumed by the editor
		// integration's index.ts on mount and dispatches openReviewDrawer()).
		await page.goto(
			`/wp-admin/post.php?post=${ postId }&action=edit&wc_email_review_drawer=1`
		);

		// Wait for the editor canvas to be ready.
		await expect( page.locator( '#woocommerce-email-editor' ) ).toBeVisible( {
			timeout: 20000,
		} );

		// The drawer's <aside role="dialog"> becomes aria-hidden="false" once the
		// store dispatches openReviewDrawer(). The title text comes from the
		// "Review template update" h2 inside the drawer header.
		const drawer = page.getByRole( 'dialog', {
			name: /review template update/i,
		} );
		await expect( drawer ).toBeVisible( { timeout: 15000 } );

		// The change-summary fetch is triggered by the drawer's useChangeSummary
		// hook (enabled = isOpen = true). Wait for the conflict group heading
		// which appears once copy_changes are loaded. All 3 blocks conflict
		// (merchant text vs new-canonical text) so the heading says "3 conflicts".
		await expect(
			drawer.getByText( /needs your attention/i )
		).toBeVisible( { timeout: 15000 } );

		// --- Per-conflict decisions ---
		// All conflict radiogroups default to "keep yours" (aria-checked="true").
		// The first radiogroup corresponds to block A (MERCHANT EDITED A vs NEW CORE A).
		// Leave it on "keep yours" (the default).
		// Find all radiogroups; pick the second one (block B) and switch to "use core".
		const radioGroups = drawer.getByRole( 'radiogroup', {
			name: /choose which version to apply/i,
		} );

		// Verify the first conflict defaults to "keep yours" selected.
		const firstGroup = radioGroups.nth( 0 );
		await expect(
			firstGroup.getByRole( 'radio', { name: /keep yours/i } )
		).toHaveAttribute( 'aria-checked', 'true' );

		// On the second conflict, select "use core".
		const secondGroup = radioGroups.nth( 1 );
		await secondGroup.getByRole( 'radio', { name: /use core/i } ).click();
		await expect(
			secondGroup.getByRole( 'radio', { name: /use core/i } )
		).toHaveAttribute( 'aria-checked', 'true' );
		await expect(
			secondGroup.getByRole( 'radio', { name: /keep yours/i } )
		).toHaveAttribute( 'aria-checked', 'false' );

		// Click Apply — the button label is "Apply (N)" where N = total changes.
		await drawer.getByRole( 'button', { name: /^apply/i } ).click();

		// Drawer closes after a successful apply.
		await expect( drawer ).toBeHidden( { timeout: 15000 } );

		// Verify the merged post content via REST.
		// Block A: "keep yours" (default) → MERCHANT EDITED A is preserved.
		// Block B: "use core" → NEW CORE B replaces OLD BLOCK B.
		// Block C: "keep yours" (default) → OLD BLOCK C is preserved.
		const content = await getWooEmailPostContent( postId );
		expect( content ).toContain( 'MERCHANT EDITED A' ); // kept yours
		expect( content ).toContain( 'NEW CORE B' ); // used core
		expect( content ).toContain( 'OLD BLOCK C' ); // kept yours (default)
		expect( content ).not.toContain( 'NEW CORE A' ); // was not overwritten
		expect( content ).not.toContain( 'OLD BLOCK B' ); // was replaced by core
	} );
} );
