/**
 * External dependencies
 */
import {
	expect,
	test as base,
	CLASSIC_THEME_SLUG,
} from '@woocommerce/e2e-utils';

const test = base.extend( {} );

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
		// Create a page without WooCommerce blocks
		await admin.createNewPost( { postType: 'page' } );
		await editor.canvas
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( 'Test Page Without Blocks' );

		// Add regular content (no WooCommerce blocks)
		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: {
				content: 'This is a regular page with no WooCommerce blocks.',
			},
		} );

		// Publish and visit the page on frontend
		await editor.publishAndVisitPost();

		// Check for file-based stylesheets
		const specificBlockStylesheets = await page.$$eval(
			'link[rel="stylesheet"]',
			( links ) =>
				links
					.map( ( link ) => ( link as HTMLLinkElement ).href )
					.filter(
						( href ) =>
							href.includes( 'assets/client/blocks' ) &&
							! href.includes( 'wc-blocks.css' )
					)
		);

		// Check for inline styles with WooCommerce block IDs
		const inlineBlockStyles = await page.$$eval( 'style', ( styles ) =>
			styles
				.map( ( style ) => style.id )
				.filter(
					( id ) =>
						id.startsWith( 'woocommerce-' ) &&
						id.endsWith( '-style-inline-css' )
				)
		);

		expect( specificBlockStylesheets ).toHaveLength( 0 );
		expect( inlineBlockStyles ).toHaveLength( 0 );
	} );

	test( 'should load base WooCommerce styles when blocks are present', async ( {
		page,
		admin,
		editor,
	} ) => {
		// Create a page with a WooCommerce block
		await admin.createNewPost( { postType: 'page' } );
		await editor.canvas
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( 'Test Page With WooCommerce Block' );

		// Add a WooCommerce product button block
		await editor.insertBlock( {
			name: 'woocommerce/product-filters',
		} );

		// Publish and visit the page on frontend
		await editor.publishAndVisitPost();

		// Check for file-based stylesheets
		const blockStylesheets = await page.$$eval(
			'link[rel="stylesheet"]',
			( links ) =>
				links
					.map( ( link ) => ( link as HTMLLinkElement ).href )
					.filter(
						( href ) =>
							href.includes( 'assets/client/blocks' ) &&
							! href.includes( 'wc-blocks.css' )
					)
		);

		// Check for inline styles with WooCommerce block IDs
		const inlineBlockStyles = await page.$$eval( 'style', ( styles ) =>
			styles
				.map( ( style ) => style.id )
				.filter(
					( id ) =>
						id.startsWith( 'woocommerce-' ) &&
						id.endsWith( '-style-inline-css' )
				)
		);

		// Ensure styles are loaded (either as files or inline)
		const hasFileStyles = blockStylesheets.length > 0;
		const hasInlineStyles = inlineBlockStyles.length > 0;

		expect( hasFileStyles || hasInlineStyles ).toBeTruthy();

		const hasProductFilterStyle = blockStylesheets.some( ( href ) =>
			href.includes( 'woocommerce' )
		);
		const hasProductFilterInlineStyle = inlineBlockStyles.some( ( id ) =>
			id.includes( 'woocommerce' )
		);
		expect(
			hasProductFilterStyle || hasProductFilterInlineStyle
		).toBeTruthy();
	} );
} );
