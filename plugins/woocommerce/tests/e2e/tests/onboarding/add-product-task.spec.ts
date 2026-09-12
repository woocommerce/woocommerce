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

	test.afterEach( async ( { restApi } ) => {
		// The test leaves a product behind if it fails before its own delete, and leaves the
		// setup task list hidden if it fails between hiding and showing it. Both are torn down
		// here rather than in the body so that a failure reports the assertion that failed
		// instead of a cleanup error raised on the way out.
		const { data } = await restApi.get( `${ WC_API_PATH }/products`, {
			_fields: 'id',
			per_page: 100,
			status: 'any',
		} );
		// Every teardown call runs before anything is asserted: a failed delete must not stop
		// the task list from being unhidden, or the rest of this serial project inherits it.
		const { status } = await restApi.post(
			`${ WC_API_PATH }/products/batch`,
			{
				delete: data.map( ( { id } ) => id ),
			}
		);
		const taskListShown = await show_task_list( restApi, 'setup' );
		expect( status ).toBe( 200 );
		expect( taskListShown ).toBe( true );
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
		const productName = `Add product task product ${ Date.now() }`;

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
		await expect( page.locator( '#menu-posts-product' ) ).not.toHaveClass(
			/wp-has-current-submenu/
		);

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
		expect( productResponse.status ).toBe( 201 );
		expect(
			Number.isSafeInteger( createdProductId ) && createdProductId > 0
		).toBe( true );

		await page.goto( 'wp-admin/edit.php?post_type=product' );

		await expect( page.locator( '.wp-list-table' ) ).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: productName, exact: true } )
		).toBeVisible();

		const deleteResponse = await restApi.delete(
			`${ WC_API_PATH }/products/${ createdProductId }`,
			{ force: true }
		);
		expect( deleteResponse.status ).toBe( 200 );

		await page.goto( 'wp-admin/admin.php?page=wc-admin&task=products' );
		await page
			.getByRole( 'menuitem', { name: 'Physical product' } )
			.click();
		await expect(
			page.locator(
				'#menu-posts-product .wp-submenu li.current > a[href="post-new.php?post_type=product"]'
			)
		).toBeVisible();
	} );
} );
