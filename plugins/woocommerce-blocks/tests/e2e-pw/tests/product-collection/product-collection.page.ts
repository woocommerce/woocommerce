/**
 * External dependencies
 */
import { Locator, Page } from '@playwright/test';
import { Editor, Admin } from '@wordpress/e2e-test-utils-playwright';

const SELECTORS = {
	productTemplate: '.wc-block-product-template',
	product: '.wc-block-product-template .wc-block-product',
	productImage: {
		inEditor: '[data-type="woocommerce/product-image"]',
		onFrontend: '[data-block-name="woocommerce/product-image"]',
	},
	productTitle: '.wp-block-post-title',
	productPrice: {
		inEditor: '[data-type="woocommerce/product-price"]',
		onFrontend: '[data-block-name="woocommerce/product-price"]',
	},
	addToCartButton: {
		inEditor: '[data-type="woocommerce/product-button"]',
		onFrontend: '[data-block-name="woocommerce/product-button"]',
	},
	pagination: {
		inEditor: '[data-type="core/query-pagination"]',
		onFrontend: '.wp-block-query-pagination',
	},
};

class ProductCollectionPage {
	private BLOCK_NAME = 'woocommerce/product-collection';
	private page: Page;
	private admin: Admin;
	private editor: Editor;
	productTemplate!: Locator;
	products!: Locator;
	productImages!: Locator;
	productTitles!: Locator;
	productPrices!: Locator;
	addToCartButtons!: Locator;
	pagination!: Locator;

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
			name: this.BLOCK_NAME,
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

