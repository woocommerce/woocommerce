/**
 * External dependencies
 */
// eslint-disable-next-line import/no-unresolved -- Resolved by the E2E TypeScript alias.
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

const blockData = {
	name: 'Product Details',
	slug: 'woocommerce/product-details',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( "block can't be inserted in Post Editor", async ( {
		editor,
		admin,
	} ) => {
		await admin.createNewPost();

		try {
			await editor.insertBlock( { name: blockData.slug } );
		} catch {
			// noop
		}

		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeHidden();
	} );

	test( 'style supports persist across the editor and frontend', async ( {
		admin,
		requestUtils,
		editor,
		page,
	} ) => {
		const unstyledContent = `<!-- wp:woocommerce/product-details -->
<div class="wp-block-woocommerce-product-details alignwide"><!-- wp:paragraph --><p>Inner marker</p><!-- /wp:paragraph --></div>
<!-- /wp:woocommerce/product-details -->`;
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: 'single-product',
			title: 'Styled product details',
			content: unstyledContent,
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		const block = await editor.getBlockByName( blockData.slug );
		await expect( block.getByText( 'Inner marker' ) ).toBeVisible();
		await editor.selectBlocks( block );
		await editor.openDocumentSettingsSidebar();
		for ( const heading of [ 'Dimensions', 'Border' ] ) {
			await expect(
				page.getByRole( 'heading', { name: heading, exact: true } )
			).toBeVisible();
		}
		await expect(
			page.getByText( 'Background', { exact: true } ).first()
		).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'Text', exact: true } )
		).toHaveCount( 0 );

		await page.evaluate( ( slug ) => {
			const productDetails = window.wp.data
				.select( 'core/block-editor' )
				.getBlocks()
				.find(
					( item: { clientId: string; name: string } ) =>
						item.name === slug
				);
			if ( ! productDetails ) {
				throw new Error( 'Product Details block not found' );
			}
			window.wp.data
				.dispatch( 'core/block-editor' )
				.updateBlockAttributes( productDetails.clientId, {
					backgroundColor: 'contrast',
					borderColor: 'accent-1',
					style: {
						border: {
							radius: '4px',
							style: 'solid',
							width: '2px',
						},
						spacing: {
							margin: { top: 'var:preset|spacing|20' },
							padding: { left: '1rem' },
						},
					},
				} );
		}, blockData.slug );
		await expect( block ).toHaveClass( /has-contrast-background-color/ );
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );
		const savedContent = await page.evaluate( () =>
			window.wp.data.select( 'core/editor' ).getEditedPostContent()
		);
		expect( savedContent ).toContain( '"backgroundColor":"contrast"' );
		expect( savedContent ).toContain( '"borderColor":"accent-1"' );
		expect( savedContent ).toContain( 'var:preset|spacing|20' );
		expect( savedContent ).toContain( 'Inner marker' );
		expect( savedContent ).not.toContain( 'textColor' );

		await page.goto( 'product/hoodie/' );
		const frontend = page.locator(
			'.wp-block-woocommerce-product-details'
		);
		await expect( frontend ).toHaveClass( /has-accent-1-border-color/ );
		await expect( frontend ).toHaveClass( /has-contrast-background-color/ );
		await expect( frontend.getByText( 'Inner marker' ) ).toBeVisible();
		const frontendStyle = await frontend.getAttribute( 'style' );
		for ( const value of [
			'border-radius:4px',
			'border-style:solid',
			'border-width:2px',
			'margin-top:var(--wp--preset--spacing--20)',
			'padding-left:1rem',
		] ) {
			expect( frontendStyle ).toContain( value );
		}
	} );
} );
