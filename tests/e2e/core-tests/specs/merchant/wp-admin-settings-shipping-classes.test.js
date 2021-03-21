/* eslint-disable jest/no-export, jest/no-disabled-tests */

import faker from 'faker'

/**
 * Internal dependencies
 */
const { merchant } = require('@woocommerce/e2e-utils');

/**
 * Add shipping class(es) by filling up and submitting the 'Add shipping class' form
 */
const addShippingClasses = async (...shippingClasses) => {
	for (const { name, slug, description } of shippingClasses) {
		await expect(page).toClick('.wc-shipping-class-add')
		await expect(page).toFill('.editing [data-attribute="name"]', name)
		await expect(page).toFill('.editing [data-attribute="slug"]', slug)
		await expect(page).toFill('.editing [data-attribute="description"]', description)
	}

	await expect(page).toClick('.wc-shipping-class-save')
}

/**
 * Verify that the specified shipping classes were saved
 */
const verifyThatShippingClassWasSaved = async (...savedShippingClasses) => {
	for (const { name, slug, description } of savedShippingClasses) {
		const savedClass = await expect(page).toMatchElement('.wc-shipping-class-rows tr', { text: slug })

		await expect(savedClass).toMatchElement('.wc-shipping-class-name', name)
		await expect(savedClass).toMatchElement('.wc-shipping-class-slug', slug)
		await expect(savedClass).toMatchElement('.wc-shipping-class-description', description)
	}
}

const runAddShippingClassesTest = () => {
	describe('Merchant can add a shipping class', () => {
		beforeAll(async () => {
			await merchant.login();

			// Go to Shipping Classes page
			await merchant.openSettings('shipping', 'classes');
		});

		// wip
		it('can add a new shipping class', async () => {
			const expectedShippingClasses = {
				name: faker.lorem.words(),
				slug: faker.lorem.slug(),
				description: faker.lorem.sentence()
			}

			await addShippingClasses(expectedShippingClasses)
			await verifyThatShippingClassWasSaved(expectedShippingClasses)
		});

		// it('can add multiple shipping classes at once', async () => {
		// 	// todo
		// });

		// it('can automatically generate slug', async () => {
		// 	// todo
		// });
	});
};

module.exports = runAddShippingClassesTest;