	async addFilter(
		name:
			| 'Show Hand-picked Products'
			| 'Keyword'
			| 'Show Taxonomies'
			| 'Show Product Attributes'
	) {
		await this.page
			.getByRole( 'button', { name: 'Filters options' } )
			.click();
		await this.page.waitForTimeout( 500 );
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
		const sidebarSettings = await this.locateSidebarSettings();
		const inputField = await sidebarSettings.getByRole( 'spinbutton', {
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
		const sidebarSettings = await this.locateSidebarSettings();
		const orderByComboBox = await sidebarSettings.getByRole( 'combobox', {
			name: 'Order by',
		} );
		await orderByComboBox.selectOption( orderBy );
		await this.refreshLocators( 'editor' );
	}

	async setShowOnlyProductsOnSale( onSale: boolean ) {
		const sidebarSettings = await this.locateSidebarSettings();
		const input = sidebarSettings.getByLabel(
			'Show only products on sale'
		);
		if ( onSale ) {
			await input.check();
		} else {
			await input.uncheck();
		}
		await this.refreshLocators( 'editor' );
	}

	async setFilterComboboxValue( filterName: string, filterValue: string[] ) {
		const sidebarSettings = await this.locateSidebarSettings();
		const input = sidebarSettings.getByLabel( filterName );
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

		// Add new values.
		for ( const name of filterValue ) {
			await input.fill( name );
			await sidebarSettings
				.getByRole( 'option', { name } )
				.getByText( name )
				.click();
		}

		await this.refreshLocators( 'editor' );
	}

	async setKeyword( keyword: string ) {
		const sidebarSettings = await this.locateSidebarSettings();
		const input = sidebarSettings.getByLabel( 'Keyword' );
		await input.clear();
		await input.fill( keyword );
		// Timeout is needed because of debounce in the block.
		await this.page.waitForTimeout( 300 );
		await this.refreshLocators( 'editor' );
	}

	async setDisplaySettings( {
		itemsPerPage,
		offset,
		maxPageToShow,
	}: {
		itemsPerPage: number;
		offset: number;
		maxPageToShow: number;
		isOnFrontend?: boolean;
	} ) {
		// Select the block, so that toolbar is visible.
		const block = this.page
			.locator( `[data-type="${ this.BLOCK_NAME }"]` )
			.first();
		await this.editor.selectBlocks( block );

		// Open the display settings.
		await this.page
			.getByRole( 'button', { name: 'Display settings' } )
			.click();

		// Set the values.
		const displaySettingsContainer = await this.page.locator(
			'.wc-block-editor-product-collection__display-settings'
		);
		await displaySettingsContainer.getByLabel( 'Items per Page' ).click();
		await displaySettingsContainer
			.getByLabel( 'Items per Page' )
			.fill( itemsPerPage.toString() );
		await displaySettingsContainer.getByLabel( 'Offset' ).click();
		await displaySettingsContainer
			.getByLabel( 'Offset' )
			.fill( offset.toString() );
		await displaySettingsContainer.getByLabel( 'Max page to show' ).click();
		await displaySettingsContainer
			.getByLabel( 'Max page to show' )
			.fill( maxPageToShow.toString() );

		await this.page.click( 'body' );
		await this.refreshLocators( 'editor' );
	}

	async setProductAttribute( attribute: 'Color' | 'Size', value: string ) {
		await this.page.waitForLoadState( 'networkidle' );
		const sidebarSettings = await this.locateSidebarSettings();

		const productAttributesContainer = await sidebarSettings.locator(
			'.woocommerce-product-attributes'
		);

		// Whenever attributes filter is added, it fetched the attributes from the server.
		// So, we need to wait for the attributes to be fetched.
		await productAttributesContainer.getByLabel( 'Attributes' ).isEnabled();

		// If value is not visible, then toggle the attribute to make it visible.
		const isAttributeValueVisible =
			(
				await productAttributesContainer
					.getByLabel( value )
					.elementHandles()
			 ).length !== 0;
		if ( ! isAttributeValueVisible ) {
			await productAttributesContainer
				.locator( `li:has-text("${ attribute }")` )
				.click();
		}

		// Now, check the value.
		await productAttributesContainer.getByLabel( value ).check();
		await this.refreshLocators( 'editor' );
	}

	async setViewportSize( {
		width,
		height,
	}: {
		width: number;
		height: number;
	} ) {
		await this.page.setViewportSize( { width, height } );
	}

	/**
	 * Locators
	 */
	async locateSidebarSettings() {
		return await this.page.getByRole( 'region', {
			name: 'Editor settings',
		} );
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
			SELECTORS.productTemplate
		);
		this.products = await this.page
			.locator( SELECTORS.product )
			.locator( 'visible=true' );
		this.productImages = await this.page
			.locator( SELECTORS.productImage.inEditor )
			.locator( 'visible=true' );
		this.productTitles = await this.productTemplate
			.locator( SELECTORS.productTitle )
			.locator( 'visible=true' );
		this.productPrices = await this.page
			.locator( SELECTORS.productPrice.inEditor )
			.locator( 'visible=true' );
		this.addToCartButtons = await this.page
			.locator( SELECTORS.addToCartButton.inEditor )
			.locator( 'visible=true' );
		this.pagination = await this.page.getByRole( 'document', {
			name: 'Block: Pagination',
		} );
	}

	private async initializeLocatorsForFrontend() {
		this.productTemplate = await this.page.locator(
			SELECTORS.productTemplate
		);
		this.products = await this.page.locator( SELECTORS.product );
		this.productImages = await this.productTemplate.locator(
			SELECTORS.productImage.onFrontend
		);
		this.productTitles = await this.productTemplate.locator(
			SELECTORS.productTitle
		);
		this.productPrices = await this.productTemplate.locator(
			SELECTORS.productPrice.onFrontend
		);
		this.addToCartButtons = await this.productTemplate.locator(
			SELECTORS.addToCartButton.onFrontend
		);
		this.pagination = await this.page.locator(
			SELECTORS.pagination.onFrontend
		);
	}

	private async waitForProductsToLoad() {
		await this.page.waitForLoadState( 'networkidle' );
		// Wait for the product blocks to be loaded.
		await this.page.waitForSelector( SELECTORS.product );
		// Wait for the loading spinner to be detached.
		await this.page.waitForSelector( '.is-loading', { state: 'detached' } );
		await this.page.waitForLoadState( 'networkidle' );
	}
}

export default ProductCollectionPage;
