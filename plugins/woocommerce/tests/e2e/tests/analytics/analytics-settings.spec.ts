/**
 * External dependencies
 */
import { test, expect, request } from '@playwright/test';
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { tags } from '../../fixtures/fixtures';
import { setOption, deleteOption } from '../../utils/options';
import { ADMIN_STATE_PATH } from '../../playwright.config';

let page: Page;

test.describe(
	'Analytics Settings - Scheduled Import',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeAll( async ( { browser } ) => {
			page = await browser.newPage();
		} );

		test.beforeEach( async () => {
			await test.step( `Go to Analytics > Settings`, async () => {
				await page.goto(
					'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Fsettings'
				);
			} );
		} );

		test.afterAll( async ( { baseURL } ) => {
			const cleanupErrors: unknown[] = [];

			try {
				await setOption(
					request,
					baseURL,
					'woocommerce_analytics_scheduled_import',
					'yes'
				);
			} catch ( error ) {
				cleanupErrors.push( error );
			}

			try {
				await page.close();
			} catch ( error ) {
				cleanupErrors.push( error );
			}

			if ( cleanupErrors.length ) {
				throw new AggregateError(
					cleanupErrors,
					'Failed to restore Analytics Settings test state.'
				);
			}
		} );

		test( 'manages scheduled import mode confirmation and persistence', async ( {
			baseURL,
		} ) => {
			const immediately = page.getByRole( 'radio', {
				name: /Immediately/i,
			} );
			const scheduled = page.getByRole( 'radio', {
				name: /Scheduled \(recommended\)/i,
			} );
			const confirmationHeading = page.getByRole( 'heading', {
				name: 'Are you sure?',
			} );
			const successNotice = page
				.getByText( 'Your settings have been successfully saved.' )
				.first();

			await test.step( 'Use Immediate when the option is absent', async () => {
				await deleteOption(
					request,
					baseURL,
					'woocommerce_analytics_scheduled_import'
				);
				await page.reload();
				await expect( immediately ).toBeChecked();
			} );

			await test.step( 'Cancel a scheduled to immediate change', async () => {
				await setOption(
					request,
					baseURL,
					'woocommerce_analytics_scheduled_import',
					'yes'
				);
				await page.reload();
				await expect( scheduled ).toBeChecked();

				await immediately.click();
				await expect( confirmationHeading ).toBeVisible();
				await page
					.getByRole( 'button', { name: /Cancel/i, exact: false } )
					.click();
				await expect( confirmationHeading ).toBeHidden();
				await expect( scheduled ).toBeChecked();
			} );

			await test.step( 'Confirm and persist immediate mode', async () => {
				await immediately.click();
				await expect( confirmationHeading ).toBeVisible();
				await page
					.getByRole( 'button', {
						name: /Confirm/i,
						exact: false,
					} )
					.click();
				await page
					.getByRole( 'button', { name: 'Save settings' } )
					.click();
				await expect( successNotice ).toBeVisible();
				await page.reload();
				await expect( immediately ).toBeChecked();
			} );

			await test.step( 'Return to scheduled mode without a warning', async () => {
				await scheduled.click();
				await expect( confirmationHeading ).toBeHidden();
				await page
					.getByRole( 'button', { name: 'Save settings' } )
					.click();
				await expect( successNotice ).toBeVisible();
				await page.reload();
				await expect( scheduled ).toBeChecked();
			} );
		} );
	}
);
