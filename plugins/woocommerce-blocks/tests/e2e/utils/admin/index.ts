/**
 * External dependencies
 */
import { Admin as CoreAdmin } from '@wordpress/e2e-test-utils-playwright';

export class Admin extends CoreAdmin {
	async visitWidgetEditor() {
		await this.page.goto( '/wp-admin/widgets.php' );
		await this.page
			.getByLabel( 'Welcome to block Widgets' )
			.getByRole( 'button', { name: 'Close' } )
			.click();
	}
}
