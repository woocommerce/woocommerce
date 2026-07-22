/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { Admin, Editor } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from '../add-to-cart-with-options/add-to-cart-with-options.page';

/**
 * Shared helpers for the `scoped-drafts` suite.
 *
 * These flows all need the same basic building block: several independent
 * purchase surfaces (each a "Single Product" block, optionally pinned to one
 * of the product's variations) rendered on one page, so a test can prove
 * that each surface's draft state — the child quantities a shopper is
 * editing, or the variation attributes they've selected — is isolated to
 * its own keyed draft collection and never leaks into a sibling surface.
 */
class ScopedDraftsPage {
	private page: Page;
	private admin: Admin;
	private editor: Editor;
	private addToCartWithOptionsPage: AddToCartWithOptionsPage;

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
		this.addToCartWithOptionsPage = new AddToCartWithOptionsPage( {
			page,
			admin,
			editor,
		} );
	}

	/**
	 * Inserts a new "Single Product" block into the currently open editor,
	 * pointed at `product` (and, for variable products, `variation`), then
	 * upgrades its default "Add to Cart Form" to "Add to Cart + Options" —
	 * mirroring `AddToCartWithOptionsPage.createPostWithProductBlock()`, but
	 * without publishing, so several of these can be inserted into one post
	 * or template before visiting the frontend.
	 *
	 * Every call adds a new, independent block instance. Each "Single
	 * Product" block instance mints its own `single-product/<productId>/<n>`
	 * draft key and declares it in a `woocommerce/cart` context bag (see
	 * `SingleProduct.php`), so two calls for the same product remain fully
	 * isolated from one another — the isolation comes from that minted key
	 * (the `<n>` occurrence counter distinguishing same-product instances),
	 * not from the context tree the block's markup happens to establish.
	 *
	 * @param product    The product's slug.
	 * @param variation  Optional variation slug to preselect.
	 * @param searchTerm Optional text to type into the picker's product
	 *                   search first. The picker's default (unsearched) list
	 *                   only surfaces a limited set of products, so a
	 *                   product created earlier in the same test (and thus
	 *                   absent from that default list) needs to be searched
	 *                   for by its display name to appear as a selectable
	 *                   radio at all.
	 */
	async insertSingleProductBlock(
		product: string,
		variation?: string,
		searchTerm?: string
	) {
		await this.editor.insertBlock( { name: 'woocommerce/single-product' } );

		// `getBlockByName` matches every "Single Product" block on the page;
		// `.last()` scopes to the one just inserted so this method can be
		// called repeatedly on the same post/template without the picker
		// interactions below leaking onto an earlier instance.
		const singleProductBlock = (
			await this.editor.getBlockByName( 'woocommerce/single-product' )
		 ).last();

		if ( searchTerm ) {
			await singleProductBlock
				.getByLabel( 'Search for a product to display' )
				.fill( searchTerm );
		}

		await singleProductBlock
			.locator( `input[type="radio"][value="${ product }"]` )
			.nth( 0 )
			.click();

		if ( variation ) {
			await singleProductBlock
				.locator( `input[type="radio"][value="${ variation }"]` )
				.nth( 0 )
				.click();
		}

		await singleProductBlock.getByText( 'Done' ).click();

		// At this point at most one "Add to Cart Form" block remains
		// un-upgraded on the page (every previously-inserted instance was
		// already converted by its own call to this method), so this is
		// unambiguous regardless of how many "Single Product" blocks
		// precede it.
		await this.addToCartWithOptionsPage.updateAddToCartWithOptionsBlock();
	}

	/**
	 * Creates a new post containing one "Single Product" block per entry, in
	 * order, then publishes and visits it — placing several independent
	 * purchase surfaces on a single page.
	 *
	 * @param products Ordered list of products (and optional variations, and
	 *                 optional picker search terms) to render, one "Single
	 *                 Product" block per entry.
	 */
	async createPostWithSingleProductBlocks(
		products: {
			product: string;
			variation?: string;
			searchTerm?: string;
		}[]
	) {
		await this.admin.createNewPost();

		for ( const { product, variation, searchTerm } of products ) {
			await this.insertSingleProductBlock(
				product,
				variation,
				searchTerm
			);
		}

		await this.editor.publishAndVisitPost();
	}

	/**
	 * A frontend locator scoped to the nth "Single Product" block instance
	 * on the page (0-indexed, in document order).
	 *
	 * Every WooCommerce block carries a `data-block-name` attribute (see
	 * `BlockTypesController::add_data_attributes()`), so this reliably
	 * disambiguates multiple "Single Product" block instances — including
	 * two instances of the same product — rendered on one page.
	 *
	 * @param index The 0-indexed position of the block instance.
	 */
	singleProductBlockAt( index: number ) {
		return this.page
			.locator( '[data-block-name="woocommerce/single-product"]' )
			.nth( index );
	}
}

export default ScopedDraftsPage;
