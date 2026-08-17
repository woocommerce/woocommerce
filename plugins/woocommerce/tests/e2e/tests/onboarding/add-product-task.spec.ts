/**
 * External dependencies
 */
import {
	WC_ADMIN_API_PATH,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const hide_task_list = async ( restApi: any, task_list_name: string ) => {
	const {
		status,
		data: { isHidden },
	} = await restApi.post(
		`${ WC_ADMIN_API_PATH }/onboarding/tasks/${ task_list_name }/hide`
	);

	expect( status ).toEqual( 200 );

	return isHidden === true;
};

const show_task_list = async ( restApi: any, task_list_name: string ) => {
	const {
		status,
		data: { isHidden },
	} = await restApi.post(
		`${ WC_ADMIN_API_PATH }/onboarding/tasks/${ task_list_name }/unhide`
	);

	expect( status ).toEqual( 200 );

	return isHidden === false;
};

test.describe( 'Add Product Task', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async ( { restApi } ) => {
		const productIds = [];

		// Set business choice to "I'm just starting my business"
		await restApi.post( `${ WC_ADMIN_API_PATH }/onboarding/profile`, {
			skipped: true,
			business_choice: 'im_just_starting_my_business',
		} );

		// Get all products
		await restApi
			.get( `${ WC_API_PATH }/products`, {
				_fields: 'id',
				per_page: 100,
				status: 'any', // excludes trashed products
			} )
			.then( ( response ) => {
				const ids = response.data.map( ( { id } ) => id );
				productIds.push( ...ids );
			} );

		// Get all products in trash separately.
		await restApi
			.get( `${ WC_API_PATH }/products`, {
				_fields: 'id',
				per_page: 100,
				status: 'trash',
			} )
			.then( ( response ) => {
				const ids = response.data.map( ( { id } ) => id );
				productIds.push( ...ids );
			} );

		// Delete all products
		await restApi.post( `${ WC_API_PATH }/products/batch`, {
			delete: productIds,
		} );
	} );

	test.afterAll( async ( { restApi } ) => {
		await restApi.post( `${ WC_ADMIN_API_PATH }/onboarding/profile`, {
			skipped: false,
		} );
	} );

	test( 'Products page redirects to add product task when no products exist', async ( {
		page,
		restApi,
	} ) => {
		const productName = `Slice 092 product ${ Date.now() }`;
		let productId: number | undefined;
		let primaryFailure: unknown;

		try {
			await page.goto( 'wp-admin/admin.php?page=wc-admin&task=products' );
			await expect(
				page.getByRole( 'menuitem', { name: 'Physical product' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'menuitem', { name: 'Variable product' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'menuitem', { name: 'Grouped product' } )
			).toBeVisible();
			await expect(
				page.locator( '#toplevel_page_woocommerce' )
			).toHaveClass( /wp-has-current-submenu/ );
			await expect(
				page.locator( '#menu-posts-product' )
			).not.toHaveClass( /wp-has-current-submenu/ );

			await page.goto( 'wp-admin/edit.php?post_type=product' );
			await expect( page ).toHaveURL(
				/.+path=%2Fadd-product.+task=products/
			);
			await expect(
				page.getByRole( 'menuitem', { name: 'Physical product' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'menuitem', { name: 'Variable product' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'menuitem', { name: 'Grouped product' } )
			).toBeVisible();
			await expect( page.locator( '#menu-posts-product' ) ).toHaveClass(
				/wp-has-current-submenu/
			);
			await expect(
				page.locator(
					'#menu-posts-product .wp-submenu li.current > a[href="edit.php?post_type=product"]'
				)
			).toBeVisible();

			await page.getByTestId( 'header-back-button' ).click();
			await expect( page ).toHaveURL( /admin\.php\?page=wc-admin$/ );
			await expect(
				page.locator( '#toplevel_page_woocommerce' )
			).toHaveClass( /wp-has-current-submenu/ );

			expect( await hide_task_list( restApi, 'setup' ) ).toBe( true );

			await page.goto( 'wp-admin/edit.php?post_type=product' );

			await expect( page ).toHaveURL(
				/.+path=%2Fadd-product.+task=products/
			);
			await expect(
				page.getByRole( 'menuitem', { name: 'Physical product' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'menuitem', { name: 'Variable product' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'menuitem', { name: 'Grouped product' } )
			).toBeVisible();

			expect( await show_task_list( restApi, 'setup' ) ).toBe( true );

			const productResponse = await restApi.post(
				`${ WC_API_PATH }/products`,
				{
					name: productName,
					type: 'simple',
					regular_price: '10.00',
				}
			);
			const createdProductId = productResponse.data.id;
			if (
				Number.isSafeInteger( createdProductId ) &&
				createdProductId > 0
			) {
				productId = createdProductId;
			}
			expect( productResponse.status ).toBe( 201 );
			expect(
				Number.isSafeInteger( createdProductId ) && createdProductId > 0
			).toBe( true );
			expect( productId ).toBe( createdProductId );

			await page.goto( 'wp-admin/edit.php?post_type=product' );

			await expect( page.locator( '.wp-list-table' ) ).toBeVisible();
			await expect(
				page.getByRole( 'link', { name: productName, exact: true } )
			).toBeVisible();

			const deleteResponse = await restApi.delete(
				`${ WC_API_PATH }/products/${ productId }`,
				{ force: true }
			);
			expect( deleteResponse.status ).toBe( 200 );
			productId = undefined;

			await page.goto( 'wp-admin/admin.php?page=wc-admin&task=products' );
			await page
				.getByRole( 'menuitem', { name: 'Physical product' } )
				.click();
			await expect(
				page.locator(
					'#menu-posts-product .wp-submenu li.current > a[href="post-new.php?post_type=product"]'
				)
			).toBeVisible();
		} catch ( error ) {
			primaryFailure = error;
		}

		const cleanupErrors: unknown[] = [];
		if ( productId !== undefined ) {
			try {
				const response = await restApi.delete(
					`${ WC_API_PATH }/products/${ productId }`,
					{ force: true }
				);
				expect( response.status ).toBe( 200 );
			} catch ( error ) {
				cleanupErrors.push( error );
			}
		}
		try {
			expect( await show_task_list( restApi, 'setup' ) ).toBe( true );
		} catch ( error ) {
			cleanupErrors.push( error );
		}

		if ( primaryFailure && cleanupErrors.length > 0 ) {
			throw new AggregateError(
				[ primaryFailure, ...cleanupErrors ],
				'Add Product task lifecycle and cleanup both failed.'
			);
		}
		if ( primaryFailure ) {
			throw primaryFailure;
		}
		if ( cleanupErrors.length > 0 ) {
			throw new AggregateError(
				cleanupErrors,
				'Add Product task cleanup failed.'
			);
		}
	} );
} );
