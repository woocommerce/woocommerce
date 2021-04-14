/**
 * Internal dependencies
 */
import { waitForElementByText } from '../utils/actions';
import { BasePage } from './BasePage';
import { getElementByText } from '../utils/actions';

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
		await waitForElementByText( 'h1', 'Choose payment methods' );
	}

	async closeHelpModal() {
		await this.clickButtonWithText( 'Got it' );
	}

	async goToPaymentMethodSetup( method: PaymentMethodWithSetupButton ) {
		const selector = `.woocommerce-task-payment-${ method } button`;
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
		await getElementByText( 'button', 'Manage', `.woocommerce-task-payment-${ method }` )
	}

	async enableCashOnDelivery() {
		await this.clickButtonWithText( 'Enable' );
	}
}
