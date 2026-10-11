/**
 * External dependencies
 */
import { WP_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test, expect, tags } from '../../fixtures/fixtures';
import { getFakeUser } from '../../utils/data';
import { logIn } from '../../utils/login';

const OVERVIEW_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview';

// The shared admin has the tour marked as seen in site setup. Clearing that for
// this test would show the tour to parallel specs on the Overview page, and the
// tour blocks clicks outside the menu it points at, so this test runs as its own
// admin instead.
const testAsNewAdmin = test.extend< {
	newAdmin: { id: number; username: string; password: string };
} >( {
	storageState: { cookies: [], origins: [] },
	newAdmin: async ( { restApi }, use ) => {
		const userData = getFakeUser( 'administrator' );
		const { data: user } = await restApi.post( `${ WP_API_PATH }/users`, {
			...userData,
			roles: [ 'administrator' ],
		} );

		await use( {
			id: user.id,
			username: userData.username,
			password: userData.password,
		} );

		await restApi.delete( `${ WP_API_PATH }/users/${ user.id }`, {
			force: true,
			reassign: 1,
		} );
	},
} );

testAsNewAdmin.skip(
	!! process.env.IS_MULTISITE,
	'The REST API cannot delete users on multisite, so the new admin cannot be cleaned up'
);

testAsNewAdmin(
	'points new admins at the Performance menu until they open it',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	async ( { page, restApi, newAdmin } ) => {
		const tourHeading = page.getByRole( 'heading', {
			name: 'Choose which metrics to display',
		} );
		const performanceMenu = page
			.locator( '.woocommerce-section-header', {
				has: page.getByRole( 'heading', { name: 'Performance' } ),
			} )
			.getByRole( 'button', { name: 'Choose which' } );

		await testAsNewAdmin.step( 'log in as the new admin', async () => {
			await page.goto( 'wp-login.php' );
			await logIn( page, newAdmin.username, newAdmin.password );
		} );

		await testAsNewAdmin.step(
			'the tour shows next to the Performance menu',
			async () => {
				await page.goto( OVERVIEW_URL );
				await expect( tourHeading ).toBeVisible();

				const tourBox = await page
					.locator( '.woocommerce-tour-kit-step' )
					.boundingBox();
				const menuBox = await performanceMenu.boundingBox();

				// The tour sits beside the menu it points at. Without its target it
				// renders elsewhere on the page and this fails.
				expect(
					Math.abs(
						tourBox.y +
							tourBox.height / 2 -
							( menuBox.y + menuBox.height / 2 )
					)
				).toBeLessThan( tourBox.height );
				expect(
					menuBox.x - ( tourBox.x + tourBox.width )
				).toBeLessThan( 60 );
			}
		);

		await testAsNewAdmin.step(
			'opening the menu dismisses the tour',
			async () => {
				await performanceMenu.click();
				await expect( tourHeading ).toBeHidden();
				await expect(
					page.getByRole( 'menuitemcheckbox', {
						name: 'Total sales',
					} )
				).toBeVisible();
			}
		);

		await testAsNewAdmin.step(
			'the tour stays dismissed for this admin',
			async () => {
				await expect
					.poll( async () => {
						const { data } = await restApi.get(
							`${ WP_API_PATH }/users/${ newAdmin.id }`,
							{ context: 'edit' }
						);
						return data.woocommerce_meta
							.dashboard_performance_tour_shown;
					} )
					.toBe( 'yes' );

				await page.reload();
				await expect(
					page.getByRole( 'heading', { name: 'Performance' } )
				).toBeVisible();
				await expect( tourHeading ).toBeHidden();
			}
		);
	}
);
