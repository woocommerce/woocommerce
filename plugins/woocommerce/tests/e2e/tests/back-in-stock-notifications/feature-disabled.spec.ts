/**
 * Internal dependencies
 */
import { expect, request, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import {
	BIS_FEATURE_OPTION,
	bisFormLocator,
	test,
} from '../../utils/back-in-stock-notifications';
import { setOption } from '../../utils/options';

test.describe(
	'Back in Stock Notifications — feature disabled',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.beforeAll( async ( { baseURL } ) => {
			await setOption( request, baseURL!, BIS_FEATURE_OPTION, 'no' );
		} );

		test( 'no signup form on an out-of-stock product page', async ( {
			page,
			product,
		} ) => {
			await page.goto( product.permalink );

			await expect( bisFormLocator( page ) ).toHaveCount( 0 );
		} );

		test.describe( 'admin settings', () => {
			test.use( { storageState: ADMIN_STATE_PATH } );

			test( 'no Customer stock notifications settings section', async ( {
				page,
			} ) => {
				await page.goto(
					'wp-admin/admin.php?page=wc-settings&tab=products'
				);

				await expect(
					page.getByRole( 'link', {
						name: 'Customer stock notifications',
					} )
				).toHaveCount( 0 );
			} );
		} );
	}
);
