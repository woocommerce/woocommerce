/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';
import { request } from '@playwright/test';
import {
	createClient,
	WC_API_PATH,
	WP_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { expect, test as baseTest } from '../../fixtures/fixtures';
import { admin } from '../../test-data/data';
import { setOption } from '../../utils/options';
import { accessTheEmailEditor, expectEmail } from '../../utils/email';

/**
 * End-to-end coverage for file-first block email rendering (WOOPLUG-6171):
 * with the block email editor enabled, a transactional email renders from its
 * file template until the merchant edits AND saves it — a draft scratchpad
 * (even one carrying unsaved edits) never affects what customers receive.
 *
 * Uses the customer "Processing order" email; delivery is asserted through the
 * WP Mail Logging inbox like the classic `order-emails.spec.ts`.
 */

const EMAIL_LISTING_TITLE = 'Order confirmation';
const EMAIL_TYPE = 'customer_processing_order';
const SUBJECT_REGEX = /Your .+ order has been received!/;
// Footer text unique to the `wooemailtemplate` block template — proves the
// email was rendered through the block pipeline, not the classic one.
const BLOCK_TEMPLATE_FOOTER = 'All Rights Reserved';
const DRAFT_MARKER = 'WOOPLUG6171_DRAFT_ONLY_MARKER';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
} );

test.describe.configure( { mode: 'serial' } );

const createAdminApiClient = ( baseURL: string ) =>
	createClient( baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );

const deleteEmailTypePosts = async ( baseURL: string ) => {
	const apiClient = createAdminApiClient( baseURL );
	const posts = await apiClient.get( `${ WP_API_PATH }/woo_email`, {
		status: 'publish,draft',
		per_page: 100,
	} );
	for ( const post of posts.data ) {
		if ( ( post.slug as string ).startsWith( EMAIL_TYPE ) ) {
			await apiClient.delete( `${ WP_API_PATH }/woo_email/${ post.id }`, {
				force: true,
			} );
		}
	}
};

const orderIds: number[] = [];

test.beforeAll( async ( { baseURL } ) => {
	await setOption(
		request,
		baseURL,
		'woocommerce_feature_block_email_editor_enabled',
		'yes'
	);
	// Start from a clean slate in case another spec left a post behind.
	await deleteEmailTypePosts( baseURL );
} );

test.afterAll( async ( { baseURL } ) => {
	const apiClient = createAdminApiClient( baseURL );
	for ( const orderId of orderIds ) {
		await apiClient.delete( `${ WC_API_PATH }/orders/${ orderId }`, {
			force: true,
		} );
	}
	// Delete posts while the feature is still enabled so the
	// `before_delete_post` hook also clears the email type → post mapping.
	await deleteEmailTypePosts( baseURL );
	await setOption(
		request,
		baseURL,
		'woocommerce_feature_block_email_editor_enabled',
		'no'
	);
} );

/**
 * Create a processing order (which sends the customer email) and open its
 * logged email in the WP Mail Logging modal.
 *
 * @param {import('@playwright/test').Page} page    The Playwright page.
 * @param {*}                               restApi The REST API client fixture.
 * @return {Promise<import('@playwright/test').FrameLocator>} Locator of the logged email body frame.
 */
const triggerOrderEmailAndOpenLog = async ( page, restApi ) => {
	const customerEmail = faker.internet.exampleEmail();
	const orderResponse = await restApi.post( `${ WC_API_PATH }/orders`, {
		status: 'processing',
		billing: { email: customerEmail },
	} );
	orderIds.push( orderResponse.data.id );

	const emailRow = await expectEmail( page, customerEmail, SUBJECT_REGEX );
	await emailRow.getByRole( 'button', { name: 'View log' } ).click();

	const modalContent = page.locator(
		'#wp-mail-logging-modal-content-body-content'
	);
	await expect(
		modalContent.getByText( `Receiver ${ customerEmail }` )
	).toBeVisible();

	return modalContent.locator( 'iframe' ).contentFrame();
};

test( 'uncustomized email is sent from the file template', async ( {
	page,
	restApi,
} ) => {
	const emailBody = await triggerOrderEmailAndOpenLog( page, restApi );

	await expect( emailBody.locator( 'body' ) ).toContainText(
		'is now being processed'
	);
	await expect( emailBody.locator( 'body' ) ).toContainText(
		BLOCK_TEMPLATE_FOOTER
	);
} );

test( 'draft edits do not affect sent emails until saved', async ( {
	page,
	restApi,
} ) => {
	// Opening the editor lazily creates the draft scratchpad with the file
	// template content.
	await accessTheEmailEditor( page, EMAIL_LISTING_TITLE );
	await expect(
		page
			.locator( 'iframe[name="editor-canvas"]' )
			.contentFrame()
			.getByText( 'Thank you for your order' )
	).toBeVisible();

	// Write an edit into the draft via REST — a deterministic stand-in for
	// the editor's remote autosave (which fires on a 60s interval).
	const drafts = await restApi.get( `${ WP_API_PATH }/woo_email`, {
		status: 'draft',
		context: 'edit',
		per_page: 100,
	} );
	const draft = drafts.data.find( ( post ) =>
		( post.slug as string ).startsWith( EMAIL_TYPE )
	);
	expect( draft ).toBeTruthy();
	await restApi.post( `${ WP_API_PATH }/woo_email/${ draft.id }`, {
		content: `${ draft.content.raw }\n<!-- wp:paragraph --><p>${ DRAFT_MARKER }</p><!-- /wp:paragraph -->`,
	} );

	const emailBody = await triggerOrderEmailAndOpenLog( page, restApi );

	await expect( emailBody.locator( 'body' ) ).toContainText(
		BLOCK_TEMPLATE_FOOTER
	);
	await expect( emailBody.locator( 'body' ) ).not.toContainText(
		DRAFT_MARKER
	);
} );

test( 'saved email is sent from the customized post', async ( {
	page,
	restApi,
} ) => {
	// The editor reuses the edited draft (edits must survive reopening).
	await accessTheEmailEditor( page, EMAIL_LISTING_TITLE );
	await expect(
		page
			.locator( 'iframe[name="editor-canvas"]' )
			.contentFrame()
			.getByText( DRAFT_MARKER )
	).toBeVisible();

	// Save publishes the draft in the background, making it the rendering
	// source.
	await page.getByRole( 'button', { name: 'Save', exact: true } ).click();

	const drafts = await restApi.get( `${ WP_API_PATH }/woo_email`, {
		status: 'publish,draft',
		per_page: 100,
	} );
	const post = drafts.data.find( ( item ) =>
		( item.slug as string ).startsWith( EMAIL_TYPE )
	);
	expect( post ).toBeTruthy();
	await expect
		.poll(
			async () => {
				const response = await restApi.get(
					`${ WP_API_PATH }/woo_email/${ post.id }`,
					{ context: 'edit' }
				);
				return response.data.status;
			},
			{ timeout: 20000 }
		)
		.toBe( 'publish' );

	const emailBody = await triggerOrderEmailAndOpenLog( page, restApi );

	await expect( emailBody.locator( 'body' ) ).toContainText( DRAFT_MARKER );
} );
