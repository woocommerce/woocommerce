/**
 * Internal dependencies
 */
import { Login } from '../pages/Login';
import { OnboardingWizard } from '../pages/OnboardingWizard';
import { WcHomescreen } from '../pages/WcHomescreen';
import { Analytics } from '../pages/Analytics';
import { switchLanguage } from '../fixtures';

/* eslint-disable @typescript-eslint/no-var-requires */
const { afterAll, beforeAll, describe, it } = require( '@jest/globals' );
/* eslint-enable @typescript-eslint/no-var-requires */

const testAdminTranslations = () => {
	describe( 'Test client, package, and PHP class translations,', () => {
		const profileWizard = new OnboardingWizard( page );
		const homeScreen = new WcHomescreen( page );
		const analyticsPage = new Analytics( page );
		const login = new Login( page );

		beforeAll( async () => {
			await login.login();
			await switchLanguage( 'en_US' );
			await profileWizard.navigate();
			await profileWizard.skipStoreSetup();
		} );
		afterAll( async () => {} );

		it( 'translates PHP class, client, and component', async () => {
			await homeScreen.isDisplayed();
			await homeScreen.possiblyDismissWelcomeModal();
			// Tests menu translation (PHP code) and homescreen translation (react client)
			await homeScreen.navigate();
			await homeScreen.isDisplayed();
			const matchMenu = async ( expected: string ) => {
				await expect( page ).toMatchElement(
					'.toplevel_page_woocommerce ul li.wp-first-item a',
					{
						text: expected,
					}
				);
			};
			const matchH1 = async ( expected: string ) => {
				await expect( page ).toMatchElement( 'h1', {
					text: expected,
				} );
			};

			matchMenu( 'Home' );
			matchH1( 'Home' );

			await switchLanguage( 'es_ES' );
			await page.reload();
			matchMenu( 'Inicio' );
			matchH1( 'Inicio' );

			await switchLanguage( 'en_US' );

			// Navigate to the Analytics page and test the component translation
			await analyticsPage.navigate();
			await analyticsPage.isDisplayed();
			await analyticsPage.click( '.woocommerce-filters-filter button' );

			await expect( page ).toMatchElement(
				'.woocommerce-filters-date__tabs .components-tab-panel__tabs button:first-child',
				{
					text: 'Presets',
				}
			);
		} );
	} );
};

module.exports = { testAdminTranslations };
