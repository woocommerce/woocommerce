/**
 * External dependencies
 */
import { randomUUID } from 'crypto';
import type { Page } from '@playwright/test';
import { test as base, expect, guestFile, wpCLI } from '@woocommerce/e2e-utils';

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

/**
 * The sign-up stored for an email: the product it targets and the serialized
 * `posted_attributes` meta, if any.
 *
 * @param email The guest's email address.
 */
async function getStoredSignup( email: string ) {
	const { stdout } = await wpCLI(
		`db query "SELECT n.product_id, IFNULL(m.meta_value, '') FROM wp_wc_stock_notifications n LEFT JOIN wp_wc_stock_notificationmeta m ON m.notification_id = n.id AND m.meta_key = 'posted_attributes' WHERE n.user_email = '${ email }'" --skip-column-names`
	);
	const [ productId = '', postedAttributes = '' ] = stdout
		.trim()
		.split( '\t' );
	return { productId, postedAttributes };
}

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
		expect( ( await getStoredSignup( email ) ).productId ).toBe(
			String( product.id )
		);
	} );

	test.describe( 'variable product', () => {
		let product: Product;
		let outOfStockVariationId: number;

		test.beforeEach( async ( { requestUtils, createProductPage } ) => {
			const attributes = await requestUtils.rest<
				{ id: number; slug: string }[]
			>( { path: 'wc/v3/products/attributes' } );
			const attributeId = ( slug: string ) =>
				attributes.find( ( attribute ) => attribute.slug === slug )?.id;
			const colorId = attributeId( 'pa_color' );
			const sizeId = attributeId( 'pa_size' );

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

			await signUpOnProductPage( page, { email } );

			await expect(
				page.getByText( /You have successfully signed up!/ )
			).toBeVisible();
			const signup = await getStoredSignup( email );
			expect( signup.productId ).toBe( String( outOfStockVariationId ) );
			// Only the "any" attribute is stored, with the chosen term's slug.
			expect( signup.postedAttributes ).toBe(
				'a:1:{s:17:"attribute_pa_size";s:5:"large";}'
			);
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
