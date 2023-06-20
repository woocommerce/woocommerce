/**
 * External dependencies
 */
import { Locator, Page } from '@playwright/test';
import { Editor, Admin } from '@wordpress/e2e-test-utils-playwright';

class ProductCollectionPage {
	private page: Page;
	private admin: Admin;
	private editor: Editor;
	productTemplate!: Locator;
	productImages!: Locator;
	productTitles!: Locator;
	productPrices!: Locator;
	addToCartButtons!: Locator;

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

	async createNewPostAndInsertBlock() {
		await this.admin.createNewPost();
		await this.editor.insertBlock( {
			name: 'woocommerce/product-collection',
		} );
		await this.refreshLocators( 'editor' );
	}

	async publishAndGoToFrontend() {
		await this.editor.publishPost();
		const url = new URL( this.page.url() );
		const postId = url.searchParams.get( 'post' );
		await this.page.goto( `/?p=${ postId }`, { waitUntil: 'networkidle' } );
		await this.refreshLocators( 'frontend' );
	}

	async addFilter( name: 'Show Hand-picked Products' | 'Keyword' ) {
		await this.page
			.getByRole( 'button', { name: 'Filters options' } )
			.click();
		await this.page
			.getByRole( 'menuitemcheckbox', {
				name,
			} )
			.click();
		await this.page
			.getByRole( 'button', { name: 'Filters options' } )
			.click();
	}

	async setNumberOfColumns( numberOfColumns: number ) {
		const inputField = await this.page.getByRole( 'spinbutton', {
			name: 'Columns',
		} );
		await inputField.fill( numberOfColumns.toString() );
	}

	async setOrderBy(
		orderBy:
			| 'title/asc'
			| 'title/desc'
			| 'date/desc'
			| 'date/asc'
			| 'popularity/desc'
			| 'rating/desc'
	) {
		const orderByComboBox = await this.page.getByRole( 'combobox', {
			name: 'Order by',
		} );
		await orderByComboBox.selectOption( orderBy );
		await this.refreshLocators( 'editor' );
	}

	async setShowOnlyProductsOnSale( onSale: boolean ) {
		const input = this.page.getByLabel( 'Show only products on sale' );
		if ( onSale ) {
			await input.check();
		} else {
			await input.uncheck();
		}
		await this.refreshLocators( 'editor' );
	}

	async setHandpickedProducts( names: string[] ) {
		const input = this.page.getByLabel( 'Pick some products' );
		await input.click();

		// Clear the input field.
		let numberOfAlreadySelectedProducts = await input.evaluate(
			( node ) => {
				return node.parentElement?.children.length;
			}
		);
		while ( numberOfAlreadySelectedProducts ) {
			// Backspace will remove token
			await this.page.keyboard.press( 'Backspace' );
			numberOfAlreadySelectedProducts--;
		}

		// Add new products.
		for ( const name of names ) {
			await input.fill( name );
			await this.page
				.getByRole( 'option', { name } )
				.getByText( name )
				.click();
		}

		await this.refreshLocators( 'editor' );
	}

	async setKeyword( keyword: string ) {
		const input = this.page.getByLabel( 'Keyword' );
		await input.clear();
		await input.fill( keyword );
		// Timeout is needed because of debounce in the block.
		await this.page.waitForTimeout( 300 );
		await this.refreshLocators( 'editor' );
	}

	/**
	 * Private methods to be used by the class.
	 */
	private async refreshLocators( currentUI: 'editor' | 'frontend' ) {
		await this.waitForProductsToLoad();

		if ( currentUI === 'editor' ) {
			await this.initializeLocatorsForEditor();
		} else {
			await this.initializeLocatorsForFrontend();
		}
	}

	private async initializeLocatorsForEditor() {
		this.productTemplate = await this.page.locator(
			'.wc-block-product-template'
		);
		this.productImages = await this.page
			.locator( '[data-type="woocommerce/product-image"]' )
			.locator( 'visible=true' );
		this.productTitles = await this.productTemplate
			.locator( '.wp-block-post-title' )
			.locator( 'visible=true' );
		this.productPrices = await this.page
			.locator( '[data-type="woocommerce/product-price"]' )
			.locator( 'visible=true' );
		this.addToCartButtons = await this.page
			.locator( '[data-type="woocommerce/product-button"]' )
			.locator( 'visible=true' );
	}

	private async initializeLocatorsForFrontend() {
		this.productTemplate = await this.page.locator(
			'.wc-block-product-template'
		);
		this.productImages = await this.productTemplate.locator(
			'[data-block-name="woocommerce/product-image"]'
		);
		this.productTitles = await this.productTemplate.locator(
			'.wp-block-post-title'
		);
		this.productPrices = await this.productTemplate.locator(
			'[data-block-name="woocommerce/product-price"]'
		);
		this.addToCartButtons = await this.productTemplate.locator(
			'[data-block-name="woocommerce/product-button"]'
		);
	}

	private async waitForProductsToLoad() {
		await this.page.waitForLoadState( 'networkidle' );
		// Wait for the product blocks to be loaded.
		await this.page.waitForSelector( '.wc-block-product' );
		// Wait for the loading spinner to be detached.
		await this.page.waitForSelector( '.is-loading', { state: 'detached' } );
		await this.page.waitForLoadState( 'networkidle' );
	}
}

export default ProductCollectionPage;
