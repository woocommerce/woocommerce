/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { expect, request, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import {
	deleteEmailPost,
	disableEmailEditor,
	enableEmailEditor,
} from './helpers/enable-email-editor-feature';
import { accessTheEmailEditor } from '../../utils/email';
import { setOption } from '../../utils/options';

test.describe( 'WooCommerce Email Editor Core', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	const emailPostIds = new Set< string >();

	const captureEmailPostId = ( page: Page ) => {
		const postId = new URL( page.url() ).searchParams.get( 'post' );
		if ( postId && /^[1-9]\d*$/.test( postId ) ) {
			emailPostIds.add( postId );
		}
		return postId;
	};

	const accessAndTrackEmailPost = async ( page: Page ) => {
		let postId: string | null = null;
		try {
			await accessTheEmailEditor( page, 'New order' );
		} finally {
			postId = captureEmailPostId( page );
		}
		// The editor has to have opened a post, and its id is what afterAll deletes.
		expect( postId ).toMatch( /^[1-9]\d*$/ );
	};

	test.beforeAll( async ( { baseURL } ) => {
		await enableEmailEditor( baseURL );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const cleanupErrors: unknown[] = [];

		for ( const postId of emailPostIds ) {
			try {
				await deleteEmailPost( baseURL, postId );
			} catch ( error ) {
				cleanupErrors.push( error );
			}
		}

		try {
			await disableEmailEditor( baseURL );
			const verification = await setOption(
				request,
				baseURL,
				'woocommerce_feature_block_email_editor_enabled',
				'no'
			);
			// The e2e test-helper plugin answers a no-op option write with this
			// wording, so the match proves disableEmailEditor already wrote `no`.
			// A failure here is cleanup failing, not the title that ran last.
			expect( verification ).toContain( 'already set to: no' );
		} catch ( error ) {
			cleanupErrors.push( error );
		}

		if ( cleanupErrors.length > 0 ) {
			throw new AggregateError(
				cleanupErrors,
				`Email editor cleanup failed: ${ cleanupErrors
					.map( ( error ) => String( error ) )
					.join( '; ' ) }`
			);
		}
	} );

	test( 'Can access the email editor', async ( { page } ) => {
		// Try with the new order email.
		await accessAndTrackEmailPost( page );
		// TODO: WP 7.0 compat - WP 7.0 changed the editor sidebar tab role from
		// tab to button. Simplify when WP 7.0 is the minimum supported version.
		const emailTab = page
			.getByRole( 'tab', { name: 'Email' } )
			.or( page.getByRole( 'button', { name: 'Email', exact: true } ) );
		await emailTab.click();
		await expect(
			page.locator( '.editor-post-card-panel__title' )
		).toContainText( 'New order' );
		await expect(
			page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByLabel( 'Block: Heading' )
		).toContainText( `New order: #[woocommerce/order-number]` );
	} );

	test( 'Can preview in new tab', async ( { page } ) => {
		await accessAndTrackEmailPost( page );
		await page.getByRole( 'button', { name: 'View', exact: true } ).click();

		// WP 7.1 adds a "Responsive styles" toggle to this menu; the email
		// editor disables it because the email renderer cannot inline
		// per-viewport styles. Also passes on older WP without the feature.
		await expect(
			page.getByRole( 'menuitemcheckbox', {
				name: 'Responsive styles',
			} )
		).toBeHidden();

		const [ newPage ] = await Promise.all( [
			page.waitForEvent( 'popup' ), // Waits for the new tab to open
			page
				.getByRole( 'menuitem', { name: 'Preview in new tab' } )
				.click(),
		] );
		try {
			await newPage.bringToFront();
			await newPage.waitForLoadState( 'domcontentloaded' );
			// eslint-disable-next-line playwright/no-wait-for-selector -- wait for the tab to be loaded.
			await newPage.waitForSelector( '.wp-block-heading' );
			await page.close(); // close the original tab.
			await expect( newPage.locator( 'body' ) ).toContainText(
				'New order: #12345'
			);
		} finally {
			await newPage.close();
		}
	} );

	test( 'Can edit and save content', async ( { page } ) => {
		await accessAndTrackEmailPost( page );
		await expect(
			page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByText( 'You’ve received a new' )
		).toBeVisible();

		// Note: fill with a single line of text. On WP 7.1 a value containing a
		// newline splits the paragraph but the edit never registers as a
		// dirtying change, so the Save button stays disabled. A single-line
		// edit commits normally and still exercises the edit → save → preview
		// flow this test covers.
		const editableParagraph = page
			.locator( 'iframe[name="editor-canvas"]' )
			.contentFrame()
			.getByText( 'You’ve received a new' );
		await editableParagraph.click();
		await expect( editableParagraph ).toBeEditable();
		await editableParagraph.fill( 'Hello world from Woo plugin' );
		await expect(
			page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByText( 'Hello world from Woo plugin' )
		).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'Save', exact: true } )
		).toBeVisible();
		await page.getByRole( 'button', { name: 'Save', exact: true } ).click();
		// The editor writes every announcement into this one region, so match the
		// save announcement inside it rather than requiring it to be the only one.
		await expect( page.locator( '#a11y-speak-polite' ) ).toContainText(
			'Email saved.'
		);
		await expect(
			page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByText( 'Hello world from Woo' )
		).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'View', exact: true } )
		).toBeVisible();
		await page.getByRole( 'button', { name: 'View', exact: true } ).click();
		const page1Promise = page.waitForEvent( 'popup' );
		await page
			.getByRole( 'menuitem', { name: 'Preview in new tab' } )
			.click();
		const page1 = await page1Promise;
		try {
			await page1.bringToFront();
			await page1.waitForLoadState( 'domcontentloaded' );
			// Wait for the generated preview to replace the loading screen.
			await expect(
				page1.locator( '.wp-block-heading' ).first()
			).toBeVisible();
			await expect( page1.locator( 'body' ) ).toContainText(
				'Hello world from Woo plugin'
			);
		} finally {
			await page1.close();
		}
	} );

	test( 'Can use personalization tags in the Button block', async ( {
		page,
		baseURL,
	} ) => {
		// Redundant with beforeAll, which owns enablement for this suite and runs
		// again in a restarted worker. Kept so that merging this title forward
		// stays a merge: it is harmless, and removing it would be a behavior
		// change in a title this batch does not otherwise touch.
		await enableEmailEditor( baseURL );
		await accessAndTrackEmailPost( page );
		const canvas = page
			.locator( 'iframe[name="editor-canvas"]' )
			.contentFrame();

		// Set the insertion point inside the email content. The heading is not
		// touched by the other tests in this suite, unlike the first paragraph.
		await canvas.getByLabel( 'Block: Heading' ).click();

		// Insert a Button block.
		await page.getByRole( 'button', { name: 'Block Inserter' } ).click();
		await page
			.getByRole( 'searchbox', { name: 'Search' } )
			.fill( 'Buttons' );
		await page
			.getByRole( 'option', { name: 'Buttons', exact: true } )
			.click();

		// Focus the button text. The Personalization Tags toolbar button must be
		// available there — the button text field uses
		// `withoutInteractiveFormatting`, which hides the button when the
		// personalization tags format is registered as interactive.
		await canvas
			.getByRole( 'document', { name: 'Block: Button', exact: true } )
			.click();
		const personalizationTagsButton = page
			.getByRole( 'toolbar', { name: 'Block tools' } )
			.getByRole( 'button', { name: 'Personalization Tags' } );
		await expect( personalizationTagsButton ).toBeVisible();

		// Set a URL personalization tag as the button link.
		await personalizationTagsButton.click();
		await expect(
			page.getByRole( 'heading', { name: 'Personalization Tags' } )
		).toBeVisible();
		await page
			.locator(
				'.woocommerce-personalization-tags-modal-category-group-item'
			)
			.filter( { hasText: 'Payment URL' } )
			.getByRole( 'button', { name: 'Set as URL' } )
			.click();
		await expect(
			page.getByRole( 'heading', { name: 'Personalization Tags' } )
		).toBeHidden();

		await expect
			.poll( () =>
				page.evaluate(
					() =>
						window.wp.data
							.select( 'core/block-editor' )
							.getSelectedBlock()?.attributes?.url
				)
			)
			.toBe( '[woocommerce/order-payment-url]' );
	} );
} );
