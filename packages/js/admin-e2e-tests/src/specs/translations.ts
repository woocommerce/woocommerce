/**
 * External dependencies
 */
import { afterAll, beforeAll, describe, it } from '@woocommerce/e2e-utils';
/**
 * Internal dependencies
 */
import { Login } from '../pages/Login';
import { OnboardingWizard } from '../pages/OnboardingWizard';
import { WcHomescreen } from '../pages/WcHomescreen';
import { Analytics } from '../pages/Analytics';
import { switchLanguage } from '../fixtures';

export const testAdminTranslations = () => {
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

		it( 'tests translations in PHP class, client, and component', async () => {
			await homeScreen.isDisplayed();
			await homeScreen.possiblyDismissWelcomeModal();
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

			const matchDatePickerContentButton = async ( expected: string ) => {
				await expect( page ).toMatchElement(
					'.woocommerce-filters-date__button-group button',
					{
						text: expected,
					}
				);
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
			await matchDatePickerContentButton( 'Update' );

			await switchLanguage( 'es_ES' );
			await page.reload();
			await analyticsPage.isDisplayed();
			await analyticsPage.click( '.woocommerce-filters-filter button' );
			await matchDatePickerContentButton( 'Actualizar' );

			// Rendimiento
			await switchLanguage( 'en_US' );
		} );
	} );
};
