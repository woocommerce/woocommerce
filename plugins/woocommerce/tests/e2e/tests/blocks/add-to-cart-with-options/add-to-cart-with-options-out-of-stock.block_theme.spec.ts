/**
 * External dependencies
 */
import { test as base, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from './add-to-cart-with-options.page';

const test = base.extend< {
	pageObject: AddToCartWithOptionsPage;
} >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new AddToCartWithOptionsPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

const PRODUCT_NAME = 'Out Of Stock Notice Fixture';
const PRODUCT_SLUG = 'out-of-stock-notice-fixture';

test.describe( 'Add to Cart + Options Block: Out-of-stock notice', () => {
	test.beforeEach( async () => {
		const variableResult = await wpCLI(
			`wc product create --user=1 --porcelain --name="${ PRODUCT_NAME }" --slug="${ PRODUCT_SLUG }" --type="variable" --attributes='${ JSON.stringify(
				[
					{
						name: 'Style',
						options: [ 'Available', 'Unavailable' ],
						visible: true,
						variation: true,
					},
				]
			) }'`
		);
		const variableProductId = variableResult.stdout.match(
			/(\d+)\s*$/
		)?.[ 1 ] as string;

		await wpCLI(
			`wc product_variation create ${ variableProductId } --user=1 --porcelain --regular_price="10.00" --attributes='${ JSON.stringify(
				[ { name: 'Style', option: 'Available' } ]
			) }'`
		);
		await wpCLI(
			`wc product_variation create ${ variableProductId } --user=1 --porcelain --regular_price="10.00" --manage_stock=true --in_stock=false --attributes='${ JSON.stringify(
				[ { name: 'Style', option: 'Unavailable' } ]
			) }'`
		);
	} );

	test( 'submitting from the attribute select with an out-of-stock variation selected names the product', async ( {
		page,
		pageObject,
		editor,
		frontendUtils,
	} ) => {
		await pageObject.updateSingleProductTemplate();
		await pageObject.setVariationSelectorAttributes( {
			optionStyle: 'dropdown',
		} );
		await editor.saveSiteEditorEntities();

		await page.goto( `/product/${ PRODUCT_SLUG }/` );

		let unavailableOption = page.getByRole( 'option', {
			name: 'Unavailable',
			exact: true,
		} );

		// Workaround for the template not being updated on the first load.
		if ( ! ( await unavailableOption.isVisible() ) ) {
			await page.reload();
			unavailableOption = page.getByRole( 'option', {
				name: 'Unavailable',
				exact: true,
			} );
		}

		await pageObject.selectVariationSelectorOptions(
			'Style',
			'Unavailable',
			'dropdown'
		);

		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart',
			exact: true,
		} );

		// The out-of-stock variation carries `hidden` on the Add to cart
		// button (ProductButton.php:246, bound to
		// `!state.allowsAddingToCart`), taking it out of the accessibility
		// tree so a shopper cannot click it. On the Dropdown display style
		// the attribute row is a native `<select>` (Dropdown.php:83-142)
		// with only a `change` handler (blocks/dropdown/frontend.ts:52),
		// so it stays focusable and carries no key handling of its own:
		// pressing Enter on it triggers the browser's implicit form
		// submission, which activates the same (still present, merely
		// hidden) default button.
		await expect( addToCartButton ).toBeHidden();

		const styleSelect = page.getByLabel( 'Style', { exact: true } );
		await styleSelect.focus();
		await styleSelect.press( 'Enter' );

		const notices = page.getByRole( 'alert' );
		await expect( notices ).toHaveCount( 1 );
		await expect( notices ).toHaveText(
			`You cannot add "${ PRODUCT_NAME }" to the cart because the product is out of stock.`
		);

		// Nothing was added: the button never reached its "Added to cart"
		// state, and the cart page confirms no line was created.
		await expect(
			page.getByRole( 'button', { name: 'Added to cart', exact: true } )
		).toHaveCount( 0 );
		await frontendUtils.goToCart();
		await expect(
			page.getByText( 'Your cart is currently empty' )
		).toBeVisible();

		// The invalid state is not sticky: switching to the purchasable
		// variation and submitting again adds the product.
		await page.goto( `/product/${ PRODUCT_SLUG }/` );
		await pageObject.selectVariationSelectorOptions(
			'Style',
			'Available',
			'dropdown'
		);
		await expect( addToCartButton ).toBeVisible();
		await addToCartButton.click();
		await expect(
			page.getByRole( 'button', { name: '1 in cart', exact: true } )
		).toBeVisible();

		await frontendUtils.goToCart();
		await expect(
			page.getByLabel( `Quantity of ${ PRODUCT_NAME } in your cart.` )
		).toHaveValue( '1' );
	} );
} );
