/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import {
	expect,
	Editor,
	Admin,
	BLOCK_THEME_SLUG,
} from '@woocommerce/e2e-utils';

class AddToCartWithOptionsPage {
	private page: Page;
	private admin: Admin;
	private editor: Editor;
	BLOCK_SLUG = 'woocommerce/add-to-cart-with-options';
	BLOCK_NAME = 'Add to Cart + Options (Beta)';

	constructor( {
		page,
		admin,
		editor,
	}: {
		page: Page;
		admin: Admin;
		editor: Editor;
	} ) {
		this.page = page;
		this.admin = admin;
		this.editor = editor;
	}

	async switchProductType( productType: string ) {
		await this.page.getByRole( 'tab', { name: 'Template' } ).click();
		const productTypePanel = this.page.getByRole( 'button', {
			name: 'Product Type',
			exact: true,
		} );
		if (
			( await productTypePanel.getAttribute( 'aria-expanded' ) ) !==
			'true'
		) {
			await productTypePanel.click();
		}
		await this.page
			.getByLabel( 'Type switcher' )
			.selectOption( { label: productType } );

		const addToCartWithOptionsBlock = await this.editor.getBlockByName(
			this.BLOCK_SLUG
		);

		await addToCartWithOptionsBlock
			.getByLabel( 'Loading the Add to Cart + Options template part' )
			.waitFor( {
				state: 'hidden',
			} );

		await addToCartWithOptionsBlock
			.locator( '.components-spinner' )
			.waitFor( {
				state: 'hidden',
			} );
	}

	async insertParagraphInTemplatePart( content: string ) {
		const parentBlock = await this.editor.getBlockByName( this.BLOCK_SLUG );
		const parentClientId =
			( await parentBlock.getAttribute( 'data-block' ) ) ?? '';

		// Add to Cart is a dynamic block, so we need to wait for it to be
		// ready. If we don't do that, it might clear the paragraph we're
		// inserting below (depending on the test execution speed).
		await parentBlock.getByText( /^(Add to cart|Buy product)$/ ).waitFor();

		await this.editor.insertBlock(
			{
				name: 'core/paragraph',
				attributes: {
					content,
				},
			},
			{ clientId: parentClientId }
		);
	}

	async updateAddToCartWithOptionsBlock() {
		const addToCartFormBlock = await this.editor.getBlockByName(
			'woocommerce/add-to-cart-form'
		);
		if ( await addToCartFormBlock.isVisible() ) {
			await this.editor.selectBlocks( addToCartFormBlock );

			await this.page
				.getByRole( 'button', {
					name: 'Use the Add to Cart + Options block',
				} )
				.click();
		}
	}

	async updateSingleProductTemplate() {
		await this.admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//single-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await this.updateAddToCartWithOptionsBlock();
	}

	async createPostWithProductBlock( product: string, variation?: string ) {
		await this.admin.createNewPost();
		await this.editor.insertBlock( { name: 'woocommerce/single-product' } );
		const singleProductBlock = await this.editor.getBlockByName(
			'woocommerce/single-product'
		);

		await singleProductBlock
			.locator( `input[type="radio"][value="${ product }"]` )
			.nth( 0 )
			.click();

		if ( variation ) {
			await singleProductBlock
				.locator( `input[type="radio"][value="${ variation }"]` )
				.nth( 0 )
				.click();
		}

		await singleProductBlock.getByText( 'Done' ).click();

		await this.updateAddToCartWithOptionsBlock();

		await this.editor.publishAndVisitPost();
	}

	async selectVariationSelectorOptionsBlockAttribute(
		attributeName: string,
		attributeValue: string,
		optionStyle: 'Pills' | 'Dropdown'
	) {
		if ( optionStyle === 'Dropdown' ) {
			await this.page
				.getByLabel( attributeName )
				.selectOption( attributeValue );
			return;
		}
		if ( attributeValue !== '' ) {
			await this.page
				.getByLabel( attributeName )
				.getByText( attributeValue )
				.click();
			return;
		}
		await this.page
			.getByLabel( attributeName )
			.locator( 'label:has(:checked)' )
			.click();
	}

	async expectSelectedAttributes(
		productAttributes: {
			name: string;
			options: string[];
			variation: boolean;
			visible: boolean;
		}[],
		expectedValues: Record< string, string | RegExp > = {},
		optionStyle: 'Pills' | 'Dropdown'
	) {
		for ( let {
			name: attributeName,
			options: attributeValues,
		} of productAttributes ) {
			const attributeNameLocator = this.page.getByLabel( attributeName, {
				exact: true,
			} );
			if ( optionStyle === 'Dropdown' ) {
				let expectedValue: string | RegExp;
				if (
					attributeName in expectedValues &&
					expectedValues[ attributeName ] !== ''
				) {
					expectedValue = expectedValues[ attributeName ];
				} else {
					expectedValue = '';
				}
				await expect( attributeNameLocator ).toHaveValue(
					expectedValue
				);
				return;
			}
			if (
				attributeName in expectedValues &&
				expectedValues[ attributeName ] !== ''
			) {
				attributeValues = attributeValues.filter(
					( item ) => item !== expectedValues[ attributeName ]
				); // Omit attributeName
				await expect(
					attributeNameLocator.getByLabel(
						expectedValues[ attributeName ],
						{ exact: true }
					)
				).toBeChecked();
			}
			if ( attributeValues.length ) {
				for ( const attributeValue of attributeValues ) {
					await expect(
						attributeNameLocator.getByLabel( attributeValue, {
							exact: true,
						} )
					).not.toBeChecked();
				}
			}
		}
	}
}

export default AddToCartWithOptionsPage;
