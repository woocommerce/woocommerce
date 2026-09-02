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
	EDITOR_INNER_BLOCKS = {
		simple: [
			'Product Stock Indicator',
			'Product Quantity (Beta)',
			'Add to Cart Button',
		],
		external: [ 'Add to Cart Button' ],
		variable: [
			'Variation Selector (Beta)',
			'Variation Selector: Attribute Name (Beta)',
			'Variation Selector: Template (Beta)',
			'Variation Description (Beta)',
			'Product Stock Indicator',
			'Product Quantity (Beta)',
			'Add to Cart Button',
		],
		grouped: [
			'Grouped Product Selector (Beta)',
			'Grouped Product: Template (Beta)',
			'Grouped Product: Item Selector (Beta)',
			'Grouped Product: Item Label (Beta)',
			'Product Price',
		],
	};

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

		await expect(
			addToCartWithOptionsBlock.locator( '.components-spinner' )
		).toHaveCount( 0 );
	}

	async expectEditorInnerBlocks(
		productType: 'simple' | 'external' | 'grouped' | 'variable'
	) {
		const blockNames = this.EDITOR_INNER_BLOCKS[ productType ];
		const addToCartWithOptionsBlock = await this.editor.getBlockByName(
			this.BLOCK_SLUG
		);
		for ( const blockName of blockNames ) {
			await expect(
				addToCartWithOptionsBlock
					.getByLabel( `Block: ${ blockName }` )
					.first()
			).toBeVisible();
		}
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

	async setVariationSelectorAttributes( {
		optionStyle,
		autoselect,
		disabledAttributesAction,
	}: {
		optionStyle?: 'chips' | 'dropdown';
		autoselect?: boolean;
		disabledAttributesAction?: 'disable' | 'hide';
	} = {} ) {
		const page = this.editor.page;

		await this.switchProductType( 'Variable product' );
		await page.getByRole( 'tab', { name: 'Block' } ).click();

		// Verify inner blocks have loaded.
		await expect(
			this.editor.canvas
				.getByLabel( 'Block: Variation Selector: Template (Beta)' )
				.first()
		).toBeVisible();

		const attributeTemplateBlock = await this.editor.getBlockByName(
			'woocommerce/add-to-cart-with-options-variation-selector-attribute'
		);
		await this.editor.selectBlocks( attributeTemplateBlock.first() );

		// Option style attribute (inner block titles: Chips, Dropdown).
		if ( optionStyle ) {
			const styleLabel =
				optionStyle === 'dropdown' ? 'Dropdown' : 'Chips';
			const optionStyleInput = page.getByRole( 'radio', {
				name: styleLabel,
			} );
			await optionStyleInput.click();
		}

		// Auto-select attribute.
		if ( typeof autoselect === 'boolean' ) {
			const autoselectInput = page.getByRole( 'checkbox', {
				name: 'Auto-select when only one option is available',
			} );
			await autoselectInput.setChecked( autoselect );
		}

		// Invalid options attribute.
		if ( disabledAttributesAction ) {
			const invalidOptionsLabel =
				disabledAttributesAction === 'disable'
					? 'Grayed-out'
					: 'Hidden';
			const invalidOptionsRadio = page
				.getByLabel( 'Invalid options' )
				.getByRole( 'radio', { name: invalidOptionsLabel } );
			await invalidOptionsRadio.click();
		}
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

	async selectVariationSelectorOptions(
		attributeName: string,
		attributeValue: string,
		optionStyle: 'chips' | 'dropdown'
	) {
		if ( optionStyle === 'dropdown' ) {
			const select = this.page.getByLabel( attributeName, {
				exact: true,
			} );
			if ( attributeValue !== '' ) {
				await expect(
					select.getByRole( 'option', {
						name: attributeValue,
						exact: true,
					} )
				).toBeAttached();
			}
			await select.selectOption( attributeValue );
			return;
		}
		const group = this.page.getByRole( 'radiogroup', {
			name: attributeName,
		} );
		if ( attributeValue !== '' ) {
			await group
				.getByRole( 'radio', { name: attributeValue, exact: true } )
				.click();
		} else {
			const selected = group.getByRole( 'radio', { checked: true } );
			await selected.click();
		}
	}

	async expectVariationSelectorOptions(
		productAttributes: {
			name: string;
			options: string[];
			variation: boolean;
			visible: boolean;
		}[],
		expectedValues: Record< string, string > = {},
		optionStyle: 'chips' | 'dropdown'
	) {
		for ( let {
			name: attributeName,
			options: attributeValues,
		} of productAttributes ) {
			if ( optionStyle === 'dropdown' ) {
				const attributeNameLocator = this.page.getByLabel(
					attributeName,
					{
						exact: true,
					}
				);
				let expectedValue: string;
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
				continue;
			}
			const group = this.page.getByRole( 'radiogroup', {
				name: attributeName,
			} );
			if (
				attributeName in expectedValues &&
				expectedValues[ attributeName ] !== ''
			) {
				attributeValues = attributeValues.filter(
					( item ) => item !== expectedValues[ attributeName ]
				);
				await expect(
					group.getByRole( 'radio', {
						name: String( expectedValues[ attributeName ] ),
						exact: true,
					} )
				).toBeChecked();
			}
			if ( attributeValues.length ) {
				for ( const attributeValue of attributeValues ) {
					const radio = group.getByRole( 'radio', {
						name: attributeValue,
						exact: true,
					} );
					if ( ( await radio.count() ) > 0 ) {
						await expect( radio ).not.toBeChecked();
					}
				}
			}
		}
	}
}

export default AddToCartWithOptionsPage;
