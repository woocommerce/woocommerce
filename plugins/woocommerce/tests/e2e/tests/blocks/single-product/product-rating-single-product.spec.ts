/**
 * External dependencies
 */
import {
	Editor,
	test as base,
	expect,
	getPostIdBySlug,
	wpCLI,
} from '@woocommerce/e2e-utils';

const blockData = {
	slug: 'woocommerce/single-product',
	productSlug: 'hoodie',
};

class BlockUtils {
	editor: Editor;
	admin;

	constructor( { editor, admin }: { editor: Editor; admin } ) {
		this.editor = editor;
		this.admin = admin;
	}

	async configureSingleProductBlockForProduct( product: string ) {
		const singleProductBlock = await this.editor.getBlockByName(
			'woocommerce/single-product'
		);

		await singleProductBlock
			.locator( `input[type="radio"][value="${ product }"]` )
			.nth( 0 )
			.click();

		await singleProductBlock.getByText( 'Done' ).click();
	}

	async insertBlockAndVisit( block: string, product: string ) {
		await this.admin.createNewPost();
		await this.editor.insertBlock( { name: block } );
		await this.configureSingleProductBlockForProduct( product );
		await this.editor.publishAndVisitPost();
	}
}

const test = base.extend< { blockUtils: BlockUtils } >( {
	blockUtils: async ( { editor, admin }, use ) => {
		await use( new BlockUtils( { editor, admin } ) );
	},
} );

test.describe( `${ blockData.slug } Block`, () => {
	test( 'Product Rating block is not visible if ratings are disabled for product', async ( {
		page,
		blockUtils,
	} ) => {
		await test.step( `Disable reviews for ${ blockData.productSlug }`, async () => {
			const productId = await getPostIdBySlug( blockData.productSlug );
			await wpCLI( `post update ${ productId } --comment_status=closed` );
		} );

		await test.step( `Insert the block in a post and visit it`, async () => {
			await blockUtils.insertBlockAndVisit(
				blockData.slug,
				blockData.productSlug
			);
		} );

		await expect(
			page.locator( '.wc-block-components-product-rating' )
		).toBeHidden();
	} );

	test( 'Product Rating block is not visible if ratings are disabled globally in the store', async ( {
		admin,
		page,
		blockUtils,
	} ) => {
		await test.step( `Disable reviews in the store`, async () => {
			await page.goto(
				'/wp-admin/admin.php?page=wc-settings&tab=products'
			);
			await admin.page
				.getByRole( 'checkbox', {
					name: 'Enable product reviews',
				} )
				.uncheck();
			await page.getByRole( 'button', { name: 'Save changes' } ).click();
		} );

		await test.step( 'Insert the block in a post and visit it', async () => {
			await blockUtils.insertBlockAndVisit(
				blockData.slug,
				blockData.productSlug
			);
		} );

		await expect(
			page.locator( '.wc-block-components-product-rating' )
		).toBeHidden();
	} );
} );
