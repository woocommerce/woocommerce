/**
 * External dependencies
 */
import {
	WC_API_PATH,
	WC_ADMIN_API_PATH,
} from '@woocommerce/e2e-utils-playwright';
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { expect, test as baseTest } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { getFakeProduct } from '../../utils/data';
import { toggleVariableProductTour } from '../../utils/tours';

const productAttributes = [
	{
		name: 'Colour',
		visible: true,
		variation: true,
		options: [ 'Red', 'Green' ],
	},
	{
		name: 'Size',
		visible: true,
		variation: true,
		options: [ 'Small', 'Medium' ],
	},
	{
		name: 'Logo',
		visible: true,
		variation: true,
		options: [ 'Woo', 'WordPress' ],
	},
];

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	page: async ( { page, restApi }, use ) => {
		await restApi.put( `${ WC_ADMIN_API_PATH }/options`, {
			woocommerce_task_list_reminder_bar_hidden: 'yes',
		} );

		await use( page );
	},
	product: async ( { restApi }, use ) => {
		let product = getFakeProduct( { type: 'variable' } );

		await restApi
			.post( `${ WC_API_PATH }/products`, product )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
			force: true,
		} );
	},
} );

async function goToAttributesTab( page: Page ) {
	// There is the chance we might click on the 'Attributes' tab too early. To
	// prevent that, we wait until the 'Variations' tab is hidden, which means
	// the tabs have been updated.
	// @see https://github.com/woocommerce/woocommerce/issues/53449.
	await expect(
		page
			.locator( '.attribute_tab' )
			.getByRole( 'link', { name: 'Variations' } )
	).toBeHidden();

	await test.step( 'Go to the "Attributes" tab.', async () => {
		const attributesTab = page
			.locator( '.attribute_tab' )
			.getByRole( 'link', { name: 'Attributes' } );

		await attributesTab.click();
	} );
}
async function addAttribute(
	page: Page,
	attributeName: string,
	attributeValues: string,
	firstAttribute: boolean
) {
	if ( ! firstAttribute ) {
		await test.step( "Click 'Add new'.", async () => {
			await page.getByRole( 'button', { name: 'Add new' } ).click();

			await expect(
				page
					.getByRole( 'heading', {
						name: 'New attribute',
					} )
					.first()
			).toBeVisible();
		} );
	}

	await test.step( `Type "${ attributeName }" in the "Attribute name" input field.`, async () => {
		await page
			.getByPlaceholder( 'e.g. length or weight' )
			.last()
			.type( attributeName );
	} );

	await test.step( `Type the attribute values "${ attributeValues }".`, async () => {
		await page
			.getByPlaceholder( 'Enter options for customers to choose from' )
			.last()
			.type( attributeValues );
	} );

	await test.step( `Expect "Visible on the product page" checkbox to be checked by default`, async () => {
		await expect(
			page
				.getByText( 'Visible on the product page' )
				.getByRole( 'checkbox' )
				.last()
		).toBeChecked();
	} );

	await test.step( `Expect "Used for variations" checkbox to be checked by default`, async () => {
		await expect(
			page
				.getByText( 'Used for variations' )
				.getByRole( 'checkbox' )
				.last()
		).toBeChecked();
	} );

	await test.step( 'Save attributes', async () => {
		const waitForSave = page.waitForResponse(
			( response ) =>
				response.url().includes( '/post.php' ) &&
				response.status() === 200
		);

		await page
			.getByRole( 'button', {
				name: 'Save attributes',
			} )
			.click();

		await waitForSave;
	} );

	await test.step( `Wait for the loading overlay to disappear.`, async () => {
		await expect( page.locator( '.blockOverlay' ) ).toBeHidden();
	} );
}

