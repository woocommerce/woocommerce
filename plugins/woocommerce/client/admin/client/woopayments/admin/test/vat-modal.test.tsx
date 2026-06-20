/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { WooPaymentsVatModal } from '../documents/vat-modal';
import {
	saveWooPaymentsVatDetails,
	validateWooPaymentsVatNumber,
} from '../documents/data';

jest.mock( '../documents/data', () => ( {
	saveWooPaymentsVatDetails: jest.fn(),
	validateWooPaymentsVatNumber: jest.fn(),
} ) );

const mockSaveVat = saveWooPaymentsVatDetails as jest.MockedFunction<
	typeof saveWooPaymentsVatDetails
>;
const mockValidateVat = validateWooPaymentsVatNumber as jest.MockedFunction<
	typeof validateWooPaymentsVatNumber
>;

describe( 'WooPaymentsVatModal', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockValidateVat.mockResolvedValue( {
			valid: true,
			vat_number: 'DE123456789',
			name: 'Ada Bakery',
			address: '1 Market Street',
			country_code: 'DE',
		} );
		mockSaveVat.mockResolvedValue( {
			vat_number: 'DE123456789',
			name: 'Ada Bakery',
			address: '2 Market Street',
		} );
	} );

	it( 'keeps focus on the edited tax details field while details are updated', async () => {
		const onCompleted = jest.fn();

		render(
			<WooPaymentsVatModal
				country="DE"
				onClose={ jest.fn() }
				onCompleted={ onCompleted }
			/>
		);

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'I have a valid VAT Number',
			} )
		);
		await userEvent.type(
			screen.getByLabelText( 'VAT Number' ),
			'DE123456789'
		);
		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Continue' } )
			);
		} );

		const businessName = await screen.findByLabelText( 'Business name' );
		expect( businessName ).toHaveValue( 'Ada Bakery' );
		expect( businessName ).toHaveFocus();

		const address = screen.getByLabelText( 'Address' );
		await userEvent.clear( address );
		await userEvent.type( address, '2 Market Street' );

		expect( address ).toHaveFocus();

		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Confirm' } )
			);
		} );

		await waitFor( () =>
			expect( mockSaveVat ).toHaveBeenCalledWith( {
				vat_number: 'DE123456789',
				name: 'Ada Bakery',
				address: '2 Market Street',
			} )
		);
		expect( onCompleted ).toHaveBeenCalledWith( {
			vat_number: 'DE123456789',
			name: 'Ada Bakery',
			address: '2 Market Street',
		} );
	} );
} );
