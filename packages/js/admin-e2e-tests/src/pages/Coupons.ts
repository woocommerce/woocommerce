/**
 * Internal dependencies
 */
import { BasePage } from './BasePage';

export class Coupons extends BasePage {
	url = 'wp-admin/edit.php?post_type=shop_coupon&legacy_coupon_menu=1';

	async isDisplayed(): Promise< void > {
		// This is a smoke test that ensures the single page was rendered without crashing
		await this.page.waitForSelector( '#woocommerce-layout__primary' );
	}
}