test( 'can add custom product attributes', async ( { page, product } ) => {
	await test.step( `Open "Edit product" page of product id ${ product.id }`, async () => {
		await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );
		await toggleVariableProductTour( page, false );
	} );

	await goToAttributesTab( page );

	for ( let i = 0; i < productAttributes.length; i++ ) {
		const attributeName = productAttributes[ i ].name;
		const attributeValues = productAttributes[ i ].options.join( ' | ' );

		await test.step( `Add the attribute "${ attributeName }" with values "${ attributeValues }"`, async () => {
			await addAttribute( page, attributeName, attributeValues, i === 0 );
		} );
	}

	await test.step( 'Update product', async () => {
		await page
			.locator( '#publishing-action' )
			.getByRole( 'button', { name: 'Update' } )
			.click();

		await expect(
			page.locator( '.notice-success', { name: 'Product updated' } )
		).toBeVisible();
	} );

	await goToAttributesTab( page );

	for ( let j = 0; j < productAttributes.length; j++ ) {
		const attributeName = productAttributes[ j ].name;
		const attributeValues = productAttributes[ j ].options.join( ' | ' );

		await test.step( `Expect "${ attributeName }" to appear on the list of saved attributes, and expand it.`, async () => {
			await page
				.getByRole( 'heading', {
					name: attributeName,
				} )
				.last()
				.click();
		} );

		await test.step( `Expect its details to be saved correctly`, async () => {
			await expect(
				page.getByPlaceholder( 'e.g. length or weight' ).nth( j )
			).toHaveValue( attributeName );
			await expect(
				page
					.getByPlaceholder(
						'Enter options for customers to choose from'
					)
					.nth( j )
			).toHaveValue( attributeValues );
			await expect(
				page
					.getByText( 'Visible on the product page' )
					.getByRole( 'checkbox' )
					.nth( j )
			).toBeChecked();
			await expect(
				page
					.getByText( 'Used for variations' )
					.getByRole( 'checkbox' )
					.nth( j )
			).toBeChecked();
		} );
	}
} );

test( 'can create attribute terms from the attributes modal', async ( {
	page,
	restApi,
} ) => {
	const attributeName = 'Fabric';
	const initialTerm = 'Cotton';
	const newTerm = `Linen-${ Date.now() }`;
	let attributeId: number | undefined;
	let createdProductId: number | undefined;

	try {
		await test.step( `Create a global attribute "${ attributeName }"`, async () => {
			const response = await restApi.post(
				`${ WC_API_PATH }/products/attributes`,
				{
					name: attributeName,
				}
			);

			attributeId = response.data.id;
		} );

		await test.step( 'Create a variable product with that global attribute', async () => {
			const response = await restApi.post( `${ WC_API_PATH }/products`, {
				...getFakeProduct( { type: 'variable' } ),
				attributes: [
					{
						id: attributeId,
						visible: true,
						variation: true,
						options: [ initialTerm ],
					},
				],
			} );

			createdProductId = response.data.id;
		} );

		await test.step( `Open "Edit product" page of product id ${ createdProductId }`, async () => {
			await page.goto(
				`wp-admin/post.php?post=${ createdProductId }&action=edit`
			);
			await toggleVariableProductTour( page, false );
		} );

		await goToAttributesTab( page );

		await test.step( `Expand the "${ attributeName }" attribute`, async () => {
			await page
				.getByRole( 'heading', {
					name: attributeName,
				} )
				.last()
				.click();
		} );

		await test.step( `Create a new term "${ newTerm }" from the modal`, async () => {
			await page.getByRole( 'button', { name: 'Create value' } ).click();

			const modal = page.locator(
				'.wc-backbone-modal-add-attribute-term .wc-backbone-modal-content'
			);
			await expect( modal ).toBeVisible();

			const modalHeader = modal.getByRole( 'heading', {
				name: 'Create value',
			} );
			await expect( modalHeader ).toBeVisible();

			const submitButton = page.getByRole( 'button', { name: 'OK' } );
			await expect( submitButton ).toBeDisabled();

			await modal.getByLabel( 'Name' ).fill( newTerm );
			await expect( submitButton ).toBeEnabled();
			await submitButton.click();
		} );

		await test.step( `Expect "${ newTerm }" to be in attribute values`, async () => {
			await expect(
				page.locator(
					'.woocommerce_attribute .attribute_values option',
					{
						hasText: newTerm,
					}
				)
			).toBeVisible();
		} );
	} finally {
		// eslint-disable-next-line playwright/no-conditional-in-test
		if ( createdProductId ) {
			await restApi.delete(
				`${ WC_API_PATH }/products/${ createdProductId }`,
				{
					force: true,
				}
			);
		}

		// eslint-disable-next-line playwright/no-conditional-in-test
		if ( attributeId ) {
			await restApi.delete(
				`${ WC_API_PATH }/products/attributes/${ attributeId }`,
				{
					force: true,
				}
			);
		}
	}
} );
