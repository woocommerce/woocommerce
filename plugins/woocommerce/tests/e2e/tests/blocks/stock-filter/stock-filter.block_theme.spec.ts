/**
 * External dependencies
 */
import {
	test as base,
	expect,
	TemplateCompiler,
	wpCLI,
	BLOCK_THEME_SLUG,
} from '@woocommerce/e2e-utils';

export const blockData = {
	name: 'Filter by Stock',
	slug: 'woocommerce/stock-filter',
	urlSearchParamWhenFilterIsApplied: 'filter_stock_status=outofstock',
};

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_filters-with-product-collection'
		);
		await use( compiler );
	},
} );

test.describe( `${ blockData.name } Block - with PHP classic template`, () => {
	test.beforeEach( async ( { admin, page, editor } ) => {
		await wpCLI(
			'option update wc_blocks_use_blockified_product_grid_block_as_template false'
		);

		await page.reload();

		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'stock-filter',
				heading: 'Filter By Stock',
			},
		} );
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );
		await page.goto( '/shop' );
	} );

	test( 'filters the classic product template by stock status', async ( {
		frontendUtils,
		page,
	} ) => {
		const stockFilter = await frontendUtils.getBlockByName(
			'woocommerce/filter-wrapper'
		);
		await stockFilter.getByText( 'Out of Stock' ).click();

		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);

		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);
		const products = legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' );

		await expect( products ).toHaveCount( 1 );
		await expect(
			products.getByRole( 'heading', { name: 'T-Shirt with Logo' } )
		).toBeVisible();
	} );
} );

test.describe( `${ blockData.name } Block - with Product Collection`, () => {
	test( 'filters Product Collection automatically and defers changes until Apply when configured', async ( {
		page,
		admin,
		editor,
		templateCompiler,
	} ) => {
		await page.clock.install();
		const template = await templateCompiler.compile();
		const productTitles = page.locator(
			'.wp-block-woocommerce-product-template .wp-block-post-title'
		);

		await page.goto( '/shop' );
		await expect( productTitles.first() ).toBeVisible();
		const automaticBaseline = ( await productTitles.allTextContents() ).map(
			( title ) => title.trim()
		);
		expect( automaticBaseline ).not.toHaveLength( 0 );

		await page.getByText( 'Out of Stock' ).click();
		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);
		await expect( productTitles ).toHaveText( [ 'T-Shirt with Logo' ] );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: template.type,
			canvas: 'edit',
		} );

		const stockFilterControls = await editor.getBlockByName(
			blockData.slug
		);
		await expect( stockFilterControls ).toBeVisible();
		await editor.selectBlocks( stockFilterControls );
		await editor.openDocumentSettingsSidebar();
		await page.getByText( "Show 'Apply filters' button" ).click();
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/shop' );
		await expect( productTitles.first() ).toBeVisible();
		const outOfStockFilter = page.getByRole( 'checkbox', {
			name: 'Out of Stock',
		} );
		await expect( outOfStockFilter ).toBeVisible();
		await page.clock.pauseAt(
			( await page.evaluate( () => Date.now() ) ) + 1_000
		);
		const deferredBaseline = ( await productTitles.allTextContents() ).map(
			( title ) => title.trim()
		);
		expect( deferredBaseline ).not.toHaveLength( 0 );
		const deferredUrl = page.url();

		await outOfStockFilter.click();
		await page.clock.runFor( 501 );
		await page.evaluate(
			() =>
				new Promise< void >( ( resolve ) => {
					const channel = new MessageChannel();
					channel.port1.addEventListener(
						'message',
						() => {
							channel.port1.close();
							channel.port2.close();
							resolve();
						},
						{ once: true }
					);
					channel.port1.start();
					channel.port2.postMessage( null );
				} )
		);
		await expect( outOfStockFilter ).toBeChecked();
		const applyButton = page.getByRole( 'button', { name: 'Apply' } );
		await expect( applyButton ).toBeVisible();
		await expect( applyButton ).toBeEnabled();
		await expect( page ).toHaveURL( deferredUrl );
		await expect( productTitles ).toHaveText( deferredBaseline );

		await page.clock.resume();
		await applyButton.click();
		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);
		await expect( productTitles ).toHaveText( [ 'T-Shirt with Logo' ] );
	} );
} );
