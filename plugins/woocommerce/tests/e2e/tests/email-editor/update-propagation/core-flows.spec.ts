/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../../playwright.config';
import {
	deleteEmailPost,
	disableEmailEditor,
	enableEmailEditor,
} from '../helpers/enable-email-editor-feature';
import { accessTheEmailEditor } from '../../../utils/email';
import { setTemplateHtmlOverride } from './helpers/test-helper-plugin';
import {
	seedWooEmailPost,
	getWooEmailPostContent,
} from './helpers/seed-woo-email';
import {
	simulateCoreBump,
	triggerDetectionSweep,
} from './helpers/simulate-plugin-update';
import { assertNoLeakedFixtureState } from './helpers/leaked-state-checks';
import { STATUS } from './helpers/classifications';

test.describe( 'Update propagation — core flows', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );
	let seededPostId: number | null = null;

	test.beforeAll( async ( { baseURL } ) => {
		await enableEmailEditor( baseURL! );
	} );

	test.afterEach( async ( { baseURL } ) => {
		const cleanupErrors: unknown[] = [];

		try {
			await assertNoLeakedFixtureState();
		} catch ( error ) {
			cleanupErrors.push( error );
		}

		if ( seededPostId !== null ) {
			try {
				await deleteEmailPost( baseURL!, String( seededPostId ) );
			} catch ( error ) {
				cleanupErrors.push( error );
			} finally {
				seededPostId = null;
			}
		}

		if ( cleanupErrors.length > 0 ) {
			throw new AggregateError(
				cleanupErrors,
				'Update propagation cleanup failed.'
			);
		}
	} );

	test.afterAll( async ( { baseURL } ) => {
		await disableEmailEditor( baseURL! );
	} );

	/**
	 * Verifies the installed list-to-editor update flow: the list and editor
	 * surface a customized core update, the review drawer applies one explicit
	 * core choice, and the merged content is persisted.
	 */
	test( '@pr Review drawer: pick per-conflict yours vs core and apply', async ( {
		page,
	} ) => {
		const oldHtml =
			'<!-- wp:paragraph --><p>OLD BLOCK A</p><!-- /wp:paragraph -->' +
			'<!-- wp:paragraph --><p>OLD BLOCK B</p><!-- /wp:paragraph -->' +
			'<!-- wp:paragraph --><p>OLD BLOCK C</p><!-- /wp:paragraph -->';

		const customized = oldHtml.replace(
			'OLD BLOCK A',
			'MERCHANT EDITED A'
		);

		const newCanonical =
			'<!-- wp:paragraph --><p>NEW CORE A</p><!-- /wp:paragraph -->' +
			'<!-- wp:paragraph --><p>NEW CORE B</p><!-- /wp:paragraph -->' +
			'<!-- wp:paragraph --><p>NEW CORE C</p><!-- /wp:paragraph -->';

		// Seed the merchant edit against the old canonical content.
		await simulateCoreBump( 'new_order', oldHtml );
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: customized,
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
			version: '10.0.0',
		} );
		seededPostId = postId;

		// Move the canonical template and classify the post as requiring review.
		await setTemplateHtmlOverride( 'new_order', newCanonical );
		await triggerDetectionSweep();

		// Prove the installed list surfaces the update on the exact email row.
		await page.goto( '/wp-admin/admin.php?page=wc-settings&tab=email' );
		const newOrderRow = page
			.locator( 'tr' )
			.filter( { hasText: /New order/i } )
			.first();
		await expect(
			newOrderRow.getByRole( 'button', { name: /review update/i } )
		).toBeVisible( { timeout: 15000 } );

		// Enter through the real list/editor helper and open the review drawer
		// from the editor banner.
		await accessTheEmailEditor( page, 'New order' );
		await expect( page.locator( '#woocommerce-email-editor' ) ).toBeVisible(
			{
				timeout: 20000,
			}
		);
		await expect(
			page.getByText( /template update available/i ).first()
		).toBeVisible( { timeout: 15000 } );
		await page.getByRole( 'button', { name: /^review changes$/i } ).click();

		const drawer = page.getByRole( 'dialog', {
			name: /review template update/i,
		} );
		await expect( drawer ).toBeVisible( { timeout: 15000 } );
		await expect(
			drawer.getByRole( 'heading', { name: /needs your attention/i } )
		).toBeVisible( { timeout: 15000 } );

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

		await drawer.getByRole( 'button', { name: /^apply/i } ).click();
		await expect( drawer ).toBeHidden( { timeout: 15000 } );

		const content = await getWooEmailPostContent( postId );
		expect( content ).toContain( 'NEW CORE A' );
		expect( content ).not.toContain( 'MERCHANT EDITED A' );
	} );
} );
