/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';
import type {
	Page,
	Locator,
	Response as PlaywrightResponse,
} from '@playwright/test';

/**
 * Internal dependencies
 */
import { admin } from '../../test-data/data';
import { tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const EXPECTED_SECTION_HEADERS = [ 'Performance', 'Charts', 'Leaderboards' ];

let userId: number;
let headings_sections: Locator;
let buttons_ellipsis: Locator;
let menuitem_moveDown: Locator;
let page: Page;

const base64String = Buffer.from(
	`${ admin.username }:${ admin.password }`
).toString( 'base64' );

const headers = {
	Authorization: `Basic ${ base64String }`,
	cookie: '',
};

// Call this before the click that triggers the save, and await the returned
// promise after it — registering the listener after the click can miss a
// response that already arrived.
const waitForUserPrefsSave = () =>
	page.waitForResponse(
		( res ) =>
			res.url().includes( `/users/${ userId }` ) &&
			res.request().method() !== 'GET'
	);

const expectSaved = ( response: PlaywrightResponse ) =>
	expect(
		response.ok(),
		`${ response.status() } ${ response.url() }`
	).toBeTruthy();

const resetSections = async () => {
	const response =
		await test.step( `Send POST request to reset all sections`, async () => {
			const pageRequest = page.request;
			const url = `./wp-json/wp/v2/users/${ userId }`;
			const params = { _locale: 'user' };
			const data = {
				id: userId,
				woocommerce_meta: {
					dashboard_sections: '',
				},
			};

			return await pageRequest.post( url, {
				data,
				params,
				headers,
			} );
		} );

	await test.step( `Assert response status is OK`, async () => {
		expect( response.ok() ).toBeTruthy();
	} );

	await test.step( `Verify that sections were reset`, async () => {
		const { woocommerce_meta } = await response.json();
		const { dashboard_sections } = woocommerce_meta;

		expect( dashboard_sections ).toHaveLength( 0 );
	} );
};

test.describe(
	'Analytics pages',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeAll( async ( { browser } ) => {
			page = await browser.newPage();

			await test.step( `Send GET request to get the current user id`, async () => {
				const pageRequest = page.request;
				const data = {
					_fields: 'id',
				};
				const response = await pageRequest.get(
					'./wp-json/wp/v2/users/me',
					{
						data,
						headers,
					}
				);
				const { id } = await response.json();

				userId = id;
			} );

			await resetSections();

			await test.step( `Initialize locators`, async () => {
				const pattern = new RegExp(
					EXPECTED_SECTION_HEADERS.join( '|' )
				);

				headings_sections = page.getByRole( 'heading', {
					name: pattern,
				} );

				buttons_ellipsis = page.getByRole( 'button', {
					name: 'Choose which',
				} );

				menuitem_moveDown = page.getByRole( 'menuitem', {
					name: 'Move down',
				} );
			} );
		} );

		test.beforeEach( async () => {
			await test.step( `Go to Analytics > Overview`, async () => {
				await page.goto(
					'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
				);
			} );
		} );

		test.afterEach( async () => {
			await resetSections();
		} );

		test.afterAll( async () => {
			await page.close();
		} );

		test( 'persists reordered Analytics sections after reload', async () => {
			await test.step( `Assert the default sections and their real controls`, async () => {
				await expect( headings_sections ).toHaveText(
					EXPECTED_SECTION_HEADERS
				);
				await expect( buttons_ellipsis ).toHaveCount( 3 );

				for ( const button of await buttons_ellipsis.all() ) {
					await expect( button ).toBeVisible();
				}
			} );

			await test.step( `Move Performance below Charts and save`, async () => {
				await buttons_ellipsis.first().click();
				const savePromise = waitForUserPrefsSave();
				await menuitem_moveDown.click();
				expectSaved( await savePromise );
			} );

			const reorderedSections = [
				'Charts',
				'Performance',
				'Leaderboards',
			];
			await expect( headings_sections ).toHaveText( reorderedSections );

			await page.reload();

			await expect( headings_sections ).toHaveText( reorderedSections );
			await expect( buttons_ellipsis ).toHaveCount( 3 );
		} );
	}
);
