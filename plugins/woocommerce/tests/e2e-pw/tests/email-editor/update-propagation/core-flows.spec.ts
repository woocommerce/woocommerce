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

		const dismissButton = page
			.getByRole( 'button', { name: /dismiss/i } )
			.first();
		await expect( dismissButton ).toBeVisible( { timeout: 15000 } );
		await dismissButton.click();

		await spy.expectFired( TRACKS_EVENTS.DISMISSED );
	} );
} );
