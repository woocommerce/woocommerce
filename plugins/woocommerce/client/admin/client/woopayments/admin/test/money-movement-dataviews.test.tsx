/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { WooPaymentsMoneyMovementDataViews } from '../money-movement/dataviews';

const mockDataViews = jest.fn(
	( props: {
		header?: ReactNode;
		data?: Array< { id: string } >;
		isLoading?: boolean;
	} ) => (
		<div
			data-testid="mock-dataviews"
			aria-busy={ props.isLoading ? 'true' : 'false' }
		>
			{ props.header }
			{ props.data?.map( ( item ) => (
				<div key={ item.id }>{ item.id }</div>
			) ) }
		</div>
	)
);

jest.mock( '@wordpress/dataviews/wp', () => ( {
	DataViews: ( props: Record< string, unknown > ) =>
		mockDataViews( props as { data?: Array< { id: string } > } ),
} ) );

describe( 'WooPaymentsMoneyMovementDataViews', () => {
	beforeEach( () => {
		mockDataViews.mockClear();
	} );

	it( 'passes server-owned DataViews state through a thin wrapper', () => {
		const fields = [ { id: 'date', label: 'Date' } ];
		const rows = [ { id: 'txn_1', date: '2026-06-19' } ];
		const view = {
			type: 'table',
			page: 2,
			perPage: 25,
			search: 'Ada',
			fields: [ 'date' ],
		};
		const onChangeView = jest.fn();

		render(
			<WooPaymentsMoneyMovementDataViews
				fields={ fields }
				rows={ rows }
				view={ view }
				onChangeView={ onChangeView }
				total={ 52 }
				isLoading={ false }
				header={ <h2>Transactions</h2> }
				toolbarActions={
					<button type="button">Download transactions</button>
				}
				searchLabel="Search transactions"
			/>
		);

		expect(
			screen.getByRole( 'heading', { name: 'Transactions' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Download transactions' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'txn_1' ) ).toBeInTheDocument();
		expect( mockDataViews ).toHaveBeenCalledWith(
			expect.objectContaining( {
				fields,
				data: rows,
				view,
				onChangeView,
				isLoading: false,
				search: true,
				searchLabel: 'Search transactions',
				paginationInfo: {
					totalItems: 52,
					totalPages: 3,
				},
			} )
		);
	} );

	it( 'keeps loading and empty states in stable semantic regions', () => {
		const { rerender } = render(
			<WooPaymentsMoneyMovementDataViews
				fields={ [ { id: 'date', label: 'Date' } ] }
				rows={ [] }
				view={ {
					type: 'table',
					page: 1,
					perPage: 25,
					fields: [ 'date' ],
				} }
				onChangeView={ jest.fn() }
				total={ 0 }
				isLoading
				loadingMessage="Loading transactions"
				empty={ <p>No transactions found.</p> }
				searchLabel="Search transactions"
			/>
		);

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading transactions'
		);

		rerender(
			<WooPaymentsMoneyMovementDataViews
				fields={ [ { id: 'date', label: 'Date' } ] }
				rows={ [] }
				view={ {
					type: 'table',
					page: 1,
					perPage: 25,
					fields: [ 'date' ],
				} }
				onChangeView={ jest.fn() }
				total={ 0 }
				isLoading={ false }
				loadingMessage="Loading transactions"
				empty={ <p>No transactions found.</p> }
				searchLabel="Search transactions"
			/>
		);

		expect(
			screen.getByText( 'No transactions found.' )
		).toBeInTheDocument();
	} );
} );
