/**
 * External dependencies
 */
import {
	test as base,
	expect,
	wpCLI,
	TemplateCompiler,
	BLOCK_THEME_SLUG,
	flushMacrotask,
} from '@woocommerce/e2e-utils';

// The Cap is the only seeded product in this bucket: its reviews are rated 1
// and 2, averaging 1.5.
const blockData = {
	name: 'Filter by Rating',
	slug: 'woocommerce/rating-filter',
	urlSearchParamWhenFilterIsApplied: 'rating_filter=2',
};

// The Blocks E2E store seeds sixteen products, and the shop page lists them all
// on one page.
const UNFILTERED_PRODUCT_COUNT = 16;

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, provideTemplateCompiler ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_filters-with-product-collection'
		);
		await provideTemplateCompiler( compiler );
	},
} );

test.describe( `${ blockData.name } Block - with PHP classic template`, () => {
	test.beforeEach( async ( { admin, page, editor } ) => {
		await wpCLI(
			'option update wc_blocks_use_blockified_product_grid_block_as_template false'
		);

		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'rating-filter',
				heading: 'Filter By Rating',
			},
		} );

		await page.keyboard.press( 'Escape' );
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/shop' );
	} );

	test( 'filters the classic product template by rating', async ( {
		frontendUtils,
		page,
	} ) => {
		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);
		const products = legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' );
		const ratingCheckbox = page.getByRole( 'checkbox', {
			name: 'Rated 2 out of 5',
		} );

		await expect( products ).toHaveCount( UNFILTERED_PRODUCT_COUNT );
		await expect( ratingCheckbox ).toBeVisible();

		await ratingCheckbox.click();

		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);

		await expect( products ).toHaveCount( 1 );
		await expect(
			products.getByRole( 'heading', { name: 'Cap' } )
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
		await expect( productTitles ).toHaveCount( UNFILTERED_PRODUCT_COUNT );

		await page
			.getByRole( 'checkbox', { name: 'Rated 2 out of 5' } )
			.click();

		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);
		await expect( productTitles ).toHaveText( [ 'Cap' ] );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: template.type,
			canvas: 'edit',
		} );

		const ratingFilterControls = await editor.getBlockByName(
			blockData.slug
		);
		await expect( ratingFilterControls ).toBeVisible();
		await editor.selectBlocks( ratingFilterControls );
		await editor.openDocumentSettingsSidebar();
		await page.getByText( "Show 'Apply filters' button" ).click();
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/shop' );
		await expect( productTitles.first() ).toBeVisible();
		const ratingFilterCheckbox = page.getByRole( 'checkbox', {
			name: 'Rated 2 out of 5',
		} );
		await expect( ratingFilterCheckbox ).toBeVisible();
		await page.clock.pauseAt(
			( await page.evaluate( () => Date.now() ) ) + 1_000
		);
		const deferredBaseline = ( await productTitles.allTextContents() ).map(
			( title ) => title.trim()
		);
		// Greater than one, not merely non-empty: an unfiltered /shop must show more
		// than the single product the filter is about to leave behind. Asserting only
		// that it is non-empty passes on a shop page that arrived already filtered,
		// and then the post-filter assertion passes too, for the wrong reason.
		expect( deferredBaseline.length ).toBeGreaterThan( 1 );
		const deferredUrl = page.url();

		await ratingFilterCheckbox.click();
		await page.clock.runFor( 501 );
		await flushMacrotask( page );
		await expect( ratingFilterCheckbox ).toBeChecked();
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
		await expect( productTitles ).toHaveText( [ 'Cap' ] );
	} );
} );
