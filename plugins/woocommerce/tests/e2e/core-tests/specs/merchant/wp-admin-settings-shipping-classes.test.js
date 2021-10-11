/**
 * Internal dependencies
 */
const { merchant } = require('@woocommerce/e2e-utils');
const { lorem, helpers } = require('faker');

const runAddShippingClassesTest = () => {
	describe('Merchant can add shipping classes', () => {
		beforeAll(async () => {
			await merchant.login();

			// Go to Shipping Classes page
			await merchant.openSettings('shipping', 'classes');
		});

		it('can add shipping classes', async () => {
			const shippingClassSlug = {
				name: lorem.words(),
				slug: lorem.slug(),
				description: lorem.sentence()
			};
			const shippingClassNoSlug = {
				name: lorem.words(3),
				slug: '',
				description: lorem.sentence()
			};
			const shippingClasses = [shippingClassSlug, shippingClassNoSlug];

			// Add shipping classes
			for (const { name, slug, description } of shippingClasses) {
				await expect(page).toClick('.wc-shipping-class-add');
				await expect(page).toFill(
					'.editing:last-child [data-attribute="name"]',
					name
				);
				await expect(page).toFill(
					'.editing:last-child [data-attribute="slug"]',
					slug
				);
				await expect(page).toFill(
					'.editing:last-child [data-attribute="description"]',
					description
				);
			}
			await expect(page).toClick('.wc-shipping-class-save');

			// Set the expected auto-generated slug
			shippingClassNoSlug.slug = helpers.slugify(
				shippingClassNoSlug.name
			);

			// Verify that the specified shipping classes were saved
			for (const { name, slug, description } of shippingClasses) {
				const row = await expect(
					page
				).toMatchElement('.wc-shipping-class-rows tr', { text: slug, timeout: 50000 });

				await expect(row).toMatchElement(
					'.wc-shipping-class-name',
					name
				);
				await expect(row).toMatchElement(
					'.wc-shipping-class-description',
					description
				);
			}
		});
	});
};

module.exports = runAddShippingClassesTest;
