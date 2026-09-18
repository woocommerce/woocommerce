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

	// Ids this spec creates, so teardown can delete its own products and nobody else's.
	const createdProductIds: number[] = [];

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
		//
		// Only this spec's own products are deleted. The site is shared with the rest of the
		// run, so sweeping every id the products endpoint returns would take other specs'
		// fixtures with it.
		// Copy rather than drain: an id stays tracked until the batch confirms it deleted, so a
		// later afterEach retries whatever this one could not remove.
		const idsToDelete = [ ...createdProductIds ];
		// Every teardown call runs before anything is asserted: a failed delete must not stop
		// the task list from being unhidden, or the rest of this serial project inherits it.
		const deleteResponse = idsToDelete.length
			? await restApi.post( `${ WC_API_PATH }/products/batch`, {
					delete: idsToDelete,
			  } )
			: null;
		const taskListShown = await show_task_list( restApi, 'setup' );

		// On a passing run the test deletes its own product, so there is nothing left here
		// and no response to check. Asserting 200 unconditionally would pass whether a
		// delete happened or not.
		if ( deleteResponse !== null ) {
			expect( deleteResponse.status ).toBe( 200 );
			// The batch endpoint reports per-item failures in the body and still
			// returns 200, so the status on its own says nothing about whether the
			// products actually went.
			// An extension filter can reshape these results, so an entry that is missing,
			// errored or carries no id counts as not deleted rather than throwing here.
			const batchDeleteResults = deleteResponse.data?.delete;
			const deleted: Array< { id?: number; error?: unknown } | null > =
				Array.isArray( batchDeleteResults ) ? batchDeleteResults : [];
			const confirmedIds = new Set(
				deleted
					.filter( ( item ) => item && ! item.error )
					.map( ( item ) => item?.id )
			);
			const unconfirmedIds = idsToDelete.filter(
				( id ) => ! confirmedIds.has( id )
			);
			createdProductIds.splice(
				0,
				createdProductIds.length,
				...unconfirmedIds
			);
			expect( deleted ).toHaveLength( idsToDelete.length );
			expect( unconfirmedIds ).toEqual( [] );
		}
		expect( taskListShown ).toBe( true );
	} );

	test.afterAll( async ( { restApi } ) => {
		await restApi.post( `${ WC_ADMIN_API_PATH }/onboarding/profile`, {
			skipped: false,
		} );
	} );

	test( 'Products page redirects to the add product task until a product exists', async ( {
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
		createdProductIds.push( createdProductId );
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
		// Stop tracking only once the delete is confirmed. Splicing first means a
		// failed delete leaves a published product that the afterEach sweep no
		// longer knows about -- in a spec whose whole premise is an empty catalog.
		createdProductIds.splice(
			createdProductIds.indexOf( createdProductId ),
			1
		);

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
