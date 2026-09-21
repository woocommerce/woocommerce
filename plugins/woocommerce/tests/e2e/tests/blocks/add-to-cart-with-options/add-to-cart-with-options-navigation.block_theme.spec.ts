/**
 * External dependencies
 */
import { test as base, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage from '../product-collection/product-collection.page';

const test = base.extend< {
	productCollectionPageObject: ProductCollectionPage;
} >( {
	productCollectionPageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Add to Cart + Options Block: Navigation', () => {
	/**
	 * Fixture: a category containing exactly two products, so a Product
	 * Collection filtered to it has a predictable, two-page result once
	 * "Products per page" is set to 1.
	 *
	 * - "AAA ..." (variable, one variation) sorts first when ordered by
	 *   title, so it is the only tile on page 1.
	 * - "BBB ..." (simple) is the only tile on page 2.
	 */
	test.beforeEach( async () => {
		const categoryResult = await wpCLI(
			`wc product_cat create --user=1 --porcelain --name="Add to Cart with Options Navigation Fixture"`
		);
		const categoryId = categoryResult.stdout.match( /(\d+)\s*$/ )?.[ 1 ];

		const variableResult = await wpCLI(
			`wc product create --user=1 --porcelain --name="AAA Navigation Variable Product" --slug="nav-fixture-variable-product" --type="variable" --categories='${ JSON.stringify(
				[ { id: Number( categoryId ) } ]
			) }' --attributes='${ JSON.stringify( [
				{
					name: 'Color',
					options: [ 'Blue', 'Green' ],
					visible: true,
					variation: true,
				},
				{
					name: 'Size',
					options: [ 'Small', 'Medium' ],
					visible: true,
					variation: true,
				},
			] ) }'`
		);
		const variableProductId =
			variableResult.stdout.match( /(\d+)\s*$/ )?.[ 1 ];

		await wpCLI(
			`wc product_variation create ${ variableProductId } --user=1 --porcelain --regular_price="10.00" --attributes='${ JSON.stringify(
				[
					{ name: 'Color', option: 'Blue' },
					{ name: 'Size', option: 'Small' },
				]
			) }'`
		);

		await wpCLI(
			`wc product create --user=1 --porcelain --name="BBB Navigation Simple Product" --slug="nav-fixture-simple-product" --type="simple" --regular_price="5.00" --categories='${ JSON.stringify(
				[ { id: Number( categoryId ) } ]
			) }'`
		);
	} );

	test( 'paging a Product Collection tile away and back keeps an in-progress variation selection and quantity, which the tile then submits, and a full reload resets the tile', async ( {
		page,
		editor,
		frontendUtils,
		productCollectionPageObject,
	} ) => {
		await productCollectionPageObject.createNewPostAndInsertBlock(
			'productCatalog'
		);
		await productCollectionPageObject.addFilter(
			'Show product categories'
		);
		await productCollectionPageObject.checkTaxonomyTerm(
			'categories',
			'Add to Cart with Options Navigation Fixture'
		);
		await productCollectionPageObject.setOrderBy( 'title/asc' );

		await productCollectionPageObject.focusProductCollection();
		const settingsPanel = page.locator(
			'.wc-block-editor-product-collection__settings_panel'
		);
		await settingsPanel
			.getByRole( 'spinbutton', { name: 'Products per page' } )
			.fill( '1' );

		// The block editor's Product Template does not allow inserting Add to
		// Cart with Options there (it is designed for a single product's
		// page, not a loop: `canInsertBlockType` returns false), so the
		// Product Button block is published as usual and then swapped for
		// Add to Cart with Options directly in the saved post content. The
		// frontend renderer does not enforce that editor-only restriction.
		const postId = await editor.publishPost();
		const swapProductButtonPhp =
			`$post = get_post(${ postId }); ` +
			'$content = preg_replace("#<!--\\s*wp:woocommerce/product-button[^>]*/-->#", "<!-- wp:woocommerce/add-to-cart-with-options /-->", $post->post_content, 1); ' +
			`wp_update_post(array("ID" => ${ postId }, "post_content" => $content));`;
		await wpCLI( `eval '${ swapProductButtonPhp }'` );

		await page.goto( `/?p=${ postId }` );

		// The Product Collection's "Reload full page" setting defaults to off
		// (`forcePageReload: false`), so pagination navigates client-side.
		await expect(
			page.getByRole( 'heading', {
				name: 'AAA Navigation Variable Product',
			} )
		).toBeVisible();

		const addToCartForm = page.locator(
			'.wp-block-add-to-cart-with-options'
		);
		await addToCartForm
			.getByRole( 'radiogroup', { name: 'Color' } )
			.getByRole( 'radio', { name: 'Blue', exact: true } )
			.click();
		await addToCartForm
			.getByRole( 'radiogroup', { name: 'Size' } )
			.getByRole( 'radio', { name: 'Small', exact: true } )
			.click();

		const quantityInput = page.getByLabel( 'Product quantity' );
		await quantityInput.fill( '3' );

		await page.getByRole( 'link', { name: 'Next Page' } ).click();
		await expect(
			page.getByRole( 'heading', {
				name: 'BBB Navigation Simple Product',
			} )
		).toBeVisible();

		await page.getByRole( 'link', { name: 'Previous Page' } ).click();
		await expect(
			page.getByRole( 'heading', {
				name: 'AAA Navigation Variable Product',
			} )
		).toBeVisible();

		// Client-side navigation away and back re-renders the tile under the
		// same server-derived scope name, so its record is found again and
		// the selection and typed quantity survive.
		await expect(
			addToCartForm
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Blue', exact: true } )
		).toBeChecked();
		await expect(
			addToCartForm
				.getByRole( 'radiogroup', { name: 'Size' } )
				.getByRole( 'radio', { name: 'Small', exact: true } )
		).toBeChecked();
		await expect( page.getByLabel( 'Product quantity' ) ).toHaveValue(
			'3'
		);

		await addToCartForm
			.getByRole( 'button', { name: 'Add to cart' } )
			.click();
		await expect(
			addToCartForm.getByRole( 'button', { name: '3 in cart' } )
		).toBeVisible();

		// Submitting posts the typed quantity that survived the round trip,
		// not the product's minimum purchase quantity.
		await frontendUtils.goToCart();
		await expect(
			page.getByLabel(
				'Quantity of AAA Navigation Variable Product in your cart.'
			)
		).toHaveValue( '3' );
		await expect( page.getByText( 'Color: Blue' ) ).toBeVisible();
		await expect( page.getByText( 'Size: Small' ) ).toBeVisible();

		// A full browser load rebuilds the tile from scratch instead of
		// re-rendering under a surviving name, so it shows its initial state
		// again: no selection, and the product's minimum purchase quantity.
		await page.goto( `/?p=${ postId }` );
		await expect(
			page.getByRole( 'heading', {
				name: 'AAA Navigation Variable Product',
			} )
		).toBeVisible();
		const reloadedForm = page.locator(
			'.wp-block-add-to-cart-with-options'
		);
		await expect(
			reloadedForm
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { checked: true } )
		).toHaveCount( 0 );
		await expect(
			reloadedForm
				.getByRole( 'radiogroup', { name: 'Size' } )
				.getByRole( 'radio', { checked: true } )
		).toHaveCount( 0 );
		await expect( page.getByLabel( 'Product quantity' ) ).toHaveValue(
			'1'
		);
	} );
} );
