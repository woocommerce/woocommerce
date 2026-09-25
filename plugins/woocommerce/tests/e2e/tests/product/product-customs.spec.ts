/**
 * External dependencies
 */
// eslint-disable-next-line import/no-unresolved -- The E2E runner resolves this workspace package through its wc-source export.
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test, expect, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe( 'Product customs fields', { tag: [ tags.GUTENBERG ] }, () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test( 'saves, validates and clears customs fields in the classic editor', async ( {
		page,
		restApi,
	} ) => {
		const { data: product } = await restApi.post(
			`${ WC_API_PATH }/products`,
			{
				name: 'Customs editor product',
				type: 'simple',
				regular_price: '10',
			}
		);
		try {
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
			await page.locator( '.shipping_tab a' ).click();
			const code = page.locator( '#_customs_commodity_code' );
			const country = page.locator( '#_customs_country_of_origin' );
			const description = page.locator( '#_customs_description' );
			await expect( description ).toHaveAttribute( 'maxlength', '35' );
			await code.pressSequentially( '12AB' );
			await expect(
				page.locator( '.wc_error_tip.i18n_commodity_code_error' )
			).toBeVisible();
			await code.blur();
			await expect( code ).toHaveValue( '12' );
			await expect(
				page.locator( '.wc_error_tip.i18n_commodity_code_error' )
			).toBeVisible();

			await code.clear();
			await code.pressSequentially( '123456' );
			await expect(
				page.locator( '.wc_error_tip.i18n_commodity_code_error' )
			).toBeHidden();
			await code.pressSequentially( '789012345' );
			await expect( code ).toHaveValue( '12345678901234' );
			await expect(
				page.locator( '.wc_error_tip.i18n_commodity_code_error' )
			).toBeVisible();

			await description.pressSequentially( 'Cotton 👕' );
			await expect( description ).toHaveValue( 'Cotton ' );
			await expect(
				page.locator( '.wc_error_tip.i18n_customs_description_error' )
			).toBeVisible();

			await country.selectOption( 'RO' );
			await description.fill( 'Cotton shirt' );
			await code.fill( '0012.34' );
			await code.blur();
			await expect( code ).toHaveValue( '001234' );
			await page.locator( '#publish' ).click();
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();
			await page.reload();
			await page.locator( '.shipping_tab a' ).click();
			await expect( code ).toHaveValue( '001234' );
			await expect( country ).toHaveValue( 'RO' );
			await expect( description ).toHaveValue( 'Cotton shirt' );

			await code.fill( '' );
			await country.selectOption( '' );
			await description.fill( '' );
			await page.locator( '#publish' ).click();
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();
			const { data: saved } = await restApi.get(
				`${ WC_API_PATH }/products/${ product.id }`
			);
			expect( saved.customs_commodity_code ).toBeNull();
			expect( saved.customs_country_of_origin ).toBeNull();
			expect( saved.customs_description ).toBeNull();
		} finally {
			await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
				force: true,
			} );
		}
	} );

	test( 'shows inherited values and saves variation overrides', async ( {
		page,
		restApi,
	} ) => {
		const { data: product } = await restApi.post(
			`${ WC_API_PATH }/products`,
			{
				name: 'Customs variation product',
				type: 'variable',
				customs_commodity_code: '001234',
				customs_country_of_origin: 'RO',
				customs_description: 'Cotton shirt',
				attributes: [
					{ name: 'Size', variation: true, options: [ 'Small' ] },
				],
			}
		);
		try {
			const { data: variation } = await restApi.post(
				`${ WC_API_PATH }/products/${ product.id }/variations`,
				{
					regular_price: '10',
					attributes: [ { name: 'Size', option: 'Small' } ],
				}
			);
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
			await page.locator( '.variations_tab a' ).click();
			const row = page.locator( '.woocommerce_variation' ).first();
			await row.locator( 'h3' ).click();
			const code = row.locator(
				'[name^="variable_customs_commodity_code"]'
			);
			const country = row.locator(
				'[name^="variable_customs_country_of_origin"]'
			);
			const description = row.locator(
				'[name^="variable_customs_description"]'
			);
			await expect( code ).toHaveValue( '' );
			await expect( code ).toHaveAttribute( 'placeholder', '001234' );
			await expect( country.locator( 'option:checked' ) ).toHaveText(
				'Same as parent'
			);

			await code.fill( '654321' );
			await country.selectOption( 'US' );
			await description.fill( 'Variation shirt' );
			await description.blur();
			await page.locator( '.save-variation-changes' ).click();
			await expect( row ).not.toHaveClass( /variation-needs-update/ );
			await expect( code ).toHaveValue( '654321' );
			const { data: saved } = await restApi.get(
				`${ WC_API_PATH }/products/${ product.id }/variations/${ variation.id }?context=edit`
			);
			expect( saved.customs_commodity_code ).toBe( '654321' );
			expect( saved.customs_country_of_origin ).toBe( 'US' );
			expect( saved.customs_description ).toBe( 'Variation shirt' );
		} finally {
			await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
				force: true,
			} );
		}
	} );
} );
