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
import { resetFixtureState } from './helpers/reset-fixture-state';
import { STATUS } from './helpers/classifications';
import { locks } from '../../../fixtures/fixtures';

test.describe(
	'Update propagation — core flows',
	{ lock: locks.EMAIL_FEATURE_FLAGS },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );
		let seededPostId: number | null = null;

		test.beforeAll( async ( { baseURL } ) => {
			await enableEmailEditor( baseURL! );
		} );

		test.afterEach( async ( { baseURL } ) => {
			const cleanupErrors: unknown[] = [];

			try {
				await resetFixtureState();
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
			await expect(
				page.locator( '#woocommerce-email-editor' )
			).toBeVisible( {
				timeout: 20000,
			} );
			await expect(
				page.getByText( /template update available/i ).first()
			).toBeVisible( { timeout: 15000 } );
			await page
				.getByRole( 'button', { name: /^review changes$/i } )
				.click();

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

			// B and C were changed by core only. Assert the drawer's promise about
			// them before applying, so the claim and the result stay tied together.
			await expect(
				drawer.getByText(
					/your version was unchanged, so the update will apply/i
				)
			).toHaveCount( 2 );

			await drawer.getByRole( 'button', { name: /^apply/i } ).click();
			await expect( drawer ).toBeHidden( { timeout: 15000 } );

			const content = await getWooEmailPostContent( postId );
			expect( content ).toContain( 'NEW CORE A' );
			expect( content ).not.toContain( 'MERCHANT EDITED A' );

			expect( content ).toContain( 'NEW CORE B' );
			expect( content ).toContain( 'NEW CORE C' );
			expect( content ).not.toContain( 'OLD BLOCK B' );
			expect( content ).not.toContain( 'OLD BLOCK C' );
		} );

		/**
		 * Guards the "did the merchant edit this block?" check against the
		 * block editor itself. Saving in the editor rewrites blocks nobody
		 * edited: it migrates the `align` attribute, decodes entities and
		 * trims the space inside a block's outer tag. Those blocks must still
		 * count as untouched, or the update turns into conflicts the merchant
		 * never created.
		 */
		test( '@pr Blocks the editor rewrote on save still auto-resolve', async ( {
			page,
		} ) => {
			const oldHtml =
				'<!-- wp:paragraph --><p>EDITABLE LINE</p><!-- /wp:paragraph -->' +
				'<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"> OLD CENTERED LINE </p><!-- /wp:paragraph -->' +
				'<!-- wp:paragraph --><p>OLD LINE WE COULDN&#039;T SKIP</p><!-- /wp:paragraph -->';

			const newCanonical = oldHtml
				.replace( 'OLD CENTERED LINE', 'NEW CENTERED LINE' )
				.replace( 'OLD LINE WE', 'NEW LINE WE' );

			await simulateCoreBump( 'new_order', oldHtml );
			const postId = await seedWooEmailPost( {
				emailId: 'new_order',
				storedSourceHash: 'AUTO_CURRENT',
				status: STATUS.IN_SYNC,
				version: '10.0.0',
			} );
			seededPostId = postId;

			// Edit one block and save, so the editor re-serializes the whole post.
			await accessTheEmailEditor( page, 'New order' );
			const canvas = page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame();
			const editable = canvas.getByText( 'EDITABLE LINE' );
			await editable.click();
			await expect( editable ).toBeEditable();
			await editable.fill( 'MERCHANT LINE' );
			await page
				.getByRole( 'button', { name: 'Save', exact: true } )
				.click();
			await expect(
				page
					.locator( '.components-snackbar' )
					.filter( { hasText: 'Email saved.' } )
			).toBeVisible();

			await setTemplateHtmlOverride( 'new_order', newCanonical );
			await triggerDetectionSweep();

			await accessTheEmailEditor( page, 'New order' );
			await expect(
				page.getByText( /template update available/i ).first()
			).toBeVisible( { timeout: 15000 } );

			// Neither rewritten block is a conflict, so the banner offers the
			// plain "Review" button, not "Review changes".
			await page
				.getByRole( 'button', { name: 'Review', exact: true } )
				.click();

			const drawer = page.getByRole( 'dialog', {
				name: /review template update/i,
			} );
			await expect( drawer ).toBeVisible( { timeout: 15000 } );
			await expect(
				drawer.getByText(
					/your version was unchanged, so the update will apply/i
				)
			).toHaveCount( 2 );
			await expect(
				drawer.getByRole( 'heading', { name: /needs your attention/i } )
			).toHaveCount( 0 );

			await drawer.getByRole( 'button', { name: /^apply/i } ).click();
			await expect( drawer ).toBeHidden( { timeout: 15000 } );

			const content = await getWooEmailPostContent( postId );
			expect( content ).toContain( 'MERCHANT LINE' );
			expect( content ).toContain( 'NEW CENTERED LINE' );
			expect( content ).toContain( 'NEW LINE WE' );
			expect( content ).not.toContain( 'OLD CENTERED LINE' );
			expect( content ).not.toContain( 'OLD LINE WE' );
		} );
	}
);
