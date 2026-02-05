const { test, expect } = require( '@playwright/test' );
const { ADMIN_STATE_PATH } = require( '../../playwright.config' );
const {
	enableEmailEditor,
	disableEmailEditor,
	resetWCTransactionalEmail,
} = require( './helpers/enable-email-editor-feature' );
const {
	accessTheEmailEditor,
	ensureEmailEditorSettingsPanelIsOpened,
} = require( '../../utils/email' );

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
} );
