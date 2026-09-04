/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { hoodieReviews } from '../../../test-data/blocks/data/data';

const BLOCK_NAME = 'woocommerce/reviews-by-product';

const latestReview = hoodieReviews[ hoodieReviews.length - 1 ];

const highestRating = [ ...hoodieReviews ].sort(
	( a, b ) => b.rating - a.rating
)[ 0 ];

const lowestRating = [ ...hoodieReviews ].sort(
	( a, b ) => a.rating - b.rating
)[ 0 ];

test.describe( `${ BLOCK_NAME } Block`, () => {
	test.describe( 'with a product selected', () => {
		test.beforeEach( async ( { admin, editor } ) => {
			await admin.createNewPost();
			await editor.insertBlock( { name: BLOCK_NAME } );

			const productCheckbox = editor.canvas.getByLabel(
				'Hoodie, has 3 reviews'
			);
			await productCheckbox.check();
			await expect( productCheckbox ).toBeChecked();

			await editor.canvas.getByRole( 'button', { name: 'Done' } ).click();
		} );

		test( 'block can be inserted and it successfully renders a review in the editor and the frontend', async ( {
			page,
			editor,
		} ) => {
			await expect(
				editor.canvas.getByText( hoodieReviews[ 0 ].review )
			).toBeVisible();

			await editor.publishAndVisitPost();

			await expect(
				page.getByText( hoodieReviews[ 0 ].review )
			).toBeVisible();
		} );

		test( 'sorts by most recent by default and can sort by highest rating', async ( {
			page,
			frontendUtils,
			editor,
		} ) => {
			await editor.publishAndVisitPost();
			const block = await frontendUtils.getBlockByName( BLOCK_NAME );

			const reviews = block.locator(
				'.wc-block-components-review-list-item__text'
			);

			await expect( reviews.first() ).toHaveText( latestReview.review );

			const select = page.getByLabel( 'Order by' );
			await select.selectOption( 'Highest rating' );

			await expect( reviews.first() ).toHaveText( highestRating.review );
		} );

		test( 'can sort by lowest rating', async ( {
			page,
			frontendUtils,
			editor,
		} ) => {
			await editor.publishAndVisitPost();
			const block = await frontendUtils.getBlockByName( BLOCK_NAME );

			const reviews = block.locator(
				'.wc-block-components-review-list-item__text'
			);

			await expect( reviews.first() ).toHaveText( latestReview.review );

			const select = page.getByLabel( 'Order by' );
			await select.selectOption( 'Lowest rating' );

			await expect( reviews.first() ).toHaveText( lowestRating.review );
		} );
	} );

	test( 'renders no reviews on the frontend when published without a product selected', async ( {
		admin,
		editor,
		frontendUtils,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: BLOCK_NAME } );
		await editor.publishAndVisitPost();

		const block = await frontendUtils.getBlockByName( BLOCK_NAME );

		await expect(
			block.locator( '.wc-block-components-review-list-item__text' )
		).toHaveCount( 0 );
	} );

	test( 'can skip reviews with an offset in the editor and frontend', async ( {
		page,
		admin,
		frontendUtils,
		editor,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: BLOCK_NAME } );
		const productCheckbox = editor.canvas.getByLabel(
			'Hoodie, has 3 reviews'
		);
		await productCheckbox.check();
		await editor.canvas
			.getByRole( 'button', {
				name: 'Done',
			} )
			.click();

		await editor.openDocumentSettingsSidebar();
		const sidebarSettings = page.getByRole( 'region', {
			name: 'Editor settings',
		} );

		await sidebarSettings
			.getByRole( 'spinbutton', { name: 'Offset' } )
			.fill( '1' );

		await expect(
			editor.canvas.getByText( hoodieReviews[ 0 ].review )
		).toBeVisible();
		await expect(
			editor.canvas.getByText( hoodieReviews[ 1 ].review )
		).toBeVisible();
		await expect(
			editor.canvas.getByText( hoodieReviews[ 2 ].review )
		).toBeHidden();

		await editor.publishAndVisitPost();

		const block = await frontendUtils.getBlockByName( BLOCK_NAME );

		await expect(
			block.getByText( hoodieReviews[ 0 ].review )
		).toBeVisible();
		await expect(
			block.getByText( hoodieReviews[ 1 ].review )
		).toBeVisible();
		await expect(
			block.getByText( hoodieReviews[ 2 ].review )
		).toBeHidden();
	} );
} );
