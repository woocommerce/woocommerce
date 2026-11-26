/**
 * External dependencies
 */
import { randomUUID } from 'crypto';
import type { Page } from '@playwright/test';
import {
	test as base,
	expect,
	getProductAttributeIds,
	guestFile,
	wpCLI,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	BIS_FEATURE_OPTION,
	BIS_OPTIONS,
	bisFormLocator,
	bisNotice,
	bisTargetProductInput,
	signUpOnProductPage,
	uniqueGuestEmail,
} from '../../../utils/back-in-stock-notifications';

type Product = { id: number; name: string; slug: string; permalink: string };

const test = base.extend< {
	// Creates a product and a `single-product-<slug>` template that renders it
	// with the Add to Cart + Options block. The serial project's database reset
	// removes both after the test.
	createProductPage: (
		data: Record< string, unknown >
	) => Promise< Product >;
} >( {
	createProductPage: async ( { requestUtils }, use ) => {
		// eslint-disable-next-line react-hooks/rules-of-hooks -- Playwright's fixture `use`, not a React hook.
		await use( async ( data ) => {
			const product = await requestUtils.rest< Product >( {
				method: 'POST',
				path: 'wc/v3/products',
				data: {
					name: `BIS Block ${ randomUUID().slice( 0, 8 ) }`,
					regular_price: '10',
					...data,
				},
			} );
			await requestUtils.createTemplate( 'wp_template', {
				slug: `single-product-${ product.slug }`,
				title: `Single Product ${ product.slug }`,
				content:
					'<!-- wp:woocommerce/store-notices /--><!-- wp:woocommerce/add-to-cart-with-options /-->',
			} );
			return product;
		} );
	},
} );

test.describe( 'Add to Cart + Options Block: Back in Stock form', () => {
	test.use( { storageState: guestFile } );

	test.beforeEach( async () => {
		await wpCLI( `option update ${ BIS_FEATURE_OPTION } yes` );
		await wpCLI( `option update ${ BIS_OPTIONS.allowSignups } yes` );
	} );

	test( 'renders after the block for an out-of-stock simple product', async ( {
		page,
		createProductPage,
	} ) => {
		const product = await createProductPage( {
			type: 'simple',
			stock_status: 'outofstock',
		} );
		const email = uniqueGuestEmail( 'bis-block-simple' );

		await page.goto( product.permalink );

		await expect( bisFormLocator( page ) ).toBeVisible();

		await signUpOnProductPage( page, { email } );

		await expect(
			page.getByText( bisNotice.success( product.name ) )
		).toBeVisible();
	} );

	test.describe( 'variable product', () => {
		let product: Product;
		let outOfStockVariationId: number;

		test.beforeEach( async ( { requestUtils, createProductPage } ) => {
			const { colorAttributeId: colorId, sizeAttributeId: sizeId } =
				await getProductAttributeIds();

			product = await createProductPage( {
				type: 'variable',
				attributes: [
					{
						id: colorId,
						variation: true,
						visible: true,
						options: [ 'Blue', 'Red' ],
					},
					{
						id: sizeId,
						variation: true,
						visible: true,
						options: [ 'Small', 'Large' ],
					},
				],
			} );

			// Both variations take "any" size.
			const { create } = await requestUtils.rest< {
				create: { id: number }[];
			} >( {
				method: 'POST',
				path: `wc/v3/products/${ product.id }/variations/batch`,
				data: {
					create: [
						{ option: 'Blue', stock_status: 'outofstock' },
						{ option: 'Red', stock_status: 'instock' },
					].map( ( { option, stock_status } ) => ( {
						regular_price: '10',
						stock_status,
						attributes: [
							{ id: colorId, option },
							{ id: sizeId, option: '' },
						],
					} ) ),
				},
			} );
			outOfStockVariationId = create[ 0 ].id;
		} );

		const chip = ( page: Page, attribute: string, option: string ) =>
			page
				.locator( '.wp-block-add-to-cart-with-options' )
				.getByRole( 'radiogroup', { name: attribute } )
				.getByRole( 'radio', { name: option, exact: true } );

		test( 'signs up for the selected out-of-stock variation and its "any" attribute', async ( {
			page,
		} ) => {
			const email = uniqueGuestEmail( 'bis-block-variation' );
			const form = bisFormLocator( page );

			await page.goto( product.permalink );
			await expect( form ).toBeHidden();

			await chip( page, 'Color', 'Blue' ).click();
			await chip( page, 'Size', 'Large' ).click();

			await expect( form ).toBeVisible();
			await expect( bisTargetProductInput( page ) ).toHaveValue(
				String( outOfStockVariationId )
			);
			// Rendered in the block's <form> and posted with the Back in Stock form.
			await expect(
				page.locator(
					'.wp-block-add-to-cart-with-options input[name="attribute_pa_size"][form]'
				)
			).toHaveValue( 'large' );

			const signupRequest = page.waitForRequest(
				( request ) =>
					request.method() === 'POST' &&
					( request.postData() ?? '' ).includes( 'wc_bis_product_id' )
			);
			await signUpOnProductPage( page, { email } );
			const posted = new URLSearchParams(
				( await signupRequest ).postData() ?? ''
			);

			await expect(
				page.getByText( /You have successfully signed up!/ )
			).toBeVisible();
			expect( posted.get( 'wc_bis_product_id' ) ).toBe(
				String( outOfStockVariationId )
			);
			expect( posted.get( 'attribute_pa_size' ) ).toBe( 'large' );
		} );

		test( 'hides the form for an in-stock variation and a cleared selection', async ( {
			page,
		} ) => {
			const form = bisFormLocator( page );

			await page.goto( product.permalink );
			await chip( page, 'Color', 'Blue' ).click();
			await chip( page, 'Size', 'Large' ).click();
			await expect( form ).toBeVisible();

			await chip( page, 'Color', 'Red' ).click();
			await expect( form ).toBeHidden();

			await chip( page, 'Color', 'Blue' ).click();
			await expect( form ).toBeVisible();

			// Clicking the selected chip clears it.
			await chip( page, 'Size', 'Large' ).click();
			await expect( form ).toBeHidden();
			await expect( bisTargetProductInput( page ) ).toHaveValue(
				String( product.id )
			);
		} );
	} );
} );
