/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, test, expect, request } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { getFakeProduct, getFakeAttribute } from '../../utils/data';
import { setOption } from '../../utils/options';

// Both the attribute and its term must be unique: the attribute creates a
// global `pa_*` taxonomy and the term a global term within it, so a fixed name
// would collide across parallel workers.
const productAttributeName = getFakeAttribute().name;
const productAttributeTerm = getFakeAttribute().name;

const productIds: number[] = [];
let product1Slug = '';
let attributeId = 0;

test.describe(
	'Browse product tags and attributes from the product page',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		const product1 = getFakeProduct();
		const product2 = getFakeProduct();
		const product3 = getFakeProduct();

		test.beforeAll( async ( { restApi } ) => {
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
					attributes: [
						{
							id: attributeId,
							visible: true,
							options: [ productAttributeTerm ],
						},
					],
				} )
				.then( ( response ) => {
					productIds.push( response.data.id );
					product1Slug = response.data.slug;
				} );
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					...product2,
					attributes: [
						{
							id: attributeId,
							visible: true,
							options: [ productAttributeTerm ],
						},
					],
				} )
				.then( ( response ) => {
					productIds.push( response.data.id );
				} );
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					...product3,
					attributes: [
						{
							id: attributeId,
							visible: true,
							options: [ productAttributeTerm ],
						},
					],
				} )
				.then( ( response ) => {
					productIds.push( response.data.id );
				} );
		} );

		test.afterAll( async ( { restApi } ) => {
			const cleanupErrors: unknown[] = [];

			if ( productIds.length > 0 ) {
				try {
					await restApi.post( `${ WC_API_PATH }/products/batch`, {
						delete: productIds,
					} );
				} catch ( error ) {
					cleanupErrors.push( error );
				}
			}
			if ( attributeId > 0 ) {
				try {
					await restApi.post(
						`${ WC_API_PATH }/products/attributes/batch`,
						{
							delete: [ attributeId ],
						}
					);
				} catch ( error ) {
					cleanupErrors.push( error );
				}
			}

			if ( cleanupErrors.length > 0 ) {
				throw new AggregateError(
					cleanupErrors,
					'Failed to clean up Product Tags and Attributes fixtures.'
				);
			}
		} );

		test( 'can navigate from a product attribute to its archive', async ( {
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
	}
);
