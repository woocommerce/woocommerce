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
import { expect, test as baseTest, locks } from '../../fixtures/fixtures';
import { admin } from '../../test-data/data';
import { deleteOption, setOption } from '../../utils/options';
import { accessTheEmailEditor, expectEmail } from '../../utils/email';

/**
 * End-to-end coverage for the installed block-email customization seam: a
 * merchant opens the file-backed editor, edits its scratchpad, saves it, and
 * receives the published customization in a transactional email.
 *
 * Uses the customer "Processing order" email; delivery is asserted through the
 * WP Mail Logging inbox like the classic `order-emails.spec.ts`.
 *
 * The customization includes a Product Collection block, so the sent email also
 * covers WooCommerce registering its block types for the render. Orders created
 * through the REST API are one of the requests that skip that registration, and
 * the block used to render empty in the emails they send.
 */

const EMAIL_LISTING_TITLE = 'Order confirmation';
const EMAIL_TYPE = 'customer_processing_order';
const SUBJECT_REGEX = /Your .+ order has been received!/;
const DRAFT_MARKER = 'WOOPLUG6171_DRAFT_ONLY_MARKER';
const PRODUCT_NAME = 'WOOPLUG7795 Email product';
const EMAIL_POST_MAPPING_OPTION =
	'woocommerce_email_templates_customer_processing_order_post_id';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
} );

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
let productId: number;

/**
 * Block markup for a product collection listing just the given product.
 *
 * @param {number} handPickedProductId The product to list.
 * @return {string} The serialized block markup.
 */
const productCollectionBlock = ( handPickedProductId: number ) => {
	const attributes = {
		queryId: 0,
		query: {
			perPage: 1,
			pages: 1,
			offset: 0,
			postType: 'product',
			order: 'asc',
			orderBy: 'title',
			search: '',
			exclude: [],
			inherit: false,
			taxQuery: {},
			isProductCollectionBlock: true,
			woocommerceOnSale: false,
			woocommerceStockStatus: [ 'instock', 'outofstock', 'onbackorder' ],
			woocommerceAttributes: [],
			woocommerceHandPickedProducts: [ String( handPickedProductId ) ],
		},
		tagName: 'div',
		displayLayout: { type: 'flex', columns: 1 },
		collection: 'woocommerce/product-collection/hand-picked',
	};

	return `<!-- wp:woocommerce/product-collection ${ JSON.stringify(
		attributes
	) } -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:post-title {"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection -->`;
};

test.beforeAll( async ( { baseURL } ) => {
	await setOption(
		request,
		baseURL,
		'woocommerce_feature_block_email_editor_enabled',
		'yes'
	);
	// Start from a clean slate in case another spec left a post behind.
	await deleteEmailTypePosts( baseURL );
	// A mapping can outlive its email post and later point at an unrelated post
	// after an environment restore reuses the numeric ID. Remove this email
	// type's mapping so the real Edit action creates a fresh `woo_email` draft.
	await deleteOption( request, baseURL, EMAIL_POST_MAPPING_OPTION );

	const productResponse = await createAdminApiClient( baseURL ).post(
		`${ WC_API_PATH }/products`,
		{
			name: PRODUCT_NAME,
			type: 'simple',
			regular_price: '10',
			status: 'publish',
		}
	);
	productId = productResponse.data.id;
} );

test.afterAll( async ( { baseURL } ) => {
	const apiClient = createAdminApiClient( baseURL );
	for ( const orderId of orderIds ) {
		await apiClient.delete( `${ WC_API_PATH }/orders/${ orderId }`, {
			force: true,
		} );
	}
	if ( productId ) {
		await apiClient.delete( `${ WC_API_PATH }/products/${ productId }`, {
			force: true,
		} );
	}
	// Delete posts while the feature is still enabled so the
	// `before_delete_post` hook also clears the email type → post mapping.
	await deleteEmailTypePosts( baseURL );
	await deleteOption( request, baseURL, EMAIL_POST_MAPPING_OPTION );
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

test(
	'saved email is sent from the customized post',
	{ lock: locks.EMAIL_FEATURE_FLAGS },
	async ( { page, restApi } ) => {
		// Opening the editor creates this email type's draft scratchpad from the
		// canonical file template.
		await accessTheEmailEditor( page, EMAIL_LISTING_TITLE );
		await expect(
			page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByText( 'Thank you for your order' )
		).toBeVisible();

		const drafts = await restApi.get( `${ WP_API_PATH }/woo_email`, {
			status: 'draft',
			context: 'edit',
			per_page: 100,
		} );
		const draft = drafts.data.find( ( post ) =>
			( post.slug as string ).startsWith( EMAIL_TYPE )
		);
		expect( draft ).toBeTruthy();
		expect( Number.isSafeInteger( draft.id ) && draft.id > 0 ).toBe( true );
		await restApi.post( `${ WP_API_PATH }/woo_email/${ draft.id }`, {
			content: `${
				draft.content.raw
			}\n<!-- wp:paragraph --><p>${ DRAFT_MARKER }</p><!-- /wp:paragraph -->\n${ productCollectionBlock(
				productId
			) }`,
		} );

		// The real listing must reopen that same edited scratchpad before Save.
		await accessTheEmailEditor( page, EMAIL_LISTING_TITLE );
		await expect(
			page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByText( DRAFT_MARKER )
		).toBeVisible();

		// Save publishes the draft in the background, making it the rendering
		// source. Observe the exact post write and then poll the same draft ID.
		const saveResponse = page.waitForResponse( ( response ) => {
			const url = new URL( response.url() );
			return (
				url.pathname.endsWith( `/wp/v2/woo_email/${ draft.id }` ) &&
				[ 'POST', 'PUT' ].includes( response.request().method() ) &&
				response.ok()
			);
		} );
		await page.getByRole( 'button', { name: 'Save', exact: true } ).click();
		await saveResponse;

		await expect
			.poll(
				async () => {
					const response = await restApi.get(
						`${ WP_API_PATH }/woo_email/${ draft.id }`,
						{ context: 'edit' }
					);
					return response.data.status;
				},
				{ timeout: 20000 }
			)
			.toBe( 'publish' );

		const emailBody = await triggerOrderEmailAndOpenLog( page, restApi );

		await expect( emailBody.locator( 'body' ) ).toContainText(
			DRAFT_MARKER
		);
		// The order was created through the REST API, which skips registering
		// WooCommerce's block types. Without the on-demand registration the
		// product collection renders empty in the sent email.
		await expect( emailBody.locator( 'body' ) ).toContainText(
			PRODUCT_NAME
		);
	}
);
