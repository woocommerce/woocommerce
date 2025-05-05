/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { WooPaymentsPostSandboxAccountSetupModal } from '..';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

describe( 'WooPaymentsPostSandboxAccountSetupModal', () => {
	it( 'should record settings_payments_switch_to_live_account_click event when Activate Payments button is clicked', () => {
		const { getByRole } = render(
			<WooPaymentsPostSandboxAccountSetupModal
				isOpen={ true }
				devMode={ false }
				onClose={ jest.fn() }
			/>
		);

		const activatePaymentsButton = getByRole( 'button', {
			name: 'Activate payments',
		} );
		fireEvent.click( activatePaymentsButton );

		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_switch_to_live_account_click',
			{
				provider_id: 'woocommerce_payments',
			}
		);
	} );

	it( 'should record settings_payments_continue_store_setup_click event when Continue Store Setup button is clicked', async () => {
		const { getByRole } = render(
			<WooPaymentsPostSandboxAccountSetupModal
				isOpen={ true }
				devMode={ false }
				onClose={ jest.fn() }
			/>
		);

		const continueStoreSetupButton = getByRole( 'button', {
			name: 'Continue store setup',
		} );
		fireEvent.click( continueStoreSetupButton );

		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_continue_store_setup_click',
			{
				provider_id: 'woocommerce_payments',
			}
		);
	} );
} );
