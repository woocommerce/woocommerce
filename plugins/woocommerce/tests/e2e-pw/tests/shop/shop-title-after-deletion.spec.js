/**
 * Internal dependencies
 */
import { test as baseTest, expect, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { WP_API_PATH } from '../../utils/api-client';

// test case for bug https://github.com/woocommerce/woocommerce/pull/46429
const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	page: async ( { page, restApi }, use ) => {
		const response = await restApi.get(
			`${ WP_API_PATH }/pages?slug=shop`,
			{
				data: {
					_fields: [ 'id' ],
				},
			}
		);

		const pages = await response.data;
		const pageId = pages[ 0 ].id;

		await restApi.delete( `${ WP_API_PATH }/pages/${ pageId }`, {
			data: {
				force: false,
			},
		} );

		await use( page );

		await restApi.post( `${ WP_API_PATH }/pages/${ pageId }`, {
			data: {
				status: 'publish',
			},
		} );
	},
} );

test(
	'Check the title of the shop page after the page has been deleted',
	{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
	async ( { page } ) => {
		await page.goto( 'shop/' );
		expect( await page.title() ).toBe(
			'Shop – WooCommerce Core E2E Test Suite'
		);
	}
);
