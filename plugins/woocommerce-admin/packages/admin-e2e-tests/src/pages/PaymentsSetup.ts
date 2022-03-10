/**
 * Internal dependencies
 */
import {
	waitForElementByText,
	getElementByText,
	waitForTimeout,
} from '../utils/actions';
import { BasePage } from './BasePage';

type PaymentMethodWithSetupButton =
	| 'wcpay'
	| 'stripe'
	| 'paypal'
	| 'klarna_payments'
	| 'mollie'
	| 'bacs';

type PaymentMethod = PaymentMethodWithSetupButton | 'cod';

export class PaymentsSetup extends BasePage {
	url = 'wp-admin/admin.php?page=wc-admin&task=payments';

	async isDisplayed() {
		await waitForElementByText( 'h1', 'Set up payments' );
	}

	async closeHelpModal() {
		await this.clickButtonWithText( 'Got it' );
	}

	async goToPaymentMethodSetup( method: PaymentMethodWithSetupButton ) {
		const selector = `.woocommerce-task-payment-${ method } button`;
		await this.page.waitForSelector( selector );
		const button = await this.page.$( selector );

		if ( ! button ) {
			throw new Error(
				`Could not find button with selector: ${ selector }`
			);
		} else {
			await button.click();
		}
	}

	async methodHasBeenSetup( method: PaymentMethod ) {
		const selector = `.woocommerce-task-payment-${ method }`;
		await this.page.waitForSelector( selector );
		expect(
			await getElementByText( '*', 'Manage', selector )
		).toBeDefined();
	}

	async enableCashOnDelivery() {
		await this.page.waitForSelector( '.woocommerce-task-payment-cod' );
		await this.clickButtonWithText( 'Enable' );
	}
}
