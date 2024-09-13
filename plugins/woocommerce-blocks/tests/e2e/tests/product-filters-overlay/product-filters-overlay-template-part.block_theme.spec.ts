/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

const templatePartData = {
	selectors: {
		frontend: {},
		editor: {
			blocks: {
				activeFilters: {
					title: 'Active (Experimental)',
					blockLabel: 'Block: Active (Experimental)',
				},
				productFilters: {
					title: 'Product Filters (Experimental)',
					blockLabel: 'Block: Product Filters (Experimental)',
				},
				filterOptions: {
					title: 'Filter Options',
					blockLabel: 'Block: Filter Options',
				},
				productFiltersOverlayNavigation: {
					title: 'Overlay Navigation (Experimental)',
					name: 'woocommerce/product-filters-overlay-navigation',
					blockLabel: 'Block: Overlay Navigation (Experimental)',
				},
			},
		},
	},
	slug: 'product-filters',
	productPage: '/product/hoodie/',
};

test.describe( 'Filters Overlay Template Part', () => {
	test.beforeEach( async ( { admin, requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-enable-experimental-features'
		);
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
			postId: 'woocommerce/woocommerce//product-filters-overlay',
			canvas: 'edit',
		} );
	} );

	test( 'should be visible in the template parts list', async ( {
		page,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
		} );
		const block = page
			.getByLabel( 'Patterns content' )
			.getByText( 'Filters Overlay' )
			.and( page.getByRole( 'button' ) );
		await expect( block ).toBeVisible();
	} );

	test( 'should render the correct inner blocks', async ( { editor } ) => {
		const productFiltersTemplatePart = editor.canvas
			.locator( '[data-type="core/template-part"]' )
			.filter( {
				has: editor.canvas.getByLabel(
					templatePartData.selectors.editor.blocks.activeFilters
						.blockLabel
				),
			} );

		await expect( productFiltersTemplatePart ).toBeVisible();
	} );
} );
