/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';

const uq = faker.string.alphanumeric( 5 );

[
	{
		name: `Small Items ${ uq }`,
		slug: `small-items-${ uq }-slug`,
		expectedSlug: `small-items-${ uq }-slug`,
		description: `Small items that don't cost much to ship ${ uq }`,
		testTitle: 'can add a shipping class with an unique slug',
	},
	{
		name: `Poster Pack ${ uq }`,
		slug: '',
		expectedSlug: `poster-pack-${ uq }`,
		description: `Posters, stickers, and other flat items ${ uq }`,
		testTitle: 'can add a shipping class with an auto-generated slug',
	},
].forEach( ( { testTitle, name, slug, expectedSlug, description } ) => {
	const test = baseTest.extend( {
		storageState: ADMIN_STATE_PATH,
		page: async ( { restApi, page }, use ) => {
			await use( page );

			// Cleanup
			const allShippingClasses = await restApi.get(
				`${ WC_API_PATH }/products/shipping_classes`
			);
			for ( const shippingClass of allShippingClasses.data ) {
				if ( shippingClass.slug === expectedSlug ) {
					await restApi.delete(
						`${ WC_API_PATH }/products/shipping_classes/${ shippingClass.id }`,
						{ force: true }
					);
				}
			}
		},
	} );

	test( testTitle, { tag: [ tags.SERVICES ] }, async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes'
		);
		await page.getByRole( 'link', { name: 'Add shipping class' } ).click();
		await page
			.getByPlaceholder( 'e.g. Heavy', { exact: true } )
			.fill( name );
		await page
			.getByPlaceholder( 'e.g. heavy-packages', { exact: true } )
			.fill( slug );
		await page
			.getByPlaceholder(
				'e.g. For heavy items requiring higher postage',
				{ exact: true }
			)
			.fill( description );

		await page.getByRole( 'button', { name: 'Create' } ).click();

		await expect( page.getByText( name, { exact: true } ) ).toBeVisible();
		await expect( page.locator( `text=${ expectedSlug }` ) ).toBeVisible();
		await expect(
			page.getByText( description, { exact: true } )
		).toBeVisible();
	} );
} );
