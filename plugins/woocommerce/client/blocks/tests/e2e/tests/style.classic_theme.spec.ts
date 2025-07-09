/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import {
	expect,
	test as base,
	CLASSIC_THEME_SLUG,
} from '@woocommerce/e2e-utils';

const test = base.extend( {} );

async function getStylesheets( page: Page ) {
	const styleLocators = page.locator(
		'link[rel="stylesheet"][href*="assets/client/blocks"]:not([href*="wc-blocks.css"])'
	);
	return await styleLocators.evaluateAll( ( links ) =>
		links.map( ( link ) => ( link as HTMLLinkElement ).href )
	);
}

async function getInlineStyles( page: Page ) {
	const styleLocators = page.locator(
		'style[id^="woocommerce-"][id$="-style-inline-css"]'
	);
	return await styleLocators.evaluateAll( ( styles ) =>
		styles.map( ( style ) => style.id )
	);
}

test.describe( 'Block Style Loading in Classic Themes', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		// Activate classic theme for all tests
		await requestUtils.activateTheme( CLASSIC_THEME_SLUG );
	} );

	test( 'should not load unnecessary block styles on pages without WooCommerce blocks', async ( {
		page,
		admin,
		editor,
	} ) => {
		await admin.createNewPost( { postType: 'page' } );
		await editor.canvas
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( 'Test Page Without Blocks' );

		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: {
				content: 'This is a regular page with no WooCommerce blocks.',
			},
		} );

		await editor.publishAndVisitPost();

		const blockStylesheets = await getStylesheets( page );
		const inlineBlockStyles = await getInlineStyles( page );

		expect( blockStylesheets ).toHaveLength( 0 );
		expect( inlineBlockStyles ).toHaveLength( 0 );
	} );

	test( 'should load base WooCommerce styles when blocks are present', async ( {
		page,
		admin,
		editor,
	} ) => {
		await admin.createNewPost( { postType: 'page' } );
		await editor.canvas
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( 'Test Page With WooCommerce Block' );

		await editor.insertBlock( {
			name: 'woocommerce/product-filters',
		} );

		await editor.publishAndVisitPost();

		const blockStylesheets = await getStylesheets( page );
		const inlineBlockStyles = await getInlineStyles( page );

		// Ensure styles are loaded (either as files or inline)
		const hasFileStyles = blockStylesheets.length > 0;
		const hasInlineStyles = inlineBlockStyles.length > 0;

		expect( hasFileStyles || hasInlineStyles ).toBeTruthy();

		const hasProductFilterStyle = blockStylesheets.some( ( href ) =>
			href.includes( 'product-filters' )
		);
		const hasProductFilterInlineStyle = inlineBlockStyles.some( ( id ) =>
			id.includes( 'product-filters' )
		);
		expect(
			hasProductFilterStyle || hasProductFilterInlineStyle
		).toBeTruthy();
	} );
} );
