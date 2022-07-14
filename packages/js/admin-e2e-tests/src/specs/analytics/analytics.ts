/**
 * External dependencies
 */
import { afterAll, beforeAll, describe, it } from '@jest/globals';
/**
 * Internal dependencies
 */
import { Analytics } from '../../pages/Analytics';
import { Customers } from '../../pages/Customers';
import { Login } from '../../pages/Login';

export const testAdminAnalyticsPages = () => {
	describe( 'Analytics pages', () => {
		const analyticsPage = new Analytics( page );
		const customersPage = new Customers( page );
		const login = new Login( page );

		beforeAll( async () => {
			await login.login();
		} );
		afterAll( async () => {
			await login.logout();
		} );

		it( 'A user can view the analytics overview without it crashing', async () => {
			await analyticsPage.navigate();
			await analyticsPage.isDisplayed();
		} );

		it( 'A user can view the analytics for products without it crashing', async () => {
			await analyticsPage.navigateToSection( 'products' );
			await analyticsPage.isDisplayed();
		} );

		it( 'A user can view the analytics for revenue without it crashing', async () => {
			await analyticsPage.navigateToSection( 'revenue' );
			await analyticsPage.isDisplayed();
		} );

		it( 'A user can view the analytics for orders without it crashing', async () => {
			await analyticsPage.navigateToSection( 'orders' );
			await analyticsPage.isDisplayed();
		} );

		it( 'A user can view the analytics for variations without it crashing', async () => {
			await analyticsPage.navigateToSection( 'variations' );
			await analyticsPage.isDisplayed();
		} );

		it( 'A user can view the analytics for categories without it crashing', async () => {
			await analyticsPage.navigateToSection( 'categories' );
			await analyticsPage.isDisplayed();
		} );

		it( 'A user can view the analytics for coupons without it crashing', async () => {
			await analyticsPage.navigateToSection( 'coupons' );
			await analyticsPage.isDisplayed();
		} );

		it( 'A user can view the analytics for taxes without it crashing', async () => {
			await analyticsPage.navigateToSection( 'taxes' );
			await analyticsPage.isDisplayed();
		} );

		it( 'A user can view the analytics for downloads without it crashing', async () => {
			await analyticsPage.navigateToSection( 'downloads' );
			await analyticsPage.isDisplayed();
		} );

		it( 'A user can view the analytics for stock without it crashing', async () => {
			await analyticsPage.navigateToSection( 'stock' );
			await analyticsPage.isDisplayed();
		} );

		it( 'A user can view the analytics for settings without it crashing', async () => {
			await analyticsPage.navigateToSection( 'settings' );
			await analyticsPage.isDisplayed();
		} );

		it( 'A user can view the customers page without it crashing', async () => {
			await customersPage.navigate();
			await customersPage.isDisplayed();
		} );
	} );
};
