/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { WooPaymentsDisputeChallengePage } from '../money-movement/dispute-challenge-page';
import { getWooPaymentsDispute } from '../money-movement/data';

jest.mock( '../money-movement/data', () => ( {
	getWooPaymentsDispute: jest.fn(),
} ) );

const mockGetDispute = getWooPaymentsDispute as jest.MockedFunction<
	typeof getWooPaymentsDispute
>;

describe( 'WooPaymentsDisputeChallengePage', () => {
	beforeEach( () => {
		mockGetDispute.mockReset();
	} );

	it( 'loads the dispute and fails closed for evidence submission', async () => {
		mockGetDispute.mockResolvedValue( {
			id: 'dp_test',
			reason: 'fraudulent',
			status: 'needs_response',
		} );

		const { container } = render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/disputes/challenge?id=dp_test',
				] }
			>
				<WooPaymentsDisputeChallengePage />
			</MemoryRouter>
		);

		expect( await screen.findByText( 'dp_test' ) ).toBeInTheDocument();
		expect(
			container.querySelector(
				'.woocommerce-woopayments-money-movement__notice p'
			)
		).toHaveTextContent(
			'Dispute evidence submission is not available in this native WooPayments admin surface yet.'
		);
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Dispute evidence submission is not available in this native WooPayments admin surface yet.'
		);
		expect( mockGetDispute ).toHaveBeenCalledWith( 'dp_test' );
	} );
} );
