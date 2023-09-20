/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';

const product = {
	name: 'Protected Product',
	slug: 'protected-product',
	password: 'password',
};
test.describe( 'Single Product Template', () => {
	let id: null | string = null;
	test.beforeEach( async ( { admin, page } ) => {
		await admin.visitAdminPage( `/post-new.php?post_type=product` );

		const input = page.locator( '#title' );
		await input.fill( product.name );
		await page.getByRole( 'button', { name: 'Edit visibility' } ).click();

		await page.locator( '#visibility-radio-password' ).click();
		await page.locator( '#post_password' ).fill( product.password );
		await page.waitForResponse( ( response ) =>
			response.url().includes( 'admin-ajax.php' )
		);
		await page.locator( '#publish.button-primary' ).click();
		await page.waitForSelector(
			'#woocommerce-product-updated-message-view-product__link'
		);
		const url = new URL( page.url() );
		const queryParams = new URLSearchParams( url.search );
		id = queryParams.get( 'post' );
	} );

	test.afterAll( async () => {
		await cli(
			`npm run wp-env run tests-cli -- wp post delete ${ id } --force`
		);
	} );

	test.describe(
		`should render a password input when the product is protected `,
		() =>
			test( 'add product specific classes to the body', async ( {
				page,
			} ) => {
				await page.goto( `/product/${ product.slug }` );
				const placeholder = page.getByText(
					'This content is password protected. To view it please enter your password below:'
				);

				await expect( placeholder ).toBeVisible();

				await page.getByLabel( 'Password' ).fill( 'password' );

				await page.getByRole( 'button', { name: 'Enter' } ).click();

				await expect( placeholder ).toBeHidden();
			} )
	);
} );
