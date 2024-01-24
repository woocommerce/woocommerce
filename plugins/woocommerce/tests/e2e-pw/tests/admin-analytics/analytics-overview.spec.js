const { test, expect, Page, Locator } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );

const EXPECTED_SECTION_HEADERS = [ 'Performance', 'Charts', 'Leaderboards' ];

/**
 * @type {number}
 */
let userId;
/**
 * @type {Locator}
 */
let headings_sections;
/**
 * @type {Locator}
 */
let heading_performance;
/**
 * @type {Locator}
 */
let buttons_ellipsis;
/**
 * @type {Locator}
 */
let menuitem_moveUp;
/**
 * @type {Locator}
 */
let menuitem_moveDown;
/**
 * @type {Page}
 */
let page;

const base64String = Buffer.from(
	`${ admin.username }:${ admin.password }`
).toString( 'base64' );

const headers = {
	Authorization: `Basic ${ base64String }`,
	cookie: '',
};

const hidePerformanceSection = async () => {
	const response =
		await test.step( `Send POST request to hide Performance section`, async () => {
			const request = page.request;
			const url = `/wp-json/wp/v2/users/${ userId }`;
			const params = { _locale: 'user' };
			const dashboard_sections = JSON.stringify( [
				{ key: 'store-performance', isVisible: false },
			] );
			const data = {
				id: userId,
				woocommerce_meta: {
					dashboard_sections,
				},
			};

			return await request.post( url, {
				data,
				params,
				headers,
			} );
		} );

	await test.step( `Assert response status is OK`, async () => {
		expect( response.ok() ).toBeTruthy();
	} );

	await test.step( `Inspect the response payload to verify that Performance section was successfully hidden`, async () => {
		const { woocommerce_meta } = await response.json();
		const { dashboard_sections } = woocommerce_meta;
		const sections = JSON.parse( dashboard_sections );
		const performanceSection = sections.find(
			( { key } ) => key === 'store-performance'
		);
		expect( performanceSection.isVisible ).toBeFalsy();
	} );
};

const resetSections = async () => {
	const response =
		await test.step( `Send POST request to reset all sections`, async () => {
			const request = page.request;
			const url = `/wp-json/wp/v2/users/${ userId }`;
			const params = { _locale: 'user' };
			const data = {
				id: userId,
				woocommerce_meta: {
					dashboard_sections: '',
				},
			};

			return await request.post( url, {
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

test.describe( 'Analytics pages', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();

		await test.step( `Send GET request to get the current user id`, async () => {
			const request = page.request;
			const data = {
				_fields: 'id',
			};
			const response = await request.get( '/wp-json/wp/v2/users/me', {
				data,
				headers,
			} );
			const { id } = await response.json();

			userId = id;
		} );

		await resetSections();

		await test.step( `Initialize locators`, async () => {
			const pattern = new RegExp( EXPECTED_SECTION_HEADERS.join( '|' ) );

			headings_sections = page.getByRole( 'heading', {
				name: pattern,
			} );

			heading_performance = page.getByRole( 'heading', {
				name: 'Performance',
			} );

			buttons_ellipsis = page.getByRole( 'button', {
				name: 'Choose which',
			} );

			menuitem_moveUp = page.getByRole( 'menuitem', {
				name: 'Move up',
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

	test( 'a user should see 3 sections by default - Performance, Charts, and Leaderboards', async () => {
		for ( const expectedSection of EXPECTED_SECTION_HEADERS ) {
			await test.step( `Assert that the "${ expectedSection }" section is visible`, async () => {
				await expect(
					headings_sections.filter( { hasText: expectedSection } )
				).toBeVisible();
			} );
		}
	} );

	test.describe( 'moving sections', () => {
		test( 'should not display move up for the top, or move down for the bottom section', async () => {
			await test.step( `Check the top section`, async () => {
				await buttons_ellipsis.first().click();
				await expect( menuitem_moveUp ).toBeHidden();
				await expect( menuitem_moveDown ).toBeVisible();
				await page.keyboard.press( 'Escape' );
			} );

			await test.step( `Check the bottom section`, async () => {
				await buttons_ellipsis.last().click();
				await expect( menuitem_moveDown ).toBeHidden();
				await expect( menuitem_moveUp ).toBeVisible();
				await page.keyboard.press( 'Escape' );
			} );
		} );

		test( 'should allow a user to move a section down', async () => {
			const firstSection = await headings_sections.first().innerText();
			const secondSection = await headings_sections.nth( 1 ).innerText();

			await test.step( `Move first section down`, async () => {
				await buttons_ellipsis.first().click();
				await menuitem_moveDown.click();
			} );

			await test.step( `Expect the second section to become first, and first becomes second.`, async () => {
				await expect( headings_sections.first() ).toHaveText(
					secondSection
				);

				await expect( headings_sections.nth( 1 ) ).toHaveText(
					firstSection
				);
			} );
		} );

		test( 'should allow a user to move a section up', async () => {
			const firstSection = await headings_sections.first().innerText();
			const secondSection = await headings_sections.nth( 1 ).innerText();

			await test.step( `Move second section up`, async () => {
				await buttons_ellipsis.nth( 1 ).click();
				await menuitem_moveUp.click();
			} );

			await test.step( `Expect second section becomes first section, first becomes second`, async () => {
				await expect( headings_sections.first() ).toHaveText(
					secondSection
				);
				await expect( headings_sections.nth( 1 ) ).toHaveText(
					firstSection
				);
			} );
		} );
	} );

	test( 'should allow a user to remove a section', async () => {
		await test.step( `Remove the Performance section`, async () => {
			await page
				.getByRole( 'button', {
					name: 'Choose which analytics to display and the section name',
				} )
				.click();
			await page
				.getByRole( 'menuitem', { name: 'Remove section' } )
				.click();
			await page.waitForResponse(
				( response ) =>
					response.url().includes( '/users' ) && response.ok()
			);
		} );

		await test.step( `Expect the Performance section to be hidden`, async () => {
			await expect( headings_sections ).toHaveCount( 2 );
			await expect( heading_performance ).toBeHidden();
		} );
	} );

	test( 'should allow a user to add a section back in', async () => {
		await hidePerformanceSection( page );
		await page.reload();

		await test.step( `Add the Performance section back in.`, async () => {
			await page.getByTitle( 'Add more sections' ).click();
			await page.getByTitle( 'Add Performance section' ).click();
		} );

		await test.step( `Expect the Performance section to be added back.`, async () => {
			await expect( heading_performance ).toBeVisible();
		} );
	} );
} );
