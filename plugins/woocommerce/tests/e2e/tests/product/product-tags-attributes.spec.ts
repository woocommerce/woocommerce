/**
 * External dependencies
 */
import {
	getCanvas,
	goToPageEditor,
	insertBlockByShortcut,
	publishPage,
	WC_API_PATH,
	WP_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, test, expect, request } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { fillPageTitle } from '../../utils/editor';
import { getFakeProduct, getFakeTag, getFakeAttribute } from '../../utils/data';
import { setOption } from '../../utils/options';

const pageTitle = 'Product Showcase';

const productTagName1 = getFakeTag().name;
const productTagName2 = getFakeTag().name;
const productTagName3 = getFakeTag().name;

// Both the attribute and its term must be unique: the attribute creates a
// global `pa_*` taxonomy and the term a global term within it, so a fixed name
// would collide across parallel workers.
const productAttributeName = getFakeAttribute().name;
const productAttributeTerm = getFakeAttribute().name;

let product1Id: number,
	product2Id: number,
	product3Id: number,
	product1Slug: string,
	productTag1Id: number,
	productTag2Id: number,
	productTag3Id: number,
	attributeId: number;

test.describe(
	'Browse product tags and attributes from the product page',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		const product1 = getFakeProduct();
		const product2 = getFakeProduct();
		const product3 = getFakeProduct();

		test.beforeAll( async ( { restApi } ) => {
			// add product tags
			await restApi
				.post( `${ WC_API_PATH }/products/tags`, {
					name: productTagName1,
				} )
				.then( ( response ) => {
					productTag1Id = response.data.id;
				} );
			await restApi
				.post( `${ WC_API_PATH }/products/tags`, {
					name: productTagName2,
				} )
				.then( ( response ) => {
					productTag2Id = response.data.id;
				} );
			await restApi
				.post( `${ WC_API_PATH }/products/tags`, {
					name: productTagName3,
				} )
				.then( ( response ) => {
					productTag3Id = response.data.id;
				} );

			// add product attribute
			await restApi
				.post( `${ WC_API_PATH }/products/attributes`, {
					name: productAttributeName,
					has_archives: true,
				} )
				.then( ( response ) => {
					attributeId = response.data.id;
				} );

			// add product attribute term
			await restApi.post(
				`${ WC_API_PATH }/products/attributes/${ attributeId }/terms`,
				{
					name: productAttributeTerm,
				}
			);

			// add products
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					...product1,
					tags: [
						{ id: productTag1Id },
						{
							id: productTag2Id,
						},
						{
							id: productTag3Id,
						},
					],
					attributes: [
						{
							id: attributeId,
							visible: true,
							options: [ productAttributeTerm ],
						},
					],
				} )
				.then( ( response ) => {
					product1Id = response.data.id;
					product1Slug = response.data.slug;
				} );
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					...product2,
					tags: [
						{ id: productTag1Id },
						{
							id: productTag2Id,
						},
					],
					attributes: [
						{
							id: attributeId,
							visible: true,
							options: [ productAttributeTerm ],
						},
					],
				} )
				.then( ( response ) => {
					product2Id = response.data.id;
				} );
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					...product3,
					tags: [ { id: productTag1Id } ],
					attributes: [
						{
							id: attributeId,
							visible: true,
							options: [ productAttributeTerm ],
						},
					],
				} )
				.then( ( response ) => {
					product3Id = response.data.id;
				} );
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.post( `${ WC_API_PATH }/products/batch`, {
				delete: [ product1Id, product2Id, product3Id ],
			} );
			await restApi.post( `${ WC_API_PATH }/products/tags/batch`, {
				delete: [ productTag1Id, productTag2Id, productTag3Id ],
			} );
			await restApi.post( `${ WC_API_PATH }/products/attributes/batch`, {
				delete: [ attributeId ],
			} );

			const pages = await restApi.get( `${ WP_API_PATH }/pages` );

			for ( const page of pages.data ) {
				if ( page.title.rendered === pageTitle ) {
					await restApi.delete(
						`${ WP_API_PATH }/pages/${ page.id }`,
						{
							data: {
								force: true,
							},
						}
					);
				}
			}
		} );

		test( 'should see shop catalog with all its products', async ( {
			page,
		} ) => {
			await page.goto( 'shop/' );
			await expect(
				page.getByRole( 'heading', { name: 'Shop' } )
			).toBeVisible();
			await expect(
				page.locator( '.woocommerce-ordering' )
			).toBeVisible();

			const addToCart = page.getByRole( 'add_to_cart_button' );
			for ( let i = 0; i < addToCart.count(); ++i )
				await expect( addToCart.nth( i ) ).toBeVisible();

			const productPrice = page.getByRole( 'woocommerce-Price-amount' );
			for ( let i = 0; i < productPrice.count(); ++i )
				await expect( productPrice.nth( i ) ).toBeVisible();

			const productTitle = page.getByRole(
				'woocommerce-loop-product__title'
			);
			for ( let i = 0; i < productTitle.count(); ++i )
				await expect( productTitle.nth( i ) ).toBeVisible();

			const productImage = page.getByRole( 'wp-post-image' );
			for ( let i = 0; i < productImage.count(); ++i )
				await expect( productImage.nth( i ) ).toBeVisible();
		} );

		test( 'should see and sort tags page with all the products', async ( {
			page,
		} ) => {
			// Navigate straight to the product by slug. Going through the
			// shared, date-sorted shop listing is parallel-fragile: other
			// workers' products can push this one onto a later page where the
			// click would fail.
			await page.goto( `product/${ product1Slug }` );
			await page.getByRole( 'link', { name: productTagName1 } ).click();
			await expect(
				page.getByRole( 'heading', { name: productTagName1 } )
			).toBeVisible();
			await expect(
				page.getByText(
					new RegExp( `Products tagged .*${ productTagName1 }.*` )
				)
			).toBeVisible();
			await expect(
				page.getByText( 'Showing all 3 results' )
			).toBeVisible();
		} );

		test( 'should see and sort attributes page with all its products', async ( {
			page,
			baseURL,
		} ) => {
			// the api setting for enabling attribute term page doesn't apply for some reason
			// workaround for the change to take effect is to just update via the settings ui.
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=products&section=advanced'
			);

			const attributeLookupCheckbox = page.locator(
				'#woocommerce_attribute_lookup_enabled'
			);
			await expect( attributeLookupCheckbox ).toBeVisible();

			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( ! ( await attributeLookupCheckbox.isChecked() ) ) {
				await attributeLookupCheckbox.click();
				await page.locator( 'text=Save changes' ).click();
				await expect(
					page
						.locator( '#message' )
						.getByText( 'Your settings have been saved' )
				).toBeVisible();
			}

			await expect( attributeLookupCheckbox ).toBeChecked();

			// wc_create_attribute() only queues the attribute-archive rewrite
			// rules flush as a WP-Cron event, which doesn't run in the test env,
			// so the term archive 404s. Set WooCommerce's own flush flag; it is
			// applied on the next request's `init` (the product page load below).
			await setOption(
				request,
				baseURL || '',
				'woocommerce_queue_flush_rewrite_rules',
				'yes'
			);

			await page.goto( `product/${ product1Slug }` );

			await page
				.getByRole( 'tab', { name: 'Additional information' } )
				.click();
			await page
				.locator(
					'.woocommerce-product-attributes-item__value > p > a',
					{
						hasText: productAttributeTerm,
					}
				)
				.click();
			await expect(
				page.getByRole( 'heading', { name: productAttributeTerm } )
			).toBeVisible();
			await expect(
				page.locator( '.woocommerce-breadcrumb' )
			).toContainText(
				` / Product ${ productAttributeName } / ${ productAttributeTerm }`
			);
			await expect(
				page.getByText( 'Showing all 3 results' )
			).toBeVisible();
		} );

		test( 'can see products showcase', async ( { page } ) => {
			// create as a merchant a new page with Product Collection block
			await goToPageEditor( { page } );
			await fillPageTitle( page, pageTitle );
			await insertBlockByShortcut( page, 'Product Collection' );
			const canvas = await getCanvas( page );

			// Product Collection requires choosing some collection.
			await canvas
				.locator(
					'[data-type="woocommerce/product-collection"] .components-placeholder'
				)
				.getByRole( 'button', {
					name: 'create your own',
				} )
				.click();

			await publishPage( page, pageTitle );

			// go to created page with products showcase
			await page.goto( 'product-showcase' );
			await expect(
				page.getByRole( 'heading', { name: pageTitle } )
			).toBeVisible();
			expect(
				await page
					.getByRole( 'button', { name: 'Add to cart' } )
					.count()
			).toBeGreaterThan( 0 );

			const productPrice = page.locator( '.woocommerce-Price-amount' );
			for ( let i = 0; i < productPrice.count(); ++i )
				await expect( productPrice.nth( i ) ).toBeVisible();

			const productTitle = page.locator(
				'.woocommerce-loop-product__title'
			);
			for ( let i = 0; i < productTitle.count(); ++i )
				await expect( productTitle.nth( i ) ).toBeVisible();

			const productImage = page.locator( '.wp-post-image' );
			for ( let i = 0; i < productImage.count(); ++i )
				await expect( productImage.nth( i ) ).toBeVisible();
		} );
	}
);
