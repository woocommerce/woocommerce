/**
 * Internal dependencies
 */
import { waitForElementByText } from '../utils/actions';
import { BasePage } from './BasePage';

export class WpSettings extends BasePage {
	url = 'wp-admin/options-permalink.php';

	async openPermalinkSettings() {
		await waitForElementByText( 'h1', 'Permalink Settings' );
	}

	async saveSettings() {
		await this.click( '#submit' );
		await this.page.waitForNavigation( {
			waitUntil: 'networkidle0',
		} );
	}
}
