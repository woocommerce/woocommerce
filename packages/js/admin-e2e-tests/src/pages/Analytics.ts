/**
 * Internal dependencies
 */
import { BasePage } from './BasePage';

export type AnalyticsSection =
	| 'overview'
	| 'products'
	| 'revenue'
	| 'orders'
	| 'variations'
	| 'categories'
	| 'coupons'
	| 'taxes'
	| 'downloads'
	| 'stock'
	| 'settings';

export class Analytics extends BasePage {
	// If you need to navigate to the base analytics page you can go to the overview
	url = 'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview';

	// If you need to go to a specific single page of the analytics use `navigateToSection`
	async navigateToSection( section: AnalyticsSection ): Promise< void > {
		await this.goto( this.url.replace( 'overview', section ) );
	}

	async isDisplayed(): Promise< void > {
		// This is a smoke test that ensures the single page was rendered without crashing
		await this.page.waitForSelector( '#woocommerce-layout__primary' );
	}
}
