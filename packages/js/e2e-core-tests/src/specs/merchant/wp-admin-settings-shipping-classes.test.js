/**
 * Internal dependencies
 */
const { merchant, withRestApi } = require( '@woocommerce/e2e-utils' );

const runAddShippingClassesTest = () => {
	describe( 'Merchant can add shipping classes', () => {
		beforeAll( async () => {
			await merchant.login();

			// Go to Shipping Classes page
			await merchant.openSettings( 'shipping', 'classes' );
		} );

		afterAll( async () => {
			await withRestApi.deleteAllShippingClasses( false );
		} );

		it( 'can add shipping classes', async () => {
			const shippingClassSlug = {
				name: 'Small Items',
				slug: 'small-items',
				description: "Small items that don't cost much to ship.",
			};
			const shippingClassNoSlug = {
				name: 'Poster Pack',
				slug: '',
				description: '',
			};
			const shippingClasses = [ shippingClassSlug, shippingClassNoSlug ];

			// Add shipping classes
			for ( const { name, slug, description } of shippingClasses ) {
				await expect( page ).toClick( '.wc-shipping-class-add' );
				await expect( page ).toFill(
					'.editing:last-child [data-attribute="name"]',
					name
				);
				await expect( page ).toFill(
					'.editing:last-child [data-attribute="slug"]',
					slug
				);
				await expect( page ).toFill(
					'.editing:last-child [data-attribute="description"]',
					description
				);
			}
			await expect( page ).toClick( '.wc-shipping-class-save' );

			// Set the expected auto-generated slug
			shippingClassNoSlug.slug = 'poster-pack';

			// Verify that the specified shipping classes were saved
			for ( const { name, slug, description } of shippingClasses ) {
				const row = await expect( page ).toMatchElement(
					'.wc-shipping-class-rows tr',
					{
						text: slug,
						timeout: 50000,
					}
				);

				await expect( row ).toMatchElement(
					'.wc-shipping-class-name',
					name
				);
				await expect( row ).toMatchElement(
					'.wc-shipping-class-description',
					description
				);
			}
		} );
	} );
};

// eslint-disable-next-line jest/no-export
module.exports = runAddShippingClassesTest;
