/**
 * Internal dependencies
 */
import { BasePage } from '../../pages/BasePage';

/* eslint-disable @typescript-eslint/no-var-requires */
const { clearAndFillInput } = require( '@woocommerce/e2e-utils' );
/* eslint-enable @typescript-eslint/no-var-requires */

type AccountDetails = {
	accountName: string;
	accountNumber: string;
	bankName: string;
	sortCode: string;
	iban: string;
	swiftCode: string;
};

export class BankAccountTransferSetup extends BasePage {
	url = 'wp-admin/admin.php?page=wc-admin&task=payments&method=bacs';

	async saveAccountDetails( {
		accountName,
		accountNumber,
		bankName,
		sortCode,
		iban,
		swiftCode,
	}: AccountDetails ): Promise< void > {
		await clearAndFillInput( '[placeholder="Account name"]', accountName );
		await clearAndFillInput(
			'[placeholder="Account number"]',
			accountNumber
		);
		await clearAndFillInput( '[placeholder="Bank name"]', bankName );
		await clearAndFillInput( '[placeholder="Sort code"]', sortCode );
		await clearAndFillInput( '[placeholder="IBAN"]', iban );
		await clearAndFillInput( '[placeholder="BIC / Swift"]', swiftCode );

		await this.clickButtonWithText( 'Save' );
	}
}
