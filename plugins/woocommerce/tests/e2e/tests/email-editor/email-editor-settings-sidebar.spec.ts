/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import {
	enableEmailEditor,
	disableEmailEditor,
	resetWCTransactionalEmail,
} from './helpers/enable-email-editor-feature';
import {
	accessTheEmailEditor,
	ensureEmailEditorSettingsPanelIsOpened,
} from '../../utils/email';

test.describe( 'WooCommerce Email Editor Settings Sidebar Integration', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async ( { baseURL } ) => {
		await enableEmailEditor( baseURL );
	} );

	test.afterAll( async ( { baseURL } ) => {
		await resetWCTransactionalEmail( baseURL, 'customer_note' );
		await resetWCTransactionalEmail( baseURL, 'new_order' );
		await disableEmailEditor( baseURL );
	} );

	test( 'Can update email status', async ( { page } ) => {
		await accessTheEmailEditor( page, 'Customer note' );

		await page
			.getByLabel( 'Email' )
			.getByRole( 'button', { name: 'Settings' } )
			.click();
		await ensureEmailEditorSettingsPanelIsOpened( page );
		await expect(
			page.locator( '.editor-post-status__toggle' )
		).toContainText( 'Active' );
		await page.locator( '.editor-post-status__toggle' ).click();
		await page.getByRole( 'radio', { name: 'Inactive' } ).click();
		await page.getByRole( 'button', { name: 'Save', exact: true } ).click();
		await expect(
			page.locator( '.editor-post-status__toggle' )
		).toContainText( 'Inactive' );
		// eslint-disable-next-line playwright/no-wait-for-timeout -- wait for content to be saved and updated.
		await page.waitForTimeout( 1000 );
		await page.reload();
		await expect(
			page.locator( '.editor-post-status__toggle' )
		).toContainText( 'Inactive' );
		// reset the email status.
		await page.locator( '.editor-post-status__toggle' ).click();
		await page
			.getByRole( 'radio', { name: 'Active', exact: true } )
			.click();
		await page.getByRole( 'button', { name: 'Save', exact: true } ).click();
		await expect(
			page.locator( '.editor-post-status__toggle' )
		).toContainText( 'Active' );
	} );

	test( 'Can update email subject and preview text', async ( { page } ) => {
		await accessTheEmailEditor( page, 'Customer note' );

		await page
			.getByLabel( 'Email' )
			.getByRole( 'button', { name: 'Settings' } )
			.click();
		await ensureEmailEditorSettingsPanelIsOpened( page );

		const randomNum = new Date().getTime().toString();
		const subject = `hello subject ${ randomNum } `;
		const preheader = `hello preheader ${ randomNum } `;

		// fill the subject.
		await expect(
			page
				.locator( '.woocommerce-settings-panel-subject-text span' )
				.filter( { hasText: 'Subject' } )
				.first()
		).toBeVisible();
		await page
			.locator( '[data-automation-id="email_subject"]' )
			.fill( subject );
		await page.locator( '[data-automation-id="email_subject"]' ).click(); // put the cursor at the end of the subject.
		await page
			.locator(
				'.woocommerce-settings-panel-subject-text button[title="Personalization Tags"]'
			)
			.first()
			.click(); // open personalization tags modal.
		await expect(
			page.getByRole( 'heading', { name: 'Personalization Tags' } )
		).toBeVisible();
		await expect( page.getByLabel( 'Scrollable section' ) ).toContainText(
			'Customer Email'
		);
		await page
			.locator( 'div' )
			.filter( {
				hasText:
					/^Customer Email\[woocommerce\/customer-email\]Insert$/,
			} )
			.getByRole( 'button' )
			.click();

		// fill the preheader.
		await page
			.locator( '[data-automation-id="email_preheader"]' )
			.fill( preheader );
		await page.locator( '[data-automation-id="email_preheader"]' ).click(); // put the cursor at the end of the preheader.
		await page
			.locator(
				'.woocommerce-settings-panel-preheader-text button[title="Personalization Tags"]'
			)
			.first()
			.click(); // open personalization tags modal.
		await expect(
			page.getByRole( 'heading', { name: 'Personalization Tags' } )
		).toBeVisible();
		await expect( page.getByText( 'Customer First Name' ) ).toBeVisible();
		await page.getByText( 'Customer First Name[' ).click();
		await page
			.locator( 'div' )
			.filter( {
				hasText:
					/^Customer First Name\[woocommerce\/customer-first-name\]Insert$/,
			} )
			.getByRole( 'button' )
			.click();
		await page.getByRole( 'button', { name: 'Save', exact: true } ).click();
		await expect(
			page.locator( '[data-automation-id="email_subject"]' )
		).toContainText( `${ subject } [woocommerce/customer-email]` );
		await expect(
			page.locator( '[data-automation-id="email_preheader"]' )
		).toContainText( `${ preheader } [woocommerce/customer-first-name]` );
	} );

	test( 'Can update email recipients', async ( { page } ) => {
		await accessTheEmailEditor( page, 'New order' );
		await page
			.getByLabel( 'Email' )
			.getByRole( 'button', { name: 'Settings' } )
			.click();
		await ensureEmailEditorSettingsPanelIsOpened( page );
		await expect(
			page.locator( '[for="woocommerce-email-editor-recipients"]' )
		).toBeVisible();
		await expect( page.getByTestId( 'email_recipient' ) ).toBeVisible(); // form is filled with the default value.

		const randomNum = new Date().getTime().toString();
		const ccEmail = `cc-mail-${ randomNum }@example.com`;
		const bccEmail = `bcc-mail-${ randomNum }@example.com`;

		// cc.
		await expect( page.getByText( 'Add CC' ) ).toBeVisible();
		await page.getByRole( 'checkbox', { name: 'Add CC' } ).check();
		await expect(
			page.getByText(
				'Add recipients who will receive a copy of the email.'
			)
		).toBeVisible();
		await expect( page.getByTestId( 'email_cc' ) ).toBeVisible();
		await page.getByTestId( 'email_cc' ).click();
		await page.getByTestId( 'email_cc' ).fill( ccEmail );
		// bcc.
		await expect( page.getByText( 'Add BCC' ) ).toBeVisible();
		await page.getByRole( 'checkbox', { name: 'Add BCC' } ).check();
		await expect(
			page.getByText(
				'Add recipients who will receive a hidden copy of the email.'
			)
		).toBeVisible();
		await page.getByTestId( 'email_bcc' ).click();
		await page.getByTestId( 'email_bcc' ).fill( bccEmail );
		await page.getByRole( 'button', { name: 'Save', exact: true } ).click();

		// assert the values.
		await expect( page.getByTestId( 'email_cc' ) ).toHaveValue( ccEmail );
		await expect( page.getByTestId( 'email_bcc' ) ).toHaveValue( bccEmail );
	} );

	test( 'Does not show the core Content block list in the Email tab', async ( {
		page,
	} ) => {
		await accessTheEmailEditor( page, 'Customer note' );

		const emailTab = page.getByLabel( 'Email' );
		await expect(
			emailTab.getByRole( 'button', { name: 'Settings' } )
		).toBeVisible();
		await expect(
			emailTab.getByRole( 'button', { name: 'Content', exact: true } )
		).toHaveCount( 0 );

		// Asserting the item exists keeps this test from passing when core never
		// rendered the panel. If core stops rendering it, remove the CSS rule.
		const quickNavItem = emailTab
			.locator( '.components-button .block-editor-block-icon' )
			.first();
		await expect( quickNavItem ).toBeAttached();
		await expect( quickNavItem ).toBeHidden();
	} );

	test( 'Shows the Content list in the Block tab of a content-only block', async ( {
		page,
	} ) => {
		await accessTheEmailEditor( page, 'Customer note' );
		await expect( page.getByLabel( 'Email' ) ).toBeVisible();

		await page.evaluate( () => {
			const { createBlock } = window.wp.blocks;
			const { select, dispatch } = window.wp.data;
			const group = createBlock(
				'core/group',
				{ templateLock: 'contentOnly' },
				[ createBlock( 'core/paragraph', { content: 'Nested text' } ) ]
			);
			const [ postContent ] =
				select( 'core/block-editor' ).getBlocksByName(
					'core/post-content'
				);
			dispatch( 'core/block-editor' ).insertBlocks(
				group,
				0,
				postContent
			);
			dispatch( 'core/block-editor' ).selectBlock( group.clientId );
		} );

		await page.getByRole( 'tab', { name: 'Block' } ).click();
		const sidebar = page.locator( '.editor-sidebar__panel' );
		await expect(
			sidebar.getByRole( 'button', { name: 'Paragraph' } )
		).toBeVisible();
	} );
} );
