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

	test( 'submitting with an out-of-stock variation selected names the product', async ( {
		page,
		pageObject,
		frontendUtils,
	} ) => {
		await pageObject.createPostWithProductBlock( PRODUCT_SLUG );
		const productPageUrl = page.url();

		await pageObject.selectVariationSelectorOptions(
			'Style',
			'Unavailable',
			'chips'
		);

		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart',
			exact: true,
		} );

		// The out-of-stock variation carries `hidden` on the Add to cart
		// button (ProductButton.php:246, bound to
		// `!state.allowsAddingToCart`), taking it out of the accessibility
		// tree so a shopper cannot click it. A12 holds that the form can
		// still be submitted from the keyboard: focusing another control
		// inside the form and pressing Enter should trigger the form's
		// implicit submission, which activates the same (still present,
		// merely hidden) default button.
		await expect( addToCartButton ).toBeHidden();

		const quantityInput = page.getByLabel( 'Product quantity' );
		await quantityInput.focus();
		await quantityInput.press( 'Enter' );

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
		await page.goto( productPageUrl );
		await pageObject.selectVariationSelectorOptions(
			'Style',
			'Available',
			'chips'
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
