/* eslint-disable jest/no-export, jest/no-disabled-tests */

const faker = require("faker");

/**
 * Internal dependencies
 */
const { merchant } = require('@woocommerce/e2e-utils');

/**
 * Add shipping class(es) by filling up and submitting the 'Add shipping class' form
 */
const addShippingClasses = async (shippingClasses) => {
	for (const { name, slug, description } of shippingClasses) {
		await expect(page).toClick('.wc-shipping-class-add')
		await expect(page).toFill('.editing:last-child [data-attribute="name"]', name)
		await expect(page).toFill('.editing:last-child [data-attribute="slug"]', slug)
		await expect(page).toFill('.editing:last-child [data-attribute="description"]', description)
	}

	await expect(page).toClick('.wc-shipping-class-save')
}

/**
 * Verify that the specified shipping classes were saved
 */
const verifySavedShippingClasses = async (savedShippingClasses) => {
	for (const { name, slug, description } of savedShippingClasses) {
		const row = await expect(page).toMatchElement('.wc-shipping-class-rows tr', { text: slug })

		await expect(row).toMatchElement('.wc-shipping-class-name', name)
		await expect(row).toMatchElement('.wc-shipping-class-slug', slug)
		await expect(row).toMatchElement('.wc-shipping-class-description', description)
	}
}

/**
 * Generate an array of shipping class objects to be used as test data.
 */
const generateShippingClassesTestData = (count = 1) => {
	const shippingClasses = []

	while (count--) {
		shippingClasses.push({
			name: faker.lorem.words(),
			slug: faker.lorem.slug(),
			description: faker.lorem.sentence()
		})
	}

	return shippingClasses
}

const runAddShippingClassesTest = () => {
	describe('Merchant can add a shipping class', () => {
		beforeAll(async () => {
			await merchant.login();

			// Go to Shipping Classes page
			await merchant.openSettings('shipping', 'classes');
		});

		it('can add a new shipping class', async () => {
			const shippingClass = generateShippingClassesTestData()

			await addShippingClasses(shippingClass)
			await verifySavedShippingClasses(shippingClass)
		});

		it('can add multiple shipping classes at once', async () => {
			const shippingClasses = generateShippingClassesTestData(2)

			await addShippingClasses(shippingClasses)
			await verifySavedShippingClasses(shippingClasses)
		});

		it('can automatically generate slug', async () => {
			const input = [{
				name: faker.lorem.words(3),
				slug: '',
				description: ''
			}]
			const expectedShippingClass = [{
				name: input.name,
				slug: faker.helpers.slugify(input.name),
				description: ''
			}]

			await addShippingClasses(input)
			await verifySavedShippingClasses(expectedShippingClass)
		});
	});
};

module.exports = runAddShippingClassesTest;
