/**
 * External dependencies
 */
import { afterAll, beforeAll, describe, it } from '@jest/globals';
import { takeScreenshotFor } from '@woocommerce/e2e-environment';

/**
 * Internal dependencies
 */
import { Login } from '../../pages/Login';
import { OnboardingWizard } from '../../pages/OnboardingWizard';
import { PaymentsSetup } from '../../pages/PaymentsSetup';
import { WcHomescreen } from '../../pages/WcHomescreen';
import { BankAccountTransferSetup } from '../../sections/payment-setup/BankAccountTransferSetup';
import { waitForTimeout } from '../../utils/actions';
import { WcSettings } from '../../pages/WcSettings';

export const testAdminPaymentSetupTask = () => {
	describe( 'Payment setup task', () => {
		const profileWizard = new OnboardingWizard( page );
		const homeScreen = new WcHomescreen( page );
		const paymentsSetup = new PaymentsSetup( page );
		const bankTransferSetup = new BankAccountTransferSetup( page );
		const login = new Login( page );
		const settings = new WcSettings( page );

		beforeAll( async () => {
			await login.login();

			// This makes this test more isolated, by always navigating to the
			// profile wizard and skipping, this behaves the same as if the
			// profile wizard had not been run yet and the user is redirected
			// to it when trying to go to wc-admin.
			await profileWizard.navigate();
			await profileWizard.skipStoreSetup();

			await homeScreen.isDisplayed();
			await takeScreenshotFor( 'Payment setup task home screen' );
			await homeScreen.possiblyDismissWelcomeModal();
		} );

		afterAll( async () => {
			await login.logout();
		} );

		it( 'Can visit the payment setup task from the homescreen if the setup wizard has been skipped', async () => {
			await homeScreen.clickOnTaskList( 'Set up payments' );
			await paymentsSetup.possiblyCloseHelpModal();
			await paymentsSetup.isDisplayed();
		} );

		it( 'Saving valid bank account transfer details enables the payment method', async () => {
			await paymentsSetup.showOtherPaymentMethods();
			await paymentsSetup.goToPaymentMethodSetup( 'bacs' );
			await bankTransferSetup.saveAccountDetails( {
				accountNumber: '1234',
				accountName: 'Savings',
				bankName: 'TestBank',
				sortCode: '12',
				iban: '12 3456 7890',
				swiftCode: 'ABBA',
			} );
			await waitForTimeout( 1500 );
			expect( await settings.paymentMethodIsEnabled( 'bacs' ) ).toBe(
				true
			);
		} );

		it( 'Enabling cash on delivery enables the payment method', async () => {
			await settings.cleanPaymentMethods();
			await homeScreen.navigate();
			await homeScreen.isDisplayed();
			await waitForTimeout( 1000 );
			await homeScreen.clickOnTaskList( 'Set up payments' );
			await paymentsSetup.possiblyCloseHelpModal();
			await paymentsSetup.isDisplayed();
			await paymentsSetup.showOtherPaymentMethods();
			await paymentsSetup.enableCashOnDelivery();
			await waitForTimeout( 1500 );
			expect( await settings.paymentMethodIsEnabled( 'cod' ) ).toBe(
				true
			);
		} );
	} );
};
