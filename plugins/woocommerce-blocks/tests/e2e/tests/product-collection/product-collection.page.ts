/**
 * External dependencies
 */
import { Locator, Page } from '@playwright/test';
import { TemplateApiUtils } from '@woocommerce/e2e-utils';
import { Editor, Admin } from '@wordpress/e2e-test-utils-playwright';

export const SELECTORS = {
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
	onSaleControlLabel: 'Show only products on sale',
	inheritQueryFromTemplateControl:
		'.wc-block-product-collection__inherit-query-control',
};

class ProductCollectionPage {
	private BLOCK_NAME = 'woocommerce/product-collection';
	private page: Page;
	private admin: Admin;
	private editor: Editor;
	private templateApiUtils: TemplateApiUtils;
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
		templateApiUtils,
	}: {
		page: Page;
		admin: Admin;
		editor: Editor;
		templateApiUtils: TemplateApiUtils;
	} ) {
		this.page = page;
		this.admin = admin;
		this.editor = editor;
		this.templateApiUtils = templateApiUtils;
	}

	async createNewPostAndInsertBlock() {
		await this.admin.createNewPost( { legacyCanvas: true } );
		await this.editor.insertBlock( {
			name: this.BLOCK_NAME,
		} );
		await this.refreshLocators( 'editor' );
	}

	async publishAndGoToFrontend() {
		await this.editor.publishPost();
		const url = new URL( this.page.url() );
		const postId = url.searchParams.get( 'post' );
		await this.page.goto( `/?p=${ postId }`, { waitUntil: 'commit' } );
		await this.refreshLocators( 'frontend' );
	}

	async goToProductCatalogAndInsertBlock() {
		await this.templateApiUtils.revertTemplate(
			'woocommerce/woocommerce//archive-product'
		);

		await this.admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
		} );

		await this.editor.canvas.click( 'body' );

		await this.editor.insertBlock( {
			name: this.BLOCK_NAME,
		} );
		await this.editor.openDocumentSettingsSidebar();
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
		// We should refactor this code. We should not wait for timeout.
		// eslint-disable-next-line playwright/no-wait-for-timeout
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
		const inputField = sidebarSettings.getByRole( 'spinbutton', {
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
		const orderByComboBox = sidebarSettings.getByRole( 'combobox', {
			name: 'Order by',
		} );
		await orderByComboBox.selectOption( orderBy );
		await this.refreshLocators( 'editor' );
	}

	async setShowOnlyProductsOnSale(
		{
			onSale,
			isLocatorsRefreshNeeded,
		}: {
			onSale: boolean;
			isLocatorsRefreshNeeded?: boolean;
		} = {
			isLocatorsRefreshNeeded: true,
			onSale: true,
		}
	) {
		const sidebarSettings = await this.locateSidebarSettings();
		const input = sidebarSettings.getByLabel(
			SELECTORS.onSaleControlLabel
		);
		if ( onSale ) {
			await input.check();
		} else {
			await input.uncheck();
		}

		if ( isLocatorsRefreshNeeded ) await this.refreshLocators( 'editor' );
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
		// eslint-disable-next-line playwright/no-wait-for-timeout
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
		const displaySettingsContainer = this.page.locator(
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
		const sidebarSettings = await this.locateSidebarSettings();

		const productAttributesContainer = sidebarSettings.locator(
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

	async setInheritQueryFromTemplate( inheritQueryFromTemplate: boolean ) {
		const sidebarSettings = await this.locateSidebarSettings();
		const input = sidebarSettings.locator(
			`${ SELECTORS.inheritQueryFromTemplateControl } input`
		);
		if ( inheritQueryFromTemplate ) {
			await input.check();
		} else {
			await input.uncheck();
		}
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
		return this.page.getByRole( 'region', {
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
		this.productTemplate = this.page.locator( SELECTORS.productTemplate );
		this.products = this.page
			.locator( SELECTORS.product )
			.locator( 'visible=true' );
		this.productImages = this.page
			.locator( SELECTORS.productImage.inEditor )
			.locator( 'visible=true' );
		this.productTitles = this.productTemplate
			.locator( SELECTORS.productTitle )
			.locator( 'visible=true' );
		this.productPrices = this.page
			.locator( SELECTORS.productPrice.inEditor )
			.locator( 'visible=true' );
		this.addToCartButtons = this.page
			.locator( SELECTORS.addToCartButton.inEditor )
			.locator( 'visible=true' );
		this.pagination = this.page.getByRole( 'document', {
			name: 'Block: Pagination',
		} );
	}

	private async initializeLocatorsForFrontend() {
		this.productTemplate = this.page.locator( SELECTORS.productTemplate );
		this.products = this.page.locator( SELECTORS.product );
		this.productImages = this.productTemplate.locator(
			SELECTORS.productImage.onFrontend
		);
		this.productTitles = this.productTemplate.locator(
			SELECTORS.productTitle
		);
		this.productPrices = this.productTemplate.locator(
			SELECTORS.productPrice.onFrontend
		);
		this.addToCartButtons = this.productTemplate.locator(
			SELECTORS.addToCartButton.onFrontend
		);
		this.pagination = this.page.locator( SELECTORS.pagination.onFrontend );
	}

	private async waitForProductsToLoad() {
		// Wait for the product blocks to be loaded.
		await this.page.waitForSelector( SELECTORS.product );
		// Wait for the loading spinner to be detached.
		await this.page.waitForSelector( '.is-loading', { state: 'detached' } );
	}
}

export default ProductCollectionPage;
